<?php
/**
 * Created by PhpStorm.
 * User: vmitin
 * Date: 22.01.2017
 * Time: 11:20
 */

include_once 'config.php';

function getConnection() {
    $db_host = DB_HOST;
    $db_user = DB_USER;
    $db_password = DP_PASS;
    $db_base = DB_NAME;
    $mysqli = new mysqli($db_host, $db_user, $db_password, $db_base);
    if (mysqli_connect_errno()) {
        return null;
    }
    $mysqli->set_charset("utf8");
    return $mysqli;
}