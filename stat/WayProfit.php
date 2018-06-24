<?php

/**
 * 投注方式维度统计
 *
 * @author system
 */
class WayProfit extends BaseModel {

    protected $table                         = 'way_profits';
    protected static $cacheUseParentClass    = false;
    protected static $cacheLevel             = self::CACHE_LEVEL_FIRST;
    protected static $cacheMinutes           = 0;
    public static $htmlNumberColumns         = [
        'prj_count'     => 0,
        'tester_prj_count'     => 0,
        'net_prj_count'     => 0,
        'turnover'          => 4,
        'prize'             => 6,
        'profit'            => 6,
        'commission'        => 6,
        'tester_turnover'   => 4,
        'tester_prize'      => 6,
        'tester_profit'     => 6,
        'tester_commission' => 6,
        'net_turnover'      => 4,
        'net_prize'         => 6,
        'net_profit'        => 6,
        'net_commission'    => 6,
    ];
    protected $fillable                      = [
        'id',
        'series_id',
        'date',
        'way_id',
        'way',
        'prj_count',
        'tester_prj_count',
        'net_prj_count',
        'turnover',
        'prize',
        'commission',
        'profit',
        'tester_turnover',
        'tester_prize',
        'tester_commission',
        'tester_profit',
        'net_turnover',
        'net_prize',
        'net_commission',
        'net_profit',
        'profit_margin',
        'turnover_ratio',
        'created_at',
        'updated_at',
    ];
    public static $sequencable               = false;
    public static $enabledBatchAction        = false;
    protected $validatorMessages             = [];
    protected $isAdmin                       = true;
    public static $resourceName              = 'WayProfit';
    protected $softDelete                    = false;
    protected $defaultColumns                = [ '*'];
    protected $hidden                        = [];
    protected $visible                       = [];
    public static $treeable                  = '';
    public static $foreFatherIDColumn        = '';
    public static $foreFatherColumn          = '';
    public static $columnForList             = [
        'series_id',
        'date',
        'way',
        'net_prj_count',
        'net_turnover',
        'net_prize',
        'net_commission',
        'net_profit',
        'profit_margin',
        'turnover_ratio',
        'updated_at',
    ];
    public static $totalColumns              = [
        'net_prj_count',
        'net_deposit',
        'net_withdrawal',
        'net_turnover',
        'net_prize',
        'net_bonus',
        'net_commission',
        'net_lose_commission',
        'net_share',
        'net_profit',
        'profit_margin',
    ];
    public static $totalRateColumns          = [
        'profit_margin' => ['net_profit', 'net_turnover']
    ];
    public static $weightFields              = [
        'profit',
        'way',
    ];
    public static $classGradeFields          = [
        'net_profit',
        'profit_margin'
    ];
    public static $floatDisplayFields        = [];
    public static $noOrderByColumns          = [];
    public static $ignoreColumnsInView       = [
    ];
    public static $ignoreColumnsInEdit       = [
    ];
    public static $listColumnMaps            = [
        'profit_margin' => 'profit_margin_formatted',
        'turnover_ratio' => 'turnover_ratio_formatted',
    ];
    public static $viewColumnMaps            = [
        'profit_margin' => 'profit_margin_formatted',
        'turnover_ratio' => 'turnover_ratio_formatted',
    ];
    public static $htmlSelectColumns         = [
        'series_id' => 'aSeries'
    ];
    public static $htmlTextAreaColumns       = [];
    public static $htmlOriginalNumberColumns = [];
    public static $amountAccuracy            = 6;
    public static $originalColumns;
    public $orderColumns                     = [
        'date'         => 'desc',
        'net_turnover' => 'desc',
    ];
    public static $titleColumn               = 'way_id';
    public static $mainParamColumn           = 'date';
    public static $rules                     = [
        'date'              => 'required|date',
        'way_id'            => 'required|integer',
        'way'               => 'max:50',
        'prj_count'         => 'integer',
        'turnover'          => 'numeric',
        'prize'             => 'numeric',
        'commission'        => 'numeric',
        'profit'            => 'numeric',
        'tester_prj_count'  => 'integer',
        'tester_turnover'   => 'numeric',
        'tester_prize'      => 'numeric',
        'tester_commission' => 'numeric',
        'tester_profit'     => 'numeric',
        'net_prj_count'     => 'integer',
        'net_turnover'      => 'numeric',
        'net_prize'         => 'numeric',
        'net_commission'    => 'numeric',
        'net_profit'        => 'numeric',
        'profit_margin'     => 'numeric',
        'turnover_ratio'    => 'numeric',
        'profit_ratio'      => 'numeric',
    ];

    protected function beforeValidate() {
        if (is_null($this->prj_count)) {
            $this->prj_count        = $this->tester_prj_count = $this->net_prj_count    = 0;
        }
        if (!$this->series_id && $this->way_id) {
            $oSeriesWay      = SeriesWay::find($this->way_id);
            $this->series_id = $oSeriesWay->series_id;
        }
        if (!$this->way) {
            isset($oSeriesWay) or $oSeriesWay = SeriesWay::find($this->way_id);
            $oSeries    = Series::find($oSeriesWay->series_id);
            $this->way  = $oSeries->name . '-' . $oSeriesWay->name;
        }
    }

    /**
     * 返回对象
     * @param string $sDate
     * @param int       $iWayId
     * @return UserProfit
     */
    public static function getProfitObject($sDate, $iWayId) {
        $obj = self::where('way_id', '=', $iWayId)->where('date', '=', $sDate)->lockForUpdate()->first();

        if (!is_object($obj)) {
            $data = [
                'way_id' => $iWayId,
                'date'   => $sDate,
            ];
            $obj  = new static($data);
            if (!$obj->save()) {
                pr($obj->getValidationErrorString());
                return false;
            }
            $obj = self::where('way_id', '=', $iWayId)->where('date', '=', $sDate)->lockForUpdate()->first();
        }
        return $obj;
    }

    /**
     * 累加销售额
     * @param float $fAmount
     * @param boolean $bTester
     * @return boolean
     */
    public function addTurnover($fAmount, $bTester = false) {
        $this->turnover += $fAmount;
        $fAmount > 0 ? $this->prj_count++ : $this->prj_count--;
        if ($bTester) {
            $this->tester_turnover += $fAmount;
            $fAmount > 0 ? $this->tester_prj_count++ : $this->tester_prj_count--;
        }
        $this->net_prj_count = $this->prj_count - $this->tester_prj_count;
        $this->calculateProfit($bTester);
        $this->setRatio();
        if (!$bSucc               = $this->save()) {
            file_put_contents('/tmp/lottery_profit', var_export($this->validationErrors->toArray(), true));
        }
        return $bSucc;
    }

    public function calculateProfit($bTester = false) {
        $this->profit         = $this->turnover - $this->prize - $this->commission;
        $this->tester_profit  = $this->tester_turnover - $this->tester_prize - $this->tester_commission;
        $this->net_prj_count  = $this->prj_count - $this->tester_prj_count;
        $this->net_turnover   = $this->turnover - $this->tester_turnover;
        $this->net_prize      = $this->prize - $this->tester_prize;
        $this->net_commission = $this->commission - $this->tester_commission;
        $this->net_profit     = $this->net_turnover - $this->net_prize - $this->net_commission;
//        pr($this->toArray());
        $this->profit_margin  = $this->net_turnover ? $this->net_profit / $this->net_turnover : 0;
    }

    /**
     * 累加奖金
     *
     * @param float $fAmount
     * @param boolean $bTester
     * @return boolean
     */
    public function addPrize($fAmount, $bTester = false) {
        $this->prize += $fAmount;
        !$bTester or $this->tester_prize += $fAmount;
        $this->calculateProfit($bTester);
        return $this->save();
    }

    /**
     * 累加团队佣金
     * @param float $fAmount
     * @param boolean $bDirect
     * @return boolean
     */
    public function addCommission($fAmount, $bTester = false) {
        $this->commission += $fAmount;
        !$bTester or $this->tester_commission += $fAmount;
        $this->calculateProfit($bTester);
        return $this->save();
    }

    public static function updateProfitData($sType, $sDate, $iWayId, $oUser, $fAmount) {
        $oWayProfit = self::getProfitObject($sDate, $iWayId);
        if (!is_object($oWayProfit)) {
            return false;
        }
        $sFunction = 'add' . ucfirst($sType);
        $bSucc     = true;
        return $oWayProfit->$sFunction($fAmount, $oUser->is_tester);
    }

    public function setRatio($oDailyProfit = null) {
        if (is_null($oDailyProfit)) {
            $fTurnover = Profit::getTurnoverFromCache($this->date);
        } else {
            $fTurnover = $oDailyProfit->net_turnover;
        }
        $this->turnover_ratio = $fTurnover > 0 ? $this->net_turnover / $fTurnover : null;
//        return $this->isDirty('turnover_ratio') ? $this->save() : true ;
    }

    protected function getProfitMarginFormattedAttribute() {
        return number_format($this->attributes['profit_margin'] * 100, 2) . '%';
    }

    protected function getTurnoverRatioFormattedAttribute() {
        return number_format($this->attributes['turnover_ratio'] * 100, 2) . '%';
    }

}
