# Yii2 Order Flow

Pet-проект для отработки навыков Middle PHP/Yii2 разработчика.
REST API для управления заказами с асинхронной обработкой через RabbitMQ, кэшированием в Redis и распределенными блокировками.

## 🎯 Стек

- **PHP:** 8.4
- **Фреймворк:** Yii2 Advanced
- **База данных:** PostgreSQL 16
- **Брокер сообщений:** RabbitMQ 3.13 (Management UI)
- **Кэш/Блокировки:** Redis 7
- **Веб-сервер:** Nginx + PHP-FPM (Docker)
- **Тесты:** Codeception (Unit + Functional)
- **CI:** GitHub Actions
- **Документация:** Swagger/OpenAPI

## 🏗 Архитектура

```mermaid
graph TD
    Client[Client] -->|HTTP| Nginx
    Nginx --> PHP[PHP-FPM]
    PHP -->|POST /api/orders| Controller[OrderController]
    Controller -->|Create| DB[(PostgreSQL)]
    Controller -->|Push Job| RMQ[RabbitMQ]
    RMQ -->|Consume| Worker[Worker: orders]
    Worker -->|Update Status| DB
    Worker -->|Invalidate| Redis[(Redis Cache)]
    PHP -->|GET /api/orders/{id}| Redis
    Redis -->|Miss| DB

🚀 Запуск
Требования
Docker & Docker Compose

Установка
bash
# 1. Клонировать репозиторий
git clone https://github.com/your-username/yii2-order-flow.git
cd yii2-order-flow

# 2. Скопировать конфиги из .dist
cp common/config/main-local.php.dist common/config/main-local.php
cp common/config/params-local.php.dist common/config/params-local.php
cp common/config/test-local.php.dist common/config/test-local.php
cp frontend/config/main-local.php.dist frontend/config/main-local.php
cp frontend/config/params-local.php.dist frontend/config/params-local.php
cp frontend/config/test-local.php.dist frontend/config/test-local.php
cp console/config/main-local.php.dist console/config/main-local.php
cp console/config/params-local.php.dist console/config/params-local.php

# 3. Поднять контейнеры
docker compose up -d --build

# 4. Установить зависимости
docker compose exec app composer install

# 5. Применить миграции
docker compose exec app php yii migrate --interactive=0

# 6. Сгенерировать Swagger JSON
docker compose exec app php yii swagger/generate
Доступные сервисы
API: http://localhost:20080/api/orders

Swagger UI: http://localhost:20080/api-doc

RabbitMQ UI: http://localhost:15672 (user/password)

PostgreSQL: localhost:5433

🧪 Тесты
bash
# Unit-тесты (common)
docker compose exec app-test vendor/bin/codecept run Unit -c common

# Functional-тесты (frontend)
docker compose exec app-test vendor/bin/codecept run Functional -c frontend
📚 Что реализовано
Очереди (RabbitMQ)
Две очереди: orders (заказы) и notifications (email).

Два воркера: worker и worker-notifications.

Application-Level DLQ: если задача падает 3 раза, она уходит в orders_dead / notifications_dead.

Mutex (Redis Lock): защита от race condition (если два воркера возьмут один заказ).

Кэширование (Redis)
GET /api/orders/{id} кэшируется на 5 минут.

При обновлении заказа (в воркере) кэш инвалидируется.

API
GET /api/orders — список заказов.

GET /api/orders/{id} — заказ по ID (с кэшем).

POST /api/orders — создание заказа (с валидацией и отправкой в очередь).

GET /api/health — проверка работоспособности.

🔧 CI/CD
GitHub Actions прогоняет Unit-тесты при каждом пуше в main.

https://github.com/your-username/yii2-order-flow/actions/workflows/ci.yml/badge.svg