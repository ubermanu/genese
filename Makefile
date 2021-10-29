build:
	rm .phpunit* -rf
	rm vendor -rf
	composer install -o
	php box.phar compile
