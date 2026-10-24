FROM yiisoftware/yii2-php:8.4-fpm-nginx

WORKDIR /app

RUN docker-php-ext-install sockets

RUN docker-php-ext-enable opcache

RUN docker-php-ext-enable xdebug || true \
 && echo "xdebug.mode=off" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
 && echo "xdebug.start_with_request=trigger" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
 && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
 && echo "xdebug.client_port=9005" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini