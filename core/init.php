<?php
session_start();
header('Content-Type: text/html; charset=UTF8');
require_once (CORE_DIR . DIRSEP . 'core_config.php');
require_once (SYSTEM_DIR . DIRSEP . 'debug.php');
require_once (SYSTEM_DIR . DIRSEP . 'router.php');
require_once (SYSTEM_DIR . DIRSEP . 'view.php');
require_once (SYSTEM_DIR . DIRSEP . 'controller_base.php');
require_once (SYSTEM_DIR . DIRSEP . 'db.php');

function __autoload($className){
    $fileName = strtolower($className) . '.php';
    $file = CLASSES_DIR . DIRSEP . $fileName;

    if (strpos($fileName, 'model_') !== false ) {
        $file = MODELS_DIR . DIRSEP . $fileName;
    } elseif (strpos($fileName, 'controller_') !== false) {
        $fileName = str_replace('controller_', '', $fileName);
        $file = CONTROLLERS_DIR . DIRSEP . $fileName;
    } elseif (strpos($fileName, 'module_') !== false) {
        $fileName = str_replace('module_', '', $fileName);
        $file = MODULES_DIR . DIRSEP . $fileName;
    }

    if (!file_exists($file)){
        return false;
    }

    require_once($file);
}

$route = isset($_GET['route']) ? $_GET['route'] : '';
Router::getInstance()->delegate($route);