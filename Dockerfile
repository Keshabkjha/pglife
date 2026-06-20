FROM php:8.2-apache

# Install mysqli and mbstring extensions
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Enable Apache rewrite module
RUN a2enmod rewrite

# Allow .htaccess overrides in the web root
RUN sed -i 's|AllowOverride None|AllowOverride All|g' /etc/apache2/apache2.conf
RUN echo '<Directory /var/www/html>\n    AllowOverride All\n    Options Indexes FollowSymLinks\n    Require all granted\n</Directory>' >> /etc/apache2/apache2.conf
