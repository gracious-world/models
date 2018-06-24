<?php

class UserPercentSet extends BaseModel {
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'user_percent_sets';
    /**
     * 软删除
     * @var boolean
     */
    protected $softDelete = false;
    protected $fillable = [
        'user_id',
        'user_parent_id',
        'user_parent',
        'username',
        'series_id',
        'lottery_id',
        'percent_way_id',
        'percent_identity',
        'percent_value',
        'is_agent'
    ];

    public static $iFootBallLotteryId = 31;

    const AG_GAME_ID  = 54;
    const GA_GAME_ID  = 58;

    public static $resourceName = 'User Percent Set';

    /**
     * the columns for list page
     * @var array
     */
    public static $columnForList = [
        'user_parent',
        'username',
        'series_id',
        'lottery_id',
        'percent_way_id',
        'percent_identity',
        'percent_value',
        'is_agent'
    ];
    /**
     * 下拉列表框字段配置
     * @var array
     */
    public static $htmlSelectColumns = [
        'lottery_id' => 'aLotteries',
        'series_id' => 'aSeries',
        'percent_way_id' => 'aPercentWays',
        'is_agent'  => 'aIsAgent',
    ];

    /**
     * order by config
     * @var array
     */
    public $orderColumns = [
        'user_id' => 'asc'
    ];

    /**
     * readonly columns for edit
     * @var array
     */
    public static $readonlyColumnsInEdit = [
        'username' ,
        'percent_way_id'
    ];

    /**
     * If Tree Model
     * @var Bool
     */
    public static $treeable = false;

    /**
     * the main param for index page
     * @var string
     */
    public static $mainParamColumn = '';

    public static $ignoreColumnsInEdit = [
        'user_id',
        'is_agent',
        'series_id',
        'lottery_id'
    ];

     public static $listColumnMaps      = [
        // 'account_available' => 'account_available_formatted',
        'percent_value'    => 'percent_value_formatted',
    ];

    public static $viewColumnMaps = [
        'percent_values' => 'full_percent_value'
    ];

    protected function getPercentValueFormattedAttribute() {
        $number = $this->attributes['percent_value'] * 100;
        return $number.'%';
    }

    protected function getFullPercentValueFormattedAttribute(){
        return $this->attributes['percent_value'] * 100;
    }

    public static $rules = [
        'user_id'   => 'required|integer',
        'username' => 'required|max:16',
        'series_id' => 'required|integer',
        'lottery_id' => 'required|integer',
        'percent_way_id' => 'required|integer',
        'percent_value' => 'required|numeric|max:1',
    ];

    protected function beforeValidate() {
        if($this->user_id && empty($this->username)){
            $aUser = User::find($this->user_id);
            $this->username = $aUser['username'];
            $this->is_agent = $aUser['is_agent'];
            if(!empty($aUser['parent_id'])){
                $this->user_parent_id = $aUser['parent_id'];
                $this->user_parent = $aUser['parent'];
            }
            unset($oUser);
        }
        $this->percent_value < 1 or $this->percent_value /= 100;
        if(empty($this->percent_identity)){
            $aPercentWay = PercentWay::find($this->percent_way_id);
            $this->series_id = $aPercentWay['series_id'];
            $this->lottery_id = $aPercentWay['lottery_id'];
            $this->percent_identity = $aPercentWay['identity'];
        }
        if ($this->user_parent_id) {
            $bCheckLottery = in_array($this->percent_way_id, [PercentWay::AG_PERCENT_WAY, PercentWay::GA_PERCENT_WAY]) ? false : true;
            $fParentSetting = static::getPercentValueByUser($this->user_parent_id, $this->lottery_id, $this->percent_way_id, $bCheckLottery);
            if ($fParentSetting < $this->percent_value) {
                return false;
            }
        } else {
            $sPercentIdentity = $this->percent_way_id == 1 ? 'single' : 'multi';
            $sTopAgentPercentRate = PercentWay::getPercentRateByIdentity($sPercentIdentity);
            if ($this->percent_value > $sTopAgentPercentRate['max'] || $this->percent_value < $sTopAgentPercentRate['min']) {
                return false;
            }
        }
        if(!$this->id){
            $aCondition = [
                'user_id' => ['=',$this->user_id],
                'series_id' => ['=',$this->series_id],
                'lottery_id' => ['=',$this->lottery_id],
                'percent_way_id' => ['=',$this->percent_way_id]
            ];
            $isExist = static::doWhere($aCondition)->get();
            if(!$isExist->isEmpty()){
                return false;
            }
        }
        return parent::beforeValidate();
    }

    /**
     * 获取用户百分比返点设置
     * @param $iUserId
     * @param $iLotteryId
     * @param $iPercentWayId
     * @return bool
     */
    public static function getPercentValueByUser($iUserId, $iLotteryId, $iPercentWayId, $bCheckLottery = true) {
        $oQuery = static::where('user_id', '=', $iUserId)->where('percent_way_id', '=', $iPercentWayId);
        !$bCheckLottery or $oQuery = $oQuery->where('lottery_id', '=', $iLotteryId);
        $oPercentSet = $oQuery->first();
        return is_object($oPercentSet) ? $oPercentSet->percent_value : 0;
    }

    public static function getUserPercentValues($iUserId){
        $aRes = [
            'fb_single' => 0,
            'fb_all' => 0,
            'ag_percent' => 0,
            'ga_percent' => 0,
        ];
        $oUserPercentSets = static::getPercentsByUser($iUserId);
        if(!$oUserPercentSets->count()){
            return $aRes;
        }
        foreach($oUserPercentSets as $oUserPercentSet){
            $iLotteryId = $oUserPercentSet->lottery_id;
            $iPercentWayId = $oUserPercentSet->percent_way_id;
            $fPercentValue = $oUserPercentSet->percent_value;
            if($iLotteryId == self::$iFootBallLotteryId){
                if ($iPercentWayId == PercentWay::$jcWays['single']){
                    $aRes['fb_single'] = $fPercentValue;
                }else{
                    $aRes['fb_all'] = $fPercentValue;
                }
            }else if($iLotteryId == self::AG_GAME_ID){
                if($iPercentWayId== PercentWay::AG_PERCENT_WAY){
                    $aRes['ag_percent'] = $fPercentValue;
                }
            }else{
                if($iPercentWayId== PercentWay::GA_PERCENT_WAY){
                    $aRes['ga_percent'] = $fPercentValue;
                }
            }
        }
        return $aRes;
    }

    /**
     * 获取用户百分比返点设置
     *
     * @param $iUserId
     * @return float
     */
    public static function getPercentsByUser($iUserId) {
        $sCacheKey = self::createCacheKey($iUserId);
        $obj = Cache::get($sCacheKey);
        if (!$obj) {
            $obj = static::where('user_id', '=', $iUserId)->get();
            Cache::forever($sCacheKey, $obj);
        }
        return $obj;
    }

    protected function afterSave($oSavedModel) {
        $sCacheKey = static::createCacheKey($oSavedModel->user_id);
        !Cache::has($sCacheKey) or Cache::forget($sCacheKey);
        return parent::afterSave($oSavedModel);
    }
    /**
     * 生成与实例关联的缓存key
     * @param int $iUserId
     * @return string
     * @access protected
     * @static
     */
    protected static function createCacheKey($iUserId) {
        return static::getCachePrefix() . $iUserId;
    }
    /**
     * 返回多个用户的百分比返点设置
     * @param array $aUsers
     * @param integer $iLotteryId
     * @param integer $iPercentWayId
     * @return array
     */
    public static function getPercentSetOfUsers($aUsers, $iLotteryId, $iPercentWayId, $bCheckLottery = true) {
        $aPercentSets = [];
        foreach ($aUsers as $iUserId) {
            $aPercentSets[$iUserId] = static::getPercentValueByUser($iUserId, $iLotteryId, $iPercentWayId,$bCheckLottery);
        }
        return $aPercentSets;
    }

    public static function createUserPercentSet($oUser,$aPercentSet){
         if (!$oPercentWay = PercentWay::where('identity' ,'=',$aPercentSet['percent_identity'])->first()){
                return false;
            }
            // pr($oUser->toArray());exit;
            $data = [
                'user_id' => $oUser->id,
                'username' => $oUser->username,
                'series_id' => $oPercentWay->series_id,
                'lottery_id' => $oPercentWay->lottery_id,
                'percent_way_id' => $oPercentWay->id,
                'percent_identity' => $oPercentWay->identity,
                'percent_value' => $aPercentSet['percent_value'],
                'is_agent' => $oUser->is_agent,
            ];
            if(!empty($oUser->parent)){
                $data['user_parent_id'] = $oUser->parent_id;
                $data['user_parent'] = $oUser->parent;
            }
            $obj = new static($data);
            $bSucc = $obj->save();
            return $bSucc;
    }


    public static function initUserPercentSet($oUser,$aPercentSet){
        foreach($aPercentSet as $percentSet){
            $bSucc = self::createUserPercentSet($oUser,$percentSet);
            if(!$bSucc){
                break;
            }
        }
        return $bSucc;
    }

    public static function updateUserPercentSet($oUser, $aPercentSet) {
        foreach ($aPercentSet as $percentSet) {
            if (!$oUserPercent = self::where('percent_identity', '=', $percentSet['percent_identity'])->where('user_id', '=', $oUser->id)->first()) {
                $bSucc = self::createUserPercentSet($oUser, $percentSet);
            } else {
                if (bccomp($percentSet['percent_value'], $oUserPercent->percent_value, 3) == -1) {
                    return false;
                }
                $oUserPercent ->percent_value = $percentSet['percent_value'];
                $bSucc = $oUserPercent->save();
            }
            if(!$bSucc){
                    break;
            }
        }
        return $bSucc;
    }

     /**
     * 获取指定
     * @param $aUserIds
     * @return mixed
     */
    public static function getMaxPercentValueByUsers($aUserIds){
        return static::whereIn('user_id', $aUserIds)->max('percent_value');
    }
}
