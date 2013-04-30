<?php

abstract class Controller_Base
{
    public $view;
    const DEFAULT_LIMIT = 8;
    const DEFAULT_PAGE = 1;
    
    function __construct()
    {
        $this->view = View::getInstance();
    }

    abstract function index($action, $params = array());
}
