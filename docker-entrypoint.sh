#!/bin/bash
# Write env vars as Apache SetEnv directives so PHP can read them via getenv()
cat > /etc/apache2/conf-available/env-vars.conf << EOF
SetEnv DB_HOST "${DB_HOST}"
SetEnv DB_PORT "${DB_PORT}"
SetEnv DB_USER "${DB_USER}"
SetEnv DB_PASS "${DB_PASS}"
SetEnv DB_NAME "${DB_NAME}"
SetEnv DB_SSL_CA "${DB_SSL_CA}"
SetEnv BASE_URL "${BASE_URL}"
EOF

a2enconf env-vars

# Run migrations
if [ -n "$DB_HOST" ] && [ -n "$DB_USER" ]; then
    for f in /var/www/html/database/*.sql; do
        mysql -h"$DB_HOST" -P"${DB_PORT:-3306}" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$f" 2>/dev/null || true
    done
fi

exec apache2-foreground
