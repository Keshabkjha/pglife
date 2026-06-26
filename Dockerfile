FROM php:8.2-apache

# Install mysqli, zip extension and zip utilities for composer
RUN apt-get update && apt-get install -y \
        libzip-dev \
        zip \
        unzip \
        git \
    && docker-php-ext-install mysqli zip \
    && docker-php-ext-enable mysqli zip

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Enable Apache modules
RUN a2enmod rewrite headers

# Allow .htaccess overrides in the web root
RUN sed -i 's|AllowOverride None|AllowOverride All|g' /etc/apache2/apache2.conf
RUN echo '<Directory /var/www/html>\n    AllowOverride All\n    Options Indexes FollowSymLinks\n    Require all granted\n</Directory>' >> /etc/apache2/apache2.conf
