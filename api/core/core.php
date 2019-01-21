<?php
/**
 * Created by PhpStorm.
 * User: vmitin
 * Date: 22.01.2017
 * Time: 11:20
 */

include_once 'db_core.php';
include_once 'fb.php';
require_once ('models/Product.php');
require_once ('models/ContactInfo.php');

if (NEED_SHOW_ERRORS) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

function getAllProducts($lastId, $limit, $firebaseUserId, $isPlus) {
    $mysql = getConnection();
    $noUserId = is_null($firebaseUserId);
    $stmt = $mysql->prepare(prepareQueryForProducts('premium', $noUserId, $isPlus));
    if (!$noUserId) {
       // $isPlus ? $stmt->bind_param('s', $firebaseUserId) : $stmt->bind_param('ss', $firebaseUserId, $firebaseUserId);
        
        $isPlus ? $stmt->bind_param('s', $firebaseUserId) : $stmt->bind_param('s',  $firebaseUserId);
    }
    $stmt->execute();
    $stmt->bind_result($localId, $id, $brandId, $categoryId, $city, $mobile, $postCode, $street, $created, $description, $price, $productTypeId, $size, $userId, $views, $visibilityMode);
    $data = array();
    while ($stmt->fetch()) {
        $data[] = array(
            'id' => $id,
            'brandId' => $brandId,
            'brandName' => getLocalizedBrandName($brandId),
            'categoryId' => $categoryId,
            'contactInfo' => array(
                'city' => $city,
                'mobile' => $mobile,
                'postCode' => $postCode,
                'street' => $street,
            ),
            'created' => $created,
            'description' => $description,
            'photos' => getProductPhotos($id),
            'price' => $price,
            'typeId' => $productTypeId,
            'typeName' => getLocalizedProductType($productTypeId),
            'size' => $size,
            'tags' => getProductTags($id),
            'userId' => $userId,
            'views' => $views,
            'visibilityMode' => $visibilityMode,
            'isAddedToFavorites' => isAddedToFavorites($id, $userId)
        );
    }
    $stmt->close();

    $stmt = $mysql->prepare(prepareQueryForProducts('simple', $noUserId, $isPlus));
    if (!$noUserId) {
       // $isPlus ? $stmt->bind_param('s', $firebaseUserId) : $stmt->bind_param('ss', $firebaseUserId, $firebaseUserId);
        
        $isPlus ? $stmt->bind_param('s', $firebaseUserId) : $stmt->bind_param('s', $firebaseUserId);
    }
    $stmt->execute();
    $stmt->bind_result($localId, $id, $brandId, $categoryId, $city, $mobile, $postCode, $street, $created, $description, $price, $productTypeId, $size, $userId, $views, $visibilityMode);
    while ($stmt->fetch()) {
        $data[] = array(
            'id' => $id,
            'brandId' => $brandId,
            'brandName' => getLocalizedBrandName($brandId),
            'categoryId' => $categoryId,
            'contactInfo' => array(
                'city' => $city,
                'mobile' => $mobile,
                'postCode' => $postCode,
                'street' => $street,
            ),
            'created' => $created,
            'description' => $description,
            'photos' => getProductPhotos($id),
            'price' => $price,
            'typeId' => $productTypeId,
            'typeName' => getLocalizedProductType($productTypeId),
            'size' => $size,
            'tags' => getProductTags($id),
            'userId' => $userId,
            'views' => $views,
            'visibilityMode' => $visibilityMode
        );
    }
    $stmt->close();

    if ($limit != null) {
        $newData = array();
        $i = 0;
        $foundStart = $lastId == null;
        foreach ($data as $item) {
            if ($foundStart) {
                $newData[] = $item;
                $i++;
                if ($i == $limit) {
                    return $newData;
                }
            } elseif ($lastId != null && $item['id'] == $lastId) {
                $foundStart = true;
            }
        }
        return $newData;
    }

    return $data;
}

function getProducts($filters, $lastId, $limit, $userId, $isPlus) {
    if ($filters == null) {
        return getAllProducts($lastId, $limit, $userId, $isPlus);
    }
}

function getFavoriteProducts($userId) {
    $mysql = getConnection();
    $query = "SELECT p.`local_id`, p.`id`, p.`brand_id`,
                p.`category_id`, p.`city`, p.`mobile`, p.`post_сode`,
                p.`street`, ROUND(UNIX_TIMESTAMP(p.`created`) * 1000), p.`description`, p.`price`,
                p.`product_type_id`, p.`size`,
                p.`user_id`, p.`views`, p.`visibility_mode`
                    FROM `products` p WHERE EXISTS (SELECT 1 FROM `product_favorites` pf WHERE pf.`user_id` = ? AND pf.`product_id` = p.`id` LIMIT 1) ORDER BY p.`created` DESC";
    $stmt = $mysql->prepare($query);
    $stmt->bind_param('s', $userId);
    $stmt->execute();
    $stmt->bind_result($localId, $id, $brandId, $categoryId, $city, $mobile, $postCode, $street, $created, $description, $price, $productTypeId, $size, $userId, $views, $visibilityMode);
    $data = array();
    while ($stmt->fetch()) {
        $data[] = array(
            'id' => $id,
            'brandId' => $brandId,
            'brandName' => getLocalizedBrandName($brandId),
            'categoryId' => $categoryId,
            'contactInfo' => array(
                'city' => $city,
                'mobile' => $mobile,
                'postCode' => $postCode,
                'street' => $street,
            ),
            'created' => $created,
            'description' => $description,
            'photos' => getProductPhotos($id),
            'price' => $price,
            'typeId' => $productTypeId,
            'typeName' => getLocalizedProductType($productTypeId),
            'size' => $size,
            'tags' => getProductTags($id),
            'userId' => $userId,
            'views' => $views,
            'visibilityMode' => $visibilityMode
        );
    }
    $stmt->close();
    $mysql->close();
    return $data;
}

function removeProduct($userId, $productId) {
    $mysql = getConnection();
    $query = "DELETE FROM `products`
                    WHERE `id` = ? AND `user_id` = ?";
    $stmt = $mysql->prepare($query);
    $stmt->bind_param('ss', $productId, $userId);
    $result = $stmt->execute();
    $stmt->close();
    $mysql->close();
    if ($result) {
        deleteFromTableByField('product_favorites', 'product_id', $productId, 's');
        deleteFromTableByField('product_notifications', 'product_id', $productId, 's');
        deleteFromTableByField('showed_products', 'product_id', $productId, 's');
        deleteFromTableByField('product_tags', 'product_id', $productId, 's');
        deleteFromTableByField('product_photos', 'product_id', $productId, 's');
        fbDeleteProduct($userId, $productId);
        showSuccessMessage();
    } else {
        showErrorAndTerminate();
    }
}

function isAddedToFavorites($fireBaseId, $userId) {
    $mysql = getConnection();
    $query = "SELECT * FROM `product_favorites` WHERE `user_id` = ? AND `product_id` = ?";
    $stmt = $mysql->prepare($query);
    $stmt->bind_param('ss', $userId, $fireBaseId);
    $stmt->execute();
    $stmt->store_result();
    $count = $stmt->num_rows;
    $stmt->close();
    $mysql->close();
    if ($count > 0) {
        return true;
    }
    return false;
}

function saveProductAsShowed($userId, $productId) {
    $mysql = getConnection();
    $query = "INSERT INTO `showed_products` (`user_id`, `product_id`)
                    VALUES(?, ?)";
    $stmt = $mysql->prepare($query);
    $stmt->bind_param('ss', $userId, $productId);
    $result = $stmt->execute();

    if ($result) {
        $stmt->close();
        $mysql->close();
        showSuccessMessage();
    } else {
        $error = $stmt->error;
        $stmt->close();
        $mysql->close();
        showErrorAndTerminate(BAD_REQUEST, $error);
    }
}

function addFavoriteProduct($userId, $productId, $isPlus) {
    $mysql = getConnection();
    if (!$isPlus) {
        $query = "SELECT `user_id` FROM `product_favorites` WHERE `user_id` = ?";
        $stmt = $mysql->prepare($query);
        $stmt->bind_param('s', $userId);
        $stmt->execute();
        $stmt->store_result();
        $count = $stmt->num_rows;
        $stmt->close();
        $mysql->close();
        if ($count >= 10) {
            showErrorAndTerminate(TO_MANY_FAVORITES);
        }
    }
    $mysql = getConnection();
    $query = "INSERT INTO `product_favorites` (`user_id`, `product_id`)
                    VALUES(?, ?)";
    $stmt = $mysql->prepare($query);
    $stmt->bind_param('ss', $userId, $productId);
    $result = $stmt->execute();
    $stmt->close();
    $mysql->close();
    if ($result) {
        showSuccessMessage();
    } else {
        showErrorAndTerminate();
    }
}

function removeFavoriteProduct($userId, $productId) {
    $mysql = getConnection();
    $query = "DELETE FROM `product_favorites` WHERE `user_id` = ? AND `product_id` = ?";
    $stmt = $mysql->prepare($query);
    $stmt->bind_param('ss', $userId, $productId);
    $result = $stmt->execute();
    $stmt->close();
    $mysql->close();
    if ($result) {
        showSuccessMessage();
    } else {
        showErrorAndTerminate();
    }
}

function getProductTags($productId) {
    $mysql = getConnection();
    $query = "SELECT `tag` 
                    FROM `product_tags`
                    WHERE `product_id` = ?";
    $stmt = $mysql->prepare($query);
    $stmt->bind_param('s', $productId);
    $stmt->execute();
    $stmt->bind_result($tag);
    $data = array();
    while ($stmt->fetch()) {
        $data[] = $tag;
    }
    return $data;
}

function getProductPhotos($productId) {
    $mysql = getConnection();
    $query = "SELECT `url` 
                    FROM `product_photos`
                    WHERE `product_id` = ?";
    $stmt = $mysql->prepare($query);
    $stmt->bind_param('s', $productId);
    $stmt->execute();
    $stmt->bind_result($url);
    $data = array();
    while ($stmt->fetch()) {
        $data[] = $url;
    }
    return $data;
}

function getLocalizedBrandName($brandId) {
    $mysql = getConnection();
    $query = "SELECT `en`
                    FROM `product_brands`
                    WHERE `firebase_id` = ?";
    $stmt = $mysql->prepare($query);
    $stmt->bind_param('s', $brandId);
    $stmt->execute();
    $stmt->bind_result($en);

    while ($stmt->fetch()) {
        return array(
            'en' => $en
        );
    }
    return null;
}

function getLocalizedProductType($productType) {
    $mysql = getConnection();
    $query = "SELECT `en`, `dk` 
                    FROM `product_types`
                    WHERE `firebase_id` = ?";
    $stmt = $mysql->prepare($query);
    $stmt->bind_param('s', $productType);
    $stmt->execute();
    $stmt->bind_result($en, $dk);

    while ($stmt->fetch()) {
        return array(
            'en' => $en,
            'dk' => $dk
        );
    }
    return null;
}

function addProduct($base64Data) {
    $product = new Product($base64Data);
    if (!$product->isValid()) {
        showErrorAndTerminate(PRODUCT_SERIALIZE_DATA_INCORRECT);
    }

    $query = "INSERT INTO `products`(
                  `local_id`, `id`, `brand_id`, `category_id`, `city`, `mobile`, 
                  `post_сode`, `street`, `description`, 
                  `product_type_id`, `size`, `user_id`, `price`, `visibility_mode`, `lat`, `lng`) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $mysql = getConnection();
    addOrUpdateBrandAndType($product);
    $localId = generateProductId($mysql);
    $smt = $mysql->prepare($query);
    $smt->bind_param('isssssssssssiidd',
        $localId,
        $product->id,
        $product->brandId,
        $product->categoryId,
        $product->contactInfo->city,
        $product->contactInfo->mobile,
        $product->contactInfo->postCode,
        $product->contactInfo->street,
        $product->description,
        $product->typeId,
        $product->size,
        $product->userId,
        $product->price,
        $product->visibilityMode,
        $product->contactInfo->lat,
        $product->contactInfo->lng);
    $qResult = $smt->execute();
    $message = "";
    if (!$qResult) {
        $message = $smt->error;
    }
    $smt->close();
    if ($qResult) {
        if (fillProductTags($product->id, $product->tags, $mysql)
            && fillProductPhotos($product->id, $product->photos, $mysql)) {
            $mysql->close();
            showSuccessMessage(SUCCESS);
        } else {
            $mysql->close();
            deleteFromTableByField('product', 'id', $product->id, 's');
            showErrorAndTerminate(TAGS_OR_PHOTOS_NOT_SAVE);
        }
    } else {
        $mysql->close();
        showErrorAndTerminate(PRODUCT_NOT_SAVE, $message);
    }
}

function addOrUpdateBrandAndType($product, $mysql = null) {
    $needCloseMysql = false;
    if ($mysql == null) {
        $mysql = getConnection();
        $needCloseMysql = true;
    }

    if (isRecordExists("product_brands", "firebase_id", 's', $product->brandId, $mysql)) {
        $query = "UPDATE `product_brands` SET `en` = ? WHERE `firebase_id` = ?";
        $smt = $mysql->prepare($query);
        $smt->bind_param('ss',$product->brandName->en, $product->brandId);
    } else {
        $query = "INSERT INTO `product_brands`(
                  `firebase_id`, `en`) 
                   VALUES (?, ?)";
        $smt = $mysql->prepare($query);
        $smt->bind_param('ss',$product->brandId, $product->brandName->en);
    }
    $smt->execute();
    $smt->close();

    if (isRecordExists("product_types", "firebase_id", 's', $product->typeId, $mysql)) {
        $query = "UPDATE `product_types` SET `en` = ?, `dk` = ? WHERE `firebase_id` = ?";
        $smt = $mysql->prepare($query);
        $smt->bind_param('sss',$product->typeName->en, $product->typeName->dk, $product->typeId);
    } else {
        $query = "INSERT INTO `product_types`(
                  `firebase_id`, `en`, `dk`) 
                   VALUES (?, ?, ?)";
        $smt = $mysql->prepare($query);
        $smt->bind_param('sss', $product->typeId, $product->typeName->en, $product->typeName->dk);
    }
    $smt->execute();
    $smt->close();
    if ($needCloseMysql) $mysql->close();
}

function isRecordExists($table, $predicate, $bind, $value, $mysql) {
    $query = "SELECT 1 
                    FROM $table
                    WHERE $predicate = ?";
    $stmt = $mysql->prepare($query);
    $stmt->bind_param($bind, $value);
    $stmt->execute();

    $result = false;
    while ($stmt->fetch()) {
        $result = true;
        break;
    }
    $stmt->close();
    return $result;
}

function fillProductTags($firebaseId, $tags, $mysql = null) {
    if ($tags == null || !is_array($tags)) {
        return true;
    }
    if ($mysql == null) $mysql = getConnection();

    $query = "INSERT INTO `product_tags` (`product_id`, `tag`) VALUES (?, ?)";
    $allSuccess = true;
    foreach ($tags as $tag) {
        $smt = $mysql->prepare($query);
        $smt->bind_param('ss',$firebaseId, $tag);
        if (!$smt->execute()) {
            $allSuccess = false;
        }
        $smt->close();
    }
    return $allSuccess;
}

function fillProductPhotos($firebaseId, $photos, $mysql = null) {
    if ($photos == null || !is_array($photos)) {
        return true;
    }
    $needCloseMysql = false;
    if ($mysql == null) {
        $mysql = getConnection();
        $needCloseMysql = true;
    }

    $query = "INSERT INTO `product_photos`(
                  `product_id`, `url`) 
                   VALUES (?, ?)";
    $allSuccess = true;
    foreach ($photos as $photo) {
        $smt = $mysql->prepare($query);
        $smt->bind_param('ss',$firebaseId, $photo);
        if (!$smt->execute()) {
            $allSuccess = false;
        }
        $smt->close();
    }
    if ($needCloseMysql) $mysql->close();
    return $allSuccess;
}

function showErrorAndTerminate($code = BAD_REQUEST, $message = "Internal error") {
    echo json_encode(array('code' => $code, 'message' => $message));
    exit;
}

function showSuccessMessage($code = SUCCESS, $payload = null) {
    if ($payload == null && !is_array($payload)) {
        echo json_encode(array('code' => $code));
    } else {
        echo json_encode(array('code' => $code, 'data' => $payload));
    }
    exit;
};

function generateProductId($mysql = null) {
    $needCloseMysql = false;
    if ($mysql == null) {
        $mysql = getConnection();
        $needCloseMysql = true;
    }

    $query = "SELECT next_val()";
    $stmt = $mysql->prepare($query);
    $stmt->execute();
    $stmt->bind_result($id);
    $generated = null;
    while ($stmt->fetch()) {
        $generated = $id;
    }
    $stmt->close();
    if ($needCloseMysql) $mysql->close();
    if ($generated == null) {
        showErrorAndTerminate(INTERNAL_SERVER_ERROR, "Function next_val() return null");
    }
    return $generated;
}
