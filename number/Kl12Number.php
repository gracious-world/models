<?php

/**
 * 快乐十二号码类
 *
 * @author white
 */
class Kl12Number {

    public $number = '';
    private $attributes = [];
    private $digitals = [];
    private $uniqueDigitals = [];
    private $splitChar = ' ';
    private $sum;

    function __construct($sNumber) {
        $this->number = $sNumber;
        $this->compileAttributes();
    }

    function compileAttributes(){
        $this->digitals = explode($this->splitChar,$this->number);
//        $this->sum = $this->digitals[0] + $this->digitals[1];

//        $this->attributes['balls'] = $this->getDigitals();
//        $this->attributes['sum'] = $this->sum;
//        $this->attributes['sumSmallOdd'] = $this->sumIsSmallOdd();
//        $this->attributes['sumSmallEven'] = $this->sumIsSmallEven();
//        $this->attributes['sumBigOdd'] = $this->sumIsBigOdd();
//        $this->attributes['sumBigEven'] = $this->sumIsBigEven();
//        $this->attributes['dragon0'] = $this->IsDrgon(0);
//        $this->attributes['dragon1'] = $this->IsDrgon(1);
//        $this->attributes['dragon2'] = $this->IsDrgon(2);
//        $this->attributes['dragon3'] = $this->IsDrgon(3);
//        $this->attributes['dragon4'] = $this->IsDrgon(4);
    }

    function sumIsOdd(){
        return $this->sum % 2;
    }

    function sumIsEven(){
        return $this->sum % 2 == 0;
    }

    function sumIsBig(){
        return $this->sum > 10;
    }

    function sumIsSmall(){
        return $this->sum <= 10;
    }

    function sumIsSmallOdd(){
        return $this->sumIsSmall() && $this->sumIsOdd();
    }

    function sumIsSmallEven(){
        return $this->sumIsSmall() && $this->sumIsEven();
    }

    function sumIsBigOdd(){
        return $this->sumIsBig() && $this->sumIsOdd();
    }

    function sumIsBigEven(){
        return $this->sumIsBig() && $this->sumIsEven();
    }

    function IsDrgon($iPos){
        return $this->digitals[$iPos] > $this->digitals[9 - $iPos];
    }

    function getSum(){
        return array_sum($this->digitals);
    }

    function getAttributes(){
        return $this->attributes;
    }

    function getDigitals(){
        return $this->digitals;
    }

    static function compileNumber(){
        for($i = 1,$a = []; $i < 11; $i++){
            $a[] = str_pad($i, 2, '0', STR_PAD_LEFT);
        }
        shuffle($a);
        return implode($this->splitChar,$a);
    }

    static function getAllDigitals(){
        for($i = 1,$a = []; $i <= 12;$a[] = str_pad($i++,2,0,STR_PAD_LEFT));
        return $a;
    }
}
