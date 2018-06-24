<?php
/*
 * 数字彩投注号码类
 */

class DigitalNumber {
    
    /**
     * get shape of number
     * @param string $sNumber
     * @return int
     */
    public static function getShape($sNumber){
        $iDitigtal = strlen($sNumber);
        $aDitigtals = str_split($sNumber);
        sort($aDitigtals);
        $aValues = array_count_values($aDitigtals);
        switch(count($aValues)){
            case 1: // 豹子
                return 0;
                break;
            case 2:
                switch($iDitigtal){
                    case 2:
                        return 2;
                        break;
                    case 3:
                        return 3;
                        break;
                    case 4:
                        $iMaxCount = max($aValues);
                        return ($iMaxCount == 3) ? 4 : 6;
                        break;
                    case 5:
                        $iMaxCount = max($aValues);
                        return ($iMaxCount == 4) ? 5 : 10;
                        break;
                }
                break;
            case 3:
                switch($iDitigtal){
                    case 3:
                        return 6;
                        break;
                    case 4:
                        return 12;
                        break;
                    case 5:
                        $iMaxCount = max($aValues);
                        return ($iMaxCount == 3) ? 20 : 30;
                        break;
                }
                break;
            case 4:
                switch($iDitigtal){
                    case 4:
                        return 24;
                        break;
                    case 5:
                        return 60;
                }
                break;
            case 5:
                return 120;
        }
    }
    
	/**
	 * 检查给定的投注码是否合法
	 *
	 * @param string $sCode
	 * @param integer $iCodeLen				合法的投注码长度
	 * @param integer $sShape				是否组选(仅用于数字三型游戏或类似玩法),0表示是直选,1为不定位,2为二星组选,3为组三,6为组六,16为大小单双
	 * @return boolean
	 */
	public static function checkCode(& $sCode,$iCodeLen,$sShape = -1){
		$sPattern = $sShape == 16 ? '/^[0123]{1,4}\,[0123]{1,4}$/' : '/^\d{' . $iCodeLen . '}$/';
		if (!preg_match($sPattern,$sCode))	return false;
		if ($sShape > -1 && !in_array($sShape,array(1,16))){
			$aShape = explode(',',$sShape);
			if (!in_array(static::getShape($sCode),$aShape))	return false;
			$a = str_split($sCode,1);
			sort($a);
			$sCode = implode($a);
		}
		return true;
	}

    /**
	 * 将直选号码转为组选号码
	 *
	 * @param string $sCode
     * @param bool $bUnique 是否需要唯一
	 * @return string
	 */
    public static function getCombinNumber($sNumber, $bUnique = false){
        if (!static::getShape($sNumber)) return '';

        $aWei = str_split($sNumber,1);
        !$bUnique or $aWei = array_unique($aWei);
        sort($aWei);
        return implode($aWei);
    }
    
    /**
     * Get Sum
     * @param string $sNumber
     * @return int
     */
    public static function getSum($sNumber){
        $aWei = str_split($sNumber);
        return array_sum($aWei);
    }

    /**
     * Get Sum Tail
     * @param string $sNumber
     * @return int
     */
    public static function getSumTail($sNumber){
        return static::getSum($sNumber) % 10;
    }

    /**
     * Get Span
     * @param string $sNumber
     * @return int
     */
    public static function getSpan($sNumber){
        $aWei = str_split($sNumber);
        return max($aWei) - min($aWei);
    }

    public static function & getFullNumbers($sNumber, $sPosition, $iFullLength = 5){
        $aFullNumbers = range(0, 9, 1);
//        $sPosition = $oSeriesWay->position;
//        $oSeries = Series::find($oSeriesWay->series_id);
//        $iFullLength = $oSeries->digital_count;
        $aAllPositions = range(0, $iFullLength - 1, 1);
        $aBetNumberOfPositions = str_split($sNumber,1);
//        pr($aBetNumberOfPositions);
        $aBetPositions = str_split($sPosition, 1);
//        pr($aBetPositions);
//        exit;
        $aFullBetNumbers = [];
        for($i = 0; $i < $iFullLength; $i++){
            if (($k = array_search($i, $aBetPositions)) !== false){
                $aFullBetNumbers[$i] = str_split($aBetNumberOfPositions[$k]);
            }
            else{
                $aFullBetNumbers[$i] = $aFullNumbers;
            }
        }
//        pr($aFullBetNumbers);
//        exit;
        $aNumbers = [];
        
        switch($iFullLength){
            case 5:
                foreach($aFullBetNumbers[0] as $iBall_0){
                    foreach($aFullBetNumbers[1] as $iBall_1){
                        foreach($aFullBetNumbers[2] as $iBall_2){
                            foreach($aFullBetNumbers[3] as $iBall_3){
                                foreach($aFullBetNumbers[4] as $iBall_4){
                                    $aNumbers[] = $iBall_0 . $iBall_1 . $iBall_2 . $iBall_3 . $iBall_4;
                                }
                            }
                        }
                    }
                }
                break;
            case 3:
                foreach($aFullBetNumbers[0] as $iBall_0){
                    foreach($aFullBetNumbers[1] as $iBall_1){
                        foreach($aFullBetNumbers[2] as $iBall_2){
                            $aNumbers[] = $iBall_0 . $iBall_1 . $iBall_2;
                        }
                    }
                }
                break;
        }
        return $aNumbers;
    }
}
