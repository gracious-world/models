<?php
class Coefficient {
    public static $coefficients = [
        '1.000' => '2元',
        '0.500' => '1元',
        '0.100' => '2角',
        '0.050' => '1角',
        '0.010' => '2分',
        '0.001' => '2厘'
    ];

    public static function getValidCoefficientValues(){
        return array_keys(static::$coefficients);
    }
    
    public static function getCoefficientText($key){
        return static::$coefficients[formatNumber($key,3)];
    }
}
