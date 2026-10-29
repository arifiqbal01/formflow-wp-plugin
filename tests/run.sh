#!/usr/bin/env bash
# Requires PHP + mysqli, a disposable MariaDB database, and a local WordPress source tree.
set -euo pipefail
: "${WP_TEST_ROOT:?Set WP_TEST_ROOT to a WordPress checkout}"
: "${FORMFLOW_TEST_DB_HOST:=127.0.0.1:33077}"
: "${FORMFLOW_TEST_DB_NAME:=formflow_test}"
: "${FORMFLOW_TEST_DB_USER:=root}"
: "${FORMFLOW_TEST_DB_PASSWORD:=}"
export FORMFLOW_TEST_DB_HOST FORMFLOW_TEST_DB_NAME FORMFLOW_TEST_DB_USER FORMFLOW_TEST_DB_PASSWORD
php "$(dirname "$0")/payments.php"
node "$(dirname "$0")/checkout.test.js"
