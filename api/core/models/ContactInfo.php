<?php

/**
 * Created by PhpStorm.
 * User: vmitin
 * Date: 22.01.2017
 * Time: 13:01
 */
class ContactInfo {
    var $city;
    var $mobile;
    var $postCode;
    var $street;
    var $lat;
    var $lng;

    function __construct($data) {
        if ($data != null) {
            foreach ($data AS $key => $value) {
                $this->{$key} = $value;
            }
        }
    }

    public function isValid() {
        return !is_null($this->city)
        && !is_null($this->mobile)
        && !is_null($this->postCode)
        && !is_null($this->street)
        && !is_null($this->lat)
        && !is_null($this->lng);
    }
}