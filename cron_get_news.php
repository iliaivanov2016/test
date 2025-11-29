<?php

// block run from browser
if ((php_sapi_name() !== 'cli')) die;

require_once __DIR__ . "/api/api.php";

TestAPI::news_cron();