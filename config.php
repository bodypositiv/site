<?php
error_reporting(1);
ini_set('display_errors', 1);

// Настройки БД
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASSWORD', 'q11601160');
define('DB_NAME', 'boommax');

define('SITE_ID', 'bm');
define('DIRSEP', DIRECTORY_SEPARATOR);
define('CORE_DIR', $_SERVER['DOCUMENT_ROOT'] . DIRSEP . 'core');
define('TEMPLATE_DIR', $_SERVER['DOCUMENT_ROOT'] . DIRSEP . 'templates');