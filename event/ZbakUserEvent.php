<?php

/**
 * Class Event - 用户活动表
 *
 */
class ZbakUserEvent extends BaseModel {

    protected static $cacheLevel     = self::CACHE_LEVEL_FIRST;
    protected $table                 = 'zbak_user_events';
    public static $resourceName      = 'UserEvent';
    // 状态常量
    const STATUS_CREATED        = 0; //已报名
    const STATUS_FINISHED       = 1; //已达成
    const STATUS_VERIRIED        = 2; //已审核
    const STATUS_RECEIVED       = 3;  //已领取
    const STATUS_SENT         = 4;  //已发放
    const STATUS_REJECT         = 5; //已拒绝

    protected $softDelete            = false;

    public static $validStatuses     = [
      self::STATUS_CREATED  => 'status-created',
      self::STATUS_FINISHED => 'status-finished',
      self::STATUS_VERIRIED => 'status-verified',
      self::STATUS_REJECT   => 'status-rejected',
      self::STATUS_RECEIVED => 'status-received',
      self::STATUS_SENT     => 'status-sent',
    ];

    protected $fillable              = [
        'event_id',
        'event_name',
        'user_id',
        'username',
        'is_tester',
        'status',
        'ip',
        'current_turnover',
        'current_deposit',
        'current_point',
        'rate',
        'time_rate',
        'amount',
        'available_bill_at'
    ];

    public static $htmlSelectColumns = [
      'status'  => 'aValidStatuses',
    ];

    public static $columnForList       = [
      'id',
      'event_name',
      'username',
      'status',
      'is_tester',
      'ip',
      'current_turnover',
      'current_deposit',
      'current_point',
      'rate',
      'time_rate',
      'amount',
      'available_bill_at'
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
        'is_tester'
    ];
    public static $ignoreColumnsInView = [
        'author',
        'editor',
    ];
    public static $rules               = [
      'event_id' =>  'required|integer',
      'event_name'  =>  'max:45',
      'username'  =>  'max:50',
      'status'  =>  'required|integer|in:0,1,2,3,4',
      'is_tester' =>  'required|integer|in:0,1',
      'ip'  =>  'max:15',
      'current_turnover'  =>  'numeric',
      'current_deposit' =>  'numeric',
      'current_point' =>  'numeric',
      'rate'  =>  'required|numeric|max:1',
      'time_rate' =>  'required|numeric|max:1',
      'amount'  =>  'required|numeric',
      'available_bill_at',
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
//        pr($this->toArray());
//        exit;
        return parent::beforeValidate();
    }

    public static function getValidStatuses() {
      return static::_getArrayAttributes(__FUNCTION__);
    }

}
