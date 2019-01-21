<?php

include_once 'core/core.php';
header('Content-Type: application/json');
if (isset($_GET['products'])) {
    $filter = isset($_GET['filter']) ? base64_encode($_GET['filter']) : null;
    $lastId = isset($_GET['lastId']) ? $_GET['lastId'] : null;
    $limit = isset($_GET['limit']) ? $_GET['limit'] : null;
    $userId = isset($_GET['userId']) ? $_GET['userId'] : null;
    $isPlus = isset($_GET['isPlus']) ? $_GET['isPlus'] == 1 : true;

    echo json_encode(getProducts($filter, $lastId, $limit, $userId, $isPlus));
    exit;
}

if (isset($_GET['favorites']) && isset($_GET['userId'])) {
    $userId = isset($_GET['userId']) ? $_GET['userId'] : null;
    $isPlus = isset($_GET['isPlus']) ? $_GET['isPlus'] == 1 : true;

    echo json_encode(getFavoriteProducts($_GET['userId']));
    exit;
}

if (isset($_GET['add']) && isset($_POST['product'])) {
    addProduct($_POST['product']);
    exit;
}

if (isset($_GET['add_favorite']) && isset($_POST['productId']) && isset($_POST['userId'])) {
    $isPlus = isset($_POST['isPlus']) ? ($_POST['isPlus'] == 1) : true;
    addFavoriteProduct($_POST['userId'], $_POST['productId'], $isPlus);
    exit;
}

if (isset($_GET['remove_favorite']) && isset($_POST['productId']) && isset($_POST['userId'])) {
    removeFavoriteProduct($_POST['userId'], $_POST['productId']);
    exit;
}

if (isset($_GET['remove']) && isset($_POST['userId']) && isset($_POST['productId'])) {
    removeProduct($_POST['userId'], $_POST['productId']);
    exit;
}

if (isset($_GET['shown']) && isset($_POST['userId']) && isset($_POST['productId'])) {
    saveProductAsShowed($_POST['userId'], $_POST['productId']);
    exit;
}

header('HTTP/1.1 405 Method not allowed');
echo json_encode(array('code' => 405));