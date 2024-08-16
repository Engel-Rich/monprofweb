FROM php:8.2-fpm-alpine
RUN docker-php-ext-install mysqli pdo pdo_mysql

RUN apk --no-cache add shadow

RUN addgroup -g 1000 monprof && adduser -u 1000 -G monprof -D monprof
RUN addgroup -g 1001 -S www && adduser -u 1001 -S www -G www


RUN php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer

COPY --chown=www:www . /www/html/monprof

COPY custom-php.ini /usr/local/etc/php/conf.d/


WORKDIR /www/html/monprof

EXPOSE 9000



RUN chown -R www:www /www/html/monprof/storage /www/html/monprof/bootstrap/cache
RUN chmod -R 777 /www/html/monprof/storage /www/html/monprof/bootstrap/cache


CMD ["php-fpm"]
