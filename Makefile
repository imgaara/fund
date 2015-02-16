help:
	@echo 'Usage: make (catalog|info|history|test)'

catalog:
	php src/catalog.php
.PHONY: catalog

info:
	php src/info.php
.PHONY: info

history:
	php src/history.php
.PHONY: history

test:
	for file in test/*_test.php; do \
	  echo $$file >&2; \
	  php test/phpunit.phar $$file; \
	done
.PHONY: test
