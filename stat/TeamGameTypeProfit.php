<?php

/**
 * 团队盈亏明细表
 *
 * @author Garin
 * @date 2016-11-23
 */
class TeamGameTypeProfit extends BaseModel {

    protected $table = 'team_gt_profits';
    public static $resourceName = 'TeamGameTypeProfit';
    public static $amountAccuracy = 6;
    public static $htmlNumberColumns = [
        'deposit' => 2,
        'withdrawal' => 2,
        'turnover' => 4,
        'prize' => 6,
        'profit' => 6,
        'commission' => 6,
        'lose_commission' => 0,
    ];
    public static $htmlOriginalNumberColumns = [
        'prize_group'
    ];
    public static $columnForList = [
        'date',
        'username',
        'is_tester',
        'user_type',
        'game_type',
        'prize_group',
        'deposit',
        'withdrawal',
        'turnover',
        'prize',
        'bonus',
        'commission',
        'lose_commission',
        'profit',
    ];
    public static $totalColumns = [
        'deposit',
        'withdrawal',
        'turnover',
        'prize',
        'bonus',
        'commission',
        'profit',
    ];

    public static $listColumnMaps            = [
        'user_type'  => 'user_type_formatted',
        'turnover'   => 'turnover_formatted',
        'prize'      => 'prize_formatted',
        'bonus'      => 'bonus_formatted',
        'commission' => 'commission_formatted',
        'profit'     => 'profit_formatted',
        'is_tester'  => 'is_tester_formatted',
        'game_type'  => 'game_type_formatted'
    ];
    public static $viewColumnMaps            = [
        'user_type'  => 'user_type_formatted',
        'turnover'   => 'turnover_formatted',
        'prize'      => 'prize_formatted',
        'bonus'      => 'bonus_formatted',
        'commission' => 'commission_formatted',
        'profit'     => 'profit_formatted',
        'is_tester'  => 'is_tester_formatted',
        'game_type'  => 'game_type_formatted'
    ];
    public static $weightFields = [
        'username',
        'profit',
    ];
    public static $classGradeFields = [
        'profit',
    ];
    public static $noOrderByColumns = [
        'user_type'
    ];
    protected $fillable = [
        'date',
        'user_id',
        'is_agent',
        'is_tester',
        'prize_group',
        'user_level',
        'username',
        'parent_user_id',
        'parent_user',
        'user_forefather_ids',
        'user_forefathers',
        'deposit',
        'withdrawal',
        'turnover',
        'prize',
        'bonus',
        'commission',
        'lose_commission',
        'profit',
        'game_type',
    ];
    public static $rules = [
        'date' => 'required|date',
        'user_id' => 'required|integer',
        'is_agent ' => 'in:0,1',
        'prize_group' => 'integer',
        'user_level' => 'required|min:0',
        'username' => 'required|max:16',
        'parent_user_id' => 'integer',
        'parent_user' => 'max:16',
        'user_forefather_ids' => 'max:100',
        'user_forefathers' => 'max:1024',
        'deposit' => 'numeric|min:0',
        'withdrawal' => 'numeric|min:0',
        'turnover' => 'numeric',
        'prize' => 'numeric',
        'bonus' => 'numeric',
        'profit' => 'numeric',
        'commission' => 'numeric',
        'lose_commission' => 'numeric',
    ];
    public $orderColumns = [
        'date' => 'desc',
        'turnover' => 'desc',
        'username' => 'asc',
    ];
    public static $mainParamColumn = 'user_id';
    public static $titleColumn = 'username';
    public static $aUserTypes = ['-1' => 'Top Agent', '0' => 'Agent'];

    /**
     * 返回TeamProfit对象
     *
     * @param string  $sDate
     * @param integer $iUserId
     * @param integer $iGameType
     *
     * @return object TeamProfitDetails
     */
    public static function getTeamGameTypeProfitObject($sDate, $iUserId, $iGameType) {
        $obj = self::where('user_id', '=', $iUserId)
            ->where('date', '=', $sDate)
            ->where('game_type', '=', $iGameType)
            ->lockForUpdate()->first();
        $oUser = User::find($iUserId);
        if (!is_object($obj)) {
            $data = [
                'user_id' => $oUser->id,
                'is_agent' => $oUser->is_agent,
                'is_tester' => $oUser->is_tester,
                'prize_group' => $oUser->prize_group,
                'user_level' => $oUser->user_level,
                'username' => $oUser->username,
                'parent_user_id' => $oUser->parent_id,
                'parent_user' => $oUser->parent,
                'user_forefather_ids' => $oUser->forefather_ids,
                'user_forefathers' => $oUser->forefathers,
                'date' => $sDate,
                'game_type' => $iGameType
            ];
            $obj = new static($data);
            if (!$obj->save()) {
                return false;
            }
            $obj = self::getTeamGameTypeProfitObject($sDate, $iUserId, $iGameType);
        } else {
            $obj->user_level = $oUser->user_level;
            $obj->prize_group = $oUser->prize_group;
        }
        return $obj;
    }

    /**
     * 累加充值额
     *
     * @param float $fAmount
     *
     * @return boolean
     */
    public function addDeposit($fAmount) {
        $this->deposit += $fAmount;
        return $this->save();
    }

    /**
     * 累加提现额
     *
     * @param float $fAmount
     *
     * @return boolean
     */
    public function addWithdrawal($fAmount) {
        $this->withdrawal += $fAmount;
        return $this->save();
    }

    /**
     * 累加个人销售额
     *
     * @param float $fAmount
     *
     * @return boolean
     */
    public function addTurnover($fAmount) {
        $this->turnover += $fAmount;
        $this->profit = $this->countProfit();
        return $this->save();
    }

    /**
     * 累加奖金
     *
     * @param float $fAmount
     *
     * @return boolean
     */
    public function addPrize($fAmount) {
        $this->prize += $fAmount;
        $this->profit = $this->countProfit();
        return $this->save();
    }

    /**
     * 累加促销奖金
     *
     * @param float $fAmount
     *
     * @return boolean
     */
    public function addBonus($fAmount) {
        $this->bonus += $fAmount;
        $this->profit = $this->countProfit();
        return $this->save();
    }

    private function countProfit() {
        return $this->prize + $this->bonus + $this->commission + $this->lose_commission - $this->turnover;
    }

    /**
     * 累加个人佣金
     *
     * @param float $fAmount
     *
     * @return boolean
     */
    public function addCommission($fAmount) {
        $this->commission += $fAmount;
        $this->profit = $this->countProfit();
        return $this->save();
    }

    /**
     * 累加输值佣金
     *
     * @param float $fAmount
     *
     * @return boolean
     */
    public function addLoseCommission($fAmount) {
        $this->lose_commission += $fAmount;
        $this->profit = $this->countProfit();
        return $this->save();
    }

    public static function & comipleTurnover($oUser, $fAmount) {
        $aForeFathers = explode(',', $oUser->forefather_ids);
        $aTurnovers = [];
        foreach ($aForeFathers as $iForeFatherId) {
            $aTurnovers[$iForeFatherId] = $fAmount;
        }
        $aTurnovers[$oUser->id] = $fAmount;
        return $aTurnovers;
    }

    public static function updateTurnOver($sDate, $oUser, $fAmount) {
        return static::updateProfitData('turnover', $sDate, $oUser, $fAmount);
    }

    public static function updatePrize($sDate, $oUser, $fAmount) {
        return static::updateProfitData('prize', $sDate, $oUser, $fAmount);
    }

    public static function updateBonus($sDate, $oUser, $fAmount) {
        return static::updateProfitData('bonus', $sDate, $oUser, $fAmount);
    }

    public static function updateCommission($sDate, $oUser, $fAmount) {
        return static::updateProfitData('commission', $sDate, $oUser, $fAmount);
    }

    public static function updateProfitData($sType, $sDate, $iGameType, $oUser, $fAmount) {
        $sFunction = 'add' . ucfirst(String::camel($sType));
        $bSucc = true;
        //适应汇众国际的模型只需要给直接上级加代理盈亏数据
        if ($oUser->parent_id) {
            $oUserProfit = self::getTeamGameTypeProfitObject($sDate, $oUser->parent_id, $iGameType);
            if (!is_object($oUserProfit)) {
                return false;
            }
            $bSucc = $oUserProfit->$sFunction($fAmount);
        }
        return $bSucc;

        /*$aForeFathers = $oUser->forefather_ids ? explode(',', $oUser->forefather_ids) : [];
        !$oUser->is_agent or $aForeFathers[] = $oUser->id;
        foreach ($aForeFathers as $iForeFatherId) {
            $oUserProfit = self::getTeamGameTypeProfitObject($sDate, $iForeFatherId, $iGameType);
            if (!is_object($oUserProfit)) {
                return false;
            }
            if (!$bSucc = $oUserProfit->$sFunction($fAmount)) {
                break;
            }

        }
        return $bSucc;*/
    }


    protected function getUserTypeFormattedAttribute() {
        if ($this->parent_user_id)
            $sUserType = User::$userTypes[$this->is_agent];
        else
            $sUserType = User::$userTypes[User::TYPE_TOP_AGENT];
        return __('_user.' . $sUserType);
    }

    protected function getGameTypeFormattedAttribute() {
        $sGameTypes = GameType::getGameTypesIdentifier();

        return __('_gametype.' . $sGameTypes[$this->game_type]);
    }

    protected function getDepositFormattedAttribute() {
        return $this->getFormattedNumberForHtml('deposit');
    }

    protected function getWithdrawalFormattedAttribute() {
        return $this->getFormattedNumberForHtml('withdrawal');
    }

    protected function getTurnoverFormattedAttribute() {
        return $this->getFormattedNumberForHtml('turnover');
    }

    protected function getPrizeFormattedAttribute() {
        return $this->getFormattedNumberForHtml('prize');
    }

    protected function getBonusFormattedAttribute() {
        return $this->getFormattedNumberForHtml('bonus');
    }

    protected function getCommissionFormattedAttribute() {
        return $this->getFormattedNumberForHtml('commission');
    }

    protected function getLoseCommissionFormattedAttribute() {
        return $this->getFormattedNumberForHtml('lose_commission');
    }

    protected function getProfitFormattedAttribute() {
        return $this->getFormattedNumberForHtml('profit');
    }

    protected function getIsTesterFormattedAttribute() {
        return is_null($this->attributes['is_tester']) ? '' : __('_basic.' . strtolower(Config::get('var.boolean')[$this->attributes['is_tester']]));
    }
    public function clear() {
        $this->deposit = 0;
        $this->withdrawal = 0;
        $this->turnover = 0;
        $this->prize = 0;
        $this->bonus = 0;
        $this->commission = 0;
        $this->profit = 0;
    }

    /**
     * 获取总代指定时间按照游戏类别分组的盈亏数据总和
     *
     * @param       $sBeginDate 开始时间
     * @param       $sEndDate 结束时间
     * @param       $aUserIds
     * @param       $bIn
     *
     * @return mixed
     */
    public static function & getTopAgentProfitDetailsByDate($sBeginDate, $sEndDate, $aUserIds = [], $bIn = true) {
        $oQuery = static::select(DB::raw('username,user_id,date,game_type,is_tester,sum(profit) total_profit, sum(turnover) total_turnover, sum(prize) total_prize, sum(commission) total_commission, sum(bonus) total_bonus, sum(lose_commission) total_lose_commission'));
        $oQuery = $oQuery->where('date', '>=', $sBeginDate)->where('date', '<=', $sEndDate)->where('user_level', '=', 0);
        if ($aUserIds) {
            $oQuery = $bIn ? $oQuery->whereIn('user_id', $aUserIds) : $oQuery->whereNotIn('user_id', $aUserIds);
        }

        $oQuery = $oQuery->groupBy('username')
            ->groupBy('game_type')
            ->orderBy('user_id');
        $aResult = $oQuery->get();
        return $aResult;
    }

    /**
     * 获取总代指定时间按照游戏类别分组的销售额总和
     *
     * @param       $sBeginDate
     * @param       $sEndDate
     * @param array $aUserIds
     * @param bool  $bIn
     *
     * @return array
     */
    public static function & getTopAgentSalesDetailsByDate($sBeginDate, $sEndDate, $aUserIds = [], $bIn = true) {
        $oQuery = static::select(DB::raw('username,user_id,game_type,is_tester, sum(turnover) total_turnover'));
        $oQuery = $oQuery->where('date', '>=', $sBeginDate)->where('date', '<=', $sEndDate);//->where('user_level', '=', 0);
        if ($aUserIds) {
            $oQuery = $bIn ? $oQuery->whereIn('user_id', $aUserIds) : $oQuery->whereNotIn('user_id', $aUserIds);
        }
        $oQuery = $oQuery->groupBy('username')
            ->groupBy('game_type')
            ->orderBy('user_id');
        $aResult = $oQuery->get();
        $aData = [];
        foreach ($aResult as $obj) {
            $aData[$obj->user_id][$obj->game_type] = $obj->total_turnover;
        }
        return $aData;
    }

    /**
     * 获取指定日期的投注，返点信息
     * @author Lucky
     * @param $iUserId
     * @param $iGameType
     * @param $sBeginDate
     * @param $sEndDate
     */
    public static function getObjectsSumInfoByDate($iUserId, $iGameType, $sBeginDate, $sEndDate)
    {
        return static::select(DB::raw("sum(turnover) as turnover_total"))->where("user_id", $iUserId)->where("game_type", $iGameType)->whereBetween("date", [$sBeginDate, $sEndDate])->get()[0];
    }


    /**
     * 代理盈亏总计
     * @param string $sBeginDate  开始日期
     * @param string $sEndDate    结束日期
     * @param int $iGameType        游戏类别
     * @param int $iUserId         用户id
     * @return array
     */
    public static function getAgentSumInfo($sBeginDate, $sEndDate, $iGameType, $iUserId, $username = '') {
        $sSql     = 'select sum(deposit) total_deposit, sum(withdrawal) total_withdrawal,sum(turnover) total_turnover,sum(commission) total_commission, sum(profit) total_profit,sum(prize) total_prize, sum(lose_commission) total_lose_commission, sum(bonus) total_bonus from team_gt_profits where 1';
        $iUserId == null or $sSql .= ' and (parent_user_id = ?)';
        $iUserId == null or $aValue   = [$iUserId];
        !$sBeginDate or $sSql .=" and date>=?";
        !$sBeginDate or $aValue[] = $sBeginDate;
        !$sEndDate or $sSql .=" and date<=?";
        !$sEndDate or $aValue[] = $sEndDate;
        if ($username) {
            $sSql .= ' and username = ?';
            array_push($aValue, $username);
        }

        if ($iGameType) {
            $sSql .= ' and game_type = ?';
            array_push($aValue, $iGameType);
        }
        $results = DB::select($sSql, $aValue);
        return objectToArray($results[0]);
    }
}
