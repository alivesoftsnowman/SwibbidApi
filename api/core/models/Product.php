<?php

/**
 * Created by PhpStorm.
 * User: vmitin
 * Date: 22.01.2017
 * Time: 12:59
 */
class Product {
    var $id;
    var $brandId;
    var $brandName;
    var $categoryId;
    var $contactInfo;
    var $created;
    var $description;
    var $photos;
    var $price;
    var $typeId;
    var $typeName;
    var $size;
    var $tags;
    var $userId;
    var $views;
    var $visibilityMode;

    function __construct($base64Data) {
        $data = json_decode(base64_decode($base64Data));
        if ($data != null) {
            foreach ($data AS $key => $value) {
                if ($key == 'contactInfo') continue;
                $this->{$key} = $value;
            }
            $this->contactInfo = new ContactInfo($data->contactInfo);
        }
        if (is_null($this->size)) {
            $this->size = "";
        }
	if (is_null($this->typeId)) {
            $this->typeId = "";
        }
	if (is_null($this->typeName)) {
            $this->typeName = "";
        }
    }

    public function isValid() {
        return !is_null($this->id)
        && !is_null($this->brandId)
        && !is_null($this->brandName)
        && !is_null($this->categoryId)
        && $this->contactInfo->isValid()
        && !is_null($this->description)
        && !is_null($this->photos)
        && !is_null($this->price)
        && !is_null($this->typeId)
        && !is_null($this->typeName)
        && !is_null($this->userId)
        && !is_null($this->visibilityMode);
    }
}
