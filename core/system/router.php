<?php
class Router
{
    protected static $instance;
    protected $_controller;
    protected $_action;
    protected $_params = array();

    private function __construct(){}
    private function __clone(){}

    public static function getInstance()
    {
        if ( is_null(self::$instance) ) {
            self::$instance = new Router;
        }
        return self::$instance;
    }

    protected function _getController($route)
    {
        $route = trim($route, '/\\');

        if (trim($route) == '') {
            $this->_controller = 'index';
        }
        else {
            $parts = explode('/', $route);
            $this->_controller = array_shift($parts);
            $this->_action = array_shift($parts);
            $this->_params = $parts;
        }
    }

    public function delegate($string)
    {
        $this->_getController($string);

        if (!file_exists(CONTROLLERS_DIR . DIRSEP . $this->_controller . '.php')) {
            // Потом переписать, чтобы перенаправлял на 404
            $this->_controller = 'index';
        }

        $controllerClass = 'Controller_' . $this->_controller;
        if (class_exists($controllerClass)) {
            $controller = new $controllerClass;
            $controller->index($this->_action, $this->_params);
        }
    }
}
