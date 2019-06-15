<?php 

namespace Yob;
/**
 * yob-mysql 类
 */
class Mysql
{

	protected $_field = '*';

	protected $_where = '';

	protected $_order = '';

	protected $_limit = '';

	protected $_join = '';

	protected $_debug = false;

	protected $_param = [];

	protected $_sql = '';
 
	function __construct($config) {
		// 连接
		$this->_pdo = new \PDO('mysql:host='. $config['host'] .';dbname=' . $config['dbname'], $config['user'], $config['password'], [\PDO::ATTR_PERSISTENT => true]);

		// $this->_table = isset($table) ? $table : '';
		$this->_table = $table;
	}

	// pdo对象
	public function pdo() {
		return $this->_pdo;
	}

	public function table($table) {
		$this->_table = $table;
		return $this;
	}

	public function field($filed) {
		$this->_field = $filed;
		return $this;
	}

	public function order($order) {
		$this->_order = 'order by ' . $order;
		return $this;
	}

	public function limit($limit) {
		$this->_limit = 'limit ' . $limit;
		return $this;
	}

	public function where($where) {
		$this->_where = self::options_handle($where);
		return $this;
	}

	public function page($page = 1, $num = 10) {
		$page = intval($page);
		$num = intval($num);
		$start = ($page - 1) * $num;
		$this->_limit = "limit $start,$num";
		return $this;
	}

	public function join($join) {
		if (stripos($join, 'join') === false) {
			$join = 'left join ' . $join;
		}
		$this->_join = $join;
		return $this;
	}

	public function debug() {
		$this->_debug = true;
		return $this;
	}

	public function get() {
		$res = $this->_query();
		return $res;
	}

	public function find() {
		$this->limit(1);
		$res = $this->_query();
		if (isset($res[0]) && $res[0]) {
			return $res[0];
		}
		return [];
	}

	public function update($data) {
		if ($this->_where) {
			$handleData = self::handleData($data);
			$this->preWhere();
			$this->_sql = "update {$this->_table} set $handleData {$this->_where};";
			return $this->exec($this->_sql, $this->_param);
		} else {
			echo "修改数据需指定条件";
			die;
		}
	}

	public function add($data) {
		$handleData = self::handleData($data);
		$this->_sql = "insert into {$this->_table} set $handleData;";
		$this->exec($this->_sql, $this->_param);
		return $this->_pdo->lastInsertId();
	}

	public function delete() {
		if ($this->_where) {
			$this->preWhere();
			$this->_sql = "delete from {$this->_table} {$this->_where};";
			return $this->exec($this->_sql, $this->_param);
		} else {
			echo "删除数据需指定条件";
			die;
		}
	}

	public function query($sql, $param = []) {
		$this->clearParam();
		if ($this->_debug) {
			echo '<pre>';
			echo $this->debugSql();
			die();
		} else {
			$pre = $this->_pdo->prepare($sql);
			if (!$pre) {
				$this->_error();
			}
			$pre->execute($param);
			if ($this->_error()) {
				return $pre->fetchAll(\PDO::FETCH_ASSOC);
			}
		}
	}

	public function exec($sql, $param = []) {
		$this->clearParam();
		if ($this->_debug) {
			echo '<pre>';
			echo $this->debugSql();
			die();
		} else {
			$pre = $this->_pdo->prepare($sql);
			$res = $pre->execute($param);
			if ($this->_error()) {
				return $res;
			}
		}
	}

	public function trans($callback, $arr = []) {

	}

	public function clearParam() {
		$this->_field = '*';
		$this->_where = '';
		$this->_order = '';
		$this->_limit = '';
		$this->_join = '';
		$this->_debug = false;
		$this->_param = [];
		$this->_sql = '';
	}

	public function increment($field, $step = 1) {
		if ($this->_where) {
			$handleData = $field.'='.$field.'+'.$step;
			$this->preWhere();
			$this->_sql = "update {$this->_table} set $handleData {$this->_where};";
			return $this->exec($this->_sql, $this->_param);
		} else {
			echo '自增操作需指定条件';
			die();
		}
	}

	public function decrement($field, $step = 1) {
		if ($this->_where) {
			$handleData = $field.'='.$field.'-'.$step;
			$this->preWhere();
			$this->_sql = "update {$this->_table} set $handleData {$this->_where};";
			return $this->exec($this->_sql, $this->_param);
		} else {
			echo '自减操作需指定条件';
			die();
		}
	}

	public function preWhere() {
		if ($this->_where) {
			$this->_where = 'where' . trim($this->_where, 'add');
		}
		return $this;
	}

	public function _query() {
		$this->preWhere();
		$this->_sql = "select {$this->_field} from {$this->_table} {$this->_join} {$this->_where} {$this->_order} {$this->_limit}";
		return $this->query($this->_sql, $this->_param);
	}

	public function _error() {
		if ($this->_pdo->errorCode() == 00000) {
			return true;
		} else {
			echo '<pre>';
			$msg = $this->_pdo->errorInfo()[2];
			$e = new \Exception($msg);
			echo '<h2>' . $msg . '</h2>';
			echo '<h2>' . $e->getTrace()[2]['file'] . 'In line' . $e->getTrace()[2]['line'] . '</h2>';
			echo '<h2>SQL 语句： ' . $this->debugSql() . '</h2>';
			die();
		}
	}

	public function debugSql() {
		$res = $this->_sql;
		foreach ($this->_param as $k => $v) {
			$res = str_replace(':'.$k, '"' . $v .'"', $res);
		}
		return $res;
	}

	// 表达式参数处理
	public function options_handle($param) {
		if (is_numeric($param)) {
			$option = $param;
		} elseif (is_string($param) && !empty($param) && !is_numeric($param)) {
			$params = explode(',', $param);
			$count = count($params); 
			if ($count == 1) {
				$option = ' ' . $param;
			} else {
				$option = implode(' and ', $params);
			}			 
		} elseif (is_array($param) && !empty($param)) {
			$params = $param;
			$count = count($param);
			$arr = [];
			foreach ($param as $k => $v) {
				$temp = " $k = $v ";
				array_push($arr, $temp);
			}
			$option = implode(' and ', $arr);
		} else {
			return false;
		}

		return $option;
	}

	protected function handleData($data) {
		$handleData = '';
		foreach ($data as $k => $v) {
			$columnKey = '';
			foreach (explode('.', $k) as $ko => $vo) {
				$columnKey .= '`' . $vo . '`.';
				$columnPlac = $vo;
			}
			$this->_param[$columnPlac] = $v;
			$columnKey = trim($columnKey, '.');
			$handleData .= $columnKey . '=:' . $columnPlac . ',';
		}
		$handleData = trim($handleData, ',');
		return $handleData;
	}

}