<?php
/**
 * Created by PhpStorm.
 * User: vmitin
 * Date: 25.01.2017
 * Time: 21:20
 */

include_once '../core/db.php';

$removeById = "DELETE FROM `products` WHERE `local_id` = ?";

$mysql = getConnection();
$stmt = $mysql->prepare("SELECT `local_id` FROM `products` WHERE UNIX_TIMESTAMP(`created`) > UNIX_TIMESTAMP()  + 864000");
$stmt->execute();
$stmt->bind_result($id);
$removeMysql = getConnection();
while ($stmt->fetch()) {
    $removeStmt = $removeMysql->prepare($removeById);
    $removeStmt->bind_param('i',$id);
    $removeStmt->execute();
    $removeStmt->close();
}
$removeMysql->close();
$stmt->close();
$mysql->close();

