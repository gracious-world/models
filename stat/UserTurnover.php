<?php

/**
 * 用户奖期销售额表
 *
 * @author Winter
 */
class UserTurnover extends BaseModel {

    protected $table = 'user_turnovers';
    public static $resourceName = 'UserTurnover';
    public static $amountAccuracy    = 4;
    public static $htmlNumberColumns = [
        'turnover' => 4,
    ];
    public static $columnForList = [
        'lottery_id',
        'issue',
        'username',
        'turnover',
    ];

    public static $listColumnMaps = [
        'turnover' => 'turnover_formatted',
    ];

    protected $fillable = [
        'lottery_id',
        'issue',
        'user_id',
        'account_id',
        'username',
        'turnover',
        'parent_user_id',
        'parent_user',
    ];
    public static $rules = [
        'user_id' => 'required|integer',
        'account_id' => 'required|integer',
        'username' => 'required|max:16',
        'parent_user_id' => 'integer',
        'parent_user' => 'max:16',
        'turnover' => 'numeric',
    ];

    public $orderColumns = [
        'lottery_id' => 'asc',
        'issue' => 'desc',
        'username' => 'asc'
    ];

    public static $mainParamColumn = 'user_id';
    public static $titleColumn = 'username';

    /**
     * 返回UserProfit对象
     *
     * @param string $sDate
     * @param string $iUserId
     * @return UserProfit
     */
    public static function getUserTurnverObject($iLotteryId, $sIssue, $iUserId) {
        $aConditions = [
            'lottery_id' => $iLotteryId,
            'issue' => $sIssue,
            'user_id' => $iUserId,
        ];
        return static::firstOrCreate($aConditions);
    }

    protected function beforeValidate() {
        if (!$this->username){
            $oUser = User::find($this->user_id);
            $this->account_id = $oUser->account_id;
            $this->username = $oUser->username;
            $this->parent_user_id = $oUser->parent_id;
            $this->parent_user = $oUser->parent;
        }
        return parent::beforeValidate();
    }

    /**
     * 累加销售额
     * @param float $fAmount
     * @return boolean
     */
    public function addTurnover($fAmount) {
        $this->turnover += $fAmount;
//        pr($this->attributes);
        return $this->save();
    }

    public static function updateTurnoverData($iLotteryId, $sIssue, $iUserId, $fAmount) {
        $oTurnover = static::getUserTurnverObject($iLotteryId,$sIssue,$iUserId);
//        pr($oTurnover->getAttributes());
        return $oTurnover->addTurnover($fAmount);
    }

    // protected function getUserTypeFormattedAttribute() {
    //     // return static::$aUserTypes[($this->parent_user_id != null ? 'not_null' : 'null')];
    //     return __('_userprofit.' . strtolower(static::$aUserTypes[intval($this->parent_user_id != null) - 1]));
    // }

    protected function getTurnoverFormattedAttribute() {
        return $this->getFormattedNumberForHtml('turnover');
    }

    /**
     * 返回指定期的用户销售额数组
     * @param int $iLotteryId
     * @param string $sIssue
     * @param bool $bUsed
     * @return array
     */
    public static function getIssueUserTurnOvers($iLotteryId, $sIssue, $bUsed = false){
        $iUsed = $bUsed ? 1 : 0;
        return static::where('lottery_id','=',$iLotteryId)
                ->where('issue','=',$sIssue)
                ->where('turnover','>',0)
                ->where('used','=',$iUsed)
                ->orderBy('user_id','asc')
                ->get(['id','user_id','account_id','turnover'])->toArray();
    }

    public static function & getIssueUserTurnOversForFund($iLotteryId, $sIssue, $bUsed = false){
        $iUsed = $bUsed ? 1 : 0;
        $data = static::where('lottery_id','=',$iLotteryId)
            ->where('issue','=',$sIssue)
            ->where('turnover','>',0)
            ->where('fund_used','=',$iUsed)
            ->orderBy('user_id','asc')
            ->get(['id','user_id','account_id','turnover','updated_at'])->toArray();
        return $data;
    }

    /**
     * 获取user_turnovers 中指定期的没有被使用的数据
     *
     * @param $iLotteryId
     * @param $sIssue
     * @param bool $bUsed
     * @return mixed
     */
    public static function getIssueUserTurnOversForPoint($iLotteryId, $sIssue = '', $bUsed = false) {
        $iUsed = $bUsed ? 1 : 0;
        $where = [
            'lottery_id' => ['=', $iLotteryId],
            'turnover' => ['>', 0],
            'point_used' => ['=', $iUsed]
        ];
        if (!empty($sIssue)) {

            $where['issue'] = ['=', $sIssue];
        }

        $data = static::doWhere($where)
            ->orderBy('user_id', 'asc')
            ->get(['id', 'user_id', 'account_id', 'lottery_id', 'issue', 'turnover', 'updated_at'])
            ->toArray();
        return $data;
    }

    public static function setToUsed($id){
        return static::where('id','=',$id)->where('used','=',0)->update(['used' => 1]) > 0;
    }

    public static function setToFundUsed($id){
        return static::where('id','=',$id)->where('fund_used','=',0)->update(['fund_used' => 1]) > 0;
    }

    public static function setToPointUsed($id){
        return static::where('id','=',$id)->where('point_used','=',0)->update(['point_used' => 1]) > 0;
    }

    public static function getLotteryUserTurnOvers($iLotteryId,$iUsed = 0,$iCount = 500, & $i = null){
        $dStartTime = date('Y-m-d H:i:s',strtotime('-2 day'));
        $i++;
        return static::where('created_at','>=',$dStartTime)
            ->where('lottery_id','=',$iLotteryId)
            ->where('turnover','>',0)
            ->where('used','=',$iUsed)
            ->orderBy('id','asc')
            ->limit($iCount)
            ->get(['id','user_id','account_id','turnover','updated_at'])->toArray();
    }

    public static function & getLotteryUserTurnOversForFund($iLotteryId, $iUsed = 0){
        $data = static::where('lottery_id','=',$iLotteryId)
            ->where('turnover','>',0)
            ->where('fund_used','=',$iUsed)
            ->orderBy('user_id','asc')
            ->get(['id','user_id','account_id','turnover','updated_at'])->toArray();
        return $data;
//        $data = [];
//        $aDetailIds = [];
//        foreach($a as $aInfo){
//            $iUserId = $aInfo['user_id'];
//            $aDetailIds = $aInfo['id'];
//            if (isset($data[$iUserId])){
//                $data[$iUserId]['turnover'] += $aInfo['turnover'];
//            }
//            else{
//                $data[$iUserId] = [
//                    'user_id' => $iUserId,
//                    'account_id' => $aInfo['account_id'],
//                    'turnover' => $aInfo['turnover']
//                ];
//            }
//        }
//        return $data;
    }
 /**
     * 返回UserProfit对象
     *
     * @param string $sDate
     * @param string $iUserId
     * @param array $aLotteryIds
     * @return object
     */
    public static function getUserTurnverByDate($iUserId, $sStartDate, $sEndDate, $aLotteryIds = []) {
        $oQuery = static::where('user_id', '=', $iUserId)
                ->where('created_at', '>=', $sStartDate)
                ->where('created_at', '<', $sEndDate);

        if ($aLotteryIds) {
             $oQuery=$oQuery->whereIn('lottery_id', $aLotteryIds);
        }
        
        return $oQuery->sum("turnover");
    }

}

