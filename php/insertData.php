<?php
	require_once('sql.php');
	$json = $GLOBALS['HTTP_RAW_POST_DATA'];
	$sql = new DB();
	$sql->connect();
	$ok = $sql->insert_all($json);
	$sql->close();
	echo $ok;
?>
