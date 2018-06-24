<?php

/**
 * 用户彩种投注方式盈亏表
 *
 * @author white
 */
class UserLotteryWayProfit extends BaseModel {

    protected $table = 'user_lottery_way_profits';
    public static $resourceName = 'UserLotteryWayProfit';
    public static $htmlOriginalNumberColumns = [
        'prize_group'
    ];
    public static $amountAccuracy = 6;
    public static $htmlNumberColumns = [
        'deposit' => 2,
        'withdrawal' => 2,
        'turnover' => 4,
        'prize' => 6,
        'profit' => 6,
        'commission' => 6,
        'lose_commission' => 2,
    ];
    public static $columnForList = [
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
    public static $htmlSelectColumns = [
        'lottery_id' => 'aLotteries',
        'series_id' => 'aSeries',
    ];
    public static $totalColumns = [
        'turnover',
        'prize',
        'bonus',
        'commission',
        'profit',
    ];
    public static $listColumnMaps = [
        'user_type' => 'user_type_formatted',
        'turnover' => 'turnover_formatted',
        'prize' => 'prize_formatted',
        'bonus' => 'bonus_formatted',
        'commission' => 'commission_formatted',
        'profit' => 'profit_formatted',
        'is_tester' => 'is_tester_formatted',
    ];
    public static $weightFields = [
        'username',
        'lottery_id',
        'profit',
        'way',
    ];
    public static $classGradeFields = [
        'profit',
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
        'lottery_id' => 'required|integer|min:1',
        'way_id' => 'required|integer|min:1',
        'way' => 'max:50',
        'series_id' => 'required|integer|min:1',
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

    protected function beforeValidate() {
        if (!$this->series_id && $this->way_id) {
            $oSeriesWay = SeriesWay::find($this->way_id);
            $this->series_id = $oSeriesWay->series_id;
        }
        if (!$this->way) {
            isset($oSeriesWay) or $oSeriesWay = SeriesWay::find($this->way_id);
            $oSeries = Series::find($oSeriesWay->series_id);
            $this->way = $oSeries->name . '-' . $oSeriesWay->name;
        }
    }

    /**
     * 返回对象
     *
     * @param string $sDate
     * @param string $iUserId
     *
     * @return UserProfit
     */
    public static function getUserProfitObject($sDate, $iUserId, $iLotteryId, $iWayId) {
        $obj = self::where('user_id', '=', $iUserId)
            ->where('date', '=', $sDate)
            ->where('lottery_id', '=', $iLotteryId)
            ->where('way_id', '=', $iWayId)
            ->lockForUpdate()->first();
        $oUser = User::find($iUserId);
        $oUser->save();
        if (!is_object($obj)) {

            $oLottery = Lottery::find($iLotteryId);
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
                'lottery_id' => $iLotteryId,
                'way_id' => $iWayId,
                'series_id' => $oLottery->series_id,
            ];
            $obj = new static($data);
            if (!$obj->save()) {
                // pr($obj->getValidationErrorString());
                return false;
            }
            $obj = self::getUserProfitObject($sDate, $iUserId, $iLotteryId, $iWayId);
        } else {
            $obj->user_level = $oUser->user_level;
            $obj->prize_group = $oUser->prize_group;
        }
        return $obj;
    }

    /**
     * 返回包含直接销售额，直接盈亏记录和团队销售额的数组
     *
     * @param        $sBeginDate  只有年和月,格式：2014-01-01
     * @param        $sEndDate   只有年和月,格式：2014-01-01
     * @param string $iUserId 用户id
     *
     * @return array
     */
    public static function getUserProfitByDate($sBeginDate, $sEndDate, $iUserId) {
        $oQuery = static::where('user_id', '=', $iUserId);
        if (!is_null($sBeginDate)) {
            $oQuery->where('date', '>=', $sBeginDate);
        }
        if (!is_null($sEndDate)) {
            $oQuery->where('date', '<=', $sEndDate);
        }
        $aUserProfits = $oQuery->get(['turnover', 'profit']);
        $data = [];
        $i = 0;
        foreach ($aUserProfits as $oUserProfit) {
            $data[$i]['turnover'] = $oUserProfit->turnover;
            $data[$i]['profit'] = $oUserProfit->profit;
            $i++;
        }
        return $data;
    }

    /**
     * 获取指定用户的销售总额
     *
     * @param     $sBeginDate  只有年和月,格式：2014-01-01
     * @param     $sEndDate   只有年和月,格式：2014-01-01
     * @param int $iUserId 用户id
     *
     * @return float        销售总额
     */
    public static function getUserTotalTurnover($sBeginDate, $sEndDate, $iUserId) {
        $aUserProfits = static::getUserProfitByDate($sBeginDate, $sEndDate, $iUserId);
        $aTurnovers = [];
        foreach ($aUserProfits as $data) {
            $aTurnovers[] = $data['turnover'];
        }
        $fTotalTurnover = array_sum($aTurnovers);
        return $fTotalTurnover;
    }

    /**
     * 获取指定用户用户盈亏
     *
     * @param     $sBeginDate  只有年和月,格式：2014-01-01
     * @param     $sEndDate   只有年和月,格式：2014-01-01
     * @param int $iUserId 用户id
     *
     * @return float        用户盈亏
     */
    public static function getUserTotalProfit($sBeginDate, $sEndDate, $iUserId) {
        $aUserProfits = static::getUserProfitByDate($sBeginDate, $sEndDate, $iUserId);
        $aProfits = [];
        foreach ($aUserProfits as $data) {
            $aProfits[] = $data['profit'];
        }
        $fTotalProfit = array_sum($aProfits);
        return $fTotalProfit;
    }

    /**
     * 获取用户擅长的彩种玩法
     *
     * @param        $iUserIds
     * @param string $sBeginDate
     * @param string $sEndDate
     *
     * @return array
     */
    public static function getUserGoodAtLotteryWay($iUserIds, $sBeginDate = '', $sEndDate = '') {
        $aConditions = [];
        if (is_array($iUserIds) && !empty($iUserIds)) {
            $aConditions['user_id'] = ['in', $iUserIds];
        } else {
            return [];
        }

        if (!empty($sBeginDate)) {
            $aConditions['date'] = ['>=', $sBeginDate];
        }
        if (!empty($sEndDate)) {
            $aConditions['date'] = ['<=', $sBeginDate];
        }

        $oQuery = static::doWhere($aConditions);
        $oUserLotteryWays = $oQuery->select('id', 'user_id', 'lottery_id', 'way_id', 'way', DB::raw('sum(turnover) as turnoverSum'), DB::raw('sum(prize) as prizeSum'), DB::raw('sum(prize)/sum(turnover) as per'))
            ->groupBy('user_id', 'lottery_id', 'way_id')
            ->get();
        $aLotteries = Lottery::getTitleList();

        //筛选用户中奖概率最大的彩种玩法作为用户最擅长的
        $aUserGoodAtLotteryWay = [];
        foreach ($oUserLotteryWays as $key => $oLotteryWay) {
            if (!isset($aUserGoodAtLotteryWay[$oLotteryWay->user_id]) ||
                ($aUserGoodAtLotteryWay[$oLotteryWay->user_id]['per'] < $oLotteryWay->per)
            ) {
                $aUserGoodAtLotteryWay[$oLotteryWay->user_id]['lottery_name'] = $aLotteries[$oLotteryWay->lottery_id];
                $aUserGoodAtLotteryWay[$oLotteryWay->user_id]['title'] = $oLotteryWay->way;
                $aUserGoodAtLotteryWay[$oLotteryWay->user_id]['per'] = $oLotteryWay->per;
            }
        }
        return $aUserGoodAtLotteryWay;
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

    public function countProfit() {
        return $this->prize + $this->bonus + $this->commission + $this->lose_commission - $this->turnover;
    }

    /**
     * 代理盈亏总计
     *
     * @param string $sBeginDate 开始日期
     * @param string $sEndDate 结束日期
     * @param int    $iUserId 用户id
     *
     * @return array
     */
    public static function getAgentSumInfo($sBeginDate, $sEndDate, $iUserId, $username = '') {
        $sSql = 'select sum(turnover) total_turnover,sum(commission) total_commission, sum(profit) total_profit,sum(prize) total_prize, sum(lose_commission) total_lose_commission, sum(bonus) total_bonus from user_lottery_profits where (parent_user_id = ? ';
        $aValue = [$iUserId];
        !$username ? $sSql .= " or user_id=?)" : $sSql .= ")";
        $username or $aValue[] = $iUserId;
        !$sBeginDate or $sSql .= " and date>=?";
        !$sBeginDate or $aValue[] = $sBeginDate;
        !$sEndDate or $sSql .= " and date<=?";
        !$sEndDate or $aValue[] = $sEndDate;
        if ($username) {
            $sSql .= ' and username = ?';
            array_push($aValue, $username);
        }
        $results = DB::select($sSql, $aValue);
        return objectToArray($results[0]);
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

    public static function updateProfitData($sType, $sDate, $iLotteryId, $iWayId, $oUser, $fAmount) {
        $sFunction = 'add' . ucfirst(String::camel($sType));
        $oProfit = self::getUserProfitObject($sDate, $oUser->id, $iLotteryId, $iWayId);
        if (!is_object($oProfit)) {
            return false;
        }
        $bSucc = $oProfit->$sFunction($fAmount);
        if (!$bSucc) {
            pr($oProfit->getValidationErrorString());
            //file_put_contents('/tmp/setlotteryprofit', $oProfit->getValidationErrorString());
        }
        return $bSucc;
    }

    public static function clearProfitData($sDate, $oUser, $iLotteryId) {
        $oProfit = static::getUserProfitObject($sDate, $oUser->id, $iLotteryId);
        if ($oProfit->id) {
            $oProfit->deposit = $oProfit->withdrawal = $oProfit->turnover = $oProfit->prize = $oProfit->bonus - $oProfit->commission = $oProfit->profit = 0;
            $oProfit->save();
        }
    }

    // protected function getUserTypeFormattedAttribute() {
    //     // return static::$aUserTypes[($this->parent_user_id != null ? 'not_null' : 'null')];
    //     return __('_userprofit.' . strtolower(static::$aUserTypes[intval($this->parent_user_id != null) - 1]));
    // }

    /**
     * 获得最近用户投注的彩种ID列表
     *
     * @param int $iUserId
     * @param int $iCount
     * @param int $iDays
     *
     * @return array
     */
    public static function getBoughtLotteryIdsOfUserId($iUserId, $iCount = 6, $iDays = 15) {
        $dEarliestDate = date('Y-m-d', strtotime("-$iDays day"));
        return static::where('user_id', '=', $iUserId)
            ->where('date', '>=', $dEarliestDate)
            ->distinct()
            ->orderBy('turnover', 'desc')
            ->take($iCount)
            ->lists('lottery_id');
    }

    protected function getUserTypeFormattedAttribute() {
        if ($this->parent_user_id)
            $sUserType = User::$userTypes[$this->is_agent];
        else
            $sUserType = User::$userTypes[User::TYPE_TOP_AGENT];
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
        return $this->getFormattedNumberForHtml('lose_commission');
//        return number_format($this->attributes['lose_commission'], 2);
    }

    protected function getProfitFormattedAttribute() {
        return $this->getFormattedNumberForHtml('profit');
    }

    protected function getIsTesterFormattedAttribute() {
        return is_null($this->attributes['is_tester']) ? '' : __('_basic.' . strtolower(Config::get('var.boolean')[$this->attributes['is_tester']]));
    }

}
