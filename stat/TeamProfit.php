<?php

/**
 * 团队盈亏表
 *
 * @author white
 */
class TeamProfit extends BaseModel {

    protected $table                         = 'team_profits';
    public static $resourceName              = 'TeamProfit';
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
        'deposit',
        'withdrawal',
        'turnover',
        'prize',
        'bonus',
        'commission',
        'lose_commission',
        'profit',
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
        'profit',
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
        'deposit',
        'withdrawal',
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
        'deposit'             => 'numeric|min:0',
        'withdrawal'          => 'numeric|min:0',
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

    // 按钮指向的链接，查询列名和实际参数来源的列名的映射
    // public static $aButtonParamMap = ['parent_user_id' => 'user_id'];

    /**
     * 返回TeamProfit对象
     *
     * @param string $sDate
     * @param string $iUserId
     * @return TeamProfit
     */
    public static function getTeamProfitObject($sDate, $iUserId) {
        $obj   = self::where('user_id', '=', $iUserId)->where('date', '=', $sDate)->lockForUpdate()->first();
        $oUser = User::find($iUserId);
        $oUser->save();
        if (!is_object($obj)) {

            $data = [
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
                'date'                => $sDate
            ];
            $obj  = new static($data);
            if (!$obj->save()) {
                return false;
            }
            $obj = self::getTeamProfitObject($sDate, $iUserId);
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

    public static function updateProfitData($sType, $sDate, $oUser, $fAmount) {
        $sFunction      = 'add' . ucfirst(String::camel($sType));
        $bSucc          = true;
        //适应汇众国际的模型只需要给直接上级加代理盈亏数据
        if ($oUser->parent_id) {
            $oUserProfit = self::getTeamProfitObject($sDate, $oUser->parent_id);
            if (!is_object($oUserProfit)) {
                return false;
            }
            $bSucc = $oUserProfit->$sFunction($fAmount);
        }
        return $bSucc;


        /*
        $bSucc          = true;
        $aForeFathers   = $oUser->forefather_ids ? explode(',', $oUser->forefather_ids) : [];//给父级加
        !$oUser->is_agent or $aForeFathers[] = $oUser->id;//自己是代理也要给自己加
        foreach ($aForeFathers as $iForeFatherId) {
            $oUserProfit = self::getTeamProfitObject($sDate, $iForeFatherId);
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
    public static function & getTopAgentProfitByDate($sBeginDate, $sEndDate, $aUserIds = null, $bIn = true, $bIsLevel = true) {
        $oQuery = static::select(DB::raw('username,user_id,is_tester, sum(profit) total_profit, sum(turnover) total_turnover, sum(prize) total_prize, sum(commission) total_commission, sum(bonus) total_bonus, sum(lose_commission) total_lose_commission'));
        if (!$bIsLevel) {
            $oQuery = $oQuery->where('date', '>=', $sBeginDate)->where('date', '<=', $sEndDate)->whereNull('parent_user_id');
        }

        $oQuery = $oQuery->where('date', '>=', $sBeginDate)->where('date', '<=', $sEndDate);


        if ($aUserIds) {
            $oQuery = $bIn ? $oQuery->whereIn('user_id', $aUserIds) : $oQuery->whereNotIn('user_id', $aUserIds);
        }
        $oQuery  = $oQuery->groupBy('username')->orderBy('user_id');

//        $oQuery = $oQuery->where('is_tester',false);
        $aResult = $oQuery->get();

        return $aResult;
    }

 /**
     * 获取agent指定时间的盈亏数据总和
     * @param string $sBeginDate    开始时间
     * @param string $sEndDate       结束时间
     */
    public static function & getAgentProfitByDate($sBeginDate, $sEndDate, $iParentId) {
        $oQuery = static::select(DB::raw('username,user_id,is_tester, sum(profit) total_profit, sum(turnover) total_turnover, sum(prize) total_prize, sum(commission) total_commission, sum(bonus) total_bonus, sum(lose_commission) total_lose_commission'));
        $oQuery = $oQuery->whereBetween('date',[$sBeginDate,$sEndDate])->where('is_agent',true);
        $oQuery = $oQuery->whereRaw('(find_in_set(?,user_forefather_ids))', [$iParentId, $iParentId]);

//        $oQuery = $oQuery->where('is_tester',false);
        $aResult = $oQuery->get();

        return $aResult;
    }


    /**
     * 获取团队指定时间的盈亏数据总和
     * @author lucky
     * @date 2016-11-15
     * @param string $sBeginDate    开始时间
     * @param string $sEndDate       结束时间
     */
    public static function & getParentProfitByDate($sBeginDate, $sEndDate) {
        $oQuery = static::select(DB::raw('username,user_id,is_tester, sum(profit) total_profit, sum(turnover) total_turnover, sum(prize) total_prize, sum(commission) total_commission, sum(bonus) total_bonus, sum(lose_commission) total_lose_commission'));
        $oQuery = $oQuery->where('date', '>=', $sBeginDate)->where('date', '<=', $sEndDate);
        $oQuery = $oQuery->groupBy('user_id')->orderBy('user_id');
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

    /**
     * 用户今日获利,投注,返点
     * @author lucky
     * @created_at 2016-10-10
     * @param int $iUserId
     * @return mixed
     */
    static function getTeamProfitObjByDate($iUserId,$sDate=null) {
        $sDate = $sDate ? $sDate : Carbon::now()->toDateString();
        return static::where('user_id',$iUserId)->where('date',$sDate);
    }


    /**
     * 用户本月获利,投注,返点
     * @author lucky
     * @created_at 2016-10-10
     * @param int $iUserId
     * @return mixed
     */
    static function getTeamSumProfitObjByDate($iUserId = 0, $dDateStart = null, $dDateEnd = null)
    {
        return static::getSumCommission($iUserId, $dDateStart, $dDateEnd);
    }

    static function getSumCommission($user_id = null, $time_start = null, $time_end = null)
    {
        $rs = static::select(DB::raw("sum(commission) as commission_total,sum(turnover) as turnover_total,sum(profit) as profits_total,sum(deposit) as deposit_total"));
        if ($user_id) {
            $rs = $rs->where("user_id", "=", $user_id);
        }
        if ($time_start) {
            $rs = $rs->where("date", ">=", $time_start);
        }
        if ($time_end) {
            $rs = $rs->where("date", "<=", $time_end);
        }
        return $rs->get()[0];
    }

    /**
     * 查询某天内用户的团队盈亏
     *
     * @author Garin
     * @date 2016-11-09
     *
     * @param $sDate 时间
     * @param $aUserIds 用户id
     * @param $iUserLevel 用户层级  [默认值false,不限制层级;其他和系统层级对应的值则限制生效]
     *
     * @return mixed
     */
    public static function getProfitByDateAndUserIds($sDate, $aUserIds, $iUserLevel = false) {
        if (empty($sDate)) {
            return false;
        }

        $aConditions['date'] = ['=', $sDate];
        //用户层级 默认为不限制层级
        if ($iUserLevel !== false && $iUserLevel >= 0) {
            $aConditions['user_level'] = ['<=', $iUserLevel];
        }

        if (!empty($aUserIds)) {
            $aConditions['user_id'] = ['in', $aUserIds];
        }

        return static::doWhere($aConditions)->get(['id', 'user_id', 'turnover', 'date']);
    }
}
