<?php

/**
 * 平台银行卡
 *
 * @author white
 */
class PaymentPlatformBankCard extends BaseModel {

    protected static $cacheLevel = self::CACHE_LEVEL_FIRST;
    protected $table = 'payment_platform_bank_cards';
    protected $softDelete = false;
//    public $timestamps = false; // 取消自动维护新增/编辑时间

    const STATUS_AVAILABLE = 1;
    const STATUS_CLOSED = 2;
    
    public static $validStatuses = [
        self::STATUS_AVAILABLE => 'status-available',
        self::STATUS_CLOSED => 'status-closed',
    ];
   
    protected $fillable = [
        'platform_id',
        'bank_card_id',
        'platform_name',
        'platform_identifier',
        'bank',
        'account_no',
        'owner',
        'status',
        'creator_id',
        'creator',
        'editor_id',
        'editor',
        'status',
        'created_at',
        'updated_at',
    ];
    public static $resourceName = 'PaymentPlatformBankcard';
    public static $columnForList = [
        'platform_name',
        'bank',
        'account_no',
        'owner',
        'status'
    ];

    public static $listColumnMaps = [
        'account_no' => 'account_no_formatted',
        'owner' => 'owner_formatted',
        'status' => 'formatted_status'
    ];

    public static $viewColumnMaps = [
        'status' => 'formatted_status',
    ];

    /**
     * 下拉列表框字段配置
     * @var array
     */
    public static $htmlSelectColumns = [
        'status' => 'aValidStatuses',
        'bank_card_id'  =>  'aAllBankCards',
        'platform_id'   =>  'aAllPlatforms'
    ];

    /**
     * order by config
     * @var array
     */
    public $orderColumns = [
        'id' => 'asc'
    ];
    public static $ignoreColumnsInEdit = [
        'bank',
        'account_no',
        'owner',
        'creator_id',
        'creator',
        'editor_id',
        'editor'
    ];
    public static $ignoreColumnsInView = [
        'bank_card_id',
        'platform_id',
        'creator_id',
        'editor_id'
    ];

    /**
     * the main param for index page
     * @var string
     */
    public static $mainParamColumn = 'platform_id';
    public static $rules = [
        'platform_id' => 'required|integer',
        'bank_card_id' => 'required|integer',
        'bank' => 'required|max:50',
        'account_no' => 'required|max:19',
        'owner' => 'required|max:50',
        'status' => 'required|integer|in:1,2',
        'creator_id' => 'required|integer',
        'creator' => 'required|max:16',
        'editor_id' => 'integer',
        'editor' => 'max:16',
    ];

    protected function beforeValidate() {
        if ($this->platform_id) {
            $oPaymentPlatform = PaymentPlatform::find($this->platform_id);
            $this->platform_name = $oPaymentPlatform->name;
            $this->platform_identifier = $oPaymentPlatform->identifier;
        }
        if ($this->bank_card_id){
            $oBankCard = PaymentBankCard::find($this->bank_card_id);
            $this->bank_id = $oBankCard->bank_id;
            $this->bank = $oBankCard->bank;
            $this->account_no = $oBankCard->account_no;
            $this->owner = $oBankCard->owner;
            $this->email = $oBankCard->email;
        }
        if ($this->id){
            $this->editor_id = Session::get('admin_user_id');
            $this->editor = Session::get('admin_username');
        }
        else{
            $this->creator_id = Session::get('admin_user_id');
            $this->creator = Session::get('admin_username');
        }
        return parent::beforeValidate();
    }

    public function getFormattedStatusAttribute() {
        return __('_paymentbankcard.' . static::$validStatuses[$this->status]);
    }

    public function getAccountNoFormattedAttribute(){
        return substr($this->attributes['account_no'],-4);
    }

    public function getOwnerFormattedAttribute(){
        $sOriginal = $this->attributes['owner'];
        $iLen = mb_strlen($sOriginal);
        return mb_substr($this->attributes['owner'],0,1) . str_repeat('*', $iLen - 1);
    }

//    public function getBankCardFormattedAttribute(){
//    }
    
    public static function getValidStatuses(){
        return static::_getArrayAttributes(__FUNCTION__);
    }

    public static function getAvailableBankcards($iPlatformId,$iBankId){
        return static::where('platform_id','=',$iPlatformId)->where('bank_id', '=', $iBankId)->where('status', '=', self::STATUS_AVAILABLE)->get();
    }

    public static function getBankcardForDeposit($iPlatformId,$iBankId){
        $oBanks = static::getAvailableBankcards($iPlatformId,$iBankId);
        $iCount = $oBanks->count();
        if ($iCount == 0) {
            return false;
        }
        if ($iCount == 1) {
            return $oBanks[0];
        }
        return $oBanks[mt_rand(0, $iCount - 1)];
    }
}
