<?php

use Illuminate\Support\Facades\Redis;

class Project extends BaseModel {

    protected static $cacheLevel     = self::CACHE_LEVEL_FIRST;
    protected static $cacheMinutes   = 1;
    protected $table                 = 'projects';
    public static $resourceName      = 'Project';
    public static $amountAccuracy    = 4;
    public static $htmlNumberColumns = [
        'amount' => 4,
        'prize'  => 6
    ];
    public static $columnForList     = [
        'id',
        'serial_number',
        'trace_id',
        'username',
        'multiple',
        'lottery_id',
        'issue',
        'end_time',
        'title',
        'bet_number',
        'coefficient',
        'amount',
        'prize',
        'bought_at',
        'ip',
        'status',
    ];
    public static $totalColumns      = [
        'amount',
        'prize',
    ];
    public static $listColumnMaps    = [
        'end_time' => 'friendly_end_time'
    ];
    protected $fillable              = [
        'terminal_id',
        'trace_id',
        'user_id',
        'username',
        'is_tester',
        'prize_group',
        'account_id',
        'multiple',
        'serial_number',
        'user_forefather_ids',
        'issue',
        'end_time',
        'title',
        'position',
        'way_total_count',
        'single_count',
        'bet_rate',
        'bet_number',
        'display_bet_number',
        'lottery_id',
        'method_id',
        'way_id',
        'coefficient',
        'single_amount',
        'amount',
        'status',
        'prize_set',
        'ip',
        'proxy_ip',
        'bought_at',
        'bet_commit_time',
        'canceled_at',
        'canceled_by',
        'bought_time',
        'user_parent_id'
    ];
    public static $rules             = [
        'terminal_id'         => 'integer',
        'trace_id'            => 'integer',
        'user_id'             => 'required|integer',
        'account_id'          => 'required|integer',
        'multiple'            => 'required|integer',
        'serial_number'       => 'required|max:32',
        'user_forefather_ids' => 'max:1024',
        'bet_number'          => 'required',
        'note'                => 'max:250',
        'lottery_id'          => 'required|integer',
        'issue'               => 'required|max:13',
        'end_time'            => 'integer',
        'way_id'              => 'required|integer',
        'title'               => 'required|max:100',
        'position'            => 'max:10',
        'coefficient'         => 'required|in:1.00,0.50,0.10,0.01',
        'single_amount'       => 'regex:/^[\d]+(\.[\d]{0,4})?$/',
        'amount'              => 'regex:/^[\d]+(\.[\d]{0,4})?$/',
        'status'              => 'in:0,1,2,3',
        'ip'                  => 'required|ip',
        'proxy_ip'            => 'required|ip',
        'bought_at'           => 'date_format:Y-m-d H:i:s',
        'canceled_at'         => 'date_format:Y-m-d H:i:s',
        'canceled_by'         => 'max:16',
        'way_total_count'     => 'integer',
        'bet_count'           => 'integer',
        'bet_rate'            => 'numeric',
    ];
    public $orderColumns             = [
        'id' => 'desc'
    ];

    const STATUS_NORMAL             = 0;
    const STATUS_DROPED             = 1;    //撤单
    const STATUS_LOST               = 2;
    const STATUS_WON                = 3;

    const STATUS_DROPED_BY_SYSTEM   = 5;   //系统撤单
    const DROP_BY_USER              = 1;
    const DROP_BY_ADMIN             = 2;
    const DROP_BY_SYSTEM            = 3;

    const COMMISSION_STATUS_WAITING = 0;
    const COMMISSION_STATUS_SENDING = 1;
    const COMMISSION_STATUS_PARTIAL = 2;
    const COMMISSION_STATUS_SENT    = 4;

    const PRIZE_STATUS_WAITING      = 0;
    const PRIZE_STATUS_SENDING      = 1;
    const PRIZE_STATUS_PARTIAL      = 2;
    const PRIZE_STATUS_SENT         = 4;

    public static $validStatuses      = [
        self::STATUS_NORMAL           => 'Normal',
        self::STATUS_DROPED           => 'Canceled',
        self::STATUS_LOST             => 'Lost',
        self::STATUS_WON              => 'Counted',
        self::STATUS_DROPED_BY_SYSTEM => 'Canceled By System'
    ];
    public static $commissionStatuses = [
        self::COMMISSION_STATUS_WAITING => 'Waiting',
        self::COMMISSION_STATUS_SENDING => 'Sending',
        self::COMMISSION_STATUS_PARTIAL => 'Partial',
        self::COMMISSION_STATUS_SENT    => 'Done',
    ];
    public static $prizeStatuses      = [
        self::PRIZE_STATUS_WAITING => 'Waiting',
        self::PRIZE_STATUS_SENDING => 'Sending',
        self::PRIZE_STATUS_PARTIAL => 'Partial',
        self::PRIZE_STATUS_SENT    => 'Done',
    ];
    public static $aHiddenColumns     = [];
    public static $aReadonlyInputs    = [];
    public static $mainParamColumn    = 'user_id';
    public static $titleColumn        = 'serial_number';

    /**
     * User
     * @var User|Model
     */
    public $User;

    /**
     * Account
     * @var Account|Model
     */
    public $Account;

    /**
     * Lottery
     * @var Lottery|Model
     */
    public $Lottery;

    /**
     * 下拉列表框字段配置
     * @var array
     */
    public static $htmlSelectColumns = [
        'lottery_id' => 'aLotteries',
        'status'     => 'aValidStatuses',
//        'coefficient' => 'aCoefficients',
    ];

    const ERRNO_BET_SUCCESSFUL                 = -200;
    const ERRNO_PROJECT_MISSING                = -201;
    const ERRNO_BET_SLAVE_DATA_SAVED           = -202;
    const ERRNO_SLAVE_DATA_CANCELED            = -203;
    const ERRNO_COUNT_ERROR                    = -204;
    const ERRNO_PRIZE_OVERFLOW                 = -205;
    const ERRNO_BET_ERROR_SAVE_ERROR           = -210;
    const ERRNO_BET_ERROR_COMMISSIONS          = -211;
    const ERRNO_BET_ERROR_DATA_ERROR           = -213;
    const ERRNO_BET_ERROR_LOW_BALANCE          = -214;
    const ERRNO_DROP_SUCCESS                   = -230;
    const ERRNO_DROP_ERROR_STATUS              = -231;
    const ERRNO_DROP_ERROR_NOT_YOURS           = -232;
    const ERRNO_DROP_ERROR_STATUS_UPDATE_ERROR = -233;
    const ERRNO_DROP_ERROR_PRIZE               = -234;
    const ERRNO_DROP_ERROR_COMMISSIONS         = -235;
    const ERRNO_BET_TURNOVER_UPDATE_FAILED     = -236;
    const ERRNO_BET_ALL_CREATED                = -500;
    const ERRNO_BET_PARTLY_CREATED             = -501;
    const ERRNO_BET_FAILED                     = -502;
    const ERRNO_BET_DATA_ERROR                 = -503;
    const ERRNO_BET_LOW_AMOUNT                 = -504;
    const ERRNO_BET_NO_RIGHT                   = -999;

    /**
     * check project data
     * @return boolean
     */
    public function checkProject() {
        return true;
    }

    /**
     * save prize setting of this project
     * @return boolean
     */
    protected function saveCommissions() {
        if (!$this->id) {
            return false;
//            return self::ERRNO_PROJECT_MISSING;
        }
        $aCommissions = & $this->compileCommissions();
        if ($aCommissions) {
//                $rules = Commission::compileRules();
            foreach ($aCommissions as $data) {
                $oPrjCommission = new Commission($data);
                //            pr($oPrjCommission->getAttributes());

                if (!$bSucc = $oPrjCommission->save()) {
                    pr(get_class($this));
                    pr(__LINE__);
                    pr($oPrjCommission->validationErrors->toArray());
                    return false;
//                    return self::ERRNO_BET_ERROR_COMMISSIONS;
                }
            }
        }
        return true;
//        return self::ERRNO_BET_SLAVE_DATA_SAVED;
    }

    protected function setPrizeAttribute($fAmount) {
        $this->attributes['prize'] = formatNumber($fAmount, static::$amountAccuracy);
    }

    protected function getSerialNumberShortAttribute() {
        return substr($this->attributes['serial_number'], -6);
    }

    /**
     * 撤单
     * @param int $iType self::DROP_BY_USER | self::DROP_BY_ADMIN | self::DROP_BY_SYSTEM
     * @return int errno self::ERRNO_DROP_SUCCESS 成功
     */
    public function drop($iType = self::DROP_BY_USER) {
        if ($this->status != self::STATUS_NORMAL) {
            return self::ERRNO_DROP_ERROR_STATUS;
        }
        if ($iType == self::DROP_BY_USER) {
            if ($this->user_id != Session::get('user_id')) {
                return self::ERRNO_DROP_ERROR_NOT_YOURS;
            }
            $oIssue = Issue::getIssue($this->lottery_id, $this->issue);
            if (empty($oIssue)) {
                return Issue::ERRNO_ISSUE_MISSING;
            }
            if (time() > $oIssue->end_time) {
                return Issue::ERRNO_ISSUE_EXPIRED;
            }
            unset($oIssue);
        }
        return $this->_drop($iType);
    }

    protected function _drop($iType) {
        is_object($this->User) or $this->User               = User::find($this->user_id);
        is_object($this->Account) or $this->Account            = Account::find($this->account_id);
        $aExtraData               = $this->getAttributes();
        $aExtraData['project_id'] = $this->id;
        $aExtraData['project_no'] = $this->serial_number;
//        if ($iType == self::DROP_BY_ADMIN){
//            $aExtraData['admin_user_id'] = Session::get('admin_user_id');
//            $aExtraData['canceled_by'] = Session::get('admin_username');
//        }
        unset($aExtraData['id']);
        $iReturn                  = Transaction::addTransaction($this->User, $this->Account, TransactionType::TYPE_DROP, $this->amount, $aExtraData);
        $iReturn != Transaction::ERRNO_CREATE_SUCCESSFUL or $iReturn                  = $this->setDroped($iType);
//        if ($iReturn == self::ERRNO_DROP_SUCCESS) {
//            UserTurnover::updateTurnoverData($this->lottery_id, $this->issue, $this->user_id, -$this->amount);
//        }
//        $iReturn != self::ERRNO_DROP_SUCCESS or $this->addBackTask(false);      // 修正用户销售额
        return $iReturn;
    }

    /**
     * 更新状态为撤单
     * @return bool
     */
    public function setDroped($iType = self::DROP_BY_USER) {
        if (($iReturn = $this->cancelCommissons()) != self::ERRNO_SLAVE_DATA_CANCELED) {
            return $iReturn;
        }

        $aData['canceled_at']     = date('Y-m-d H:i:s');
        $aData['user_commission'] = 0; //将会员自身的返点 归零
        if ($this->user_forefather_ids) {
            $aData['parent_commission'] = 0; //将上级的返点 归零
        }

        $iType != self::DROP_BY_ADMIN or $aData['canceled_by'] = Session::get('admin_username');
        $iStatus              = $iType == self::DROP_BY_SYSTEM ? self::STATUS_DROPED_BY_SYSTEM : self::STATUS_DROPED;
        if (!$this->setStatus($iStatus, self::STATUS_NORMAL, $aData)) {
            return self::ERRNO_DROP_ERROR_STATUS_UPDATE_ERROR;
        }

        $this->canceled_at = $aData['canceled_at'];
        $this->status      = $iStatus;
        $iType != self::DROP_BY_ADMIN or $this->canceled_by = $aData['canceled_by'];
//        if ($iReturn == self::ERRNO_DROP_SUCCESS) {
        UserTurnover::updateTurnoverData($this->lottery_id, $this->issue, $this->user_id, -$this->amount);
//        }
        return self::ERRNO_DROP_SUCCESS;
    }

    /**
     * 更新状态
     *
     * @param int $iToStatus
     * @param int $iFromStatus
     * @param $aExtraData
     * @return int  0: success; -1: prize set cancel fail; -2: commissions cancel fail
     */
    protected function setStatus($iToStatus, $iFromStatus, $aExtraData = []) {
        $aExtraData['status'] = $iToStatus;
        $aConditions          = [
            'id'     => ['=', $this->id],
            'status' => ['=', $iFromStatus],
            'status' => ['<>', $iToStatus],
        ];
        if ($bSucc                = $this->strictUpdate($aConditions, $aExtraData)) {
            $this->status = $iToStatus;
        }
//        if ($bSucc = Project::where('id', '=', $this->id)->where('status', '=', $iFromStatus)->where('status', '<>', $iToStatus)->update($aExtraData)) {
//            $this->deleteCache();
//        }
        return $bSucc;
    }

    /**
     * 撤销佣金记录
     * @return int  self::ERRNO_SLAVE_DATA_CANCELED or self::ERRNO_DROP_ERROR_COMMISSIONS
     */
    protected function cancelCommissons() {
        if (!Commission::setDroped($this->id)) {
            return self::ERRNO_DROP_ERROR_COMMISSIONS;
        }
        return self::ERRNO_SLAVE_DATA_CANCELED;
    }

    /**
     * set Account Model
     * @param Account $oAccount
     */
    public function setAccount($oAccount) {
        if (!empty($this->account_id) && $this->account_id == $oAccount->id) {
            $this->Account = $oAccount;
        }
    }

    /**
     * set User Model
     * @param User $oUser
     */
    public function setUser($oUser) {
        if (!empty($this->user_id) && $this->user_id == $oUser->id) {
            $this->User = $oUser;
        }
    }

    /**
     * set Lottery Model
     * @param Lottery $oLottery
     */
    public function setLottery($oLottery) {
        $this->Lottery = $oLottery;
    }

    public static function getUnCalcutatedCount($iLotteryId, $sIssue, $mSeriesWayId = null, $bTask = null) {
        $aCondtions           = [
            'lottery_id' => $iLotteryId,
            'issue'      => $sIssue,
            'status'     => self::STATUS_NORMAL
        ];
        is_null($mSeriesWayId) or $aCondtions['way_id'] = $mSeriesWayId;
        if (!is_null($bTask)) {
            $sOperator              = $bTask ? '<>' : '=';
            $aCondtions["trace_id"] = [$sOperator, null];
        }
        return static::getCount($aCondtions);
    }

    public static function getCount($aParams) {
        $aCondtions = [];
        foreach ($aParams as $sColumn => $mValue) {
            $a = explode(' ', $sColumn);
            if (count($a) == 1) {
                $sOperator = is_array($mValue) ? 'in' : '=';
            } else {
                $sColumn   = $a[0];
                $sOperator = $a[1];
            }
            $aCondtions[$sColumn] = [$sOperator, $mValue];
        }
//        exit;
        return static::doWhere($aCondtions)->count();
    }

    protected function getPrizeSetFormattedAttribute() {
        $aPrizeSets = json_decode($this->attributes['prize_set'], true);
        $aDisplay   = [];
//        pr($aPrizeSets);
//        exit;
        foreach ($aPrizeSets as $iBasicMethodId => $aPrizes) {
            $oBasicMethod = BasicMethod::find($iBasicMethodId);
            $oBasicMethod->name;
            $a            = [];
            foreach ($aPrizes as $iLevel => $fPrize) {
                $a[] = ChnNumber::getLevel($iLevel) . ': ' . $fPrize * $this->coefficient . '元';
            }
            $aDisplay[$oBasicMethod->name] = $oBasicMethod->name . ' : ' . implode(' ; ', $a);
        }
        return implode('<br />', $aDisplay);
    }

    protected function getFormattedStatusAttribute() {
        return __('_project.' . strtolower(Str::slug(static::$validStatuses[$this->attributes['status']])));
    }

    protected function getPrizeFormattedAttribute() {
        return $this->attributes['prize'] ? $this->getFormattedNumberForHtml('prize') : null;
    }

    protected function getAmountFormattedAttribute() {
        return $this->attributes['amount'] ? $this->getFormattedNumberForHtml('amount') : null;
    }

    protected function getUpdatedAtTimeAttribute() {
        return substr($this->updated_at, 5, -3);
    }

    protected function getEndTimeFormattedAttribute() {
        return date('Y-m-d H:i:s', $this->attributes['end_time']);
    }

    /**
     * 向后台任务队列增加任务
     * @param boolean $bPlus
     */
    public function addTurnoverStatTask($bPlus = true) {
        $sField    = $bPlus ? 'bought_at' : 'canceled_at';
        $aTaskData = [
            'type'        => 'turnover',
            'user_id'     => $this->user_id,
            'amount'      => $bPlus ? $this->amount : -$this->amount,
            'date'        => substr($this->$sField, 0, 10),
            'lottery_id'  => $this->lottery_id,
            'issue'       => $this->issue,
            'way_id'      => $this->way_id,
            'terminal_id' => $this->terminal_id,
        ];
        return BaseTask::addTask('StatUpdateProfit', $aTaskData, 'stat');
    }

    public function addBetNumberCollectTask($bPlus = true) {
        $oLottery            = Lottery::find($this->lottery_id);
        $sConfigFile         = 'risk_' . $oLottery->series_id;
        $sConfigItemOfWay    = $sConfigFile . '.ways';
        $sConfigItemOfAmount = $sConfigFile . '.min_amount';

//        pr($sConfigItem);
        $fMinAmount = Config::get($sConfigItemOfAmount);
//        exit;
        if ($this->amount < $fMinAmount || !in_array($this->lottery_id, Config::get('risks.lotteries')) || !in_array($this->way_id, Config::get($sConfigItemOfWay))) {
            return true;
        }
        $aTaskData          = [
            'id'   => $this->id,
            'plus' => $bPlus
        ];
        $sConfigItemOfQueue = $sConfigFile . '.queue';
        return BaseTask::addTask('doCollectBets', $aTaskData, Config::get($sConfigItemOfQueue));
    }

    /**
     * 获取用户当前时间的有效投注金额
     * @param int $iUserId     用户id
     * @param string $currentDateTime     当前时间
     */
    public static function getCurrentDayTurnover($iUserId, $currentDateTime, $endDateTime = null) {
        $oQuery         = static::where('user_id', $iUserId)->where('bought_at', '>=', $currentDateTime);
        !$endDateTime or $oQuery->where('bought_at', '<=', $endDateTime);
        $aTurnover      = $oQuery->whereIn('status', [Project::STATUS_LOST, Project::STATUS_WON])->get(['amount']);
        $aTotalTurnover = [];
        foreach ($aTurnover as $data) {
            $aTotalTurnover[] = $data['amount'];
        }
        $fTotalTurnover = array_sum($aTotalTurnover);
        return $fTotalTurnover;
    }
    /**
     * check用户在有效时间的有效投注
     * @param int $iUserId     用户id
     * @param string $currentDateTime     当前时间
     * @return boolean
     */
    public static function checkCurrentDayTurnover($iUserId, $currentDateTime, $endDateTime = null) {
        $oQuery         = static::where('user_id', $iUserId)->where('bought_at', '>=', $currentDateTime);
        !$endDateTime or $oQuery->where('bought_at', '<=', $endDateTime);
        $aTurnover      = $oQuery->whereIn('status', [Project::STATUS_LOST, Project::STATUS_WON])->get()->toArray();
        $bTotalTurnover = true;
        foreach ($aTurnover as $data) {
            if($data['single_count']/$data['way_total_count']>0.7){
                $bTotalTurnover = false;
                return $bTotalTurnover;
            }
        }
        //$fTotalTurnover = array_sum($aTotalTurnover);
        return $bTotalTurnover;
    }
    protected static function & compileRules($oProject) {
        $rules                = static::$rules;
        $rules['coefficient'] = 'required|in:' . implode(',', Coefficient::getValidCoefficientValues());
        if (!$oProject->trace_id) {
            $rules['bought_time'] = 'required|integer|max:' . $oProject->end_time;
        }
        return $rules;
    }

    protected function getUsernameHiddenAttribute() {
        return substr($this->attributes['username'], 0, 2) . '***' . substr($this->attributes['username'], -2);
    }

    protected static function compileListCacheKeyPrefix() {
        return static::getCachePrefix(true) . 'for-user-';
    }

    protected static function compileListCacheKey($iUserId = null, $iPage = 1, $iLotteryId = '') {
        $sKey = static::compileUserDataListCachePrefix($iUserId, $iLotteryId);
        empty($iPage) or $sKey .= '-' . $iPage;
        return $sKey;
    }

    protected static function compileUserDataListCachePrefix($iUserId, $iLotteryId = '') {
        return static::compileListCacheKeyPrefix() . $iUserId . '-' . $iLotteryId;
    }

    public function setCommited() {
        $this->updateBuyCommitTime();
//        $this->addBetNumberCollectTask(true);
        $this->addTurnoverStatTask(true);
        $this->deleteUserDataListCache($this->user_id);
//        $this->updateUserBetList();
    }

    public function updateBuyCommitTime() {
        $data  = ['bet_commit_time' => time()];
        if ($bSucc = $this->update($data)) {
            $this->bet_commit_time = $data['bet_commit_time'];
        }
        return $bSucc;
    }

    public function updateUserBetList() {
        $redis = Redis::connection();
        $sKey  = static::compileListCacheKey($this->user_id, 1);
        $redis->llen($sKey) < $this->maxBetListLength or $redis->ltrim($sKey, 0, $this->maxBetListLength - 1);
        $redis->lpush($sKey, json_encode($this->toArray()));
    }

    public static function deleteUserDataListCache($iUserId) {
        $sKeyPrifix = static::compileUserDataListCachePrefix($iUserId);
        $redis      = Redis::connection();
        if ($aKeys      = $redis->keys($sKeyPrifix . '*')) {
            foreach ($aKeys as $sKey) {
                $redis->del($sKey);
            }
        }
    }

    /**
     * 统计每个彩种每天测试和非测试用户投注数
     *
     * @param $sDate
     *
     * @return mixed
     */
    public static function getPrjCountByLottery($sDate) {
        $sEndDate = date('Y-m-d H:i:s', strtotime("$sDate +1 day -1 second"));

        return Project::whereNotIn('status', [Project::STATUS_DROPED, Project::STATUS_DROPED_BY_SYSTEM ])
            ->whereBetween('bought_at', [$sDate, $sEndDate])
            ->select(DB::raw('lottery_id,is_tester,count(*) prj_count '))
            ->groupBy('lottery_id')
            ->groupBy('is_tester')
            ->orderBy('lottery_id')
            ->orderBy('is_tester')
            ->get();
    }

    /**
     * 取得多笔已成功发放的注单列表
     *
     * @author Rex
     * @date   2017-03-09
     * @param  int                            $iUserId
     * @param  array                        $aCondition
     * @return  Project collection
     */
    public static function getSentPrjByUserId($iUserId, $dStartDate, $dEndDate) {
        $aCondition['user_id'] = ['=', $iUserId];
        $aCondition['status_commission'] = ['=', Project::COMMISSION_STATUS_SENT];
        $aCondition['created_at'] = ['>', $dStartDate];
        $aCondition['created_at'] = ['<=', $dEndDate];

        return Project::doWhere($aCondition)->get();
    }

    protected function getFormattedCoefficientAttribute() {
        return !is_null($this->coefficient) ? Coefficient::getCoefficientText($this->coefficient) : '';
    }

    protected function getDisplayBetNumberForListAttribute() {
        $iMaxLen = 100;
        if (strlen($this->attributes['display_bet_number']) > $iMaxLen) {
            return substr($this->attributes['display_bet_number'], 0, $iMaxLen) . '...';
        } else {
            return $this->attributes['display_bet_number'];
        }
    }

    protected function getValidStatuses(){
        return parent::_getArrayAttributes(__FUNCTION__);
    }

    /**
     * 获取最高中奖金额
     * @author sara
     * @created_at 2016-11-21
     * @param null $iUserId
     * @return array
     *
     */
    static function getPrizeMaxByUserId($iUserId = null) {
        return static::doWhere(['user_id' => ['=', $iUserId]])
                ->select('user_id', DB::raw('max(prize) as prize_max'))
                ->first();
    }

    /**
     * 获取用户最近玩的游戏
     * @author lucky
     * @created_at 2016-10-10
     * @param null $iUserId
     * @return mixed
     *
     */

    static function getUserLatestProject($iUserId=null){
        return  static::Where("user_id", '=', $iUserId)
                ->where("created_at", ">", date("Y-m-d H:i:s", time() - 24 * 3600 * 7))
                ->orderBy("id", "desc")
                ->first();
    }

}
