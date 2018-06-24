<?php

/**
 * 客户端发布管理
 *
 * @author winter
 * @date 2017-08-29
 */
class Release extends BaseModel {

    protected $table                         = 'releases';
    protected static $cacheUseParentClass    = false;
    protected static $cacheLevel             = self::CACHE_LEVEL_FIRST;
    protected static $cacheMinutes           = 0;
    protected $fillable                      = [
        'id',
        'terminal_id',
        'version',
        'filename',
        'description',
        'is_force',
        'start_time',
        'status',
        'created_at',
        'updated_at',
    ];
    public static $sequencable               = false;
    public static $enabledBatchAction        = false;
    protected $validatorMessages             = [];
    protected $isAdmin                       = true;
    public static $resourceName              = 'Release';
    protected $softDelete                    = false;
    protected $defaultColumns                = ['*'];
    protected $hidden                        = [];
    protected $visible                       = [];
    public static $treeable                  = '';
    public static $foreFatherIDColumn        = '';
    public static $foreFatherColumn          = '';
    public static $columnForList             = [
        'terminal_id',
        'version',
        'filename',
        'is_force',
        'start_time',
        'status',
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
        'terminal_id' => 'aTerminals'
    ];
    public static $htmlTextAreaColumns       = [
        'description'
    ];
    public static $htmlNumberColumns         = [];
    public static $htmlOriginalNumberColumns = [];
    public static $amountAccuracy            = 0;
    public static $originalColumns;
    public $orderColumns                     = [];
    public static $titleColumn               = 'terminal_id';
    public static $mainParamColumn           = 'terminal_id';
    public static $rules                     = [
        'terminal_id' => 'required|integer|min:1',
        'version'     => 'required|integer|min:1',
        'filename'    => 'required|max:200',
        'description' => 'max:65535',
        'is_force'    => 'required|integer|in:0,1',
        'start_time'  => 'required',
        'status'      => 'required|integer|in:0,1',
    ];

    protected function beforeValidate() {
        return parent::beforeValidate();
    }

    /**
     * 获取最新版本信息
     * @param int $iTerminalId
     * @return Release
     */
    public static function getLatestRelease($iTerminalId) {
        return static::where('terminal_id', '=', $iTerminalId)
                ->orderBy('version', 'desc')
                ->first();
    }

}
