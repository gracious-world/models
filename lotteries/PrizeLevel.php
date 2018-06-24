<?php

class PrizeLevel extends BaseModel {

    protected $table      = 'prize_levels';

    /**
     * 软删除
     * @var boolean
     */
    protected $softDelete = false;
    protected $fillable   = [
        'lottery_type_id',
        'series_id',
        'basic_method_id',
        'level',
        'offical_prize',
        'max_win_count',
        'probability',
        'rule',
        'max_prize',
        'full_prize',
        'max_group',
        'min_water',
    ];
    public static $resourceName      = 'Prize Level';

    /**
     * number字段配置
     * @var array
     */
    public static $htmlNumberColumns = [
        'max_prize' => 2
    ];

    /**
     * the columns for list page
     * @var array
     */
    public static $columnForList = [
        'lottery_type_id',
        'series_id',
        'basic_method_id',
        'level',
        'max_win_count',
        'probability',
        'offical_prize',
        'max_group',
        'full_prize',
        'max_prize',
        'min_water',
        'rule',
    ];

    /**
     * 下拉列表框字段配置
     * @var array
     */
    public static $htmlSelectColumns = [
//        'type' => 'aLotteryTypes',
        'basic_method_id' => 'aBasicMethods',
    ];
    public static $listColumnMaps = [
        'probability' => 'probability_formatted',
        'min_water'   => 'min_water_formatted',
        'full_prize'  => 'full_prize_formatted',
        'max_win_count'  => 'max_win_count_formatted'
    ];
    public static $viewColumnMaps      = [
        'probability' => 'probability_formatted',
        'min_water'   => 'min_water_formatted',
        'full_prize'  => 'full_prize_formatted',
        'max_win_count'  => 'max_win_count_formatted'
    ];
    public static $ignoreColumnsInEdit = [
//        'full_prize',
//        'max_prize',
        'min_water',
    ];

    /**
     * order by config
     * @var array
     */
    public $orderColumns               = [
        'lottery_type_id' => 'asc',
        'basic_method_id' => 'asc',
    ];
    public static $mainParamColumn = 'basic_method_id';
    public $digitalCounts = [];
    public static $rules  = [
        'basic_method_id' => 'required|integer',
        'level'           => 'required|numeric',
        'offical_prize'   => 'numeric',
        'max_win_count'   => 'integer',
        'probability'     => 'numeric|max:0.9',
        'rule'            => 'max:50',
        'max_group'       => 'required|numeric',
        'full_prize'      => 'numeric',
        'max_prize'       => 'numeric',
        'min_water'       => 'numeric|min:0',
    ];

    protected function beforeValidate() {
        if (empty($this->basic_method_id)) {
            return false;
        }
        $oBasicMethod          = BasicMethod::find($this->basic_method_id);
        $this->lottery_type_id = $oBasicMethod->lottery_type;
        $oBasicMethod          = BasicMethod::find($this->basic_method_id);
        $this->basic_method or $this->basic_method    = $oBasicMethod->name;
        if ($this->max_win_count > $oBasicMethod->all_count) {
            return false;
        }
        $oSeries = Series::find($oBasicMethod->series_id);
        if (!$this->probability) {
            $this->probability = $this->max_win_count / $oBasicMethod->all_count;
        }
        else{
                if (substr($this->probability, -1) == '%') {
                    $this->probability = substr($this->probability, 0, -1) / 100;
                }
                if (!$this->max_win_count) {
                    $this->max_win_count = intval($this->probability * $oBasicMethod->all_count);
                }
        }
        if (!$this->full_prize){
            if ($this->offical_prize && $oSeries->offical_prize_rate) {
                $this->full_prize = formatNumber($this->offical_prize / $oSeries->offical_prize_rate, 2);
            } else {
                if ($this->probability) {
                    $this->full_prize = formatNumber(2 / $this->probability, 4);
                }
            }
        }
//        pr(!$this->max_prize);
//        exit;
//        if (empty($this->max_prize)){
//            exit;
        $this->series_id = $oBasicMethod->series_id;
        $this->max_group > 0 or $this->max_group = $oSeries->max_real_group;
//        pr($oSeries->toArray());
//        exit;
        if (!$this->max_prize){
            if ($oSeries->id > 5) {
                $this->max_prize = $this->full_prize * ($this->max_group / 2000);
            } else {
                $this->max_prize = $this->full_prize * ($this->max_group / $oSeries->classic_amount);
            }
            $this->max_prize = Math::truncateNumber($this->max_prize, 2);
        }
        $this->min_water = 1 - $this->max_prize / $this->full_prize;
//        pr($this->toArray());
//        exit;
        //        }
        return parent::beforeValidate();
    }

    private static function compileAllPrizeLevelCacheKey($iTypeId) {
        return static::getCachePrefix(true) . $iTypeId;
    }

    public static function getTheoreticPrizeSets($iTypeId) {
        if (!$iTypeId) {
            return false;
        }
        $bReadDb   = true;
        $bPutCache = false;
        if (static::$cacheLevel != self::CACHE_LEVEL_NONE) {
            Cache::setDefaultDriver(static::$cacheDrivers[static::$cacheLevel]);
            $sCacheKey = static::compileAllPrizeLevelCacheKey($iTypeId);
            if ($aData     = Cache::get($sCacheKey)) {
                $bReadDb = false;
            } else {
                $bPutCache = true;
            }
        }
        if ($bReadDb) {
            $oPrizeLevels = static::where('lottery_type_id', '=', $iTypeId)->get(['basic_method_id', 'level', 'full_prize']);
            if (!is_object($oPrizeLevels)) {
                return false;
            }
            $aData = [];
            foreach ($oPrizeLevels as $oPrizeLevel) {
                $aData[$oPrizeLevel->basic_method_id][$oPrizeLevel->level] = $oPrizeLevel->full_prize;
            }
        }

        if ($bPutCache) {
            Cache::forever($sCacheKey, $aData);
        }
        return $aData;
//        $array = [];
//        $aData = static::where('lottery_type_id' , '=', $iTypeId)->get(['basic_method_id','level','full_prize']);
//        foreach($aData as $model){
//            $array[$model->basic_method_id][$model->level] = $model->full_prize;
//        }
//        return $array;
    }

    protected function getProbabilityFormattedAttribute() {
        return $this->attributes['probability'] ? formatNumber($this->attributes['probability'] * 100, 8) . '%' : null;
    }

    protected function getMaxWinCountFormattedAttribute() {
        return $this->attributes['max_win_count'] ? number_format($this->attributes['max_win_count'] )   : null;
    }

    protected function getMinWaterFormattedAttribute() {
        return formatNumber($this->attributes['min_water'] * 100, 2) . '%';
    }

    protected function getFullPrizeFormattedAttribute() {
        return number_format($this->attributes['full_prize'], 4);
    }

}
