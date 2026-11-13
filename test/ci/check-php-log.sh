#!/usr/bin/env bash
# Fails if the PHP error log of a container contains a PHP error or warning.
# The test containers run with display_errors off (see php-test.ini), so this log is the only place errors show up.
#
#   test/ci/check-php-log.sh CONTAINER [EXPECTED_REGEX]
#
# EXPECTED_REGEX, if given, names messages that are allowed in the log.
set -euo pipefail

container=$1
expected=${2:-}

# Apache prefixes PHP errors with [php:level]; PHP's own log format uses "PHP Warning:" etc.
pattern='\[php:|PHP (Warning|Notice|Deprecated|Fatal error|Parse error):'

errors=$(docker logs "$container" 2>&1 | grep -E "$pattern" || true)
if [[ -n $expected ]]; then
  errors=$(grep -vE "$expected" <<< "$errors" || true)
fi

if [[ -n $errors ]]; then
  echo "PHP errors in the log of $container:" >&2
  echo "$errors" >&2
  exit 1
fi
echo "No unexpected PHP errors in the log of $container"
