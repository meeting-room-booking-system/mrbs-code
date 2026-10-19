# Functional tests

These tests drive a running MRBS instance over HTTP and are run by the GitHub Actions workflow
in `.github/workflows/docker-image.yml`. They need a MySQL database loaded with
`tables.my.sql` and then `fixtures.my.sql`.

## Running locally with Docker

Requires Docker, Python 3 and `curl`.

    test/ci/run-local.sh

This starts MySQL, loads the schema and fixtures, builds and starts MRBS from the working tree,
runs the tests, and then removes the containers. Anything after the script name is passed to
pytest:

    test/ci/run-local.sh -k Areas -x
    test/ci/run-local.sh test/ci/test_views.py

Options are set with environment variables:

| Variable      | Default     | Purpose |
|---------------|-------------|---------|
| `PHP_VERSION` | `8.4`       | PHP version of the MRBS image (CI tests 7.2 and 8.2 to 8.5) |
| `MYSQL_IMAGE` | `mysql:8.4` | MySQL image |
| `MRBS_PORT`   | `8080`      | Host port for the web server |
| `KEEP`        | `0`         | `1` leaves the containers running afterwards |

For example, `PHP_VERSION=7.2 test/ci/run-local.sh`.

With `KEEP=1` the site stays up at `http://localhost:8080/` and you can log in as `admin`,
`alice` or `bob` (password `testpass`). The next run removes the old containers first.

## Running against an existing instance

The tests only need a URL, so they can run against any instance loaded with the fixtures:

    pip install -r test/ci/requirements.txt
    MRBS_URL=http://localhost:8080/ pytest test/ci

The tests create and delete their own data, but they assume the fixtures are in place, so
reload the database if a run is interrupted part-way through.

## Layout

* `fixtures.my.sql` - users, areas, rooms and bookings. The bookings are in January 2030 (UTC),
  so the tests use fixed dates.
* `helpers.py` - the HTTP client, a form parser and shared page helpers.
* `conftest.py` - the `anon`, `alice` and `admin` fixtures.
* `test_*.py` - the tests, one file per feature.
