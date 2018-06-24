<?php

/**
 * 用户盈亏明细表模型
 *
 * @author Garin
 * @date 2016-11-23
 */
class UserGameTypeProfit extends BaseModel {

    protected $table = 'user_gt_profits';
    public static $resourceName = 'UserGameTypeProfit';

    protected static $cacheUseParentClass = false;

    protected static $cacheLevel = self::CACHE_LEVEL_FIRST;

    protected static $cacheMinutes = 0;

    public static $htmlOriginalNumberColumns = [
        'prize_group',
        'user_id'
    ];
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
        'id',
        'date',
        'username',
        'game_type',
        'user_type',
        'is_tester',
        'prize_group',
        'user_level',
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
        'lose_commission',
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
    protected $fillable = [
        'id',
        'date',
        'user_id',
        'game_type',
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
        'transfer_in',
        'transfer_out',
        'withdrawal',
        'turnover',
        'prize',
        'bonus',
        'commission',
        'lose_commission',
        'profit',
        'prj_count',
        'won_prj_count',
        'created_at',
        'updated_at',
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

    /**
     * 返回UserProfit对象
     *
     * @param string  $sDate
     * @param integer $iUserId
     * @param integer $iGameType
     *
     * @return UserProfit
     */
    public static function getUserGameTypeProfitObject($sDate, $iUserId, $iGameType) {
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
            $obj = self::getUserGameTypeProfitObject($sDate, $iUserId, $iGameType);
        } else {
            $obj->user_level = $oUser->user_level;
            $obj->prize_group = $oUser->prize_group;
        }
        return $obj;
    }

    /**
     * 获取用户盈亏明细
     *
     * @param array $aDate 时间数组 格式：['date'=>'2014-01-01', 'end_date'=>'2014-01-01']
     * @param int   $iUserId
     *
     * @return array
     */
    public static function & getUserProfitDetailsByDate($aDate = [], $iUserId = 0) {
        $oQuery = static::select('*');
        if (!empty($aDate)) {
            if (isset($aDate['date']) && isset($aDate['end_date'])){
                $oQuery->whereBetween('date', [$aDate['date'], $aDate['end_date']]);
            }else{
                $oQuery->where('date', '=', $aDate['date']);
            }
        }
        if($iUserId) {
            $oQuery->where('user_id', '=', $iUserId);
        }
        $oData = $oQuery->get();

        return $oData;
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
        $fAmount > 0 ? $this->prj_count++ : $this->prj_count--;
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
        $fAmount > 0 ? $this->won_prj_count++ : $this->won_prj_count--;
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

    public function countProfit() {
        return $this->prize + $this->bonus + $this->commission + $this->lose_commission - $this->turnover;
    }

    public static function getTotalAmount($sColumn, $sBeginDate, $sEndDate, $iUserId) {
        return static::whereBetween('date', [$sBeginDate, $sEndDate])
            ->where('user_id', '=', $iUserId)
            ->sum($sColumn);
    }

    public static function updateProfitData($sType, $sDate, $iGameType ,$oUser, $fAmount) {
        $sFunction = 'add' . ucfirst(String::camel($sType));
        $oProfit = self::getUserGameTypeProfitObject($sDate, $oUser->id, $iGameType);
        if (!is_object($oProfit)) {
            return false;
        }
        $bSucc = $oProfit->$sFunction($fAmount);
        return $bSucc;
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
    /**
     * 获取某一天的 英雄榜数据
     * @author lucda
     * @date 2016-11-14
     * @param $sDay
     * @param int $iLimit
     * @return array
     */
    static function getDayHeros($sDay, $iGameType, $iLimit = 10) {
        return static::doWhere(['date' => ['=', $sDay]])
            ->where('game_type', '=', $iGameType)
            ->select('user_id', 'username', 'turnover', 'prize', 'commission', 'profit', DB::raw('won_prj_count/prj_count as per'))
            ->orderBy('prize', 'desc')
            ->orderBy('id', 'desc')
            ->limit($iLimit)
            ->get()
            ->toArray();
    }

    /**
     * 组合 英雄榜数据, 和 某天 相比 的 上升名次
     * @author lucda
     * @date 2016-11-14
     * @param $aData
     * @param $sDay
     * @return array
     */
    static function compileDayHeros($aData, $sDay, $iGameType=1) {
        $aUserIds = array_pluck($aData, 'user_id');
        $aConditions['date'] = ['=', $sDay];
        $aConditions['user_id'] = ['in', $aUserIds];
        $aUserProfits = static::doWhere($aConditions)
            ->where('game_type', '=', $iGameType)
            ->select('user_id', 'prize')
            ->get()
            ->lists('prize','user_id');//获取 指定某天 的 所有这些会员的 信息

        //$aData这组数据 和 指定某天的这批会员的数据$aUserProfits 上升名次 计算
        for ($i = 0; $i < sizeof($aData); $i++) {
            $aData[$i]['top'] = 0;
            if ( isset($aUserProfits[$aData[$i]['user_id']]) ) {
                //说明这个会员 昨天 也有 userprofits 记录
                $iPrize = $aUserProfits[$aData[$i]['user_id']];
                $iGtCount = static::doWhere(['date' => ['=', $sDay], 'prize' => ['>', $iPrize]])
                    ->where('game_type', '=', $iGameType)
                    ->select(DB::raw('count(id) as gtCount'))
                    ->lists('gtCount')[0];//奖金 大于 此会员 的 人数

                $aUserProfitEquels = static::doWhere(['date' => ['=', $sDay], 'prize' => ['=', $iPrize]])
                    ->where('game_type', '=', $iGameType)
                    ->select('user_id')
                    ->orderBy('prize', 'desc')
                    ->orderBy('id', 'desc')
                    ->get()
                    ->toArray();
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
     * @param $sDateBegin
     * @param $sDateEnd
     * @param int $iLimit
     * @return array
     */
    static function getDaysHeros($sDateBegin, $sDateEnd, $iGameType, $iLimit = 10) {
        return static::whereBetween('date', [$sDateBegin, $sDateEnd])
            ->where('game_type',$iGameType)
            ->select('user_id', 'username', DB::raw('sum(turnover) turnover'), DB::raw('sum(prize) prize'), DB::raw('sum(commission) commission'), DB::raw('sum(profit) profit'), DB::raw('sum(won_prj_count)/sum(prj_count) as per'))
            ->groupBy('user_id')
            ->orderBy('prize', 'desc')
            ->orderBy('id', 'desc')
            ->limit($iLimit)
            ->get()
            ->toArray();
    }

    /**
     * 组合 英雄榜数据, 和 某周 相比 的 上升名次
     * @author lucda
     * @date 2016-11-14
     * @param $aData
     * @param $sMonday
     * @param $sSunday
     * @return array
     */
    static function compileWeekHeros($aData, $sMonday, $sSunday, $iGameType) {
        $aUserIds = array_pluck($aData, 'user_id');
        $aUserProfits = static::whereBetween('date', [$sMonday, $sSunday])
            ->where('game_type','=',$iGameType)
            ->whereIn('user_id', $aUserIds)
            ->select('user_id', DB::raw('sum(prize) prize'))->groupBy('user_id')->orderBy('prize', 'desc')
            ->orderBy('id', 'desc')
            ->get()
            ->lists('prize','user_id');//获取 指定某周 的 所有这些会员的 信息

        //$aData这组数据 和 指定这周的这批会员的数据$aUserProfits 上升名次 计算
        for ($i = 0; $i < sizeof($aData); $i++) {
            $aData[$i]['top'] = 0;
            if ( isset($aUserProfits[$aData[$i]['user_id']]) ) {
                //说明这个会员 指定的这周 也有 userprofits 记录
                $iPrize = $aUserProfits[$aData[$i]['user_id']]; //这个会员 这周的 总 prize

                $aGtPrizes = static::whereBetween('date', [$sMonday, $sSunday])
                    ->where('game_type','=', $iGameType)
                    ->select(DB::raw('sum(prize) prize'))
                    ->groupBy('user_id')
                    ->havingRaw('sum(prize) >' . $iPrize)
                    ->get()
                    ->toArray();//奖金 大于 此会员 的 人数
                $iGtCount = sizeof($aGtPrizes);

                $aUserProfitEquels = static::whereBetween('date', [$sMonday, $sSunday])
                    ->where('game_type','=', $iGameType)
                    ->select(DB::raw('sum(prize) prize'),'user_id')
                    ->groupBy('user_id')
                    ->havingRaw('sum(prize) =' . $iPrize)
                    ->orderBy('prize', 'desc')
                    ->orderBy('id', 'desc')
                    ->get()
                    ->toArray();
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
     * 代理盈亏总计
     * @param string $sBeginDate  开始日期
     * @param string $sEndDate    结束日期
     * @param string $iGameType    游戏类型
     * @param int $iUserId         用户id
     * @return array
     */
    public static function getAgentSumInfo($sBeginDate, $sEndDate, $iGameType, $iUserId, $sUserName = '') {
        $sSql     = 'select sum(deposit) total_deposit, sum(withdrawal) total_withdrawal,sum(turnover) total_turnover,sum(commission) total_commission, sum(profit) total_profit,sum(prize) total_prize, sum(lose_commission) total_lose_commission, sum(bonus) total_bonus from user_gt_profits where (parent_user_id = ? ';
        $aValue   = [$iUserId];
        !$sUserName ? $sSql     .= " or user_id=?)" : $sSql     .= ")";
        $sUserName or $aValue[] = $iUserId;
        !$sBeginDate or $sSql     .= " and date>=?";
        !$sBeginDate or $aValue[] = $sBeginDate;
        !$sEndDate or $sSql     .= " and date<=?";
        !$sEndDate or $aValue[] = $sEndDate;
        if ($sUserName) {
            $sSql .= ' and username = ?';
            array_push($aValue, $sUserName);
        }
        if ($iGameType) {
            $sSql .= ' and game_type = ?';
            array_push($aValue, $iGameType);
        }
        $results = DB::select($sSql, $aValue);
        return objectToArray($results[0]);
    }
}
