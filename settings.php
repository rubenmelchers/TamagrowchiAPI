<?php

// $url_base = "http://$_SERVER[HTTP_HOST]/ondiep/api/";
$url_base = "https://stud.hosted.hr.nl/0889496/tamagrowchi/api";

// DATABASE VARIABLES - LOCALHOST
$db_host = "sql.hosted.hr.nl";
$db_username = "0889496";
$db_password = "Skwotor1";
$db_name = "0889496";


// CONNECT WITH DATABASE
$connection = mysqli_connect($db_host, $db_username, $db_password, $db_name)
or die(mysqli_error($connection));