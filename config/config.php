<?php
    error_reporting(~E_WARNING);
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $db_host = "localhost";
    $db_user = "lithernet";
    $db_password = "CiILWCSMM3VVnbBE";
    $db_database = "lithernet";

    $lithernet_base_ip = "192.168.1.255";
    $lithernet_base_port = "10009";

    $operation_mode = "demo"; //demo = keine UDP Aussendung
?>