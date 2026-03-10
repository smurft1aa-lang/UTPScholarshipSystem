.PHONY: up down logs test setup

up:
	docker-compose up -d --build

down:
	docker-compose down -v

logs:
	docker-compose logs -f app

test:
	./vendor/bin/phpunit

setup:
	cp .env.example .env
	docker-compose up -d
