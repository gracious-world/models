<?php

/**
 * Class Event - 活动表
 * @author lucda
 */
class Events extends BaseModel {

    // 计算周期常量 计算周期, 1即时, 2每日, 3 每周, 4每月, 5 活动结束后排程, 6 不处理 等待人工处理发动计算, 7根据天数限定
    const CALCULATE_CYCLE_IMMEDIATELY   = 1;
    const CALCULATE_CYCLE_DAILY         = 2;
    const CALCULATE_CYCLE_WEEK          = 3;
    const CALCULATE_CYCLE_MONTH         = 4;
    const CALCULATE_CYCLE_ENDED         = 5;
    const CALCULATE_CYCLE_AUDIT         = 6;
    const CALCULATE_CYCLE_DAYS          = 7;

    //对于领取任务的提示
    const RECEIVE_MESSAGE_EVENT_DOING = 1;
    const RECEIVE_MESSAGE_EVENT_FINISHED_CAN_RECEIVE = 2;
    const RECEIVE_MESSAGE_EVENT_FINISHED_CAN_NOT_RECEIVE = 3;
    const RECEIVE_MESSAGE_EVENT_CAN_RECEIVE = 4;
    const RECEIVE_MESSAGE_EVENT_CAN_NOT_RECEIVE = 5;
    const RECEIVE_MESSAGE_EVENT_RECEIVE_SUCCESS = 6;
    const RECEIVE_MESSAGE_EVENT_NOT_EXIST = 7;

    public static $aReceiveMessage = [
        self::RECEIVE_MESSAGE_EVENT_DOING                       =>  'receive-message-event-doing',//'活动进行中,不能领取新活动',
        self::RECEIVE_MESSAGE_EVENT_FINISHED_CAN_RECEIVE        =>  'receive-message-event-finished-can-receive',//'活动已完成,再次领取',
        self::RECEIVE_MESSAGE_EVENT_FINISHED_CAN_NOT_RECEIVE    =>  'receive-message-event-finished-can-not-receive',//'活动已完成,未达到再次领取的条件',
        self::RECEIVE_MESSAGE_EVENT_CAN_RECEIVE                 =>  'receive-message-event-can-receive',//'领取活动',
        self::RECEIVE_MESSAGE_EVENT_CAN_NOT_RECEIVE             =>  'receive-message-event-can-not-receive',//'未达到领取条件',
        self::RECEIVE_MESSAGE_EVENT_RECEIVE_SUCCESS             =>  'receive-message-event-receive-success',//'领取成功',
        self::RECEIVE_MESSAGE_EVENT_NOT_EXIST                   =>  'receive-message-event-not-exist',//'活动不存在',
    ];

    public static $aReceiveMessageForView = [
        self::RECEIVE_MESSAGE_EVENT_CAN_RECEIVE,
        self::RECEIVE_MESSAGE_EVENT_CAN_NOT_RECEIVE,
        self::RECEIVE_MESSAGE_EVENT_FINISHED_CAN_RECEIVE,
    ];

    //循环的任务
    public static $aCycleEvents = [
        self::CALCULATE_CYCLE_DAILY,
        self::CALCULATE_CYCLE_WEEK,
        self::CALCULATE_CYCLE_MONTH,
    ];

    protected static $cacheLevel     = self::CACHE_LEVEL_FIRST;
    protected $table                 = 'events';
    public static $resourceName      = 'Events';

    //计算周期常量 下拉所需
    public static $validCalculateCycle     = [
        self::CALCULATE_CYCLE_IMMEDIATELY   => 'calculate-cycle-immediately',
        self::CALCULATE_CYCLE_DAILY         => 'calculate-cycle-daily',
        self::CALCULATE_CYCLE_WEEK          => 'calculate-cycle-week',
        self::CALCULATE_CYCLE_MONTH         => 'calculate-cycle-month',
        self::CALCULATE_CYCLE_ENDED         => 'calculate-cycle-ended',
        self::CALCULATE_CYCLE_AUDIT         => 'calculate-cycle-audit',
        self::CALCULATE_CYCLE_DAYS          => 'calculate-cycle-days',
    ];

    protected $softDelete            = false;
    protected $fillable              = [
        'title',
        'identifier',
        'description',
        'calculate_cycle',
        'is_team_event',            //是否为团队任务 1是 0否
        'is_show_team_leader',      //是否显示给队长知晓 1是 0否
        'is_show_team_member',      //是否显示给会员知晓 1是 0否
        'is_receive',               //是否为领取型任务 1是 0否
        'after_receive_day_limit',  //领取后完成天数限制, 若为领取型任务一定要填写
        'status',                   //1启用 0关闭
        'is_get_mulite_prize',      //
        'start_time',               //活动开始时间
        'end_time',                 //活动结束时间
    ];

    public static $htmlSelectColumns = [
        'calculate_cycle'       => 'aValidCalculateCycle',
//        'is_get_mulite_prize'  => 'aValidConditionPrizeType',
    ];



    public static $columnForList       = [
        'id',
        'title',
        'identifier',
        'description',
        'calculate_cycle',
        'is_team_event',
        'is_show_team_leader',
        'is_show_team_member',
        'is_receive',
        'after_receive_day_limit',
        'status',
        'is_get_mulite_prize',
        'start_time',
        'end_time',
    ];
    public $orderColumns               = [
        'id' => 'desc'
    ];
    public static $titleColumn         = 'title';
    public static $ignoreColumnsInEdit = [

    ];
    public static $ignoreColumnsInView = [

    ];
    public static $rules               = [
        'title'                     => 'required|max:255',
        'identifier'                => 'max:20',
        'description'                => 'max:1024',
        'calculate_cycle'           => 'required|integer|in:1,2,3,4,5,6,7',
        'is_team_event'             => 'required|integer|in:0,1',
        'is_show_team_leader'       => 'required|integer|in:0,1',
        'is_show_team_member'       => 'required|integer|in:0,1',
        'is_receive'                => 'required|integer|in:0,1',
        'after_receive_day_limit'   => 'required|integer',
        'status'                    => 'required|integer|in:0,1',
        'is_get_mulite_prize'       => 'required|integer|in:0,1',
        'start_time'                => 'required|date',
        'end_time'                  => 'required|date',
    ];

    protected function beforeValidate() {
        return parent::beforeValidate();
    }

    public static function getValidCalculateCycle() {
      return static::_getArrayAttributes(__FUNCTION__);
    }

    /**
     * 查出所有的 启用着的,是/非 领取型,开始时间,结束时间 任务. 默认 将 获取 非领取型的任务.后续 需要 移到 service 里面
     * @author lucda
     * @date    2016-10-22
     * @return array
     */
    static function getValidEvents($iIsReceive = NULL, $iEventConditionType = NULL) {

        $sTime = date('Y-m-d H:i:s',time());
        $aConditions['status'] = ['=',1];
        $aConditions['start_time'] = ['<=',$sTime];
        $aConditions['end_time'] = ['>=',$sTime];
        if ( !is_null($iIsReceive) ) {
            $aConditions['is_receive'] = ['=',$iIsReceive];
        }

        if ($iEventConditionType) {
            $aEventConditions = EventConditions::doWhere(['type'=>['=',$iEventConditionType]])->groupBy('type','event_id')->get()->toArray();
            $aEventIds = array_pluck($aEventConditions,'event_id');
            $aConditions['id'] = ['in',$aEventIds];
        }

        return static::doWhere($aConditions)->get()->toArray();
    }

    /**
     * 检查某个 活动 是否是 有效的活动.如果是 有效的活动,返回对象.  如果 指定了日期,则检查这个日期时,活动是否有效(活动6)
     * @author lucda
     * @date    2016-12-01
     * @param $iEventId
     */
    static function isValidEvent($iEventId, $sTime = NULL) {
        if (!$sTime) {
            $sTime = date('Y-m-d H:i:s',time());
        }
        $aConditions['id'] = ['=',$iEventId];
        $aConditions['status'] = ['=',1];
        $aConditions['start_time'] = ['<=',$sTime];
        $aConditions['end_time'] = ['>=',$sTime];
        return static::doWhere($aConditions)->first();
    }


    /**
     * 获取任务的领取时间 TODO
     *
     * @author lucda
     * @date    2016-11-28
     * @return string
     */
    public function getReceivedAt() {
        $sReceivedAt = date('Y-m-d H:i:s',time());//默认的领取时间 是 当前时间
        switch ($this->calculate_cycle){
            case static::CALCULATE_CYCLE_MONTH:
                $sReceivedAt = date("Y-m",time()) . '-01 00:00:00';//月初
                break;
            default:
                break;
        }

        //如果是 活动6,则 领取时间,就是 活动的 开始时间
        if ($this->id == 6) {
            $sReceivedAt = $this->start_time;
        }

        return $sReceivedAt;
    }

    /**
     * 获取任务的过期时间 TODO
     *
     * @author lucda
     * @date    2016-10-23
     * @return string
     */
    public function getExpiredAt() {
        $sExpiredAt = '2036-12-31 00:00:00';
        switch ($this->calculate_cycle) {
            case static::CALCULATE_CYCLE_DAYS:
                $sExpiredAt = date('Y-m-d H:i:s',time() + ($this->after_receive_day_limit * 24*3600));
                break;
            case static::CALCULATE_CYCLE_MONTH:
                $sTimeBegin = date("Y-m",time()) . '-01 00:00:00';
                $sExpiredAt = date('Y-m-d',strtotime($sTimeBegin  . '+1 month -1 day')) . ' 23:59:59'; //对于 月任务,则 开始时 月初领取.. 月末到期.
                break;
            default:
                break;
        }

        //如果是 活动6,则 领取时间,就是 活动的 开始时间
        if ($this->id == 6) {
            $sExpiredAt = $this->end_time;
        }

        return $sExpiredAt;
    }

    /**
     * 获得所有有效的活动
     *
     * @return mixed
     */
    public static function findAllValidActivity() {
        $now = date('Y-m-d H:i:s');

        //缓存5分钟
        return static::remember(5)
            ->where('start_time', '<=', $now)
            ->where('end_time', '>=', $now)
            ->get();
    }
    
    /**
     * 确认活动是否在期间内且是启用状态
     * @author  Wright
     * @return  boolean     $bResult
     * @date    2017-04-10
     */
    public function checkEvent() {
        $bResult = false;
        $now = time();
        if (strtotime($this->start_time) <= $now and
            $now <= strtotime($this->end_time) and
            $this->status 
        ) {
            $bResult = true;
        }
        return $bResult;
    }


}
