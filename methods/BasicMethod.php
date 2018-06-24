<?php

class BasicMethod extends BaseModel {

    protected static $cacheLevel       = self::CACHE_LEVEL_FIRST;
    protected $table                   = 'basic_methods';
    protected $softDelete              = false;
    protected $fillable                = [
        'lottery_type',
        'series_id',
        'type',
        'name',
        'full_prize',
        'price',
        'sequencing',
        'digital_count',
        'unique_count',
        'max_repeat_time',
        'min_repeat_time',
        'span',
        'min_span',
        'choose_count',
        'min_choose_count',
        'special_count',
        'fixed_number',
        'valid_nums',
        'buy_length',
        'wn_length',
        'wn_count',
        'all_count',
        'wn_function',
        'status',
//        'sequence',
    ];
    public static $ignoreColumnsInEdit = [
        'lottery_type'
    ];
    public static $resourceName        = 'Basic Method';
    public static $sequencable         = false;

    /**
     * the columns for list page
     * @var array
     */
    public static $columnForList = [
        'id',
        'lottery_type',
        'series_id',
        'type',
        'name',
        'sequencing',
        'digital_count',
        'unique_count',
        'max_repeat_time',
        'min_repeat_time',
        'span',
        'min_span',
        'special_count',
        'choose_count',
        'fixed_number',
        'valid_nums',
        'buy_length',
        'wn_length',
        'wn_count',
        'status',
//        'sequence',
    ];
    public static $titleColumn   = 'name';

    /**
     * 下拉列表框字段配置
     * @var array
     */
    public static $htmlSelectColumns = [
        'lottery_type' => 'aLotteryTypes',
        'series_id'    => 'aSeries',
        'type'         => 'aMethodTypes',
    ];

    /**
     * order by config
     * @var array
     */
    public $orderColumns = [
        'digital_count' => 'asc',
//        'sequence' => 'asc'
    ];

    /**
     * the main param for index page
     * @var string
     */
    public static $mainParamColumn = 'lottery_type';
    public $digitalCounts          = [];
    public static $rules           = [
        'lottery_type'     => 'required|integer',
        'series_id'        => 'required|integer',
        'type'             => 'required|integer',
        'name'             => 'required|max:10',
        'full_prize'       => 'numeric|min:0',
        'digital_count'    => 'required|numeric',
        'sequencing'       => 'required|in:0,1',
        'unique_count'     => 'integer|min:0|max:10',
        'max_repeat_time'  => 'integer|min:0|max:10',
        'min_repeat_time'  => 'integer|min:0|max:10',
        'span'             => 'integer|min:0|max:9',
        'min_span'         => 'integer|min:0|max:9',
        'choose_count'     => 'integer|min:0|max:9',
        'min_choose_count' => 'max:9',
        'special_count'    => 'integer|min:0|max:9',
        'fixed_number'     => 'integer|min:0|max:9',
        'price'            => 'numeric',
        'buy_length'       => 'required|numeric',
        'wn_length'        => 'required|numeric',
        'wn_count'         => 'required|numeric',
        'valid_nums'       => 'required|max:50',
        'all_count'        => 'required|numeric',
        'status'           => 'required|integer|in:0,1',
//        'sequence'      => 'numeric',
    ];
    protected $splitChar;
    protected $splitCharInArea;

    public function __construct(array $attributes = []) {
        parent::__construct($attributes);
    }

    /**
     * 设置splitChar属性
     * @return void
     */
    protected function init() {
        $this->splitChar = Config::get('bet.split_char') or $this->splitChar = '|';
        if (!$this->lottery_type) {
            return;
        }
        if ($this->lottery_type == Lottery::LOTTERY_TYPE_LOTTO) {
            $this->splitCharInArea = Config::get('bet.split_char_lotto_in_area') or $this->splitCharInArea = '';
        }
    }

    protected function beforeValidate() {
        if ($this->series_id) {
            $oSeries            = Series::find($this->series_id);
            $this->lottery_type = $oSeries->type;
        }
        $this->price or $this->price            = Config::get('price.default');
//        $this->indexs or $this->indexs = $this->max('indexs') + 1;
        $this->sequencing or $this->sequencing       = 0;
//        $this->digital_count or $this->digital_count   = null;
        $this->unique_count or $this->unique_count     = null;
        $this->max_repeat_time or $this->max_repeat_time  = null;
        $this->min_repeat_time or $this->min_repeat_time  = null;
        $this->span or $this->span             = null;
        $this->min_span or $this->min_span         = null;
        $this->choose_count or $this->choose_count     = null;
        $this->min_choose_count != '' or $this->min_choose_count = null;
        $this->special_count or $this->special_count    = null;
        $this->fixed_number or $this->fixed_number     = null;
        $this->status           = intval($this->status);
        if (!$this->type) {
            return false;
        }
        $oMethodType       = MethodType::find($this->type);
        $this->wn_function = $oMethodType->wn_function;
        return parent::beforeValidate();
    }

    /**
     * 分析中奖号码
     * @param string $sWinningNumber
     * @return string | array
     */
    public function getWinningNumber($sWinningNumber) {
        $this->init();
//        $a = Config::get('new-prize.wn_number');
//        if (in_array($this->series_id, $a)){
        $sClass = 'Method' . ucfirst(Str::camel($this->wn_function));
        return $sClass::getWinningNumber($sWinningNumber, $this);
//        return $this->getWinningNumberK3($sWinningNumber);
        //        }
//        $sFunction = $this->getWnFunction();
//        return $this->$sFunction($sWinningNumber);
    }

    public function getWinningNumberK3($sWinningNumber) {
        $sClass = 'Method' . ucfirst(Str::camel($this->wn_function));
        return $sClass::getWinningNumber($sWinningNumber, $this);
    }

    /**
     * 计算投注码的中奖注数
     * @param string $sWayFunction
     * @param string $sBetNumber
     * @return int
     */
    public function countBetNumber($sWayFunction, & $sBetNumber) {
        $this->init();
        $sFunction = $this->getCheckFunction($sWayFunction);
//        die($sFunction);
//        file_put_contents('/tmp/check',$sFunction);

        return $this->$sFunction($sBetNumber);
    }

    /**
     * 计奖方法，返回中奖注数或false
     * @param SeriesWay $oSeriesWay
     * @param BasicWay $oBasicWay
     * @param string $sWnNumber
     * @param string $sBetNumber
     * @return int | false
     */
    public function checkPrize($oSeriesWay, $oBasicWay, $sWnNumber, $sBetNumber, $sPosition = null) {
        $this->init();
        switch ($this->series_id) {
            case 1:
            case 3:
                return $this->checkPrizeSSC($oSeriesWay, $oBasicWay, $sWnNumber, $sBetNumber, $sPosition);
                break;
            case 2:
                break;
            case 5:
                break;
            case 4:
                return $this->checkPrizeK3($oSeriesWay, $sWnNumber, $sBetNumber);
        }
        if (in_array($this->series_id, [1, 4])) {
            return $this->checkPrizeK3($oSeriesWay, $sWnNumber, $sBetNumber);
        } else {
            $sFunction = $this->getPrizeFunction($oBasicWay->function);
            return $this->$sFunction($oSeriesWay, $sWnNumber, $sBetNumber);
        }
    }

    public function checkPrizeK3($oSeriesWay, $sWnNumber, $sBetNumber) {
//        return 'prize' . $sWayFunction . ucfirst(Str::camel($this->wn_function));
        $sMethodClass = 'Method' . ucfirst(Str::camel($this->wn_function));
        return $sMethodClass::getWonCount($this, $sWnNumber, $sBetNumber);
    }

//    public function checkPrizeSSC($oSeriesWay,$oBasicWay,$sWnNumber,$sBetNumber,$sPosition = null){
////        return 'prize' . $sWayFunction . ucfirst(Str::camel($this->wn_function));
////        $sMethodClass = 'Method' . ucfirst(Str::camel($this->wn_function));
//        $sMethodClass = 'Way' . $oBasicWay->function . ucfirst(Str::camel($this->wn_function));
//        file_put_contents('/tmp/a',$sMethodClass);
//        return $sMethodClass::getWonCount($this, $sWnNumber,$sBetNumber, $sPosition);
//    }

    /**
     * 返回组选中奖号码
     * @param SeriesMethod $oSeriesMethod
     * @param string $sWinningNumber
     * @return string
     */
//    public function getWnNumberCombin($sWinningNumber){
//        return $this->checkCombinValid($sWinningNumber) ? $sWinningNumber : false;
//    }

    /**
     * 返回合适的计算中奖号码的方法
     * @return string
     */
    public function getWnFunction() {
        return 'getWnNumber' . ucfirst(Str::camel($this->wn_function));
    }

    /**
     * 返回合适的检查投注码是否正确与投注注数的方法
     * @param string $sWayFunction
     * @return string
     */
    public function getCheckFunction($sWayFunction) {
        return 'count' . $sWayFunction . ucfirst(Str::camel($this->wn_function));
    }

    /**
     * 返回合适的判断是否中奖的方法
     * @param string $sWayFunction
     * @return string
     */
    public function getPrizeFunction($sWayFunction) {
        return 'prize' . $sWayFunction . ucfirst(Str::camel($this->wn_function));
    }

    /**
     * 返回直选定位复式的注数
     * @param string $sNumber
     * @return int
     */
//    private function _countSeparatedConstituted(& $sNumber,$mValidNums = null){
//        $aNumbers    = explode($this->splitChar,$sNumber);
//        $aBetNumbers = [];
//        if ($mValidNums){
//            if (!is_array($mValidNums)){
//                $mValidNums = array_fill(0,$this->digital_count,$mValidNums);
//            }
//        }
//        else{
//            $mValidNums = array_fill(0,$this->digital_count,$this->valid_nums);
//        }
//        $iCount      = 1;
//        foreach ($aNumbers as $i => $sPartNumber){
//            if (!preg_match('/^[' . $mValidNums[ $i ] . ']+$/',$sPartNumber)){
//                return 0;
//            }
//            $aDigitals     = array_unique(str_split($sPartNumber));
//            sort($aDigitals);
//            $iCount *= count($aDigitals);
//            $aBetNumbers[] = implode($aDigitals);
//        }
//        $sNumber = implode($this->splitChar,$aBetNumbers);
//        return $iCount;
//    }

    /**
     * 检查乐透型直选单式码是否合法并格式化
     * @param type $sNumber
     * @param type $iMin
     * @param type $iMax
     * @return int
     */
//    public function checkLottoEqualValid(& $sNumber, $iMin, $iMax, $bCombin = false){
//        $aDigitals = array_unique(explode($this->splitCharInArea, $sNumber));
//        foreach($aDigitals as $i => $iDigital){
//            if ($iDigital < $iMin || $iDigital > $iMax){
//                return 0;
//            }
//            $aDigitals[$i] = str_pad($iDigital,2,'0',STR_PAD_LEFT);
//        }
//        $aDigitals = array_unique($aDigitals);
//        if (count($aDigitals) != $this->buy_length){
//            return 0;
//        }
//        !$bCombin or sort($aDigitals);
//        $sNumber = implode($this->splitCharInArea, $aDigitals);
//        return 1;
//    }

    /**
     * 检查大小单双号码是否合法
     * @param string $sNumber
     * @return bool
     */
    public function checkBsde(& $sNumber) {
        $aParts = explode($this->splitChar, $sNumber);
        if (count($aParts) != $this->digital_count) {
            return false;
        }
        $aAllowDigitals = [0, 1, 2, 3];
        $aNumberOfParts = [];
        foreach ($aParts as $sPartNumber) {
            $aDigitals = array_unique(str_split($sPartNumber, 1));
            $aDiff     = array_diff($aDigitals, $aAllowDigitals);
            if (!empty($aDiff)) {
                return false;
            }
            sort($aDigitals);
            $aNumberOfParts = $aDigitals;
        }
        $sNumber = implode($this->splitChar, $aNumberOfParts);
        return true;
    }

    /**
     * 检查趣味号码是否合法
     * @param string $sNumber
     * @return bool
     */
    public function checkInterest(& $sNumber) {
        return $this->_checkInterestAndArea($sNumber, true);
    }

    /**
     * 检查区间号码是否合法
     * @param string $sNumber
     * @return bool
     */
    public function checkArea(& $sNumber) {
        return $this->_checkInterestAndArea($sNumber, false);
    }

    /**
     * 检查不定位号码是否合法
     * @param string $sNumber
     * @return bool
     */
    public function checkContain(& $sNumber) {
        return $this->_checkOriginalSingArea($sNumber);
    }

    /**
     * 检查和尾号码是否合法
     * @param string $sNumber
     * @return bool
     */
    public function checkSumTail(& $sNumber) {
        return $this->_checkOriginalSingArea($sNumber);
    }

    /**
     * 检查区间和趣味玩法投注码的合法性
     * @param string $sNumber
     * @param bool $bInterest
     * @return boolean
     */
    private function _checkInterestAndArea(& $sNumber, $bInterest) {
        $aParts     = explode($this->splitChar, $sNumber);
        $aWnNumbers = [];
        $aPatterns  = [
            0 => '/^[\d]+$/',
            1 => $bInterest ? '/^[01]+$/' : '/^[01234]+$/',
        ];
        foreach ($aParts as $i => $sPartNumber) {
            $sPatternKey = intval($i < $this->special_count);
            if (!preg_match($aPatterns[$sPatternKey], $sPartNumber)) {
                return false;
            }
            $aWnNumbers[] = implode(array_unique(str_split($sPartNumber)));
        }
        $sNumber = implode($this->splitChar, $aWnNumbers);
        return true;
    }

    /**
     * 检查单区复式投注码的合法性
     * @param string $sNumber
     * @return boolean
     */
    private function _checkOriginalSingArea(& $sNumber) {
        if (!preg_match('/^[\d]+$/', $sNumber)) {
            return false;
        }
        $aParts  = array_unique(str_split($sNumber));
        sort($aParts);
        $sNumber = implode($aParts);
        return true;
    }

    /**
     * 按offset来截取中奖号码
     * @param string $sFullWinningNumber
     * @param int $iOffset
     * @return string
     */
    public function getWnNumber($sFullWinningNumber, $bAdjacent, $iOffset, $sPosition = null) {
        switch ($this->lottery_type) {
            case Lottery::LOTTERY_TYPE_DIGITAL:
                if ($bAdjacent) {
                    $sWnNumber = substr($sFullWinningNumber, intval($iOffset), $this->digital_count);
                } else {
                    $aPositions = str_split($sPosition, 1);
                    $sWnNumber  = '';
                    foreach ($aPositions as $i) {
                        $sWnNumber .= $sFullWinningNumber{$i};
                    }
                }
                break;
            case Lottery::LOTTERY_TYPE_LOTTO:
                $this->init();
                $aBalls     = explode($this->splitCharInArea, $sFullWinningNumber);
                $aNeedBalls = [];
                if (!$bAdjacent) {
//                    $aPos = explode(',',$sPosition);
                    $aPos = str_split($sPosition);
                    foreach ($aPos as $iPos) {
                        $aNeedBalls[] = $aBalls[$iPos];
                    }
                } else {
                    for ($i = $iOffset, $j = 0; $j < $this->digital_count; $aNeedBalls[$j++] = $aBalls[$i++])
                        ;
                }
                $sWnNumber = implode($this->splitCharInArea, $aNeedBalls);
                break;
        }
        return $sWnNumber;
    }

    /**
     * 获取奖级列表,键为规则,值为奖级
     * @return array
     */
    public function getPrizeLevels() {
        $aConditions = [
            'basic_method_id' => ['=', $this->id]
        ];
        $oLevels     = PrizeLevel::doWhere($aConditions)->orderBy('level', 'asc')->get(['id', 'level', 'rule']);
        $aLevels     = [];
        foreach ($oLevels as $oLevel) {
            $a = explode(',', $oLevel->rule);
            foreach ($a as $sRule) {
                $aLevels[$sRule] = $oLevel->level;
            }
        }
        return $aLevels;
    }

    public function getPrizeRate() {
        return $this->full_prize / 2000;
    }

    private function getPls($aNumbers, $iChooseCount) {
        $iTotalNumCount = count($aNumbers);
        for ($i = 0, $aPls = []; $i < $iTotalNumCount; $i++) {
            for ($j = $i + 1; $j < $iTotalNumCount; $j++) {
                $aPls[] = [$aNumbers[$i], $aNumbers[$j]];
            }
        }
        return $aPls;
    }

}
