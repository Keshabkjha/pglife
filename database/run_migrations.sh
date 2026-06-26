#!/bin/bash
set -e

echo "Applying incremental database migrations..."

for migration in /migrations/[0-9][0-9]_*.sql; do
    if [ -f "$migration" ]; then
        echo "Running $(basename "$migration")"
        mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE" < "$migration"
    fi
done

echo "Database migrations complete."
