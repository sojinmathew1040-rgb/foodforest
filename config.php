<?php
// =========================================================================
// Food Forest Sanctuary — Database & Application Configuration
// =========================================================================

// Database Connection Configuration (XAMPP Defaults)
// You can edit the parameters below to connect to your database.
defined('DB_HOST') or define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
defined('DB_PORT') or define('DB_PORT', getenv('DB_PORT') ?: '3306');
defined('DB_NAME') or define('DB_NAME', getenv('DB_NAME') ?: 'foodforest_db');
defined('DB_USER') or define('DB_USER', getenv('DB_USER') ?: 'root');
defined('DB_PASS') or define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
defined('DB_CHARSET') or define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');
