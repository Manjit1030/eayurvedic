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


// ==============================
// KHALTI PAYMENT KEYS (TEST)
// ==============================
define('KHALTI_PUBLIC_KEY', '31ed4461eb0d4ba8af6a8f078a999b7d');
define('KHALTI_SECRET_KEY', '819a4bb66d804f8c86c7f2f38ac32713');
define('KHALTI_INITIATE_URL', 'https://dev.khalti.com/api/v2/epayment/initiate/');
define('KHALTI_LOOKUP_URL', 'https://dev.khalti.com/api/v2/epayment/lookup/');
