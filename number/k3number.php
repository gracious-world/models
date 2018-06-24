<?php

/**
 * 快三号码类
 *
 * @author white
 */
class K3Number {
    
    public $number = '';
    private $attributes = [];
    private $digitals = [];
    private $uniqueDigitals = [];
    
    public function __construct($sNumber) {
        $this->number = $sNumber;
        $this->compileAttributes();
    }
    
    public function compileAttributes(){
        $this->digitals = str_split($this->number);
        $this->uniqueDigitals = array_unique($this->digitals);
        $this->attributes['3diff'] = $this->is3Diff();
        $this->attributes['2diff'] = $this->is2Diff();
        $this->attributes['2same'] = $this->is2same();
        $this->attributes['3same'] = $this->is3same();
        $this->attributes['odd'] = $this->isOdd();
        $this->attributes['big'] = $this->isBig();
        $this->attributes['sum'] = $this->getSum();
        $this->attributes['ordered'] = $this->isOrdered();
        $this->attributes['smallOdd'] = !$this->attributes['big'] && $this->attributes['odd'];
        $this->attributes['smallEven'] = !$this->attributes['big'] && !$this->attributes['odd'];
        $this->attributes['BigOdd'] = $this->attributes['big'] && $this->attributes['odd'];
        $this->attributes['BigEven'] = $this->attributes['big'] && !$this->attributes['odd'];
    }
    
    public function is3Diff(){
        return count($this->uniqueDigitals) == 3;
    }
    
    public function is2Diff(){
        return count($this->uniqueDigitals) >= 2;
    }
    
    public function is2same(){
        return count($this->uniqueDigitals) <= 2;
    }

    public function is3same(){
        return count($this->uniqueDigitals) == 1;
    }
    
    public function isBig(){
        return array_sum($this->digitals) > 9;
    }

    public function isOdd(){
        return array_sum($this->digitals) % 2 == 1;
    }

    public function isOrdered(){
        return max($this->digitals) - min($this->digitals) == 2 && count($this->uniqueDigitals) == 3;
    }
    
    public function getSum(){
        return array_sum($this->digitals);
    }
    
    public function getAttributes(){
        return $this->attributes;
    }
    
    public function getDigitals(){
        return $this->digitals;
    }

    public function getUniqueDigitals(){
        return $this->uniqueDigitals;
    }
    
    public static function compileNumber(){
        for($i = 0,$a = []; $i < 3; $a[$i++] = mt_rand(1,6));
        sort($a);
        return implode($a);
    }
    
    public static function & getAllNumbers($aBalls = [1,2,3,4,5,6], $bOrdered = false){
        $aNumbers = [];
        $iMaxScope = count($aBalls) - 1;
//        if ($bCombin){
            for($i = 0;$i <= $iMaxScope;$i++){
                for($j = $bOrdered ? 0 : $i;$j <= $iMaxScope;$j++){
                    for($k = $bOrdered ? 0 : $j;$k <= $iMaxScope;$k++){
                        $aNumbers[] = $aBalls[$i] . $aBalls[$j] . $aBalls[$k];
                    }
                }
            }
//        }
//        else{
//            for($i = 0;$i <= $iMaxScope;$i++){
//                for($j = 0;$j <= $iMaxScope;$j++){
//                    for($k = 0;$k <= $iMaxScope;$k++){
//                        $aNumbers[] = $aBalls[$i] . $aBalls[$j] . $aBalls[$k];
//                    }
//                }
//            }
//        }
        return $aNumbers;
    }

    static function & getAllNumbersOfSum( $iSum = 0, $bOrdered = true){
        $aNumbers = [];
        for($t = 0,$i = 1;$i <= 6 && $i < $iSum;$i++){
            for($j = $bOrdered ? 1 : $i;$j <= 6;$j++){
//                $t++;
                if ($i + $j >= $iSum){
                    break;
                }
                $k = $iSum - $i - $j;
//                    pr($k);
//                    exit;
//                    $t++;
                if ($k >= 1 && $k <= 6){
                    $sNumber = $i . $j . $k;
                    $bOrdered or $sNumber = self::getCombinNumber($sNumber);
                    $aNumbers[] = $sNumber;
                }
            }
        }
//        pr($t);
        if (!$bOrdered){
            $aNumbers = array_unique($aNumbers);
            sort($aNumbers);
        }
        return $aNumbers;
    }
    
    public static function getCombinNumber($sNumber){
//        if (!static::getShape($sNumber)) return '';
        $aWei = str_split($sNumber,1);
//        !$bUnique or $aWei = array_unique($aWei);
        sort($aWei);
        return implode($aWei);
    }

    public static function & get3DiffNumbers($bOrdered = true,$aBalls = [1,2,3,4,5,6]){
        $aNumbers = [];
//        $aBalls = [1,2,3,4,5,6];
        $iMaxScope = count($aBalls) - 1;
        for($i = 0;$i <= $iMaxScope - 2;$i++){
            for($j = $i + 1;$j <= $iMaxScope - 1;$j++){
                for($k = $j + 1;$k <= $iMaxScope;$k++){
                    $sNumber = $aBalls[$i] . $aBalls[$j] . $aBalls[$k];
                    if ($bOrdered){
                        $aSubNumbers = self::Z6ToOrdered($sNumber);
                        $aNumbers = array_merge($aNumbers, $aSubNumbers);
                    }
                    else{
                        $aNumbers[] = $sNumber;
                    }
                }
            }
        }
//        sort($aNumbers);
//        pr($aNumbers);
        return $aNumbers;
    }

    public static function & Z6ToOrdered($sNumber){
        $aDigitals = str_split($sNumber);
        $a = [
            $aDigitals[0] . $aDigitals[1] . $aDigitals[2],
            $aDigitals[0] . $aDigitals[2] . $aDigitals[1],
            $aDigitals[1] . $aDigitals[0] . $aDigitals[2],
            $aDigitals[1] . $aDigitals[2] . $aDigitals[0],
            $aDigitals[2] . $aDigitals[0] . $aDigitals[1],
            $aDigitals[2] . $aDigitals[1] . $aDigitals[0],
        ];
        return $a;
    }

    public static function & Z3ToOrdered($sNumber){
        $aDigitals = str_split($sNumber);
        if ($aDigitals[0] == $aDigitals[1] && $aDigitals[1] == $aDigitals[2]){
            $a = [$sNumber];
            return $a;
        }
        if ($aDigitals[0] == $aDigitals[1]){
            $iDouble = $aDigitals[0];
            $iSingle = $aDigitals[2];
        }
        else{
            $iDouble = $aDigitals[1];
            $iSingle = $aDigitals[0];
        }
        $a = [
            $iSingle . $iDouble . $iDouble,
            $iDouble . $iSingle . $iDouble,
            $iDouble . $iDouble . $iSingle,
        ];
        return $a;
    }

    public static function & Z3ToOrderedArray($aNumbers){
        $data = [];
        foreach($aNumbers as $sNumber){
            $a = static::Z3ToOrdered($sNumber);
            $data = array_merge($data, $a);
        }
        return $data;
    }

}
