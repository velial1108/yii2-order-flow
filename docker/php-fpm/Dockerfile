FROM yiisoftware/yii2-php:8.4-fpm-nginx

WORKDIR /app

RUN docker-php-ext-install sockets

RUN docker-php-ext-enable opcache

RUN docker-php-ext-enable xdebug || true
RUN echo "xdebug.mode=off" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini