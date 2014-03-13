<?php
	//define our token
	define("TOKEN", "xiaodanding");
	$para = array_merge($_GET, $_POST, $_FILES);

	if(array_key_exists('echostr', $_GET)) {
		require_once('WeChatValid.php');		
		$pvalid = new PValid();
		$result = $pvalid->all($para);
		//mf('YES');
		echo $_GET['echostr'];
	}
	else {
		//get post data, May be due to the different environments
		$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
		//extract post data
		if (!empty($postStr)){
			$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
			$names = array();
			$values = array();
			foreach ($postObj as $key => $value) {
				array_push($names, $key);
				array_push($values, $value);
			}
			$para = array_merge($para, array_combine($names, $values));
		}
		require_once('logic.php');
		require_once('sql.php');
		$logic = new Logic();
		$result = $logic->all($para);
		$textTpl = "<?xml version='1.0' encoding='utf-8' ?>
					<xml>
						<ToUserName><![CDATA[%s]]></ToUserName>
						<FromUserName><![CDATA[%s]]></FromUserName>
						<CreateTime>%s</CreateTime>
						<MsgType><![CDATA[%s]]></MsgType>
						<Content><![CDATA[%s]]></Content>
						<FuncFlag>0</FuncFlag>
					</xml>";             
	  	echo sprintf($textTpl, $para['FromUserName'], $para['ToUserName'], time(), $result['MsgType'], $result['Content']);
	}
	
	function mf($name) {
        $handle = fopen($name, 'w');
        fwrite($handle, 'string');
        fclose($handle);
    }
?>