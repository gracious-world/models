<?php

/**
 * Class Activitys - 活动表
 *
 */
class Activity extends BaseModel {

    const STATUS_NOT_AVAILABLE = 0;
    const STATUS_SUSPENDED     = 1;
    const STATUS_AVAILABLE     = 2;
    const STATUS_ENDED         = 3;

    protected $bonusSettings;
    protected static $cacheLevel     = self::CACHE_LEVEL_FIRST;
    protected $table                 = 'activities';
    public static $amountAccuracy    = 2;
    public static $htmlNumberColumns = [
        'max_bonus'      => 0,
        'turnover_times' => 0,
    ];
    public static $validStatuses     = [
        self::STATUS_NOT_AVAILABLE => 'status-not-available',
        self::STATUS_SUSPENDED     => 'status-suspended',
        self::STATUS_AVAILABLE     => 'status-available',
        self::STATUS_ENDED         => 'status-ended',
    ];
    protected $softDelete            = false;
    protected $fillable              = [
        'type_id',
        'name',
        'is_deposit',
        'is_turnover',
        'is_first',
        'is_daily',
        'is_reenterable',
        'is_instant',
        'bonus_limit',
        'author_id',
        'author',
        'editor_id',
        'editor',
        'start_time',
        'end_time',
        'need_sign',
        'min_amount',
        'bonus_data',
        'bonus_rate',
        'turnover_times',
        'max_bonus',
        'need_verify',
        'auto_send',
        'note',
        'status',
    ];
    public static $resourceName      = 'Activity';
    public static $htmlSelectColumns = [
        'type_id' => 'aTypes',
        'status'  => 'aValidStatuses',
    ];

    public static $columnForList       = [
        'id',
        'name',
        'type_id',
        'author',
        'start_time',
        'end_time',
        'min_amount',
        'bonus_rate',
        'max_bonus',
        'turnover_times',
        'status',
    ];
    public $orderColumns               = [
        'id' => 'desc'
    ];
    public static $titleColumn         = 'name';
    public static $ignoreColumnsInEdit = [
        'author_id',
        'author',
        'editor_id',
        'editor',
    ];
    public static $ignoreColumnsInView = [
        'author',
        'editor',
    ];
    public static $rules               = [
        'name'           => 'required|between:0,45',
//        'bonus_limit' => 'required|integer',
        'author_id'      => 'required|integer',
        'author'         => 'required|between:1,16',
        'start_time'     => 'required|date',
        'end_time'       => 'required|date',
        'min_amount'     => 'integer',
        'max_bonus'      => 'integer',
        'bonus_data'     => 'required|max:1024',
        'turnover_times' => 'integer',
        'bonus_rate'     => 'numeric',
        'need_verify'    => 'required|in:0,1',
        'auto_send'      => 'required|in:0,1',
        'is_deposit'     => 'required|in:0,1',
        'is_turnover'    => 'required|in:0,1',
        'is_first'       => 'required|in:0,1',
        'is_daily'       => 'required|in:0,1',
        'is_reenterable' => 'required|in:0,1',
        'is_instant'     => 'required|in:0,1',
        'status'         => 'required|integer|in:0,1,2,3',
        'note'           => 'max:65535'
    ];

    protected function beforeValidate() {
        if ($this->id) {
            $this->editor_id = Session::get('admin_user_id');
            $this->editor    = Session::get('admin_username');
        } else {
            $this->author_id = Session::get('admin_user_id');
            $this->author    = Session::get('admin_username');
        }
        if ($this->type_id) {
            $oType             = ActivityType::find($this->type_id);
            $this->is_deposit  = $oType->is_deposit;
            $this->is_turnover = $oType->is_turnover;
            $this->is_first    = $oType->is_first;
//            $this->is_daily    = $oType->is_daily;
        }
        $this->is_reenterable or $this->is_reenterable = 0;
        if ($this->bonus_rate && substr($this->bonus_rate, -1) == '%') {
            $this->bonus_rate = substr($this->bonus_rate, 0, -1) / 100;
        }
        $this->bonus_rate or $this->bonus_rate     = null;
        $this->turnover_times or $this->turnover_times = null;
        $this->max_bonus or $this->max_bonus      = null;
        $this->need_verify or $this->need_verify    = 0;
        !is_null($this->status) or $this->status         = self::STATUS_NOT_AVAILABLE;
//        pr($this->toArray());
//        exit;
        return parent::beforeValidate();
    }

    /**
     * 活动是否有效
     *
     * @return bool
     */
//    public function isValidateActivity($sTime=null) {
    public function isAvailable($sTime = null) {
        if ($this->status != self::STATUS_AVAILABLE) {
            return false;
        }
        $now = empty($sTime) ? date('Y-m-d H:i:s') : $sTime;
        return $this->start_time <= $now && $this->end_time >= $now;
    }

    /**
     * 获得所有有效的活动
     *
     * @return mixed
     */
    public static function findAllValidActivity() {
        $now = date('Y-m-d H:i:s');

        //缓存5分钟
        return static::remember(5)
                        ->where('start_time', '<=', $now)
                        ->where('end_time', '>=', $now)
                        ->get();
    }

    public static function getValidStatuses() {
        return static::_getArrayAttributes(__FUNCTION__);
    }

    /**
     * 根据事件获取相关的任务
     *
     * @param $event
     * @return array
     */
    static public function findAllByEvent($event, $bAvailable = true) {
        $oType = ActivityType::findByEvent($event);
//        pr($oType->toArray());
        return $oType->activities()->get();
//        $oConditionClass = ActivityConditionClass::findAllByEvent($event);
//
//        $tasks = [];
////        foreach ($data as $value) {
//        foreach ($oConditionClass->conditions()->get() as $condition) {
//            if (!isset($tasks[$condition['task_id']])) {
//                $tasks[$condition['task_id']] = $condition->task()->first();
//            }
//        }
////        }
//
//        return $tasks;
    }

    public function compileCompleteCondition($fBonus) {
        $aBonusSettings = json_decode($this->bonus_data, true);
        switch ($aBonusSettings['get_condition']) {
            case 'abs_turnover':
                return ['abs_turnover', $aBonusSettings['get_rules'][$fBonus]];
                break;
            case 'turnover_times':
                return ['turnover_times', $aBonusSettings['times']];
                break;
            default:
                return [];
        }
    }

    /**
     * 根据活动配置数据，和条件金额，计算红包金额
     * @param float $fConditionAmount
     * @return float
     */
    public function compileBonusAmount($fConditionAmount) {
        if ($fConditionAmount <= 0 || $fConditionAmount < $this->min_amount) {
            return 0;
        }
        $aBonusSettings = $this->getBonusSettings();
        switch ($aBonusSettings['method']) {
            case 'custom':
                krsort($aBonusSettings['rules']);
                foreach ($aBonusSettings['rules'] as $iMinDeposit => $fBonus) {
                    if ($fConditionAmount >= $iMinDeposit) {
                        break;
                    }
                }
                $fConditionAmount < $iMinDeposit or $fConditionAmount = 0;
                break;
            case 'rate':
                $fRate = $aBonusSettings['rate'];
                $fBonus           = $fConditionAmount * $fRate;
                break;
            case 'float_rate':
                $fRate = $this->bonus_rate;
                $fBonus           = $fConditionAmount * $this->bonus_rate;
                break;
        }
        if ($this->max_bonus && $fBonus > $this->max_bonus) {
            $fBonus = $this->max_bonus;
        }
//        file_put_contents('/tmp/base_amount', $fBaseAmount);
        return $fBonus;
    }

    public function checkOrCreateBonus($aData) {
        $aAttributes         = [
            'activity_id' => $this->id,
            'user_id'     => $aData['user_id'],
        ];
        !$this->is_daily or $aAttributes['date'] = $aData['date'];
//        $aData['activity_id'] = $this->id;
//        $aData = [
//            'user_id' => $aData['user_id'],
//        ];
//        pr($aAttributes);
        $oActivityUserBonus  = ActivityUserBonus::firstOrNew($aAttributes);
//        pr($oActivityUserBonus->toArray());
//        exit;

        if ($oActivityUserBonus->id) {
            return true;
        }
        $aBonusSettings = $this->getBonusSettings();
//        pr($aBonusSettings);
//        exit;
        $iBonusCount    = isset($aBonusSettings['bonus_count']) ? $aBonusSettings['bonus_count'] : 1;
//        pr($iBonusCount);
//        pr($aData['amount']);
        if ($iBonusCount == 1) {
            file_put_contents('/tmp/ssssssssss', $aData['amount'] . "\n");
            $fBaseAmount = $aData['amount'];
            $fBonus = $this->compileBonusAmount($aData['amount']);
            file_put_contents('/tmp/ssssssssss', $aData['amount'] . "\n", FILE_APPEND);
            if ($fBonus) {
                $aAttributes['amount']       = $fBonus;
                $aAttributes['base_amount']  = $aData['amount'];
                $aAttributes['is_float'] = $aBonusSettings['method'] == 'float_rate';
                $oUserBonus                  = new ActivityUserBonus($aAttributes);
                $oUserBonus->complete_method = $aBonusSettings['get_condition'];
                if (isset($aBonusSettings['get_rules'])){
                    $oUserBonus->target_turnover = $aBonusSettings['get_rules'][$fBonus];
                }
                
                !$this->is_deposit or $oUserBonus->deposit_amount  = $aData['amount'];
//                pr($oUserBonus->toArray());
//                exit;
                if (!$bSucc                       = $oUserBonus->save()) {
                    pr($oUserBonus->getValidationErrorString());
                    exit;
                }
            } else {
                $bSucc = true;
            }
        } else {
//            $bSucc = true;
            $aBonusSets = $aBonusSettings['get_rules'];
            foreach ($aBonusSets as $fBonus => $fTargetTurnover) {
                $aData['amount']             = $fBonus;
                $aData['target_turnover']    = $fTargetTurnover;
                $oUserBonus                  = new ActivityUserBonus($aData);
                $oUserBonus->complete_method = $aBonusSettings['get_condition'];
                if (!$bSucc                  = $oUserBonus->save()) {
                    pr($oUserBonus->getValidationErrorString());
                    break;
                }
            }
        }
//        pr('r:' . $bSucc);
//        exit;
        return $bSucc;
//        pr($aBonusSettings);
//        exit;
    }

    public function getBonusSettings() {
        $this->bonusSettings or $this->bonusSettings = json_decode($this->bonus_data, true);
        return $this->bonusSettings;
    }

    /**
     * 加应用锁
     * @param int $iUserId
     * @param string & $sLockName
     * @return bool
     */
    public function getAppLock($iUserId, & $sLockName) {
        $sLockName = 'luckydraw-event-' . $this->id . '-' . $iUserId;
        return DbTool::getAppLock($sLockName);
    }

    /**
     * 加应用锁
     * @param string $sLockName
     * @return bool
     */
    public function releaseAppLock($sLockName) {
        return DbTool::releaseAppLock($sLockName);
    }

    public static function getEventLists() {
        return static::orderBy('id', 'desc')->lists('name', 'id');
    }

}
