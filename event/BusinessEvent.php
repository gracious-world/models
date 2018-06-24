<?php

/**
 * 招商活动表
 *
 * @author yong02
 */

class BusinessEvent extends BaseModel {

    protected $table = 'business_event';

    protected static $cacheUseParentClass = false;

    protected static $cacheLevel = self::CACHE_LEVEL_NONE;

    protected static $cacheMinutes = 0;

    protected $fillable = [
        'id',
        'email',
        'tel',
        'qq',
        'note',
        'ip',
        'created_at',
        //'updated_at',
    ];

    public static $sequencable = false;

    public static $enabledBatchAction = false;

    protected $validatorMessages = [];

    protected $isAdmin = false;


    public static $resourceName = 'BusinessEvent';

    protected $softDelete = false;

    protected $defaultColumns = ['*'];

    protected $hidden = [];

    protected $visible = [];

    public static $treeable = '';

    public static $foreFatherIDColumn = '';

    public static $foreFatherColumn = '';

    public static $columnForList = [
        'id',
        'email',
        'tel',
        'qq',
        'note',
        'ip',
        'created_at',
        //'updated_at',
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
    ];

    public static $listColumnMaps = [];

    public static $viewColumnMaps = [];

    public static $htmlSelectColumns = [];

    public static $htmlTextAreaColumns = [];

    public static $htmlNumberColumns = [];

    public static $htmlOriginalNumberColumns = [];

    public static $amountAccuracy = 0;

    public static $originalColumns;

    public $orderColumns = [
        'id' => 'desc'
    ];

    public static $titleColumn = 'id';

    public static $mainParamColumn = '';

    public static $rules = [
        'email' => 'email|max:45',
        'tel' => 'regex:/^\d{0,11}$/',
        'qq' => 'regex:/^\d{0,16}$/',
        'note' => 'max:100',
    ];

    protected function beforeValidate() {
        return parent::beforeValidate();
    }

    /**
     * 查询同个ip提交数据的次数
     * @author   yong
     * @date    2017-12-17
     *
     * @param string $iStart
     * @param string $sEnd
     * @param int    $sIp
     *
     */
    public static function getBusinessUserIp($sStart, $sEnd, $sIp) {
        return static::whereBetween('created_at', [$sStart, $sEnd])->where('ip', '=', $sIp)->count('id');
    }

    /**
     * 查询同个business_info  表中有无重复数据
     * @author   yong
     * @date    2017-12-18
     *
     * @param string $sTel
     * @param string $sQq
     * @param string $sEmail
     *
     * @return int
     */
    public static function getBusinessUserInfo($sTel = null, $sQq = null, $sEmail = null) {
        if ($sTel) {
            $iId = static::where('tel', '=', $sTel);
            if ($sQq) {
                $iId->orWhere('qq', '=', $sQq);
            }
            if ($sEmail) {
                $iId->orWhere('email', '=', $sEmail);
            }
        }
        if (!$sTel && $sQq) {
            $iId = static::where('qq', '=', $sQq);
            if ($sEmail) {
                $iId->orWhere('email', '=', $sEmail);
            }
        }
        if (!$sTel && !$sQq) {
            $iId = static::where('email', '=', $sEmail);
        }

        return $iId->count(['id']);
    }
}