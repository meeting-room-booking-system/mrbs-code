#!/usr/bin/env bash
# Runs the functional tests in this directory against a throwaway MySQL and MRBS Docker setup.
#
#   test/ci/run-local.sh [pytest arguments]
#
# Environment:
#   PHP_VERSION  PHP version of the MRBS image (default 8.4)
#   MYSQL_IMAGE  MySQL image to use (default mysql:8.4)
#   MRBS_PORT    Host port for the web server (default 8080)
#   KEEP         Set to 1 to leave the containers running afterwards, e.g. to browse the site
set -euo pipefail

cd "$(dirname "$0")/../.."

PHP_VERSION=${PHP_VERSION:-8.4}
MYSQL_IMAGE=${MYSQL_IMAGE:-mysql:8.4}
MRBS_PORT=${MRBS_PORT:-8080}
KEEP=${KEEP:-0}

NETWORK=mrbs-test
DB=mrbs-test-db
APP=mrbs-test-app
NOBASE=mrbs-test-nobase
NOBASE_PORT=$((MRBS_PORT + 1))
IMAGE=mrbs-test:php$PHP_VERSION
VENV=${TMPDIR:-/tmp}/mrbs-test-venv

cleanup() {
  if [[ $KEEP != 1 ]]; then
    docker rm -f "$APP" "$NOBASE" "$DB" >/dev/null 2>&1 || true
    docker network rm "$NETWORK" >/dev/null 2>&1 || true
  fi
  find test/ci -name __pycache__ -prune -exec rm -rf {} + 2>/dev/null || true
}
trap cleanup EXIT

if [[ ! -x $VENV/bin/pytest ]]; then
  python3 -m venv "$VENV"
  "$VENV/bin/pip" install --quiet -r test/ci/requirements.txt
fi

docker rm -f "$APP" "$NOBASE" "$DB" >/dev/null 2>&1 || true
docker network rm "$NETWORK" >/dev/null 2>&1 || true
docker network create "$NETWORK" >/dev/null

echo "Starting $MYSQL_IMAGE"
# mysql_native_password is needed by PHP < 7.4 and is disabled by default in MySQL 8.4
docker run -d --name "$DB" --network "$NETWORK" \
  -e MYSQL_ROOT_PASSWORD=root -e MYSQL_DATABASE=mrbs -e MYSQL_USER=mrbs -e MYSQL_PASSWORD=mrbs \
  "$MYSQL_IMAGE" --mysql-native-password=ON >/dev/null

# Check over TCP: the server briefly accepts socket connections while it is still initialising
until docker exec "$DB" mysqladmin ping -h127.0.0.1 -uroot -proot --silent >/dev/null 2>&1; do sleep 2; done

mysql_root() { docker exec -i "$DB" mysql -uroot -proot "$@" 2>/dev/null; }
mysql_root mrbs < tables.my.sql
mysql_root -e "ALTER USER 'mrbs'@'%' IDENTIFIED WITH mysql_native_password BY 'mrbs'"
mysql_root mrbs < test/ci/fixtures.my.sql

echo "Building MRBS with PHP $PHP_VERSION"
docker build --quiet --build-arg PHP_VERSION="$PHP_VERSION" -t "$IMAGE" . >/dev/null

run_app() { # name port extra-env...
  local name=$1 port=$2
  shift 2
  docker run -d --name "$name" --network "$NETWORK" -p "$port:80" \
    -v "$PWD/test/ci/php-test.ini:/usr/local/etc/php/conf.d/zz-ci.ini:ro" \
    -e MRBS_DB_SYSTEM=mysql -e MRBS_DB_HOST="$DB" -e MRBS_DB_DATABASE=mrbs \
    -e MRBS_DB_USER=mrbs -e MRBS_DB_PASSWORD=mrbs "$@" \
    "$IMAGE" >/dev/null
  until curl -fsS "http://localhost:$port/" >/dev/null 2>&1; do sleep 2; done
}

# The main instance sets $url_base, so password reset is enabled; the nobase instance does not
run_app "$APP" "$MRBS_PORT" -e MRBS_URL_BASE="http://localhost:$MRBS_PORT/"
run_app "$NOBASE" "$NOBASE_PORT"

echo "Running tests against http://localhost:$MRBS_PORT/ and http://localhost:$NOBASE_PORT/"
MRBS_URL="http://localhost:$MRBS_PORT/" MRBS_URL_NOBASE="http://localhost:$NOBASE_PORT/" \
  "$VENV/bin/pytest" test/ci -p no:cacheprovider "$@"

# The nobase instance logs the "No trusted base" warning on every request by design (see AuthDb.php)
test/ci/check-php-log.sh "$APP"
test/ci/check-php-log.sh "$NOBASE" 'No trusted base available'
