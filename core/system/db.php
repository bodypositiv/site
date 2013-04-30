<?php
class MyPDOStatement extends PDOStatement{
    protected $_debugValues = null;
    private $test_exec = false;

    protected function __construct(){
        // need this empty construct()!
    }

    public function execute($values=array()){
        $this->_debugValues = $values;
        $tpassed = 0;
        try {
            if(Debug::isDebug()){
                $mtime = microtime();
                $mtime = explode(" ",$mtime);
                $mtime = $mtime[1] + $mtime[0];
                $tstart = $mtime;
            }

            $t = null;
            if(!$this->test_exec){
                $t = parent::execute($values);
            }

            if(Debug::isDebug()){
                $mtime = microtime();
                $mtime = explode(" ",$mtime);
                $mtime = $mtime[1] + $mtime[0];
                $tend = $mtime;
                $tpassed = ($tend - $tstart);
                $count = parent::rowCount();
            }

        } catch (PDOException $e) {
            throw $e;
        }

        if(Debug::isDebug()){
            Debug::getInstance()->addQuery($this->_debugQuery(), $tpassed, $count);
        }

        return $t;
    }

    /**
     * Для постраничной разбивки
     *
     * @param array $values
     * @return array
     * @throws PDOException
     */
    public function executeWithCount($values=array()){
        $this->_debugValues = $values;
        $tpassed = 0;
        try {
            if(Debug::isDebug()){
                $mtime = microtime();
                $mtime = explode(" ",$mtime);
                $mtime = $mtime[1] + $mtime[0];
                $tstart = $mtime;
            }

            $t = parent::execute($values);
            $count = parent::rowCount();

            if(Debug::isDebug()){
                $mtime = microtime();
                $mtime = explode(" ",$mtime);
                $mtime = $mtime[1] + $mtime[0];
                $tend = $mtime;
                $tpassed = ($tend - $tstart);
            }

        } catch (PDOException $e) {
            throw $e;
        }

        if(Debug::isDebug()){
            Debug::getInstance()->addQuery($this->_debugQuery(), $tpassed, $count);
        }

        $res = array();
        $res['count'] = $count;
        $res['data'] = $t;

        return $res;
    }

    public function _debugQuery($replaced=true){
        $q = $this->queryString;
        if (!$replaced) {
            return $q;
        }
        return preg_replace_callback('/:([0-9a-z_]+)/i', array($this, '_debugReplace'), $q);
    }

    public function testExec($val = true){
        $this->test_exec = $val;
    }

    protected function _debugReplace($m){
        $v = $this->_debugValues[$m[0]];
        if ($v === null) {
            return "NULL";
        }
        if (!is_numeric($v)) {
            $v = str_replace("'", "''", $v);
        }

        return "'". $v ."'";
    }
}

class Db extends PDO{
    protected $_debugValues = null;
    protected static $instance;

    /**
     * @return Db
     */
    public static function getInstance(){
        if ( is_null(self::$instance) ) {
            self::$instance = new Db;
			self::$instance->query("SET NAMES 'UTF8'");
        }
        return self::$instance;
    }

    public function __construct(){
        $options = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_STATEMENT_CLASS => array('MyPDOStatement', array()),
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        );
        $db_name = DB_NAME;
        parent::__construct("mysql:host=".DB_HOST.";dbname=".$db_name.";charset=utf8", DB_USER, DB_PASSWORD, $options);
    }

    public static function getCount($table, $where = '') {
    	$sql = "SELECT COUNT(*) FROM `{$table}` WHERE 1 {$where}";
    	$query = self::$instance->prepare($sql);
    	$query->execute();
    	$query->setFetchMode(PDO::FETCH_NUM);
    	$row = $query->fetch();
    	return $row[0];
    }

    private static function getDb() {
        if (defined('DB_NAME')) {
            die('ERROR: Задано имя базы по умолчанию: '.DB_NAME);
            return DB_NAME;
        }
        else {
            return (isset($_SESSION['db_name'])) ? $_SESSION['db_name'] : DB_DEFAULT_NAME;
        }
    }

    public function setDb($db) {
        $_SESSION['db_name'] = $db;
    }

    public static function isValidDb($db_name) {
        $db_name = strtolower($db_name);
        $correct = array('dev', 'master');
        return (in_array($db_name, $correct)) ? true : false;
    }

    public static function simpleUpdate($table, $fields, $values, $where_field, $where_value) {
        $sql = "UPDATE `{$table}` SET ";
        if (is_array($fields) && is_array($values)) {
            if (count($fields) != count($values)) {
                trigger_error('count($fields) != count($values)', E_USER_NOTICE);
                return false;
            }
            for ($i = 0; $i < count($fields); $i++) {
                $sql .= "`{$fields[$i]}` = '{$values[$i]}'";
                if (isset($fields[$i+1])) {
                    $sql .= ",";
                }
                $sql .= " ";
            }
        }
        elseif (!is_array($fields) && !is_array($values)) {
            $sql .= "`{$fields}` = '{$values}' ";
        }
        else {
            trigger_error('$fields and $values must be arrays or string only', E_USER_NOTICE);
            return false;
        }
        $sql .= "WHERE `{$where_field}` = '{$where_value}'";
        $db = self::getInstance();
        $query = $db->prepare($sql);
        return $query->execute();
    }

    public static function getStringWithParams($sql, $sort = null, $sort_arr = array(), $rev = false, $limit = null, $page = 0) {
        $sql = trim($sql);
        if (in_array($sort, $sort_arr)) {
            $sql .= " ORDER BY `{$sort}` ";
            $order = (!$rev) ? 'ASC' : 'DESC';
            $sql .= $order;
        }
		
        if (!is_null($limit) && intval($limit) > 0) {
            $sql .= " LIMIT ";
            $sql .= intval($page) * intval($limit) . ", ";
            $sql .= intval($limit);
        }

        return $sql;
    }
    /* Простая выборка 
     * Функция возвращает ассоциативный массив где
     * Ключ - $where_field
     * Значение - $field
     */
    public static function runQuery($table = '', $field = array(), $where_field = '', $array = array()){
		$db = self::getInstance();
		$fields = '`' . implode('`,`', $field) . '`';
		$array_str = implode(',', $array);
		$sql = "SELECT `{$where_field}`, {$fields} FROM {$table} WHERE {$where_field} IN ({$array_str})";
		$query = $db->prepare($sql);
		$query->execute();
		$result = $query->fetchAll();
		foreach ($result as $key => $value){
			$array[$value[$where_field]] = $value;
		}
		return $array;
	}
}
