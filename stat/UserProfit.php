<?php

/**
 * 用户盈亏表
 *
 * @author white
 */
class UserProfit extends BaseModel {

    protected $table = 'user_profits';
    public static $resourceName = 'UserProfit';
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
        'lose_commission' => 0,
    ];
    public static $columnForList = [
        'date',
        'username',
        'is_tester',
        'user_type',
        'parent_user',
        'prize_group',
        'deposit',                        //分红
        'withdrawal',                     //提现
        'turnover',                       //投注
        'prize',
        'bonus',
        'commission',                     //返点
        'lose_commission',                //输值佣金
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
        'profit',
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
        'deposit',
        'withdrawal',
        'turnover',
        'prize',
        'bonus',
        'commission',
        'lose_commission',
        'profit',
        'prj_count',
        'won_prj_count',
        'transfer_in',
        'transfer_out',
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
        'date' => 'desc'
    ];
    public static $mainParamColumn = 'user_id';
    public static $titleColumn = 'username';
    public static $aUserTypes = ['-1' => 'Top Agent', '0' => 'Agent'];

    // 按钮指向的链接，查询列名和实际参数来源的列名的映射
    // public static $aButtonParamMap = ['parent_user_id' => 'user_id'];

    /**
     * 返回UserProfit对象
     *
     * @param string $sDate
     * @param string $iUserId
     *
     * @return UserProfit
     */
    public static function getUserProfitObject($sDate, $iUserId) {
        $obj = self::where('user_id', '=', $iUserId)->where('date', '=', $sDate)->lockForUpdate()->first();
        $oUser = User::find($iUserId);
        $oUser->save();
        if (!is_object($obj)) {
//            $oUser = User::find($iUserId);
//            pr($oUser->toArray());
//            pr($oUser->toArray());
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
                'date' => $sDate
            ];
            $obj = new static($data);
            if (!$obj->save()) {
                return false;
            }
            $obj = self::getUserProfitObject($sDate, $iUserId);
        } else {
            $obj->user_level = $oUser->user_level;
            $obj->prize_group = $oUser->prize_group;
        }
        return $obj;
    }

    /**
     * 返回包含直接销售额，直接盈亏记录和团队销售额的数组
     *
     * @param string $sDate 只有年和月,格式：2014-01-01
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
        $aUserProfits = $oQuery->get(['turnover', 'profit', 'commission', 'deposit','prize']);
        $data = [];
        $i = 0;
        foreach ($aUserProfits as $oUserProfit) {
            $data[$i]['turnover'] = $oUserProfit->turnover;
            $data[$i]['profit'] = $oUserProfit->profit;
            $data[$i]['commission'] = $oUserProfit->commission;
            $data[$i]['deposit'] = $oUserProfit->deposit;
            $data[$i]['bonus'] = $oUserProfit->bonus;
            $data[$i]['prize'] = $oUserProfit->prize;
            $i++;
        }
        return $data;
    }

    /**
     * 获取指定用户的销售总额
     *
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
     * 获取指定用户的销售总额
     *
     * @param int $iUserId 用户id
     *
     * @return float        销售总额
     */
    public static function getUserTotalDeposit($sBeginDate, $sEndDate, $iUserId) {
        $aUserProfits = static::getUserProfitByDate($sBeginDate, $sEndDate, $iUserId);
        $aTurnovers = [];
        foreach ($aUserProfits as $data) {
            $aTurnovers[] = $data['deposit'];
        }
        $fTotalTurnover = array_sum($aTurnovers);
        return $fTotalTurnover;
    }

    /**
     * 获取指定用户用户盈亏
     *
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
     * 获取指定用户用户盈亏
     *
     * @param int $iUserId 用户id
     *
     * @return float        用户盈亏
     */
    public static function getUsersTotalProfit($sBeginDate, $sEndDate, $iUserId) {
        $aUserProfits = static::getUserProfitByDate($sBeginDate, $sEndDate, $iUserId);
        $aProfits = [];
        foreach ($aUserProfits as $data) {
            $aProfits[] = $data['profit'];
        }
        $fTotalProfit = array_sum($aProfits);
        return $fTotalProfit;
    }

    /**
     * 获取指定用户用户返点
     *
     * @param int $iUserId 用户id
     *
     * @return float        用户盈亏
     */
    public static function getUserTotalCommission($sBeginDate, $sEndDate, $iUserId) {
        $aUserProfits = static::getUserProfitByDate($sBeginDate, $sEndDate, $iUserId);
        $aProfits = [];
        foreach ($aUserProfits as $data) {
            $aProfits[] = $data['commission'];
        }
        $fTotalProfit = array_sum($aProfits);
        return $fTotalProfit;
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
        $fAmount > 0 ? $this->prj_count++ : $this->prj_count--;//前台会员下单时,user_profits表里面这个会员 这天的 prj_count 相应变化 2016-09-30
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
        $fAmount > 0 ? $this->won_prj_count++ : $this->won_prj_count--;//计奖时,user_profits表里面这个会员 这天的 won_prj_count 相应变化 2016-09-30
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
        $sSql = 'select sum(deposit) total_deposit, sum(withdrawal) total_withdrawal,sum(turnover) total_turnover,sum(commission) total_commission, sum(profit) total_profit,sum(prize) total_prize, sum(lose_commission) total_lose_commission, sum(bonus) total_bonus from user_profits where (parent_user_id = ? ';
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

    public static function getTotalAmount($sColumn, $sBeginDate, $sEndDate, $iUserId) {
        return static::whereBetween('date', [$sBeginDate, $sEndDate])
            ->where('user_id', '=', $iUserId)
            ->sum($sColumn);
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

    public static function updateProfitData($sType, $sDate, $oUser, $fAmount) {
        $sFunction = 'add' . ucfirst(String::camel($sType));
//        $sFunction = 'add' . ucfirst($sType);
        $oProfit = self::getUserProfitObject($sDate, $oUser->id);
        if (!is_object($oProfit)) {
            return false;
        }
//            pr($oUserProfit->validationErrors->toArray());
        $bSucc = $oProfit->$sFunction($fAmount);
//        pr($bSucc);
        return $bSucc;
    }

    public static function clearProfitData($sDate, $oUser) {
        $oProfit = static::getUserProfitObject($sDate, $oUser->id);
        if ($oProfit->id) {
            $oProfit->deposit = $oProfit->withdrawal = $oProfit->turnover = $oProfit->prize = $oProfit->bonus - $oProfit->commission = $oProfit->profit = 0;
            $oProfit->save();
        }
    }

    // protected function getUserTypeFormattedAttribute() {
    //     // return static::$aUserTypes[($this->parent_user_id != null ? 'not_null' : 'null')];
    //     return __('_userprofit.' . strtolower(static::$aUserTypes[intval($this->parent_user_id != null) - 1]));
    // }

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

    /**
     * 获取返点
     * @author lucky
     * @create_date 2016-08-09
     *
     * @param $children_ids
     * @param $series_id 数字彩，竞彩,老虎机
     * @param $time_start
     * @param $time_end
     *
     * @return mixed
     */

    static function getCommission($user_id = null, $time_start = null, $time_end = null) {
        $rs = static::select(DB::raw("sum(commission) as commission_total,sum(turnover) as turnover_total"));
        if ($user_id) {
            $rs = $rs->where("user_id", "=", $user_id);
        }
        if ($time_start) {
            $rs = $rs->where("date", ">=", $time_start);
        }
        if ($time_end) {
            $rs = $rs->where("date", "<", $time_end);
        }

        return $rs->get()[0];
    }

    /**
     * 用户今日获利,投注
     * @author lucky
     * @created_at 2016-10-10
     *
     * @param int $iUserId
     *
     * @return mixed
     */
    static function getUserTodayCommission($iUserId = 0) {
        $sTodayStart = Date::todayDate();
        $sTommorrowStart = Date::tommorrowDate();

        return static::getCommission($iUserId, $sTodayStart, $sTommorrowStart);
    }

    /**
     * 获取某一天的 英雄榜数据
     * @author lucda
     * @date 2016-11-14
     *
     * @param     $sDay
     * @param int $iLimit
     *
     * @return array
     */
    static function getDayHeros($sDay, $iLimit = 10) {
        return static::doWhere(['date' => ['=', $sDay]])->select('user_id', 'username', 'turnover', 'prize', 'commission', 'profit', DB::raw('won_prj_count/prj_count as per'))->orderBy('prize', 'desc')->orderBy('id', 'desc')->limit($iLimit)->get()->toArray();
    }

    /**
     * 组合 英雄榜数据, 和 某天 相比 的 上升名次
     * @author lucda
     * @date 2016-11-14
     *
     * @param $aData
     * @param $sDay
     *
     * @return array
     */
    static function compileDayHeros($aData, $sDay) {
        $aUserIds = array_pluck($aData, 'user_id');
        $aConditions['date'] = ['=', $sDay];
        $aConditions['user_id'] = ['in', $aUserIds];
        $aUserProfits = static::doWhere($aConditions)->select('user_id', 'prize')->get()->lists('prize', 'user_id');//获取 指定某天 的 所有这些会员的 信息

        //$aData这组数据 和 指定某天的这批会员的数据$aUserProfits 上升名次 计算
        for ($i = 0; $i < sizeof($aData); $i++) {
            $aData[$i]['top'] = 0;
            if (isset($aUserProfits[$aData[$i]['user_id']])) {
                //说明这个会员 昨天 也有 userprofits 记录
                $iPrize = $aUserProfits[$aData[$i]['user_id']];
                $iGtCount = static::doWhere(['date' => ['=', $sDay], 'prize' => ['>', $iPrize]])->select(DB::raw('count(id) as gtCount'))->lists('gtCount')[0];//奖金 大于 此会员 的 人数

                $aUserProfitEquels = static::doWhere(['date' => ['=', $sDay], 'prize' => ['=', $iPrize]])->select('user_id')->orderBy('prize', 'desc')->orderBy('id', 'desc')->get()->toArray();
                $iEquel = 0;
                foreach ($aUserProfitEquels as $k => $aUserProfitEquel) {
                    if ($aUserProfitEquel['user_id'] == $aData[$i]['user_id']) {
                        $iEquel = $k;
                    }
                }

                $aData[$i]['top'] = $iGtCount + $iEquel - $i;
            }
        }
        return $aData;
    }

    /**
     * 获取连续些天的 英雄榜数据
     * @author lucda
     * @date 2016-11-14
     *
     * @param     $sDateBegin
     * @param     $sDateEnd
     * @param int $iLimit
     *
     * @return array
     */
    static function getDaysHeros($sDateBegin, $sDateEnd, $iLimit = 10) {
        return static::whereBetween('date', [$sDateBegin, $sDateEnd])->select('user_id', 'username', DB::raw('sum(turnover) turnover'), DB::raw('sum(prize) prize'), DB::raw('sum(commission) commission'), DB::raw('sum(profit) profit'), DB::raw('sum(won_prj_count)/sum(prj_count) as per'))->groupBy('user_id')->orderBy('prize', 'desc')->orderBy('id', 'desc')->limit($iLimit)->get()->toArray();
    }

    /**
     * 组合 英雄榜数据, 和 某周 相比 的 上升名次
     * @author lucda
     * @date 2016-11-14
     *
     * @param $aData
     * @param $sMonday
     * @param $sSunday
     *
     * @return array
     */
    static function compileWeekHeros($aData, $sMonday, $sSunday) {
        $aUserIds = array_pluck($aData, 'user_id');
        $aUserProfits = static::whereBetween('date', [$sMonday, $sSunday])->whereIn('user_id', $aUserIds)->select('user_id', DB::raw('sum(prize) prize'))->groupBy('user_id')->orderBy('prize', 'desc')->orderBy('id', 'desc')->get()->lists('prize', 'user_id');//获取 指定某周 的 所有这些会员的 信息

        //$aData这组数据 和 指定这周的这批会员的数据$aUserProfits 上升名次 计算
        for ($i = 0; $i < sizeof($aData); $i++) {
            $aData[$i]['top'] = 0;
            if (isset($aUserProfits[$aData[$i]['user_id']])) {
                //说明这个会员 指定的这周 也有 userprofits 记录
                $iPrize = $aUserProfits[$aData[$i]['user_id']]; //这个会员 这周的 总 prize

                $aGtPrizes = static::whereBetween('date', [$sMonday, $sSunday])->select(DB::raw('sum(prize) prize'))->groupBy('user_id')->havingRaw('sum(prize) >' . $iPrize)->get()->toArray();//奖金 大于 此会员 的 人数
                $iGtCount = sizeof($aGtPrizes);

                $aUserProfitEquels = static::whereBetween('date', [$sMonday, $sSunday])->select(DB::raw('sum(prize) prize'), 'user_id')->groupBy('user_id')->havingRaw('sum(prize) =' . $iPrize)->orderBy('prize', 'desc')->orderBy('id', 'desc')->get()->toArray();
                $iEquel = 0;
                foreach ($aUserProfitEquels as $k => $aUserProfitEquel) {
                    if ($aUserProfitEquel['user_id'] == $aData[$i]['user_id']) {
                        $iEquel = $k;
                    }
                }

                $aData[$i]['top'] = $iGtCount + $iEquel - $i;
            }
        }
        return $aData;
    }

    public static function getUserProfitsByDate($sBeginDate, $sEndDate, $iUserId) {
        return static::where('user_id', '=', $iUserId)->where('date', '>=', $sBeginDate)->where('date', '<=', $sEndDate)->get();
    }


    /**
     * 获取用户的充值总金额,充值总次数,提款总次数
     *
     * @param $iUserId
     *
     * @return mixed
     */
    public static function getUserDepositAndWithdrawInfo($iUserId) {
        $sSql = 'select sum(deposit) total_deposit, sum(withdraw_times) total_withdraw_times,sum(deposit_times) total_deposit_times from user_profits where (user_id = ? )';
        $aValue = [$iUserId];
        $results = DB::select($sSql, $aValue);
        $aRet = objectToArray($results[0]);
        //增加取出空的处理
        foreach ($aRet as $key => $val) {
            if ($val == '') {
                $aRet[$key] = 0;
            }
        }

        return $aRet;
    }

    public static function getProfitData($aUserIds,$sBeginDate, $sEndDate){
        return static::whereIn('user_id',$aUserIds)->whereBetween('date',[$sBeginDate,$sEndDate])->get();
    }

    /**
     * 获取总代 所有下级指定时间的盈亏数据总和
     * @param string $sBeginDate    开始时间
     * @param string $sEndDate       结束时间
     */
    public static function & getUserProfitByTopAgentDate($sBeginDate, $sEndDate, $iParentId , $aUserIds=null, $sUsername=null) {
        $oQuery = static::select(DB::raw('sum(profit) total_profit, sum(turnover) total_turnover, sum(prize) total_prize, sum(commission) total_commission, sum(bonus) total_bonus, sum(lose_commission) total_lose_commission'))
            ->where('date', '>=', $sBeginDate)->where('date', '<=', $sEndDate);

        $oQuery = $oQuery->whereRaw(" (find_in_set(?,user_forefather_ids) or user_id= ? )", [$iParentId,$iParentId]);

        if ($aUserIds) {
            $oQuery =  $oQuery->whereIn('user_id', $aUserIds);
        }

        if($sUsername){
            $oQuery = $oQuery->where('parent_user', 'like', '%' . $sUsername . '%');
        }


//        $oQuery = $oQuery->orderBy('user_id');
//        $oQuery = $oQuery->where('is_tester',false);
        $aResult = $oQuery->first();

        return $aResult;
    }


}
