<?php
  // Define database connection constants
  define('DB_HOST', $_ENV['OPENSHIFT_MYSQL_DB_HOST']);
  define('DB_USER', $_ENV['OPENSHIFT_MYSQL_DB_USERNAME']);
  define('DB_PASSWORD', $_ENV['OPENSHIFT_MYSQL_DB_PASSWORD']);
  define('DB_NAME', $_ENV['OPENSHIFT_APP_NAME']);
?>
