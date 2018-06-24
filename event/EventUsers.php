<?php

/**
 * @author lucda
 */
class EventUsers extends BaseModel {

    // 状态 1进行中,2完成,3到期未完成
    const STATUS_DOING                  = 1;
    const STATUS_FINISHED               = 2;
    const STATUS_ENDED_NOT_FINISHED     = 3;

    protected static $cacheLevel     = self::CACHE_LEVEL_FIRST;
    protected $table                 = 'event_users';
    public static $resourceName      = 'EventUsers';

    //条件计算类别 下拉所需
    public static $validStatus     = [
        self::STATUS_DOING                  => 'status-doing',             //进行中
        self::STATUS_FINISHED               => 'status-finished',          //完成
        self::STATUS_ENDED_NOT_FINISHED     => 'status-ended-not-finished',//未完成
    ];

    public static $aIsTeamEvent     =
        [
        0 => '否',
        1 => '是',
    ];

    protected $softDelete            = false;
    protected $fillable              = [
        'event_id',
        'is_team_event',                    //是否为团队任务 1是 0否
        'username',                         //用户名字
        'user_id',                          //若为团队任务, 此为队长id.若为个人任务,此为用户本人id号
        'received_at',                      //任务领取时间
        'finished_at',                      //任务完成时间
        'expired_at',                       //任务过期时间
        'status',
    ];

    public static $htmlSelectColumns = [
        'status'  => 'aValidStatus',
    ];



    public static $columnForList       = [
        'id',
        'event_id',
        'is_team_event',
        'user_id',
        'received_at',
        'finished_at',
        'expired_at',
        'status',
    ];
    public $orderColumns               = [
        'id' => 'desc'
    ];
    public static $titleColumn         = 'event_id';
    public static $ignoreColumnsInEdit = [

    ];

    public static $listColumnMaps      = [
        'status'    => 'status_formmatted',
    ];


    public static $ignoreColumnsInView = [

    ];
    public static $rules               = [
        'event_id'                  => 'required|integer',
        'is_team_event'             => 'required|integer|in:0,1',
        'user_id'                   => 'required|integer',
        'received_at'               => 'required|date',
        'finished_at'               => 'date',
        'expired_at'                => 'required|date',
        'status'                    => 'required|integer|in:1,2,3',
    ];

    protected function beforeValidate() {
        if (!$this->username) {
            $oUser                     = User::find($this->user_id);
            $this->username            = $oUser->username;
        }
        !is_null($this->status) or $this->status         = self::STATUS_DOING;
        return parent::beforeValidate();
    }

    public static function getValidStatus() {
      return static::_getArrayAttributes(__FUNCTION__);
    }

    public function getStatusFormmattedAttribute() {
        return __('_neweventuser.' . static::$validStatus[$this->status]);
    }

    /**
     * 查出这会员参与的 个人任务和队长领取的团队任务
     *
     * @author lucda
     * @date   2016-10-22
     * @param $iUserId
     * @param $iType
     * @return array
     */
    static function getEventsByUserIdType($iUserId, $iStatus=NULL, $iType=NULL) {
        $aEventUsers['self'] = [];
        $aEventUsers['team'] = [];
        $oUser = UserUser::find($iUserId);
        if(!$oUser){
            return $aEventUsers;
        }

        if($iType && !EventConditions::isValidType($iType)) {
            return $aEventUsers;
        }

        //直接把会员本身的所有任务 和 团队所有任务 查出来. 任务是正在进行中的
        $iParentId = $oUser->parent_id;
        if($iStatus) {
            $oTmpEventUsers = static::where(function ($oQuery) use ($iUserId,$iStatus) {
                $oQuery->where('user_id',$iUserId)->where('status',$iStatus)->where('is_team_event', 0);
            })->orwhere(function ($oQuery) use ($iParentId,$iStatus) {
                if($iParentId) {
                    $oQuery->where('user_id', $iParentId)->where('is_team_event', 1)->where('status',$iStatus);
                }
            })->get();
        } else {
            $oTmpEventUsers = static::where(function ($oQuery) use ($iUserId) {
                $oQuery->where('user_id',$iUserId)->where('is_team_event', 0);
            })->orwhere(function ($oQuery) use ($iParentId) {
                if($iParentId) {
                    $oQuery->where('user_id', $iParentId)->where('is_team_event', 1);
                }
            })->orderBy('id','asc')->get();
        }
        $aTmpEventUsers = $oTmpEventUsers->toArray();//这个会员的所有任务

        $aEventUsersSelf = $aEventUsersTeamTmp = [];
        foreach($aTmpEventUsers as $aTmpEventUser) {
            $aTmpEventUser['is_team_event'] ? $aEventUsersTeamTmp[$aTmpEventUser['event_id']] = $aTmpEventUser : $aEventUsersSelf[$aTmpEventUser['event_id']] = $aTmpEventUser;
        }

        $aEventUsersTeam = $aEventUsersTeamTmp;//目前是所有的团队任务,所有的会员都可以参加.  $aEventUsersTeamTmp为自己所在团队的所有团队任务 . 从所有团队任务中..过滤出自己加入的任务 或者 全团队人都参加的任务 $aEventUsersTeam 为 过滤后的 结果

        $aEventUsers['self'] = $aEventUsersSelf;
        $aEventUsers['team'] = $aEventUsersTeam;
        if(!$iType) {
            return $aEventUsers;
        }

        $aEventIds = array_pluck($aEventUsersSelf+$aEventUsersTeam,'event_id');
        $aEventConditions = EventConditions::getRecordsByEventIdsType($aEventIds,$iType,$bGroupBy = true);
        if(!$aEventConditions) {
            $aEventUsers['self'] = $aEventUsers['team'] = [];
            return $aEventUsers;
        }

        $aValidEventIds = array_pluck($aEventConditions,'event_id');
        $aEventUsersSelf = array_only($aEventUsersSelf,$aValidEventIds);
        $aEventUsersTeam = array_only($aEventUsersTeam,$aValidEventIds);
        $aEventUsers['self'] = $aEventUsersSelf;
        $aEventUsers['team'] = $aEventUsersTeam;
        return $aEventUsers;
    }

    /**
     * 获取会员的所有任务
     *
     * @author sara
     * @date   2016-12-15
     * @param $iUserId
     * @return bool
     */
    static function getEventUsersByUserId($iUserId) {
        $aEventUsers = [];
        $oUser = UserUser::find($iUserId);
        if(!$oUser) {
            return $aEventUsers;
        }
        $iParentId = $oUser->parent_id;
        $oTmpEventUsers = static::where(function ($oQuery) use ($iUserId) {
            $oQuery->where('user_id',$iUserId);
        })->orwhere(function ($oQuery) use ($iParentId) {
            if($iParentId) {
                $oQuery->where('user_id', $iParentId)->where('is_team_event', 1);
            }
        })->orderBy('id','asc')->get();
        $aEventUsers = $oTmpEventUsers->toArray();//这个会员的所有任务

        return $aEventUsers;
    }
    /**
     * 给 这个会员 所有参与的任务 更新 数值.
     *
     * @author lucda
     * @date   2016-10-22
     * @param $iUserId
     * @param $iType
     * @param $iValue
     * @param $aEventUsers
     * @return bool
     */
    static function updateEventUserGoalsValueByUserIdEventUsers($iUserId,$iType,$iValue,$aEventUsers) {
        $oUser = UserUser::find($iUserId);
        if(!$oUser) {
            return false;
        }

        if(!EventConditions::isValidType($iType)) {
            return false;
        }
        if(!$aEventUsers) {
            return true;
        }
        $aEventUsersSelf = $aEventUsers['self'];
        if(!$aEventUsersSelf) {
            return true;
        }
        $aEventIdsSelf = array_pluck($aEventUsersSelf , 'event_id');

        $aEventUserGoals = EventUserGoals::getUserMaxValueRecordByUserIdType($iUserId,$iType,$aEventIdsSelf);
        if(!$aEventUserGoals) {
            return true;
        }
        $aEventConditions = EventConditions::getRecordsByEventIdsType($aEventIdsSelf,$iType);
        $aCompileEventConditions = EventConditions::compileEventConditionsUseEventIdLevel($aEventConditions);
        $aEventConditionsResults = $aCompileEventConditions['result'];
        $aEventConditionsLevels = $aCompileEventConditions['level'];
        //循环更新 current_value 值
        foreach($aEventUserGoals as $iId=>$aEventUserGoal) {
            $iEventId = $aEventUserGoal['event_id'];
            $iLevel = $aEventUserGoal['level'];
            $iCurrentValue = $aEventUserGoal['current_value'];
            $iNewValue = $iCurrentValue+$iValue;

            if( !isset( $aEventConditionsResults[$iEventId][$iLevel][$iType] ) ) {
                continue;//说明 event_condition_goals 里面有 这个任务这个级别这个类型,但是在 event_conditions 里面没有
            }

            $aEventConditionsLevel = $aEventConditionsLevels[$iEventId];
            sort($aEventConditionsLevel);
            $iNextLevel = $iLevel;
            foreach($aEventConditionsLevel as $iEventConditionLevel) {
                if($iEventConditionLevel > $iLevel){
                    $iNextLevel = $iEventConditionLevel;
                    break;
                }
            }
            $iTargetValue = $aEventConditionsResults[$iEventId][$iLevel][$iType]['target_value'];
            if($iNewValue <= $iTargetValue || $iNextLevel == $iLevel) {
                $oEventUserGoal = EventUserGoals::find($iId);
                $oEventUserGoal->current_value = $iNewValue;
                if($iNewValue >= $iTargetValue) {
                    $oEventUserGoal->is_finished = 1;
                }
                $oEventUserGoal->save();
                continue;
            }
            $iLevelMax = $iLevel;
            foreach($aEventConditionsLevel as $iEventConditionLevel) {
                if($iEventConditionLevel <= $iLevel){
                    continue;
                }
                $iLevelTargetValue = $aEventConditionsResults[$iEventId][$iEventConditionLevel][$iType]['target_value'];
                if($iLevelTargetValue >= $iNewValue){
                    $iLevelMax = $iEventConditionLevel;
                    break;
                }
                $iLevelMax = $iEventConditionLevel;
            }
            foreach($aEventConditionsLevel as $iEventConditionLevel) {
                if($iEventConditionLevel > $iLevelMax){
                    break;
                }
                $iLevelTargetValue = $aEventConditionsResults[$iEventId][$iEventConditionLevel][$iType]['target_value'];
                $aParams['event_condition_id'] = $aEventConditionsResults[$iEventId][$iEventConditionLevel][$iType]['id'];
                $aParams['user_id'] = $iUserId;
                if($iEventConditionLevel < $iLevelMax) {
                    EventUserGoals::updateValueByParams($aParams,$iLevelTargetValue,$bIsFinished=true);
                }else{
                    $bIsFinished = $iNewValue == $iLevelTargetValue ? true : false;
                    EventUserGoals::addValueByParams($aParams,$iNewValue,$bIsFinished);
                }
            }
        }

        //虽然 $aEventUsers 包含 当前会员的 个人任务 和 他队长领取的团队任务 ,但是目前 团队任务 直接commands里面更新 数值. 而不是时时 对 进行 current_value 的数值的更新.
        //$aEventUserGoals = EventUserGoals::getTeamMaxValueRecordByUserIdType($iUserId,$iType,$aEventIds);

        return true;
    }

    /**
     * 更新 某个会员的 这个类型 的 所有符合条件的 任务的 值
     *
     * @author lucda
     * @date   2016-10-22
     * @param $iUserId
     * @param $iType
     * @param $iValue
     * @return bool
     */
    static function updateEventUserGoalsValueByUserIdTypeValue($iUserId,$iType,$iValue) {
        if($iValue < 0 || !is_numeric($iValue)) {
            return false;
        }
        $oUser = UserUser::find($iUserId);
        if(!$oUser) {
            return false;
        }
        if(!EventConditions::isValidType($iType)) {
            return false;
        }
        $aEventUsers = static::getEventsByUserIdType($iUserId,static::STATUS_DOING,$iType);//获取 这个会员的 所有的 正在进行中的任务.. 可能存在的情况是, 此会员作为队长的任务. 此会员独自的任务.

        if(! ($aEventUsers['self'] + $aEventUsers['team']) ) {
            return true;
        }
        return static::updateEventUserGoalsValueByUserIdEventUsers($iUserId,$iType,$iValue,$aEventUsers);
    }

    /**
     * 将 这些任务 加入到 event_users 里面,同时 相关的表也插入数据
     *
     * @authoer lucda
     * @date    2016-10-23
     * @param $iUserId
     * @param $aEvents
     * @return bool
     */
    static function addEventsToGoalsEventUser($iUserId, $aEvents) {
        if(!$aEvents || ($aEvents && !is_array($aEvents))) {
            return true;
        }
        $aEventIds = array_pluck($aEvents,'id');
        $aEventConditions = EventConditions::doWhere(['event_id'=>['in',$aEventIds]])->get()->toArray();
        //if(!$aEventConditions){
            //return true; //有的活动,比如 活动6 ,没有 conditions
        //}

        //将条件插入到 event_condition_goals 里面, 及 任务到event_users 里面 .
        $aEventUserGoals = $aEventConditions ? EventUserGoals::compileEventConditions($aEventConditions, $aEvents, $iUserId) : [];
        $aEventUsers = static::compileEvents($aEvents, $iUserId);
        //if($aEventUserGoals && $aEventUsers){ //有的活动,比如活动6 没有 conditions
        return static::addEventUserAndGoals($aEventUsers, $aEventUserGoals);
        //}
    }

    /**
     * 组合events数组的数据为 event_users 使用
     *
     * @author  lucda
     * @date    2016-10-23
     * @param $aEvents
     * @param $iUserId
     * @return array
     */
    static function compileEvents($aEvents,$iUserId) {
        $aEventUsers = [];
        if( !$aEvents || ($aEvents && !is_array($aEvents)) ) {
            return $aEventUsers;
        }
        foreach($aEvents as $aEvent) {
            $iEventId = $aEvent['id'];
            $oEvent = Events::find($iEventId);

            $aEventUser['event_id'] = $iEventId;
            $aEventUser['is_team_event'] = $aEvent['is_team_event'];

            /*
            //2016-11-29 lucda 目前只有队长可以领取 团队任务. 则 领取人就还是当前 会员(队长)
            if($aEvent['is_team_event']){
                $oUser = User::find($iUserId);
                $iParentId = $oUser->parent_id;
                $aEventUser['user_id'] = $iParentId ? $iParentId : $iUserId;
            }else{
                $aEventUser['user_id'] = $iUserId;
            }
            */

            $aEventUser['user_id'] = $iUserId;//2016-11-29 lucda 目前只有队长可以领取 团队任务. 则 领取人就还是当前 会员(队长)

            $aEventUser['received_at'] = $oEvent->getReceivedAt();//date("Y-m-d H:i:s",time());
            $aEventUser['expired_at'] = $oEvent->getExpiredAt();
            $aEventUsers[] = $aEventUser;
        }
        return $aEventUsers;
    }

    /**
     * 将数组添加到表中
     *
     * @author  lucda
     * @date    2016-10-23
     * @param $aEventUsers
     * @return bool
     */
    static function addEventUser($aEventUsers) {
        if(!$aEventUsers || ($aEventUsers && !is_array($aEventUsers))) {
            return true;
        }
        foreach ($aEventUsers as $aEventUser) {
            $oEventUser = new EventUsers();
            $oEventUser->fill($aEventUser);
            $bSucc = $oEventUser->save();
            if( !$bSucc ) {
                return false;
            }
        }
        return true;
        //return static::insert($aEventUsers);
    }

    /**
     * 获取进行中任务
     *
     * @author  sara
     * @date    2016-10-24
     * @param $iUserId
     * @return array
     */
    static function getUserEventList($iUserId, $iStatus) {
        return static::where("user_id","=",$iUserId)
            ->where('status', '=', $iStatus)
            ->orderby("id","desc")
            ->limit(5)
            ->get();
    }


    /**
     * 将数组添加 event_users 表中,每加入一行,同时 event_user_goals 表里面加入对应的行
     * 使用 event_users 里面的 id号关联
     *
     * @author  lucda
     * @date    2016-10-23
     * @param $aEventUsers
     * @param $aEventUserGoals
     * @return bool
     */
    static function addEventUserAndGoals($aEventUsers,$aEventUserGoals) {
        /*
        //有的 活动 没有 conditions 比如 活动6, 也就 没有 evetn_user_goals  2017-01-04
        if(!$aEventUserGoals || !$aEventUsers || ($aEventUsers && !is_array($aEventUsers))){
            return false;
        }
        */
        $aEventUserGoalsUseEventId = [];
        foreach ($aEventUserGoals as $aEventUserGoal) {
            $aEventUserGoalsUseEventId[$aEventUserGoal['event_id']][] = $aEventUserGoal;
        }
        foreach ($aEventUsers as $aEventUser) {
            $oEventUser = new EventUsers();
            $oEventUser->fill($aEventUser);
            DB::connection()->beginTransaction();
            $bSucc = $oEventUser->save();
            $iEventUserId = $oEventUser->id;

            $aEventUserGoalsThisEvent = isset($aEventUserGoalsUseEventId[$aEventUser['event_id']]) ? $aEventUserGoalsUseEventId[$aEventUser['event_id']] : [];
            if (!$aEventUserGoalsThisEvent) {
                DB::connection()->commit();
            } else {
                $bSuccGoals = EventUserGoals::addEventUserGoals($aEventUserGoalsThisEvent, $iEventUserId);
                if ($bSucc && $bSuccGoals) {
                    DB::connection()->commit();
                } else {
                    DB::connection()->rollback();
                }
            }
        }
        return true;
    }


    /**
     * 根据 活动id号,会员id号,获取最新的那行记录. 可能为 空.默认查询的是  非团队任务
     * @author lucda
     * @date    2016-11-29
     * @param $iEventId
     * @param $iUserId
     * @return object
     */
    static function getNewestRowByEventIdUserId($iEventId, $iUserId, $iIsTeamEvent = 0, $iStatus = NULL) {
        $aConditions['event_id'] = ['=',$iEventId];
        $aConditions['user_id'] = ['=',$iUserId];
        $aConditions['is_team_event'] = ['=',$iIsTeamEvent];
        if ($iStatus) {
            $aConditions['status'] = ['=',$iStatus];
        }
        return static::doWhere($aConditions)->orderBy('id','desc')->first();
    }

    /**
     * 根据 结束日期,获取 某个会员,某个活动的 event_users 那行信息. 目前主要是用于 升级任务获取结束日期是上个月最后一秒的那行
     * @author lucda
     * @date    2016-12-01
     * @param $sExpiredAt
     * @param $iEventId
     * @param $iUserId
     * @return object
     */
    static function getRowByExpiredAtEventIdUserId($sExpiredAt, $iEventId, $iUserId) {
        return EventUsers::doWhere([ 'expired_at'=>['=',$sExpiredAt],'event_id'=>['=',$iEventId],'user_id'=>['=',$iUserId] ])->first();
    }

    /**
     * 根据 活动id号 , 已经过了 结束日期的 , 获取符合条件的任务. 可能为 空.默认查询的是  团队任务
     * @author lucda
     * @date    2016-12-06
     * @param $iEventId
     * @param int $iIsTeamEvent
     * @param null $iStatus
     * @param bool $bTest
     * @return mixed
     */
    static function getRowsByExpiredAtEventId($iEventId, $iIsTeamEvent = 1, $iStatus = NULL, $bTest = false) {
        $sDateNow = Date::todayDateTime();
        if (!$bTest) {
            $aConditions['expired_at'] = ['<=', $sDateNow];//如果不是测试,则查询已经过了 结束日期的...如果是 测试,则不用管 结束日期.
        }
        $aConditions['event_id'] = ['=', $iEventId];
        $aConditions['is_team_event'] = ['=', $iIsTeamEvent];
        if ($iStatus) {
            $aConditions['status'] = ['=',$iStatus];
        }
        return static::doWhere($aConditions)->orderBy('id','desc')->get();
    }

    /**
     * 根据 活动id号 , 在结束日期内 , 获取符合条件的任务. 可能为 空.默认查询的是  个人任务
     * @author simon
     * @date    2017-12-26
     * @param $iEventId
     * @param int $iIsTeamEvent
     * @param null $iStatus
     * @param bool $bTest
     * @return mixed
     */
    static function getRowsInExpiredByEventId($iEventId, $iIsTeamEvent = 0, $iStatus = 2, $bTest = false) {
        $sDateNow = Date::todayDateTime();
        if (!$bTest) {
            $aConditions['expired_at'] = ['>=', $sDateNow];//如果不是测试,则查询已经过了 结束日期的...如果是 测试,则不用管 结束日期.
        }
        $aConditions['event_id'] = ['=', $iEventId];
        $aConditions['is_team_event'] = ['=', $iIsTeamEvent];
        if ($iStatus) {
            $aConditions['status'] = ['=',$iStatus];
        }
        return static::doWhere($aConditions)->orderBy('id','desc')->get();
    }
    /**
     * 根据 活动, 添加个人 活动记录 活动6
     * @param $oEvent
     * @param $iUserId
     * @return object
     */
    static function firstOrCreateFromEvent($oEvent, $iUserId) {
        $aAttributes = [
            'event_id'    => $oEvent->id,
            'is_team_event'   => 0,
            'user_id' => $iUserId,
            'received_at' => $oEvent->start_time,
            'expired_at' => $oEvent->end_time,
        ];
        return EventUsers::firstOrCreate($aAttributes);

    }

    /**
     * 取得进行中的event_user  ( Warning: 过期时就算状态为 doing 也算没参与!!!!! )
     *
     * @author    Wright
     * @param     object $oUser
     * @param     object $oEvent
     * @date      2017-01-19
     * @return    object
     */
    public static function findDoingUser($oUser, $oEvent) {
        return static::where('event_id', $oEvent->id)
                ->where('user_id', $oUser->id)
                ->where('status', EventUsers::STATUS_DOING)
                //->where('expired_at', '>', date('Y-m-d H:i:s'))
                ->orderBy('created_at', 'desc')
                ->first();
    }
    public static function findDoingUserOnEvent10($oUser, $oEvent) {
        return static::where('event_id', $oEvent->id)
            ->where('user_id', $oUser->id)
            ->where('status', EventUsers::STATUS_DOING)
            ->orderBy('created_at', 'desc')
            ->first();
    }
    /**
     * 建立进行中 event user
     *
     * @author Wright
     * @date   2017-03-07
     *
     * @param  object $oEvent
     * @param  object $oUser
     *
     * @return object $oEventUser
     */
    public static function createDoingEventUser($oEvent, $oUser) {
        $oEventUser                = new EventUsers();
        $oEventUser->event_id      = $oEvent->id;
        $oEventUser->is_team_event = $oEvent->is_team_event;
        $oEventUser->user_id       = $oUser->id;
//        $oEventUser->username      = $oUser->username;

        $sExpiredAt               = static::getEventUserExpireTime($oEvent);
        $sRecievedAt              = static::getEventUserReceiveTime($oEvent, $sExpiredAt);
        $oEventUser->received_at  = $sRecievedAt;
        $oEventUser->expired_at   = $sExpiredAt;
//        $oEventUser->expired_date = Carbon::parse($oEventUser->expired_at)->toDateString();

//        $oEventUser->is_tester = $oUser->is_tester;
        $oEventUser->status    = EventUsers::STATUS_DOING;
        $bDbResult             = $oEventUser->save();
        if ($bDbResult) {
            return $oEventUser;
        }
        else {
            return null;
        }
    }
 /**
     * 获取新建活动使用者 过期时间
     *
     * @author Wright
     * @date   2017-03-07
     * @param  object       $oEvent
     * @return string       $sExpiredAt
     */
    private static function getEventUserExpireTime($oEvent) {

        // default event end time
        $sExpiredAt = $oEvent->end_time;

        switch ($oEvent->calculate_cycle) {
            case Events::CALCULATE_CYCLE_DAYS:      // 每几天
                $sExpiredAt = date('Y-m-d H:i:s', time() + ($oEvent->after_receive_day_limit * 24 * 3600));
                break;

            case Events::CALCULATE_CYCLE_MONTH:   // 每月
                $sExpiredAt = Carbon::today()->endOfMonth()->toDateTimeString();
                break;

            case Events::CALCULATE_CYCLE_WEEK:      // 每周
                $sExpiredAt = Carbon::today()->endOfWeek()->toDateTimeString();
                break;

            case Events::CALCULATE_CYCLE_DAILY:     // 每日
                $sExpiredAt = Carbon::today()->endOfDay()->toDateTimeString();
                break;
        }

        return $sExpiredAt;
    }

/**
     * 利用过期时间回推 接取时间
     *
     * @author Wright
     * @date   2017-05-03
     * @param  object       $oEvent
     * @param  string       $sExpiredAt
     * @return string       $sRecievedAt
     */
    private static function getEventUserReceiveTime($oEvent, $sExpiredAt) {

        // default event end time
        $sRecievedAt = null;
        $oExpiredAt  = Carbon::parse($sExpiredAt);

        switch ($oEvent->calculate_cycle) {
            case Events::CALCULATE_CYCLE_DAYS:      // 每几天
                $oExpiredAt->addSecond()->subDays($oEvent->after_receive_day_limit);
                break;

            case Events::CALCULATE_CYCLE_MONTH:   // 每月
                $oExpiredAt->addSecond()->subMonth();
                break;

            case Events::CALCULATE_CYCLE_WEEK:      // 每周
                $oExpiredAt->addSecond()->subWeek();
                break;

            case Events::CALCULATE_CYCLE_DAILY:     // 每日
                $oExpiredAt->addSecond()->subDay();
                break;
        }

        // 如果在活动开始时间之前就 使用 活动开始时间
        if ($oExpiredAt->timestamp < strtotime($oEvent->start_time)) {
            $sRecievedAt = $oEvent->start_time;
        }
        else {
            $sRecievedAt = $oExpiredAt->toDateTimeString();
        }

        return $sRecievedAt;
    }
    
    /**
     * 确认活动会员是否有效
     *
     * @author  Wright
     * @date    2017-04-21
     * @param   object     $oEventUser
     * @return  boolean
     */
    public static function countcheckDoingUser($oEventUser) {
        if (is_null($oEventUser)) {
            return false;
        }
        if ($oEventUser->status != self::STATUS_DOING) {
            return false;
        }
        $tNow         = time();
        $tExpiredTime = strtotime($oEventUser->expired_at);
        if ($tExpiredTime <= $tNow) {
            return false;
        }

        return true;
    }

    protected function getEventIdFormattedAttribute() {
        $oEvent = Events::find($this->event_id);
        return $oEvent ? $oEvent->title : $this->event_id;
    }


    /**
     * 检查完成活动的人数
     *
     * @author  simon
     * @date    2017-04-21
     * @param   object     $oEventUser
     * @return  boolean
     */
    public static function countEventUser($event_id,$amount) {
        $bCheck = true;
        $oQuery = static::where('event_id', '=',$event_id);
        $oQuery->where('created_at', '>=', date('Y-m-d',time())." 00:00:00");
        $oQuery->where('created_at', '<', date('Y-m-d',strtotime("+1 day"))." 00:00:00");
        $oQuery->where('status', '=', 1);
        $aUserProfits = $oQuery->get(['id']);
//        $queries = DB::getQueryLog();
//        $last_query = end($queries);
////        pr($last_query);
//        dd(count($aUserProfits)>1000);
        if(count($aUserProfits)>$amount){
            $bCheck =  false;
        }
        return $bCheck;
    }

}
