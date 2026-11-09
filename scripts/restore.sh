#!/bin/sh
set -eu
if [ "$#" -ne 1 ] || [ ! -s "$1" ]; then echo 'Usage: scripts/restore.sh BACKUP.sql' >&2; exit 1; fi
restore_prefix="${1%.sql}"
[ -s "$restore_prefix-uploads.tar.gz" ] || { echo 'Matching attachment archive required.' >&2; exit 1; }
[ -s "$restore_prefix.sha256" ] || { echo 'Checksum manifest required.' >&2; exit 1; }
sha256sum -c "$restore_prefix.sha256"
printf 'This replaces database contents. Type RESTORE: '
read -r restore_answer
[ "$restore_answer" = RESTORE ] || exit 1
docker compose stop web scheduler
trap 'docker compose start web scheduler >/dev/null' EXIT
docker compose exec -T db sh -c 'MYSQL_PWD="$MARIADB_PASSWORD" exec mariadb -u"$MARIADB_USER" "$MARIADB_DATABASE"' < "$1"
docker compose exec -T php tar -xzf - -C /var/www/html/storage < "$restore_prefix-uploads.tar.gz"
echo 'Restore completed. Verify login, room records and private attachments.'
