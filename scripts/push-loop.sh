#!/bin/sh
# One short PHP process per pass: reconnect safely and never hold an idle PDO transaction.
while true; do
    php /var/www/html/scripts/push-worker.php
    sleep 60
done
