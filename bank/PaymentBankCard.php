<?php

/**
 * 平台银行卡
 *
 * @author white
 */
class PaymentBankCard extends BaseModel {

    protected static $cacheLevel = self::CACHE_LEVEL_FIRST;
    protected $table = 'payment_bank_cards';
    public static $sequencable = false;
    protected $softDelete = false;
    public $timestamps = false; // 取消自动维护新增/编辑时间
    const PURPOSE_RECEIVE = 1;
    const PURPOSE_PAY = 2;
    const PURPOSE_BACKUP = 3;
    public static $validPurposes = [
        self::PURPOSE_RECEIVE => 'purpose-receive',
        self::PURPOSE_PAY => 'purpose-pay',
        self::PURPOSE_BACKUP => 'purpose-backup',
    ];

    const METHOD_NETBANK = 1;
    const METHOD_MOBILE_BANK = 2;
    const METHOD_ALIPAY = 3;
    const METHOD_TENPAY = 4;
    const METHOD_ATM = 5;
    const METHOD_OTHER = 6;

    public static $validMethods = [
        self::METHOD_NETBANK => 'method-netbank',
        self::METHOD_MOBILE_BANK => 'method-mobilebank',
        self::METHOD_ALIPAY => 'method-alipay',
        self::METHOD_TENPAY => 'method-tenpay',
        self::METHOD_ATM => 'method-atm',
        self::METHOD_OTHER => 'method-other',
    ];
    
    const STATUS_AVAILABLE = 1;
    const STATUS_OVERFLOW_RECEIVE = 2;
    const STATUS_BALANCE_LOW = 3;
    const STATUS_OVERFLOW_PAY = 4;
    const STATUS_CLOSED = 5;
    
    public static $validStatuses = [
        self::STATUS_AVAILABLE => 'status-available',
        self::STATUS_OVERFLOW_RECEIVE => 'status-receive-overflow',
        self::STATUS_BALANCE_LOW => 'status-low-balance',
        self::STATUS_OVERFLOW_PAY => 'status-pay-overflow',
        self::STATUS_CLOSED => 'status-closed',
    ];
   
    protected $fillable = [
        'purpose',
        'method',
        'bank_id',
        'bank_no',
        'bank',
        'account_no',
        'owner',
        'email',
        'mobile',
        'branch',
        'province_id',
        'province',
        'city_id',
        'city',
        'username',
        'pwd',
        'ukey_pwd',
        'balance_limit',
        'daily_pay_limit',
        'status',
        'creator_id',
        'creator',
        'editor_id',
        'editor',
        'status',
        'created_at',
        'updated_at',
    ];
    public static $resourceName = 'PaymentBankcard';
    public static $columnForList = [
        'bank',
        'account_no',
        'owner',
        'email',
        'mobile',
        'status'
    ];

    public static $listColumnMaps = [
        'account_no' => 'account_no_formatted',
        'owner' => 'owner_formatted',
        'status' => 'formatted_status'
    ];

    public static $viewColumnMaps = [
        'account_no' => 'account_no_formatted',
        'owner' => 'owner_formatted',
        'status' => 'formatted_status'
    ];

    /**
     * 下拉列表框字段配置
     * @var array
     */
    public static $htmlSelectColumns = [
        'bank_id' => 'aBanks',
        'status' => 'aValidStatuses',
        'method' => 'aValidMethods',
        'purpose' => 'aValidPurposes',
        'province_id' => 'aProvinces',
        'city_id' => 'aCities',
    ];

    /**
     * order by config
     * @var array
     */
    public $orderColumns = [
        'bank' => 'asc'
    ];
    public static $titleColumn = 'account_no';
    public static $ignoreColumnsInEdit = [
        'bank',
//        'bank_id',
        'bank_no',
        'bank_identifier',
        'province_id',
        'province',
        'city',
        'creator_id',
        'creator',
        'editor_id',
        'editor'
    ];
    public static $ignoreColumnsInView = [
        'username',
        'pwd',
        'ukey_pwd',
    ];

    /**
     * the main param for index page
     * @var string
     */
    public static $mainParamColumn = 'bank';
    public static $rules = [
        'bank_id' => 'required|integer',
        'purpose' => 'required|integer|in:1,2,3',
        'method' => 'required|integer|in:1,2,3,4,5,6',
        'bank_no' => 'integer',
        'bank' => 'required|max:50',
        'account_no' => 'required',
        'owner' => 'required|max:30',
        'email' => 'max:100',
        'branch' => 'max:128',
        'province_id' => 'integer',
        'province' => 'max:20',
        'city_id' => 'integer',
        'city' => 'max:20',
        'username' => 'max:32',
        'pwd' => 'max:60',
        'ukey_pwd' => 'max:60',
        'balance_limit' => 'integer',
        'daily_pay_limit' => 'integer',
        'status' => 'required|integer|in:1,2,3,4,5',
        'creator_id' => 'required|integer',
        'creator' => 'required|max:16',
        'editor_id' => 'integer',
        'editor' => 'max:16',
        'mobile' => 'max:13',
        'status' => 'integer',
    ];

    protected function beforeValidate() {
        if ($this->bank_id) {
            $oBank = Bank::find($this->bank_id);
            $this->bank = $oBank->name;
        }
        if ($this->city_id){
            $oCity = District::find($this->city_id);
            $this->city = $oCity->name;
            $oProvince = District::find($oCity->province_id);
            $this->province_id = $oProvince->id;
            $this->province = $oProvince->name;
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

    public static function getAvailableBankcards($iBankId) {
        return static::where('bank_id', '=', $iBankId)->where('status', '=', self::STATUS_AVAILABLE)->get();
    }

    public static function getBankcardForDeposit($iBankId) {
        $oBanks = static::getAvailableBankcards($iBankId);
        $iCount = $oBanks->count();
        if ($iCount == 0) {
            return false;
        }
        if ($iCount == 1) {
            return $oBanks[0];
        }
        return $oBanks[mt_rand(0, $iCount - 1)];
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
    
    public static function getValidMethods(){
        return static::_getArrayAttributes(__FUNCTION__);
    }

    public static function getValidStatuses(){
        return static::_getArrayAttributes(__FUNCTION__);
    }

    public static function getValidPurposes(){
        return static::_getArrayAttributes(__FUNCTION__);
    }
}
