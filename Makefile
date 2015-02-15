help:
	@echo 'Usage: make (catalog)'

catalog:
	php src/catalog.php
.PHONY: catalog

test:
	php test/phpunit.phar test/*_test.php
.PHONY: test
