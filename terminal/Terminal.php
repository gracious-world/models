<?php

/**
 * 终端模型
 *
 * @author system
 */
class Terminal extends BaseModel {

    protected $table = 'terminals';
    protected static $cacheUseParentClass = false;
    protected static $cacheLevel = self::CACHE_LEVEL_FIRST;
    protected static $cacheMinutes = 0;
    protected $fillable = [
        'id',
        'name',
        'safekey',
        'status',
        'created_at',
        'updated_at',
    ];

    const STATUS_NOT_AVAILABLE = 0;
    const STATUS_FOR_TESTER    = 1;
    const STATUS_FOR_USER      = 2;
    const STATUS_AVAILABLE     = 3;

    public static $validStatuses = [
        self::STATUS_NOT_AVAILABLE => 'Closed',
        self::STATUS_FOR_TESTER    => 'Testing',
//        self::STATUS_FOR_USER => 'User',
        self::STATUS_AVAILABLE     => 'Available',
    ];
    public static $sequencable   = false;
    public static $enabledBatchAction = false;
    protected $validatorMessages = [];
    protected $isAdmin = true;
    public static $resourceName = 'Terminal';
    protected $softDelete = false;
    protected $defaultColumns = [ '*'];
    protected $hidden = [];
    protected $visible = [];
    public static $treeable = '';
    public static $foreFatherIDColumn = '';
    public static $foreFatherColumn = '';
    public static $columnForList = [
        'id',
        'name',
//        'safekey',
        'status',
        'created_at',
        'updated_at',
    ];
    public static $totalColumns = [];
    public static $totalRateColumns = [];
    public static $weightFields = [];
    public static $classGradeFields = [];
    public static $floatDisplayFields = [];
    public static $noOrderByColumns = [];
    public static $ignoreColumnsInView = [
    ];
    public static $ignoreColumnsInEdit = [
        'id',
//        'safeKey',
        'created_at',
        'updated_at',
    ];
    public static $listColumnMaps = [];
    public static $viewColumnMaps = [];
    public static $htmlSelectColumns = [
        'status' => 'aValidStatus'
    ];
    public static $htmlTextAreaColumns = [];
    public static $htmlNumberColumns = [];
    public static $htmlOriginalNumberColumns = [];
    public static $amountAccuracy = 0;
    public static $originalColumns;
    public $orderColumns = [];
    public static $titleColumn = 'name';
    public static $mainParamColumn = 'status';
    public static $rules = [
        'name'    => 'required|max:20',
        'safekey' => 'required|max:32',
        'status'  => 'required|integer|in:0,1,2,3',
    ];

    protected function beforeValidate() {
        if (!$this->safekey) {
            $this->safekey = $this->compileSafeKey();
        }
        return parent::beforeValidate();
    }

    public function compileSafeKey() {
        return md5(md5(uniqid($this->name) . mt_rand(0, 99999999)));
    }

    public static function getValidStatuses() {
        return static::_getArrayAttributes(__FUNCTION__);
    }

}
