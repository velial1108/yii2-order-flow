swagger:
	docker compose exec app php yii swagger/generate

up:
	docker compose up -d

down:
	docker compose down