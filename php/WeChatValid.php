<?php
	/**
		* 
		*/
		class PValid
		{
			
			function __construct()
			{
				# code...
			}

			function all($para) {
				if($this->checkSignature($para)) {
					$result = array('Valid' => True);
				}
				else {
					$result = array();
				}
				return $result;
			}

			private function checkSignature($para)
			{
		        $signature = $para["signature"];
		        $timestamp = $para["timestamp"];
		        $nonce = $para["nonce"];	
		        		
				$token = 'xiaodanding';
				$tmpArr = array($token, $timestamp, $nonce);
				sort($tmpArr);
				$tmpStr = implode( $tmpArr );
				$tmpStr = sha1( $tmpStr );
				
				if( $tmpStr == $signature ){
					return true;
				}else{
					return false;
				}
			}
		}	
?>