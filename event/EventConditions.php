<?php

/**
 * @author lucda
 */
class EventConditions extends BaseModel {

    // 条件计算类别, 1累计充值, 2累计有效投注, 3累计获得积分, 4目前等级, 5累计盈亏, 6累计中奖注数
    const TYPE_DEPOSIT  = 1;
    const TYPE_TURNOVER = 2;
    const TYPE_POINT    = 3;
    const TYPE_LEVEL    = 4;
    const TYPE_PRIZE    = 5;
    const TYPE_WONNUM   = 6;

    protected static $cacheLevel     = self::CACHE_LEVEL_FIRST;
    protected $table                 = 'event_conditions';
    public static $resourceName      = 'EventConditions';

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
        'level',                    //任务条件等级
        'type',
        'target_value',             //条件达成值
        'start_value',              //起始值 比如 每月升级礼包任务 3级的 起始值是 2
        'attendance_number_limit',  //此条件最低参加人数限制, 每个人都要达到条件
        'status',                   //1启用 0关闭
    ];

    public static $htmlSelectColumns = [
        'type'  => 'aValidType',
        'status' => 'aStatus',
    ];

    public static $aValidType = [
        1 => '累计充值',
        2 => '累计有效投注',
        3 => '累计获得积分',
        4 => '目前等级',
        5 => '累计盈亏',
        6 => '累计中奖注数',
    ];

    public static $aStatus = [
        0 => '否',
        1 => '是',
    ];

    public static $aLevel = [
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
    ];

    public static $columnForList       = [
        'id',
        'event_id',
        'level',
        'type',
        'target_value',
        'start_value',
        'attendance_number_limit',
        'status',
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
        'level'                     => 'required|integer',
        'type'                      => 'required|integer|in:1,2,3,4,5,6',
        'target_value'              => 'required|integer',
        'start_value'               => 'integer',
        'attendance_number_limit'   => 'required|integer|in:0,1',
        'status'                    => 'required|integer|in:0,1',
    ];

    protected function beforeValidate() {
        return parent::beforeValidate();
    }

    public static function getValidType() {
      return static::_getArrayAttributes(__FUNCTION__);
    }

    /**
     * @param $iType
     * @return bool
     * 检查 类型是否 是有效类型
     */
    static function isValidType($iType) {
        return isset(static::$validType[$iType]) ? true : false;
    }

    /**
     * @param $aEventIds
     * @param $iType
     * @return array
     * 根据 event_id数组 及 type ,从 event_id数组中,过滤出符合type的行. 根据 type 来 group by
     */
    static function getRecordsByEventIdsType($aEventIds, $iType, $bGroupBy = false) {
        if(!$aEventIds) {
            return [];
        }
        if($bGroupBy) {
            return static::isValidType($iType) ? static::doWhere(['event_id'=>['in',$aEventIds],'type'=>['=',$iType]])->groupBy('type','event_id')->get()->toArray() : [];
        }
        return static::isValidType($iType) ? static::doWhere(['event_id'=>['in',$aEventIds],'type'=>['=',$iType]])->get()->toArray() : [];
    }

    static function compileEventConditionsUseEventIdLevel($aEventConditions) {
        $aResult = [];
        if ( !$aEventConditions || !is_array($aEventConditions) ) {
            return $aResult;
        }
        foreach ($aEventConditions as $aEventCondition) {
            $aResult['result'][$aEventCondition['event_id']][$aEventCondition['level']][$aEventCondition['type']] = $aEventCondition;
            $aResult['level'][$aEventCondition['event_id']][] = $aEventCondition['level'];
        }
        return $aResult;
    }

    /**
     * 获取某个 活动的 level 和 target_value 的对应关系
     * @author lucda
     * @date    2016-12-07
     * @param $iEventId
     * @return array
     */
    static function eventConditonsTargetValueUserLevel($iEventId) {
        return EventConditions::doWhere(['event_id' => ['=', $iEventId]])->lists('target_value', 'level');//查询 condition 表,获得 各个 level 的 target_value
    }
    /**
     * 获取某个 活动的 level 返回 target_value
     * @author lucda
     * @date    2016-12-07
     * @param $iEventId
     * @return array
     */
    static function getTargetValueByEventidAndLevel($iEventId,$level,$type) {
        return EventConditions::doWhere(['event_id' => ['=', $iEventId],'level' => ['=',$level],'type'=>['=',$type]])
            ->lists('target_value');//查询 condition 表,获得 各个 level 的 target_value
    }
    
    /**
     * 取得指定活动编号的活动条件
     * @author  Wright
     * @date    2017-02-06
     * @param   Integer $iEventId
     * @return  Collection
     */
    public static function getByEventId($iEventId) {
        return static::where('event_id', $iEventId)
                ->where('status', true)
                ->get();
    }
    
    /**
     * 取得 指定活动编号及指定类型 活动条件
     * @author  Wright
     * @date    2017-03-08
     * @param   Integer $iEventId
     * @return  Collection
     */
    public static function getByEventUserAndType($iEventId, $iType) {
        return static::where('event_id', $iEventId)
                ->where('status', true)
                ->where('type', $iType)
                ->get();
    }


}
