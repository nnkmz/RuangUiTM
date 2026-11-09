#!/bin/sh
set -eu
umask 077
mkdir -p backups
backup_prefix="backups/fasiliti-$(date -u +%Y%m%dT%H%M%SZ)"
docker compose exec -T db sh -c 'MYSQL_PWD="$MARIADB_PASSWORD" exec mariadb-dump --single-transaction --routines --triggers -u"$MARIADB_USER" "$MARIADB_DATABASE"' > "$backup_prefix.sql"
[ -s "$backup_prefix.sql" ] || { echo 'Empty database backup.' >&2; exit 1; }
# Attachment bytes are immutable: copying after the DB snapshot retains all referenced files.
docker compose exec -T php tar -czf - -C /var/www/html/storage uploads > "$backup_prefix-uploads.tar.gz"
[ -s "$backup_prefix-uploads.tar.gz" ] || { echo 'Empty attachment backup.' >&2; exit 1; }
sha256sum "$backup_prefix.sql" "$backup_prefix-uploads.tar.gz" > "$backup_prefix.sha256"
printf 'Backup completed: %s.sql and attachment archive\n' "$backup_prefix"
