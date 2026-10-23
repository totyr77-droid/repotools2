#!/bin/sh
set -eu
# Render may override CMD. Keep the worker in ENTRYPOINT so that Apache overrides do not skip it.
app_dir="${REPOTOOLS_APP_DIR:-/var/www/html}"
case "${1:-}" in
    php|php-fpm|composer) ;;
    *)
        if [ "$(id -u)" = "0" ]; then
            mkdir -p /var/lib/repotools
            chown www-data:www-data /var/lib/repotools
        fi
        if [ "${REPOTOOLS_PUSH_WORKER:-1}" = "1" ]; then
            echo 'Repotools push: starting automatic delivery loop' >&2
            if [ "$(id -u)" = "0" ]; then
                setpriv --reuid=www-data --regid=www-data --init-groups sh "$app_dir/scripts/push-loop.sh" &
            else
                sh "$app_dir/scripts/push-loop.sh" &
            fi
        else
            echo 'Repotools push: automatic delivery disabled by configuration' >&2
        fi
        ;;
esac
exec docker-php-entrypoint "$@"
