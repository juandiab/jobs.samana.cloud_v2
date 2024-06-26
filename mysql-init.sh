#!/bin/bash
set -e

# Wait for MySQL to be ready
until mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" -h"$MYSQL_HOST" -e "select 1" &>/dev/null; do
  echo "Waiting for MySQL to be ready..."
  sleep 2
done

# Check if the database is empty
if [ -z "$(mysql -u$MYSQL_USER -p$MYSQL_PASSWORD -h$MYSQL_HOST -D$MYSQL_DATABASE -e 'SHOW TABLES;')" ]; then
  echo "Database is empty. Importing SQL file..."
  mysql -u$MYSQL_USER -p$MYSQL_PASSWORD -h$MYSQL_HOST $MYSQL_DATABASE < /docker-entrypoint-initdb.d/jobs_samana_db.sql
else
  echo "Database is not empty. Skipping SQL file import."
fi

exec "$@"
