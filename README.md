db creation script located in api/db.sql

config file is api/.env

to install create new mysql database and run  api/db.sql
after that update .env file with config setting for mysql (TEST_DB_...) and memcached (TEST_MC_...)

set path to folder with index.php file to TEST_PATH in .env file ( like "/test", or "/" )

setup cron job for getting news from RSS this way (replace /var/www/ttbgrossist.com/test with your install path):

*/5 * * * * cd /var/www/ttbgrossist.com/test && php -f cron_get_news.php

Requirements:
PHP7+, MySQL

PHP Extensions:
PDO
memcache
SimpleXML
