<?php

/**
 * Class ActivityUserBonus
 *
 */
class ActivityUserBonus extends BaseModel {

    protected static $cacheLevel = self::CACHE_LEVEL_FIRST;

    const SOURCE_TASK_SYSTEM    = 1;
    const SOURCE_LOTTERY_SYSTEM = 2;
    const SOURCE_COMMAND        = 3;
    const SOURCE_MANUAL         = 4;
    const STATUS_CREATED        = 0;
    const STATUS_FINISHED       = 1;
    const STATUS_APPLYED        = 2;
    const STATUS_VERIRIED       = 3;
    const STATUS_REJECT         = 4;
    const STATUS_RECEIVED       = 5;
    const STATUS_SENT           = 6;

    public static $resourceName        = 'ActivityUserBonus';
    protected $table                   = 'activity_user_bonus';
    public static $amountAccuracy      = 2;
    protected $softDelete              = false;
    protected $fillable                = [
        'activity_id',
        'activity_name',
        'act_start_time',
        'act_end_time',
        'is_daily',
        'is_float',
        'date',
        'user_id',
        'username',
        'is_tester',
        'deposit_amount',
        'base_amount',
        'bonus_rate',
        'amount',
        'complete_method',
        'need_verify',
        'auto_send',
        'target_turnover',
        'turnover_times',
        'count',
        'status',
        'ip',
        'expired_at',
        'received_at',
        'accepter_id',
        'accepter',
        'accepted_at',
        'auditor_id',
        'auditor',
        'audited_at',
        'note',
        'current_turnover',
        'current_times',
        'rate',
        'finished_at',
        'sent_at',
        'created_at',
        'updated_at',
    ];
    public static $columnForList       = [
        'activity_name',
        'date',
        'username',
        'is_tester',
        'deposit_amount',
//        'bonus_rate',
        'amount',
        'target_turnover',
        'current_turnover',
        'rate',
        'ip',
        'status',
        'auditor',
        'audited_at',
        'created_at',
        'updated_at',
    ];
    public static $totalColumns        = [
        'deposit_amount',
        'amount',
        'target_turnover',
        'current_turnover',
    ];
    public static $ignoreColumnsInView = [
        'id',
        'activity_id',
        'user_id',
        'auditor_id',
        'source',
    ];
    public $orderColumns               = [
        'id' => 'desc'
    ];
    public static $validStatuses       = [
        self::STATUS_CREATED  => 'status-created',
        self::STATUS_FINISHED => 'status-finished',
        self::STATUS_APPLYED  => 'status-applyed',
        self::STATUS_VERIRIED => 'status-verified',
        self::STATUS_REJECT   => 'status-rejected',
        self::STATUS_RECEIVED => 'status-received',
        self::STATUS_SENT     => 'status-sent',
    ];
    public static $listColumnMaps      = [
        // 'account_available' => 'account_available_formatted',
        'status'    => 'status_formmatted',
        'is_tester' => 'friendly_is_tester',
        'rate'      => 'rate_formatted',
    ];
    public static $viewColumnMaps      = [
        'status'    => 'status_formmatted',
        'is_tester' => 'friendly_is_tester',
        'rate'      => 'rate_formatted',
    ];
    public static $aSources            = [
        self::SOURCE_TASK_SYSTEM    => 'task-system',
        self::SOURCE_LOTTERY_SYSTEM => 'lottery-system',
        self::SOURCE_COMMAND        => 'command-system',
        self::SOURCE_MANUAL         => 'manual-system',
    ];
    public static $ignoreColumnsInEdit = [
        'activity_name',
//        'activity_id',
        'user_id',
//        'username',
//        'ip',
    ];
    public static $rules               = [
        'user_id'          => 'required|integer',
        'username'         => 'required|between:1,16',
        'activity_id'      => 'required|integer',
        'activity_name'    => 'required|between:1,50',
        'status'           => 'in:0,1,2,3,4,5,6',
        'deposit_amount'   => 'numeric',
        'amount'           => 'required|numeric',
        'turnover_times'   => 'integer',
        'current_turnover' => 'required|numeric',
        'target_turnover'  => 'numeric',
        'ip'               => 'ip',
        'need_verify'      => 'required|in:0,1',
        'auto_send'        => 'required|in:0,1',
    ];
    public static $htmlSelectColumns   = [
        'status' => 'aValidStatuses',
    ];
    protected $User;
    protected $Account;

    /**
     * 活动信息
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function activity() {
        return $this->belongsTo('Activity', 'activity_id', 'id');
    }

    /**
     * 活动信息
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
//    public function actitivy() {
//        return $this->belongsTo('actitivy', 'actitivy_id', 'id');
//    }

    /**
     * 用户信息
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user() {
        return $this->belongsTo('User', 'user_id', 'id');
    }

    /**
     * 验证之前操作
     *
     * @return bool
     */
    protected function beforeValidate() {
        $oAct                   = $this->activity()->first();
        $oUser = User::find($this->user_id);
        $this->activity_id      = $oAct->id;
        $this->activity_name    = $oAct->name;
        $this->act_start_time   = $oAct->start_time;
        $this->act_end_time     = $oAct->end_time;
        $this->is_daily         = $oAct->is_daily;
        if ($this->is_daily && !$this->date){
            $this->date = date('Y-m-d');
        }
        if (!$this->complete_method){
            $aBonusSettings = json_decode($oAct->bonus_data,true);
            $this->complete_method = $aBonusSettings['get_condition'];
        }
        if (!$this->id){
            if ($oAct->max_bonus){
                $fMaxBaseAmount = $oAct->max_bonus / $oAct->bonus_rate;
                $this->base_amount = min($this->deposit_amount,$fMaxBaseAmount);
            }
            else if($this->deposit_amount){
                $this->base_amount = $this->deposit_amount;
            }
        }
        if (!$this->target_turnover && $this->complete_method == 'turnover_times'){
            $this->target_turnover = $this->turnover_times * $this->base_amount;
        }
        if ($this->user_id){
            $oUser              = $this->user()->first();
        }
        else if ($this->username){
            $oUser = User::findUser($this->username);
            $this->user_id = $oUser->id;
        }
        if (empty($oUser)){
//            file_put_contents('/tmp/bonus-save', 'no user');
            return false;
        }
//        pr($oAct->toArray());
//        pr($oUser->toArray());
//        exit;
        $this->username         = $oUser->username;
        $this->is_tester        = $oUser->is_tester;
//        $this->ip or $this->ip  = Tool::getClientIp();
        //如果没有IP,则默认读取用户登陆IP
//        $this->ip != '0.0.0.0' or $this->ip  = $oUser->login_ip;
        $this->ip or $this->ip  = $oUser->login_ip;
        $this->count or $this->count         = 1;
        $this->need_verify      = $oAct->need_verify;
        $this->auto_send        = $oAct->auto_send;
        $this->turnover_times or $this->turnover_times   = $oAct->turnover_times;
        if($this->base_amount){
            $this->bonus_rate       = $this->amount / $this->base_amount;
        }
        $this->current_turnover or $this->current_turnover = 0;
        $this->status or $this->status = self::STATUS_CREATED;
        $this->current_turnover = str_replace(',','',$this->current_turnover);
        if ($this->id && ($this->status == self::STATUS_CREATED) && $this->isComplete()){
            if (!$this->setToFinished()){
//                file_put_contents('/tmp/bonus-save', 'set finished');
                return false;
            }
        }
        // 计算流水倍数基准金额
//        $this->amount         = $this->deposit_amount * $this->bonus_rate;
//        if ($oAct->max_bonus && $this->amount > $oAct->max_bonus) {
//            $this->amount = $oAct->max_bonus;
//        }
//        if (!$this->id) {
//            if ($this->turnover_times) {
//                $this->current_turnover = $this->current_times    = 0;
//            }
//        } else {
////            if ($this->status == self::STATUS_CREATED){
//                if ($this->current_turnover && $this->deposit_amount) {
//                    $this->current_times = $this->current_turnover / ($this->amount * 2);
//                    $this->rate          = $this->current_times / $this->turnover_times;
//                }
////                if ($this->current_times >= $this->turnover_times) {
////                    $this->status = $this->need_verify ? self::STATUS_FINISHED : self::STATUS_VERIRIED;
////                }
////            }
//        }
//        if (is_null($this->status)) {
//            if (!$this->turnover_times) {
//                $this->status = $this->need_verify ? self::STATUS_FINISHED : self::STATUS_VERIRIED;
//            } else {
//                $this->status = self::STATUS_CREATED;
////                $this->status = $this->current_times >= $this->turnover_times ? self::STATUS_FINISHED : self::STATUS_CREATED;
////                if ($this->status == self::STATUS_FINISHED && !$this->need_verify) {
////                    $this->status = self::STATUS_VERIRIED;
////                }
//            }
//        } else {
//            if ($this->status == self::STATUS_CREATED) {
//                $this->status = $this->current_times >= $this->turnover_times ? self::STATUS_FINISHED : self::STATUS_CREATED;
//                if ($this->status == self::STATUS_FINISHED) {
//                    if (!$this->need_verify) {
//                        $this->status = self::STATUS_VERIRIED;
//                    } else {
//                        $this->finished_at = date('Y-m-d H:i:s');
//                    }
//                }
//            }
//        }
//        if (!$this->status) {
//            $this->status = $oAct->need_verify ? ActivityUserBonus::STATUS_NO_SEND : ActivityUserBonus::STATUS_VERIRIED;
//        }
        if (!$this->expire_at) {
            $this->expired_at = Carbon::createFromTimestamp(strtotime($oAct->end_time . ' +7 days'))->toDateTimeString();
        }
//        if ($oPrize->need_get && is_null($this->data)) {
//            $aParam     = json_decode($oPrize->params, true);
//            $this->data = json_encode([$aParam['amount_column'] => $oPrize->value]);
//        }
        return parent::beforeValidate();
    }

    /**
     * 活动是否有效
     *
     * @return bool
     */
    public function isValidateActivity() {
        $now = date('Y-m-d H:i:s');
        if ($this->start_time <= $now && $this->end_time >= $now) {
            return true;
        }
        return false;
    }

    /**
     * 获得商品价值
     *
     * @param $value
     */
    public function getValueAttribute($value) {
        $prize = $this->prize()->remember(5)->first();

        return ($prize) ? $prize->value * $this->count : 0;
    }

    /**
     *  完成
     *
     * @return bool
     */
    public function completed() {
        $this->status = 2;
        return $this->save();
    }

    /**
     * 受理申请成功
     */
    public function setAccected($iAdminUserId) {
        $oAdminUser = AdminUser::find($iAdminUserId);
        $aExtraData = [
            'accepter_id' => $iAdminUserId,
            'accepter'    => $oAdminUser->username,
            'accepted_at' => date('Y-m-d H:i:s'),
            'status'      => self::STATUS_ACCEPTED,
        ];
        return static::where('id', '=', $this->id)->whereIn('status', [self::STATUS_NO_SEND])->update($aExtraData) > 0;
    }

    /**
     * 审核通过
     */
    public function setToVerified($iAdminUserId, $aExtraInfo = []) {
        $oAdminUser = AdminUser::find($iAdminUserId);
        $aExtraData = [
            'auditor_id' => $iAdminUserId,
            'auditor'    => $oAdminUser->username,
            'audited_at' => date('Y-m-d H:i:s'),
//            'status'     => self::STATUS_VERIRIED,
        ];
        $aExtraData = array_merge($aExtraData, $aExtraInfo);
//        $this->flushAvailableHBCount();
//        $this->flushAvailabelTotalHBAmount();
        return $this->setStatus(self::STATUS_VERIRIED, self::STATUS_FINISHED, $aExtraData);

//        return static::where('id', '=', $this->id)->whereIn('status', [self::STATUS_ACCEPTED])->update($aExtraData) > 0;
    }

    public function flushAllCountCache() {
        $this->flushAvailableHBCount();
        $this->flushUnAvailableHBCount();
        $this->flushReceivedHBCount();
        $this->flushAvailabelTotalHBAmount();
    }

    private function flushCountCache($mStatus = null) {
        if (static::$cacheLevel == self::CACHE_LEVEL_NONE) {
            return true;
        }
        !is_array($mStatus) or $mStatus = implode('-', $mStatus);
        $key     = static::createAvailableHBCountCacheKey($this->user_id, $mStatus);
        Cache::setDefaultDriver(static::$cacheDrivers[static::$cacheLevel]);
        !Cache::has($key) or Cache::forget($key);
    }

    public function flushUnAvailableHBCount() {
        return $this->flushCountCache([self::STATUS_CREATED, self::STATUS_FINISHED]);
    }

    public function flushAvailableHBCount() {
        return $this->flushCountCache(self::STATUS_VERIRIED);
    }

    public function flushReceivedHBCount() {
        return $this->flushCountCache([self::STATUS_RECEIVED, self::STATUS_SENT]);
    }

    public function flushAvailabelTotalHBAmount() {
        return $this->flushCountCache();
    }

    /**
     * 审核拒绝
     * @param integer   $iAdminUserId
     * @param array     $aExtraInfo
     * @return boolean
     */
    public function setToReject($iAdminUserId, $aExtraInfo = []) {
        $oAdminUser = AdminUser::find($iAdminUserId);
        $data       = [
            'auditor_id' => $iAdminUserId,
            'auditor'    => $oAdminUser->username,
            'audited_at' => date('Y-m-d H:i:s'),
//            'status'     => self::STATUS_REJECT,
        ];
        $data       = array_merge($data, $aExtraInfo);
        return $this->setStatus(self::STATUS_REJECT, self::STATUS_FINISHED, $data);
//        return static::where('id', '=', $this->id)->whereIn('status', [self::STATUS_ACCEPTED])->update($aExtraData) > 0;
    }

    /**
     *
     * 扩展参数修饰器
     *
     * @param $value
     * @return array
     */
    public function getDatasAttribute($value) {
        return (array) @json_decode($this->attributes['data']);
    }

    /**
     * 扩展参数修改器
     *
     * @param $value
     */
    public function setDatasAttribute($value) {
        $data = $this->datas;
        $data = array_merge($data, $value);

        $this->attributes['data'] = json_encode($data);
    }

    /**
     * 获得第day天的数据
     *
     */
    public function getTurnoverDay($day = 1) {
        $date = date('Y-m-d', strtotime($this->created_at . " +{$day} Day"));

        return UserProfit::getUserTotalTurnover($date, $date, $this->user_id);
    }

    /**
     * 获得返现金额
     *
     * @return float|int
     */
    public function getMoneyback() {
        $total = $this->getTurnoverDay(1) + $this->getTurnoverDay(2);

        $num = 0;
        switch ($this->prize_id) {
            case 10:
                $num = 0.2;
                break;
            case 11:
                $num = 0.1;
                break;
        }


        return $num * $total;
    }

    public static function getUserPrizesByUserIdAndPrizeId($iUserId = null, $iPrizeId) {
        $aConditions = [];
        if ($iUserId != null) {
            $aConditions['user_id'] = ['=', $iUserId];
        }
        if ($iPrizeId != null) {
            $aConditions['prize_id'] = ['=', $iPrizeId];
        }
        $aData = static::doWhere($aConditions)->get();
        return $aData;
    }

    /**
     * 获取用户所有已中奖数据
     * @param User $oUser 用户对象
     * @param Activity $oActivity 活动对象
     * @param int $iSource 指定奖品来源（默认NULL查全部）
     * @return ActivityUserBonus[] 用户活动奖品对象列表
     */
    public static function findUserzPrizes(User $oUser, Activity $oActivity, $iSource = null) {
        if (!$oUser || !$oActivity) {
            return false;
        }
        $oQuery = static::where('user_id', '=', $oUser->id)
                ->where('activity_id', '=', $oActivity->id);
        if (in_array($iSource, [self::SOURCE_TASK_SYSTEM, self::SOURCE_LOTTERY_SYSTEM])) {
            $oQuery->where('source', '=', $iSource);
        }
        return $oQuery->get();
    }

    /**
     * 设置奖品为已领取状态
     * @param array $aExtraData
     * @return bool
     */
    public function setToReceived($aExtraData = []) {
        $aExtraInfo = [
            'received_at' => date('Y-m-d H:i:s'),
        ];
        return $this->setStatus(self::STATUS_RECEIVED, self::STATUS_VERIRIED, $aExtraData);
//        $aConditions = [
//            'id' =>  ['=', $this->id],
//            'status' => ['=', self::STATUS_VERIRIED],
//        ];
//        $data = [
//            'status' => self::STATUS_RECEIVED
//        ];
//        $data = array_merge($data, $aExtraData);
//        return $this->strictUpdate($aConditions, $data) > 0;
    }

    public function addSendBonusTask() {
        $aTaskData = [
            'id' => $this->id,
        ];
        return BaseTask::addTask('SendBonus', $aTaskData, 'activity');
    }

    public function getStatusFormmattedAttribute() {
        return __('_activityuserbonus.' . static::$validStatuses[$this->status]);
    }

    protected function getFriendlyIsTesterAttribute() {
        return yes_no(intval($this->is_tester));
    }

    /**
     * 获取可用红包个数
     * @param int $IUserId 用户id
     */
    public static function getReceivedHBCount($iUserId) {
        return static::getCountByStatus($iUserId, [self::STATUS_RECEIVED, self::STATUS_SENT]);
//        Cache::setDefaultDriver(static::$cacheDrivers[static::$cacheLevel]);
//        $key = static::createAvailableHBCountCacheKey($iUserId, static::$validStatuses[self::STATUS_RECEIVED]);
//        if (static::$cacheLevel == self::CACHE_LEVEL_NONE) {
//            $oQuery = static::getActivityUserBonusQuery($iUserId, [self::STATUS_RECEIVED, self::STATUS_RECEIVED]);
//            return $oQuery->count();
//        }
//        $iUserBankCardCount = 0;
//        if (!($iUserBankCardCount = Cache::get($key))) {
//            $oQuery             = static::getActivityUserBonusQuery($iUserId, [self::STATUS_RECEIVED, self::STATUS_RECEIVED]);
//            $iUserBankCardCount = $oQuery->count();
//            if (static::$cacheMinutes) {
//                Cache::put($key, $data, static::$cacheMinutes);
//            } else {
//                Cache::forever($key, $iUserBankCardCount);
//            }
//        } else {
//            
//        }
//        return $iUserBankCardCount;
    }

    /**
     * 获取过期红包个数
     * @param int $IUserId 用户id
     */
    public static function getExpiredHBCount($iUserId) {
        Cache::setDefaultDriver(static::$cacheDrivers[static::$cacheLevel]);
        $key = static::createAvailableHBCountCacheKey($iUserId, 'expired');
        if (static::$cacheLevel == self::CACHE_LEVEL_NONE) {
            $oQuery = static::getExpiredActivityUserPrizes($iUserId, self::STATUS_VERIRIED);
            return $oQuery->count();
        }
        $iUserBankCardCount = 0;
        if (!($iUserBankCardCount = Cache::get($key))) {
            $oQuery             = static::getExpiredActivityUserPrizes($iUserId, self::STATUS_VERIRIED);
            $iUserBankCardCount = $oQuery->count();
            if (static::$cacheMinutes) {
                Cache::put($key, $data, static::$cacheMinutes);
            } else {
                Cache::forever($key, $iUserBankCardCount);
            }
        }
        return $iUserBankCardCount;
    }

    /**
     * 获取红包个数
     * @param int $IUserId 用户id
     */
    public static function getAvailableHBCount($iUserId) {
        return static::getCountByStatus($iUserId, [self::STATUS_VERIRIED]);
    }

    /**
     * 获取红包个数
     * @param int $IUserId 用户id
     */
    public static function getUnAvailableHBCount($iUserId) {
        return static::getCountByStatus($iUserId, [self::STATUS_CREATED, self::STATUS_FINISHED]);
    }

    public static function getCountByStatus($iUserId, $mStatuses = null) {
        $bReadDb   = true;
        $bPutCache = false;
        if (!is_null($mStatuses)) {
            $sType = is_array($mStatuses) ? implode('-', $mStatuses) : $mStatuses;
        } else {
            $sType = 'all';
        }
        $aStatuses = (array) $mStatuses;
        if (static::$cacheLevel != self::CACHE_LEVEL_NONE) {
            Cache::setDefaultDriver(static::$cacheDrivers[static::$cacheLevel]);
            $key     = static::createAvailableHBCountCacheKey($iUserId, $sType);
            if ($bReadDb = !Cache::has($key)) {
                $bPutCache = true;
            }
        }
        if (!$bReadDb) {
            $iCount = Cache::get($key);
        } else {
            $oQuery = static::getActivityUserBonusQuery($iUserId, $aStatuses);
            $iCount = $oQuery->count();
//            pr($iCount);
        }
        if ($bPutCache) {
            static::$cacheMinutes ? Cache::put($key, $data, static::$cacheMinutes) : Cache::forever($key, $iCount);
        }
        return $iCount;
    }

    /**
     * 获取可领取红包总额
     * @param int $IUserId 用户id
     */
    public static function getAvailableHBTotalAmount($iUserId) {
        return static::getCountByStatus($iUserId, self::STATUS_VERIRIED);
    }

    public static function getHBTotalAmount($iUserId) {
        return static::getCountByStatus($iUserId);
    }

    protected static function getActivityUserBonusQuery($iUserId, $aStatus) {
        $oQuery = static::where('user_id', '=', $iUserId)->whereIn('status', $aStatus);
        return $oQuery;
    }

    protected static function getExpiredActivityUserPrizes($iUserId, $iStatus) {
        $oQuery = static::where('user_id', '=', $iUserId)->where('status', '=', self::STATUS_VERIRIED)->where('expired_at', '<', date('Y-m-d H:i:s'));
        return $oQuery;
    }

    public static function createAvailableHBCountCacheKey($iUserId, $sStatus) {
        $sPrefix = static::getCachePrefix();
        return $sPrefix . $sStatus . '-hb-count' . $iUserId;
    }

    public static function createAvailableTotalHBAmountCacheKey($iUserId, $sStatus) {
        $sPrefix = static::getCachePrefix();
        return $sPrefix . $sStatus . 'total-hb-amount' . $iUserId;
    }

    /**
     * run after save
     * @param $bSucc
     * @param $bNew
     * @return boolean
     */
    protected function afterSave($oSavedModel) {
        parent::afterSave($oSavedModel);
        $this->flushAllCountCache();
//        $oSavedModel->setTurnover(0);
        return true;
    }

    public static function getUserPrizeByDate($sBeginTime, $sEndTime) {
        return static::where('created_at', '>=', $sBeginTime)->where('created_at', '<=', $sEndTime)->get();
    }

    /**
     * 向后台任务队列增加任务
     * @param boolean $bPlus
     */
    public function addPromoBonusTask() {
//        $oPrize         = ActivityPrize::find($this->prize_id);
//        $aPrizeData     = json_decode($oPrize->params, true);
//        $aUserPrizeData = json_decode($this->data, true);
//
//        $fAmount   = array_get($aUserPrizeData, array_get($aPrizeData, 'amount_column'));
//        $fAmount != null or $fAmount   = $oPrize->value;
        $aTaskData = [
            'type'    => 'bonus',
            'user_id' => $this->user_id,
            'amount'  => $this->amount,
            'date'    => date('Y-m-d'),
        ];
        return BaseTask::addTask('StatUpdateProfit', $aTaskData, 'stat');
    }

    public static function getAvailableRecords($iUserId, $dStatTime) {
//        $dNow = Carbon::now()->toDateTimeString();
        return static::where('user_id', '=', $iUserId)
                        ->where('act_end_time', '>=', $dStatTime)
                        ->where('status', '=', self::STATUS_CREATED)->get();
    }

    public function addTurnover($fAmount) {
        $this->current_turnover += $fAmount;
        $this->current_times = $this->current_turnover / $this->base_amount;
        return $this->save();
    }

    protected function getAmountFormattedAttribute() {
        return $this->getFormattedNumberForHtml('amount');
    }

    protected function getTargetTurnoverFormattedAttribute() {
        return $this->getFormattedNumberForHtml('target_turnover');
    }

    protected function getCurrentTurnoverFormattedAttribute() {
        return $this->getFormattedNumberForHtml('current_turnover');
    }

    protected function getRateFormattedAttribute() {
        $fRate = $this->attributes['rate'];
        $fRate <= 1 or $fRate = 1;
        return number_format($fRate * 100, 2) . '%';
    }

    protected function getBonusRateFormattedAttribute() {
//        $fRate = $this->attributes['rate'];
//        $fRate <= 1 or $fRate = 1;
        return $this->attributes['bonus_rate'] * 100 . '%';
    }

    public function sendBonus() {
        $aExtraData['note'] = '活动：' . $this->activity_name;
        $bSucc              = Transaction::addTransaction($this->User, $this->Account, TransactionType::TYPE_PROMOTIANAL_BONUS, $this->amount, $aExtraData, $oTransaction) == Transaction::ERRNO_CREATE_SUCCESSFUL;
        !$bSucc or $bSucc   = $this->setStatus(self::STATUS_SENT, self::STATUS_RECEIVED);
        return $bSucc;
    }

    public function setUser($oUser) {
        $this->User = $oUser;
    }

    public function setAccount($oAccount) {
        $this->Account = $oAccount;
    }

    protected function setToSent($aExtraData) {
        $aExtraInfo = [
            'sent_at' => date('Y-m-d H:i:s'),
        ];
        return $this->setStatus(self::STATUS_SENT, self::STATUS_RECEIVED, $aExtraData);
    }

    protected function setStatus($iToStatus, $iFromStatus, $aExtraData = []) {
        $aConditions = [
            'id'     => ['=', $this->id],
            'status' => ['=', $iFromStatus],
        ];
        $data        = [
            'status' => $iToStatus
        ];
        $data        = array_merge($data, $aExtraData);
//        file_put_contents('/tmp/user_bonus', var_export($aConditions,true) . "\n", FILE_APPEND);
//        file_put_contents('/tmp/user_bonus', var_export($data,true) . "\n", FILE_APPEND);
        return $this->strictUpdate($aConditions, $data) > 0;
    }

    protected function afterUpdate() {
        parent::afterUpdate();
        $this->flushAllCountCache();
    }

    public static function getValidStatuses() {
        return static::_getArrayAttributes(__FUNCTION__);
    }

    public function setBonusAmount($fDepositAmount) {
        $oActivity = Activity::find($this->activity_id);
        if ($fDepositAmount < $oActivity->min_amount) {
            return false;
        }
        $this->deposit_amount = $fDepositAmount;
        if (!$this->amount         = $oActivity->compileBonusAmount($fDepositAmount)) {
            return 0;
        }
        $aCompleteCondtions     = $oActivity->compileCompleteCondition($this->amount);
//        pr($aCompleteCondtions);
        list($sMethod, $sSetting) = $aCompleteCondtions;
        $this->complete_method  = $sMethod;
        $this->current_turnover = 0;
        switch ($sMethod) {
            case 'abs_turnover':
                $this->target_turnover = $sSetting;
                break;
            case 'turnover_times':
                $this->turnover_times  = $sSetting;
                $this->current_times   = 0;
                break;
        }
        $this->status = self::STATUS_CREATED;
//        pr($this->toArray());
        return $this->amount;
    }

    public function isComplete() {
        if ($this->status != self::STATUS_CREATED) {
            return true;
        }
        // 如果活动奖金是根据规则浮动的话
//        if ($this->is_float){
//            $this->resetAmount();
//        }
        switch ($this->complete_method) {
            case 'abs_turnover':
                $this->rate          = $this->current_turnover / $this->target_turnover;
//                $bComplete = $this->current_turnover >= $this->target_turnover;
                break;
            case 'turnover_times':
                $this->current_times = $this->current_turnover / $this->base_amount;
                $this->rate          = $this->current_times / $this->turnover_times;
//                $bComplete = $this->current_times >= $this->turnover_times;
                break;
        }
        return $this->rate >= 1;
    }

    public function setTurnover($fTurnover) {
        if ($this->status != self::STATUS_CREATED) {
            return true;
        }
        $this->current_turnover += $fTurnover;
//        $bComplete = $this->isComplete();
        if (!$this->save()) {
            return false;
        }
//        file_put_contents('/tmp/user_bonus', $bComplete . "\n", FILE_APPEND);
//        if ($bComplete) {
//            return $this->setToFinished();
//        }
        return true;
    }

    public function setToFinished() {
        return $this->setStatus(self::STATUS_FINISHED, self::STATUS_CREATED);
    }

    // 此方法可用
    protected function afterCreate($oSavedModel){
//        pr($oSavedModel->toArray());
        $oSavedModel->setTurnover(0);
    }
    
    public function createBonus($fBaseAmount) {
        if (($fBonus = $oActivityUserBonus->setBonusAmount($data['amount'])) === false) {
            $this->log .= 'The Deposit Amount Less than min amount: ' . $oAct->min_amount . '!';
            continue;
        }
        if (!$fBonus) {
            $this->log .= 'The Bonus is Zero!';
            continue;
        }
//                $oActivityUserBonus->deposit_amount = $data['amount'];
//                $oActivityUserBonus->ip = $data['ip'];
//                pr($oActivityUserBonus->toArray());
//                exit;
        $oActivityUserBonus->date = $data['date'];
    }

    private function checkAndSetFinished(){
        if ($this->id && ($this->status == self::STATUS_CREATED) && $this->isComplete()){
            if (!$this->setToFinished()){
//                file_put_contents('/tmp/bonus-save', 'set finished');
                return false;
            }
        }

    }
    
    public function resetAmount(){
        if ($this->status != self::STATUS_CREATED){
            return true;
        }
        $oAct = $this->activity()->first();
        $aBonusSettings = json_decode($oAct->bonus_data,true);
        if ($aBonusSettings['method'] != 'float_rate'){
            return true;
        }
        $aBonusSets = & $aBonusSettings['bonus_rate'];
//        krsort($aBonusSets);
        $aTimes = array_keys($aBonusSets);
//        if ($this->amount == $oAct->max_bonus){
//            return true;
//        }
        rsort($aTimes);
//        $iTimes = $iMinTimes = min($aTimes);
//        if ($this->current_times >= $iMinTimes){
        $has = false;
//        $fTrueTimes = $this->current_turnover / $this->deposit_amount;
        
        foreach($aTimes as $k => $iTimes){
            if ($this->current_times >= $iTimes){
                $has = true;
                break;
            }
        }
        if (!$has){
            return true;
        }
//        $iTimes = $aTimes[$k + 1];
        
        $this->bonus_rate = $aBonusSets[$iTimes];
        $this->amount = $aBonusSets[$iTimes] * $this->deposit_amount;
//        file_put_contents('/tmp/ccccccc', "$this->current_times  $iTimes  $this->amount");
        if($this->amount > $oAct->max_bonus){
            $this->amount = $oAct->max_bonus;
//            $this->base_amount = $this->amount / $oAct->bonus_rate;
        }
//        else{
//            $this->base_amount = $this->deposit_amount;
//        }
//        $this->bonus_rate       = $this->amount / $this->deposit_amount;
        $this->turnover_times = $iTimes;
//        $this->target_turnover = $this->deposit_amount * $this->turnover_times;
//        $this->current_times = $this->current_turnover / $this->deposit_amount;
        return true;
    }
}
