<?php

/**
 * @author lucda
 */
use Illuminate\Support\Facades\Redis;
class EventUserPrizes extends BaseModel {

    protected $table = 'event_user_prizes';

    const PRIZE_STATUS_CREATED          = 0; //待审核
    const PRIZE_STATUS_ACCEPTED         = 1; //已受理
    const PRIZE_STATUS_VERIRIED         = 2; //审核通过
    const PRIZE_STATUS_REJECT           = 3; //审核未通过
    const PRIZE_STATUS_SENT             = 4;  //已派发
    const PRIZE_STATUS_SENT_FAILED      = 5; //派发失败

    // 类别 1. 固定数值发放奖赏 2.根据投注总额决定奖赏 3.根据中奖次数决定奖赏
    const TYPE_FIXED        = 1;
    const TYPE_TURNOVER     = 2;
    const TYPE_PRIZE_TIME   = 3;
    const TYPE_MULTIPLE     = 4;

    // 发放类别, 1礼金, 2积分 3.奖品
    const GIFT_TYPE_CASH        = 1;
    const GIFT_TYPE_POINT       = 2;
    const GIFT_TYPE_GOODS       = 3;

    protected static $cacheUseParentClass = false;

    protected static $cacheLevel = self::CACHE_LEVEL_FIRST;

    protected static $cacheMinutes = 0;

    public static $validStatus     = [
        self::PRIZE_STATUS_CREATED          => 'prize-status-created',    //待受理
        self::PRIZE_STATUS_ACCEPTED         => 'prize-status-accepted',   //受理
        self::PRIZE_STATUS_VERIRIED         => 'prize-status-veriried',   //审核
        self::PRIZE_STATUS_REJECT           => 'prize-status-reject',     //无效
        self::PRIZE_STATUS_SENT             => 'prize-status-sent',       //已发放
        self::PRIZE_STATUS_SENT_FAILED      => 'prize-status-sent-failed',//发放失败
    ];

    public static $validTypes     = [
        self::TYPE_FIXED        => 'type-fixed',
        self::TYPE_TURNOVER     => 'type-turnover',
        self::TYPE_PRIZE_TIME   => 'type-prize-time',
        self::TYPE_MULTIPLE     => 'type-multiple',
    ];

    public static $validGiftTypes     = [
        self::GIFT_TYPE_CASH        => 'gift-type-cash',
        self::GIFT_TYPE_POINT       => 'gift-type-point',
        self::GIFT_TYPE_GOODS       => 'gift-type-goods',
    ];

    public static $validLevel     = [
        1,2,3,4,5,6
    ];


    protected $fillable = [
        'date',
        'event_id',                     //任务id
        'event_user_id',                //event_users 表里面的id
        'user_id',
        'level',                        //任务条件等级
        'type',                         //类别, 1. 固定数值发放奖赏 2.根据投注总额决定奖赏 3.根据中奖次数决定奖赏
        'gift_type',                    //发放类别, 1礼金, 2积分 3.奖品
        'gift_value',                   //奖品价值
        'status',                       //奖品发放状态
        'sended_prize_at',
        'verified_at',
        'created_at',
        'updated_at',
        'is_captain',
    ];

    public static $sequencable = false;

    public static $enabledBatchAction = false;

    protected $validatorMessages = [];

    protected $isAdmin = true;

    public static $resourceName = 'EventUserPrizes';

    protected $softDelete = false;

    protected $defaultColumns = [ '*' ];

    protected $hidden = [];

    protected $visible = [];

    public static $treeable = '';

    public static $foreFatherIDColumn = '';

    public static $foreFatherColumn = '';

    public static $columnForList = [
        'id',
        'date',
        'event_id',
        'event_user_id',
        'user_id',
        'username',
        'level',
        'type',
        'gift_type',
        'gift_value',
        'status',
        'sended_prize_at',
        'verified_at',
        'created_at',
    ];

    public static $totalColumns = [];

    public static $totalRateColumns = [];

    public static $weightFields = [];

    public static $classGradeFields = [];

    public static $floatDisplayFields = [];

    public static $noOrderByColumns = [];

    public static $ignoreColumnsInView = [
    ];

    public static $ignoreColumnsInEdit = [

    ];

    public static $listColumnMaps = [
        'status'    => 'status_formatted',
        'type'     => 'type_formatted',
        'gift_type' => 'gift_type_formatted',
    ];

    public static $viewColumnMaps = [];

    public static $htmlSelectColumns = [
        'level' => 'aValidLevel',
        'status'=>'aValidStatus',
        'type' => 'aValidTypes',
        'gift_type' => 'aValidGiftTypes',
    ];


    public static $htmlTextAreaColumns = [];

    public static $htmlNumberColumns = [];

    public static $htmlOriginalNumberColumns = [];

    public static $amountAccuracy = 0;

    public static $originalColumns;

    public $orderColumns = [
        'event_id'     => 'desc',
        'created_at'     => 'desc',
        'event_user_id'    => 'desc',
        'user_id' => 'desc',
    ];

    public static $titleColumn = 'event_id';

    public static $mainParamColumn = 'user_id';

    public static $rules = [
        'date'                      => 'required',
        'event_id'                  => 'required|integer',
        'event_user_id'             => 'required|integer',
        'user_id'                   => 'required|integer',
        'level'                     => 'integer',         //活动等级可以为0,代表没有级别
        'type'                      => 'required|integer|in:1,2,3,4',
        'gift_type'                 => 'required|integer|in:1,2,3',
        'gift_value'                => 'required',
        'status'                    => 'required|integer|in:0,1,2,3,4,5',
        'sended_prize_at'           => 'date',
        'verified_at'               => 'date',
    ];

    protected function beforeValidate() {
        if (!$this->username) {
            $oUser                     = User::find($this->user_id);
            $this->username            = $oUser->username;
        }
        !is_null($this->status) or $this->status            = self::PRIZE_STATUS_CREATED;
        !is_null($this->date) or $this->date                = date('Y-m-d', time());
        !is_null($this->level) or $this->level              = 0;
        return parent::beforeValidate();
    }

    public function getStatusFormattedAttribute() {
        return __('_eventuserprizes.' . static::$validStatus[$this->status]);
    }

    public function getGiftTypeFormattedAttribute() {
        return __('_eventuserprizes.' . static::$validGiftTypes[$this->gift_type]);
    }

    public function getTypeFormattedAttribute() {
        return __('_eventuserprizes.' . static::$validTypes[$this->type]);
    }

    public static function getValidTypes() {
        return static::_getArrayAttributes(__FUNCTION__);
    }


    public static function getValidGiftTypes() {
        return static::_getArrayAttributes(__FUNCTION__);
    }

    public static function getValidStatus() {
        return static::_getArrayAttributes(__FUNCTION__);
    }

    public static function getValidLevel() {
        return static::_getArrayAttributes(__FUNCTION__);
    }

    /**
     * 根据条件查询
     *
     * @author  lucda
     * @date    2016-10-23
     * @param $aEventUserPrize
     * @return array
     */
    public function hasRow($aEventUserPrize) {
        $sDate          =   isset ($aEventUserPrize['date']) ? $aEventUserPrize['date'] : date('Y-m-d', time());
        $iEventId       =   $aEventUserPrize['event_id'];
        $iEventUserId   =   $aEventUserPrize['event_user_id'];
        $iUserId        =   $aEventUserPrize['user_id'];
        $iLevel         =   $aEventUserPrize['level'];
        return static::doWhere(
            [
                'date' => ['=',$sDate],
                'event_id' => ['=',$iEventId],
                'event_user_id' => ['=',$iEventUserId],
                'user_id' => ['=',$iUserId],
                'level' => ['=',$iLevel],
            ]
        )->get()->toArray();
    }

    /**
     * 将 一条计奖数组添加到 event_user_prizes 表中
     * @author  lucda
     * @date    2016-12-23
     * @param $aEventUserPrize
     * @param $iEventId
     * @return bool
     */
    static function addEventUserPrize($aEventUserPrize) {
        $bSucc = true;
        $oEventUserPrize = new EventUserPrizes();
        if( !$oEventUserPrize->hasRow($aEventUserPrize) ) {
            if($aEventUserPrize['gift_value'] <= 0) {
                $aEventUserPrize['verified_at'] = date("Y-m-d H:i:s",time());
                $aEventUserPrize['sended_prize_at'] = date("Y-m-d H:i:s",time());
                $aEventUserPrize['status'] = static::PRIZE_STATUS_SENT;
                $oEventUserPrize->fill($aEventUserPrize);
                $bSucc = $oEventUserPrize->save();
            } else {
                //自动发放
                $bNeedVerify = $aEventUserPrize['need_verify'];//是否需要审核发放. 还是直接发放到帐变表
                if(!$bNeedVerify) {
                    $aEventUserPrize['verified_at'] = date("Y-m-d H:i:s",time());
                    $aEventUserPrize['status'] = static::PRIZE_STATUS_VERIRIED;
                }
                $oEventUserPrize->fill($aEventUserPrize);
                $bSucc = $oEventUserPrize->save();

                //非审核发放. 直接发放到帐变表
                if( !$bNeedVerify && $bSucc ) {
//                    Log::info(__FILE__ . __LINE__ . json_encode($aEventUserPrize));
                    BaseTask::addTask("EventUserPrizeSend",['id'=>$oEventUserPrize->id],"event");
                }
            }
        }
        return $bSucc;
    }

    /**
     * 将 计奖数组添加到 event_user_prizes 表中
     * @author  lucda
     * @date    2016-12-23
     * @param $aEventUserPrizes
     * @return bool
     */
    static function addEventUserPrizes($aEventUserPrizes) {
        foreach ($aEventUserPrizes as $aEventUserPrize) {
            static::addEventUserPrize($aEventUserPrize);
        }
        return true;
    }


    /**
     * 整理数据给 event_user_prizes 使用
     *
     * @author  lucda
     * @date    2016-10-23
     * @param $aEventPrizesUseSendPeopleTypes
     * @param $iEventId
     * @param $iEventUserId
     * @param $iLevel
     * @param $iUserId
     * @return array =>[  ['event_id'=>1,  'event_user_id'=>12, 'event_prize_id'=>5 , 'user_id'=>10 , 'level'=>5 , 'type'=>1 , 'gift_type'=>1 , 'gift_value'=>18000 , 'need_verify' =>0    ] ]
     */
    static function compileEventUserPrizes($aEventPrizesUseSendPeopleTypes, $iEventId, $iEventUserId, $iLevel, $iUserId) {
        $aEventUserPrizes = [];
        foreach ($aEventPrizesUseSendPeopleTypes as $aEventPrizesUseSendPeopleType) {
            $aEventUserPrize['event_id'] = $iEventId;
            $aEventUserPrize['event_user_id'] = $iEventUserId;
            $iGetGiftUserId = $iUserId;
            switch ($aEventPrizesUseSendPeopleType['send_people_type']) {
                case EventPrizes::SEND_PEOPLE_TYPE_SELF:
                    $iGetGiftUserId = $iUserId;
                    break;
                case EventPrizes::SEND_PEOPLE_TYPE_PARENT:
                    $oUser = User::find($iUserId);
                    $iGetGiftUserId = $oUser->parent_id;
                    break;
                case EventPrizes::SEND_PEOPLE_TYPE_TEAM_FINISHED:
                    //TODO
                    break;
                case EventPrizes::SEND_PEOPLE_TYPE_TEAM_ALL:
                    //TODO
                    break;
                default:
                    $iGetGiftUserId = $iUserId;
                    break;
            }
            if(!$iGetGiftUserId){
                continue;
            }
            $aEventUserPrize['event_prize_id'] = $aEventPrizesUseSendPeopleType['id'];
            $aEventUserPrize['user_id'] = $iGetGiftUserId;
            $aEventUserPrize['level'] = $iLevel;
            $aEventUserPrize['type'] = $aEventPrizesUseSendPeopleType['type'];
            $aEventUserPrize['gift_type'] = $aEventPrizesUseSendPeopleType['gift_type'];
            $aEventUserPrize['gift_value'] = $aEventPrizesUseSendPeopleType['gift_value'];
            $aEventUserPrize['need_verify'] = $aEventPrizesUseSendPeopleType['need_verify'];
            $key = $iEventId.$iEventUserId.$iGetGiftUserId.$iLevel;
            $aEventUserPrizes[$key] = $aEventUserPrize;
        }
        return $aEventUserPrizes;
    }


    /**
     * compile指定会员和状态的 奖金数据
     * 数组格式为 event_user_id => gift_type => total gift_value
     *
     * @author lucda
     * @date 2016-10-26
     * @param $aUserIds
     * @param $iStatus
     * @return array
     */
    static function compilePrizesUseEventUserIdGiftType($aUserIds, $aStatus = [self::PRIZE_STATUS_SENT]) {
        if(!$aUserIds) {
            return [];
        }
        $aPrizesUseEventUserIdGiftType = [];
        $aConditions['status']=['in',$aStatus];
        $aConditions['user_id']=['in',$aUserIds];
        $aEventUserPrizes = EventUserPrizes::doWhere($aConditions)->get()->toArray();
        if(!$aEventUserPrizes) {
            return $aPrizesUseEventUserIdGiftType;
        }
        foreach($aEventUserPrizes as $aEventUserPrize) {
            $iEventUserId = $aEventUserPrize['event_user_id'];
            $iGiftType = $aEventUserPrize['gift_type'];
            $iGiftValue = $aEventUserPrize['gift_value'];
            $iTotalGiftValue = isset ( $aPrizesUseEventUserIdGiftType[$iEventUserId][$iGiftType] ) ? $aPrizesUseEventUserIdGiftType[$iEventUserId][$iGiftType]+$iGiftValue : $iGiftValue;
            $aPrizesUseEventUserIdGiftType[$iEventUserId][$iGiftType] = $iTotalGiftValue;
        }
        return $aPrizesUseEventUserIdGiftType;
    }

    /**
     * 根据 event_users 里面的那 id 获取 所有的行
     * @author lucda
     * @date    2016-12-01
     * @param $iEventUserId
     */
    static function getRowsByEventUserId($iEventUserId) {
        return EventUserPrizes::doWhere(['event_user_id'=>['=',$iEventUserId]])->get();
    }

    /**
     * 团队当月活动奖励
     * @author lucky
     * @date 2016-10-30
     * @param array $aTeamIds
     * @return mixed
     */
    static function getCurrentMonthBonus($aTeamIds="(0)", $sMonthStart, $sMonthEnd) {
        $sCurrentMonthStart = "'" . $sMonthStart . "'";
        $sCurrentMonthEnd   = "'" . $sMonthEnd . "'";
        $iGiftTypeCash = EventUserPrizes::GIFT_TYPE_CASH;
        $iStatus = EventUserPrizes::PRIZE_STATUS_SENT;
        $sSql = "select user_id, username,sum(gift_value) as gift_value from event_user_prizes where user_id in $aTeamIds and gift_type=$iGiftTypeCash and status=$iStatus and created_at between $sCurrentMonthStart and $sCurrentMonthEnd";
        return DB::select($sSql);
    }

    /**
     * 查询已经派奖的记录
     * @author lucda
     * @date    2016-12-07
     * @param int $iEventId
     * @param int $iIsCaptain
     * @param int $iCount
     * @return object collections
     */
    static function getSendPrizeObjectsByEventId($iEventId, $iIsCaptain = 0, $iCount = 10) {
        $aConditions['event_id'] = ['=', $iEventId];
        $aConditions['status'] = ['=', Static::PRIZE_STATUS_SENT];
        if ($iIsCaptain) {
            $aConditions['is_captain'] = ['=', $iIsCaptain];
        }
        return Static::doWhere($aConditions)->orderBy('id', 'desc')->take($iCount)->get();
    }
    /**
     * 根据时间查询已经派奖的记录
     * @author simon
     * @date    2016-12-07
     * @param int $iEventId
     * @param date $iBeginTime
     * @param date $iEndTime
     * @return object collections
     */
    static function getSendPrizeObjectsBySend($iEventId,$iBeginTime,$iEndTime) {
        $aConditions['event_id'] = ['=', $iEventId];
        $aConditions['status'] = ['=', Static::PRIZE_STATUS_SENT];
        $aConditions['sended_prize_at'] = ['between', [$iBeginTime,$iEndTime]];
        return Static::doWhere($aConditions)->orderBy('id', 'desc')->get();
    }

    /*
     * 检查用户是否已经领取奖了
     * @author simon
     * @date 2012-12-27
     * @$aEventUserPrize
     * @return boolean
     */
    public static function checkSendPrizeByUserIdEventId($aEventUserPrize){
        $sDate          =  date('Y-m-d', time());
        $iEventId       =   $aEventUserPrize['event_id'];
        //$iEventUserId   =   $aEventUserPrize['event_user_id'];
        $iUserId        =   $aEventUserPrize['user_id'];
        //$iLevel         =   $aEventUserPrize['level'];
        $oQuery = static::where('user_id', '=', $iUserId);
        $oQuery->where('date', '=', $sDate);
        $oQuery->where('event_id', '=', $iEventId);
       // $oQuery->where('event_user_id', '=', '33723');
        //$oQuery->where('level', '=', $iLevel);
        $aSendPrizes = $oQuery->get();
        $bSendPrize = false;
        if (count($aSendPrizes)>0){
            foreach ($aSendPrizes as $aSendPrize){
                if($aSendPrize->status == EventUserPrizes::PRIZE_STATUS_SENT || $aSendPrize->status == EventUserPrizes::PRIZE_STATUS_VERIRIED ){
                    $bSendPrize = true;
                }
            }
        }
        return $bSendPrize;
    }
    /*
   * 检查用户是否已经领取奖了 返回领取等级
   * @author simon
   * @date 2012-12-27
   * @$aEventUserPrize
   * @return boolean
   */
    public static function getLevelSendPrizeByUserIdEventId($aEventUserPrize){
        $sDate          =  date('Y-m-d', time());
        $iEventId       =   $aEventUserPrize['event_id'];
        //$iEventUserId   =   $aEventUserPrize['event_user_id'];
        $iUserId        =   $aEventUserPrize['user_id'];
        //$iLevel         =   $aEventUserPrize['level'];
        $oQuery = static::where('user_id', '=', $iUserId);
        $oQuery->where('date', '=', $sDate);
        $oQuery->where('event_id', '=', $iEventId);
        // $oQuery->where('event_user_id', '=', '33723');
        //$oQuery->where('level', '=', $iLevel);
        $aSendPrizes = $oQuery->get();
        $bSendPrize = '';
        if (count($aSendPrizes)>0){
            foreach ($aSendPrizes as $aSendPrize){
                if($aSendPrize->status == EventUserPrizes::PRIZE_STATUS_SENT || $aSendPrize->status == EventUserPrizes::PRIZE_STATUS_VERIRIED){
                    $bSendPrize = $aSendPrize->level;
                }
            }
        }
        return $bSendPrize;
    }

    /*
        * 根据用户ID来查今天用户时候参加了活动，如果参加了返回等级
        * @author simon
        * @date 2012-12-27
        * @$aEventUserPrize
        * @return boolean
        */
    public static function getLevelByUserIdEventId($iEventId,$iUserId){
        $sDate          =  date('Y-m-d', time());
        $oQuery = static::where('user_id', '=', $iUserId);
        $oQuery->where('date', '=', $sDate);
        $oQuery->where('event_id', '=', $iEventId);
        $oQuery->whereIn('status', [EventUserPrizes::PRIZE_STATUS_SENT]);
        $aSendPrizes = $oQuery->get();
        $iLevel = '';
        if (count($aSendPrizes)>0){
            foreach ($aSendPrizes as $aSendPrize){
                $iLevel = $aSendPrize->level;
            }
        }
        return $iLevel;
    }
    /*
      * 根据用户ID来查今天用户时候参加了活动，如果参加了返回等级
      * @author simon
      * @date 2012-12-27
      * @$aEventUserPrize
      * @return boolean
      */
    public static function getLevelByUserIdSend_at($iEventId,$iUserId){
        $sDate          =  date('Y-m-d', time());
        $oQuery = static::where('user_id', '=', $iUserId);
        $oQuery->where('date', '=', $sDate);
        $oQuery->where('event_id', '=', $iEventId);
        $oQuery->whereIn('status', [EventUserPrizes::PRIZE_STATUS_SENT]);
        $aSendPrizes = $oQuery->get();
        $sended_prize_at = '';
        if (count($aSendPrizes)>0){
            foreach ($aSendPrizes as $aSendPrize){
                $sended_prize_at = $aSendPrize->sended_prize_at;
            }
        }
        return $sended_prize_at;
    }
    /**
     * 整理活动6 一条 竞彩加奖数据 给 event_user_prizes 使用
     * @author  lucda
     * @date    2016-12-22
     * @param $aPlatPrize
     * @param $sDate
     * @param $iEventId
     * @param $iEventUserId
     * @return array
     */
    static function compilePrizeFromPlat($aPlatPrize, $sDate, $iEventId, $iEventUserId) {
        $aEventUserPrize['date']            = $sDate;
        $aEventUserPrize['event_id']        = $iEventId;
        $aEventUserPrize['event_user_id']   = $iEventUserId;
        $aEventUserPrize['user_id']         = $aPlatPrize['user_id'];
        $aEventUserPrize['level']       = 0;
        $aEventUserPrize['type']        = static::TYPE_FIXED;
        $aEventUserPrize['gift_type']   = static::GIFT_TYPE_CASH;
        $aEventUserPrize['gift_value']  = $aPlatPrize['prizeAdded'];
        $aEventUserPrize['need_verify'] = 0;

        return $aEventUserPrize;
    }
    
   /**
     * 下级member活动发放礼金给队长
     * @param $iUserId
     * @param $iCount
     * @return array
     */
    public static function getUserChildrenPanelData($iUserId,$aChildrenIds,$iCount) {
        $aChildrenIds[]=0;
        $sKey = static::compileUserChildrenPanelDataCacheKey($iUserId);
        $redis = Redis::connection();
        if ($redis->exists($sKey)) {
            $aData = $redis->lrange($sKey, 0, $redis->llen($sKey) - 1);
            $aResultData = [];
            foreach ($aData  as $sData) {
                $aResultData[] = (array)json_decode($sData);
            }
        } else {
            $aConditions=['send_people_type'=>['=',iEventPrizes::SEND_PEOPLE_TYPE_PARENT]];
            //下级活动发放礼金给队长
            $aChildrenEventIds = EventPrizes::Where('send_people_type', EventPrizes::SEND_PEOPLE_TYPE_PARENT)->lists("event_id");
            $aResultData = Static::dowhere(['user_id' => ['in', $aChildrenIds], 'event_id' => ['in', $aChildrenEventIds], 'status' => ['=', EventUserPrizes::PRIZE_STATUS_SENT]])->limit($iCount)->get()->toArray();
            $redis->multi();
            foreach($aResultData as $aData){
                $redis->rpush($sKey, json_encode($aData));
            }
            $redis->exec();
        }
        return $aResultData;
    }


    /**
     * 删除用户面板活动奖品变动缓存
     * TODO combine createlistCache
     * @author lucky
     * @date 2016-12-22
     * @param $iUserId
     */
    protected function deleteUserPanelDataCache() {
        $sKey = static::compileUserPanelDataCacheKey($this->user_id);
        $redis = Redis::connection();
        if ($aKeys = $redis->keys($sKey. '*')) {
            foreach ($aKeys as $sKey) {
                $redis->del($sKey);
            }
        }
    }

    public function afterSave($oSavedModel) {
        parent::afterSave($oSavedModel); // TODO: Change the autogenerated stub
    }
    
    /**
     * 取得指定活动使用者的奖品
     *
     * @author    Wright
     * @param  integer $iEventUserId
     * @date      2017-04-11
     * @return  object
     */
    public static function getByEventUserId($iEventUserId) {
        return static::where('event_user_id', $iEventUserId)
            ->get();
    }
    
 /**
     * 建立已取得的奖励
     *
     * @author  Wright
     * @param   object      $oUser
     * @param   object      $oEventUser
     * @return  objcet      $oEventUserPrize
     * @date    2017-01-19
     */
    public static function createEventUserPrize($oEventUser, $oEventPrize) {
        # 当奖励为随机乱数的时候需要进行处理 start
        $sGiftValue = $oEventPrize->gift_value;
//        switch ($oEventPrize->type) {
//            case EventPrizes::TYPE_RANDOM:
//                $aGiftValue = explode(',', $sGiftValue);
//                $sGiftValue = mt_rand($aGiftValue[0], $aGiftValue[1]);
//                break;
//        }
        # 当奖励为随机乱数的时候需要进行处理 end

        // 已领取 编号(第几个已领取的)
        $iAlreadyReceivedNum = static::getByEventUserIdAndPrizeLevel($oEventUser->id, $oEventPrize->level)->count() + 1;

        $oEventUserPrize = new self;
        $oEventUserPrize->event_id = $oEventUser->event_id;
        $oEventUserPrize->event_user_id = $oEventUser->id;
//        $oEventUserPrize->event_prize_id = $oEventPrize->id;
        $oEventUserPrize->user_id = $oEventUser->user_id;
//        $oEventUserPrize->username = $oEventUser->username;
//        $oEventUserPrize->is_tester = $oEventUser->is_tester;
        $oEventUserPrize->level = $oEventPrize->level;
        $oEventUserPrize->type = $oEventPrize->type;
        $oEventUserPrize->gift_type = $oEventPrize->gift_type;
        $oEventUserPrize->gift_value = $oEventPrize->gift_value;
        
//        $oEventUserPrize->already_received_num = $iAlreadyReceivedNum;
        $oEventUserPrize->status = self::PRIZE_STATUS_CREATED;

        # 判断 gift_type 是否为奖品
        // 如果是獎品則把 $oEventPrize 的 gift_value 寫入此模型的 gift_value_text 欄位
        // 反之則把 $oEventPrize 的 gift_value 寫入此模型的 gift_value 欄位
        $oEventUserPrize->gift_value = $sGiftValue;
        $oEventUserPrize->is_captain = false;

        $bSucc = $oEventUserPrize->save();
        return $oEventUserPrize;
    }
    
    /**
     * 建立已取得的奖励
     *
     * @author Wright
     * @date   2017-03-04
     * @param  integer $iEventUserId
     * @param  integer $iEventPrizeLevel
     * @return object
     */
    public static function getByEventUserIdAndPrizeLevel($iEventUserId, $iEventPrizeLevel) {
        return static::where('event_user_id', $iEventUserId)
            ->where('level', $iEventPrizeLevel)
            ->limit(1)
            ->get();
    }
    
    /**
     * 设置状态为 审合通过
     *
     * @author  Wright
     * @return  Boolean     $bDbResult    更新结果
     * @date    2017-03-17
     */
    public function setStatusToVeriried() {
        $aExtraData = [
                'verified_at' => date('Y-m-d H:i:s'),
        ];
        $bDbResult = $this->setStatus(
                self::PRIZE_STATUS_VERIRIED, self::PRIZE_STATUS_CREATED, $aExtraData
        );

        return $bDbResult;
    }
    
    /**
     * 设置状态
     *
     * @author  Wright
     * @param   Integer     $iToStatus      目标状态
     * @param   Integer     $iFromStatus    前置状态
     * @return  Boolean     $bDbResult      更新结果
     * @date    2017-03-17
     */
    protected function setStatus($iToStatus, $iFromStatus, $aExtraData = []) {
        $aConditions = [
            'id' => ['=', $this->id],
            'status' => ['=', $iFromStatus],
        ];
        $data = [
            'status' => $iToStatus
        ];
        $data = array_merge($data, $aExtraData);

        return $this->strictUpdate($aConditions, $data) > 0;
    }

    protected function getEventIdFormattedAttribute() {
        $oEvent = Events::find($this->event_id);
        return $oEvent ? $oEvent->title : $this->event_id;
    }
}
