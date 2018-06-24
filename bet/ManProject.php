<?php

use Illuminate\Support\Facades\Redis;

class ManProject extends Project {

    protected static $cacheUseParentClass = true;
    protected $fillable = [
        'winning_number',
        'prize',
        'single_won_count',
        'won_count',
        'won_data',
        'status',
        'counted_at',
        'status_prize',
        'status_commission',
        'locked_prize',
        'locked_commission',
        'prize_sent_at',
        'commission_sent_at',
        'counted_time',
        'prize_sent_time',
        'commission_sent_time',
        'prize_sale_rate',
    ];
    public static $htmlSelectColumns = [
        'lottery_id' => 'aLotteries',
        'status' => 'aStatusDesc',
        'terminal_id' => 'aTerminals',
//        'coefficient' => 'aCoefficients',
    ];
    public static $htmlOriginalNumberColumns = [
        'won_count',
        'single_won_count'
    ];

    const ERRNO_LOCK_FAILED = -980;
    const ERRNO_PRIZE_SENDING = -981;
    const ERRNO_COMMISSION_SENDING = -982;

    public static $columnForList = [
        'id',
        'terminal_id',
        'serial_number',
        'trace_id',
        'username',
        'is_tester',
        'lottery_id',
        'issue',
        'prize_group',
        'title',
        'multiple',
//        'display_bet_number',
        'winning_number',
        'coefficient',
        'amount',
        'prize',
        'end_time',
        'bought_at',
        'ip',
        'status',
        'status_prize',
        'status_commission',
    ];
    public static $ignoreColumnsInView = [
        'account_id',
        'user_forefather_ids',
        'way_id',
        'won_issue',
        'user_id',
        'bet_number',
        'locked_prize',
        'locked_commission',
//        'prize_set',
        'bought_time',
        'counted_time',
        'prize_sent_time',
        'commission_sent_time',
    ];
    public static $floatDisplayFields = [
        'display_bet_number',
        'bought_at',
    ];

    public static $weightFields = [
        'display_bet_number',
        'amount',
        'winning_number',
        'prize',
    ];

    /**
     * 视图显示时使用，用于某些列有特定格式，且定义了虚拟列的情况
     * @var array
     */
    public static $listColumnMaps = [
        'serial_number' => 'serial_number_short',
        'status' => 'formatted_status',
        'amount' => 'amount_formatted',
        'prize' => 'prize_formatted',
        'display_bet_number' => 'display_bet_number_short',
        'status_prize' => 'status_prize_formatted',
        'status_commission' => 'status_commission_formatted',
        'is_tester' => 'formatted_is_tester',
        'end_time' => 'friendly_end_time',
        'bought_at' => 'friendly_bought_at',
        'coefficient' => 'formatted_coefficient',
    ];

    /**
     * 视图显示时使用，用于某些列有特定格式，且定义了虚拟列的情况
     * @var array
     */
    public static $viewColumnMaps = [
        'status' => 'formatted_status',
        'prize' => 'prize_formatted',
        'prize_set' => 'prize_set_formatted',
        'status_prize' => 'status_prize_formatted',
        'status_commission' => 'status_commission_formatted',
        'display_bet_number' => 'display_bet_number_for_view',
        'is_tester' => 'formatted_is_tester',
        'bet_rate' => 'bet_rate_formatted',
        'bet_commit_time' => 'bet_commit_time_formatted',
        'coefficient' => 'formatted_coefficient',
        'end_time' => 'end_time_formatted',
    ];
    public static $ignoreColumnsInEdit = [];
    public static $rules = [
//        'trace_id'            => 'integer',
//        'user_id'             => 'required|integer',
//        'account_id'          => 'required|integer',
//        'multiple'            => 'required|integer',
//        'serial_number'       => 'required|max:32',
//        'user_forefather_ids' => 'max:1024',
//        'issue'               => 'required|max:12',
//        'title'               => 'required|max:100',
//        'bet_number'          => 'required|max:10240',
//        'note'                => 'max:250',
//        'lottery_id'          => 'required|numeric',
//        'way_id'              => 'required|numeric',
//        'coefficient'         => 'in:1,0.1',
//        'single_amount'       => 'regex:/^[\d]+(\.[\d]{0,4})?$/',
//        'amount'              => 'regex:/^[\d]+(\.[\d]{0,4})?$/',
//        'status'              => 'in:0,1,2,3',
//        'ip'                  => 'required|ip',
//        'proxy_ip'            => 'required|ip',
        'bought_at' => 'date_format:Y-m-d H:i:s',
        'counted_at' => 'date_format:Y-m-d H:i:s',
        'prize_sent_at' => 'date_format:Y-m-d H:i:s',
        'commission_sent_at' => 'date_format:Y-m-d H:i:s',
        'counted_time' => 'integer',
        'prize_sent_time' => 'integer',
        'commission_sent_time' => 'integer',
        'won_count' => 'integer',
    ];

    protected function afterSave($oSavedModel) {
        parent::afterSave($oSavedModel);
        $oSavedModel->deleteUserDataListCache($oSavedModel->user_id);
    }

    /**
     * 撤单
     * @param int $iType self::DROP_BY_USER | self::DROP_BY_ADMIN | self::DROP_BY_SYSTEM
     * @return int errno self::ERRNO_DROP_SUCCESS 成功
     */
    public function drop($iType = self::DROP_BY_USER) {
        if ($iType == self::DROP_BY_USER) {
            return false;
        }
        return $this->_drop($iType);
    }

    protected function _drop($iType){
        is_object($this->User) or $this->User = User::find($this->user_id);
        is_object($this->Account) or $this->Account = Account::find($this->account_id);
        $aExtraData = $this->getAttributes();
        $aExtraData['project_id'] = $this->id;
        $aExtraData['project_no'] = $this->serial_number;
//        if ($iType == self::DROP_BY_ADMIN){
//            $aExtraData['admin_user_id'] = Session::get('admin_user_id');
//            $aExtraData['canceled_by'] = Session::get('admin_username');
//        }
        unset($aExtraData['id']);
        $iReturn = Transaction::addTransaction($this->User, $this->Account, TransactionType::TYPE_DROP, $this->amount, $aExtraData);
        $iReturn != Transaction::ERRNO_CREATE_SUCCESSFUL or $iReturn = $this->setDroped($iType);
//        $iReturn != self::ERRNO_DROP_SUCCESS or $this->addBackTask(false);      // 修正用户销售额
        return $iReturn;
    }

    public static function getValidProjects($iLotteryId, $sIssue, $iWayId = null) {
        $aConditions = [
            'lottery_id' => ['=', $iLotteryId],
            'issue' => ['=', $sIssue],
            'status' => ['<>', self::STATUS_DROPED]
        ];
        is_null($iWayId) or $aConditions['way_id'] = [ '=', $iWayId];
//        pr($aConditions);
//        exit;
        return static::doWhere($aConditions)->orderBy('id', 'asc')->get(['id', 'trace_id', 'user_id', 'is_tester', 'multiple', 'way_id', 'coefficient', 'bet_number', 'prize_set']);
    }

    /**
     * 返回指定玩法的注单ID数组
     * @param int $iLotteryId
     * @param string $sIssue
     * @param int $iWayId
     * @return array
     */
    public static function & getValidProjectIds($iLotteryId, $sIssue, $iWayId = null) {
        $aConditions = [
            'lottery_id' => ['=', $iLotteryId],
            'issue' => ['=', $sIssue],
            'status' => ['<>', self::STATUS_DROPED]
        ];
        is_null($iWayId) or $aConditions['way_id'] = [ '=', $iWayId];
        $aIds = static::doWhere($aConditions)->orderBy('id', 'asc')->lists('id');
        return $aIds;
//        $oProjects = static::doWhere($aConditions)->orderBy('id', 'asc')->get(['id']);
//        $aIds = [];
//        foreach ($oProjects as $oProject) {
//            $aIds[] = $oProject->id;
//        }
//        return $aIds;
    }

    /**
     * 返回指定玩法的注单所关联的TRACE ID数组
     * @param int $iLotteryId
     * @param string $sIssue
     * @param int $iWayId
     * @return array
     */
    public static function & getLostTraceIds($iLotteryId, $sIssue, $iWayId = null) {
        $aConditions = [
            'lottery_id' => ['=', $iLotteryId],
            'issue' => ['=', $sIssue],
            'status' => ['=', self::STATUS_LOST]
        ];
        is_null($iWayId) or $aConditions['way_id'] = [ '=', $iWayId];
        $aIds = static::doWhere($aConditions)->orderBy('id', 'asc')->lists('trace_id');
        return $aIds;
    }

    /**
     * 返回未派奖的注单
     * @param array $aIds
     * @param int $iLimit default 100
     * @return Containor|Project
     */
    public static function getUnSentPrizesProjects($aIds, $iLimit = 100) {
        $aConditions = [
            'id' => ['in', $aIds],
            'status' => ['=', self::STATUS_WON],
            'status_prize' => ['in', [self::PRIZE_STATUS_WAITING, self::PRIZE_STATUS_PARTIAL]],
        ];
        return static::doWhere($aConditions)->orderBy('id', 'asc')->get();
    }

    /**
     * 返回未返点的注单
     * @param array $aIds
     * @param int $iLimit default 100
     * @return Containor|Project
     */
    public static function getUnSentCommissionsProjects($aIds = null, $iPs =  100, $iPage = 1) {
        $aConditions = [
            'status' => ['in', [self::STATUS_WON, self::STATUS_LOST]],
            'status_commission' => ['in', [self::COMMISSION_STATUS_WAITING, self::COMMISSION_STATUS_PARTIAL]],
        ];
        !is_array($aIds) or $aConditions['id'] = ['in', $aIds];
//        file_put_contents('/tmp/uncom',var_export($aConditions, 1));

        return static::doWhere($aConditions)->orderBy('id', 'asc')->get();
    }

    public static function getUnSentCommissionsProjectIds() {
        $aConditions = [
            'status' => ['in', [self::STATUS_WON, self::STATUS_LOST]],
            'status_commission' => ['in', [self::COMMISSION_STATUS_WAITING, self::COMMISSION_STATUS_PARTIAL]],
        ];
//        is_array($aIds) or $aConditions['id'] => ['in', $aIds];

        return static::doWhere($aConditions)->orderBy('id', 'asc')->lists('id');
    }

    /**
     * 返回未计奖的注单
     * @param int $iLotteryId
     * @param string $sIssue
     * @param int $iWayId
     * @param bool $bTask
     * @return Containor|Project
     */
    public static function getUnCalculatedProjects($iLotteryId, $sIssue, $iWayId = null, $sBeginTime = null, $iOffset = null, $iLimit = null) {
        $aConditions = [
            'lottery_id' => ['=', $iLotteryId],
            'issue' => ['=', $sIssue],
            'status' => ['=', self::STATUS_NORMAL],
        ];
        empty($iWayId) or $aConditions['way_id'] = [ '=', $iWayId];
        empty($sBeginTime) or $aConditions['bought_at'] = ['>=', $sBeginTime];
//        if (!is_null($bTask)){
//            $sOperator                = $bTask ? '<>' : '=';
//            $aConditions[ "trace_id" ] = [$sOperator,null];
//        }
        $oQuery = static::doWhere($aConditions)->orderBy('id', 'asc');
        empty($iOffset) or $oQuery = $oQuery->offset($iOffset);
        empty($iLimit) or $oQuery = $oQuery->limit($iLimit);
//        pr($aConditions);
//        exit;
        return $oQuery->get();
    }

    /**
     * 将指定方式的所有正常注单全部设置为未中奖
     * 注意：bTask参数的应用
     *
     * @param int $iLotteryId
     * @param string $sIssue
     * @param int $iWayId
     * @param bool $bTask
     * @return bool
     */
    public static function setLostOfWay($sWnNumber, $iLotteryId, $sIssue, $iWayId, $bTask = null) {
        $aConditions = [
            'lottery_id' => ['=', $iLotteryId],
            'issue' => ['=', $sIssue],
            'way_id' => ['=', $iWayId],
            'status' => ['=', self::STATUS_NORMAL],
        ];
        if (!is_null($bTask)) {
            $sOperator = $bTask ? '<>' : '=';
            $aConditions["trace_id $sOperator"] = null;
        }
        $oCarbon = Carbon::now();
        $data = [
//            'prize'          => 0,
            'winning_number' => $sWnNumber,
            'status' => self::STATUS_LOST,
            'counted_at' => $oCarbon->toDateTimeString(),
            'counted_time' => $oCarbon->timestamp,
        ];
        // todo: 未中奖的注单缓存更新问题
        if ($bSucc = static::doWhere($aConditions)->update($data) > 0) {
            $data = [
                'lottery_id' => $iLotteryId,
                'issue' => $sIssue,
                'way_id' => $iWayId,
            ];
//            file_put_contents('/tmp/aaaaaaa', var_export($data, true));
            BaseTask::addTask('ClearProjectCache', $data, 'main');
        }
//        file_put_contents('/tmp/aaaaaaa', var_export($bSucc, true), FILE_APPEND);
        return $bSucc;
//        return $this->strictUpdate($aConditions, $data);
    }

    /**
     * 将指定ID的所有正常注单全部设置为未中奖
     *
     * @param array $aUnPrizedProjects
     * @return bool
     */
    public static function setLostOfIds($sWnNumber, $aUnPrizedProjects) {
        $aConditions = [
            'id' => ['in', $aUnPrizedProjects],
            'status' => ['=', self::STATUS_NORMAL],
        ];
        $oCarbon = Carbon::now();
        $data = [
//            'prize'          => 0,
            'winning_number' => $sWnNumber,
            'status' => self::STATUS_LOST,
            'counted_at' => $oCarbon->toDateTimeString(),
            'counted_time' => $oCarbon->timestamp,
        ];
        if ($bSucc = static::doWhere($aConditions)->update($data)) {
            foreach ($aUnPrizedProjects as $id) {
                static::deleteCache($id);
//                static::deleteUserDataListCache($id)
            }
            $aUserIds = & static::getBetUserIdsOfIds($aUnPrizedProjects);
            foreach ($aUserIds as $iUserId) {
                static::deleteUserDataListCache($iUserId);
            }
        }
        return $bSucc;
    }

    public static function & getBetUserIdsOfIds($aIds) {
        $aUserIds = static::whereIn('id', $aIds)->distinct()->lists('user_id');
        return $aUserIds;
//        $oProjects = static::whereIn('id', $aIds)->get(['user_id']);
//        $aUserIds = [];
//        foreach ($oProjects as $oProject) {
//            $aUserIds[] = $oProject->user_id;
//        }
//        $aUserIds = array_unique($aUserIds);
//        return $aUserIds;
    }

    /**
     * set prize and let status to won
     * @param string $sWnNumber
     * @param array $aPrized
     * @param array & $aPrizeDetails
     * @return bool
     */
    public function setWon($sWnNumber, $aPrized, & $aPrizeDetails, & $oTrace, & $oRiskProject = null) {
        $aPrizeSet = json_decode($this->prize_set);
        $aPrizes = [];
        $fPrize = 0;
        $iSingleWonCount = $iWonCount = 0;
        foreach ($aPrized as $iBasicMethodId => $aPrizeOfBasicMethod) {
//            list($iLevel, $iCount) = each($aPrizeOfBasicMethod);
            $oBasicMethod = BasicMethod::find($iBasicMethodId);
            foreach($aPrizeOfBasicMethod as $iLevel => $iCount){
                $aPrizes[] = [
                    'basic_method_id' => $iBasicMethodId,
                    'basic_method' => $oBasicMethod->name,
                    'level' => $iLevel,
                    'prize_set' => $aPrizeSet->$iBasicMethodId->$iLevel,
                    'single_won_count' => $iCount,
                    'won_count' => $iCount * $this->multiple,
                    'prize' => $fPrizeOf = $aPrizeSet->$iBasicMethodId->$iLevel * $iCount * $this->multiple * $this->coefficient,
                ];
                $iSingleWonCount += $iCount;
                $fPrize += $fPrizeOf;
                $iWonCount += $iCount * $this->multiple;
            }
        }
        if ($aPrizeDetails = $this->setWonDetails($aPrizes)) {
            $oCarbon = Carbon::now();
            $fPrizeSaleRate = formatNumber($fPrize / $this->amount,2);
            $fMinRiskPrizeSaleRate = SysConfig::get('wd_min_prize_sale_rate');
            $fMinRiskPrize = SysConfig::get('wd_min_prize');
            $data = [
                'prize' => $fPrize,
                'winning_number' => $sWnNumber,
                'counted_at' => $oCarbon->toDateTimeString(),
                'counted_time' => $oCarbon->timestamp,
                'status' => self::STATUS_WON,
                'single_won_count' => $iSingleWonCount,
                'won_count' => $iWonCount,
                'won_data' => json_encode($aPrizes),
                'prize_sale_rate' => $fPrizeSaleRate,
            ];
//            pr($data);
//            exit;
            if (($bSucc = $this->update($data) > 0)){
                // 判断风险
                if ( $fPrize >= $fMinRiskPrize && $fPrizeSaleRate >= $fMinRiskPrizeSaleRate){ // 盈利比及奖金条件
                    $oRiskProject = RiskProject::addRiskProject($this);
                }
                else{
                    $iBetTimeRiskSeconds = SysConfig::get('wd_bet_time_seconds');
                    $iBetTimeRiskPrize = SysConfig::get('wd_bet_time_min_prize');
                    if (($this->end_time - $this->bought_time < $iBetTimeRiskSeconds) && $fPrize >= $iBetTimeRiskPrize){
                        $oRiskProject = RiskProject::addRiskProject($this);
                    }
                }
                if ($this->trace_id) {
                    $this->prize = $fPrize;
                    $this->winning_number = $sWnNumber;
                    $this->counted_at = $data['counted_at'];
                    $this->single_won_count = $data['single_won_count'];
                    $this->won_count = $data['won_count'];
                    $this->won_data = $data['won_data'];
                    $this->status = self::STATUS_WON;
                    $bSucc = $this->updateTracePrize($oTrace);
                }
            } else {
                pr($this->validationErrors->toArray());
            }
        } else {
            $bSucc = false;
        }
        return $bSucc;
    }

    /**
     * 保存中奖详情
     * @param array & $aPrizes
     * @return array
     */
    protected function setWonDetails(& $aPrizes) {
        $aPrizeIds = [];
        foreach ($aPrizes as $aPrizeInfo) {
            if (!$iResult = PrjPrizeSet::addDetail($this, $aPrizeInfo)) {
                break;
            }
            $aPrizeIds[] = $iResult;
        }
        return $aPrizeIds;
    }

    /**
     * 派发奖金
     * @return int 派发笔数
     */
    public function sendPrizes() {
        $iCount = 0;
        $oDetails = PrjPrizeSet::getPrizeDetailOfProject($this->id, PrjPrizeSet::STATUS_WAIT);
        if (!$oDetails->count()) {
            return true;
        }
//        pr($oDetails->count());
//        pr($oDetails->toArray());
//        exit;
        $bSucc = true;
        foreach ($oDetails as $oPrjPrizeSet) {
            if (($iReturn = $oPrjPrizeSet->send($this)) < 0) {
                break;
            }
            $iCount ++;
        }
        return $iReturn;
    }

    /**
     * 派发返点
     * @return int 派发笔数
     */
    public function sendCommissions(& $aUsers, & $aAccounts, & $aCommissions) {
        $iCount = 0;
        $oDetails = Commission::getDetailsOfProject($this->id);
        if (!$oDetails->count()) {
            return true;
        }
//        pr($oDetails->count());
//        pr($oDetails->toArray());
//        exit;
        $bSucc = true;
        foreach ($oDetails as $oCommission) {
            if (($iReturn = $oCommission->send($this, $aUsers, $aAccounts)) < 0) {
                break;
            }
            isset($aCommissions[$oCommission->user_id]) ? $aCommissions[$oCommission->user_id] += $oCommission->amount : $aCommissions[$oCommission->user_id] = $oCommission->amount;
            $iCount ++;
        }
        return $iReturn;
    }

    /**
     * 设置派奖状态为已完成
     * @return bool
     */
    public function setPrizeSentStatus() {
        $aConditions = [
            'id' => ['=', $this->id],
            'status' => ['=', self::STATUS_WON],
//            'status_prize' => ['=',self::PRIZE_STATUS_SENDING]
//            'status_prize' => ['in', [self::PRIZE_STATUS_WAITING, self::PRIZE_STATUS_PARTIAL]]
            'status_prize' => ['in', [self::PRIZE_STATUS_SENDING]]
        ];
        $oCarbon = Carbon::now();
        $data = [
            'status_prize' => $this->status_prize = self::PRIZE_STATUS_SENT,
            'locked_prize' => 0,
            'prize_sent_at' => $this->prize_sent_at = $oCarbon->toDateTimeString(),
            'prize_sent_time' => $this->prize_sent_time = $oCarbon->timestamp
        ];
        if (!$bSucc = $this->strictUpdate($aConditions, $data)) {
            $this->prize_sent_at = $this->prize_sent_time = null;
            $this->status_prize = $this->original['status_prize'];
//            $this->deleteCache($this->id);
        }
        return $bSucc;
    }

    /**
     * 设置返点状态为已完成
     * @return bool
     */
    public function setCommissionSentStatus($bFinished = true) {
        $aConditions = [
            'id' => ['=', $this->id],
//            'status_commission' => ['in', [self::COMMISSION_STATUS_WAITING, self::COMMISSION_STATUS_PARTIAL]]
            'status_commission' => ['in', [self::COMMISSION_STATUS_SENDING]]
//            'status_commission' => ['=',self::COMMISSION_STATUS_SENDING]
        ];
//        $data        = [
//            'status_commission'  => self::COMMISSION_STATUS_SENT,
//            'locked_commission'  => 0,
//            'commission_sent_at' => Carbon::now()->toDateTimeString()
//        ];
//        return $this->strictUpdate($aConditions, $data);
        $iToStatus = $bFinished ? ManProject::COMMISSION_STATUS_SENT : ManProject::COMMISSION_STATUS_PARTIAL;
        $oCarbon = Carbon::now();
        $data = [
            'status_commission' => $this->status_commission = $iToStatus,
            'locked_commission' => 0,
            'commission_sent_at' => $this->commission_sent_at = $oCarbon->toDateTimeString(),
            'commission_sent_time' => $this->commission_sent_time = $oCarbon->timestamp
        ];
        if (!$bSucc = $this->strictUpdate($aConditions, $data)) {
            $this->commission_sent_at = $this->commission_sent_time = null;
            $this->status_commission = $this->original['status_commission'];
            $this->deleteCache($this->id);
        }
        return $bSucc;
    }

    /**
     * 加发送锁
     * @param bool $bForPrize 是否是派发奖金
     * @return bool
     */
    public function lock($bForPrize) {
        $sFunction = $bForPrize ? 'lockForSendPrize' : 'lockForSendCommission';
        return $this->$sFunction();
    }

    /**
     * 解发送锁
     * @param bool $bForPrize 是否是派发奖金
     * @return bool
     */
    public function unlock($bForPrize) {
        $sFunction = $bForPrize ? 'unlockForSendPrize' : 'unlockForSendCommission';
        return $this->$sFunction();
    }

    /**
     * 加奖金发送锁
     * @return bool
     */
    private function lockForSendPrize() {
        $aConditions = [
            'id' => ['=', $this->id],
            'status_prize' => ['=', self::PRIZE_STATUS_WAITING]
        ];
        $data = [
            'locked_prize' => $iThreadId = DbTool::getDbThreadId(),
            'status_prize' => self::PRIZE_STATUS_SENDING
        ];
        if ($bSucc = $this->strictUpdate($aConditions, $data)) {
            $this->status_prize = self::PRIZE_STATUS_SENDING;
            $this->locked_prize = $iThreadId;
//            $this->deleteCache($this->id);
        }
        return $bSucc;
    }

    /**
     * 加佣金发送锁
     * @return bool
     */
    private function lockForSendCommission() {
        $aConditions = [
            'id' => ['=', $this->id],
            'status_commission' => ['in', [self::COMMISSION_STATUS_WAITING,self::COMMISSION_STATUS_PARTIAL]]
        ];
        $data = [
            'status_commission' => $iThreadId = DbTool::getDbThreadId(),
            'status_commission' => self::COMMISSION_STATUS_SENDING
        ];
        if ($bSucc = $this->strictUpdate($aConditions, $data)) {
            $this->status_commission = self::COMMISSION_STATUS_SENDING;
            $this->locked_commission = $iThreadId;
//            $this->deleteCache($this->id);
        }
        return $bSucc;
    }

    /**
     * 解奖金发送锁
     * @return bool
     */
    public function unlockForSendPrize() {
        $aConditions = [
            'id' => ['=', $this->id],
            'status_prize' => ['=', self::PRIZE_STATUS_SENDING],
            'locked_prize' => $this->locked_prize
        ];
        $data = [
            'locked_prize' => 0,
            'status_prize' => self::PRIZE_STATUS_WAITING
        ];
        if ($bSucc = $this->strictUpdate($aConditions, $data)) {
            $this->status_commission = self::PRIZE_STATUS_WAITING;
            $this->locked_commission = 0;
//            $this->deleteCache($this->id);
        }
        return $bSucc;
    }

    /**
     * 解佣金发送锁
     * @return bool
     */
    public function unlockForSendCommission() {
        $aConditions = [
            'id' => ['=', $this->id],
            'status_commission' => ['=', self::COMMISSION_STATUS_SENDING],
            'locked_commission' => $this->locked_commission
        ];
        $data = [
            'locked_commission' => 0,
            'status_commission' => self::COMMISSION_STATUS_WAITING
        ];
        if ($bSucc = $this->strictUpdate($aConditions, $data)) {
            $this->locked_commission = 0;
            $this->status_commission = self::COMMISSION_STATUS_WAITING;
//            $this->deleteCache($this->id);
        }
        return $bSucc;
    }

    /**
     * 更新注单所属的追号任务的奖金,及向队列增加任务
     * @param Trace & $oTrace 返回Trace对象
     * @return boolean
     */
    function updateTracePrize(& $oTrace) {
        if (empty($this->trace_id)) {
            return true;
        }
        if ($this->status != ManProject::STATUS_WON) {
            return true;
        }
        $oTrace = ManTrace::find($this->trace_id);
        if (!$bSucc = $oTrace->updatePrize($this->issue, $this->prize)) {
            pr('trace-update-error');
            pr($oTrace->toArray());
            pr($oTrace->validationErrors->toArray());
        }
        return $bSucc;
    }

    /**
     * 重新派发奖金
     * @return bool or self::ERRNO_PRIZE_SENDING
     */
    public function setPrizeTask(& $sRealQueue) {
        if ($bNeedLocker = $this->status_prize == self::PRIZE_STATUS_SENDING) {
            $aThreads = DbTool::getDbThreads();
            if (in_array($this->locked_prize, $aThreads)) {
                return self::ERRNO_PRIZE_SENDING;
            }
        }
        $aConditions = [
            'id' => ['=', $this->id],
            'status' => ['=', self::STATUS_WON],
            'status_prize' => ['=', self::PRIZE_STATUS_SENDING],
        ];
        !$bNeedLocker or $aConditions['locked_prize'] = ['=', $this->locked_prize];
        $data = [
            'status_prize' => self::PRIZE_STATUS_WAITING,
            'locked_prize' => 0
        ];
        if ($bSucc = $this->strictUpdate($aConditions, $data)) {
//            $this->deleteCache($this->id);
            $aJobData = [
                'type' => 'prize',
                'projects' => [ $this->id ],
            ];
            $bSucc = BaseTask::addTask('SendPrize', $aJobData, 'prize',0,$sRealQueue);
        }
        return $bSucc;
    }

    /**
     * 重新派发佣金
     * @return bool or self::ERRNO_COMMISSION_SENDING
     */
    public function setCommissionTask() {
        if ($bNeedLocker = $this->status_commission == self::COMMISSION_STATUS_SENDING) {
            $aThreads = DbTool::getDbThreads();
            if (in_array($this->locked_commission, $aThreads)) {
                return self::ERRNO_COMMISSION_SENDING;
            }
        }
        $aConditions = [
            'id' => ['=', $this->id],
            'status' => ['in', [self::STATUS_WON, self::STATUS_LOST]],
            'status_commission' => ['in', [self::COMMISSION_STATUS_WAITING, self::COMMISSION_STATUS_SENDING]],
        ];
        !$bNeedLocker or $aConditions['locked_commission'] = ['=', $this->locked_commission];
        $data = [
            'status_commission' => self::COMMISSION_STATUS_WAITING,
            'locked_commission' => 0
        ];
        if ($bSucc = $this->strictUpdate($aConditions, $data)) {
//            $this->deleteCache($this->id);
            $aJobData = [
                'type' => 'commission',
                'projects' => [ $this->id],
            ];
            $bSucc = BaseTask::addTask('SendCommission', $aJobData, 'commission');
        }
        return $bSucc;
    }

    public function getTransactions() {
        return Transaction::where('project_id', '=', $this->id)->get();
    }

    /**
     * 将注单恢复至待计奖状态
     * @return bool
     */
    public function reset() {
        $aConditions = [
            'id' => ['=', $this->id],
            'status' => ['=', self::STATUS_WON],
            'status_prize' => ['=', self::PRIZE_STATUS_SENT]
        ];
        $data = [
            'status' => self::STATUS_NORMAL,
            'prize' => null,
            'status_prize' => self::PRIZE_STATUS_WAITING,
        ];
        if ($bSucc = $this->strictUpdate($aConditions, $data)) {
//            $this->deleteCache($this->id);
        }
        return $bSucc;
    }

    public static function & getTraceIdArrayOfDroped($iLotteryId, $sIssue) {
        $aConditions = [
            'lottery_id' => ['=', $iLotteryId],
            'issue' => ['=', $sIssue],
            'status' => ['=', self::STATUS_DROPED],
            'trace_id' => ['<>', null]
        ];
        $aTraces = self::doWhere($aConditions)->lists('trace_id');
        return $aTraces;
    }

    protected function getStatusPrizeFormattedAttribute() {
        return $this->attributes['status'] == self::STATUS_WON ? (__('_project.' . strtolower(Str::slug(static::$prizeStatuses[$this->attributes['status_prize']])))) : null;
    }

    protected function getStatusCommissionFormattedAttribute() {
        return __('_project.' . strtolower(Str::slug(static::$commissionStatuses[$this->attributes['status_commission']])));
    }

    protected function getDisplayBetNumberShortAttribute() {
        return mb_strlen($this->attributes['display_bet_number']) > 10 ? mb_substr($this->attributes['display_bet_number'], 0, 10) . '...' : $this->attributes['display_bet_number'];
    }

    protected function getdisplayBetNumberForViewAttribute() {
        $iWidthScreen = 120;
        if (strlen($this->attributes['display_bet_number']) > $iWidthScreen) {
            $sSplitChar = Config::get('bet.split_char');
            $aNumbers = explode($sSplitChar, $this->attributes['display_bet_number']);
            $iWidthBetNumber = strlen($aNumbers[0]);
            $aMultiArray = array_chunk($aNumbers, intval($iWidthScreen / $iWidthBetNumber));
            $aText = [];
            foreach ($aMultiArray as $aNumberArray) {
                $aText[] = implode($sSplitChar, $aNumberArray);
            }
            return implode('<br />', $aText);
        } else {
            return $this->attributes['display_bet_number'];
        }
    }

    protected function getFormattedIsTesterAttribute() {
        if ($this->attributes['is_tester'] !== null) {
            return __('_basic.' . strtolower(Config::get('var.boolean')[$this->attributes['is_tester']]));
        } else {
            return '';
        }
    }

    protected function getFriendlyEndTimeAttribute() {
        return date('H:i:s', $this->attributes['end_time']);
    }

    protected function getFriendlyBoughtAtAttribute() {
        return substr($this->attributes['bought_at'], 11);
    }

    public static function getBoughtUserCount($sBeginDate, $sEndDate = null) {
        $sEndDate or $sEndDate = "$sBeginDate 23:59:59";
        $sSql = "select count(distinct user_id) count from projects where bought_at between '$sBeginDate' and '$sEndDate' and is_tester = 0";
        $aResults = DB::select($sSql);
        return $aResults[0]->count ? $aResults[0]->count : 0;
    }

    protected function getBetRateFormattedAttribute() {
        return formatNumber($this->attributes['bet_rate'] * 100, 2) . '%';
    }

    protected function getBetCommitTimeFormattedAttribute() {
        return $this->attributes['bet_commit_time'] ? date('Y-m-d H:i:s', $this->attributes['bet_commit_time']) : null;
    }

    protected function getWonDataAttribute() {
        if (!$sWonData = $this->attributes['won_data']) {
            return null;
        }
        $aOriginalData = json_decode($sWonData, true);
//        pr($aOriginalData);
//        exit;
        $aWonData = [];
        foreach ($aOriginalData as $aSingleData) {
            $aWonData[] = $aSingleData['basic_method'] . ' : ' . ChnNumber::getLevel($aSingleData['level']) . ': ' . $aSingleData['single_won_count'] . ' , 总注数: ' . $aSingleData['won_count'];
        }
        return implode('<br />', $aWonData);
//        return var_export($aWonData, true);
    }

    public static function getBetNumbers($iLotteryId, $sIssue, $iSeriesWayId) {
        $sql = "select distinct bet_number from projects where lottery_id = '$iLotteryId' and issue = '$sIssue' and way_id = '$iSeriesWayId' order by bet_number";
//        $sql = "select distinct bet_number from projects where lottery_id = '$iLotteryId' and way_id = '$iSeriesWayId' order by bet_number";
        return DB::select($sql);
//        pr($data);
    }

    public static function deleteAllUserBetListCache() {
        $redis = Redis::connection();
        $sKeyPrefix = self::compileListCacheKeyPrefix();
        if ($aKeys = $redis->keys($sKeyPrefix . '*')) {
            foreach ($aKeys as $sKey) {
                $redis->del($sKey);
            }
        }
    }

    /**
     * 将注单恢复至待计奖状态
     * @return bool
     */
    public static function resetAll($iLotteryId, $sIssue) {
        $aConditions = [
            'lottery_id' => ['=', $iLotteryId],
            'issue' => ['=', $sIssue],
        ];
        $data = [
            'status' => self::STATUS_NORMAL,
            'prize' => null,
            'status_prize' => self::PRIZE_STATUS_WAITING,
        ];
        $oQuery = self::doWhere($aConditions);
        if ($oQuery->count() == 0) {
            return true;
        } else {
            return $oQuery->update($data) > 0;
        }
    }

    public static function getWayIds($iLotteryId, $sIssue){
        return static::where('lottery_id', '=', $iLotteryId)
                ->where('issue', '=', $sIssue)
                ->distinct()
                ->lists('way_id');
    }

    public static function getBetDelayCountOfUser($iUserId, $iRecentCount = 50){
        $oPrjs = static::where('user_id','=',$iUserId)->orderBy('id','desc')->take($iRecentCount)->get(['id','end_time','bought_time']);
        $iTotal = 0;
        foreach($oPrjs as $oPrj){
            $oPrj->bought_time <= $oPrj->end_time or $iTotal++;
        }
        return $iTotal;
    }

    public static function getTotalTurnover($iUserId,$dBeginTime,$dEndTime){
        $dBeginTime or $dBeginTime = '1970-01-01';
        return static::whereBetween('bought_at',[ $dBeginTime,$dEndTime ])
            ->where('user_id','=',$iUserId)
            ->whereIn('status',[ self::STATUS_LOST, self::STATUS_WON ])
            ->sum('amount');
    }

    public static function getTotalPrize($iUserId,$dBeginTime,$dEndTime){
        $dBeginTime or $dBeginTime = '1970-01-01';
        return static::whereBetween('bought_at',[ $dBeginTime,$dEndTime ])
            ->where('user_id','=',$iUserId)
            ->whereIn('status',[ self::STATUS_WON ])
            ->sum('prize');
    }

    /**
     * 获取在指定时间段内，投注比例不低于指定比例的注单数据
     *
     * @param datetime  $dBeginDate  开始时间
     * @param datetime  $dEndDate    结束时间
     * @param float         $fMinBetRate       投注比例
     * @param int       $iUserId     用户ID
     * @return Collection
     */
    public static function getBetRateOverloadedPrjs($dBeginDate, $dEndDate, $fMinBetRate, $iUserId = null, $iStatus = null){
        $aFields = ['id','lottery_id','issue','way_id','title','display_bet_number','prize','bought_at','status'];
        $oQuery = static::whereBetween('bought_at', [$dBeginDate, $dEndDate])
            ->where('bet_rate', '>=', $fMinBetRate);
        empty($iUserId) or  $oQuery =  $oQuery->where('user_id', '=', $iUserId);
        empty($iStatus) or  $oQuery =  $oQuery->where('status', '=', $iStatus);
        return $oQuery->get($aFields);
    }

    /** 返回指定时间段内，不小于指定奖金，且中投比不小于指定比例的注单
     *
     * @param datetime  $dBeginDate  开始时间
     * @param datetime  $dEndDate    结束时间
     * @param float         $fMinBetRate       投注比例
     * @param float         $fMinRateOfPrizeAndSale 最小中投比
     * @param float         $fMinPrize      最小奖金
     * @param int           $iUserId     用户ID
     * @return Collection
     */
    public static function getPrizeOverloadPrjs($dBeginDate, $dEndDate, $fMinRateOfPrizeAndSale, $fMinPrize, $iUserId = null){
        $aFields = ['id','lottery_id','issue','way_id','title','display_bet_number','prize','bought_at','status'];
        $oQuery = static::whereBetween('bought_at', [$dBeginDate, $dEndDate])
            ->where('prize_sale_rate', '>=', $fMinRateOfPrizeAndSale)
            ->where('prize', '>=', $fMinPrize);
        empty($iUserId) or  $oQuery =  $oQuery->where('user_id', '=', $iUserId);
        return $oQuery->get($aFields);
    }


}
