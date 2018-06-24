<?php

/**
 * 用户活动销量统计表
 *
 * @author Winter
 */
class UserEventSale extends BaseModel {

    protected $table = 'user_event_sales';
    public static $resourceName = 'UserEventSale';
    public static $amountAccuracy    = 4;
    public static $htmlNumberColumns = [
        'turnover' => 4,
    ];
    public static $columnForList = [
        'activity_id',
        'username',
        'turnover',
    ];

    public static $listColumnMaps = [
        'turnover' => 'turnover_formatted',
    ];

    protected $fillable = [
        'activity_id',
        'user_id',
        'account_id',
        'username',
        'is_tester',
        'turnover',
        'parent_user_id',
        'parent_user',
        'date'
    ];
    public static $rules = [
        'activity_id' => 'required|integer|min:1',
        'user_id' => 'required|integer',
        'account_id' => 'required|integer',
        'username' => 'required|max:16',
        'parent_user_id' => 'integer',
        'parent_user' => 'max:16',
        'turnover' => 'numeric',
    ];

    public $orderColumns = [
        'activity_id' => 'desc',
        'username' => 'asc'
    ];

    public static $mainParamColumn = 'activity_id';
    public static $titleColumn = 'username';

    /**
     * 返回UserEventSale对象
     *
     * @param string $sDate
     * @param string $iUserId
     * @return UserEventSale
     */
    public static function getUserSaleObject($iActivityId, $iUserId) {
        $aConditions = [
            'activity_id' => $iActivityId,
            'user_id' => $iUserId,
        ];
        return static::firstOrNew($aConditions);
    }

    protected function beforeValidate() {
        if (!$this->username){
            $oUser = User::find($this->user_id);
            $this->account_id = $oUser->account_id;
            $this->username = $oUser->username;
            $this->parent_user_id = $oUser->parent_id;
            $this->parent_user = $oUser->parent;
            $this->is_tester = $oUser->is_tester;
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

    public static function updateTurnoverData($iActivityId, $iUserId, $fAmount) {
        $oTurnover = static::getUserTurnverObject($iActivityId,$iUserId);
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
    public static function getUserTurnOvers($iActivityId){
        return static::where('activity_id','=',$iActivityId)
                ->where('turnover','>',0)
                ->orderBy('user_id','asc')->get(['id','user_id','account_id','turnover'])
                ->toArray();
    }

    public static function setToUsed($id){
        return static::where('id','=',$id)->update(['used' => 1]) > 0;
    }
    
    public function reset(){
        $this->turnover = 0;
        return $this->save();
    }

    public function minusTurnover($fAmount){
        $this->turnover = $this->turnover - $fAmount;
//        pr($this->attributes);
        return $this->save();
    }
    
    public static function getSale($iActivityId,$iUser,$date = ''){
        if(!empty($date)){
            return self::where('activity_id','=',$iActivityId)->where('user_id','=',$iUser)->where('date','=',$date)->pluck('turnover');
        }else{
            return self::where('activity_id','=',$iActivityId)->where('user_id','=',$iUser)->pluck('turnover');
        }
    }
    
    public static function getObject($iActivityId,$iUser,$date = ''){
        if(!empty($date)){
            return self::where('activity_id','=',$iActivityId)->where('user_id','=',$iUser)->where('date','=',$date)->first();
        }else{
            return self::where('activity_id','=',$iActivityId)->where('user_id','=',$iUser)->first();
        }
    }
}
