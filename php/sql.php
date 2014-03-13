<?php
	require_once('connectvars.php');

	/**
	* MongoDB的接口
	*/
	class NoSQL
	{
		public $mongo_client;
		public $dbc;

		function __construct() {
		}

		//数据库连接
		function connect() {
			$this->mongo_client = new MongoClient(DB_URL);
			$this->dbc = $this->mongo_client->selectDB(DB_NAME);
		}

		//数据库关闭
		function close() {
			$this->dbc = null;
			return $this->mongo_client->close();
		}

		//插入
		function insert($table, $keys_values) {
			return $this->dbc->$table->insert($keys_values);
		}

		//内部插入接口
		function insert_all($json_content) {
			// $json_content = json_decode($json);
			$table = $json_content->table;
			$data = $json_content->data;
			$len = count($data);
			for($i = 1; $i < $len; $i++) {
				$this->insert($table, array_combine($data[0], $data[$i]));
			}
		}

		//查询
		function select($table, $criteria = array(), $fields = array()) {
		    return $this->dbc->$table->find($criteria, $fields);
		}

		//删除表
		function drop($table) {
			return $this->dbc->$table->drop();
		}

		//删除表中信息
		function delete($table, $criteria = array()) {
			return $this->dbc->$table->remove($criteria);
		}

		//更新
		function update($table, $modify, $criteria = array()) {
			return $this->dbc->$table->update($criteria, array('$set' => $modify));
		}
	}

	/**
	* MySQL的接口
	*/
	class MySQL
	{
		public $dbc;

		function __construct() {
		}

		//数据库连接
		function connect() {
			$this->dbc = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
			// mysqli_query($this->dbc, "SET NAMES 'utf8'");
		}

		//数据库关闭
		function close() {
			mysqli_close($this->dbc);
		}

		//建表
		function create($table, $items_types, $notnull = array(), $default = array(), $auto_increment = array(), $primarykey = null) {
			$query = 'CREATE TABLE ' . $table . '(';
			$items = array();
			$types = array();
			foreach ($items_types as $key => $value) {
				array_push($items, $key);
				array_push($types, $value);
			}
			$len = count($items_types);
			for ($i = 0; $i < $len-1; $i++) { 
				$item = $items[$i];
				$query .= ' ' . $item . ' ' . $types[$i];
				if(in_array($item, $notnull)) {
					$query .= " NOT NULL";
				}
				if(array_key_exists($item, $default)) {
					$query .= " DEFAULT '$default[$item]'";
				}
				if(in_array($item, $auto_increment)) {
					$query .= " AUTO_INCREMENT";
				}
				$query .= ',';
			}
			$item = $items[$len-1];
			$query .= ' ' . $item . ' ' . $types[$len-1];
			if(in_array($item, $notnull)) {
				$query .= " NOT NULL";
			}
			if(array_key_exists($item, $default)) {
				$query .= " DEFAULT '$default[$item]'";
			}
			if(in_array($item, $auto_increment)) {
				$query .= " AUTO_INCREMENT";
			}
			if(!empty($primarykey)) {
				$query .= ', PRIMARY KEY (' . $primarykey . ')';
			}
			$query .= ' );';
			// echo "$query <br />";
			return mysqli_query($this->dbc, $query);
		}

		//插入
		function insert($table, $keys_values) {
			$keys = array();
			$values = array();
			foreach ($keys_values as $key => $value) {
				array_push($keys, $key);
				array_push($values, $value);
			}
			$query = 'INSERT INTO ' . $table . ' (';
			$len = count($keys_values);
			for ($i = 0; $i < $len-1; $i++) { 
				$query .= ' ' . $keys[$i] . ',';
			}
			$query .= ' ' . $keys[$len-1] . ' ) VALUES ( ';
			for ($i = 0; $i < $len-1; $i++) { 
				$query .= "'$values[$i]', ";
			}
			$value = $values[$len-1];
			$query .= "'$value' );";
			// echo $query . '<br />';
			return mysqli_query($this->dbc, $query);
		}

		//内部插入接口
		function insert_all($json_content) {
			// $json_content = json_decode($json);
			$table = $json_content->table;
			$data = $json_content->data;
			$len = count($data);
			for($i = 1; $i < $len; $i++) {
				$this->insert($table, array_combine($data[0], $data[$i]));
			}
		}

		function mysql_get_criteria($arr, &$ret) {
			$ret .= '( ';
			$len = count($arr);
			// echo "dep = $dep, len = $len <br />";
			$cur = 0;
			foreach ($arr as $key => $value) {
				if($key == '$or') {
					$len = count($value);
					for ($i = 0; $i < $len-1; $i++) { 
						$this->mysql_get_criteria($value[$i], $ret);
						$ret .= ' OR ';
					}
					$this->mysql_get_criteria($value[$len-1], $ret);
				}
				else if(is_array($value)) {		//不是 =
					foreach ($value as $k => $v) {
						if($k == '$gt') {
							$ret .= "( $key > '$v' )";
						}
						else if($k == '$lt') {
							$ret .= "( $key < '$v' )";
						}
						else if($k == '$ne') {
							$ret .= "( $key != '$v' )";
						}
						else if($k == '$gte') {
							$ret .= "( $key >= '$v' )";
						}
						else if($k == '$lte') {
							$ret .= "( $key <= '$v' )";
						}
						else if($k == '$or') {
							$x = 0;
							foreach ($v as $val) {
								$ret .= "( $key = '$val' )";
								if($x < count($v)-1) $ret .= ' OR ';
								$x++;
							}
						}
					}
				}
				else {		//是 =
					$ret .= "( $key = '$value' )";
				}
				if($cur < count($arr)-1) $ret .= ' AND ';
				$cur++;
			}
			$ret .= ' )';
			// echo "ret = $ret <br /> cur = $cur, dep = $dep, len = $len <br /> <br />";
		}

		//查询		条件是一个调用的过程
		function select($table, $criteria = array(), $fields = array()) {
			$query = '';
			if(empty($criteria)) {
				$query = "SELECT * FROM $table;";
			}
			else {
				$query = 'SELECT ';
				$len = count($fields);
				for ($i = 0; $i < $len; $i++) { 
					$query .= $fields[$i];
					if($i < $len-1) $query .= ', ';
				}
				$query .= " FROM $table WHERE ";
				$this->mysql_get_criteria($criteria, $query);
				$query .= ';';
			}
			// echo $query . '<br />';
			$this->mf('query', $query);
			// mysqli_query($this->dbc, "SET NAMES 'uft8'");
			// $this->mf('ret', json_encode(mysqli_query($this->dbc, $query)));
			
			return mysqli_query($this->dbc, $query);
		}

		//删除表
		function drop($table) {
			$query = "DROP TABLE $table;";
			return mysqli_query($this->dbc, $query);
		}

		//删除表中记录
		function delete($table, $criteria = array()) {
			$query = '';
			if(empty($criteria)) {
				$query = "DELETE FROM $table;";
			}
			else {
				$query = 'DELETE FROM ' . $table . ' WHERE ';
				$this->mysql_get_criteria($criteria, $query);
				$query .= ';';
			}
			// echo $query . '<br />';
			return mysqli_query($this->dbc, $query);
		}

		//更新
		function update($table, $modify, $criteria = array()) {
			$query = $query = "UPDATE $table SET ";
			$len = count($modify);
			$i = 0;
			foreach ($modify as $key => $value) {
				$query .= "$key = '$value'";
				if($i < $len-1) $query .= ', ';
				$i++;
			}
			if(!empty($criteria)) {
				$query .= ' WHERE ';
				$this->mysql_get_criteria($criteria, $query);
			}
			$query .= ';';
			// echo "$query <br />";
			return mysqli_query($this->dbc, $query);
		}

		//设置连入编码方式
		function setnames($encoding) {
			return mysqli_query($this->dbc, "SET NAMES '$encoding'");
		}

		function mf($name, $str) {
			$handle = fopen($name, 'w');
			fwrite($handle, $str);
			fclose($handle);
		}
	}
?>