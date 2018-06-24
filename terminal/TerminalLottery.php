<?php

/**
 * 终端彩票配置
 *
 * @author winter
 * @date 2017-05-03
 */
class TerminalLottery extends BaseModel {

    const STATUS_CLOSED                    = 0;
    const STATUS_TESTING                   = 1;
    const STATUS_AVAILABLE_FOR_NORMAL_USER = 2;
    const STATUS_AVAILABLE                 = 3;

    protected $table                         = 'terminal_lotteries';
    protected static $cacheUseParentClass    = false;
    protected static $cacheLevel             = self::CACHE_LEVEL_FIRST;
    protected static $cacheMinutes           = 0;
    protected $fillable                      = [
        'id',
        'terminal_id',
        'lottery_id',
        'status',
        'created_at',
        'updated_at',
    ];
    public static $validStatuses             = [
        self::STATUS_CLOSED    => 'status-closed',
        self::STATUS_TESTING   => 'status-testing',
        self::STATUS_AVAILABLE => 'status-available'
    ];
    public static $sequencable               = false;
    public static $enabledBatchAction        = false;
    protected $validatorMessages             = [];
    protected $isAdmin                       = true;
    public static $resourceName              = 'TerminalLottery';
    protected $softDelete                    = false;
    protected $defaultColumns                = ['*'];
    protected $hidden                        = [];
    protected $visible                       = [];
    public static $treeable                  = '';
    public static $foreFatherIDColumn        = '';
    public static $foreFatherColumn          = '';
    public static $columnForList             = [
        'terminal_id',
        'lottery_id',
        'status',
        'created_at',
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
    public static $listColumnMaps            = [
        'status' => 'formatted_status'
    ];
    public static $viewColumnMaps            = [
        'status' => 'formatted_status'
    ];
    public static $htmlSelectColumns         = [
        'terminal_id' => 'aTerminals',
        'lottery_id'  => 'aLotteries',
        'status'      => 'aValidStatuses',
    ];
    public static $htmlTextAreaColumns       = [];
    public static $htmlNumberColumns         = [];
    public static $htmlOriginalNumberColumns = [];
    public static $amountAccuracy            = 0;
    public static $originalColumns;
    public $orderColumns                     = [];
    public static $titleColumn               = 'lottery_id';
    public static $mainParamColumn           = '';
    public static $rules                     = [
        'terminal_id' => 'required|min:1',
        'lottery_id'  => 'required|integer|min:1',
        'status'      => 'required|integer|in:0,1,2,3',
    ];

    protected function beforeValidate() {
        return parent::beforeValidate();
    }

    protected function getFormattedStatusAttribute() {
        return __('_terminallottery.' . strtolower(Str::slug(static::$validStatuses[$this->attributes['status']])));
    }

    public static function getValidStatuses() {
        return static::_getArrayAttributes(__FUNCTION__);
    }

    /**
     * 按终端和状态，返回可用的彩种ID列表
     * @param int $iTerminalId
     * @param int $iStatus
     * @return array
     */
    public static function getLotteries($iTerminalId, $iStatus) {
        $aStatus = self::_getStatusArray($iStatus);
        return static::where('terminal_id', '=', $iTerminalId)
            ->whereIn('status', $aStatus)
            ->lists('lottery_id');
    }

    /**
     * 根据给定的状态值返回实际所需要的所有状态值的数组
     * @param int $iNeedStatus
     * @return array
     */
    protected static function _getStatusArray($iNeedStatus) {
        $aStatus = [];
        foreach (static::$validStatuses as $iStatus => $sTmp) {
            if (($iStatus & $iNeedStatus) == $iNeedStatus) {
                $aStatus[] = $iStatus;
            }
        }
        return $aStatus;
    }

}
