FROM php:8.2-fpm-alpine

# Installer les extensions PHP nécessaires
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Ajouter les groupes et utilisateurs
RUN apk --no-cache add shadow

RUN addgroup -g 1000 monprof && adduser -u 1000 -G monprof -D monprof

RUN addgroup -g 1001 -S www && adduser -u 1001 -S www -G www

# Installer Supervisor
RUN apk --no-cache add supervisor

# Installer Composer
RUN php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer

# Copier les fichiers du projet dans le conteneur
COPY --chown=www:www . /www/html/monprof

# Copier la configuration PHP personnalisée
COPY custom-php.ini /usr/local/etc/php/conf.d/

# Définir le répertoire de travail avant d'exécuter Composer
WORKDIR /www/html/monprof

# Installer les dépendances Composer
RUN composer install --no-dev --optimize-autoloader

# Exécuter les migrations
RUN php artisan migrate

# Configuration de Supervisor
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Exposer le port
EXPOSE 9000

# Ajuster les permissions
RUN chown -R www:www /www/html/monprof/storage /www/html/monprof/bootstrap/cache
RUN chmod -R 777 /www/html/monprof/storage /www/html/monprof/bootstrap/cache