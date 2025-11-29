<?php
// load .env file to array
$env = parse_ini_file(__DIR__.'/.env');

/*
define ("TEST_DEBUG", 1);
//define ("TEST_DEBUG_CRON", 1);
define ("TEST_DEBUG_DB", 1);
*/

// mysql settings
define ("TEST_DB_HOST", $env["TEST_DB_HOST"]);
define ("TEST_DB_NAME", $env["TEST_DB_NAME"]);
define ("TEST_DB_USER", $env["TEST_DB_USER"]);
define ("TEST_DB_PASSWORD", $env["TEST_DB_PASSWORD"]);
define ("TEST_DB_PORT", $env["TEST_DB_PORT"]);
// memcached host & port
define ("TEST_MC_HOST", $env["TEST_MC_HOST"]);
define ("TEST_MC_PORT", $env["TEST_MC_PORT"]);
// RSS URL
define ("TEST_RSS_URL", $env["TEST_RSS_URL"]);
// path for index script
define ("TEST_PATH", $env["TEST_PATH"]);
// max cache time in seconds (time between cron runs - default 300, 5 minutes)
define ("TEST_MAX_CACHE_TIME", $env["TEST_MAX_CACHE_TIME"]);
// max number of days to show
define ("TEST_MAX_DAYS", $env["TEST_MAX_DAYS"]);
