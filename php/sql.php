<?php
	require_once('connectvars.php');

	/**
	* 数据库接口
	*/
	class DB
	{
		public $sql;
		function __construct() {
			if(file_exists('dbinfo')) {
				$dbinfo = file_get_contents('dbinfo');
				$k_vs = explode("\n", $dbinfo);
				$v_names = array();
				$v_values = array();
				for($i = 0; $i < count($k_vs) && $k_vs[$i] != ''; $i++) {
					$k_v = explode(" ", $k_vs[$i]);
					array_push($v_names, trim($k_v[0]));
					array_push($v_values, trim($k_v[1]));	
				}
				$openshift_variables = array_combine($v_names, $v_values);
				// echo 'eheh' . $openshift_variables['DB'] . 'hehe';
				if($openshift_variables['DB'] == 'mongo') {
					$this->sql = new NoSQL();
				}
				else if($openshift_variables['DB'] == 'mysql') {
					$this->sql = new MySQL();
				}
				else $this->sql = null;
			}
			else {
				$this->sql = null;
			}
		}

		//数据库连接
		function connect() {
			$this->sql->connect();
		}

		//数据库关闭
		function close() {
			return $this->sql->close();
		}

		//建表
		function create($table, $items_types, $notnull = array(), $default = array(), $auto_increment = array(), $primarykey = null) {
			$query = $this->sql->create($table, $items_types, $notnull, $default, $auto_increment, $primarykey);
		}

		//插入
		function insert($table, $keys_values) {
			return $this->sql->insert($table, $keys_values);
		}

		//内部插入接口
		function insert_all($json) {
			return $this->sql->insert_all($json);
		}

		//查询
		function select($table, $criteria = '', $fields = array()) {
		    return $this->sql->select($table, $criteria, $fields);
		}

		//删除表
		function drop($table) {
			return $this->sql->drop($table);
		}

		//删除表中信息
		function delete($table, $criteria = '') {
			return $this->sql->delete($table, $criteria);
		}

		//更新
		function update($table, $modify, $criteria = '') {
			return $this->sql->$table->update($criteria, array('$set' => $modify));
		}

		//设置连入编码方式
		function set_names($encoding) {
			return $this->sql->set_names($encoding);
		}
	}

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

		//建表
		function create($table, $items_types, $notnull = array(), $default = array(), $auto_increment = array(), $primarykey = null) {
			return True;
		}

		//插入
		function insert($table, $keys_values) {
			return $this->dbc->$table->insert($keys_values);
		}

		//内部插入接口
		function insert_all($json) {
			$json_content = json_decode($json);
			$table = $json_content->table;
			$data = $json_content->data;
			$len = count($data);
			for($i = 1; $i < $len; $i++) {
				$this->insert($table, array_combine($data[0], $data[$i]));
			}
		}

		//查询
		function select($table, $criteria = '', $fields = array()) {
		    return $this->dbc->$table->find($this->get_mongo_criteria($criteria), $fields);
		}

		//删除表
		function drop($table) {
			return $this->dbc->$table->drop();
		}

		//删除表中信息
		function delete($table, $criteria = '') {
			return $this->dbc->$table->remove($this->get_mongo_criteria($criteria));
		}

		//更新
		function update($table, $modify, $criteria = '') {
			return $this->dbc->$table->update($this->get_mongo_criteria($criteria), array('$set' => $modify));
		}

		//条件
		function get_mongo_criteria($res) {
			if(empty($res)) return '';
			$r = explode(' ', $res);
			array_push($r, '#');
			$stack1 = array(); $p1 = -1;
			$stack2 = array(); $p2 = -1;
			$stack2[0] = '#'; $p2 = 0;
			for ($i = 0; $i < count($r); $i++) { 
				$x = $r[$i];
				if($this->issign($x) == True) {		//操作符
					$ok = True;
	                while($ok) {
	                    if($this->rank($x) > $this->rank($stack2[$p2]) || $p1 < 1 || ($stack2[$p2] == '(' && $x != ')')) {
	                        $stack2[++$p2] = $x;
	                        $ok = False;
	                    }
	                    else {
	                        if($x == '#' && $stack2[$p2] == '#') break;        //结束
	                        if($x == ')' && $stack2[$p2] == '(') {
	                        	$p2--;
	                        	break;
	                        }
	                        $b = $stack1[$p1]; $p1--;
	                        $a = $stack1[$p1]; $p1--;
	                        $op = $stack2[$p2]; $p2--;
	                        $p1++;
	                        $stack1[$p1] = $this->cal($a, $op, $b); 
	                    }
	                }
				}
				else {		//操作数
					$stack1[++$p1] = $x;
				}
			}
			return $stack1[0];
		}

		//设置操作符优先级
		function rank($c) {
			$r = 100;
			if($c == '(') return $r;
			else if($c == '==' || $c == '!=' || $c == '>' || $c == '<' || $c == '>=' || $c == '<=') return $r-1;
			else if($c == '&&') return $r-2;
			else if($c == '||') return $r-3;
			else if($c == ')') return $r-4;
			else return $r-5;
		}

		//判断是否操作符
		function issign($c) {
			return preg_match('/^(\(|\)|==|!=|>|<|>=|<=|&&|#|(\|\|))$/', $c);
			// if($c == '(' || $c == ')' || $c == '==' || $c == '!=' || $c == '>' || $c == '<' || $c == '>=' || $c == '<=' || $c == '&&' $c == '||' || $c == '#') return True;
			// return False;
		}

		//转换元表达式
		function cal($a, $op, $b) {
			$ret = '';
			if($op == '==') $ret = "array($a => $b)";
			else if($op == '!=') $ret = "array($a => array('\$ne' => $b))";
			else if($op == '>') $ret = "array($a => array('\$gt' => $b))";
			else if($op == '<') $ret = "array($a => array('\$lt' => $b))";
			else if($op == '>=') $ret = "array($a => array('\$gte' => $b))";
			else if($op == '<=') $ret = "array($a => array('\$lte' => $b))";
			else if($op == '&&') $ret = "array('\$and' => array($a, $b)";
			else if($op == '||') $ret = "array('\$or' => array($a, $b)";
			return $ret;
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
			$query = "CREATE TABLE IF NOT EXISTS $table (";
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
		function insert_all($json) {
			$json_content = json_decode($json);
			$table = $json_content->table;
			$data = $json_content->data;
			$len = count($data);
			for($i = 1; $i < $len; $i++) {
				$this->insert($table, array_combine($data[0], $data[$i]));
			}
		}
		
		//查询		条件是一个调用的过程
		function select($table, $criteria = '', $fields = array()) {
			// 缓存cache的路径
			$cache_filename = hash('md5', $table . $criteria . implode('', $fields));
			$cache_path = 'cache/' . $cache_filename;

			// 判断是否存在缓存
			if(file_exists($cache_path)) {
				// printf("%s\n", fileatime($cache_path));
				// printf("%s\n", time() - fileatime($cache_path));
				if(time() - fileatime($cache_path) > 200) {
					unlink($cache_path);
				}
				else return json_decode(file_get_contents($cache_path));
			}

			// 不存在缓存或者缓存过期时
			// 构造查询语句
			$query = '';
			if(empty($criteria)) $query = "SELECT * FROM $table;";
			else {
				$query = 'SELECT ';
				if(empty($fields)) $query .= '*';
				else {
					$len = count($fields);
					for ($i = 0; $i < $len; $i++) { 
						$query .= $fields[$i];
						if($i < $len-1) $query .= ', ';
					}
				}
				$query .= " FROM $table WHERE " . $this->get_mysql_criteria($criteria);
				$query .= ';';
			}

			// 在数据库中查询
			$data = mysqli_query($this->dbc, $query);
			if($data) {
				$ret = array();
				while ($row = mysqli_fetch_array($data)) array_push($ret, $row);
				$json = json_encode($ret);
				$this->set_cache($cache_path, $json);		//缓存
				return json_decode($json);
			}
			else return $data;
		}

		function set_cache($filepath, $content) {
			$handle = fopen($filepath, 'w');
			fwrite($handle, $content);
			fclose($handle);
		}

		//删除表
		function drop($table) {
			$query = "DROP TABLE $table;";
			return mysqli_query($this->dbc, $query);
		}

		//删除表中记录
		function delete($table, $criteria = '') {
			$query = '';
			if(empty($criteria)) {
				$query = "DELETE FROM $table;";
			}
			else {
				$query = 'DELETE FROM ' . $table . ' WHERE ' . $this->get_mysql_criteria($criteria);
				$query .= ';';
			}
			// echo $query . '<br />';
			return mysqli_query($this->dbc, $query);
		}

		//更新
		function update($table, $modify, $criteria = '') {
			$query = $query = "UPDATE $table SET ";
			$len = count($modify);
			$i = 0;
			foreach ($modify as $key => $value) {
				$query .= "$key = '$value'";
				if($i < $len-1) $query .= ', ';
				$i++;
			}
			if(!empty($criteria)) {
				$query .= ' WHERE ' . $this->get_mysql_criteria($criteria);
			}
			$query .= ';';
			// echo "$query <br />";
			return mysqli_query($this->dbc, $query);
		}

		//设置连入编码方式
		function set_names($encoding) {
			return mysqli_query($this->dbc, "SET NAMES '$encoding'");
		}

		//将逻辑表达式转换为MySQL的格式
		function get_mysql_criteria($res) {
			$r = explode(' ', $res);
			for ($i = 0; $i < count($r); $i++) { 
				$x = $r[$i];
				if($x == '==') $r[$i] = '=';
				else if($x == '&&') $r[$i] = 'AND';
				else if($x == '||') $r[$i] = 'OR';
			}
			return implode(' ', $r);
		}

		//生成名为$name，内容为$str的文件，测试用
		function mf($name, $str) {
			$handle = fopen($name, 'w');
			fwrite($handle, $str);
			fclose($handle);
		}
	}
?>
