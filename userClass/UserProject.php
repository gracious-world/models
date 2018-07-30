<?php

use Illuminate\Support\Facades\Redis;

class UserProject extends Project {

    protected static $cacheUseParentClass = true;
    public static $columnForList          = [
        'lottery_id',
        'serial_number',
        'bet_number',
        'amount',
        'prize',
        'status',
    ];
    protected $fillable                   = [
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
    protected $maxBetListLength           = 20;

    protected function beforeValidate() {
        $this->terminal_id or $this->terminal_id = Session::get('terminal_id');
        if (!$this->single_amount || !$this->amount || !$this->user_id || !$this->lottery_id || !$this->issue || !$this->way_id) {
            return false;
        }

        $oUser                     = User::find($this->user_id);
        if (!$this->user_forefather_ids || !$this->account_id) {
            $this->user_forefather_ids = $oUser->forefather_ids;
            $this->account_id          = $oUser->account_id;
            $this->is_tester           = $oUser->is_tester;
//            $this->prize_group = $oUser->prize_group;
        }
        $this->status or $this->status        = self::STATUS_NORMAL;
        $this->trace_id or $this->trace_id      = null;
//        $this->coefficient or $this->coefficient = 0;
        $this->single_amount or $this->single_amount = 0;
        $this->serial_number or $this->serial_number = static::makeSeriesNumber($this->user_id);


        //前台投注时,自己的等级,返点比率,返点值
        $this->user_grade = $oUser->grade;//自己的等级
        $oLottery = Lottery::find($this->lottery_id);
        $iGameTypeId = $oLottery->game_type;
        $oRebateSetting = RebateSetting::getRebateSettingByUserIdGameTypeId($this->user_id,$iGameTypeId);//从 grade_game_type_sets 表里面 获取 此会员及其上线对应的 返点比率
        if($oRebateSetting){
            //说明 这个 会员级别 此彩种 有 返点比率
            $this->user_rebate = $oRebateSetting->user_rebate;//会员自己的 返点比率
            $this->user_commission = formatNumber($this->user_rebate * $this->amount, 6);
        }
        $iParentId = $oUser->parent_id;//上级的用户id号
        $this->user_parent_id = 0;
        if ($iParentId && $oRebateSetting) {
            //如果存在上级,则 上级的 等级,返点比率,返点值 写入.否则 为 NULL
            $oParent = User::find($iParentId);
            $this->parent_grade = $oParent->grade;//上级等级
            $this->parent_rebate = $oRebateSetting->parent_rebate;//会员上级的 返点比率
            $this->parent_commission = formatNumber($this->parent_rebate * $this->amount, 6);//上级返点
        }
                return parent::beforeValidate();
    }

    /**
     * 生成序列号
     * @param int $iUserId
     * @return string
     */
    public static function makeSeriesNumber($iUserId) {
//        return md5($iUserId . microtime(true) . mt_rand());
        return uniqid($iUserId, true);
    }

    /**
     * 保存注单
     * @return int      0: 成功; -1: 交易明细数据错误; -2: 账变保存失败; -3: 账户余额保存失败; -4: 余额不足 -5: 注单保存失败 -6: 佣金数据保存失败 -7: 奖金数据保存失败
     */
    public function addProject($bNeedIP = true, & $iErrno = null) {
        if ($this->Account->available < $this->amount) {
            $iErrno = self::ERRNO_BET_ERROR_LOW_BALANCE;
            return false;
        }
        $rules = & static::compileRules($this);
        if (!$bNeedIP) {
            unset($rules['ip'], $rules['proxy_ip']);
        }
        if (!$this->save($rules)) {
//            pr($this->validationErrors->toArray());
            $iErrno = self::ERRNO_BET_ERROR_SAVE_ERROR;
            return false;
        }

        $aExtraData               = $this->getAttributes();
        $aExtraData['project_id'] = $this->id;
        $aExtraData['project_no'] = $this->serial_number;
        unset($aExtraData['id']);
        // //获取该彩种可用优惠券额度  @update Damon 2016.07.03
        // $fDiscountCardAmount      = UserActivityUserBonus::getUserAvailableTotalAmountByLottery($this->lottery_id, $this->user_id);
        // //如果不为0，优惠卡转移到余额中
        // if ($fDiscountCardAmount > 0) {
        //     $transferMoney = min($fDiscountCardAmount, $this->amount);
        //     $bSucc         = UserActivityUserBonus::TransferAmountToAccountForProject($this, $this->User, $this->Account);
        //     if (!$bSucc) {
        //         $iErrno = self::ERRNO_BET_ERROR_SAVE_ERROR;
        //         return false;
        //     }
        // }

        $iReturn = Transaction::addTransaction($this->User, $this->Account, TransactionType::TYPE_BET, $this->amount, $aExtraData);
        $bSucc   = $iReturn == Transaction::ERRNO_CREATE_SUCCESSFUL;
        !$bSucc or $bSucc   = $this->saveCommissions();
        if ($bSucc) {
            $bSucc = UserTurnover::updateTurnoverData($this->lottery_id, $this->issue, $this->user_id, $this->amount);
            // 处理销售量
//            $this->addBackTask(true);
//            $aTaskData = [
//                'user_id' => $this->user_id,
//                'amount' => $this->amount,
//                'date' => substr($this->bought_at,0,10)
//            ];
//            $bSucc = BaseTask::addTask('StatUpdateTurnover',$aTaskData,'stat');
//            $iReturn = UserProfit::updateTurnOver(substr($this->bought_at,0,10),$this->User,$this->amount) ? self::ERRNO_BET_SUCCESSFUL : self::ERRNO_BET_TURNOVER_UPDATE_FAILED;
        }
        return $bSucc;
//        return $iReturn;
//        return $iReturn == self::ERRNO_BET_SLAVE_DATA_SAVED ? self::ERRNO_BET_SUCCESSFUL : $iReturn;
//        if (($iSlaveDataSaved = $this->saveCommissions()) != self::ERRNO_BET_SLAVE_DATA_SAVED){
//            return $iSlaveDataSaved;
//        }
//        $iReturn = $this->save() ? 0 : -4;
//        return self::ERRNO_BET_SUCCESSFUL;
    }

    /**
     * 组合注单数据
     * @param array     $aOrder
     * @param SeriesWay $oSeriesWay
     * @param Lottery   $oLottery
     * @param array     $aExtraData
     * @return array &
     */
    public static function & compileProjectData($aOrder, $oSeriesWay, $oLottery, $aExtraData = [], $oBetTime = null) {
        if (isset($aOrder['user_id'])) {
            $iUserId        = $aOrder['user_id'];
            $sForeFatherIds = $aOrder['user_forefather_ids'];
            $sUsername      = $aOrder['username'];
            $iAccountId     = $aOrder['account_id'];
        } else {
            $iUserId        = Session::get('user_id');
            $sForeFatherIds = Session::get('forefather_ids');
            $sUsername      = Session::get('username');
            $iAccountId     = Session::get('account_id');
        }
        
        $oBetTime or $oBetTime = Carbon::now();
//        $oIssue = Issue::getIssue($oLottery->id, $aOrder['issue']);
        $iUserId  = isset($aOrder['user_id']) ? $aOrder['user_id'] : Session::get('user_id');
        
        $oUser = User::find($iUserId);
        
        $data     = [
            'trace_id'            => isset($aOrder['trace_id']) ? $aOrder['trace_id'] : 0,
            'user_id'             => $iUserId,
            'username'            => $sUsername,
            'account_id'          => $iAccountId,
            'multiple'            => $aOrder['multiple'],
//            'serial_number'       => '',
            'user_forefather_ids' => $sForeFatherIds,
            'issue'               => $aOrder['issue'],
            'end_time'            => $aOrder['end_time'],
            'title'               => $oSeriesWay->name,
            'position'            => $aOrder['position'],
            'way_total_count'     => $oSeriesWay->total_number_count,
            'single_count'        => $aOrder['single_count'],
            'bet_rate'            => $aOrder['single_count'] / $oSeriesWay->total_number_count,
            'bet_number'          => $aOrder['bet_number'],
            'display_bet_number'  => isset($aOrder['display_bet_number']) ? $aOrder['display_bet_number'] : $aOrder['bet_number'],
            'lottery_id'          => $oLottery->id,
            'way_id'              => $oSeriesWay->id,
            'coefficient'         => $aOrder['coefficient'],
            'single_amount'       => $aOrder['single_amount'],
            'amount'              => $aOrder['single_amount'] * $aOrder['multiple'],
            'bought_at'           => $oBetTime->toDateTimeString(),
            'bought_time'         => $oBetTime->timestamp,
            'user_parent_id'      => $oUser->parent_id
        ];
        if (isset($aOrder['prize_set'])) {
            $data['prize_set']   = $aOrder['prize_set'];
            $data['prize_group'] = $aOrder['prize_group'];
        } else {
            $aPrizeSettingOfUsers = UserPrizeSet::getPrizeSetOfUsers([$iUserId], $oLottery->id, $oSeriesWay->id, $aGroupNames);
            $data['prize_set']    = json_encode($aPrizeSettingOfUsers[$iUserId]);
            $data['prize_group']  = $aGroupNames[$iUserId];
        }
        if (isset($aExtraData['client_ip'])) {
            $data['ip']       = $aExtraData['client_ip'];
            $data['proxy_ip'] = $aExtraData['proxy_ip'];
        }
        if (isset($aExtraData['terminal_id'])) {
            $data['terminal_id']       = $aExtraData['terminal_id'];
        }
        $data['is_tester'] = $aExtraData['is_tester'];
        return $data;
    }

    protected function getSplittedWinningNumberAttribute() {
        if (!$this->winning_number) {
            return [];
        }
        $oLottery  = Lottery::find($this->lottery_id);
        return $oLottery->type == Lottery::LOTTERY_TYPE_DIGITAL ? str_split($this->winning_number, 1) : $aSplitted = explode(' ', $this->winning_number);
    }

    public static function createListCache($iUserId, $iPage = 1, $iPageSize = 20, $iLotteryId = '') {
        $redis     = Redis::connection();
        $sKey      = static::compileListCacheKey($iUserId, $iPage, $iLotteryId);
        $aColumns  = ['id', 'lottery_id', 'amount', 'status', 'issue', 'title', 'prize_group', 'multiple', 'display_bet_number', 'coefficient', 'winning_number', 'prize'];
        $oQuery    = static::where('user_id', '=', $iUserId);
        $iLotteryId == '' or $oQuery    = $oQuery->where('lottery_id', '=', $iLotteryId);
        $iStart    = ($iPage - 1) * $iPageSize;
        $oProjects = $oQuery->orderBy('id', 'desc')->skip($iStart)->limit($iPageSize)->get($aColumns);
        $redis->multi();
        $redis->del($sKey);
        foreach ($oProjects as $oProject) {
            $redis->rpush($sKey, json_encode($oProject->toArray()));
        }
        $redis->exec();
    }

    public static function & getListOfPage($iUserId, $iPage = 1, $iPageSize = 20, $iLotteryId = '') {
        $redis       = Redis::connection();
        $sKey        = static::compileListCacheKey($iUserId, $iPage, $iLotteryId);
        if (!$bHasInRedis = $redis->exists($sKey)) {
            static::createListCache($iUserId, $iPage, $iPageSize, $iLotteryId);
        }
        $aProjectsFromRedis = $redis->lrange($sKey, 0, $redis->llen($sKey) - 1);
        $aProjects          = [];
        foreach ($aProjectsFromRedis as $sProject) {
            $obj         = new static;
            $obj         = $obj->newFromBuilder(json_decode($sProject, true));
            $aProjects[] = $obj;
        }
        unset($aProjectsFromRedis, $obj, $sKey, $redis);
//        pr($aProjects);
//        exit;
        return $aProjects;
    }

    public static function & getLatestRecords($iUserId = null, $iCount = 10, $iLotteryId = '') {
        $aProjects      = $aFirstProjects = & static::getListOfPage($iUserId, 1, 20, $iLotteryId) ? array_slice($aFirstProjects, 0, $iCount) : [];
        return $aProjects;
    }

    public static function & getLatestRecordsEx($iStartTime, $iUserId = null, $iCount = 10, $iLotteryId = '') {
        $sBeginTime = date('Y-m-d H:i:s',$iStartTime);
        $aColumns = ['id', 'lottery_id', 'amount', 'status', 'issue', 'title', 'prize_group', 'multiple', 'display_bet_number', 'coefficient', 'winning_number', 'prize'];
        $oQuery = static::where('bought_at', '>=', $sBeginTime)->where('user_id', '=', $iUserId);
        $iLotteryId == '' or $oQuery = $oQuery->where('lottery_id', '=', $iLotteryId);
        $oProjects = $oQuery->orderBy('id', 'desc')->limit($iCount)->get($aColumns);
//        $aProjects = $aFirstProjects = & static::getListOfPage($iUserId, 1, 20, $iLotteryId) ? array_slice($aFirstProjects, 0, $iCount) : [];
        return $oProjects;
    }

    protected function getAmountFormattedAttribute() {
        return number_format($this->amount, 4);
    }

    public static function getWonProjects($iLotteryId, $iCount = 5) {
        return static::where('lottery_id', '=', $iLotteryId)->where('status', '=', self::STATUS_WON)->orderBy('id', 'desc')->take($iCount)->get(['username', 'title', 'prize']);
    }

    protected function getWonOrLoseAttribute() {
        $iStatus = $this->attributes['status'];
        switch ($iStatus) {
            case self::STATUS_LOST:
                $text = '否';
                break;
            case self::STATUS_WON:
                $text = '是';
                break;
            default:
                $text = '';
        }
        return $text;
    }

//    /**
//     * 计算佣金
//     * 计算佣金 根据 game-type 及 等级 2016-10-12
//     * 根本不用考虑奖金组. 直接 给 本人及 其直属上线那一个人 根据等级 和 game-type 算佣金
//     * @param array $aCommissions &
//     * @return void
//     */
//    protected function & compileCommissions() {
//        $aCommissions    = [];
//
//        $oLottery = Lottery::find($this->lottery_id);
//        $iGameTypeId = $oLottery->game_type;
//
//        $oRebateSetting = RebateSetting::getRebateSettingByUserIdGameTypeId($this->user_id,$iGameTypeId);//从 grade_game_type_sets 表里面 获取 此会员及其上线对应的 返点比率
//        if(!$oRebateSetting){
//            return $aCommissions;
//        }
//        $oUser           = User::find($this->user_id);
//        if (($iRateSelf = $oRebateSetting->user_rebate) > 0){
//            $fAmountSelf         = formatNumber($iRateSelf * $this->amount, 6);
//            $aCommissionDataSelf = [
//                'user_id'             => $oUser->id,
//                'account_id'          => $oUser->account_id,
//                'username'            => $oUser->username,
//                'is_tester'           => $oUser->is_tester,
//                'user_forefather_ids' => $oUser->forefather_ids,
//                'base_amount'         => $this->amount,
//                'amount'              => $fAmountSelf,
//                'user_parent_id'      => $oUser->parent_id
//            ];
//            $aCommissions[] = array_merge($this->compileBasicData(), $aCommissionDataSelf);
//        }
//
//        if (!$this->user_forefather_ids) {
//            return $aCommissions;
//        }
//
//        $oParent           = User::find($oUser->parent_id);
//        if (($iRateParent = $oRebateSetting->parent_rebate) > 0){
//            $fAmountParent = formatNumber($iRateParent * $this->amount, 6); //上级的 佣金,,仅仅只是 和 当前投注会员的等级和投注的彩票有关系..和上级自身无关 2016-10-13
//            $aCommissionDataParent = [
//                'user_id'             => $oParent->id,
//                'account_id'          => $oParent->account_id,
//                'username'            => $oParent->username,
//                'is_tester'           => $oParent->is_tester,
//                'user_forefather_ids' => $oParent->forefather_ids?$oParent->forefather_ids:"",
//                'base_amount'         => $this->amount,
//                'amount'              => $fAmountParent,
//                'parent_id'           => $oParent->parent_id
//            ];
//            $aCommissions[] = array_merge($this->compileBasicData(), $aCommissionDataParent);
//        }
//        return $aCommissions;
//    }

 /*   //计算佣金 根据奖金组
    protected function & compileCommissions() {
        $aCommissions    = [];
        if ($aSelfCommission = $this->compileSelfCommission()) {
            $aCommissions[] = $aSelfCommission;
        }
        if (!$this->user_forefather_ids) {
            return $aCommissions;
        }
        $aFores       = $aUserIds     = explode(',', $this->user_forefather_ids);
        array_push($aUserIds, $this->user_id);
        $iGroupId     = UserPrizeSet::getGroupId($this->user_id, $this->lottery_id, $sGroupName);
        $aPrizeGroups = UserPrizeSet::getPrizeGroupOfUsers($aFores, $this->lottery_id);
        $iForeCount   = count($aFores);
        foreach ($aFores as $i => $iUserId) {
            $iUpAgentGroup  = $aPrizeGroups[$iUserId];
            $iDownUserGroup = ($i < $iForeCount - 1) ? $aPrizeGroups[$aFores[$i + 1]] : $sGroupName;
            if ($iUpAgentGroup <= $iDownUserGroup) {
                continue;
            }
            $oFore          = User::find($iUserId);
            $aCommissions[] = $this->compileSingleCommission($oFore, $iUpAgentGroup - $iDownUserGroup);
        }
//        pr($aCommissions);
//        exit;
        return $aCommissions;
    }*/

    /**
     * 计算佣金
     *
     * @param array $aCommissions &
     * @return void
     */
    protected function & compileCommissions() {
        $aCommissions    = [];
//        if ($aSelfCommission = $this->compileSelfCommission()) {
//            $aCommissions[] = $aSelfCommission;
//        }
        if (!$this->user_forefather_ids) {
            return $aCommissions;
        }
        $aFores       = $aUserIds     = explode(',', $this->user_forefather_ids);
        array_push($aUserIds, $this->user_id);
        $iGroupId     = UserPrizeSet::getGroupId($this->user_id, $this->lottery_id, $sGroupName);
        $aPrizeGroups = UserPrizeSet::getPrizeGroupOfUsers($aFores, $this->lottery_id);
        $iForeCount   = count($aFores);
        foreach ($aFores as $i => $iUserId) {
            $iUpAgentGroup  = $aPrizeGroups[$iUserId];
            $iDownUserGroup = ($i < $iForeCount - 1) ? $aPrizeGroups[$aFores[$i + 1]] : $sGroupName;
            if ($iUpAgentGroup <= $iDownUserGroup) {
                continue;
            }
            $oFore          = User::find($iUserId);
            $aCommissions[] = $this->compileSingleCommission($oFore, $iUpAgentGroup - $iDownUserGroup);
        }
//        pr($aCommissions);
//        exit;
        return $aCommissions;
    }

    protected function & compileCommissionsOld() {
        $aCommissions    = [];
        if ($aSelfCommission = $this->compileSelfCommission()) {
            $aCommissions[] = $aSelfCommission;
        }
        if (!$this->user_forefather_ids) {
            return $aCommissions;
        }
        $aFores                = $aUserIds              = explode(',', $this->user_forefather_ids);
        array_push($aUserIds, $this->user_id);
        $iGroupId              = UserPrizeSet::getGroupId($this->user_id, $this->lottery_id, $sGroupName);
        $aPrizeSettingOfUsers  = UserPrizeSet::getPrizeSetOfUsers($aUserIds, $this->lottery_id, $this->way_id, $aGroupNames);
        $aPrizeSettingOfBettor = & $aPrizeSettingOfUsers[$this->user_id];
        $aTheoreticPrizeSets   = PrizeLevel::getTheoreticPrizeSets($this->Lottery->type);
//        pr($aTheoreticPrizeSets);
//        exit;
//        pr($aPrizeSettingOfUsers);
//        pr($aPrizeSettingOfBettor);
        $aBasicData            = $this->compileBasicData();
        $iForeCount            = count($aFores);
        foreach ($aPrizeSettingOfBettor as $iBasicMethodId => $aPrizeSetting) {
            foreach ($aPrizeSetting as $iLevel => $fPrize) {
                $aPrizeData = [
                    'basic_method_id' => $iBasicMethodId,
                    'level'           => $iLevel,
                    'prize_set'       => $fPrize,
                    'prize'           => $fPrize * $this->multiple * $this->coefficient,
                ];

                for ($i = $iForeCount - 1; $i >= 0; $i--) {
                    if ($i == $iForeCount - 1) {
                        $iLastId = $this->user_id;
                    }
                    $iForeId = $aFores[$i];
                    $oFore   = User::find($iForeId);
                    if ($aPrizeSettingOfUsers[$iForeId][$iBasicMethodId][$iLevel] == $aPrizeSettingOfUsers[$iLastId][$iBasicMethodId][$iLevel]) {
                        continue;
                    }
                    $fDiffPrizeSet  = $fDiffPrize     = ($aPrizeSettingOfUsers[$iForeId][$iBasicMethodId][$iLevel] - $aPrizeSettingOfUsers[$iLastId][$iBasicMethodId][$iLevel]);
                    $aCommissions[] = $this->compileSingleCommission($oFore, $iBasicMethodId, $iLevel, $fPrize, $fDiffPrizeSet, $aTheoreticPrizeSets);
                    $iLastId        = $iForeId;
                }
                break;
            }
            break;
        }
        return $aCommissions;
    }

    //计算佣金 根据奖金组
    private function compileSingleCommission($oUser, $fDiffPrizeGroup) {
        $aCommissionData = [
            'user_id'             => $oUser->id,
            'account_id'          => $oUser->account_id,
            'username'            => $oUser->username,
            'is_tester'           => $oUser->is_tester,
            'user_forefather_ids' => $oUser->forefather_ids,
            'base_amount'         => $this->amount,
            'amount'              => $fAmount = Commission::countCommission($fDiffPrizeGroup, $this->amount),
            'user_parent_id'      => $oUser->parent_id
        ];
        if($oUser->id == $this->user_parent_id){
            $this->parent_rebate = $fAmount;
        }
        return array_merge($this->compileBasicData(), $aCommissionData);
    }


    private function compileSingleCommissionOld($oUser, $iBasicMethodId, $iLevel, $fPrize, $fDiffPrizeSet, $aTheoreticPrizeSets) {
        $aCommissionData = [
            'user_id'             => $oUser->id,
            'account_id'          => $oUser->account_id,
            'username'            => $oUser->username,
            'user_parent_id'      => $oUser->parent_id,
            'is_tester'           => $oUser->is_tester,
            'user_forefather_ids' => $oUser->forefather_ids,
            'basic_method_id'     => $iBasicMethodId,
            'level'               => $iLevel,
            'prize_set'           => $fPrize,
            'base_amount'         => $this->amount,
            'amount'              => Commission::countCommission($fDiffPrizeSet, $aTheoreticPrizeSets[$iBasicMethodId][$iLevel], $this->amount),
        ];
        return array_merge($this->compileBasicData(), $aCommissionData);
    }

    private function compileBasicData() {
        $iGameType = Lottery::getGameTypeByLotteryId($this->lottery_id);
        return [
            'project_id'  => $this->id,
            'project_no'  => $this->serial_number,
            'trace_id'    => $this->trace_id,
//            'user_id'             => $this->user_id,
//            'username'            => $this->username,
//            'user_forefather_ids' => $this->user_forefather_ids,
//            'account_id'          => $this->account_id,
            'coefficient' => $this->coefficient,
            'game_type'   => $iGameType,
            'lottery_id'  => $this->lottery_id,
            'issue'       => $this->issue,
            'multiple'    => $this->multiple,
            'way_id'      => $this->way_id,
            'bought_at'   => $this->bought_at
        ];
    }

    //计算佣金 根据奖金组
    private function compileSelfCommission() {
        $iMaxGroupId = UserUserPrizeSet::getGroupId($this->user_id, $this->lottery_id, $sMaxGroupName);
        if ($sMaxGroupName == $this->prize_group) {
            return false;
        }
        $oUser           = User::find($this->user_id);
        $iRate           = ($sMaxGroupName - $this->prize_group) / 2000;
        $fAmount         = formatNumber($iRate * $this->amount, 6);
        $aCommissionData = [
            'user_id'             => $oUser->id,
            'account_id'          => $oUser->account_id,
            'username'            => $oUser->username,
            'is_tester'           => $oUser->is_tester,
            'user_forefather_ids' => $oUser->forefather_ids,
//            'basic_method_id'     => $iBasicMethodId,
//            'level'               => $iLevel,
//            'prize_set'           => $fPrize,
            'base_amount'         => $this->amount,
            'amount'              => $fAmount,
        ];

        return array_merge($this->compileBasicData(), $aCommissionData);
    }


    protected function setSingleAmountAttribute($fAmount) {
        $this->attributes['single_amount'] = formatNumber($fAmount, static::$amountAccuracy);
    }

    protected function setAmountAttribute($fAmount) {
        $this->attributes['amount'] = formatNumber($fAmount, static::$amountAccuracy);
    }

    protected function setCoefficientAttribute($fCoefficient) {
//        $aCoefficient = Config::get('bet.coefficients');
        $aCoefficient = Coefficient::getValidCoefficientValues();
        $fCoefficient = formatNumber($fCoefficient, 4);
        if (!in_array($fCoefficient, $aCoefficient)) {
            return false;
        }
        return $this->attributes['coefficient'] = $fCoefficient;
    }

    protected function setSerialNumberAttribute($sSerialNumber) {
        $this->attributes['serial_number'] = strtoupper($sSerialNumber);
    }

}
