<?php

// */5 * * * * cd /var/www/ttbgrossist.com/test && php -f cron_get_news.php
/*
cd /var/www/ttbgrossist.com/test
php -f cron_get_news.php
  
telnet localhost 11211
flush_all
quit
 */

// block run from browser
if ((php_sapi_name() !== 'cli')) die;

require_once __DIR__ . "/api/api.php";

TestAPI::get_news();