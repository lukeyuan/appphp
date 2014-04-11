<?php
	require_once('sql.php');
	$create_text = $POST['createSQL'];
	$sql = new DB();
	$sql->connect();
	$ok = $sql->execute($create_text);
	$sql->close();
	return $ok;
?>