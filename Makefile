.PHONY: run test check check-code clean zip z prepare-db migrate fixtures reset-db schema-update

PROJECT_NAME=FitnessTracker
ZIP_NAME=$(PROJECT_NAME)_handoff.zip

run:
	symfony server:start

test:
	php bin/phpunit

check-code:
	php bin/console about
	php bin/console debug:router
	php bin/phpunit

check:
	php bin/console about
	php bin/console debug:router
	php bin/console doctrine:schema:validate
	php bin/phpunit

prepare-db:
	powershell -NoProfile -ExecutionPolicy Bypass -Command "New-Item -ItemType Directory -Force var\data | Out-Null"

schema-update: prepare-db
	php bin/console doctrine:schema:update --force

migrate: prepare-db
	php bin/console doctrine:migrations:migrate --no-interaction

fixtures:
	php bin/console doctrine:fixtures:load --no-interaction

reset-db:
	powershell -NoProfile -ExecutionPolicy Bypass -Command "if (Test-Path var\data\app.db) { Remove-Item var\data\app.db -Force }"
	$(MAKE) prepare-db
	php bin/console doctrine:schema:create
	$(MAKE) fixtures
	php bin/console doctrine:schema:validate
	php bin/phpunit

clean:
	powershell -NoProfile -ExecutionPolicy Bypass -Command "if (Test-Path var\cache) { Remove-Item var\cache -Recurse -Force }"
	powershell -NoProfile -ExecutionPolicy Bypass -Command "if (Test-Path var\log) { Remove-Item var\log -Recurse -Force }"

zip: clean
	powershell -NoProfile -ExecutionPolicy Bypass -Command " \
		$$items = @(); \
		foreach ($$p in @('composer.json','composer.lock','config','src','templates','public','tests','.env','phpunit.dist.xml','phpunit.xml.dist','migrations','docs','Makefile')) { \
			if (Test-Path $$p) { $$items += $$p } \
		}; \
		if (Test-Path '$(ZIP_NAME)') { Remove-Item '$(ZIP_NAME)' -Force }; \
		Compress-Archive -Path $$items -DestinationPath '$(ZIP_NAME)' -Force \
	"

z: zip
