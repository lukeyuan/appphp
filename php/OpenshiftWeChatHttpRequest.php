<?php
	//define our token
	define("TOKEN", "xiaodanding");
	$para = array_merge($_GET, $_POST, $_FILES);

	if(array_key_exists('echostr', $_GET)) {
		require_once('WeChatValid.php');		
		$pvalid = new PValid();
		$result = $pvalid->all($para);
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
		require_once('sql.php');		//引入数据库接口
		require_once('logic.php');		//引入逻辑模块
		require_once('statemachine.php');
		$logic = new Logic();
		$result = $logic->all($para);
		$msgType = $result['MsgType'];

		if($msgType == 'text') {		//回复文本消息
			$textTpl = "<xml>
							<ToUserName><![CDATA[%s]]></ToUserName>
							<FromUserName><![CDATA[%s]]></FromUserName>
							<CreateTime>%s</CreateTime>
							<MsgType><![CDATA[text]]></MsgType>
							<Content><![CDATA[%s]]></Content>
							<FuncFlag>0</FuncFlag>
						</xml>";	
		   	echo sprintf($textTpl, $para['FromUserName'], $para['ToUserName'], time(), $result['Content']);			
		}
		else if($msgType == 'news') {		//回复图文消息
			$temp = "<xml>
					<ToUserName><![CDATA[%s]]></ToUserName>
					<FromUserName><![CDATA[%s]]></FromUserName>
					<CreateTime>%s</CreateTime>
					<MsgType><![CDATA[news]]></MsgType>
					<ArticleCount>%s</ArticleCount>
					<Articles>";
			$textTpl = sprintf($temp, $para['FromUserName'], $para['ToUserName'], time(), $result['ArticleCount']);
			$articleCount = $result['ArticleCount'];			
			for ($i = 1; $i <= $articleCount; $i++) { 
				$temp = "<item>
							<Title><![CDATA[%s]]></Title> 
							<Description><![CDATA[%s]]></Description>
							<PicUrl><![CDATA[%s]]></PicUrl>
							<Url><![CDATA[%s]]></Url>
						</item>";
				$textTpl .= sprintf($temp, $result['Title' . "$i"], $result['Description' . "$i"], $result['PicUrl' . "$i"], $result['Url' . "$i"]);
			}
			$textTpl .= "</Articles></xml>";
			echo $textTpl;
		}

	}
?>
