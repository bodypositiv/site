<?php
/*
 * Класс, который используется для вывода сообщений о дебаге
 */

class Debug {

    protected static $instance;
    private function __construct(){
        $this->queries_num = 0;
        $this->slow_query = 0;
        $this->slow_query_time = 1;
    }
    private $vars;
    private $typed_vars;
    private $queries_num;
    private $slow_query;

    public static function isDebug(){
        if(isset($_COOKIE['show_debug'])){
            if($_COOKIE['show_debug']>=1)
                return true;
        }
        return false;
    }

    public static function getLevel(){
        if(isset($_COOKIE['show_debug'])){
            if($_COOKIE['show_debug']>=1)
                return $_COOKIE['show_debug'];
        }
        return 0;
    }

    /**
     * @return Debug
     */
    public static function getInstance() {
        if ( is_null(self::$instance) ) {
            self::$instance = new Debug;
        }
        return self::$instance;
    }

    public function addString($string) {
        if(self::isDebug()){
            $this->vars[] = $string;

            $tv = array();
            $tv['type'] = 'string';
            $tv['params'] = '';
            $tv['string'] = $string;
            $tv['backtrace'] = self::getBackTrace();
            $this->typed_vars[] = $tv;
        }
        return true;
    }

    public function addVariable($string) {
        if(self::isDebug()){
            $this->vars[] = print_r($string, true);

            $tv = array();
            $tv['type'] = 'variable';
            $tv['params'] = '';
            $tv['string'] = print_r($string, true);
            $tv['backtrace'] = self::getBackTrace();
            $this->typed_vars[] = $tv;
        }
        return true;
    }

    private function getBackTrace(){
        $backtrace = '';
        $arr = debug_backtrace();
        $n = 0;
        foreach($arr as $b){
            $file = $b['file'];
            $line = $b['line'];

            $file = str_replace(CORE_PATH, '<i>CORE_PATH</i>', $file);
            $file = str_replace(SITE_PATH, '<i>SITE_PATH</i>', $file);

            if($n>0){
                $backtrace .= "$file ($line) -> ";
            }
            $n++;
        }
        return $backtrace;
    }

    public function addQuery($string, $time, $count){
        if(self::isDebug()){
            $time = sprintf("%01.5f", $time);
            $this->vars[] = "Time: $time; Count: $count; Query: ".$string;

            $tv = array();
            $tv['type'] = 'query';
            $tv['params'] = "Time: $time; Row count: $count";

            if(!defined('SLOW_QUERY_TIME')){
                define('SLOW_QUERY_TIME', '10');
                $this->slow_query_time = 0;
            }

            if(($time*1000)>SLOW_QUERY_TIME){
                $tv['params'] = "Time: <b>$time</b>; Row count: $count";
                $this->slow_query = 1;
            }

            $tv['string'] = $string;
            $tv['backtrace'] = self::getBackTrace();
            $this->typed_vars[] = $tv;
            $this->queries_num++;
        }
        return true;
    }

    public function getText(){
        $res = "";
        if(self::isDebug()){
            foreach ($this->vars as $value) {
                $res .= $value . "\n";
            }
        }
        return $res;
    }

    public function showInView(){
        if(self::isDebug()){
            if(self::getLevel()==2){
                echo "<link rel='stylesheet' href='/st/css/debug.css'>";
                echo "<div class='debug'>";
                echo "<div class='debug_info'>".$this->getInfoText()."</div>";
                foreach($this->typed_vars as $tv){
                    echo "<div class='typed type_".$tv['type']."'>";
					echo "<div class='string'>";
					echo $tv['string'];
					echo "</div>";
					echo "<div class='params'>";
					echo $tv['params'];
					echo "</div>";
					echo "<div class='backtrace'>";
					echo $tv['backtrace'];
					echo "</div>";
                    echo "</div>";
                }
                echo "</div>";
            }
        }
    }

    public function show(){
        if(self::isDebug()){
            if(self::getLevel()==1){
                echo "<pre class='debug'>";
                print_r($this->vars);
                echo "</pre>";
            }
        }
    }

    private function getInfoText(){
        $str =  "";
        if($this->slow_query==1){
            $str .=  "<span class='error'>Slow query!</span><br>\n";
        }
        if($this->slow_query_time==0){
            $str .=  "<span class='error'>SLOW_QUERY_TIME is not defined</span><br>\n";
        }
        $str .=  "Total queries: <b>".$this->queries_num."</b>\n";
        return $str;
    }
}
