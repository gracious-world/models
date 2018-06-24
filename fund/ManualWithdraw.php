<?php
/**
 * Created by PhpStorm.
 * User: damon
 * Date: 9/28/15
 * Time: 2:45 PM
 */


class ManualWithdraw extends BaseModel {

    const STATUS_NOT_VERIFIED = 0;
    const STATUS_WITHDRAW_SUCCESS = 1;
    const STATUS_REFUSED = 2;
    const STATUS_WITHDRAW_ERROR = 3;

    public static $amountAccuracy = 2;
    public static $enabledBatchAction = true;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'manual_withdrawals';
    protected $fillable = [
        'user_id',
        'username',
        'is_tester',
        'amount',
        'transaction_type_id',
        'note',
        'creator',
        'creator_id',
        'auditor_id',
        'auditor',
        'audited_at',
    ];
    public static $resourceName = 'ManualWithdraw';

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
        'note',
        'status',
        'created_at',
    ];
    public static $validStatuses = [
        self::STATUS_NOT_VERIFIED => 'status-not-verified',
        self::STATUS_REFUSED => 'status-refused',
        self::STATUS_WITHDRAW_SUCCESS => 'status-withdraw-success',
        self::STATUS_WITHDRAW_ERROR => 'status-withdraw-error',
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
    public static $htmlNumberColumns = [
    ];

    /**
     * 下拉列表框字段配置
     * @var array
     */
    public static $htmlSelectColumns = [
        'status' => 'aValidStatuses',
    ];
    public static $noOrderByColumns = [
    ];

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
    public function changeStatus($iFromStatus, $iToStatus) {
        $data = [
            'status' => $iToStatus,
            'auditor_id' => Session::get('admin_user_id'),
            'auditor' => Session::get('admin_username'),
            'audited_at' => Carbon::now()->toDateTimeString()
        ];
        $aConditions = [
            'id' => ['=', $this->id],
            'status' => ['=', $iFromStatus],
        ];
        return $this->strictUpdate($aConditions, $data);
    }

    protected function getFriendlyStatusAttribute() {
        return __('_manualwithdraw.' . static::$validStatuses[$this->status]);
    }

    protected function getAmountFormattedAttribute() {
        return $this->getFormattedNumberForHtml('amount');
    }

    public static function getValidStatuses(){
        return static::_getArrayAttributes(__FUNCTION__);
    }

    public static function addwithdraw($aData, & $sErrorMsg){
        $data = & static::compilewithdrawData($aData);
        $obj = new static($data);
        if (!$bSucc = $obj->save()){
            $sErrorMsg = $obj->getValidationErrorString();
            return false;
        }
        return $obj;
    }

    public static function & compilewithdrawData($aData){
        $data = [
            'user_id' => $aData['user_id'],
            'amount' => $aData['amount'],
            'transaction_type_id' => $aData['transaction_type_id'],
            'note' => $aData['note'],
        ];
        return $data;
    }
}
