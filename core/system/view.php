<?php
class View
{
    private $vars = array();
    protected static $instance;
    private $layout = 'index';
    private $flag;

    private function __construct(){}

    /**
     * @static
     * @return View
     */
    public static function getInstance()
    {
        if ( is_null(self::$instance) ) {
            self::$instance = new View;
        }
        return self::$instance;
    }

    public static function clearText($text){
        $text = strip_tags($text, '<p><a><b><i><img><br>');
        $text = str_replace('<p></p>', '', $text);
        $text = str_replace("<p> </p>", '', $text);
        return $text;
    }

    /**
     * @param $varname
     * @param $value
     * @param bool $overwrite
     * @return bool
     */
    function set($varname, $value, $overwrite=false){
        if (isset($this->vars[$varname]) == true AND $overwrite == false) {
            // Вывод ошибки временно закомментирован.
            //trigger_error ('Unable to set var `' . $varname . '`. Already set, and overwrite not allowed.', E_USER_NOTICE);
            return false;
        }
        $this->vars[$varname] = $value;
        return true;
    }

    function remove($varname){
        unset($this->vars[$varname]);
        return true;
    }

    function show($template, $inMain = true){
        $path = TEMPLATE_DIR . DIRSEP . $template . '.phtml';

        if (!file_exists($path)) {
            return false;
        }

        if ($inMain) {
            $subTemplate = $this->getResult($template);
            include TEMPLATE_DIR . DIRSEP . $this->layout . '.phtml';
        }
        else {
            foreach ($this->vars as $key => $value) {
                $$key = $value;
            }
            include $path;
        }

        return 0;
    }

    public function getResult($template)
    {
        $path = TEMPLATE_DIR . DIRSEP . $template . '.phtml';
        if (file_exists($path)) {
            foreach ($this->vars as $key => $value) {
                $$key = $value;
            }
            ob_start();
            include($path);
            return ob_get_clean();
        }
        return false;
    }

    public function setLayout($layout)
    {
        $this->layout = $layout;
    }
}
