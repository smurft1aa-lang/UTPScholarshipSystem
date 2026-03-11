.PHONY: up down logs test setup dev

up:
	docker-compose up -d --build

dev:
	docker-compose --profile debug up -d --build

down:
	docker-compose down -v

logs:
	docker-compose logs -f app

test:
	./vendor/bin/phpunit

setup:
	cp .env.example .env
	docker-compose up -d
