<?php
/**
 * Created by PhpStorm.
 * User: vmitin
 * Date: 1/26/17
 * Time: 6:06 PM
 */

include_once 'db.php';

function prepareQueryForProducts($type, $noUserId, $isPlus) {
    $visibilityMode = ($type == 'simple') ? "`visibility_mode` = 0" : "`visibility_mode` <> 0";
    $where = $noUserId ? $visibilityMode : "$visibilityMode AND `user_id` <> ?";
    if (!$isPlus && !$noUserId) {
      //  $where = $where . " AND NOT EXISTS (SELECT 1 FROM `showed_products` sp WHERE sp.`user_id` = ? AND sp.`product_id` = p.`id` )";
    }
    return "SELECT p.`local_id`, p.`id`, p.`brand_id`,
                p.`category_id`, p.`city`, p.`mobile`, p.`post_сode`,
                p.`street`, ROUND(UNIX_TIMESTAMP(p.`created`) * 1000), p.`description`, p.`price`,
                p.`product_type_id`, p.`size`,
                p.`user_id`, p.`views`, p.`visibility_mode`
                    FROM `products` p WHERE $where ORDER BY p.`created` DESC";
}

function deleteFromTableByField($table, $field, $value, $type) {
    $mysql = getConnection();
    $query = "DELETE FROM $table
                    WHERE $field = ? ";
    $stmt = $mysql->prepare($query);
    $stmt->bind_param($type, $value);
    $stmt->execute();
    $stmt->close();
    $mysql->close();
}