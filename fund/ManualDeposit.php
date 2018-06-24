<?php

class ManualDeposit extends BaseModel {

    const STATUS_NOT_VERIFIED = 0;
    const STATUS_VERIFIED = 1;
    const STATUS_REFUSED = 2;
    const STATUS_DEPOSIT_SUCCESS = 3;
    const STATUS_DEPOSIT_ERROR = 4;

    public static $amountAccuracy = 2;
    public static $enabledBatchAction = true;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'manual_deposits';
    protected $fillable = [
        'user_id',
        'username',
        'is_tester',
        'amount',
        'transaction_type_id',
        'note',
        'creator_id',
        'creator',
        'auditor_id',
        'auditor',
        'audited_at',
    ];
    public static $resourceName = 'ManualDeposit';

    /**
     * the columns for list page
     * @var array
     */
    public static $columnForList = [
        'id',
        'username',
        'is_tester',
        'amount',
        'transaction_description',
        'creator',
        'auditor',
        'note',
        'status',
        'created_at',
    ];
    public static $validStatuses = [
        self::STATUS_NOT_VERIFIED => 'status-not-verified',
        self::STATUS_VERIFIED => 'status-verified',
        self::STATUS_REFUSED => 'status-refused',
        self::STATUS_DEPOSIT_SUCCESS => 'status-deposit-success',
        self::STATUS_DEPOSIT_ERROR => 'status-deposit-error',
    ];
    public static $rules = [
        'user_id' => 'integer',
        'is_tester' => 'in:0, 1',
        'amount' => 'numeric|min:0',
        'transaction_type_id' => 'integer',
        'transaction_description' => 'between:0,50',
        'note' => 'between:0,100',
        'creator_id' => 'integer',
        'status' => 'in:0,1,2',
    ];
    public static $htmlNumberColumns = [];

    /**
     * 下拉列表框字段配置
     * @var array
     */
    public static $htmlSelectColumns = [
        'status' => 'aValidStatuses',
    ];
    public static $noOrderByColumns = [];

    /**
     * order by config
     * @var array
     */
    public $orderColumns = [
        'id' => 'desc',
    ];
    public static $listColumnMaps = [
        'status' => 'friendly_status',
        'amount' => 'amount_formatted',
    ];
    public static $viewColumnMaps = [
        'status' => 'friendly_status',
        'amount' => 'amount_formatted',
    ];
    public static $ignoreColumnsInView = [
        'creator_id',
        'auditor_id',
        'transaction_type_id'
    ];

    protected function beforeValidate() {
        if ($this->user_id){
            $oUser = User::find($this->user_id);
            $this->username = $oUser->username;
            $this->is_tester = $oUser->is_tester;
        }
        if ($this->transaction_type_id){
            $oTransactionType = TransactionType::find($this->transaction_type_id);
            $this->transaction_description = $oTransactionType->friendly_description;
        }
        if ($this->id){
            $this->auditor_id = Session::get('admin_user_id');
            $this->auditor = Session::get('admin_username');
        }
        else{
            $this->creator_id = Session::get('admin_user_id');
            $this->creator = Session::get('admin_username');
        }
        return parent::beforeValidate();
    }

    public function reject($aExtraData = []){
        $aAuditData = [
            'auditor_id' => Session::get('admin_user_id'),
            'auditor' => Session::get('admin_username'),
            'audited_at' => Carbon::now()->toDateTimeString()
        ];
        $aExtraData = array_merge($aExtraData, $aAuditData);
        return $this->changeStatus(self::STATUS_NOT_VERIFIED, self::STATUS_REFUSED, $aExtraData);
    }

    public function verify($aExtraData = []){
        $aAuditData = [
            'auditor_id' => Session::get('admin_user_id'),
            'auditor' => Session::get('admin_username'),
            'audited_at' => Carbon::now()->toDateTimeString()
        ];
        $aExtraData = array_merge($aExtraData, $aAuditData);
        return $this->changeStatus(self::STATUS_NOT_VERIFIED, self::STATUS_VERIFIED, $aExtraData);
    }

    public function changeStatus($iFromStatus, $iToStatus, $aExtraData = []) {
        $data = [
            'status' => $iToStatus,
        ];
        $data = array_merge($data,$aExtraData);
//        pr($data);
//        exit;
        $aConditions = [
            'id' => ['=', $this->id],
            'status' => ['=', $iFromStatus],
        ];
        return $this->strictUpdate($aConditions, $data);
    }

    protected function getFriendlyStatusAttribute() {
        return __('_manualDeposit.' . static::$validStatuses[$this->status]);
    }

    protected function getAmountFormattedAttribute() {
        return $this->getFormattedNumberForHtml('amount');
    }

    public static function getValidStatuses(){
        return static::_getArrayAttributes(__FUNCTION__);
    }
    
    public static function addDeposit($aData, & $sErrorMsg){
        $data = & static::compileDepositData($aData);
        $obj = new static($data);
        if (!$bSucc = $obj->save()){
            $sErrorMsg = $obj->getValidationErrorString();
        }
        return $bSucc;
    }
    
    public static function & compileDepositData($aData){
        $data = [
            'user_id' => $aData['user_id'],
            'amount' => $aData['amount'],
            'transaction_type_id' => $aData['transaction_type_id'],
            'note' => $aData['note'],
        ];
        return $data;
    }
}
