<?php

include("/var/www/mysql-config2.php");

$mydatabase = $IS_DEVELOPMENT ? "mytowerdev" : "mytower";
$table_name = "leaderboard";