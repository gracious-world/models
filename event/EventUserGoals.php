<?php

/**
 * @author lucda
 */
class EventUserGoals extends BaseModel {
    
    // 条件计算类别, 1累计充值, 2累计有效投注, 3累计获得积分, 4目前等级, 5累计盈亏, 6累计中奖注数
    const TYPE_DEPOSIT  = 1;
    const TYPE_TURNOVER = 2;
    const TYPE_POINT    = 3;
    const TYPE_LEVEL    = 4;
    const TYPE_PRIZE    = 5;
    const TYPE_WONNUM   = 6;

    const STATUS_OPENING   = 1;
    const STATUS_CLOSED  = 0;

    protected static $cacheLevel     = self::CACHE_LEVEL_FIRST;
    protected $table                 = 'event_user_goals';
    const TABLE_NAME                 = 'event_user_goals';
    public static $resourceName      = 'EventUserGoals';

    //条件计算类别 下拉所需
    public static $validType     = [
        self::TYPE_DEPOSIT      => 'type-deposit',
        self::TYPE_TURNOVER     => 'type-turnover',
        self::TYPE_POINT        => 'type-point',
        self::TYPE_LEVEL        => 'type-level',
        self::TYPE_PRIZE        => 'type-prize',
        self::TYPE_WONNUM       => 'type-wonnum',
    ];

    protected $softDelete            = false;
    protected $fillable              = [
        'event_id',
        //'batch_id',
        'event_user_id',
        'level',                    //任务条件等级
        'event_condition_id',       //任务条件ID
        'current_value',            //当前获得价值
        'type',
        'is_team_event',            //1是,0不是
        'user_id',
        'attendance_number_limit',  //此条件最低参加人数限制, 每个人都要达到条件
        'status',                   //1启用 0关闭
        'is_finished'
    ];

    public static $htmlSelectColumns = [
        'type'  => 'aValidType',
        'level' => 'aLevel',
        'status' => 'aStatus',
        'is_finished' => 'aIsFinished',
    ];

    public static $aValidType = [
        1 => '累计充值',
        2 => '累计有效投注',
        3 => '累计获得积分',
        4 => '目前等级',
        5 => '累计盈亏'
    ];

    public static $aLevel = [
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
    ];

    public static $aStatus = [
        0 => '否',
        1 => '是',
    ];

    public static $aIsFinished = [
        0 => '否',
        1 => '是',
    ];

    public static $aIsTeamEvent = [
        0 => '否',
        1 => '是',
    ];

    public static $columnForList       = [
        'id',
        'event_id',
        'event_user_id',
        //'batch_id',
        'level',
        'event_condition_id',
        'current_value',
        'type',
        'is_team_event',
        'user_id',
        'attendance_number_limit',
        'status',
        'is_finished'
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
        'event_user_id'             => 'required|integer',
        'level'                     => 'required|integer',
        'event_condition_id'        => 'required|integer',
        'current_value'             => 'required',
        'type'                      => 'required|integer|in:1,2,3,4,5,6',
        'is_team_event'             => 'required|integer|in:0,1',
        'user_id'                   => 'required',
        'attendance_number_limit'   => 'required|integer',
        'status'                    => 'required|integer|in:0,1',
        'is_finished'               => 'integer|in:0,1'
    ];

    protected function beforeValidate() {
        !is_null($this->status) or $this->status         = 1;
        return parent::beforeValidate();
    }
    

    public static function getValidType() {
      return static::_getArrayAttributes(__FUNCTION__);
    }


    /**
     * 查出个人任务 根据 用户id号 和 类型 ,获取这个 会员这个 条件计算类型的 最大level的那行. 如果指定了 event_id 数组,则 查出 这些 event_id各个 最大 level行.
     *
     * @author  lucda
     * @date    2016-10-22
     * @param $iUserId
     * @param $iType
     * @return array
     */
    static function getUserMaxValueRecordByUserIdType($iUserId, $iType, $aEventIds = []) {
        $iConfigDatabaseFetch = Config::get('database.fetch');
        DB::setFetchMode(PDO::FETCH_ASSOC);
        $sWhere = ' and status = 1 ';
        $sWhere .= ' and is_team_event = 0 ';
        $sWhere .= ' and user_id = '.$iUserId;
        $sWhere .= ' and type = '.$iType;
        if($aEventIds && is_array($aEventIds)) {
            $sEventIds = implode(',',$aEventIds);
            $sWhere .= ' and event_id in ( '.$sEventIds . ')';
        }
        $sSql = "SELECT table_main.* FROM ".static::TABLE_NAME." as table_main where current_value=(select max(current_value) from ".static::TABLE_NAME." WHERE table_main.event_id=event_id ".$sWhere.") " . $sWhere . " group by event_id,current_value";
        $aResults = DB::select($sSql);
        DB::setFetchMode($iConfigDatabaseFetch);
        $aEventUserGoals = [];
        foreach($aResults as $aResult){
            $aEventUserGoals[$aResult['id']] = $aResult;
        }
        return $aEventUserGoals;
    }


    /**
     * TODO 查出团队任务 获取团队任务的 最大值设置信息
     *
     * @author  lucda
     * @date    2016-10-22
     * @param $iUserId
     * @param $iType
     * @param array $aEventIds
     */
    static function getTeamMaxValueRecordByUserIdType($iUserId, $iType, $aEventIds = []) {

    }

    /**
     * 更新值或状态
     *
     * @author  lucda
     * @date    2016-10-22
     * @param $aParams
     * @param $iValue
     * @param bool $bStatus
     * @return bool
     */
    static function updateValueByParams($aParams, $iValue, $bIsFinishEd = false) {
        $oLevelEventUserGoal = EventUserGoals::getRecordsByParams($aParams)->first();
        if(!$oLevelEventUserGoal) {
            return false;
        }
        $oLevelEventUserGoal->current_value = $iValue;
        if($bIsFinishEd) {
            $oLevelEventUserGoal->is_finished = 1;
        }
        return $oLevelEventUserGoal->save();
    }

    /**
     * 追加新值或更新状态
     *
     * @author  lucda
     * @date    2016-10-22
     * @param $aParams
     * @param $iValue
     * @param bool $bStatus
     * @return bool
     */
    static function addValueByParams($aParams, $iValue, $bIsFinishEd = false) {
        $oLevelEventUserGoal = EventUserGoals::getRecordsByParams($aParams)->first();
        if(!$oLevelEventUserGoal) {
            return false;
        }
        $oLevelEventUserGoal->current_value = $oLevelEventUserGoal->current_value + $iValue;
        if($bIsFinishEd){
            $oLevelEventUserGoal->is_finished = 1;
        }
        return $oLevelEventUserGoal->save();
    }

    /**
     * 组合event_conditions数组的数据为 event_user_goals 使用. 某个event 必须 有 event_condition 才有效
     *
     * @author  lucda
     * @date    2016-10-23
     * @param $aEventConditions
     * @param $aEvents
     * @param $iUserId
     * @return array
     */
    static function compileEventConditions($aEventConditions, $aEvents, $iUserId) {
        $aEventUserGoals = [];
        if( !$aEventConditions || !$aEvents || ($aEventConditions && !is_array($aEventConditions)) || ($aEvents && !is_array($aEvents)) ){
            return $aEventUserGoals;
        }
        $aEventIds = array_pluck($aEvents,'id');
        $aEventIsTeamEventUseEventIds = array_pluck($aEvents,'is_team_event','id');

        //$sBatchId            =  Tool::randomStr(20);
        foreach($aEventConditions as $aEventCondition) {
            $iEventId = $aEventCondition['event_id'];
            if(!in_array($iEventId,$aEventIds)) {
                continue;
            }
            $aEventUserGoal['event_id'] = $iEventId;
            //$aEventUserGoal['batch_id'] = $sBatchId;
            $aEventUserGoal['level'] = $aEventCondition['level'];
            $aEventUserGoal['event_condition_id'] = $aEventCondition['id'];
            $aEventUserGoal['current_value'] = 0;
            $aEventUserGoal['type'] = $aEventCondition['type'];

            /*
            //2016-11-29 lucda 目前只有队长可以领取 团队任务. 则 领取人就还是当前 会员(队长)
            if($aEventIsTeamEventUseEventIds[$iEventId]){
                $oUser = User::find($iUserId);
                $iParentId = $oUser->parent_id;
                $aEventUserGoal['user_id'] = $iParentId ? $iParentId : $iUserId;
            }else{
                $aEventUserGoal['user_id'] = $iUserId;
            }
            */
            $aEventUserGoal['user_id'] = $iUserId;//2016-11-29 lucda 目前只有队长可以领取 团队任务. 则 领取人就还是当前 会员(队长)

            $aEventUserGoal['attendance_number_limit'] = $aEventCondition['attendance_number_limit'];
            $aEventUserGoal['is_team_event'] = $aEventIsTeamEventUseEventIds[$iEventId];
            $aEventUserGoals[] = $aEventUserGoal;
        }
        return $aEventUserGoals;
    }

    /**
     * 将数组添加到表中
     *
     * @author  lucda
     * @date    2016-10-23
     * @param $aEventUserGoals
     * @param $iEventUserId
     * @return bool
     */
    static function addEventUserGoals($aEventUserGoals, $iEventUserId) {
        if(!$iEventUserId || !$aEventUserGoals || ($aEventUserGoals && !is_array($aEventUserGoals))){
            return false;
        }
        foreach ($aEventUserGoals as $aEventUserGoal) {
            $oEventUserGoal = new EventUserGoals();
            $oEventUserGoal->fill($aEventUserGoal);
            $oEventUserGoal->event_user_id = $iEventUserId;
            $bSucc = $oEventUserGoal->save();
            if( !$bSucc ){
                return false;
            }
        }
        return true;
        //return static::insert($aEventUserGoals);
    }

    /**
     * 获取会员任务积分
     *
     * @author  sara
     * @date    2016-10-23
     * @param $aEventUserGoals
     * @return bool
     */
    static function getUserEventsGoals($iUserId) {
        return static::where('user_id', "=", $iUserId)
                    ->where('type', '=', 3)
                    ->get();
    }


    /**
     * 查出 符合到 event_user_prizes 里面的 的 event_user_goals 有哪些
     *
     * @author lucda
     * @date   2016-10-25
     * @param  $oEvent
     * @param  $aConditions
     * @return  array
     */
    static function eventUserGoalsToPrize($oEvent, $aConditions = []) {
        $iEventId = $oEvent->id;
        $aCondition['event_id'] = ['=',$iEventId];
        $aConditions = $aConditions + $aCondition;
        $oEventUserGoals = EventUserGoals::doWhere($aConditions)->get();
        return $oEventUserGoals;
    }

    /**
     * 使用者所有任务条件过滤 及 等级达成确认 . 将资料库内所有条件完成度进行检查 只要在同一等级内,有一个条件没达成则此任务等级算失败 .
     * 如果是 计奖所需,则要 进行 过期时间 的 过滤
     *
     * @author lucda
     * @date   2016-10-22
     * @param  $oEventUserGoals
     * @return  array
     */
    static function eventUserGoalsToPrizeInfos($oEventUserGoals, $bTest = false, $bFinish = true) {
        $aEventUserGoalsToPrizeInfos = [];

        $sTime = date('Y-m-d H:i:s');

        foreach ($oEventUserGoals as $key=>$oEventUserGoal) {
            $iEventUserId = $oEventUserGoal->event_user_id;
            if (!$bTest) {
                //首先对 得到的 $oEventUserGoals 进行处理..检查 每个 event_user_goals 对应的 event_user_id 用户 结束时间到了没?如果没到,啥也不操作.
                $oEventUser = EventUsers::find($iEventUserId);
                if(!$oEventUser){
//                    Log::info("calculate event user prize command error:event_user_goals exists, event_user does not exist:" . json_encode($oEventUserGoal->toArray()) . "\n");
                    continue;
                }
                $sExpiredAt = $oEventUser->expired_at;//这行 任务的过期时间.
                $iStatus = $oEventUser->status;
                if( ($iStatus!=EventUsers::STATUS_DOING) || ($sExpiredAt > $sTime) ) {
                    continue;//说明 这行的那会员任务 不是进行中,,,或者 还没过期. 不对此行进行 计奖操作.
                }
            }

            $iLevel = $oEventUserGoal->level;
            $bIsFinished = $oEventUserGoal->is_finished;
            $iUserId = $oEventUserGoal->user_id;
            $aEventUserGoalsToPrizeInfos[$iEventUserId]['userId'] = $iUserId;
            $aEventUserGoalsToPrizeInfos[$iEventUserId]['eventId'] = $oEventUserGoal->event_id;

            //数组不存在此等级则建立一个
            if(!isset($aEventUserGoalsToPrizeInfos[$iEventUserId]['levelIsFinisheds'][$iLevel])) {
                $aEventUserGoalsToPrizeInfos[$iEventUserId]['levelIsFinisheds'][$iLevel] = true;
            }
            //若是任一条件没达成, 则在达成数组内此等级更新为未达成
            if ($bIsFinished == false) {
                $aEventUserGoalsToPrizeInfos[$iEventUserId]['levelIsFinisheds'][$iLevel] = false;
            }

            if ($bFinish) {
                $aEventUserData['status'] = EventUsers::STATUS_FINISHED;
                $aEventUserData['finished_at'] = date('Y-m-d H:i:s',time());
                $aEventUserConditions['id'] = ['=', $iEventUserId];
                $aEventUserConditions['status'] = EventUsers::STATUS_DOING;
                EventUsers::doWhere($aEventUserConditions)->update($aEventUserData);//将 event_users 里面对应的行 status 改成 2

                $aGoalsData['status'] = EventUserGoals::STATUS_CLOSED;
                $aGoalsConditions['event_user_id'] = ['=',$iEventUserId];
                $aGoalsConditions['event_id'] = ['=',$oEventUserGoal->event_id];
                $aGoalsConditions['status'] = ['=',EventUserGoals::STATUS_OPENING];
                EventUserGoals::doWhere($aGoalsConditions)->update($aGoalsData);//将 event_user_goals 里面对应的行 status 改成 0
            }

        }
        return $aEventUserGoalsToPrizeInfos;
    }

    /**
     * 用户等级发生变化时,将 event_user_goals 表里面 此用户的 status=1 的 那 各 level<=$iGrade 改成 finished
     * 此函数目前是在 等级升降 的 command 里面调用
     * @author lucda
     * @date 2016-10-28
     *
     * @param $iUserId
     * @param $iGrade
     */
    static function updateFinishedByUserIdGrade($iUserId, $iOldGrade, $iNewGrade) {

        //首先检查这个会员有 正在进行中的 和等级有关的 任务不,如果没有.则先领取任务
        $oEventService = new EventService();
        $oEventService->addEventsToEventUserGoalsEventUser($iUserId,EventUserGoals::TYPE_LEVEL);

        if($iOldGrade >= $iNewGrade) {
            return true;
        }

        //先删除 小于等于 $iOldGrade 的行.
        $aConditions['level'] = ['<=', $iOldGrade];
        $aConditions['is_team_event'] = ['=', 0]; //非团队任务
        $aConditions['user_id'] = ['=', $iUserId];
        $aConditions['type'] = ['=', self::TYPE_LEVEL]; //等级类型
        $aConditions['status'] = EventUsers::STATUS_DOING; //进行中的
        static::doWhere($aConditions)->delete();

        //然后 更改为 finished
        $aData['is_finished'] = 1; //此 level 已完成
        $aConditions['level'] = ['<=', $iNewGrade];
        return static::doWhere($aConditions)->update($aData);
    }

    /**
     * 获取大于等于 某等级的,当前值大于0 的 所有的 行. 比如,获取 用户id是10,任务id是2 的, level大于等于2的, 任务值大于0的 所有的行. 可能获取到这个会员这个任务,level是2,3,4,5的所有行.
     * @author lucda
     * @date    2016-12-01
     * @param $iEventUserId
     * @param int $iLevel
     * @param null $iStatus
     * @param int $iCurrentValue
     * @return object
     */
    static function getRowsByEventUserIdLevelStatus($iEventUserId, $iLevel = 0, $iStatus = NULL, $iCurrentValue = 0) {
        $aConditionsGoal['event_user_id'] = ['=', $iEventUserId];
        $aConditionsGoal['level'] = ['>=', $iLevel];
        $aConditionsGoal['current_value'] = ['>', $iCurrentValue];
        if ($iStatus) {
            $aConditionsGoal['status'] = ['=', $iStatus];
        }
        return EventUserGoals::doWhere($aConditionsGoal)->get();
    }


    /**
     * 根据 一组数据.获取 这组数据中, 各个类型 的 最大值. 比如 获取 累计充值的最大值, 累计投注的最大值. 主要是对 getRowsByEventUserIdLevelStatus 这个函数返回值 进行 各类型取最大值
     * array[累计充值]=数值  array[累计有效投注]=数值   1累计充值, 2累计有效投注, 3累计获得积分, 4等级, 5累计盈亏
     * @author lucda
     * @date    2016-12-01
     * @param $oEventUserGoals
     * @return array
     */
    static function getMaxTypeCurrentValue($oEventUserGoals) {
        $aGoals = [];
        foreach ($oEventUserGoals as $oEventUserGoal) {
            if (!isset($aGoals[$oEventUserGoal->type])) {
                $aGoals[$oEventUserGoal->type] = 0; //设定达成目标内类型初始值
            }
            if ($oEventUserGoal->current_value > $aGoals[$oEventUserGoal->type]) {
                $aGoals[$oEventUserGoal->type] = $oEventUserGoal->current_value; //取得达成目标内同类型最大值
            }
        }
        return $aGoals;
    }

    /**
     * 根据 event_user_id , level 数组值,获取 对应的 奖金记录信息
     * @author lucda
     * @date    2016-12-07
     * @param $aEventUserIds
     * @param $aLevels
     * @return array
     */
    static function compileUseUserIdsLevels($aEventUserIds, $aLevels) {
        $aEventUserGoalsEventByUserIdsLevels = EventUserGoals::doWhere(['event_user_id'=>['in', $aEventUserIds], 'level'=>['in', $aLevels]])->get()->toArray();
        $aEventUserGoalsEventUseUserIdsLevels = [];
        foreach ($aEventUserGoalsEventByUserIdsLevels as $aEventUserGoalEventByUserIdsLevels) {
            $iEventUserId = $aEventUserGoalEventByUserIdsLevels['event_user_id'];
            $iLevel = $aEventUserGoalEventByUserIdsLevels['level'];
            $aEventUserGoalsEventUseUserIdsLevels[$iEventUserId][$iLevel] = $aEventUserGoalEventByUserIdsLevels;
        }
        return $aEventUserGoalsEventUseUserIdsLevels;
    }

     /**
     * 取得指定event_user_id 的 event_user_goals 记录
     *
     * @author   Wright
     * @date     2017-02-06
     * @return   object
     */
    public static function getByEventUserId($sEventUserId) {
        return EventUserGoals::where('event_user_id', $sEventUserId)->get();
    }

    /**
     * 建立活动使用者进度
     *
     * @author Wright
     * @date     2017-03-07
     *
     * @param  object $oEvent
     * @param  object $oEventUser
     * @param  object $oEventCondition
     * @param  string $sCurrentValue
     * @return object
     */
    public static function createEventUserGoal($oEvent, $oEventUser, $oEventCondition, $sCurrentValue = 0) {
        $oEventUserGoal = new EventUserGoals();
        $oEventUserGoal->event_id = $oEventUser->event_id;
        $oEventUserGoal->event_user_id = $oEventUser->id;
        $oEventUserGoal->level = $oEventCondition->level;
        $oEventUserGoal->event_condition_id = $oEventCondition->id;
        $oEventUserGoal->current_value = $sCurrentValue;
        $oEventUserGoal->type = $oEventCondition->type;
        $oEventUserGoal->is_team_event = $oEvent->is_team_event;
        $oEventUserGoal->user_id = $oEventUser->user_id;
//        $oEventUserGoal->username = $oEventUser->username;
        $oEventUserGoal->attendance_number_limit = $oEventCondition->attendance_number_limit;
//        $oEventUserGoal->lottery_id = $oEventCondition->lottery_id;
        $oEventUserGoal->status = EventUserGoals::STATUS_OPENING;

//        # 立即判定注册时间
//        if ($oEventCondition->type == EventConditions::TYPE_REGISTERED) {
//            $bCheckResult = self::checkRegisterTime($oEventCondition, $sCurrentValue);
//            if ($bCheckResult) {
//                $oEventUserGoal->is_finished = true;
//            }
//            $oEventUserGoal->current_value = strtotime($sCurrentValue);
//        }

        $bDbResult = $oEventUserGoal->save();
        if ($bDbResult) {
            return $oEventUserGoal;
        } else {
            return null;
        }
    }
    
    /**
     * 获取最大的当前植元素
     *
     * @author lucky
     * @date   2017-07-27
     *
     * @param $iEventUserId
     * @param $iType
     *
     * @return mixed
     */
    public static function getMaxCurrentValueObj($iEventUserId, $iType) {
        return EventUserGoals::where("event_user_id", $iEventUserId)
                ->where("type", $iType)
                ->orderby("current_value", "desc")
                ->first();
    }
    
    /**
     * 初始化用户进度
     * @author lucky
     * @date 2017-07-27        
     * @param $iEventUserId
     *
     * @return mixed
     */
    public static function initialStatus($iEventId=11, $iEventUserId) {
        return static::where('event_id', $iEventId)
                ->where('event_user_id', $iEventUserId)
                ->update(['is_finished' => 0, 'current_value' => 0]);
    }
    
    /**
     * 用活动使用者及进度类型取得event_user_goal
     *
     * @author Wright
     * @date   2017-03-08
     *
     * @param  EventUsers $oEventUser
     * @param  Integer    $iType
     *
     * @return Colletion
     */
    public static function getByEventUserAndType($oEventUser, $iType) {
        return static::where('event_user_id', $oEventUser->id)
            ->where('type', $iType)
            ->lockForUpdate()
            ->get();
    }
    
}
