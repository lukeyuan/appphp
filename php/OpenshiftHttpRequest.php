<?php
	require_once('sql.php');		//引入数据库接口
	require_once('logic.php');		//引入逻辑模块
	require_once('statemachine.php');
	
	$para = array_merge($_GET, $_POST, $_FILES);
	$logic = new Logic();		
	if(array_key_exists('class', $_POST)) {		//判断class字段有没值，有的话新建逻辑模块的对象

		$logic = new $_POST['class']();

		if(array_key_exists('method', $_POST)) {		//判断method字段有没值，有的话执行该函数
			$method = $_POST['method'];
			$result = $logic->$method($para);
			$ret = json_encode($result['AppText']);		
			echo $ret;		//返回结果给转发模块
		}
	}

?>