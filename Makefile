.PHONY: help up down reset logs ps migrate seed test backup restore

help:
	@printf "Comandos disponibles:\n"
	@printf "  make up       -> levanta servicios\n"
	@printf "  make down     -> baja servicios\n"
	@printf "  make reset    -> baja y elimina volumenes\n"
	@printf "  make logs     -> sigue logs\n"
	@printf "  make ps       -> estado de servicios\n"
	@printf "  make migrate  -> ejecuta migraciones\n"
	@printf "  make seed     -> ejecuta seeders\n"
	@printf "  make test     -> ejecuta tests\n"
	@printf "  make backup   -> backup de base de datos\n"
	@printf "  make restore FILE=backups/archivo.sql.gz -> restaura backup\n"

up:
	docker compose up -d --build

down:
	docker compose down

reset:
	docker compose down -v

logs:
	docker compose logs -f

ps:
	docker compose ps

migrate:
	docker compose exec php php artisan migrate

seed:
	docker compose exec php php artisan db:seed

test:
	docker compose exec php php artisan test

backup:
	./scripts/backup_db.sh

restore:
	@if [ -z "$(FILE)" ]; then \
		printf "Debes indicar FILE=backups/<archivo.sql.gz>\n"; \
		exit 1; \
	fi
	./scripts/restore_db.sh "$(FILE)"
