<?php

/**
 * 团队彩种盈亏表
 *
 * @author white
 */
class TeamLotteryWayProfit extends BaseModel {

    protected $table                         = 'team_lottery_way_profits';
    public static $resourceName              = 'TeamLotteryWayProfit';
    public static $amountAccuracy            = 6;
    public static $htmlNumberColumns         = [
        'deposit'         => 2,
        'withdrawal'      => 2,
        'turnover'        => 4,
        'prize'           => 6,
        'profit'          => 6,
        'commission'      => 6,
        'lose_commission' => 0,
    ];
    public static $htmlOriginalNumberColumns = [
        'prize_group'
    ];
    public static $columnForList             = [
        'date',
        'username',
        'is_tester',
        'user_type',
        'parent_user',
        'prize_group',
        'lottery_id',
        'way',
        'series_id',
        'turnover',
        'prize',
        'bonus',
        'commission',
        'lose_commission',
        'profit',
    ];
    public static $htmlSelectColumns         = [
        'lottery_id' => 'aLotteries',
        'series_id'  => 'aSeries',
    ];
    public static $totalColumns              = [
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
    ];
    public static $weightFields              = [
        'username',
        'lottery_id',
        'profit',
        'way',
    ];
    public static $classGradeFields          = [
        'profit',
    ];
    public static $noOrderByColumns          = [
        'user_type'
    ];
    protected $fillable                      = [
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
        'lottery_id',
        'way_id',
        'way',
        'series_id',
        'turnover',
        'prize',
        'bonus',
        'commission',
        'lose_commission',
        'profit',
    ];
    public static $rules                     = [
        'date'                => 'required|date',
        'user_id'             => 'required|integer',
        'is_agent '           => 'in:0,1',
        'prize_group'         => 'integer',
        'user_level'          => 'required|min:0',
        'username'            => 'required|max:16',
        'parent_user_id'      => 'integer',
        'parent_user'         => 'max:16',
        'user_forefather_ids' => 'max:100',
        'user_forefathers'    => 'max:1024',
        'lottery_id'          => 'required|integer|min:1',
        'way_id'              => 'required|integer|min:1',
        'way'                 => 'max:50',
        'series_id'           => 'required|integer|min:1',
        'turnover'            => 'numeric',
        'prize'               => 'numeric',
        'bonus'               => 'numeric',
        'profit'              => 'numeric',
        'commission'          => 'numeric',
        'lose_commission'     => 'numeric',
    ];
    public $orderColumns                     = [
        'date'     => 'desc',
        'turnover' => 'desc',
        'username' => 'asc',
    ];
    public static $mainParamColumn           = 'user_id';
    public static $titleColumn               = 'username';
    public static $aUserTypes                = ['-1' => 'Top Agent', '0' => 'Agent'];

    protected function beforeValidate() {
        if (!$this->series_id && $this->way_id) {
            $oSeriesWay      = SeriesWay::find($this->way_id);
            $this->series_id = $oSeriesWay->series_id;
        }
        if (!$this->way) {
            isset($oSeriesWay) or $oSeriesWay = SeriesWay::find($this->way_id);
            $oSeries    = Series::find($oSeriesWay->series_id);
            $this->way  = $oSeries->name . '-' . $oSeriesWay->name;
        }
    }

    /**
     * 返回对象
     *
     * @param string $sDate
     * @param string $iUserId
     * @return TeamProfit
     */
    public static function getTeamProfitObject($sDate, $iUserId, $iLotteryId, $iWayId) {
        $obj   = self::where('user_id', '=', $iUserId)
            ->where('date', '=', $sDate)
            ->where('lottery_id', '=', $iLotteryId)
            ->where('way_id', '=', $iWayId)
            ->lockForUpdate()
            ->first();
        $oUser = User::find($iUserId);
        $oUser->save();
        if (!is_object($obj)) {
            $oLottery = Lottery::find($iLotteryId);
            $data     = [
                'user_id'             => $oUser->id,
                'is_agent'            => $oUser->is_agent,
                'is_tester'           => $oUser->is_tester,
                'prize_group'         => $oUser->prize_group,
                'user_level'          => $oUser->user_level,
                'username'            => $oUser->username,
                'parent_user_id'      => $oUser->parent_id,
                'parent_user'         => $oUser->parent,
                'user_forefather_ids' => $oUser->forefather_ids,
                'user_forefathers'    => $oUser->forefathers,
                'date'                => $sDate,
                'lottery_id'          => $iLotteryId,
                'way_id'              => $iWayId,
                'series_id'           => $oLottery->series_id,
            ];
            $obj      = new static($data);
            if (!$obj->save()) {
                return false;
            }
            $obj = self::getTeamProfitObject($sDate, $iUserId, $iLotteryId, $iWayId);
        } else {
            $obj->user_level  = $oUser->user_level;
            $obj->prize_group = $oUser->prize_group;
        }
        return $obj;
    }

    /**
     * 代理盈亏总计
     * @param string $sBeginDate  开始日期
     * @param string $sEndDate    结束日期
     * @param int $iUserId         用户id
     * @return array
     */
    public static function getAgentSumInfo($sBeginDate, $sEndDate, $iUserId, $username = '') {
        $sSql     = 'select sum(deposit) total_deposit, sum(withdrawal) total_withdrawal,sum(turnover) total_turnover,sum(commission) total_commission, sum(profit) total_profit,sum(prize) total_prize, sum(lose_commission) total_lose_commission, sum(bonus) total_bonus from team_profits where 1';
        $iUserId == null or $sSql .= ' and (parent_user_id = ?)';
        $iUserId == null or $aValue   = [$iUserId];
//        !$username ? $sSql .=" or user_id=?)" : $sSql.=")";
//        $username or $aValue[] = $iUserId;
        !$sBeginDate or $sSql .=" and date>=?";
        !$sBeginDate or $aValue[] = $sBeginDate;
        !$sEndDate or $sSql .=" and date<=?";
        !$sEndDate or $aValue[] = $sEndDate;
        if ($username) {
            $sSql .= ' and username = ?';
            array_push($aValue, $username);
        }
        $results = DB::select($sSql, $aValue);
        return objectToArray($results[0]);
    }

    /**
     * 返回包含直接销售额，直接盈亏记录和团队销售额的数组
     *
     * @param string $sDate     只有年和月,格式：2014-01-01
     * @param string $iUserId   用户id
     * @return array
     */
    public static function getTeamProfitByDate($sBeginDate, $sEndDate, $iUserId) {
        $oQuery = static::where('user_id', '=', $iUserId);
        if (!is_null($sBeginDate)) {
            $oQuery->where('date', '>=', $sBeginDate);
        }
        if (!is_null($sEndDate)) {
            $oQuery->where('date', '<=', $sEndDate);
        }
        $aTeamProfits = $oQuery->get(['turnover', 'profit']);
        $data         = [];
        $i            = 0;
        foreach ($aTeamProfits as $oTeamProfit) {
            $data[$i]['turnover'] = $oTeamProfit->turnover;
            $data[$i]['profit']   = $oTeamProfit->profit;
            $i++;
        }
        return $data;
    }

    /**
     * 获取指定用户的团队销售总额
     * @param int $iUserId  用户id
     * @return float        团队销售总额
     */
    public static function getUserTotalTeamTurnover($sBeginDate, $sEndDate, $iUserId) {
        $aUserProfits   = static::getTeamProfitByDate($sBeginDate, $sEndDate, $iUserId);
        $aTeamTurnovers = [];
        foreach ($aUserProfits as $data) {
            $aTeamTurnovers[] = $data['turnover'];
        }
        $fTotalTeamTurnover = array_sum($aTeamTurnovers);
        return $fTotalTeamTurnover;
    }

    /**
     * 获取指定用户的销售总额
     * @param int $iUserId  用户id
     * @return float        销售总额
     */
    public static function getUserTotalTurnover($sBeginDate, $sEndDate, $iUserId) {
        $aUserProfits = static::getTeamProfitByDate($sBeginDate, $sEndDate, $iUserId);
        $aTurnovers   = [];
        foreach ($aUserProfits as $data) {
            $aTurnovers[] = $data['turnover'];
        }
        $fTotalTurnover = array_sum($aTurnovers);
        return $fTotalTurnover;
    }

    /**
     * 获取指定用户用户盈亏
     * @param int $iUserId  用户id
     * @return float        用户盈亏
     */
    public static function getUserTotalProfit($sBeginDate, $sEndDate, $iUserId) {
        $aUserProfits = static::getUserProfitByDate($sBeginDate, $sEndDate, $iUserId);
        $aProfits     = [];
        foreach ($aUserProfits as $data) {
            $aProfits[] = $data['profit'];
        }
        $fTotalProfit = array_sum($aProfits);
        return $fTotalProfit;
    }

    /**
     * 累加充值额
     * @param float $fAmount
     * @return boolean
     */
    public function addDeposit($fAmount) {
        $this->deposit += $fAmount;
        return $this->save();
    }

    /**
     * 累加提现额
     * @param float $fAmount
     * @return boolean
     */
    public function addWithdrawal($fAmount) {
        $this->withdrawal += $fAmount;
        return $this->save();
    }

    /**
     * 累加个人销售额
     * @param float $fAmount
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
     * @param float $fAmount
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
     * @return boolean
     */
    public function addLoseCommission($fAmount) {
        $this->lose_commission += $fAmount;
        $this->profit = $this->countProfit();
        return $this->save();
    }

    public static function & comipleTurnover($oUser, $fAmount) {
        $aForeFathers = explode(',', $oUser->forefather_ids);
        $aTurnovers   = [];
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

    public static function updateProfitData($sType, $sDate, $iLotteryId, $iWayId, $oUser, $fAmount) {

        $sFunction      = 'add' . ucfirst(String::camel($sType));
        $bSucc          = true;
        //适应金芒果的模型只需要给直接上级加数据
        if ($oUser->parent_id) {
            $oUserProfit = self::getTeamProfitObject($sDate, $oUser->parent_id, $iLotteryId, $iWayId);
            if (!is_object($oUserProfit)) {
                return false;
            }
            $bSucc = $oUserProfit->$sFunction($fAmount);
        }
        return $bSucc;

        /*
        $sFunction      = 'add' . ucfirst(String::camel($sType));
        $bSucc          = true;
        $aForeFathers   = $oUser->forefather_ids ? explode(',', $oUser->forefather_ids) : [];
        !$oUser->is_agent or $aForeFathers[] = $oUser->id;
        foreach ($aForeFathers as $iForeFatherId) {
            $oUserProfit = self::getTeamProfitObject($sDate, $iForeFatherId, $iLotteryId, $iWayId);
            if (!is_object($oUserProfit)) {
                return false;
            }
            if (!$bSucc = $oUserProfit->$sFunction($fAmount)) {

                break;
            }

        }
        return $bSucc;*/
    }

    // protected function getUserTypeFormattedAttribute() {
    //     // return static::$aUserTypes[($this->parent_user_id != null ? 'not_null' : 'null')];
    //     return __('_userprofit.' . strtolower(static::$aUserTypes[intval($this->parent_user_id != null) - 1]));
    // }

    protected function getUserTypeFormattedAttribute() {
        if ($this->parent_user_id) {
            $sUserType = User::$userTypes[$this->is_agent];
        } else {
            $sUserType = User::$userTypes[User::TYPE_TOP_AGENT];
        }
        return __('_user.' . $sUserType);
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
        return number_format($this->attributes['lose_commission'], 2);
    }

    protected function getProfitFormattedAttribute() {
        return $this->getFormattedNumberForHtml('profit');
    }

    public function clear() {
        $this->deposit    = 0;
        $this->withdrawal = 0;
        $this->turnover   = 0;
        $this->prize      = 0;
        $this->bonus      = 0;
        $this->commission = 0;
        $this->profit     = 0;
    }

    protected function getIsTesterFormattedAttribute() {
        return is_null($this->attributes['is_tester']) ? '' : __('_basic.' . strtolower(Config::get('var.boolean')[$this->attributes['is_tester']]));
    }

    /**
     * 获取总代指定时间的盈亏数据总和
     * @param string $sBeginDate    开始时间
     * @param string $sEndDate       结束时间
     */
    public static function & getTopAgentProfitByDate($sBeginDate, $sEndDate, $aUserIds = null, $bIn = true) {
        $oQuery = static::select(DB::raw('username,user_id,is_tester, sum(profit) total_profit, sum(turnover) total_turnover, sum(prize) total_prize, sum(commission) total_commission, sum(bonus) total_bonus, sum(lose_commission) total_lose_commission'));
        $oQuery = $oQuery->where('date', '>=', $sBeginDate)->where('date', '<=', $sEndDate)->where('user_level', '=', 0);
        if ($aUserIds) {
            $oQuery = $bIn ? $oQuery->whereIn('user_id', $aUserIds) : $oQuery->whereNotIn('user_id', $aUserIds);
        }
        $oQuery  = $oQuery->groupBy('username')->orderBy('user_id');
        $aResult = $oQuery->get();
        return $aResult;
    }

    /**
     * 获取总代指定时间的盈亏数据总和
     * @param string $sBeginDate    开始时间
     * @param string $sEndDate       结束时间
     * @return Array
     */
    public static function & getTopAgentSalesByDate($sBeginDate, $sEndDate, $aUserIds = null, $bIn = true) {
        $oQuery = static::select(DB::raw('username,user_id,is_tester, sum(turnover) total_turnover'));
        $oQuery = $oQuery->where('date', '>=', $sBeginDate)->where('date', '<=', $sEndDate)->where('user_level', '=', 0);
        if ($aUserIds) {
            $oQuery = $bIn ? $oQuery->whereIn('user_id', $aUserIds) : $oQuery->whereNotIn('user_id', $aUserIds);
        }
        $oQuery  = $oQuery->groupBy('username')->orderBy('user_id');
        $aResult = $oQuery->get();
        $aData   = [];
        foreach ($aResult as $obj) {
            $aData[$obj->user_id] = $obj->total_turnover;
        }
        return $aData;
    }

}
