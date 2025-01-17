<?php

class ThirdPlat extends BaseModel {

    const STATUS_CLOSED = 1;
    const STATUS_TESTING = 2;
    const STATUS_AVAILABLE = 3;

    protected static $cacheLevel = self::CACHE_LEVEL_FIRST;

    protected $table                   = 'third_plats';
    protected $softDelete              = false;
    public $timestamps                 = false; // 取消自动维护新增/编辑时间
    protected $fillable                = [
        'id',
        'identity',
        'name',
        'plat_identity',
        'key',
        'iframe_url',
        'data_url',
        'public_key',
        'private_key',
        'status',
        'params_key'
    ];
    public static $resourceName        = 'ThirdPlat';
    public static $titleColumn         = 'identity';
    public static $htmlTextAreaColumns = [
        'public_key',
        'private_key'
    ];
    public static $columnForList       = [
        'name',
        'identity',
        'plat_identity',
        'status',
        'iframe_url',
        'data_url',
    ];
    public static $rules               = [
        'identity'      => 'required|max:50',
        'name'          => 'required|max:50',
        'plat_identity' => 'max:50',
        'params_key'    => 'max:20',
        'key'           => 'max:32',
        'iframe_url'    => 'max:200',
        'data_url'      => 'max:200',
        'public_key'    => 'max:1024',
        'private_key'   => 'max:1024',
        'status'        => 'integer|in:1,2,3',
    ];

    public static $validStatus = [
        self::STATUS_TESTING => 'status-testing',
        self::STATUS_CLOSED => 'status-closed',
        self::STATUS_AVAILABLE => 'status-available',
    ];

    public static $htmlSelectColumns = [
        'status' => 'aStatus'
    ];

    public static $viewColumnMaps = [
        'status' => 'formatted_status',
    ];

    public static $listColumnMaps = [
        'status' => 'formatted_status',
    ];

    protected function getFormattedStatusAttribute() {
        return __('_thirdplat.' . strtolower(Str::slug(static::$validStatus[$this->attributes['status']])));
    }

    public static function getThirdPlatBySeriesId($iSeriesId) {
        $oSeries = Series::find($iSeriesId);
        return static::find($oSeries->plat_id);
    }

    public static function getThirdPlatByIdentity($sIdentity, $iStatus = self::STATUS_AVAILABLE) {
        return static::where('identity', $sIdentity)->where('status', $iStatus)->first();
    }

    /**
     * 获取可用的频道
     * @return mixed
     */
    public static function getAvailableThirdPlats() {
        if (static::$cacheLevel == self::CACHE_LEVEL_NONE) {
            return static::getAvailableThirdPlatsByDb();
        }
        Cache::setDefaultDriver(static::$cacheDrivers[static::$cacheLevel]);
        $sSuffix = static::setCacheKeySuffix();
        $key = static::createCacheKey($sSuffix);
        if (!$obj = Cache::get($key)) {
            $obj = static::getAvailableThirdPlatsByDb();
            Cache::forever($key, $obj);
        }
        return $obj;
    }

    public static function setCacheKeySuffix() {
        return 'third-plat';
    }

    protected function afterSave($oSavedModel) {
        $sSuffix = static::setCacheKeySuffix();
        $this->deleteCache($sSuffix);
        return parent::afterSave($oSavedModel); // TODO: Change the autogenerated stub
    }

    public static function getAvailableThirdPlatsByDb(){
        $aNeedStatus = [self::STATUS_AVAILABLE];
        !Session::get('is_tester') or $aNeedStatus[] = self::STATUS_TESTING ;
        return static::whereIn('status',$aNeedStatus)->orderBy('id')->get();
    }

    public static function getAvailableThirdPlat($iId) {
        $oThirdPlat = static::find($iId);
        $aNeedStatus = [self::STATUS_AVAILABLE];
        !Session::get('is_tester') or $aNeedStatus[] = self::STATUS_TESTING ;
        if (is_object($oThirdPlat)) {
            return in_array($oThirdPlat->status, $aNeedStatus) ? $oThirdPlat : null;
        } else {
            return $oThirdPlat;
        }
    }

    protected function beforeValidate() {
        $this->status or $this->status = self::STATUS_CLOSED;
        return parent::beforeValidate(); // TODO: Change the autogenerated stub
    }
}
