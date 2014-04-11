<?php
	require_once('sql.php');
	$create_text = $_POST['createSQL'];
	$sql = new DB();
	$sql->connect();
	$ok = $sql->execute($create_text);
	$sql->close();
	echo $ok;
?>
