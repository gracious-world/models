<?php
use  Illuminate\Support\Facades\Redis;
/**
 *
 * @author lucda
 */
class EventPrizes extends BaseModel {

    // 类别 1. 固定数值发放奖赏 2.根据投注总额决定奖赏 3.根据中奖次数决定奖赏 4.倍数 0.2倍,5倍等
    const TYPE_FIXED        = 1;
    const TYPE_TURNOVER     = 2;
    const TYPE_PRIZE_TIME   = 3;
    const TYPE_MULTIPLE     = 4;

    // 发放类别, 1礼金, 2积分 3.奖品
    const GIFT_TYPE_CASH        = 1;
    const GIFT_TYPE_POINT       = 2;
    const GIFT_TYPE_GOODS       = 3;

    const STATUS_FALSE = 0;
    const STATUS_TRUE = 1;


    // 发放对象, 1.个人任务成员(个人任务) 2.队长 3.达成任务团员 4.所有团员
    const SEND_PEOPLE_TYPE_SELF             = 1;
    const SEND_PEOPLE_TYPE_PARENT           = 2;
    const SEND_PEOPLE_TYPE_TEAM_FINISHED    = 3;
    const SEND_PEOPLE_TYPE_TEAM_ALL         = 4;

    protected static $cacheLevel     = self::CACHE_LEVEL_FIRST;
    protected $table                 = 'event_prizes';
    public static $resourceName      = 'EventPrizes';
    private static $sSendPeopleTypeCacheKey = "send_people_type";


    //条件计算类别 下拉所需
    public static $validTypes     = [
        self::TYPE_FIXED        => 'type-fixed',
        self::TYPE_TURNOVER     => 'type-turnover',
        self::TYPE_PRIZE_TIME   => 'type-prize-time',
        self::TYPE_MULTIPLE     => 'type-multiple',
    ];

    //下拉所需
    public static $validGiftTypes     = [
        self::GIFT_TYPE_CASH        => 'gift-type-cash',
        self::GIFT_TYPE_POINT       => 'gift-type-point',
        self::GIFT_TYPE_GOODS       => 'gift-type-goods',
    ];

    public static $listColumnMaps      = [
        'gift_type'    => 'gift_type_formatted',
        'type'    => 'type_formatted',
    ];

    //下拉所需
    //发放对象
    public static $validSendPeopleType     = [
        self::SEND_PEOPLE_TYPE_SELF             => 'send-people-type-self',          //1.个人任务成员(个人任务)
        self::SEND_PEOPLE_TYPE_PARENT           => 'send-people-type-parent',        //2.队长
        self::SEND_PEOPLE_TYPE_TEAM_FINISHED    => 'send-people-type-team-finished', //3.达成任务团员
        self::SEND_PEOPLE_TYPE_TEAM_ALL         => 'send-people-type-team-all',      //4.所有团员
    ];


    protected $softDelete            = false;
    protected $fillable              = [
        'event_id',
        'level',                    //任务条件等级
        'type',
        'gift_type',
        'gift_value',
        'condition_1',              //类别条件一, 投注最低领取限制 ex:10000, 中奖次数 ex:10
        'condition_2',              //类别条件二, 投注与赠送积分比例 ex:1, 每中奖满10次的赠送金额 38
        'send_people_type',         //
        'status',                   //1启用 0关闭
    ];

    public static $aLevel = [
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
    ];

    public static $htmlSelectColumns = [
        'type'  => 'aValidTypes',
        'gift_type'  => 'aValidGiftTypes',
        'send_people_type'  => 'aValidSendPeopleType',
        'level' => 'aLevel'
    ];

    public static $columnForList       = [
        'id',
        'event_id',                    //活动ID
        'level',                       //任务条件等级
        'type',                        //类型
        'gift_type',                   //奖品类型
        'gift_value',                  //奖品价值
        'condition_1',                 //类别条件一
        'condition_2',                 //类别条件二
        'send_people_type',            //发放对象, 1.个人任务成员(个人任务) 2.队长 3.达成任务团员 4.所有团员
        'status',                      //状态
    ];
    public $orderColumns               = [
        'id' => 'desc'
    ];
    public static $titleColumn         = 'event_id';
    public static $ignoreColumnsInEdit = [

    ];
    public static $ignoreColumnsInView = [

    ];
    public static $rules               = [
        'event_id'                  => 'required|integer',
        'level'                     => 'required|integer|in:1,2,3,4,5',
        'type'                      => 'required|integer|in:1,2,3,4',
        'gift_type'                 => 'required|integer|in:1,2,3',
        'gift_value'                => 'required',
        'condition_1'               => '',
        'condition_2'               => '',
        'send_people_type'          => 'required|integer|in:1,2,3,4',
        'status'                    => 'required|integer|in:0,1',
    ];


    public function getGiftTypeFormattedAttribute() {
        return __('_neweventprizes.' . static::$validGiftTypes[$this->gift_type]);
    }

    public function getTypeFormattedAttribute() {
        return __('_neweventprizes.' . static::$validTypes[$this->type]);
    }


    protected function beforeValidate() {
        return parent::beforeValidate();
    }

    public static function getValidTypes() {
      return static::_getArrayAttributes(__FUNCTION__);
    }

    public static function getValidGiftTypes() {
        return static::_getArrayAttributes(__FUNCTION__);
    }

    public static function getValidSendPeopleType() {
        return static::_getArrayAttributes(__FUNCTION__);
    }

    /**
     * 获取 event_prizes 表里面的 奖金值信息.
     * 根据 event_id  level 组合数组
     *
     * @author lucda
     * date 2016-10-25
     */
    static function eventPrizesInfoUseEventIdLevelSendPeopleType(){
        $aEventPrizesInfoUseEventIdLevelSendPeopleType = [];
        $aEventPrizes = EventPrizes::all()->toArray();
        foreach($aEventPrizes as $aEventPrize) {
            $aEventPrizesInfoUseEventIdLevelSendPeopleType[ $aEventPrize['event_id'] ] [ $aEventPrize['level'] ] [ $aEventPrize['send_people_type'] ] = $aEventPrize;
        }
        return $aEventPrizesInfoUseEventIdLevelSendPeopleType;
    }

    /**
     * 获取 某个 任务的  各个level的奖金值
     * @author lucda
     * @date    2016-12-02
     * @param $iEventId
     */
    static function getLevelGiftValueByEventId($iEventId) {
        return EventPrizes::doWhere(['event_id'=>['=',$iEventId]])->lists('gift_value', 'level');
    }

    /**
     * 获取 某个 任务的  各个level的 详细信息. 需要 这个任务,必须 队长也有奖励.队员也有奖励的情况.
     * @author lucda
     * @date    2016-12-06
     * @param $iEventId
     */
    static function getRowsByEventIdUseLevel($iEventId) {
        $aEventPrizes = EventPrizes::doWhere(['event_id'=>['=',$iEventId]])->get()->toArray();
        $aEventPrizesUseLevel = [];
        foreach ($aEventPrizes as $aEventPrize) {
            $aEventPrizesUseLevel[$aEventPrize['level']][$aEventPrize['send_people_type']] = $aEventPrize;
        }
        return $aEventPrizesUseLevel;
    }

    /**
     * 抓单一笔任务派奖资料, 根据任务id与等级做过滤
     *
     * @author Rex
     * @date   2017-01-03
     * @param  int                  $iEventId           任务id
     * @param  int                  $iLevel               等级
     * @return  EventPrizes   $oEventPrize
     */
    public static function findByEventIdAndLevel($iEventId, $iLevel) {
        $oEventPrize = EventPrizes::where('event_id', $iEventId)->
                                    where('level', $iLevel)->
                                    where('status', EventPrizes::STATUS_TRUE)->
                                    first();
        return $oEventPrize;
    }

    /**
     * get object by send_people_type
     * @return mixed
     */
    public static function getObjectsBySendPeopleType($iType) {
        $sKey = static::compileRedisCacheKey(static::$sSendPeopleTypeCacheKey . "-" . $iType);

        $redis = Redis::connection();
        if($redis->exists($sKey)) return json_decode($redis->get($sKey), true);

        $aData = static::where("send_people_type", $iType)->get();
        $redis->set($sKey,json_encode($aData));
        return $aData; 
    }

    /**
     * delete the objects store by send_people_type cache from redis
     * @author lucky
     * @date  2017-08-10        
     * @param $iType
     */
    public function deleteSendPeopleTypeCache($iType){
        $sKey  = static::compileRedisCacheKey(static::$sSendPeopleTypeCacheKey . "-" . $iType);
        $redis = Redis::connection();
        if($aKeys = $redis->keys($sKey . '*')){
            foreach ($aKeys as $sKey) {
                $redis->del($sKey);
            }
        }
        //delete event user prizes user panel data
        //when children  get points refresh the leader userpanel data
        $sKey = EventUserPrizes::compileRedisCacheKey(EventUserPrizes::$sUserPanelChildrenPrefix . "-" . $this->user_id);
        if($aKeys = $redis->keys($sKey . '*')){
            foreach ($aKeys as $sKey) {
                $redis->del($sKey);
            }
        }
    }

    /**
     * @param $oSavedModel
     * @author lucky
     * @date 2017-08-10
     * @return bool
     */
    public function afterSave($oSavedModel) {
        $this->deleteSendPeopleTypeCache($oSavedModel->send_people_type);
        return parent::afterSave($oSavedModel); // TODO: Change the autogenerated stub
    }

    
    /**
     * 取得指定活动及等级的所有奖品
     * @author Wright
     * @param  object   $oEvent
     * @param  Integer  $iLevel
     * @param  Integer  $iSendType
     * @return object
     * @date   2017-01-19
     */
    public static function getPrizeByEventAndLevel($oEvent, $iLevel) {
        return static::where('event_id', $oEvent->id)
            ->where('level', $iLevel)
            ->where('status', true)
            ->limit(1)     
            ->get();
    }
}
