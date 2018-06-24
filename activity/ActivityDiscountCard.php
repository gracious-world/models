<?php

/**
 * Class ActivityDiscountCard - 优惠卡表
 *
 */
class ActivityDiscountCard extends BaseModel {

    const STATUS_NOT_ACTIVATED = 0;
    const STATUS_ACTIVATED     = 1;
    const STATUS_INVALID = 2;

    protected static $cacheLevel     = self::CACHE_LEVEL_FIRST;
    protected $table                 = 'activity_discount_card';
    public static $amountAccuracy    = 2;
    public static $validStatuses     = [
        self::STATUS_NOT_ACTIVATED => 'status-not-activated',
        self::STATUS_ACTIVATED     => 'status-activated',
        self::STATUS_INVALID     => 'status-invalid'
    ];
    protected $softDelete            = true;
    protected $fillable              = [
      'identifier',
      'activity_id',
      'activity_name',
      'bonus_id',
      'user_id',
      'username',
      'status',
      'face_value',
      'amount',
      'available_lottery',
      'start_at',
      'end_at'
    ];
    public static $resourceName      = 'ActivityDiscountCard';
    public static $htmlSelectColumns = [
        'status'  => 'aValidStatuses',
    ];
    //mark
    public static $columnForList       = [
      'identifier',
      'activity_name',
      'bonus_id',
      'username',
      'status',
      'face_value',
      'amount',
      'available_lottery',
      'start_at',
      'end_at'
    ];
    public $orderColumns               = [
        'id' => 'desc'
    ];
    public static $titleColumn         = 'identifier';
    public static $ignoreColumnsInEdit = [
        'author_id',
        'author',
        'editor_id',
        'editor',
    ];
    public static $ignoreColumnsInView = [
        'author',
        'editor',
    ];
    public static $rules               = [
      'identifier' => 'required|max:50',
      'activity_id' => 'required|integer',
      'activity_name' =>  'required|max:45',
      'bonus_id'  =>  'required|integer',
      'user_id' =>  'required|integer',
      'username'  =>  'required|max:45',
      'status'  =>  'required|in:0,1,2',
      'face_value'    =>  'required|numeric',
      'amount'  =>  'required|numeric',
      'available_lottery' =>  'max:255',
      'start_at' =>  'date',
      'end_at'  =>  'date'
    ];

    protected function beforeValidate() {
        $this->identifier = empty($this->identifier)?uniqid($this->user_id):$this->identifier;
//        pr($this->toArray());
//        exit;
        return parent::beforeValidate();
    }

    public static function getValidStatuses() {
        return static::_getArrayAttributes(__FUNCTION__);
    }

    /**
     * 根据彩种获取用户可使用金额
     * @param  [type] $iLotteryId [description]
     * @param  [type] $iUserId    [description]
     * @return [type]             [description]
     */
    public static function getUserAvailableTotalAmountByLottery($iLotteryId,$iUserId){
      $oDiscountCard = self::where('user_id','=',$iUserId)->where('status','=',self::STATUS_ACTIVATED)->get();
      if($oDiscountCard->isEmpty()){
        return 0;
      }

    }
}
