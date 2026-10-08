.PHONY: setup up stop install build dev migrate seed test

setup:
	$(MAKE) up
	$(MAKE) install
	$(MAKE) build
	$(MAKE) migrate
	$(MAKE) seed

up:
	docker compose up -d --build --wait

stop:
	docker compose stop

install:
	docker compose run --rm --no-deps php composer install --no-interaction
	npm ci

build:
	npm run build

dev:
	npm run dev

migrate:
	docker compose exec php php bin/migrate.php

seed:
	docker compose exec php php bin/seed.php

test:
	docker compose -f compose.yaml -f docker-compose.test.yml run --rm test
