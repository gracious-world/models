<?php

/**
 * 用户盈亏表
 *
 * @author white
 */
class UserMonthProfit extends BaseModel {

    protected $table                         = 'user_month_profits';
    public static $resourceName              = 'UserMonthProfit';
    public static $amountAccuracy            = 6;
    public static $htmlOriginalNumberColumns = [
        'year',
        'month',
        'prize_group',
    ];
    public static $htmlNumberColumns         = [
        'deposit'         => 2,
        'withdrawal'      => 2,
        'turnover'        => 4,
        'prize'           => 6,
        'profit'          => 6,
        'commission'      => 6,
        'lose_commission' => 0,
    ];
    public static $columnForList             = [
        'year',
        'month',
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
        'lose_commission',
        'commission',
        'profit',
    ];
    public static $totalColumns = [
        'deposit',
        'withdrawal',
        'turnover',
        'prize',
        'bonus',
        'commission',
        'lose_commission',
        'profit',
    ];
    public static $listColumnMaps = [
        'user_type'  => 'user_type_formatted',
        'turnover'   => 'turnover_formatted',
        'prize'      => 'prize_formatted',
        'bonus'      => 'bonus_formatted',
        'commission' => 'commission_formatted',
        'profit'     => 'profit_formatted',
        'is_tester'  => 'is_tester_formatted',
    ];
    public static $viewColumnMaps   = [
        'user_type'  => 'user_type_formatted',
        'turnover'   => 'turnover_formatted',
        'prize'      => 'prize_formatted',
        'bonus'      => 'bonus_formatted',
        'commission' => 'commission_formatted',
        'profit'     => 'profit_formatted',
        'is_tester'  => 'is_tester_formatted',
    ];
    public static $weightFields     = [
        'username',
        'profit',
    ];
    public static $classGradeFields = [
        'profit',
    ];
    protected $fillable             = [
        'year',
        'month',
        'user_id',
        'is_agent',
        'is_tester',
        'prize_group',
        'user_level',
        'username',
        'parent_user_id',
        'parent_user',
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
    ];
    public static $rules            = [
        'year'           => 'required|integer|min:2014|max:2050',
        'month'          => 'required|integer|min:1|max:12',
        'user_id'        => 'required|integer',
        'is_agent '      => 'in:0,1',
        'prize_group'    => 'integer',
        'user_level'     => 'required|min:0|max:2',
        'username'       => 'required|max:16',
        'parent_user_id' => 'integer',
        'parent_user'    => 'max:16',
        'deposit'        => 'numeric|min:0',
        'withdrawal'     => 'numeric|min:0',
        'turnover'       => 'numeric',
        'prize'          => 'numeric',
        'bonus'          => 'numeric',
        'profit'         => 'numeric',
        'commission'     => 'numeric',
    ];
    public $orderColumns            = [
        'year'     => 'desc',
        'month'    => 'desc',
        'turnover' => 'desc',
        'username' => 'asc',
    ];
    public static $mainParamColumn  = 'user_id';
    public static $titleColumn      = 'username';
    public static $aUserTypes       = ['-1' => 'Top Agent', '0' => 'Agent'];

    // 按钮指向的链接，查询列名和实际参数来源的列名的映射
    // public static $aButtonParamMap = ['parent_user_id' => 'user_id'];

    /**
     * 返回UserProfit对象
     *
     * @param string $sDate
     * @param string $iUserId
     * @return UserProfit
     */
    public static function getProfitObject($iYear, $iMonth, $iUserId) {
        $aAttributes = [
            'user_id' => $iUserId,
            'month'   => $iMonth,
            'year'    => $iYear,
        ];
        $obj         = static::firstOrCreate($aAttributes);
        return $obj;

//        $obj = static::where('user_id', '=', $iUserId)->where('month','=',$iMonth)->where('year', '=', $iYear)->first();
//        $oUser = User::find($iUserId);
//        if (!is_object($obj)) {
////            $oUser = User::find($iUserId);
////            pr($oUser->toArray());
////            pr($oUser->toArray());
//            $data = [
//                'user_id' => $oUser->id,
//                'is_agent' => $oUser->is_agent,
//                'is_tester' => $oUser->is_tester,
//                'prize_group' => $oUser->prize_group,
//                'user_level' => $oUser->user_level,
//                'username' => $oUser->username,
//                'parent_user_id' => $oUser->parent_id,
//                'parent_user' => $oUser->parent,
//                'year' => $iYear,
//                'month' => $iMonth,
//            ];
//            $obj = new static($data);
//        } else {
//            $obj->user_level = $oUser->user_level;
//            $obj->prize_group = $oUser->prize_group;
//        }
//        pr($obj->toArray());
//        exit;
//        return $obj;
    }

    protected function beforeValidate() {
        if (!$this->username) {
            $oUser                     = User::find($this->user_id);
//            pr($oUser->toArray());
            $this->is_agent            = $oUser->is_agent;
            $this->is_tester           = $oUser->is_tester;
            $this->prize_group         = $oUser->prize_group;
            $this->user_level          = $oUser->user_level;
            $this->username            = $oUser->username;
            $this->parent_user_id      = $oUser->parent_id;
            $this->parent_user         = $oUser->parent;
            $this->user_forefather_ids = $oUser->forefather_ids;
            $this->user_forefathers    = $oUser->forefathers;
        }
        return parent::beforeValidate();
    }

    /**
     * 返回包含直接销售额，直接盈亏记录和团队销售额的数组
     *
     * @param string $sDate     只有年和月,格式：2014-01-01
     * @param string $iUserId   用户id
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
        $aUserProfits = $oQuery->get(['team_turnover', 'turnover', 'profit']);
        $data         = [];
        $i            = 0;
        foreach ($aUserProfits as $oUserProfit) {
            $data[$i]['team_turnover'] = $oUserProfit->team_turnover;
            $data[$i]['turnover']      = $oUserProfit->turnover;
            $data[$i]['profit']        = $oUserProfit->profit;
            $i++;
        }
        return $data;
    }

    /**
     * 获取指定用户的销售总额
     * @param int $iUserId  用户id
     * @return float        销售总额
     */
    public static function getUserTotalTurnover($sBeginDate, $sEndDate, $iUserId) {
        $aUserProfits = static::getUserProfitByDate($sBeginDate, $sEndDate, $iUserId);
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

    public function countProfit() {
        return $this->prize + $this->bonus + $this->commission - $this->turnover;
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
        $sFunction = 'add' . ucfirst($sType);
        $oProfit   = static::getUserProfitObject($sDate, $oUser->id);
//            pr($oUserProfit->validationErrors->toArray());
        $bSucc     = $oProfit->$sFunction($fAmount);
//        pr($bSucc);
        return $bSucc;
    }

    public static function clearProfitData($sDate, $oUser) {
        $oProfit = static::getUserProfitObject($sDate, $oUser->id);
        if ($oProfit->id) {
            $oProfit->deposit    = $oProfit->withdrawal = $oProfit->turnover   = $oProfit->prize      = $oProfit->bonus - $oProfit->commission = $oProfit->profit     = 0;
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

    protected function getProfitFormattedAttribute() {
        return $this->getFormattedNumberForHtml('profit');
    }

    protected function getIsTesterFormattedAttribute() {
        return is_null($this->attributes['is_tester']) ? '' : __('_basic.' . strtolower(Config::get('var.boolean')[$this->attributes['is_tester']]));
    }

    /**
     * 获取用户当月投注，返利
     * @param $iUserId
     */

    static function getUserCurrentMonthProfit($iUserId)
    {
        $now=Carbon::now();
        $current_month=$now->month;
        $current_year=$now->year;

        return  UserMonthProfit::where("user_id", '=', $iUserId)
                            ->where("year",'=',$current_year)
                            ->where("month", "=", $current_month)
                            ->first();
    }

    /**
     * 获取下级本月获利信息
     * @param array $children_ids
     * @param $current_month_start
     * @return mixed
     */
    
    static function getChildrenCurrentMonthProfits($children_ids=[],$current_month_start){
        return UserMonthProfit::whereIn("user_id", $children_ids)
            ->where("created_at", ">", $current_month_start)
            ->orderby("user_id", "asc")
            ->get();
    }

    /**
     * 队长分红用于获取有效投注用户
     * (统计团队中满足当月充值不低月dividend_valid_deposit元
     * 投注不低于dividend_valid_turnover元的成员个数)
     *
     * @param $iYear
     * @param $iMonth
     * @param $iParentId
     *
     * @return mixed
     */
    public static function getValidUserNum($iYear, $iMonth, $iParentId){
        $iNum = UserMonthProfit::where("deposit", '>=', SysConfig::readValue("dividend_valid_deposit"))
            ->where("turnover", ">=", SysConfig::readValue("dividend_valid_turnover"))
            ->where("year", '=', $iYear)
            ->where("month", "=", $iMonth)
            ->where("parent_user_id", "=", $iParentId)
            ->count();
        return $iNum;
    }

    /**
     * 组合 英雄榜数据, 和 某月 相比 的 上升名次
     * @author lucda
     * @date 2016-11-14
     * @param $aData
     * @param $iYear
     * @param $iMonth
     * @return array
     */
    static function compileYearMonthHeros($aData, $iYear, $iMonth) {

        $aUserIds = array_pluck($aData, 'user_id');
        $aConditions['year'] = ['=', $iYear];
        $aConditions['month'] = ['=', $iMonth];
        $aConditions['user_id'] = ['in', $aUserIds];
        $aUserMonthProfits = static::doWhere($aConditions)->select('user_id', 'prize')->get()->lists('prize','user_id');//获取 指定某天 的 所有这些会员的 信息

        //$aData这组数据 和 指定某天的这批会员的数据$aUserProfits 上升名次 计算
        for ($i = 0; $i < sizeof($aData); $i++) {
            $aData[$i]['top'] = 0;
            if ( isset($aUserMonthProfits[$aData[$i]['user_id']]) ) {
                //说明这个会员 上个月  有 user_month_profits 记录
                $iPrize = $aUserMonthProfits[$aData[$i]['user_id']];
                $iGtCount = static::doWhere(['year' => ['=', $iYear], 'month' => ['=', $iMonth], 'prize' => ['>', $iPrize]])->select(DB::raw('count(id) as gtCount'))->lists('gtCount')[0];//奖金 大于 此会员 的 人数

                $aUserMonthProfitEquels = static::doWhere(['year' => ['=', $iYear], 'month' => ['=', $iMonth], 'prize' => ['=', $iPrize]])->select('user_id')->orderBy('prize', 'desc')->orderBy('id', 'desc')->get()->toArray();
                $iEquel = 0;
                foreach ($aUserMonthProfitEquels as $k => $aUserMonthProfitEquel) {
                    if ($aUserMonthProfitEquel['user_id'] == $aData[$i]['user_id']) {
                        $iEquel = $k;
                    }
                }
                $aData[$i]['top'] = $iGtCount + $iEquel - $i;
            }
        }
        return $aData;
    }
    
    /**
     * 获取某年,月 的 英雄榜数据
     * @author lucda
     * @date 2016-11-14
     * @param $iYear
     * @param $iMonth
     * @param int $iLimit
     * @return array
     */
    static function getYearMonthHeros($iYear, $iMonth, $iLimit = 10) {
        return static::doWhere(['year' => ['=', $iYear], 'month' => ['=', $iMonth]])->select('user_id', 'username', 'turnover', 'prize', 'commission', 'profit', DB::raw('won_prj_count/prj_count as per'))->orderBy('prize', 'desc')->orderBy('id', 'desc')->limit($iLimit)->get()->toArray();
    }



}
