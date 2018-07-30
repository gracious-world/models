<?php

/**
 * 游戏类型模型
 *
 * @author Garin, Winter
 * @date 2017-05-19
 */
class GameType extends BaseModel {
    
    //数字彩
    const GAME_TYPE_DIGITAL = 1;
    //竞彩
    const GAME_TYPE_SPORT = 2;
    //老虎机
    const GAME_TYPE_SLOT = 3;

    const RATE_BASIS_SALES   = 1;
    const RATE_BASIS_DEFICIT = 2;

    /*
     * 状态常量
     */
    const STATUS_CLOSED = 1;
    const STATUS_TESTING = 2;
    const STATUS_AVAILABLE = 3;

    protected $table                         = 'game_types';
    protected static $cacheUseParentClass    = false;
    protected static $cacheLevel             = self::CACHE_LEVEL_FIRST;
    protected static $cacheMinutes           = 0;
    public static $validStatuses             = [];
    public static $validRateBasis            = [
        self::RATE_BASIS_SALES   => 'rate-basis-sales',
        self::RATE_BASIS_DEFICIT => 'rate-basis-deficit'
    ];
    protected $fillable                      = [
        'id',
        'name',
        'identifier',
        'plat_id',
//        'rate_basis',
        'created_at',
        'updated_at',
        'status'
    ];
    public static $sequencable               = false;
    public static $enabledBatchAction        = false;
    protected $validatorMessages             = [];
    protected $isAdmin                       = true;
    public static $resourceName              = 'GameTypes';
    protected $softDelete                    = false;
    protected $defaultColumns                = ['*'];
    protected $hidden                        = [];
    protected $visible                       = [];
    public static $treeable                  = '';
    public static $foreFatherIDColumn        = '';
    public static $foreFatherColumn          = '';
    public static $columnForList             = [
        'id',
        'name',
        'identifier',
        'plat_id',
//        'rate_basis',
        'status',
        'created_at'
    ];
    public static $totalColumns              = [];
    public static $totalRateColumns          = [];
    public static $weightFields              = [];
    public static $classGradeFields          = [];
    public static $floatDisplayFields        = [];
    public static $noOrderByColumns          = [];
    public static $ignoreColumnsInView       = [];
    public static $ignoreColumnsInEdit       = [];
    public static $listColumnMaps            = [
        'status' => 'status_formatted'
    ];
    public static $viewColumnMaps            = [
        'status' => 'status_formatted'
    ];
    public static $htmlSelectColumns         = [
//        'rate_basis' => 'aValidRateBasis',
//        'plat_id' => 'aPlats',
        'status' => 'aStatus',
    ];
    public static $htmlTextAreaColumns       = [];
    public static $htmlNumberColumns         = [];
    public static $htmlOriginalNumberColumns = [];
    public static $amountAccuracy            = 0;
    public static $originalColumns;
    public $orderColumns                     = [
        'id' => 'asc',
    ];
    public static $titleColumn               = 'name';
    public static $mainParamColumn           = '';
    public static $rules                     = [
        'name'       => 'required',
        'identifier' => 'required',
//        'rate_basis' => 'required',
        'plat_id' => 'integer',
        'status' => 'integer',
    ];
    public static $gameTypes                 = [
        1 => "数字彩",
        2 => "竞彩",
        3 => "老虎机",
        5 => "GA游戏",
        6 => "沙巴体育"
    ];

    protected function beforeValidate() {
        $this->plat_id or $this->plat_id = 0;
        return parent::beforeValidate();
    }

    public static $validStatus = [
        self::STATUS_CLOSED => 'closed',
        self::STATUS_TESTING => 'testing',
        self::STATUS_AVAILABLE => 'available'
    ];

    protected function getStatusFormattedAttribute() {
        return $this->status ? __('_gametype.'.static::$validStatus[$this->status]) : null;
    }

    public static function getValidStatus() {
        return parent::_getArrayAttributes(__FUNCTION__);
    }
    /**
     * 获取游戏类型
     * @param $iStatus int 状态
     * @param $aFields array 请求列
     * @return Collection
     */
    public static function getGameTypes($iStatus = null) {
        $cachePrefix = static::getCachePrefix(true);
        $cacheKey    = $cachePrefix . "game-types-$iStatus";
        $expire_at   = Carbon::now()->addMonth();

        $oGameType = Cache::get($cacheKey);
        if (!$oGameType) {
            $oGameType = GameType::getGameTypesByStatus($iStatus);
            Cache::put($cacheKey, $oGameType, $expire_at);
        }
        return $oGameType;
    }

    /**
     * 根据状态获取游戏类别
     * @param null $iStatus
     * @param array $aFields
     * @return mixed
     */
    public static function getGameTypesByStatus($iStatus = null) {
        if (isset(static::$validStatus[$iStatus])) {
            $aStatus = [$iStatus];
            ($iStatus != static::STATUS_TESTING) or $aStatus[] = static::STATUS_AVAILABLE;
            return static::whereIn('status', $aStatus)->get();
        } else {
            return static::get();
        }
    }

    /**
     * 获取identifier 和 id的关联数组
     *
     * @param boolean $isFlip 是否反转成['identifier'=>id] 默认 ['id'=>identifier]
     *
     * @return mixed
     */
    public static function getGameTypesIdentifier($isFlip = false) {
        $cachePrefix = static::getCachePrefix(true);

        if ($isFlip) {
            $cacheKey = $cachePrefix . "gt-identifier-id";
        }
        else {
            $cacheKey = $cachePrefix . "gt-id-identifier";
        }

        $expire_at            = Carbon::now()->addMonth();
        $aGameTypesIdentifier = Cache::get($cacheKey);
        if (!$aGameTypesIdentifier) {
            if (!$isFlip) {
                $aGameTypesIdentifier = GameType::lists('identifier', 'id');
            }
            else {
                $aGameTypesIdentifier = GameType::lists('id', 'identifier');
            }
            Cache::put($cacheKey, $aGameTypesIdentifier, $expire_at);
        }

        return $aGameTypesIdentifier;
    }

    /**
     * 取得数字彩模型
     * @author Wright
     * @date     2017-03-23
     * @return GameType
     */
    public static function getNumberGameType() {
        return static::where('identifier', 'NUMBER')->first();
    }

    public static function getGameTypeByIdentifier($iIdentifier){
        if (static::$cacheLevel == self::CACHE_LEVEL_NONE) {
            return static::where('identifier', $iIdentifier)->first();
        }
        Cache::setDefaultDriver(static::$cacheDrivers[static::$cacheLevel]);
        $key = static::createCacheKey($iIdentifier);
        if (!$obj = Cache::get($key)) {
            $obj = static::where('identifier', $iIdentifier)->first();
            Cache::forever($key, $obj);
        }
        return $obj;

    }
    /**
     * 取得可用的比例依据数组
     * @return array
     */
    public static function getValidRateBasis() {
        return static::_getArrayAttributes(__FUNCTION__);
    }

    /**
     * 取得游戏类型信息数组
     * @return array
     */
    public static function getGameTypeArray() {
        $oGameTypes = self::getGameTypes();
        $aGameTypes = [];
        foreach ($oGameTypes as $oGameType) {
            $aGameTypes[$oGameType->id] = $oGameType->toArray();
        }
        return $aGameTypes;
    }

    /*
     * 加入缓存了的id和name键值对数组
     */
    public static function getGameTypesArrayByStatus($iStatus = null){
        $oGameTypes = self::getGameTypes($iStatus);
        $aGameTypes = [];
        foreach ($oGameTypes as $oGameType) {
            $aGameTypes[$oGameType->id] = $oGameType->name;
        }
        return $aGameTypes;
    }

    protected function afterSave($oSavedModel) {
        //清空缓存
        parent::afterSave($oSavedModel);
        $cachePrefix = static::getCachePrefix(true);
        !Cache::get($cachePrefix.'gt-identifier-id') or Cache::forget($cachePrefix.'gt-identifier-id');
        !Cache::get($cachePrefix.'gt-id-identifier') or Cache::forget($cachePrefix.'gt-id-identifier');
        !Cache::get($cachePrefix.'game-types-') or Cache::forget($cachePrefix.'game-types-');
        $aGameTypes = GameType::get();
        if(!$aGameTypes->count()){
            return true;
        }
        foreach($aGameTypes as $oGameType){
            $cacheKey = static::createCacheKey($oGameType->identifier);
            !Cache::get($cachePrefix . 'game-types-' . $oGameType->status) or Cache::forget($cachePrefix . 'game-types-' . $oGameType->status);
            !Cache::get($cacheKey) or Cache::forget($cacheKey);
        }
        return true;
    }
}
