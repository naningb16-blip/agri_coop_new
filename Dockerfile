FROM php:8.2-apache

# Enable mod_rewrite
RUN a2enmod rewrite

# Install mysqli extension and mysql client
RUN docker-php-ext-install mysqli

# Configure PHP to read environment variables
RUN echo "variables_order = EGPCS" >> /usr/local/etc/php/php.ini \
    && echo "request_order = GP" >> /usr/local/etc/php/php.ini

# Set document root to /var/www/html/public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Allow .htaccess overrides
RUN printf '<Directory /var/www/html/public>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>\n' \
    >> /etc/apache2/apache2.conf

# Copy project files
COPY . /var/www/html/

# Ensure CA cert is readable
RUN if [ -f /var/www/html/config/aiven-ca.pem ]; then chmod 644 /var/www/html/config/aiven-ca.pem; fi

# Create uploads directory
RUN mkdir -p /var/www/html/public/uploads/documents \
    && chown -R www-data:www-data /var/www/html/public/uploads

# Startup script: writes env vars into Apache config then starts Apache
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN apt-get update && apt-get install -y dos2unix default-mysql-client \
    && dos2unix /usr/local/bin/docker-entrypoint.sh \
    && chmod +x /usr/local/bin/docker-entrypoint.sh \
    && apt-get remove -y dos2unix && apt-get autoremove -y && rm -rf /var/lib/apt/lists/*

EXPOSE 80

# v5 — force redeploy
CMD ["/usr/local/bin/docker-entrypoint.sh"]
