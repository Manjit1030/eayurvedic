<?php
// app/core/config.php

// ==============================
// BASE CONFIG
// ==============================

define('BASE_URL', 'http://localhost/eayurvedic');

define('APP_NAME', 'eAyurvedic');
define('APP_DEBUG', true);

// ==============================
// DATABASE CONFIG
// ==============================

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'eayurvedic');
define('DB_USER', 'root');
define('DB_PASS', ''); // XAMPP default is empty

// ==============================
// ALGORITHM SWITCHES (DEMO)
// ==============================

// Master switch: if false -> ALL algorithms behave like "without algorithm"
define('ALGO_ENABLED', true);

// Individual switches: control algorithms one-by-one
define('ALGO_CART_TOTALS', true);
define('ALGO_SEVERITY_SCORE', true);
define('ALGO_SYMPTOM_MATCH', true);
define('ALGO_PRODUCT_RECOMMEND', true);
