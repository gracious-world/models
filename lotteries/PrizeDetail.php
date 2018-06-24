<?php

class PrizeDetail extends BaseModel {

    protected static $cacheLevel    = self::CACHE_LEVEL_FIRST;
    protected $table                = 'prize_details';
    protected static $truncatePrize = false;
    protected $softDelete           = false;
    protected $fillable             = [
        'id',
        'series_id',
        'group_id',
        'group_name',
        'classic_prize',
        'probability',
        'method_id',
        'method_name',
        'level',
        'probability',
        'prize',
        'full_prize'
    ];
    public static $amountAccuracy   = 2;

    /**
     * number字段配置
     * @var array
     */
    public static $htmlNumberColumns = [
        'prize' => 2,
        'level' => 0
    ];
    public static $resourceName      = 'Prize Detail';

    /**
     * the columns for list page
     * @var array
     */
    public static $columnForList = [
        'method_id',
        'group_name',
        'level',
//        'probability',
        'prize',
        'updated_at',
    ];

    /**
     * 下拉列表框字段配置
     * @var array
     */
    public static $htmlSelectColumns = [
//        'type' => 'aLotteryTypes',
        'method_id' => 'aBasicMethods',
    ];

    /**
     * order by config
     * @var array
     */
    public $orderColumns = [
        'method_id' => 'asc',
        'level'     => 'asc',
    ];

    /**
     * the main param for index page
     * @var string
     */
    public static $mainParamColumn = 'group_id';
    public $digitalCounts          = [];
    public static $rules           = [
        'group_id'    => 'required|integer',
        'method_id'   => 'required|integer',
        'level'       => 'required|numeric',
        'probability' => 'required|numeric|max:0.9',
        'prize'       => 'numeric',
    ];

    protected function beforeValidate() {
        if ($this->method_id) {
            $oMethod           = BasicMethod::find($this->method_id);
            $this->method_name = $oMethod->name;
        }
        if ($this->prize > $this->full_prize){
            return false;
        }
//        if (empty($this->basic_method_id)){
//            return false;
//        }
//        $oBasicMethod = BasicMethod::find($this->basic_method_id);
//        $this->lottery_type_id = $oBasicMethod->type;
        return parent::beforeValidate();
    }

    private static function compilePrizeDetailCacheKey($iGroupId, $iBasicMethodId) {
        return static::getCachePrefix(true) . $iGroupId . '-' . $iBasicMethodId;
    }

    public static function & getPrizeSetting($iGroupId, $iBasicMethodId) {
        if (!$iGroupId || !$iBasicMethodId) {
            return false;
        }
        $bReadDb   = true;
        $bPutCache = false;
        if (static::$cacheLevel != self::CACHE_LEVEL_NONE) {
            Cache::setDefaultDriver(static::$cacheDrivers[static::$cacheLevel]);
            $sCacheKey = static::compilePrizeDetailCacheKey($iGroupId, $iBasicMethodId);
            if ($aPrize    = Cache::get($sCacheKey)) {
                $bReadDb = false;
            } else {
                $bPutCache = true;
            }
        }
        if ($bReadDb) {
            $oPrizeDetails = static::where('group_id', '=', $iGroupId)->where('method_id', '=', $iBasicMethodId)->get(['level', 'prize']);
            if (!is_object($oPrizeDetails)) {
                return false;
            }
            $aPrize = [];
            foreach ($oPrizeDetails as $oPrizeDetail) {
                $aPrize[$oPrizeDetail->level] = $oPrizeDetail->prize;
            }
            ksort($aPrize);
        }

        if ($bPutCache) {
            Cache::forever($sCacheKey, $aPrize);
        }
        return $aPrize;
    }

//    public static function getPrizes($iGroupId, $iMethodId){
//        $oSettings = static::where('group_id', '=', $iGroupId)->where('method_id', '=', $iMethodId)->get(['level','prize']);
//        $data = [];
//        foreach($oSettings as $oDetail){
//            $data[$oDetail->level] = $oDetail->prize;
//        }
//        ksort($data);
//        return $data;
//    }

    public static function & getDetails($iGroupId) {
        $bReadDb   = true;
        $bPutCache = false;
        if (static::$cacheLevel != self::CACHE_LEVEL_NONE) {
            Cache::setDefaultDriver(static::$cacheDrivers[static::$cacheLevel]);
            $sCacheKey = static::makeCacheKeyOfGroup($iGroupId);
            if ($aDetails  = Cache::get($sCacheKey)) {
                $bReadDb = false;
            } else {
                $bPutCache = true;
            }
        }
        if ($bReadDb) {
            $oPrizeDetails = PrizeDetail::where('group_id', '=', $iGroupId)->where('level', '=', 1)->get();
//        $oBasicMethods = BasicMethod::all();
            $aDetails      = [];
            //        $aBasicMethods = [];
            //        foreach($oBasicMethods as $oBasicMethod){
            //            $aBasicMethods[$oBasicMethod->id] = $oBasicMethod;
            //        }

            foreach ($oPrizeDetails as $oPrizeDetail) {
                $aDetails[$oPrizeDetail->method_id] = $oPrizeDetail->getAttributes();
            }
        }
        if ($bPutCache) {
            Cache::forever($sCacheKey, $aDetails);
        }
        return $aDetails;
    }

    protected function afterSave($oSavedModel) {
        $oSavedModel->deleteDetailCache();
        return parent::afterSave($oSavedModel);
    }

    protected function afterUpdate() {
        $this->deleteCache($this->id);
        $this->deleteDetailCache();
    }

    protected function afterDelete($oDeletedModel) {
        $this->deleteCache($oDeletedModel->id);
        $oDeletedModel->deleteDetailCache();
        return true;
    }

    protected function deleteDetailCache(){
        if (static::$cacheLevel != self::CACHE_LEVEL_NONE) {
            Cache::setDefaultDriver(static::$cacheDrivers[static::$cacheLevel]);
            $sCacheKey = static::makeCacheKeyOfGroupNew($this->group_id);
            Cache::forget($sCacheKey);
        }
        return true;
    }

    public static function & getDetailsNew($iGroupId) {
        $bReadDb   = true;
        $bPutCache = false;
        if (static::$cacheLevel != self::CACHE_LEVEL_NONE) {
            Cache::setDefaultDriver(static::$cacheDrivers[static::$cacheLevel]);
            $sCacheKey = static::makeCacheKeyOfGroupNew($iGroupId);
            if ($aDetails  = Cache::get($sCacheKey)) {
                $bReadDb = false;
            } else {
                $bPutCache = true;
            }
        }
//        $bReadDb = true;
//        $bPutCache = true;
        if ($bReadDb) {
            $oPrizeDetails = PrizeDetail::where('group_id', '=', $iGroupId)
                    ->orderBy('method_id', 'asc')
                    ->orderBy('level', 'asc')->get();
//        $oBasicMethods = BasicMethod::all();
            $aDetails      = [];
            //        $aBasicMethods = [];
            //        foreach($oBasicMethods as $oBasicMethod){
            //            $aBasicMethods[$oBasicMethod->id] = $oBasicMethod;
            //        }
            $iLastMethodId = null;
            foreach ($oPrizeDetails as $oPrizeDetail) {
                if ($iLastMethodId != $oPrizeDetail->method_id) {
                    $aDetails[$oPrizeDetail->method_id] = [
                        'method_id'   => $oPrizeDetail->method_id,
                        'method_name' => $oPrizeDetail->method_name,
                        'level'       => []
                    ];
                    $iLastMethodId                      = $oPrizeDetail->method_id;
                }
                $aDetails[$oPrizeDetail->method_id]['level'][$oPrizeDetail->level] = $oPrizeDetail->prize;
            }
        }
        if ($bPutCache) {
            Cache::forever($sCacheKey, $aDetails);
        }
        return $aDetails;
    }

    public static function countPrize($oSeries, $fClassicPrize, $iHighEstGroup, & $aBasicLevel) {
//        pr(func_get_args());
//        exit;
        switch($oSeries->id){
//            case Series::LOTTERY_SERIES_KLSF:
//            case Series::LOTTERY_SERIES_KENO:
//            case Series::LOTTERY_SERIES_12X5:
//            case Series::LOTTERY_SERIES_K3:
//            case Series::LOTTERY_SERIES_3D:
//            case Series::LOTTERY_SERIES_SSC:
//            case Series::LOTTERY_SERIES_PK10:
            case Series::LOTTERY_SERIES_11Y:
                switch ($oSeries->type) {
                    case Lottery::LOTTERY_TYPE_DIGITAL:
                        $fPrize = formatNumber($fClassicPrize / $oSeries->classic_amount * $aBasicLevel['full_prize'], 4);
                        $aBasicLevel['max_group'] >= $iHighEstGroup or $fPrize *= $aBasicLevel['max_group'] / $iHighEstGroup;
                        break;
                    case Lottery::LOTTERY_TYPE_LOTTO:
                        $fPrize = $aBasicLevel['full_prize'] * ($fClassicPrize - $iHighEstGroup + $aBasicLevel['max_group']) / $oSeries->classic_amount;
                        break;
                }
                break;
            default:
                $fPrize = $aBasicLevel['max_prize'] * $fClassicPrize / $iHighEstGroup;
                break;
        }
        if (static::$truncatePrize) {
            $fPrize = Math::truncateNumber($fPrize, static::$amountAccuracy);
        } else {
            $fPrize = formatNumber($fPrize, static::$amountAccuracy);
        }
        return $fPrize;
    }

    private static function makeCacheKeyOfGroup($iGroupId) {
        return static::getCachePrefix() . '-group-' . $iGroupId;
    }

    private static function makeCacheKeyOfGroupNew($iGroupId) {
        return static::getCachePrefix() . '-new-group-' . $iGroupId;
    }

}
