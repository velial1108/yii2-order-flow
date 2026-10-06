FROM yiisoftware/yii2-php:8.4-fpm-nginx

# Устанавливаем рабочую директорию (совпадает с volume в compose)
WORKDIR /app

# Включаем opcache (для скорости)
RUN docker-php-ext-enable opcache

# Xdebug уже установлен в базовом образе — включаем без установки
RUN docker-php-ext-enable xdebug || true