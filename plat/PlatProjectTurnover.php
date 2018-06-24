<?php

/**
 * 平台注单流水表
 *
 * @author Winter
 */
class PlatProjectTurnover extends BaseModel
{

    protected $table = 'plat_project_turnovers';
    public static $resourceName = 'PlatProjectTurnover';

    //TODO
    //改变常量位置 to Model PercentWay
    const SINGLE_PERCENT_WAY = 1; //Mapping to Model PercentWay SINGLE
    const MULTI_PERCENT_WAY = 2;  //Mapping to Model PercentWay MULTI

    //对应percent_ways
    static $wayIds = [
        0 => self::MULTI_PERCENT_WAY,
        1 => self::SINGLE_PERCENT_WAY
    ];

    const IS_SINGLE_FALSE = 0;
    const IS_SINGLE_TRUE = 1;

    public static $columnForList = [
        'id',    // reserve
//        'plat_id',
//        'plat_identity',
//        'plat_name',
        'plat_project_id',    // reserve
//        'plat_project_sn',
//        'user_id',
        'username',    // reserve
//        'is_agent',
        'is_tester',    // reserve
//        'user_level',
//        'user_forefather_ids',
//        'user_forefathers',
//        'parent_user_id',
        'parent_user',      // reserve
//        'series_id',
//        'lottery_id',
        'fund_used',    // reserve
//        'is_single',
//        'turnover',
        'prize',    // reserve
//        'profit',
        'is_won',    // reserve
        'bought_at',    // reserve
        'counted_at',    // reserve
    ];

    protected $fillable = [
        'plat_id',
        'plat_identity',
        'plat_name',
        'plat_project_id',
        'plat_project_sn',
        'user_id',
        'username',
        'is_agent',
        'is_tester',
        'user_level',
        'user_forefather_ids',
        'user_forefathers',
        'parent_user_id',
        'parent_user',
        'series_id',
        'lottery_id',
        'fund_used',
        'is_single',
        'turnover',
        'prize',
        'profit',
        'is_won',
        'bought_at',
        'counted_at',
    ];

    public $orderColumns = [
        'id' => 'desc',
    ];

    public static $totalColumns = [
        'turnover',
        'prize',
        'profit',
    ];

    public static $rules = [
        'plat_id' => 'required|integer',
        'plat_identity' => 'required|max:50',
        'plat_name' => 'required|max:50',
        'plat_project_id' => 'required|integer',
        'plat_project_sn' => 'required|max:50',
        'user_id' => 'required|integer',
        'username' => 'max:16',
        'is_agent' => 'required|integer',
        'is_tester' => 'required|integer|in:0,1',
        'user_level' => 'required|integer',
        'user_forefather_ids' => 'max:100',
        'user_forefathers' => 'max:1024',
        'parent_user_id' => 'integer',
        'parent_user' => 'max:16',
        'series_id' => 'required|integer',
        'lottery_id' => 'required|integer',
        'fund_used' => 'required|integer',
        'is_single' => 'required|integer',
        'turnover' => 'required',
    ];


    public static $listColumnMaps = [
        'is_agent' => 'friendly_is_agent',
        'is_single' => 'friendly_is_single',
        'is_won' => 'friendly_is_won',
        'fund_used' => 'friendly_fund_used',
    ];

    public static $viewColumnMaps = [
        'is_agent' => 'friendly_is_agent',
        'is_single' => 'friendly_is_single',
        'is_won' => 'friendly_is_won',
        'fund_used' => 'friendly_fund_used',
    ];

    protected function getFriendlyIsAgentAttribute()
    {
        $sTmp = '';
        if ($this->is_agent == '1') {
            $sTmp = '代理';
        }
        else {
            $sTmp = '非代理';
        }
        return $sTmp;
    }

    protected function getFriendlyFundUsedAttribute()
    {
        $sTmp = '';
        if ($this->fund_used == '1') {
            $sTmp = '现金';
        }
        else {
            $sTmp = '非现金';
        }
        return $sTmp;
    }


    protected function getFriendlyIsSingleAttribute()
    {
        $sTmp = '';
        if ($this->is_single == '1') {
            $sTmp = '单注';
        }
        else {
            $sTmp = '非单注';
        }
        return $sTmp;
    }

    protected function getFriendlyIsWonAttribute()
    {
        $sTmp = '';
        if ($this->is_won == '1') {
            $sTmp = '中奖';
        }
        else {
            $sTmp = '非中奖';
        }
        return $sTmp;
    }


    static $aValidIsTester = [
        0 => '否',
        1 => '是',
    ];

    private function getWayId()
    {
        return self::$wayIds[$this->is_single];
    }

    public static function setToFundUsed($id)
    {
        return static::where('id', '=', $id)->where('fund_used', '=', 0)->update(['fund_used' => 1]) > 0;
    }


    /**
     * 获取某一天第三方(竞彩)的投注信息
     *
     * @param string $sDate    时间 eg:2016-10-26
     * @param array $aGroupBy  分组维度
     * @param boolean $bIsTest 是否是测试用户
     *
     * @return array
     */
    public static function & getPlatLotteryData($sDate, $aGroupBy = [], $bIsTest = false)
    {
        $sLotteryIds = Lottery::where('plat_id', '>', 0)->lists('id');

        $sDateFrom = $sDate . ' 00:00:00';
        $sDateTo = $sDate . ' 23:59:59';

        if (empty($aGroupBy)) {
            $aGroupBy = ['series_id', 'user_id'];
        }

        $sSqlRaw = implode(',', $aGroupBy) . ', count(*) prj_count, sum(turnover) turnover, sum(prize) prize';

        $aResults = static::whereBetween('created_at', [$sDateFrom, $sDateTo])
            ->select(DB::raw($sSqlRaw))
            ->whereIn('lottery_id', $sLotteryIds);

        if ($bIsTest) {
            $aResults = $aResults->where('is_tester', '=', 1);
        }

        foreach ($aGroupBy as $item) {
            $aResults = $aResults->groupBy($item);
        }

        $aResults = $aResults->get();

        $aData = [];
        foreach ($aResults as $obj) {
            if (isset($obj->lottery_id)) {
                $aData[$obj->lottery_id] = [
                    'lottery_id' => $obj->lottery_id,
                    'turnover' => $obj->turnover,
                    'prize' => $obj->prize,
                    'prj_count' => $obj->prj_count,
                ];
            } else {
                $aData[$obj->user_id] = [
                    'turnover' => $obj->turnover,
                    'prize' => $obj->prize,
                    'prj_count' => $obj->prj_count,
                ];
            }

        }
        return $aData;
    }

    /**
     * save 储存流水返点
     *
     * @return boolean
     */
    public function saveCommissions()
    {
        if (!$this->id) {
            return false;
        }
        $aCommissions = &$this->compileCommissions();

        $aIds = [];
        if ($aCommissions) {
            foreach ($aCommissions as $data) {
                if ($data['amount'] <= 0) {
                    continue;
                }
                $oPrjCommission = new Commission($data);
                if (!$bSucc = $oPrjCommission->save()) {
                    return false;
                }
                $aIds[] = $oPrjCommission->id;
            }
        }
        return $aIds;
    }

    protected function & compileCommissions() {
        $aCommissions    = [];
        if ($aSelfCommission = $this->compileSelfCommission()) {
            $aCommissions[] = $aSelfCommission;
        }
//        die();
//        pr($aCommissions);
//        exit;
        // pr($aCommissions);exit;
        if (!$this->user_forefather_ids) {
            return $aCommissions;
        }
        $aUserIds     = explode(',', $this->user_forefather_ids);
        array_push($aUserIds, $this->user_id);
        $aPercentSets = UserPercentSet::getPercentSetOfUsers($aUserIds, $this->lottery_id,$this->getWayId());
        $iForeCount = count($aUserIds);
        // pr($aUserIds);
        foreach ($aUserIds as $i => $iUserId) {
            if ($i == ($iForeCount - 1)) {
                break;
            }
            $iUpAgentPercent  = $aPercentSets[$iUserId];
            $iDownUserPercent = $aPercentSets[$aUserIds[$i + 1]];
            if($iUpAgentPercent == 0){
                break;
            }
            // pr($aPercentSets);exit;
            // echo $iUpAgentPercent.' '.$iDownUserPercent;exit;
            if ($iUpAgentPercent <= $iDownUserPercent) {
                continue;
            }
            $oFore          = User::find($iUserId);
            $aCommissions[] = $this->compileSingleCommission($oFore, $iUpAgentPercent - $iDownUserPercent);
            // pr($aCommissions);
        }
        // pr($aCommissions);
        // exit;
        return $aCommissions;
    }

    /**
     * 当前用户佣金
     * @return array|bool
     */
    private function compileSelfCommission() {
        $oUser             = User::find($this->user_id);
        //获取用户返点数据
        $iUserPercentValue  = UserPercentSet::getPercentValueByUser($this->user_id,$this->lottery_id,$this->getWayId(),true);
        if(!$iUserPercentValue || $iUserPercentValue == 0){
            return false;
        }
        //返点
        $fAmount = $iUserPercentValue * $this->turnover;
        $aCommissionData = [
            'user_id'             => $oUser->id,
            'account_id'          => $oUser->account_id,
            'username'            => $oUser->username,
            'is_tester'           => $oUser->is_tester,
            'user_forefather_ids' => $oUser->forefather_ids,
            'base_amount'         => $this->turnover,
            'amount'              => $fAmount,
        ];
        return array_merge($this->compileBasicData(), $aCommissionData);
    }

    /**
     * 单用户佣金
     * @param $oUser
     * @param $fDiffPrizeGroup
     * @return array
     */
    private function compileSingleCommission($oUser, $fDiffPercent) {
        $aCommissionData = [
            'user_id'             => $oUser->id,
            'account_id'          => $oUser->account_id,
            'username'            => $oUser->username,
            'is_tester'           => $oUser->is_tester,
            'user_forefather_ids' => $oUser->forefather_ids,
            'base_amount' => $this->turnover,
            'amount' => ($fDiffPercent * $this->turnover),
        ];
        // pr($aCommissionData);exit;
        return array_merge($this->compileBasicData(), $aCommissionData);
    }

    /**
     *计算佣金(根据游戏类别和游戏玩法计算自己和直接上级佣金)
     *该佣金计算方法依据用户等级表，废弃
     * @return array
     */
//    protected function & compileCommissionsOld()
//    {
//        $aCommissions = [];
//        $oLottery = Lottery::find($this->lottery_id);
//        $oUser = User::find($this->user_id);
//
//        $oRebateSetting = RebateSetting::getRebateSettingByUserIdGameTypeId($this->user_id, $oLottery->game_type, $this->getWayId());//从 grade_game_type_sets 表里面 获取 此会员及其上线对应的 返点比率
//        if (!$oRebateSetting) {
//            return $aCommissions;
//        }
//
//        if (($iRateSelf = $oRebateSetting->user_rebate) > 0) {
//            $aCommissionDataSelf = [
//                'user_id' => $oUser->id,
//                'account_id' => $oUser->account_id,
//                'username' => $oUser->username,
//                'is_tester' => $oUser->is_tester,
//                'user_forefather_ids' => $oUser->forefather_ids,
//                'base_amount' => $this->turnover,
//                'amount' => ($iRateSelf * $this->turnover),
//                'user_parent_id' => $oUser->parent_id
//            ];
//            $aCommissions[] = array_merge($this->compileBasicData(), $aCommissionDataSelf);
//        }
//
//        if (!$oUser->parent_id) {
//            return $aCommissions;
//        }
//
//        $oParent = User::find($oUser->parent_id);
//        if (($iRateParent = $oRebateSetting->parent_rebate) > 0) {
//            $aCommissionDataParent = [
//                'user_id' => $oParent->id,
//                'account_id' => $oParent->account_id,
//                'username' => $oParent->username,
//                'is_tester' => $oParent->is_tester,
//                'user_forefather_ids' => $oParent->forefather_ids ? $oParent->forefather_ids : "",
//                'base_amount' => $this->turnover,
//                'amount' => $iRateParent * $this->turnover,
//                'user_parent_id' => $oParent->parent_id
//            ];
//            $aCommissions[] = array_merge($this->compileBasicData(), $aCommissionDataParent);
//        }
//
//        return $aCommissions;
//    }
    
    /**
     * 佣金基础数据
     *
     * @return array
     */
    private function compileBasicData(){
        $iGameType = Lottery::getGameTypeByLotteryId($this->lottery_id);
        return [
            'project_id' => $this->plat_project_id,
            'project_no' => $this->plat_project_sn,
            'coefficient' => 1,
            'game_type' => $iGameType,
            'lottery_id' => $this->lottery_id,
            'multiple' => $this->multiple,
            'way_id' => $this->getWayId(),
            'bought_at' => $this->bought_at
        ];
    }


    /**
     * 获取 某个会员,某段时间 [竞彩 串关] 中奖的总金额 活动6
     *
     * @author  lucda
     * @date    2016-12-21
     * @param $iUserId
     * @param $sDateBegin
     * @param $sDateEnd
     * @param int $iPlatId
     * @param int $iIsSingle
     * @return float
     */
    static function sumPlatProjectUserPrize($iUserId, $sDateBegin, $sDateEnd, $iPlatId, $iIsSingle)
    {
        $aConditions['user_id'] = ['=', $iUserId];
        $aConditions['plat_id'] = ['=', $iPlatId];
        $aConditions['is_single'] = ['=', $iIsSingle];
        $aConditions['prize'] = ['>', 0];
        $aConditions['is_won'] = ['=', 1];
        $oSumPrize = PlatProjectTurnover::doWhere($aConditions)->whereBetween('counted_at', [$sDateBegin, $sDateEnd])->select('user_id', 'username', DB::raw('sum(prize) as sumPrize'))->first();
        return $oSumPrize->sumPrize;
    }

    /**
     * 获取 某段时间 竞彩串关 ,各个会员 的 中奖的总金额
     * for 活动6
     *
     * @author  Rex
     * @date    2017-01-03
     * @param string $sDateBegin
     * @param string $sDateEnd
     * @param int $iPlatId
     * @param int $iIsSingle
     * @return object  $oSumPrizes
     */
    static function sumPlatProjectPrizeOfUsers($sDateBegin, $sDateEnd, $iPlatId, $iIsSingle, $iLotteryId = 0)
    {
        $aConditions['plat_id'] = ['=', $iPlatId];
        $aConditions['is_single'] = ['=', $iIsSingle];
        $aConditions['prize'] = ['>', 0];
        $aConditions['is_won'] = ['=', 1];
        !$iLotteryId or $aConditions['lottery_id'] = ['=', $iLotteryId];
        $oSumPrizes = PlatProjectTurnover::doWhere($aConditions)->
        whereBetween('counted_at', [$sDateBegin, $sDateEnd])->
        select('user_id', 'username', DB::raw('sum(prize) as sumPrize'))->
        groupBy('user_id')->
        get();


        return $oSumPrizes;
    }

    /**
     * 按照时间段获取数据
     *
     * @author      lucky
     * @date        2017-03-07
     * @param string $sBeginDate
     * @param string $sEndDate
     * @return object
     */
    public static function getTurnoversByDate($sBeginDate, $sEndDate)
    {
        return static::select(DB::raw("user_id,sum(turnover) as turnover,lottery_id"))
            ->whereBetween("created_at", [$sBeginDate, $sEndDate])
            ->groupBy("user_id")
            ->groupBy("lottery_id")
            ->get();
    }

    /**
     * 按照时间段获取数据
     *
     * @author      Rex
     * @date        2017-04-06
     * @param string $sBeginDate
     * @param string $sEndDate
     * @return object
     */
    public static function getWonPrizeByDate($aPlatIds, $sBeginDate, $sEndDate)
    {
        return static::whereBetween("created_at", [$sBeginDate, $sEndDate])
            ->where('prize', '>', 0)
            ->where('is_single', PlatProjectTurnover::IS_SINGLE_FALSE)
            ->whereIn('plat_id', $aPlatIds)
            ->get();
    }

}
