<?php
	// require_once('connectvars.php');
	//建表所用
	// $tbn = 'tb' . strval(rand(1, 1000));
	// $itn = array('id', 'title', 'content', 'location', 'photo', 'audio', 'submit_user', 'submit_time', 'status');
	// $tpn = array('INT', 'VARCHAR(20)', 'VARCHAR(150)', 'VARCHAR(100)', 'VARCHAR(100)', 'VARCHAR(100)', 'VARCHAR(20)', 'DATETIME', 'INT');

	//插入所用
	// $itn =	array('id', 'title', 'content', 'location', 'photo', 'audio', 'submit_user', 'submit_time');
	// $vals = array(1, "'A Good Bird'", "'There is a bird in the tree'", "'west 3'", "'4.png'", "'2.amr'", "'Jiechao'", 'NOW()');

	//查询、删除、更新所用
	// $op1 = array('id', 'title');
	// $op = array('<', '=');
	// $op2 = array(2, 'A Good Bird');
	// $log = array('OR');
	// $log2 = array('AND');

	//更新所用
	// $keys = array('submit_user', 'location');
	// $values = array('xiaodanding', '520');

	// $this->dbc = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME) or die('error conecting to database.');


	// create_table($this->dbc, $tbn, $itn, $tpn) or die('error querying database');

	// insert_into($this->dbc, 'tb950', $itn, $vals) or die('error querying database');


	// $data = select($this->dbc, $itn, 'tb950', $op1, $op, $op2, $log);

	// delete($this->dbc, 'tb950', $op1, $op, $op2, $log2);

	// update($this->dbc, 'tb950', $keys, $values, $op1, $op, $op2, $log2);
/**
* 
*/
class SQL
{
	
	public $dbc;

	function __construct()
	{
		# code...
	}

	//数据库连接
	function connect() {
		$this->dbc = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME) or die('error connecting to database.');
	}

	//数据库关闭
	function close() {
		mysqli_close($this->dbc);
	}

	//建表
	function create_table($tablename, $itemname, $typename, $default = null, $primarykey = null) {
		$query = 'CREATE TABLE ' . $tablename . '(';
		$len = count($itemname);
		for ($i = 0; $i < $len-1; $i++) { 
			$item = $itemname[$i];
			$query .= ' ' . $item . ' ' . $typename[$i];
			if(array_key_exists($item, $default)) {
				$query .= " DEFAULT '$default[$item]'";
			}
			$query .= ',';
		}
		$item = $itemname[$len-1];
		$query .= ' ' . $item . ' ' . $typename[$len-1];
		if(array_key_exists($item, $default)) {
			$query .= " DEFAULT '$default[$item]'";
		}
		if(!empty($primarykey)) {
			$query .= ', PRIMARY KEY (' . $primarykey . ')';
		}
		$query .= ');';
//		echo $query . '<br />';
		return mysqli_query($this->dbc, $query);
	}

	//插入
	function insert_into($tablename, $itemname, $values) {
		$query = 'INSERT INTO ' . $tablename . '(';
		$len = count($itemname);
		for ($i = 0; $i < $len-1; $i++) { 
			$query .= ' ' . $itemname[$i] . ',';
		}
		$query .= ' ' . $itemname[$len-1] . ' ) VALUES (';
		for ($i = 0; $i < $len-1; $i++) { 
			$query .= ' ' . $values[$i] . ',';
		}
		$query .= ' ' . $values[$len-1] . ' );';
		echo $query . '<br />';
		return mysqli_query($this->dbc, $query);
	}

	//查询
	function select($itemname, $tablename, $operand1, $operator, $operand2, $log) {
		$query = 'SELECT ';
		$len = count($itemname);
		for ($i = 0; $i < $len-1; $i++) { 
			$query .= $itemname[$i] . ', ';
		}
		$query .= $itemname[$len-1] . ' FROM ' . $tablename . ' WHERE ';
		$len = count($operand1);
		for ($i = 0; $i < $len-1; $i++) { 
			$val = $operand2[$i];
			$query .= $operand1[$i] . $operator[$i] . "'$val' " . $log[$i] . ' ';
		}
		$val = $operand2[$len-1];
		$query .= $operand1[$len-1] . $operator[$len-1] . "'$val';";
		//echo $query . '<br />';
		return mysqli_query($this->dbc, $query);
	}

	//删除
	function delete($tablename, $operand1, $operator, $operand2, $log) {
		$query = 'DELETE FROM ' . $tablename . ' WHERE ';
		$len = count($operand1);
		for ($i = 0; $i < $len-1; $i++) { 
			$val = $operand2[$i];
			$query .= $operand1[$i] . $operator[$i] . "'$val' " . $log[$i] . ' ';
		}
		$val = $operand2[$len-1];
		$query .= $operand1[$len-1] . $operator[$len-1] . "'$val';";
		echo $query . '<br />';
		return mysqli_query($this->dbc, $query);
	}

	//更新
	function update($tablename, $keys, $values, $operand1, $operator, $operand2, $log) {
		$query = 'UPDATE ' . $tablename . ' SET ';
		$len = count($keys);
		for ($i = 0; $i < $len-1; $i++) { 
			$val = $values[$i];
			$query .= $keys[$i] . ' = ' . "'$val'" . ', ';
		}
		$val = $values[$len-1];
		$query .= $keys[$len-1] . ' = ' . "'$val' WHERE ";
		$len = count($operand1);
		for ($i = 0; $i < $len-1; $i++) { 
			$val = $operand2[$i];
			$query .= $operand1[$i] . $operator[$i] . "'$val' " . $log[$i] . ' ';
		}
		$val = $operand2[$len-1];
		$query .= $operand1[$len-1] . $operator[$len-1] . "'$val';";
		echo $query . '<br />';
		return mysqli_query($this->dbc, $query);
	}

	//设置连入的编码方式
	function set_names($encode) {
		mysqli_query($this->dbc, "SET NAMES '$encode'");
	}

}

?>