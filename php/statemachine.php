<?php
	require_once('connectvars.php');
	/**
	* State_Machine
	*/
	class State_Machine
	{
		public $ecnt;
		public $edge_to;
		public $head;
		public $nxt;
		public $weight;
		public $dbc;

		function __construct() {
			$this->ecnt = 0;
			$this->edge_to = array();
			$this->head = array();
			$this->nxt = array();
			$this->weight = array();
			$this->dbc = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
			$query = "CREATE TABLE IF NOT EXISTS user_state (
					  id int(11) NOT NULL AUTO_INCREMENT,
					  username varchar(1000) NOT NULL,
					  state varchar(1000) DEFAULT NULL,
					  PRIMARY KEY (id)
					) ENGINE=InnoDB  DEFAULT CHARSET=utf8;";
			mysqli_query($this->dbc, $query) or die('error create table user_state');
		}

		// 邻接表加边
		function add_edge($e_from, $e_to, $patern_string) {
			if(!array_key_exists($e_from, $this->head)) {
				$this->head[$e_from] = -1;
			}
			$this->edge_to[$this->ecnt] = $e_to;
			$this->weight[$this->ecnt] = $patern_string;
			$this->nxt[$this->ecnt] = $this->head[$e_from];
			$this->head[$e_from] = $this->ecnt;
			$this->ecnt++;
		}

		// 新建用户状态
		function create_state($user) {
			$user_json = json_encode(array("state" => 'null', "select" => 'null', "fa" => array()));
			$query = "INSERT INTO user_state (username, state) VALUES ('$user', '$user_json')";
			return mysqli_query($this->dbc, $query);
		}

		// 重置用户状态
		function reset_state($user) {
			$user_json = json_encode(array("state" => 'null', "select" => 'null', "fa" => array()));
			$query = "UPDATE user_state SET state = '$user_json' WHERE username = '$user'";
			return mysqli_query($this->dbc, $query);			
		}

		// 获取用户当前状态
		function get_state($user) {
			$query = "SELECT state FROM user_state WHERE username = '$user';";
			$data = mysqli_query($this->dbc, $query) or die('error querying database');
			$rows = mysqli_num_rows($data);
			if($rows == 1) {
				$row = mysqli_fetch_array($data);
				$user_json = $row['state'];
				$user_json_content = json_decode($user_json);
				return $user_json_content->state;
			}
			else {
				$this->create_state($user);
				return 'null';
			}
		}

		// 获取用户上一次的选择
		function get_select($user) {
			$query = "SELECT state FROM user_state WHERE username = '$user';";
			$data = mysqli_query($this->dbc, $query) or die('error querying database');
			$rows = mysqli_num_rows($data);
			if($rows == 1) {
				$row = mysqli_fetch_array($data);
				$user_json = $row['state'];
				$user_json_content = json_decode($user_json);
				return $user_json_content->select;
			}					
			else {
				$this->create_state($user);
				return 'null';
			}
		}

		// 保存用户状态
		function save_state($user, $state, $select) {
			$query = "SELECT state FROM user_state WHERE username = '$user';";
			$data = mysqli_query($this->dbc, $query) or die('error querying database');
			$rows = mysqli_num_rows($data);
			if($rows == 1) {
				$row = mysqli_fetch_array($data);
				$user_json = $row['state'];
				//不要存json前后的单引号
				$user_json_content = json_decode($user_json);
				$user_json_content = array('fa' => $user_json_content, 'state' => $state, 'select' => $select);
				$user_json = json_encode($user_json_content);
				$query = "UPDATE user_state SET state = '$user_json' WHERE username = '$user'";
				return mysqli_query($this->dbc, $query) or die ('error querying database.');
			}					
			else $this->create_state($user);			
		}

		// 回滚用户状态
		function rollback($user) {
			$query = "SELECT state FROM user_state WHERE username = '$user';";
			$data = mysqli_query($this->dbc, $query) or die('error querying database');
			$rows = mysqli_num_rows($data);
			if($rows == 1) {
				$row = mysqli_fetch_array($data);
				$user_json = $row['state'];
				$user_json_content = json_decode($user_json);		//会解析所有层次
				if($user_json_content->fa == array()) return False;
				$fa_json_content = $user_json_content->fa;
				$state = $fa_json_content->state;
				$select = $fa_json_content->select;
				$fa = $fa_json_content->fa;
				$user_json_content = array('fa' => $fa, 'state' => $state, 'select' => $select);
				$user_json = json_encode($user_json_content);
				$query = "UPDATE user_state SET state = '$user_json' WHERE username = '$user'";
				return mysqli_query($this->dbc, $query) or die ('error querying database.');
			}					
			else if($rows == 0) $this->create_state($user);			

		}

		// 检查用户user通过选择select能否到达目标状态target_state
		function can_go($user, $target_state, $select) {
			$cur_state = $this->get_state($user);
			if(!array_key_exists($cur_state, $this->head)) {
				$this->head[$cur_state] = -1;
			}
			for ($e = $this->head[$cur_state]; $e != -1; $e = $this->nxt[$e]) { 
				// $this->dp($this->edge_to[$e]);
				// $this->dp($this->weight[$e]);	
				if($this->edge_to[$e] == $target_state) {
					$regex = $this->weight[$e];
					if(preg_match($regex, $select)) return True;
				}
			}
			return False;
		}

		// 根据用户输入判断力其所能到达的状态
		function go_with($user, $select) {
			$cur_state = $this->get_state($user);
			if(!array_key_exists($cur_state, $this->head)) {
				$this->head[$cur_state] = -1;
			}
			for ($e = $this->head[$cur_state]; $e != -1; $e = $this->nxt[$e]) { 
				$regex = $this->weight[$e];
				if(preg_match($regex, $select)) return $this->edge_to[$e];
			}
			return '00';
		}

		function dp($s) {
			echo "$s <br />";
		}
	}

	$sm = new State_Machine();
	$sm->add_edge('null', '1', '//');
	// $sm->add_edge('111', '2', '/^1$/');
	// $sm->add_edge('111', '4', '/^2$/');
	// $sm->dp($sm->get_state('xiaodanding'));
	// $sm->dp($sm->get_select('xiaodanding'));
	// $sm->create_state('xiaodanding');
	// $sm->save_state('xiaodanding', '111', '1');
	// $sm->rollback('xiaodanding');
	// var_dump($sm->head);
	// var_dump($sm->nxt);
	// var_dump($sm->edge_to);
	// if ($sm->can_go('xiaodanding', '1', 'I love you'))
	// 	echo "YES";
	// else echo "NO";
	// echo "<br />";
	// $sm->dp($sm->go_with('xiaodanding', 'I love you'));
	// $sm->reset_state('xiaodanding');
?>	