all: clean test

test:
	php scripts/check-rename-complete.php
	php scripts/check-cluster-pins.php
	vendor/bin/phpunit --colors=never --do-not-cache-result

coverage:
	vendor/bin/phpunit --coverage-html=artifacts/coverage

view-coverage:
	open artifacts/coverage/index.html

clean:
	rm -rf artifacts/*
