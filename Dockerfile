FROM php:8.2-fpm-alpine
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Installer les outils de gestion des utilisateurs/groupes
RUN apk --no-cache add shadow

RUN addgroup -g 1000 monprof && adduser -u 1000 -G monprof -D monprof
RUN addgroup -g 1001 -S www && adduser -u 1001 -S www -G www
# Installer Supervisor
# RUN apk --no-cache add supervisor

# Installer composer 

RUN php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer

COPY --chown=www:www . /www/html/monprof
# Copier le www/html/monprof de l'application
#COPY . /www/html/monprof

# Copier les configurations PHP
COPY custom-php.ini /usr/local/etc/php/conf.d/

# Copier la configuration Supervisor
  
# COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf


# Définir le répertoire de travail
WORKDIR /www/html/monprof

# Exposer le port 9000 pour PHP-FPM
EXPOSE 9000


# Changer le propriétaire des fichiers de www/html/monprof et donner les permissions nécessaires


RUN chown -R www:www /www/html/monprof/storage /www/html/monprof/bootstrap/cache
RUN chmod -R 777 /www/html/monprof/storage /www/html/monprof/bootstrap/cache


# Démarrer PHP-FPM et Supervisor

CMD ["php-fpm"]
