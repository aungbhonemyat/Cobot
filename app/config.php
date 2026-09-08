<?php
session_start();

define('APP_ROOT', dirname(__DIR__));
define('DB_PATH', APP_ROOT . '/storage/database.sqlite');
define('UPLOAD_DIR', APP_ROOT . '/public/uploads/items/');
define('APP_URL', 'http://localhost:8000');
// Currency settings
define('CURRENCY', 'MMK');
// Example: USD to MMK exchange rate (adjust as needed)
define('EXCHANGE_RATE_USD_TO_MMK', 2100);
