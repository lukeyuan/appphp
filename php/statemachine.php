<?php
	require_once('sql.php');
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
		public $db;

		function __construct() {
			$this->ecnt = 0;
			$this->edge_to = array();
			$this->head = array();
			$this->nxt = array();
			$this->weight = array();
			$this->db = new DB();
			$this->db->connect();
			//if($this->db instanceof MySQL) {
				$this->db->sql->create('user_state',
							  array('id' => 'INT', 'username' => 'VARCHAR(30)', 'state' => 'VARCHAR(1000)'),
							  array('id'),
							  array(),
							  array('id'),
							  'id');
			//}
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
			return $this->db->insert('user_state', array('username' => $user, 'state' => $user_json));
		}

		// 重置用户状态
		function reset_state($user) {
			$user_json = json_encode(array("state" => 'null', "select" => 'null', "fa" => array()));
			return $this->db->update('user_state', array('state' => $user_json), "username == $user");
		}

		// 获取用户当前状态
		function get_state($user) {
			$data = $this->db->select('user_state', "username == $user", array('state'));
			if(count($data) == 1) {
				$row = $data[0];
				$user_json = $row->state;
				$user_json_content = json_decode($user_json);
				return $user_json_content->state;				
			}
			else {
				$this->create_state($user);
				$cache_filename = hash('md5', 'user_state' . "username == $user" . implode('', array('state')));
				$cache_path = 'cache/' . $cache_filename;
				$this->db->rm_cache($cache_path);
				return 'null';				
			}
		}

		// 获取用户上一次的选择
		function get_select($user) {
			$data = $this->db->select('user_state', "username == $user", array('state'));
			if(count($data) == 1) {
				$row = $data[0];
				$user_json = $row->state;
				$user_json_content = json_decode($user_json);
				return $user_json_content->select;				
			}
			else {
				$this->create_state($user);
				$cache_filename = hash('md5', 'user_state' . "username == $user" . implode('', array('state')));
				$cache_path = 'cache/' . $cache_filename;
				$this->db->rm_cache($cache_path);
				return 'null';
			}
		}

		// 保存用户状态
		function save_state($user, $state, $select) {
			$data = $this->db->select('user_state', "username == $user", array('state'));
			// 删缓存，因为下面会更新记录
			$cache_filename = hash('md5', 'user_state' . "username == $user" . implode('', array('state')));
			$cache_path = 'cache/' . $cache_filename;
			$this->db->rm_cache($cache_path);		

			if(count($data) == 1) {			
				$row = $data[0];
				$user_json = $row->state;
				//不要存json前后的单引号
				$user_json_content = json_decode($user_json);
				$user_json_content = array('fa' => $user_json_content, 'state' => $state, 'select' => $select);
				$user_json = json_encode($user_json_content);
				return $this->db->update('user_state', array('state' => $user_json), "username == $user");
			}					
			else $this->create_state($user);			
		}

		// 回滚用户状态
		function rollback($user) {
			$data = $this->db->select('user_state', "username == $user", array('state'));
			// 删缓存，因为下面会更新记录
			$cache_filename = hash('md5', 'user_state' . "username == $user" . implode('', array('state')));
			$cache_path = 'cache/' . $cache_filename;
			$this->db->rm_cache($cache_path);

			if(count($data) == 1) {			
				$row = $data[0];
				$user_json = $row->state;
				$user_json_content = json_decode($user_json);		//会解析所有层次
				if($user_json_content->fa == array()) return False;
				$fa_json_content = $user_json_content->fa;
				$state = $fa_json_content->state;
				$select = $fa_json_content->select;
				$fa = $fa_json_content->fa;
				$user_json_content = array('fa' => $fa, 'state' => $state, 'select' => $select);
				$user_json = json_encode($user_json_content);
				return $this->db->update('user_state', array('state' => $user_json), "username == $user");
			}					
			else if(count($data) == 0) $this->create_state($user);			

		}

		// 检查用户user通过选择select能否到达目标状态target_state
		function can_go($user, $target_state, $select) {
			$cur_state = $this->get_state($user);
			if(!array_key_exists($cur_state, $this->head)) {
				$this->head[$cur_state] = -1;
			}
			for ($e = $this->head[$cur_state]; $e != -1; $e = $this->nxt[$e]) { 
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
			var_dump($s);
		}
	}

	// $sm = new State_Machine();
	// $sm->add_edge('null', '1', '//');
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
