<?php

/**
 * Class Event - 活动表
 *
 */
class ZbakEvents extends BaseModel {
    // 状态常量
    const STATUS_NOT_AVAILABLE = 0;
    const STATUS_SUSPENDED     = 1;
    const STATUS_AVAILABLE     = 2;
    const STATUS_ENDED         = 3;
    //奖励类型常量 现金 积分 实物
    const CASH_BONUS = 0;
    const POINT_BONUS = 1;
    const PRODUCT_BONUS = 2;
    //派奖计算类型
    const CALCULATE_TYPE_FIXED = 1; //固定值
    const CALCULATE_TYPE_STRATIFIED = 2;//分级的
    const CALCULATE_TYPE_PERCENT = 3;//百分比
    const CALCULATE_TYPE_CALCULATE = 4;//公式
    //派奖基准条件
    const CONDITION_TURNOVER = 1; //流水金额
    const CONDITION_DEPOSIT = 2; //充值金额
    const CONDITION_LEVEL = 3; //等级
    const CONDITION_PRIZE = 4; //中奖金额


    protected static $cacheLevel     = self::CACHE_LEVEL_FIRST;
    protected $table                 = 'zbak_events';
    public static $resourceName      = 'Event';
    //状态下拉所需
    public static $validStatuses     = [
        self::STATUS_NOT_AVAILABLE => 'status-not-available',
        self::STATUS_SUSPENDED     => 'status-suspended',
        self::STATUS_AVAILABLE     => 'status-available',
        self::STATUS_ENDED         => 'status-ended',
    ];
    //奖励类型下拉所需
    public static $validBonusType = [
      self::CASH_BONUS  =>  'cash-bonus',
      self::POINT_BONUS =>  'point-bonus',
      self::PRODUCT_BONUS =>  'product-bonus'
    ];
    //派奖计算类型下拉所需
    public static $validCalculateTypes = [
      self::CALCULATE_TYPE_FIXED  =>  'calculate-type-fixed',
      self::CALCULATE_TYPE_STRATIFIED =>  'calculate-type-stratified',
      self::CALCULATE_TYPE_PERCENT  =>  'calculate-type-percent',
      self::CALCULATE_TYPE_CALCULATE  =>  'calculate-type-calculate'
    ];
    //派奖基准条件下拉所需
    public static $validConditions = [
      self::CONDITION_TURNOVER => 'condition-turnover', //流水金额
      self::CONDITION_DEPOSIT => 'condition-deposit', //充值金额
      self::CONDITION_LEVEL => 'condition-level', //等级
      self::CONDITION_PRIZE => 'condition-prize' //中奖金额
    ];
    //发布类型下拉所需
    public static $validIsTask = [
      'auto-issue',
      'manual-issue'
    ];

    protected $softDelete            = false;
    protected $fillable              = [
        'identifier',
        'name',
        'is_task',
        'bonus_type',
        'bonus_calculate_type',
        'bonus_condition',
        'bonus_calculate',
        'status',
        'is_repeat',
        'auto_send',
        'need_verify',
        'verify_min_value',
        'start_time',
        'end_time'
    ];

    public static $htmlSelectColumns = [
        'status'  => 'aValidStatuses',
        'bonus_type'  => 'aBonusTypes',
        'is_task' =>  'aValidIsTask',
        'bonus_calculate_type'  =>  'aValidCalculateTypes',
        'bonus_condition' =>  'aValidConditions'
    ];

    public static $columnForList       = [
      'identifier',
      'name',
      'is_task',
      'bonus_type',
      'bonus_calculate_type',
      'bonus_condition',
      'bonus_calculate',
      'status',
      'is_repeat',
      'is_task',
      'auto_send',
      'need_verify',
      'verify_min_value',
      'start_time',
      'end_time'
    ];
    public $orderColumns               = [
        'id' => 'desc'
    ];
    public static $titleColumn         = 'name';
    public static $ignoreColumnsInEdit = [
        'author_id',
        'author',
        'editor_id',
        'editor',
        'need_verify',
        'auto_send'
    ];
    public static $ignoreColumnsInView = [
        'author',
        'editor',
    ];
    public static $rules               = [
        'name'           => 'required|between:0,45',
        'author_id'      => 'required|integer',
        'author'         => 'required|between:1,16',
        'start_time'     => 'required|date',
        'end_time'       => 'required|date',
        'bonus_calculate_type' => 'required|integer|in:1,2,3,4',
        'bonus_condition' => 'required|integer|in:1,2,3,4',
        'bonus_calculate'     => 'required|max:1024',
        'status'         => 'required|integer|in:0,1,2,3',
        'is_task'    => 'required|in:0,1',
        'is_repeat'    => 'required|in:0,1',
        'auto_send'      => 'required|in:0,1',
        'need_verify'    => 'required|in:0,1',
        'verify_min_value'  => 'required|integer',
        'editor_id'      => 'integer',
        'editor'         => 'between:1,16',
    ];

    protected function beforeValidate() {
        if ($this->id) {
            $this->editor_id = Session::get('admin_user_id');
            $this->editor    = Session::get('admin_username');
        } else {
            $this->author_id = Session::get('admin_user_id');
            $this->author    = Session::get('admin_username');
        }
        $this->need_verify = empty($this->verify_min_value) ? 0 : 1;
        $this->auto_send = 1;
        !is_null($this->status) or $this->status         = self::STATUS_NOT_AVAILABLE;
//        pr($this->toArray());
//        exit;
        return parent::beforeValidate();
    }

    protected function getBonusCalculateFormattedAttribute() {
        return json_decode($this->bonus_calculate);
    }

    /**
     * 活动是否有效
     *
     * @return bool
     */
//    public function isValidateActivity($sTime=null) {
    public function isAvailable($sTime = null) {
        if ($this->status != self::STATUS_AVAILABLE) {
            return false;
        }
        $now = empty($sTime) ? date('Y-m-d H:i:s') : $sTime;
        return $this->start_time <= $now && $this->end_time >= $now;
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

    public static function getValidStatuses() {
      return static::_getArrayAttributes(__FUNCTION__);
    }

    public static function getValidBonusType() {
      return static::_getArrayAttributes(__FUNCTION__);
    }

    public static function getValidIsTask(){
      return static::_getArrayAttributes(__FUNCTION__);
    }

    public static function getValidCalculateTypes(){
      return static::_getArrayAttributes(__FUNCTION__);
    }

    public static function getValidConditions(){
      return static::_getArrayAttributes(__FUNCTION__);
    }
}
