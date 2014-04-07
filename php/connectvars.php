<?php
        // Define database connection constants
        // define('DB_HOST', $_ENV['OPENSHIFT_MYSQL_DB_HOST']);
        // define('DB_USER', $_ENV['OPENSHIFT_MYSQL_DB_USERNAME']);
        // define('DB_PASSWORD', $_ENV['OPENSHIFT_MYSQL_DB_PASSWORD']);
        // define('DB_NAME', $_ENV['OPENSHIFT_APP_NAME']);
        // define('DB_URL', $_ENV['OPENSHIFT_MONGODB_DB_URL']);
        $dbinfo = file_get_contents('dbinfo');
        $k_vs = explode("\n", $dbinfo);
        $v_names = array();
        $v_values = array();
        for($i = 0; $i < count($k_vs) && $k_vs[$i] != ''; $i++) {
                $k_v = explode(" ", $k_vs[$i]);
                array_push($v_names, trim($k_v[0]));
                array_push($v_values, trim($k_v[1]));
        }
        $openshift_variables = array_combine($v_names, $v_values);
        if(array_key_exists('DB', $openshift_variables)) {
                if($openshift_variables['DB'] == 'mysql') {
                        define('DB_HOST', $openshift_variables['OPENSHIFT_MYSQL_DB_HOST']);
                        define('DB_USER', $openshift_variables['OPENSHIFT_MYSQL_DB_USERNAME']);
                        define('DB_PASSWORD', $openshift_variables['OPENSHIFT_MYSQL_DB_PASSWORD']);
                        define('DB_NAME', $openshift_variables['OPENSHIFT_APP_NAME']);
                }
                else define('DB_URL', $openshift_variables['OPENSHIFT_MONGODB_DB_URL']);
        }
        // define('DB_HOST', 'localhost');
        // define('DB_USER', 'root');
        // define('DB_PASSWORD', '');
        // define('DB_NAME', 'tellme');
        // define('DB_URL', '');
?>