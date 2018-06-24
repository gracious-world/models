<?php

/**
 * Class Event - 活动条件表
 *
 */
class ZbakEventCondition extends BaseModel {
    //活动条件常量
    const CONDITION_TIME = 1;
    const CONDITION_TURNOVER = 2;
    const CONDITION_DEPOSIT = 3;
    const CONDITION_POINT = 4;
    const CONDITION_GAME_TYPE = 5;
    const CONDITION_USER = 6;
    //条件范围常量
    const TYPE_AVAILABLE_TIME = 1; //满足活动的时间周期
    const TYPE_FINISH_TIME = 2; //派奖的时间周期
    const TYPE_AVAILABLE_TURNOVER = 3; //满足参加活动条件的单注流水
    const TYPE_FINISH_TURNOVER = 4;//满足完成活动条件的总流水
    const TYPE_AVAILABLE_DEPOSIT = 5;//满足参加活动条件的充值金额
    const TYPE_FINISH_DEPOSIT = 6;//满足完成活动条件的充值金额
    const TYPE_AVAILABLE_POINT = 7;//满足参加活动条件的积分
    const TYPE_FINISH_POINT = 8;//满足完成活动条件的积分
    const TYPE_GAME = 9;//彩票类型 eg:竞彩 数字彩
    const TYPE_SERIE = 10;//系列
    const TYPE_LOTTERY = 11;//彩种
    const TYPE_LOTTERY_WAY = 12;//玩法
    const TYPE_NEW_PLAYER = 13;//新用户
    const TYPE_LEVEL = 14;//用户等级
    const TYPE_USER_IDS = 15;//指定ID
    //条件值类型常量
    const VALUE_TYPE_FIXED = 1; //固定值
    const VALUE_TYPE_STRATIFIED = 2;//分级的
    const VALUE_TYPE_PERCENT = 3;//百分比
    const VALUE_TYPE_RATE = 4;//范围值
    const VALUE_TYPE_CALCULATE = 5;//公式

    protected static $cacheLevel     = self::CACHE_LEVEL_FIRST;
    protected $table                 = 'zbak_event_conditions';
    public static $resourceName      = 'EventCondition';
    //活动条件下拉所需
    public static $validConditions     = [
      self::CONDITION_TIME => 'condition-time',
      self::CONDITION_TURNOVER => 'condition-turnover',
      self::CONDITION_DEPOSIT => 'condition-deposit',
      self::CONDITION_POINT => 'condition-point',
      self::CONDITION_GAME_TYPE => 'condition-game-type',
      self::CONDITION_USER => 'condition-user',
    ];
    //条件范围下拉所需
    public static $validConditionTypes = [
      self::TYPE_AVAILABLE_TIME => 'type-available-time',
      self::TYPE_FINISH_TIME => 'type-finish-time',
      self::TYPE_AVAILABLE_TURNOVER => 'type-available-turnover',
      self::TYPE_FINISH_TURNOVER => 'type-finish-turnover',
      self::TYPE_AVAILABLE_DEPOSIT => 'type-available-deposit',
      self::TYPE_FINISH_DEPOSIT => 'type-finish-deposit',
      self::TYPE_AVAILABLE_POINT => 'type-available-point',
      self::TYPE_FINISH_POINT => 'type-finish-point',
      self::TYPE_GAME => 'type-game',
      self::TYPE_SERIE => 'type-serie',
      self::TYPE_LOTTERY => 'type-lottery',
      self::TYPE_LOTTERY_WAY => 'type-lottery-way',
      self::TYPE_NEW_PLAYER => 'type-new-player',
      self::TYPE_LEVEL => 'type-level',
      self::TYPE_USER_IDS => 'type-user-ids',
    ];
    //条件值类型下拉所需
    public static $validConditionValueTypes = [
      self::VALUE_TYPE_FIXED => 'value-type-fixed',
      self::VALUE_TYPE_STRATIFIED => 'value-type-stratified',
      self::VALUE_TYPE_PERCENT => 'value-type-percent',
      self::VALUE_TYPE_RATE => 'value-type-rate',
      self::VALUE_TYPE_CALCULATE => 'value-type-calculate',
    ];

    protected $softDelete            = false;
    protected $fillable              = [
        'event_id',
        'event_name',
        'condition',
        'condition_type',
        'condition_value_type',
        'condition_value',
    ];

    public static $htmlSelectColumns = [
        'condition'  => 'aValidConditions',
        'condition_type'  => 'aValidConditionTypes',
        'condition_value_type' =>  'aValidConditionValueTypes'
    ];

    public static $columnForList       = [
      'event_id',
      'event_name',
      'condition',
      'condition_type',
      'condition_value_type',
      'condition_value',
    ];
    public $orderColumns               = [
        'id' => 'desc'
    ];
    public static $titleColumn         = 'name';
    public static $ignoreColumnsInEdit = [
        'author_id',
        'author',
        'editor_id',
        'editor'
    ];
    public static $ignoreColumnsInView = [
        'author',
        'editor',
    ];
    public static $rules               = [
      'condition' =>  'required|integer',
      'condition_type' =>  'required|integer',
      'condition_value_type' =>  'required|integer',
      'condition_value' =>  'required|max:1024',
      'editor_id' => 'integer',
      'editor' => 'between:1,16',
    ];

    protected function beforeValidate() {
        if ($this->id) {
            $this->editor_id = Session::get('admin_user_id');
            $this->editor    = Session::get('admin_username');
        } else {
            $this->author_id = Session::get('admin_user_id');
            $this->author    = Session::get('admin_username');
        }
        if($this->event_id && !$this->event_name){
          $this->event_name = Events::find($this->event_id)->name;
        }
       //
      //  pr($this->toArray());
      //  exit;
        return parent::beforeValidate();
    }

    public static function getValidConditions() {
      return static::_getArrayAttributes(__FUNCTION__);
    }

    public static function getValidConditionTypes() {
      return static::_getArrayAttributes(__FUNCTION__);
    }

    public static function getValidConditionValueTypes(){
      return static::_getArrayAttributes(__FUNCTION__);
    }

    protected function getConditionValueFormattedAttribute() {
        return json_decode($this->condition_value);
    }

    public static function getConditionsByEventId($iEventId){
      $oConditions = self::where('event_id','=',$iEventId)->get();
      $aConditions = [];
      if($oConditions->isEmpty()){
        return $aConditions;
      }
      foreach($oConditions as $k=>$condition){
        $tmp = [
          'condition_cn'  =>  static::translate(self::$validConditions[$condition->condition]),
          'condition_type_cn' =>  static::translate(self::$validConditionTypes[$condition->condition_type]),
          'condition_value_type_cn' =>  static::translate(self::$validConditionValueTypes[$condition->condition_value_type])
        ];
        $aConditions[$k] = array_merge($condition->toArray(),$tmp);
      }

      return $aConditions;
    }

}
