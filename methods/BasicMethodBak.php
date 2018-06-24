<?php 

class BasicMethodBak extends BaseModel {

    protected static $cacheLevel = self::CACHE_LEVEL_FIRST;
    protected $table = 'basic_methods';
    /**
     * 软删除
     * @var boolean
     */
    protected $softDelete = false;
    protected $fillable = [
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
    public static $resourceName = 'Basic Method';
    public static $sequencable  = false;

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

    public static $titleColumn = 'name';
    /**
     * 下拉列表框字段配置
     * @var array
     */
    public static $htmlSelectColumns = [
        'lottery_type' => 'aLotteryTypes',
        'series_id' => 'aSeries',
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
    public $digitalCounts = [];
    public static $rules = [
        'lottery_type'  => 'required|integer',
        'series_id' => 'required|integer',
        'type'          => 'required|integer',
        'name'          => 'required|max:10',
        'full_prize'    => 'numeric|min:0',
        'digital_count' => 'required|numeric',
        'sequencing'      => 'required|in:0,1',
        'unique_count'    => 'integer|min:0|max:10',
        'max_repeat_time' => 'integer|min:0|max:10',
        'min_repeat_time' => 'integer|min:0|max:10',
        'span'            => 'integer|min:0|max:9',
        'min_span'        => 'integer|min:0|max:9',
        'choose_count'    => 'integer|min:0|max:9',
        'special_count'   => 'integer|min:0|max:9',
        'fixed_number'    => 'integer|min:0|max:9',
        'price'         => 'numeric',
        'buy_length'    => 'required|numeric',
        'wn_length'     => 'required|numeric',
        'wn_count'      => 'required|numeric',
        'valid_nums'    => 'required|max:50',
        'all_count'     => 'required|numeric',
        'status'        => 'required|integer|in:0,1',
//        'sequence'      => 'numeric',
    ];

    protected $splitChar;
    protected $splitCharInArea;

    public function __construct(array $attributes = []){
        parent::__construct($attributes);
    }

    /**
     * 设置splitChar属性
     * @return void
     */
    protected function init(){
        $this->splitChar = Config::get('bet.split_char') or $this->splitChar = '|';
        if (!$this->lottery_type){
            return;
        }
        if ($this->lottery_type == Lottery::LOTTERY_TYPE_LOTTO){
            $this->splitCharInArea = Config::get('bet.split_char_lotto_in_area') or $this->splitCharInArea = '';
        }
    }

    protected function beforeValidate(){
        if ($this->series_id){
            $oSeries         = Series::find($this->series_id);
            $this->lottery_type = $oSeries->type;
        }
        $this->price or $this->price = Config::get('price.default');
//        $this->indexs or $this->indexs = $this->max('indexs') + 1;
        $this->sequencing or $this->sequencing      = 0;
//        $this->digital_count or $this->digital_count   = null;
        $this->unique_count or $this->unique_count    = null;
        $this->max_repeat_time or $this->max_repeat_time = null;
        $this->min_repeat_time or $this->min_repeat_time = null;
        $this->span or $this->span            = null;
        $this->min_span or $this->min_span        = null;
        $this->choose_count or $this->choose_count    = null;
        $this->special_count or $this->special_count   = null;
        $this->fixed_number or $this->fixed_number    = null;
        $this->status = intval($this->status);
        if (!$this->type){
            return false;
        }
        $oMethodType = MethodType::find($this->type);
        $this->wn_function = $oMethodType->wn_function;
        return parent::beforeValidate();
    }

    /**
     * 分析中奖号码
     * @param string $sWinningNumber
     * @return string | array
     */
    public function getWinningNumber($sWinningNumber){
        $this->init();
        if ($this->series_id == 4){
            return $this->getWinningNumberK3($sWinningNumber);
        }
        $sFunction = $this->getWnFunction();
        return $this->$sFunction($sWinningNumber);
    }

    public function getWinningNumberK3($sWinningNumber){
        $sClass = 'Method' . ucfirst(Str::camel($this->wn_function));
        return $sClass::getWinningNumber($sWinningNumber);
    }

    /**
     * 计算投注码的中奖注数
     * @param string $sWayFunction
     * @param string $sBetNumber
     * @return int
     */
    public function countBetNumber($sWayFunction,& $sBetNumber){
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
    public function checkPrize($oSeriesWay,$oBasicWay,$sWnNumber,$sBetNumber){
        $this->init();
        if ($this->series_id == 4){
            return $this->checkPrizeK3($oSeriesWay,$sWnNumber,$sBetNumber);
        }
        else{
            $sFunction = $this->getPrizeFunction($oBasicWay->function);
            return $this->$sFunction($oSeriesWay,$sWnNumber,$sBetNumber);
        }
    }

    public function checkPrizeK3($oSeriesWay,$sWnNumber,$sBetNumber){
        $sMethodClass = 'Method' . ucfirst(Str::camel($this->wn_function));
        return $sMethodClass::getWonCount($this, $sWnNumber,$sBetNumber);
    }

    /**
     * 返回直选中奖号码
     * @param SeriesMethod $oSeriesMethod
     * @param string $sWinningNumber
     * @return string
     */
    public function getWnNumberEqual($sWinningNumber){
        if (!is_null($this->span)){
            $aDigitals = str_split($sWinningNumber,1);
            $iSpan     = max($aDigitals) - min($aDigitals);
            if ($iSpan == $this->span){
                if ($this->min_span){
                    $iDigitalCount = count($aDigitals);
                    $aSpan         = [];
                    for ($i = 1; $i < $iDigitalCount; $aSpan[] = abs($aDigitals[ $i ] - $aDigitals[ $i++ - 1 ])) ;
                    $aDigitals[]    = abs($aDigitals[ 0 ] - $aDigitals[ $iDigitalCount - 1 ]);
                    min($aSpan) == $this->min_span or $sWinningNumber = '';
                }
            }
            else{
                $sWinningNumber = '';
            }
        }
        return $sWinningNumber;
    }

    /**
     * 返回组选中奖号码
     * @param SeriesMethod $oSeriesMethod
     * @param string $sWinningNumber
     * @return string
     */
    public function getWnNumberCombin($sWinningNumber){
        return $this->checkCombinValid($sWinningNumber) ? $sWinningNumber : false;
    }

    /**
     * 返回和尾中奖号码
     * @param SeriesMethod $oSeriesMethod
     * @param string $sWinningNumber
     * @return string
     */
    public function getWnNumberSumTail($sWinningNumber){
        return array_sum(str_split($sWinningNumber,1)) % 10;
    }

    /**
     * 返回特殊中奖号码
     * @param SeriesMethod $oSeriesMethod
     * @param string $sWinningNumber
     * @return string
     */
    public function getWnNumberSpecial($sWinningNumber){
        $aWnDigitals = array_unique(str_split($sWinningNumber));
        $bWin        = count($aWnDigitals) == $this->unique_count;
        if ($bWin && $this->unique_count == 3) {
            $iSpan = max($aWnDigitals) - min($aWnDigitals);
            if (!$bWin  = $iSpan == $this->span) {
                if ($iSpan == 9) {
                    rsort($aWnDigitals);
                    $iSpanAB  = $aWnDigitals[0] - $aWnDigitals[1];
                    $iSpanBC  = $aWnDigitals[1] - $aWnDigitals[2];
                    $iMinSpan = min($iSpanAB, $iSpanBC);
                    $bWin     = $iMinSpan == $this->min_span;
                }
            }
        }
        return $bWin ? $this->fixed_number : false;
    }

    /**
     * 返回不定位中奖号码
     * @param string $sWinningNumber
     * @return string
     */
    public function getWnNumberContain($sWinningNumber){
        $aDigitals       = str_split($sWinningNumber,1);
        $aDigitalCount = array_count_values($aDigitals);
        $aUniqueDigitals = array_keys($aDigitalCount);
        $aWnNumber       = [];
        if ($this->min_repeat_time){
            if (count($aDigitalCount) >= $this->choose_count && max($aDigitalCount) >= $this->min_repeat_time){
                foreach ($aDigitalCount as $iDigital => $iCount){
                    $iCount < $this->min_repeat_time or $aWnNumber[] = $iDigital;
                }
            }
        }
        else{
            (count($aDigitalCount) < $this->choose_count) or $aWnNumber = $aUniqueDigitals;
        }
        return $aWnNumber ? $aWnNumber : false;
    }

    /**
     * 返回大小单双中奖号码
     * @param SeriesMethod $oSeriesMethod
     * @param string $sWinningNumber
     * @return string
     */
    public function getWnNumberBsde($sWinningNumber){
        $aDigitals  = str_split($sWinningNumber,1);
        $aWnNumbers = [];
        foreach ($aDigitals as $i => $iDigital){
            $sNumberOfPosition = intval($iDigital > 4); // 大小
            $sNumberOfPosition .= $iDigital % 2 + 2; // 单双
            $aWnNumbers[ $i ]    = $sNumberOfPosition;
        }
        return implode('|',$aWnNumbers);
    }

    /**
     * 返回趣味中奖号码
     * @param SeriesMethod $oSeriesMethod
     * @param string $sWinningNumber
     * @return string
     */
    public function getWnNumberInterest($sWinningNumber){
        $aDigitals  = str_split($sWinningNumber,1);
        $aWnNumbers = [];
        foreach ($aDigitals as $i => $iDigital){
            $aWnNumbers[] = $i < $this->special_count ? intval($iDigital > 4) : $iDigital;
        }
        return implode($aWnNumbers);
    }

    /**
     * 返回区间中奖号码
     * @param SeriesMethod $oSeriesMethod
     * @param string $sWinningNumber
     * @return string
     */
    public function getWnNumberArea($sWinningNumber){
        $aDigitals  = str_split($sWinningNumber,1);
        $aWnNumbers = [];
        foreach ($aDigitals as $i => $iDigital){
            $aWnNumbers[] = $i < $this->special_count ? floor($iDigital / 2) : $iDigital;
        }
        return implode($aWnNumbers);
    }

    /**
     * 返回合适的计算中奖号码的方法
     * @return string
     */
    public function getWnFunction(){
        return 'getWnNumber' . ucfirst(Str::camel($this->wn_function));
    }

    /**
     * 返回合适的检查投注码是否正确与投注注数的方法
     * @param string $sWayFunction
     * @return string
     */
    public function getCheckFunction($sWayFunction){
        return 'count' . $sWayFunction . ucfirst(Str::camel($this->wn_function));
    }

    /**
     * 返回合适的判断是否中奖的方法
     * @param string $sWayFunction
     * @return string
     */
    public function getPrizeFunction($sWayFunction){
        return 'prize' . $sWayFunction . ucfirst(Str::camel($this->wn_function));
    }

    /**
     * 返回直选单式的中奖注数
     * @param string $sWnNumber
     * @param string $sBetNumber
     * @return int
     */
    public function prizeEnumEqual($oSeriesWay,$sWnNumber,$sBetNumber){
        $aBetNumbers = explode($this->splitChar,$sBetNumber);
        $aKeys       = array_keys($aBetNumbers,$sWnNumber);
        return count($aKeys);
    }

    /**
     * 返回组选单式的中奖注数
     * @param string $sWnNumber
     * @param string $sBetNumber
     * @return int
     */
    public function prizeEnumCombin($oSeriesWay,$sWnNumber,$sBetNumber){
        $aBetNumbers = explode($this->splitChar,$sBetNumber);
        $aKeys       = array_keys($aBetNumbers,$sWnNumber);
        return count($aKeys);
    }

    /**
     * 返回混合组选的中奖注数
     * @param string $sWnNumber
     * @param string $sBetNumber
     * @return int
     */
    public function prizeMixCombinCombin($oSeriesWay,$sWnNumber,$sBetNumber){
        $aBetNumbers = explode($this->splitChar,$sBetNumber);
        $aKeys       = array_keys($aBetNumbers,$sWnNumber);
        return count($aKeys);
    }

    /**
     * 返回直选组合的中奖注数
     * @param string $sWnNumber
     * @param string $sBetNumber
     * @return int
     */
    public function prizeMultiSequencingEqual($oSeriesWay, $sWnNumber, $sBetNumber) {
//        pr($sWnNumber);
//        pr($sBetNumber);
//        exit;
//        $aWnDigitals = str_split($sWnNumber);
//        $aBetDigitals = explode($this->splitChar, $sBetNumber);
//        $iCount       = 1;
//        foreach ($aBetDigitals as $i => $sBetDigitals) {
//            $iHit = preg_match("/{$aWnDigitals[$i]}/", $sBetDigitals);
//            if (!$iCount *= strlen($sBetDigitals)) {
//                break;
//            }
//        }
        $iCount = $this->prizeSeparatedConstitutedEqual($oSeriesWay,$sWnNumber,$sBetNumber);
        // 此处可能不对
        return $iCount;
    }

    /**
     * 返回直选和值的中奖注数
     * @param string $sWnNumber
     * @param string $sBetNumber
     * @return int
     */
    public function prizeSumEqual($oSeriesWay,$sWnNumber,$sBetNumber){
        $iSum        = DigitalNumber::getSum($sWnNumber);
        $aBetNumbers = explode($this->splitChar,$sBetNumber);
        return intval(in_array($iSum,$aBetNumbers));
    }

    /**
     * 返回三星特殊的中奖注数
     * @param string $sWnNumber
     * @param string $sBetNumber
     * @return int
     */
    public function prizeSpecialConstitutedSpecial($oSeriesWay,$sWnNumber,$sBetNumber){
        return preg_match("/$sWnNumber/",$sBetNumber);
    }

    /**
     * 返回直选跨度的中奖注数
     * @param string $sWnNumber
     * @param string $sBetNumber
     * @return int
     */
    public function prizeSpanEqual($oSeriesWay,$sWnNumber,$sBetNumber){
        $iSpan       = DigitalNumber::getSpan($sWnNumber);
        $aBetNumbers = str_split($sBetNumber);
        return intval(in_array($iSpan,$aBetNumbers));
    }

    /**
     * 返回组选和值的中奖注数
     * @param string $sWnNumber
     * @param string $sBetNumber
     * @return int
     */
    public function prizeSumCombin($oSeriesWay,$sWnNumber,$sBetNumber){
        $iSum        = DigitalNumber::getSum($sWnNumber);
        $aBetNumbers = explode($this->splitChar,$sBetNumber);
        return intval(in_array($iSum,$aBetNumbers));
    }

    /**
     * 返回和尾的中奖注数
     * @param string $sWnNumber
     * @param string $sBetNumber
     * @return int
     */
    public function prizeSumTailSumTail($oSeriesWay,$sWnNumber,$sBetNumber){
        $iSumTail    = DigitalNumber::getSumTail($sWnNumber);
        $aBetNumbers = str_split($sBetNumber);
        return intval(in_array($iSumTail,$aBetNumbers));
    }

    /**
     * 返回组选包胆的中奖注数
     * @param string $sWnNumber
     * @param string $sBetNumber
     * @return int
     */
    public function prizeNecessaryCombin($oSeriesWay,$sWnNumber,$sBetNumber){
        $aWnDigitals = array_unique(str_split($sWnNumber));
        return intval(in_array($sBetNumber,$aWnDigitals));
    }

    /**
     * 返回趣味玩法的中奖注数
     * @param string $sWnNumber
     * @param string $sBetNumber
     * @return int
     */
    public function prizeFunSeparatedConstitutedInterest($oSeriesWay,$sWnNumber,$sBetNumber){
        return $this->prizeSeparatedConstitutedEqual($oSeriesWay,$sWnNumber,$sBetNumber);
    }

    /**
     * 返回区间玩法的中奖注数
     * @param string $sWnNumber
     * @param string $sBetNumber
     * @return int
     */
    public function prizeSectionalizedSeparatedConstitutedArea($oSeriesWay,$sWnNumber,$sBetNumber){
        return $this->prizeSeparatedConstitutedEqual($oSeriesWay,$sWnNumber,$sBetNumber);
    }

    /**
     * 返回不定位的中奖注数
     * @param string $sWnNumber
     * @param string $sBetNumber
     * @return int
     */
    public function prizeConstitutedContain($oSeriesWay,$aWnNumber,$sBetNumber){
//        pr($aWnNumber);
//        pr($sBetNumber);
//        exit;
        $aBetDigitals = array_unique(str_split($sBetNumber));
        $aBoth        = array_intersect($aWnNumber,$aBetDigitals);
//        pr($aBetDigitals);
//        pr($aBoth);
        $iHitCount    = count($aBoth);
        return $iHitCount >= $this->choose_count ? Math::combin($iHitCount,$this->choose_count) : 0;
    }

    /**
     * @param string $sWnNumber
     * @param string $sBetNumber
     * @return int
     */
//    public function prizeSpecialConstitutedCombin($oSeriesWay,$aWnNumber,$sBetNumber){
//        pr($this->name);
//        pr($aWnNumber);
//        pr($sBetNumber);
//        exit;
//        $aBetDigitals = array_unique(str_split($sBetNumber));
//        $aBoth        = array_intersect($aWnNumber,$aBetDigitals);
////        pr($aBetDigitals);
////        pr($aBoth);
//        $iHitCount    = count($aBoth);
//        return $iHitCount >= $this->choose_count ? Math::combin($iHitCount,$this->choose_count) : 0;
//    }

    /**
     * 返回大小单双的中奖注数
     * @param string $sWnNumber
     * @param string $sBetNumber
     * @return int
     */
    public function prizeBigSmallOddEvenBsde($oSeriesWay,$sWnNumber,$sBetNumber){
//        pr($sWnNumber);
//        pr($sBetNumber);
//        exit;
        $aWnDigitals  = explode($this->splitChar,$sWnNumber);
        $aBetDigitals = explode($this->splitChar,$sBetNumber);
        $iWonCount    = 1;
        foreach ($aWnDigitals as $i => $sWnDigitals){
            $aWnDigitalsOfWei = str_split($sWnDigitals);
            $aBetDigitalsOfWei = str_split($aBetDigitals[ $i ]);
            $aBoth             = array_intersect($aWnDigitalsOfWei,$aBetDigitalsOfWei);
            if (!$iWonCount *= count($aBoth)){
                break;
            }
        }
        return $iWonCount;
    }

    /**
     * 返回直选复式的中奖注数
     * @param string $sWnNumber
     * @param string $sBetNumber
     * @return int
     */
    public function prizeSeparatedConstitutedEqual($oSeriesWay,$sWnNumber,$sBetNumber){
        $aWnDigitals = str_split($sWnNumber);
        $p           = [];
        foreach ($aWnDigitals as $iDigital){
            $p[] = '[\d]*' . $iDigital . '[\d]*';
        }
        $pattern = '/^' . implode('\|', $p) . '$/';
        return preg_match($pattern,$sBetNumber);
    }

    /**
     * 返回定位胆的中奖注数
     * @param string $sWnNumber
     * @param string $sBetNumber
     * @return int
     */
    public function prizeMultiOneEqual($oSeriesWay,$sWnNumber,$sBetNumber){
//        pr($sWnNumber);
//        pr($sBetNumber);
//        pr(preg_match("/$sWnNumber/",$sBetNumber));
//        exit;
        return preg_match("/$sWnNumber/",$sBetNumber);
    }

    /**
     * 计算双区型组选复式的中奖注数
     * @param string $sNumber
     * @return int
     */
    public function prizeConstitutedDoubleAreaCombin($oSeriesWay,$sWnNumber,$sBetNumber){
        $aBetNumber  = explode($this->splitChar,$sBetNumber);
        $aWnDigitals = array_count_values(str_split($sWnNumber));
        $aWnMaxs     = array_keys($aWnDigitals,$this->max_repeat_time);
        $aWnMins     = array_keys($aWnDigitals,$this->min_repeat_time);
        $aDiffMax    = array_diff($aWnMaxs,str_split($aBetNumber[ 0 ]));
        $aDiffMin    = isset($aBetNumber[ 1 ]) ? array_diff($aWnMins,str_split($aBetNumber[ 1 ])) : array_diff($aWnMins,str_split($aBetNumber[ 0 ]));
        return intval(empty($aDiffMax) && empty($aDiffMin));
    }

    /**
     * 计算双区型组选复式的中奖注数
     * @param string $sNumber
     * @return int
     */
    public function prizeConstitutedForCombin30Combin($oSeriesWay,$sWnNumber,$sBetNumber){
        return $this->prizeConstitutedDoubleAreaCombin($oSeriesWay,$sWnNumber,$sBetNumber);
    }

    /**
     * 计算单区型组选复式的中奖注数
     * @param string $sNumber
     * @return int
     */
    public function prizeConstitutedCombin($oSeriesWay,$sWnNumber,$sBetNumber){
        if ($this->max_repeat_time == 1){
            $aBetDigitals = str_split($sBetNumber);
            $aWnDigitals  = str_split($sWnNumber);
            $aDiff        = array_diff($aWnDigitals,$aBetDigitals);
            return intval(empty($aDiff));
        }
        else{
            $aBetNumber  = explode($this->splitChar,$sBetNumber);
            $aWnDigitals = array_count_values(str_split($sWnNumber));
            $aWnMaxs     = array_keys($aWnDigitals,$this->max_repeat_time);
            $aWnMins     = array_keys($aWnDigitals,$this->min_repeat_time);
            $aDiffMax    = array_diff($aWnMaxs,str_split($aBetNumber[ 0 ]));
            $aDiffMin    = isset($aBetNumber[ 1 ]) ? array_diff($aWnMins,str_split($aBetNumber[ 1 ])) : array_diff($aWnMins,str_split($aBetNumber[ 0 ]));
            return intval(empty($aDiffMax) && empty($aDiffMin));
        }
    }

    /**
     * 计算11选5直选单式的中奖注数
     * @param string $sNumber
     * @return int
     */
    private function prizeLottoEqualLottoEqual($oSeriesWay,$sWnNumber,$sBetNumber){
        $aBets = explode($this->splitChar, $sBetNumber);
        return intval(in_array($sWnNumber, $aBets));
    }

    /**
     * 计算11选5任选6中五至任选8中5单式和组选单式的中奖注数
     * @param string $sNumber
     * @return int
     */
    private function prizeLottoEqualLottoCombin($oSeriesWay,$sWnNumber,$sBetNumber){
        $aWnBalls = explode($this->splitCharInArea,$sWnNumber);
        $aBets = explode($this->splitChar, $sBetNumber);
        $iCount = 0;
        foreach($aBets as $sBet){
            $aTmpBalls = explode($this->splitCharInArea,$sBet);
            $aHitBalls = array_intersect($aTmpBalls,$aWnBalls);
            if ($bWon = count($aHitBalls) == $this->wn_length){
                $iCount++;
            }
        }
//        pr(intval($bWon));
//        exit;
        return $iCount;
    }

    /**
     * 计算11选5任选1至任选5单式的中奖注数
     * @param string $sNumber
     * @return int
     */
    private function prizeLottoEqualLottoContain($oSeriesWay,$aWnNumber,$sBetNumber){
//        pr($sBetNumber);
        sort($aWnNumber);
//        $sWnNumber = implode($this->splitCharInArea, $aWnNumber);
//        pr($sWnNumber);
        $aBets = explode($this->splitChar, $sBetNumber);
        $iCount = 0;
//        pr($aWnNumber);
//        pr($aBets);
        foreach($aBets as $sBet){
            $aBetBalls = explode($this->splitCharInArea, $sBet);
            $aHits = array_intersect($aBetBalls, $aWnNumber);
            $iHitNumber = count($aHits);
            $iCount += intval(count($aHits) == $this->choose_count);
        }
//        pr($iCount);
//        exit;
        return $iCount;
    }

    /**
     * 计算11选5任选一至五复式的中奖注数
     * @param string $sNumber
     * @return int
     */
    private function prizeLottoConstitutedLottoContain($oSeriesWay,$aWnNumber,$sBetNumber){
//        pr($oSeriesWay->name);
//        pr($aWnNumber);
//        pr($sBetNumber);
        $iHitCount = $this->_getHitNumbersOfLotto($sBetNumber,$aWnNumber, $iBetBallCount);
        return Math::combin($iHitCount,$this->choose_count);
    }

    /**
     * 计算11选5任选五至八复式的中奖注数
     * @param string $sNumber
     * @return int
     */
    private function prizeLottoConstitutedLottoCombin($oSeriesWay,$sWnNumber,$sBetNumber){
        $iHitCount = $this->_getHitNumbersOfLotto($sBetNumber,$sWnNumber, $iBetBallCount);
        if ($iHitCount < $this->wn_length) return 0;
        $iNeedOtherBallCount = $this->buy_length - $this->wn_length;
        $iUnHitCount = $iBetBallCount - $iHitCount;
        return Math::combin($iUnHitCount, $iNeedOtherBallCount);
    }

    /**
     * 计算11选5任选五至八复式的中奖注数(此处不对）
     * 同时适用于pk10前N非精确直选
     * 
     * @param string $sWnNumber
     * @param string $sBetNumber
     * @return int | array
     */
    private function prizeLottoSeparatedConstitutedLottoEqual($oSeriesWay,$sWnNumber,$sBetNumber){
//        pr($oSeriesWay->name);
//        pr($sWnNumber);
//        pr($sBetNumber);
        $aWnBalls = explode($this->splitCharInArea,$sWnNumber);
        if ($oSeriesWay->series_id == 2){
//            $aWnBalls = explode($this->splitCharInArea,$sWnNumber);
            $aBetBalls = explode($this->splitChar,$sBetNumber);
            $iHitPosCount = 0;
            if (count($aWnBalls) != count($aBetBalls)) return 0;
            foreach($aBetBalls as $i => $sBetNumberOfPos){
                $aBetBallsOfPos = explode($this->splitCharInArea, $sBetNumberOfPos);
                if (!in_array($aWnBalls[$i],$aBetBallsOfPos)){
                    break;
                }
                $iHitPosCount++;
            }
            return $iHitPosCount;
        }
        else{
            $aHited = array_fill(0, count($aWnBalls), 0);
            $aHitedBalls = [];
            $aNonHitedBalls = [];
            $aNonHitedCount = [];
            $aNonHitedPos = [];
            $aBetBalls = explode($this->splitChar,$sBetNumber);
            $iHitPosCount = 0;
            $aWons = [];
            if (count($aWnBalls) != count($aBetBalls)) return $aWons;
            $iPosCount = count($aWnBalls);
            $aBetNumbers = [];
            foreach($aBetBalls as $i => $sBetBall){
                $aBetBallsPos[$i] = explode($this->splitCharInArea, $sBetBall);
            }
            for($i = 0; $i < count($aBetBallsPos[0]);$i++){
                for($j = 0; $j < count($aBetBallsPos[1]);$j++){
                    for($k = 0; $k < count($aBetBallsPos[2]);$k++){
                        $aTmp = [
                            str_pad($aBetBallsPos[0][$i],2,0,STR_PAD_LEFT),
                            str_pad($aBetBallsPos[1][$j],2,0,STR_PAD_LEFT),
                            str_pad($aBetBallsPos[2][$k],2,0,STR_PAD_LEFT),
                        ];
                        $aTmp2 = array_unique($aTmp);
                        if (count($aTmp) != count($aTmp2)){
                            continue;
                        }
                        sort($aTmp);
                        $aBetNumbers[] = implode(' ',$aTmp);
                    }
                }
            }
            $aBetNumbers = array_unique($aBetNumbers);
            sort($aBetNumbers);
            file_put_contents('/tmp/pk-all_numbers', var_export($aBetNumbers, 1) . "\n");
            file_put_contents('/tmp/pk-all_numbers', var_export($aWnBalls, 1), FILE_APPEND);
            $aWons = [];
            foreach($aBetNumbers as $sNumber){
                $aNumbers = explode(' ',$sNumber);
                $iHitedCount = 0;
                $aHited = array_intersect($aNumbers, $aWnBalls);
                $iHitedCount = count($aHited);
//                $aNumbers = explode(' ',$snumber)
//                foreach($aNumbers as $i => $iBall){
////                    if (intval($iBall) != intval($aWnBalls[$i])){
////                        break;
////                    }
//                    $iHitedCount += intval(intval($iBall) == intval($aWnBalls[$i]));
////                    $iHitedCount ++;
//                }
                isset($aWons[$iHitedCount]) or $aWons[$iHitedCount] = 0;
                $aWons[$iHitedCount]++;
            }
            file_put_contents('/tmp/pk-checkprize', var_export($aWons, 1));
            return $aWons;
//            foreach($aBetBalls as $i => $sBetNumberOfPos){
//                $aBetBallsOfPos = explode($this->splitCharInArea, $sBetNumberOfPos);
//                if ($aHited[$i] = in_array($aWnBalls[$i],$aBetBallsOfPos)){
//                    $iHitPosCount++;
//                    $aHitedBalls[$i] = $aWnBalls[$i];
//                    $aNonHitedBalls[$i] = array_diff($aBetBallsOfPos, $aWnBalls[$i]);
//                    $aNonHitedCount[$i] = count($aNonHitedBalls[$i]);
//                }
//                else{
//                    $aHitedBalls[$i] = null;
//                    $aNonHitedPos[] = $i;
//                    $aNonHitedBalls[$i] = $aBetBallsOfPos;
//                    $aNonHitedCount[$i] = count($aBetBallsOfPos);
//                }
//            }
//            $aHitedPos = array_keys($aHitedBalls);
//            $aPos = range(0,$iPosCount);
////            $aNonHitedPos = array_keys($aNonHitedBalls);
//            for($i = $iHitPosCount; $i > 0; $i--){
//                $iWonCount = 1;
//                $aPls = $this->getPls($aHitedPos,$i);
//                foreach($aPls as $aPosHited){
//                    $aNonHitedPosTmp = array_diff($aPos, $aPosHited);
//                    foreach($aNonHitedPosTmp as $iTmpPos){
//                        $iWonCount *= count($aNonHitedBalls[$iTmpPos]);
//                    }
//                }
////                foreach($aHitedBalls as $iPos => $aBalls){
////                    if (!$aBalls){
////                        $iWonCount *= count($aNonHitedBalls);
////                    }
////                }
//                $aWons[$i] = $iWonCount;
////                foreach($aNonHitedBass)
//            }
            return $aWons;
        }
//        pr($iHitPosCount);
//        pr($this->getAttributes());
//        return intval($iHitPosCount == $this->wn_length);
    }

    /**
     * 计算11选5定单双的中奖数字
     * @param string $sWnNumber
     * @param string $sBetNumber
     * @return int
     */
    private function prizeLottoConstitutedLottoOddEven($oSeriesWay,$sWnNumber,$sBetNumber){
//        pr($sWnNumber);
//        pr($sBetNumber);
        $aBetDigitals = explode($this->splitCharInArea,$sBetNumber);
        return intval(in_array($sWnNumber, $aBetDigitals));
//        exit;
    }
    /**
     * 计算11选5任选二至五胆胆拖的中奖注数
     * @param string $sWnNumber
     * @param string $sBetNumber
     * @return int
     */
    private function prizeLottoNecessaryConstitutedLottoContain($oSeriesWay,$aWnNumber,$sBetNumber){
//        pr($oSeriesWay->name);
//        pr($aWnNumber);
//        pr($sBetNumber);
//        pr($this->getAttributes());exit;

        list($sBetNecessaried, $sBetConstituted) = explode($this->splitChar,$sBetNumber);
        $aBetNecessaried = explode($this->splitCharInArea,$sBetNecessaried);
        $aHitNecessaried = array_intersect($aBetNecessaried, $aWnNumber);
        $iHitNessariedCount = count($aHitNecessaried);
        if ($iHitNessariedCount != count($aBetNecessaried)) return 0;
        $iNeedOfNecessariedCount = $this->wn_length - $iHitNessariedCount;
        if ($iNeedOfNecessariedCount == 0) return 1;
        $aBetConstituted = explode($this->splitCharInArea,$sBetConstituted);
        $aHitConstituted = array_intersect($aBetConstituted,$aWnNumber);
        $iHitConstitutedCount= count($aHitConstituted);
        if ($iHitConstitutedCount < $iNeedOfNecessariedCount) return 0;

        return Math::combin($iHitConstitutedCount, $iNeedOfNecessariedCount);
    }

    /**
     * 计算11选5任选六至八胆胆拖的中奖注数
     * @param string $sWnNumber
     * @param string $sBetNumber
     * @return int
     */
    private function prizeLottoNecessaryConstitutedLottoCombin($oSeriesWay,$sWnNumber,$sBetNumber){
//        pr($oSeriesWay->name);
//        pr($sWnNumber);
//        pr($sBetNumber);
//        pr($this->getAttributes());
//        exit;
        $aWnNumber = explode($this->splitCharInArea, $sWnNumber);
        list($sBetNecessaried, $sBetConstituted) = explode($this->splitChar,$sBetNumber);
        $aBetNecessaried = explode($this->splitCharInArea,$sBetNecessaried);
        $aHitNecessaried = array_intersect($aBetNecessaried, $aWnNumber);
        $iBetNecessariedCount = count($aBetNecessaried);

        $iHitNessariedCount = count($aHitNecessaried);
//        if (count($aHitNecessaried) < $this->wn_length) return 0;
//        pr($iHitNessariedCount);
        $iNeedOfBetBallsCount = $this->buy_length - $iBetNecessariedCount;        // 凑足一注投注码还需要的复式码个数
//        if (!$iNeedOfNecessariedCount == 0) return 1;
        $aBetConstituted = explode($this->splitCharInArea,$sBetConstituted);
        $iBetConstitutedCount = count($aBetConstituted);
        if ($iNeedOfBetBallsCount > $iBetConstitutedCount) return 0;                 // 如果复式码个数不足, 则不中奖

        $aHitConstituted = array_intersect($aBetConstituted,$aWnNumber);        // 求出中得的复式码个数
        $iHitConstitutedCount = count($aHitConstituted);
        if ($iBetNecessariedCount + $iHitConstitutedCount > $this->buy_length) return 0;                 // 如果胆码个数+中得的复式码个数,则不中奖

        $iNonHitConstitutedCount = $iBetConstitutedCount - $iHitConstitutedCount;     // 求出未中得的复式码个数
//        pr($iHitConstitutedCount);
//        pr($iHitNessariedCount);
//        pr($iHitConstitutedCount + $iHitNessariedCount);
//        pr($this->wn_length);
//        exit;
        if ($iHitConstitutedCount + $iHitNessariedCount < $this->wn_length) return 0;
//        pr($iHitConstitutedCount);
//        pr($iNeedOfBetBallsCount);
//        exit;
//        if ($iHitConstitutedCount < $iNeedOfNecessariedCount) return 0;
        $iNeedNonHitCount = $iNeedOfBetBallsCount - $iHitConstitutedCount;
//        pr($iNeedNonHitCount);
//        $iCount = Math::combin($iNonHitConstitutedCount, $iNeedNonHitCount);
//        pr($iCount);
//        exit;
        return Math::combin($iNonHitConstitutedCount, $iNeedNonHitCount);
    }

    /**
     * 计算11选5定位胆的中奖注数
     *
     * @param string $sWnNumber
     * @param string $sBetNumber
     * @return int
     */
    private function prizeLottoMultiOneLottoEqual($oSeriesWay,$sWnNumber,$sBetNumber){
//        pr($this->name);
//        pr($sWnNumber);
//        pr($sBetNumber);
//        pr($this->getAttributes());
        $aBetBalls = explode($this->splitCharInArea,$sBetNumber);
//        pr($aBetBalls);
        return intval(in_array($sWnNumber,$aBetBalls));
    }

    /**
     * 11选5猜中位的中奖注数
     * @param string $sWnNumber
     * @param string $sBetNumber
     * @return int
     */
    private function prizeLottoConstitutedLottoMiddle($oSeriesWay,$sWnNumber,$sBetNumber){
//        pr($this->name);
//        pr($sWnNumber);
//        pr($sBetNumber);
        $aBetBalls = explode($this->splitCharInArea, $sBetNumber);
        return intval(in_array($sWnNumber,$aBetBalls));
    }

    private function _getHitNumbersOfLotto($sBetNumber, $mWnNumber, & $iBetBallCount){
        $aWnBalls = is_array($mWnNumber) ? $mWnNumber : explode($this->splitCharInArea, $mWnNumber);
        $aBetBalls = explode($this->splitCharInArea, $sBetNumber);
        $iBetBallCount = count($aBetBalls);
        return count(array_intersect($aBetBalls,$aWnBalls));
    }
    /**
     * 检查直选单式投注号码是否合法
     * @param string $sNumber
     * @return bool
     */
//    public function checkEqualValid($sNumber){
//        $pattern = '/^[\d]{' . $this->digital_count . '}$/';
//        return preg_match($pattern,$sNumber);
//    }

    /**
     * 检查直选单式投注号码是否合法,返回注数.
     * @param string $sNumber
     * @return int
     */
//    public function countEnumEqual(& $sNumber){
//        $aNumbers      = explode($this->splitChar,$sNumber);
//        $aValidNumbers = [];
//        $iCount   = 0;
//        foreach ($aNumbers as $sSNumber){
//            !$this->checkEqualValid($sSNumber) or $aValidNumbers[] = $sSNumber;
//        }
//        $aValidNumbers = array_unique($aValidNumbers);
//        $sNumber       = implode($this->splitChar,$aValidNumbers);
//        return count($aValidNumbers);
//    }

    /**
     * 返回直选复式的投注码数量
     * @param string $sNumber
     * @return int
     */
//    public function countSeparatedConstitutedEqual(& $sNumber){
//        return $this->_countSeparatedConstituted($sNumber);
//    }

    /**
     * 返回直选跨度的投注码数量
     * @param string $sNumber
     * @return int
     */
//    public function countSpanEqual(& $sNumber){
//        $countArray    = [
//            2 => [ 10,18,16,14,12,10,8,6,4,2],
//            3 => [ 10,54,96,126,144,150,144,126,96,54],
//        ];
//        $validDigitals = [2,3];
//        if (!in_array($this->digital_count,$validDigitals) || !isset($countArray[ $this->digital_count ])){
//            return 0;
//        }
//        $aNumbers = str_split($sNumber);
//        $iCount   = 0;
//        foreach ($aNumbers as $iSpan){
//            $iCount += isset($countArray[ $this->digital_count ][ $iSpan ]) ? $countArray[ $this->digital_count ][ $iSpan ] : 0;
//        }
//        return $iCount;
//    }

    /**
     * 返回直选和值的投注码数量
     * @param string $sNumber
     * @return int
     */
//    public function countSumEqual(& $sNumber){
//        $countArray    = [
//            2 => [ 1,2,3,4,5,6,7,8,9,10,9,8,7,6,5,4,3,2,1],
//            3 => [ 1,3,6,10,15,21,28,36,45,55,63,69,73,75,75,73,69,63,55,45,36,28,21,15,10,6,3,1],
//        ];
//        $validDigitals = [2,3];
//        if (!in_array($this->digital_count,$validDigitals) || !isset($countArray[ $this->digital_count ])){
//            return 0;
//        }
//        $aNumbers = explode($this->splitChar,$sNumber);
//        $iCount   = 0;
//        foreach ($aNumbers as $iSum){
//            $iCount += isset($countArray[ $this->digital_count ][ $iSum ]) ? $countArray[ $this->digital_count ][ $iSum ] : 0;
//        }
//        return $iCount;
//    }

//    public function countSumCombin(& $sNumber){
//        $countArray    = [
//            2 => [0,1,1,2,2,3,3,4,4,5,4,4,3,3,2,2,1,1,0],
//            3 => [0,1,2,2,4,5,6,8,10,11,13,14,14,15,15,14,14,13,11,10,8,6,5,4,2,2,1,0]
//        ];
//        $validDigitals = [2,3];
//        if (!in_array($this->digital_count,$validDigitals) || !isset($countArray[ $this->digital_count ])){
//            return 0;
//        }
//        $aNumbers = explode($this->splitChar,$sNumber);
//        $iCount   = 0;
//        foreach ($aNumbers as $iSum){
//            $iCount += isset($countArray[ $this->digital_count ][ $iSum ]) ? $countArray[ $this->digital_count ][ $iSum ] : 0;
//        }
//        return $iCount;
//    }

    /**
     * 返回不定位的投注码数量
     * @param string $sNumber
     * @return int
     */
//    public function countConstitutedContain(& $sNumber){
//        $aDigitals = array_unique(str_split($sNumber));
//        $sNumber    = implode($aDigitals);
//        return Math::combin(count($aDigitals),$this->choose_count);
//    }

    /**
     * 返回大小单双的投注码数量
     * @param string $sNumber
     * @return int
     */
//    public function countBigSmallOddEvenBsde(& $sNumber){
//        return $this->_countSeparatedConstituted($sNumber);
//    }

    /**
     * 返回趣味玩法的投注码数量
     * @param string $sNumber
     * @return int
     */
//    public function countFunSeparatedConstitutedInterest(& $sNumber){
//        $aValidNums = explode($this->splitChar,$this->valid_nums);
//        return $this->_countSeparatedConstituted($sNumber,$aValidNums);
//    }

    /**
     * 返回区间玩法的投注码数量
     * @param string $sNumber
     * @return int
     */
//    public function countSectionalizedSeparatedConstitutedArea(& $sNumber){
//        $aValidNums = explode($this->splitChar,$this->valid_nums);
//        return $this->_countSeparatedConstituted($sNumber,$aValidNums);
//    }

    /**
     * 返回直选定位复式的注数
     * @param string $sNumber
     * @return int
     */
    private function _countSeparatedConstituted(& $sNumber,$mValidNums = null){
        $aNumbers    = explode($this->splitChar,$sNumber);
        $aBetNumbers = [];
        if ($mValidNums){
            if (!is_array($mValidNums)){
                $mValidNums = array_fill(0,$this->digital_count,$mValidNums);
            }
        }
        else{
            $mValidNums = array_fill(0,$this->digital_count,$this->valid_nums);
        }
        $iCount      = 1;
        foreach ($aNumbers as $i => $sPartNumber){
            if (!preg_match('/^[' . $mValidNums[ $i ] . ']+$/',$sPartNumber)){
                return 0;
            }
            $aDigitals     = array_unique(str_split($sPartNumber));
            sort($aDigitals);
            $iCount *= count($aDigitals);
            $aBetNumbers[] = implode($aDigitals);
        }
        $sNumber = implode($this->splitChar,$aBetNumbers);
        return $iCount;
    }

    /**
     * 返回定位胆组合的投注码数量
     * @param string $sNumber
     * @return int
     */
//    public function countMultiOneEqual(& $sNumber){
//        $aNumbers = explode($this->splitChar,$sNumber);
//        $iCount      = 0;
//        $aBetNumbers = [];
//        foreach ($aNumbers as $sNumberOfPos){
//            if (!strlen($sNumberOfPos)){
//                $aBetNumbers[] = '';
//                continue;
//            }
//            $aNums = array_unique(str_split($sNumberOfPos));
//            $iCount += count(array_count_values($aNums));
//            sort($aNums);
//            $aBetNumbers[] = implode($aNums);
//        }
//        $sNumber = implode($this->splitChar,$aBetNumbers);
//        return $iCount;
//    }

    /**
     * 返回组选包胆的投注码数量
     * @param string $sNumber
     * @return int
     */
//    public function countNecessaryCombin(& $sNumber){
//        $countArray = [
//            2 => 9,
//            3 => 54,
//        ];
//        $validDigitals = [2,3];
//        if (!in_array($this->digital_count,$validDigitals)){
//            return 0;
//        }
//        if (isset($countArray[ $this->digital_count ])){
//            $aNetNumbers  = array_unique(explode($this->splitChar,$sNumber));
//            $iNumberCount = count($aNetNumbers);
//            $iCount       = $iNumberCount * $countArray[ $this->digital_count ];
//            $sNumber      = implode($aNetNumbers);
//        }
//        else{
//            $iCount = 0;
//        }
//        return $iCount;
//    }

    /**
     * 返回组选单式的投注码数量
     * @param string $sNumber
     * @return int
     */
//    public function countEnumCombin(& $sNumber){
//        $aNumbers         = explode($this->splitChar,$sNumber);
//        $aValidNumbers = [];
//        $iCount   = 0;
//        foreach ($aNumbers as $sSNumber){
//            !$this->checkCombinValid($sSNumber) or $aValidNumbers[] = $sSNumber;
//        }
//        $aValidNumbers = array_unique($aValidNumbers);
//        sort($aValidNumbers);
//        $sNumber       = implode($this->splitChar,$aValidNumbers);
//        return count($aValidNumbers);
//    }

    /**
     * 返回组选单式的投注码数量
     * @param string $sNumber
     * @return int
     */
//    public function countMixCombinCombin(& $sNumber) {
//        $aNumbers      = explode($this->splitChar,$sNumber);
//        $aValidNumbers = [];
//        $iCount        = 0;
//        foreach ($aNumbers as $sSNumber) {
//            !DigitalNumber::getSpan($sSNumber) or $aValidNumbers[] = DigitalNumber::getCombinNumber($sSNumber);
//        }
//        $aValidNumbers = array_unique($aValidNumbers);
//        sort($aValidNumbers);
//        $sNumber       = implode($this->splitChar,$aValidNumbers);
//        return count($aValidNumbers);
//    }

    /**
     * 返回和尾复式的投注码数量
     * @param string $sNumber
     * @return int
     */
//    public function countSumTailSumTail(& $sNumber){
//        if (preg_match('/^[\d]+$/',$sNumber)){
//            $aDigitals = array_unique(str_split($sNumber));
//            $sNumber   = implode($aDigitals);
//            $iCount    = count($aDigitals);
//        }
//        else{
//            $iCount = 0;
//        }
//        return $iCount;
//    }

    /**
     * 返回五星组选30复式的投注码数量
     * @param string $sNumber
     * @return int
     */
//    public function countConstitutedForCombin30Combin(& $sNumber){
//        $aNums      = explode($this->splitChar,$sNumber);
//        $aValidNums = [];
//        foreach ($aNums as $k => $sNums){
//            $aNumOfArea[ $k ] = str_split($sNums,1);
//            sort($aNumOfArea[ $k ]);
//            $aValidNums[ $k ]   = implode($aNumOfArea[ $k ]);
//        }
//        $aRepeatN         = array_intersect($aNumOfArea[ 1 ],$aNumOfArea[ 0 ]);
//        $iRepeatCountN    = count($aRepeatN);
//        $iNonRepeatCountN = count($aNumOfArea[ 1 ]) - $iRepeatCountN;
//        $iCountM          = count($aNumOfArea[ 0 ]);
//        $iCount           = Math::combin($iRepeatCountN,1) * Math::combin($iCountM - 1,2) + Math::combin($iNonRepeatCountN,1) * Math::combin($iCountM,2);
//        $sNumber          = implode($this->splitChar,$aValidNums);
//        return $iCount;
//    }

    /**
     * 返回双区组选复式的投注码数量，不含五星组选30
     * @param string $sNumber
     * @return int
     */
//    public function countConstitutedDoubleAreaCombin(& $sNumber){
//        $aNums = explode($this->splitChar,$sNumber);
//        $aValidNums = [];
//        foreach ($aNums as $k => $sNums){
//            $aNumOfArea[ $k ] = str_split($sNums,1);
//            sort($aNumOfArea[ $k ]);
//            $aValidNums[ $k ] = implode($aNumOfArea[ $k ]);
//        }
//
//        $iNeedNumCountOfN = ($this->digital_count - $this->max_repeat_time) / $this->min_repeat_time;
//        $aRepeatM         = array_intersect($aNumOfArea[ 0 ],$aNumOfArea[ 1 ]);
//        $iRepeatCountM    = count($aRepeatM);
//        $iNonRepeatCountM = count($aNumOfArea[ 0 ]) - $iRepeatCountM;
//        $iCountN          = count($aNumOfArea[ 1 ]);
//        $iCount           = Math::combin($iRepeatCountM,1) * Math::combin($iCountN - 1,$iNeedNumCountOfN) + Math::combin($iNonRepeatCountM,1) * Math::combin($iCountN,$iNeedNumCountOfN);
//        $sNumber          = implode($this->splitChar,$aValidNums);
//        return $iCount;
//    }

    /**
     * 返回单区组选复式的投注码数量
     * @param string $sNumber
     * @return int
     */
//    public function countSpecialConstitutedSpecial(& $sNumber){
//        $pattern = '/^[012]+$/';
//        if (preg_match($pattern,$sNumber)){
//            $aNumbers = array_unique(str_split($sNumber));
//            sort($aNumbers);
//            $sNumber  = implode($aNumbers);
//            $iCount   = count($aNumbers);
//        }
//        else{
//            $iCount = 0;
//        }
//        return $iCount;
//    }

    /**
     * 返回单区组选复式的投注码数量
     * @param string $sNumber
     * @return int
     */
//    public function countConstitutedCombin(& $sNumber){
//        $aDigitals     = array_unique(str_split($sNumber));
//        $iDigitalCount = count($aDigitals);
//        $iCount        = Math::combin($iDigitalCount,$this->unique_count);
//        if ($this->digital_count == 3 && $this->unique_count == 2){
//            $iCount *= 2;
//        }
//        sort($aDigitals);
//        $sNumber = implode($aDigitals);
//        return $iCount;
//    }

    /**
     * 11选5:前三直选复式注数计算
     * @param string & $sNumber
     * @return int
     */
//    public function countLottoSeparatedConstitutedLottoEqual(& $sNumber){
//        $aNumbers = explode($this->splitChar,$sNumber);
//        $aChoosedBalls = $aInterSectionCounts = $aChoosedCounts = [];
//        foreach($aNumbers as $i => $sNumberOfPos){
//            $aChoosedBalls[$i] = explode(' ', $sNumberOfPos);
//            sort($aChoosedBalls[$i]);
//            $aNumbers[$i] = implode(' ',$aChoosedBalls[$i]);
//        }
//        $sNumber = implode($this->splitChar,$aNumbers);
//        unset($aNumbers, $sNumberOfPos);
//
//        $iPosCount = count($aChoosedBalls);
//        $aAllChoosedBalls = $aChoosedBalls[0];
//        switch($iPosCount){
//            case 3:
//                $a = 1;
//                for($i = 0; $i < $iPosCount;$i++){
//                    $a *= $aChoosedCounts[$i] = count($aChoosedBalls[$i]);
//                    if ($i + 1 < $iPosCount){
//                        $aAllChoosedBalls = array_intersect($aAllChoosedBalls,$aChoosedBalls[$i + 1]);
//                        $aInterSectionCounts[$i . ($i + 1)] = count(array_intersect($aChoosedBalls[$i],$aChoosedBalls[$i + 1]));
//                    }
//                    if ($i + 2 < $iPosCount){
//                        $aInterSectionCounts[$i . ($i + 2)] = count(array_intersect($aChoosedBalls[$i],$aChoosedBalls[$i + 2]));
//                    }
//                }
//                $c = count($aAllChoosedBalls);
//                $b = $aInterSectionCounts['01'] * $aChoosedCounts[2] + $aInterSectionCounts['02'] * $aChoosedCounts[1] + $aInterSectionCounts['12'] * $aChoosedCounts[0];
//                $iCount = $a - $b + $c * 2;
//                break;
//            case 2:
//                $iCount = count($aChoosedBalls[0]) * count($aChoosedBalls[1]) - count(array_intersect($aChoosedBalls[0], $aChoosedBalls[1]));
//                break;
//        }
////        pr($iCount);
////        exit;
//        return $iCount;
////        计算总注数	a*b*c-(D12*c+D13*b+D23*a)+T123*2
//    }

    /**
     * 11选5定单双注数计算
     * @param string $sNumber
     */
//    public function countLottoConstitutedLottoOddEven(& $sNumber){
//        $aDigitals = array_unique(explode($this->splitCharInArea,$sNumber));
//        if (max($aDigitals) > 5 || min($aDigitals) < 0){
//            return 0;
//        }
//        $sNumber = implode($this->splitCharInArea, $aDigitals);
//        return count($aDigitals);
//    }
    /**
     * 11选5:组选复式注数计算
     * @param string & $sNumber
     * @return int
     */
//    public function countLottoConstitutedLottoCombin(& $sNumber){
//        $aChoosedNumbers = array_unique(explode($this->splitCharInArea, $sNumber));
//        list($iMin,$iMax) = explode('-',$this->valid_nums);
//        foreach($aChoosedNumbers as $i => $sChoosedNumber){
//            if ($sChoosedNumber < $iMin || $sChoosedNumber > $iMax){
//                return 0;
//            }
//            $aChoosedNumbers[$i] = str_pad($sChoosedNumber,2,'0',STR_PAD_LEFT);
//        }
//        sort($aChoosedNumbers);
//        $sNumber = implode($this->splitCharInArea,$aChoosedNumbers);
//        $iChoosedCount = count($aChoosedNumbers);
//        return Math::combin($iChoosedCount, $this->buy_length);
//    }

    /**
     * 11选5:猜中位注数计算
     * @param string & $sNumber
     * @return int
     */
//    private function countLottoConstitutedLottoMiddle(& $sNumber){
//        $aChoosedNumbers = array_unique(explode($this->splitCharInArea, $sNumber));
//        list($iMin,$iMax) = explode('-',$this->valid_nums);
//        foreach($aChoosedNumbers as $i => $sChoosedNumber){
//            if ($sChoosedNumber < $iMin || $sChoosedNumber > $iMax){
//                return 0;
//            }
//            $aChoosedNumbers[$i] = str_pad($sChoosedNumber,2,'0',STR_PAD_LEFT);
//        }
//        sort($aChoosedNumbers);
//        $sNumber = implode($this->splitCharInArea,$aChoosedNumbers);
//        return count($aChoosedNumbers);
//    }
    /**
     * 返回11选5任选包胆的投注码数量
     * @param string $sNumber
     * @return int
     */
//    public function countLottoNecessaryConstitutedLottoContain(& $sNumber){
//        @list($sNecessary, $sConstituted) = explode($this->splitChar, $sNumber);
//        if (!isset($sConstituted)){
//            return 0;
//        }
//        $aNecessaries = array_unique(explode($this->splitCharInArea, $sNecessary));
//        $aConstituteds = array_unique(explode($this->splitCharInArea, $sConstituted));
//        if (array_intersect($aNecessaries,$aConstituteds)){
//            return 0;
//        }
//        $iNecessaryCount = count($aNecessaries);
//        $iConstitutedCount = count($aConstituteds);
//        if ($iNecessaryCount >= $this->buy_length){
//            return 0;
//        }
//        $iNeedConstitutedCount = $this->buy_length - $iNecessaryCount;
//        $iCount = Math::combin($iConstitutedCount,$iNeedConstitutedCount);
//        $sNumber = implode($this->splitCharInArea, $aNecessaries) . $this->splitChar . implode($this->splitCharInArea, $aConstituteds);
//        return $iCount;
//    }

    /**
     * 11选5:任选单式注数计算
     * @param string & $sNumber
     * @return int
     */
//    public function countLottoEqualLottoContain (& $sNumber){
////        pr($sNumber);
//        $aBetNumbers = explode($this->splitChar, $sNumber);
////        pr($this->attributes);
//        list($iMin,$iMax) = explode('-',$this->valid_nums);
//        $iCount = 0;
//        $aTrueNumbers = [];
//        foreach($aBetNumbers as $sBetNumber){
//            if (!$this->checkLottoEqualValid($sBetNumber, $iMin, $iMax, true)){
//                return 0;
//            }
//            $aTrueNumbers[] = $sBetNumber;
//            $iCount++;
//        }
//        $sNumber = implode($this->splitChar,$aTrueNumbers);
//        return $iCount;
//    }

//    private function checkLotto(& $sBetNumber,$iMin,$iMax){
//        $aBalls = array_unique(explode($this->splitCharInArea,$sBetNumber));
//        sort($aBalls);
//        if (!$bValid = count($aBalls) == $this->choose_count){
//            return false;
//        }
//        $aTrueBalls = [];
//        foreach($aBalls as $iBall){
//            if (!$bValid = $iBall >= $iMin && $iBall <= $iMax){
//                break;
//            }
//            $aTrueBalls[] = str_pad($iBall,2,'0',STR_PAD_LEFT);
//        }
//        $sBetNumber = implode($this->splitCharInArea, $aTrueBalls);
//        return $bValid;
//    }
    /**
     * 返回11选5不定位的投注码数量
     * @param string $sNumber
     * @return int
     */
//    public function countLottoConstitutedLottoContain(& $sNumber){
//        $aDigitals = array_unique(explode($this->splitCharInArea, $sNumber));
//        $sNumber    = implode($this->splitCharInArea,$aDigitals);
//        return Math::combin(count($aDigitals),$this->choose_count);
//    }

    /**
     * 返回11选5定位胆组合的投注码数量
     * @param string $sNumber
     * @return int
     */
//    public function countLottoMultiOneLottoEqual(& $sNumber){
//        $aNumbers = explode($this->splitChar,$sNumber);
//        $iCount      = 0;
//        $aBetNumbers = [];
//        foreach ($aNumbers as $sNumberOfPos){
//            if (!strlen($sNumberOfPos)){
//                $aBetNumbers[] = '';
//                continue;
//            }
//            $aNums = array_unique(explode($this->splitCharInArea,$sNumberOfPos));
//            $iCount += count(array_count_values($aNums));
//            sort($aNums);
//            $aBetNumbers[] = implode($this->splitCharInArea,$aNums);
//        }
//        $sNumber = implode($this->splitChar,$aBetNumbers);checkLottoEqualValid
//        return $iCount;
//    }

    /**
     * 11选5:直选单式注数计算
     * @param string & $sNumber
     * @return int
     */
//    public function countLottoEqualLottoEqual(& $sNumber){
//        $aBetNumbers = explode($this->splitChar,$sNumber);
//        $iCount = 0;
//        list($iMin,$iMax) = explode('-',$this->valid_nums);
//        foreach($aBetNumbers as $i => $sEnumNumber){
//            if (!$this->checkLottoEqualValid($sEnumNumber, $iMin, $iMax)){
//                return 0;
//            }
//            $aBetNumbers[$i] = $sEnumNumber;
//            $iCount++;
//        }
//        $sNumber = implode($this->splitChar,$aBetNumbers);
//        return $iCount;
//    }

    /**
     * 11选5:组选单式注数计算
     * @param string & $sNumber
     * @return int
     */
//    public function countLottoEqualLottoCombin(& $sNumber){
//        $aBetNumbers = explode($this->splitChar,$sNumber);
//        $iCount = 0;
//        list($iMin,$iMax) = explode('-',$this->valid_nums);
//        foreach($aBetNumbers as $i => $sEnumNumber){
//            if (!$this->checkLottoEqualValid($sEnumNumber, $iMin, $iMax, true)){
//                return 0;
//            }
//            $aBetNumbers[$i] = $sEnumNumber;
//            $iCount++;
//        }
//        $sNumber = implode($this->splitChar,$aBetNumbers);
//        return $iCount;
//    }

    /**
     * 11选5组选胆拖注数计算
     * @param string $sNumber
     * @return int
     */
//    public function countLottoNecessaryConstitutedLottoCombin(& $sNumber){
//        return $this->countLottoNecessaryConstitutedLottoContain($sNumber);
////        $aArea = explode($this->splitChar, $sNumber);
////        if (count($aArea) != 2){
////            return 0;
////        }
////        $aNecessaries = array_unique(explode($this->splitCharInArea,$aArea[0]));
////        $aConstitues = array_unique(explode($this->splitCharInArea,$aArea[1]));
////        $iNecessary = count($aNecessaries);
////        $iConstitue = count($aConstitues);
////        if ($iNecessary >= $this->digital_count || $iNecessary + $iConstitue < $this->digital_count){
////            return 0;
////        }
////        $aBoth = array_intersect($aNecessaries,$aConstitues);
////        if (count($aBoth)){
////            return 0;
////        }
////        list($iMin,$iMax) = explode('-',$this->valid_nums);
////        foreach($aNecessaries as $i => $iDigital){
////            if ($iDigital < $iMin || $iDigital > $iMax){
////                return 0;
////            }
////        }
////        foreach($aConstitues as $i => $iDigital){
////            if ($iDigital < $iMin || $iDigital > $iMax){
////                return 0;
////            }
////        }
////        sort($aNecessaries);
////        sort($aConstitues);
////        $sNumber = implode($this->splitCharInArea,$aNecessaries) . $this->splitChar . implode($this->splitCharInArea,$aConstitues);
////        return Math::combin($iConstitue,$this->digital_count - $iNecessary);
//    }

    /**
     * 检查乐透型直选单式码是否合法并格式化
     * @param type $sNumber
     * @param type $iMin
     * @param type $iMax
     * @return int
     */
    public function checkLottoEqualValid(& $sNumber, $iMin, $iMax, $bCombin = false){
        $aDigitals = array_unique(explode($this->splitCharInArea, $sNumber));
        foreach($aDigitals as $i => $iDigital){
            if ($iDigital < $iMin || $iDigital > $iMax){
                return 0;
            }
            $aDigitals[$i] = str_pad($iDigital,2,'0',STR_PAD_LEFT);
        }
        $aDigitals = array_unique($aDigitals);
        if (count($aDigitals) != $this->buy_length){
            return 0;
        }
        !$bCombin or sort($aDigitals);
        $sNumber = implode($this->splitCharInArea, $aDigitals);
        return 1;
    }
    /**
     * 检查组选单式号码是否合法
     * @param string $sNumber
     * @return bool
     */
    public function checkCombinValid(& $sNumber){
        $aDigitals       = str_split($sNumber,1);
        $aDigitalCount   = array_count_values($aDigitals);
        $iMaxRepeatCount = max($aDigitalCount);
        $iMinRepeatCount = min($aDigitalCount);
        $iUniqueCount    = count($aDigitalCount);
        $iCount          = 0;
        if ($iUniqueCount == $this->unique_count && $iMaxRepeatCount == $this->max_repeat_time && $iMinRepeatCount == $this->min_repeat_time){
            $aUniqueDigitals = array_keys($aDigitalCount);
            sort($aDigitals);
            $sNumber         = implode($aDigitals);
            return true;
        }
        return false;
    }


    /**
     * 检查大小单双号码是否合法
     * @param string $sNumber
     * @return bool
     */
    public function checkBsde(& $sNumber){
        $aParts = explode($this->splitChar,$sNumber);
        if (count($aParts) != $this->digital_count){
            return false;
        }
        $aAllowDigitals = [0,1,2,3];
        $aNumberOfParts = [];
        foreach ($aParts as $sPartNumber){
            $aDigitals = array_unique(str_split($sPartNumber,1));
            $aDiff     = array_diff($aDigitals,$aAllowDigitals);
            if (!empty($aDiff)){
                return false;
            }
            sort($aDigitals);
            $aNumberOfParts = $aDigitals;
        }
        $sNumber = implode($this->splitChar,$aNumberOfParts);
        return true;
    }

    /**
     * 检查趣味号码是否合法
     * @param string $sNumber
     * @return bool
     */
    public function checkInterest(& $sNumber){
        return $this->_checkInterestAndArea($sNumber,true);
    }

    /**
     * 检查区间号码是否合法
     * @param string $sNumber
     * @return bool
     */
    public function checkArea(& $sNumber){
        return $this->_checkInterestAndArea($sNumber,false);
    }

    /**
     * 检查不定位号码是否合法
     * @param string $sNumber
     * @return bool
     */
    public function checkContain(& $sNumber){
        return $this->_checkOriginalSingArea($sNumber);
    }

    /**
     * 检查和尾号码是否合法
     * @param string $sNumber
     * @return bool
     */
    public function checkSumTail(& $sNumber){
        return $this->_checkOriginalSingArea($sNumber);
    }

    /**
     * 检查区间和趣味玩法投注码的合法性
     * @param string $sNumber
     * @param bool $bInterest
     * @return boolean
     */
    private function _checkInterestAndArea(& $sNumber,$bInterest){
        $aParts     = explode($this->splitChar,$sNumber);
        $aWnNumbers = [];
        $aPatterns  = [
            0 => '/^[\d]+$/',
            1 => $bInterest ? '/^[01]+$/' : '/^[01234]+$/',
        ];
        foreach ($aParts as $i => $sPartNumber){
            $sPatternKey = intval($i < $this->special_count);
            if (!preg_match($aPatterns[ $sPatternKey ],$sPartNumber)){
                return false;
            }
            $aWnNumbers[] = implode(array_unique(str_split($sPartNumber)));
        }
        $sNumber = implode($this->splitChar,$aWnNumbers);
        return true;
    }

    /**
     * 检查单区复式投注码的合法性
     * @param string $sNumber
     * @return boolean
     */
    private function _checkOriginalSingArea(& $sNumber){
        if (!preg_match('/^[\d]+$/',$sNumber)){
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
    public function getWnNumber($sFullWinningNumber,$iOffset,$sPosition = null){
        switch($this->lottery_type){
            case Lottery::LOTTERY_TYPE_DIGITAL:
                $sWnNumber = substr($sFullWinningNumber,intval($iOffset),$this->digital_count);
                break;
            case Lottery::LOTTERY_TYPE_LOTTO:
                $this->init();
                $aBalls = explode($this->splitCharInArea,$sFullWinningNumber);
                $aNeedBalls = [];
                if ($sPosition){
                    $aPos = explode(',',$sPosition);
                    foreach($aPos as $iPos){
                        $aNeedBalls[] = $aBalls[$iPos];
                    }
                }
                else{
                    for($i = $iOffset, $j = 0; $j < $this->digital_count; $aNeedBalls[$j++] = $aBalls[$i++]);
                }
                $sWnNumber = implode($this->splitCharInArea,$aNeedBalls);
                break;
        }
        return $sWnNumber;
    }

    /**
     * 获取奖级列表,键为规则,值为奖级
     * @return array
     */
    public function getPrizeLevels(){
        $aConditions = [
            'basic_method_id' => ['=',$this->id]
        ];
        $oLevels = PrizeLevel::doWhere($aConditions)->orderBy('level','asc')->get(['id','level','rule']);
        $aLevels = [];
        foreach($oLevels as $oLevel){
            $a = explode(',',$oLevel->rule);
            foreach($a as $sRule){
                $aLevels[$sRule] = $oLevel->level;
            }
        }
        return $aLevels;
    }
    
    public function getPrizeRate(){
        return $this->full_prize / 2000;
    }
    
    //=======================以下为单区乐透型的各玩法中奖号码方法===================================
    /**
     *
     * @param string $sWinningNumber
     * @return string
     */
    public function getWnNumberLottoCombin($sWinningNumber){
        return $sWinningNumber;
    }

    /**
     * get middle
     *
     * @param string $sWinningNumber
     * @return string | number
     */
    public function getWnNumberLottoMiddle($sWinningNumber){
        $aBalls = explode($this->splitCharInArea, $sWinningNumber);
        sort($aBalls);
        return $aBalls[2];
    }

    /**
     * 返回11选5不定位中奖号码
     * @param string $sWinningNumber
     * @return array
     */
    public function getWnNumberLottoContain($sWinningNumber){
        $aDigitals       = explode($this->splitCharInArea,$sWinningNumber);
        $aDigitalCount = array_count_values($aDigitals);
        $aUniqueDigitals = array_keys($aDigitalCount);
        $aWnNumber       = [];
        if ($this->min_repeat_time){
            if (count($aDigitalCount) >= $this->choose_count && max($aDigitalCount) >= $this->min_repeat_time){
                foreach ($aDigitalCount as $iDigital => $iCount){
                    $iCount < $this->min_repeat_time or $aWnNumber[] = $iDigital;
                }
            }
        }
        else{
            (count($aDigitalCount) < $this->choose_count) or $aWnNumber = $aUniqueDigitals;
        }
//        Log::info(var_export($aWnNumber,1));
//        exit;
        return $aWnNumber ? $aWnNumber : false;
    }

    /**
     * 返回11选5直选中奖号码
     * @param string $sWinningNumber
     * @return string
     */
    public function getWnNumberLottoEqual($sWinningNumber){
        return $sWinningNumber;
//        pr($sWinningNumber);
//        exit;
    }

    /**
     * 返回猜单双
     * @param string $sWinningNumber
     * @return string
     */
    public function getWnNumberLottoOddEven($sWinningNumber){
        $aBalls = explode($this->splitCharInArea, $sWinningNumber);
        $iOddCount = 0;
        foreach($aBalls as $iBall){
            $iOddCount += $iBall % 2;
        }
        return $iOddCount;
    }

    public function getWnNumberLottoSum($sWinningNumber){
        $aBalls = explode($this->splitCharInArea,$sWinningNumber);
        return $aBalls[0] + $aBalls[1];
    }
    
    public function getWnNumberLottoSumBsde($sWinningNumber){
        $iSum = $this->getWnNumberLottoSum($sWinningNumber);
        $aWnNumbers = [];
        $aWnNumbers[] = $iSum > 10;
        $aWnNumbers[] = $iSum % 2 + 2;
        return implode($aWnNumbers);
    }

    public function getWnNumberLottoDragon($sWinningNumber){
        file_put_contents('/tmp/dragon', $sWinningNumber);
        $aBalls = explode($this->splitCharInArea,$sWinningNumber);
        return intval($aBalls[0] > $aBalls[1]);
    }

    private function getPls($aNumbers, $iChooseCount){
        $iTotalNumCount = count($aNumbers);
        for($i = 0,$aPls = [];$i < $iTotalNumCount; $i++){
            for($j = $i + 1; $j < $iTotalNumCount;$j++){
                $aPls[] = [$aNumbers[$i],$aNumbers[$j]];
            }
        }
        return $aPls;
    }
}