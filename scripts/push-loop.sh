#!/bin/sh
# One short PHP process per pass: reconnect safely and never hold an idle PDO transaction.
while true; do
    "${REPOTOOLS_PHP_BINARY:-/usr/local/bin/php}" "${REPOTOOLS_APP_DIR:-/var/www/html}/scripts/push-worker.php"
    sleep 15
done
