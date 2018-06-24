<?php

/**
 * 终端支付平台管理
 *
 * @author zero
 */
class TerminalPaymentPlatform extends BaseModel {

    protected $table                         = 'terminal_payment_platforms';
    protected static $cacheUseParentClass    = false;
    protected static $cacheLevel             = self::CACHE_LEVEL_FIRST;
    protected static $cacheMinutes           = 0;

    protected $fillable                      = [
        'id',
        'terminal_id',
        'platform_id',
        'created_at',
        'updated_at',
    ];
    public static $sequencable               = false;
    public static $enabledBatchAction        = false;
    protected $validatorMessages             = [];
    protected $isAdmin                       = true;
    public static $resourceName              = 'TerminalPaymentPlatform';
    protected $softDelete                    = false;
    protected $defaultColumns                = ['*'];
    protected $hidden                        = [];
    protected $visible                       = [];
    public static $treeable                  = '';
    public static $foreFatherIDColumn        = '';
    public static $foreFatherColumn          = '';
    public static $columnForList             = [
        'terminal_id',
        'platform_id',
        'updated_at',
    ];
    public static $totalColumns              = [];
    public static $totalRateColumns          = [];
    public static $weightFields              = [];
    public static $classGradeFields          = [];
    public static $floatDisplayFields        = [];
    public static $noOrderByColumns          = [];
    public static $ignoreColumnsInView       = [
    ];
    public static $ignoreColumnsInEdit       = [
        'id',
        'created_at',
        'updated_at',
    ];
    public static $listColumnMaps            = [];
    public static $viewColumnMaps            = [];
    public static $htmlSelectColumns         = [
        'terminal_id' => 'aTerminals',
        'platform_id' => 'aPlatforms',
    ];
    public static $htmlTextAreaColumns       = [];
    public static $htmlNumberColumns         = [];
    public static $htmlOriginalNumberColumns = [];
    public static $amountAccuracy            = 0;
    public static $originalColumns;
    public $orderColumns                     = [];
    public static $titleColumn               = 'terminal_id';
    public static $mainParamColumn           = '';
    public static $rules                     = [
        'terminal_id' => 'required',
        'platform_id' => 'required|integer',
    ];

    protected function beforeValidate() {
        return parent::beforeValidate();
    }
    /**
     *
     * @param $iTerminalId
     * @return mixed
     */
    public static function getTerminalPlatformIds($iTerminalId) {
        $sCachKey = static::getTerminalPlatformCacheKey($iTerminalId);
        if (!($aPlatformIds = Cache::get($sCachKey))) {
            $aPlatformIds = static::where('terminal_id', $iTerminalId)->lists('platform_id');
            Cache::forever($sCachKey, $aPlatformIds);

            return $aPlatformIds;
        }
        return $aPlatformIds;
    }
    /**
     * @param $iTerminalId
     * @param $iPlatformId
     * @return mixed
     */
    public static function hasPlatform($iTerminalId,$iPlatformId){
        $oObj = static::where('terminal_id', $iTerminalId)->where('platform_id',$iPlatformId)->first();
        return $oObj ? true : false;
    }

    public static function getTerminalPlatformCacheKey($iTerminalId) {
        return static::getCachePrefix(true) . 'terminal-platforms-ids' . $iTerminalId;
    }

    protected function afterSave($oSavedModel) {
        $sKey = static::getTerminalPlatformCacheKey($oSavedModel->terminal_id);
        !Cache::has($sKey) or Cache::forget($sKey);
    }
    protected function afterDelete($oDeletedModel) {
        $sKey = static::getTerminalPlatformCacheKey($oDeletedModel->terminal_id);
        !Cache::has($sKey) or Cache::forget($sKey);
        return parent::afterDelete($oDeletedModel);
    }
}
