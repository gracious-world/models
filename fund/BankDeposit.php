<?php
/**
 * Created by PhpStorm.
 * User: damon
 * Date: 9/15/15
 * Time: 5:09 PM
 */

class BankDeposit extends BaseModel {

    protected $table = 'bank_deposits';

    /**
     * 软删除
     * @var boolean
     */
    protected $softDelete = false;
    public $timestamps = true; // 取消自动维护新增/编辑时间
    protected $fillable = [
        'user_id',
        'username',
        'realname',
        'is_tester',
        'is_agent',
        'user_parent',
        'user_forefather_ids',
        'top_agent_id',
        'top_agent',
        'bank_id',
        'bank_no',
        'bank',
        'bank_identifier',
        'amount',
        'order_no',
        'ip',
        'postscript',
        'payer_name',
        'service_order_no',
        'service_time',
        'service_bank_seq_no',
        'accept_card_num',
        'accept_email',
        'accept_acc_name',
        'fee',
        'pay_time',
        'status',
        'error_msg',
        'mode',
        'put_at',
        'commission_sent_at',
        'commission',
        'status_commission',
        'note',
        'platform_id',
        'platform',
        'platform_identifier'
    ];
    public static $resourceName = 'BankDeposit';

    /**
     * the columns for list page
     * @var array
     */
    public static $columnForList = [
        'id',
        'username',
        'realname',
        'is_tester',
        'top_agent',
        'user_parent',
        'created_at',
        'amount',
        'platform',
        'bank_id',
//        'bank_no',
        'order_no',
        'payer_name',
        'put_at',
        'status',
        'service_order_no',
        'service_time',
        'accepter',
        'auditor',
    ];
    public static $totalColumns = [
        'amount',
    ];
    public static $htmlNumberColumns = [
        'amount' => 2,
        'real_amount' => 2,
        'fee' => 2,
    ];
    public static $listColumnMaps = [
        'status' => 'formatted_status',
        'is_tester' => 'friendly_is_tester',
        'created_at' => 'friendly_apply_time',
        'put_at' => 'friendly_put_time',
        'amount' => 'amount_formatted',
        'service_time' => 'friendly_pay_time'
    ];
    public static $viewColumnMaps = [
        'status' => 'formatted_status',
        'amount' => 'amount_formatted',
        'real_amount' => 'real_amount_formatted',
        'fee' => 'fee_formatted',
    ];

    /**
     * 下拉列表框字段配置
     * @var array
     */
    public static $htmlSelectColumns = [
        'status' => 'validStatuses',
        'bank_id' => 'aBanks',
        'aPaymentPlatform' => 'aPaymentPlatform',
    ];
    public static $noOrderByColumns = [
        'add_game_money_time'
    ];

    /**
     * 状态：申请成功
     * @var int
     */
    const STATUS_RECEIVED = 0;

    /**
     * 状态：已受理
     * @var int
     */
    const STATUS_ACCEPTED = 1;

    /**
     * 状态：已提交凭证,待受理审核
     */
    const STATUS_WAITING_VERIFY = 2;

    /**
     * 状态：审核受理
     */
    const STATUS_VERIFY_ACCEPTED = 3;
    /**
     * 状态：校验成功，等待加币
     * @var int
     */
    const STATUS_CHECK_SUCCESS = 4;

    /**
     * 状态：加币成功,已完成
     * @var int
     */
    const STATUS_SUCCESS = 5;

    /**
     * 状态：加游戏币失败
     * @var int
     */
    const STATUS_ADD_FAIL = 6;

    /**
     * 状态：失败
     */
    const STATUS_FAIL = 7;

    /**
     * 状态：拒绝
     */
    const STATUS_VERIFY_REJECTED = 8;

    /**
     * 异常充值
     */
    const STATUS_EXCEPTION = 9;

    /**
     * 状态：关闭
     */
    const STATUS_CLOSED = 99;


    const COMMISSION_STATUS_WAITING = 0;
    const COMMISSION_STATUS_SENT = 2;

    public static $validStatuses = [
        self::STATUS_RECEIVED => 'apply-received',
        self::STATUS_ACCEPTED => 'accepted',
        self::STATUS_CHECK_SUCCESS => 'waiting-load',
        self::STATUS_SUCCESS => 'success',
        self::STATUS_FAIL => 'fail',
        self::STATUS_ADD_FAIL => 'add-failture',
        self::STATUS_CLOSED => 'closed',
        self::STATUS_WAITING_VERIFY => 'wait-verify',
        self::STATUS_VERIFY_ACCEPTED => 'verify-accepted',
        self::STATUS_VERIFY_REJECTED => 'verify-rejected',
        self::STATUS_EXCEPTION => 'exception-deposit',
    ];

    /**
     * order by config
     * @var array
     */
    public $orderColumns = [
        'id' => 'desc',
    ];

    /**
     * the main param for index page
     * @var string
     */
    public static $mainParamColumn = 'user_id';
    public static $titleColumn = 'account';
    public static $rules = [
        'user_id' => 'required|integer',
        'username' => 'required|between:1,50',
        'realname' => 'max:30',
        'top_agent' => 'between:1,50',
        'bank_id' => 'integer',
        'amount' => 'regex:/^[0-9]+(.[0-9]{1,2})?$/',
        'order_no' => 'between:1,64',
        'postscript' => 'between:1,32',
        'payer_name' => 'min:0',
        'service_order_no' => 'between:1,50',
        'collection_bank_id' => 'integer',
        'accept_card_num' => 'numeric',
        'accept_email' => 'between:1,200',
        'accept_acc_name' => 'between:1,19',
        'real_amount' => 'regex:/^[0-9]+(.[0-9]{1,2})?$/',
        'fee' => 'regex:/^[0-9]+(.[0-9]{1,2})?$/',
        'pay_time' => 'date',
        'accept_bank_address' => 'between:1,100',
        'status' => 'in:0,1,2,3,4,5,6,7,8,9,10',
        'error_msg' => 'between:1,255',
        'mode' => 'in:0,1,2',
        'break_url' => 'between:1,1000',
    ];
    public static $aReportType = [
        ReportDownloadConfig::TYPE_DEPOSIT_THE_THIRD_PART => self::STATUS_SUCCESS,
    ];
    // 编辑表单中隐藏的字段项
    public static $aHiddenColumns = [];
    // 表单只读字段
    public static $aReadonlyInputs = [];
    public static $ignoreColumnsInView = [
        'mode',
        'merchant_key',
        'merchant_code',
        'sign',
        'break_url',
        'real_amount',
    ];
    public static $ignoreColumnsInEdit = [];

    //状态修改规则
    public static $statusChangeRules = [
        self::STATUS_RECEIVED => [self::STATUS_ACCEPTED,self::STATUS_ADD_FAIL],
        self::STATUS_ACCEPTED => [self::STATUS_ACCEPTED,self::STATUS_WAITING_VERIFY,self::STATUS_FAIL,self::STATUS_EXCEPTION,self::STATUS_ADD_FAIL,self::STATUS_SUCCESS],
        self::STATUS_CHECK_SUCCESS => [self::STATUS_CHECK_SUCCESS,self::STATUS_SUCCESS],
        self::STATUS_SUCCESS => [],
        self::STATUS_ADD_FAIL => [],
        self::STATUS_FAIL => [],
        self::STATUS_WAITING_VERIFY => [self::STATUS_VERIFY_ACCEPTED],
        self::STATUS_VERIFY_ACCEPTED => [self::STATUS_EXCEPTION,self::STATUS_CHECK_SUCCESS,self::STATUS_ADD_FAIL,self::STATUS_VERIFY_REJECTED],
        self::STATUS_VERIFY_REJECTED => [self::STATUS_WAITING_VERIFY,self::STATUS_FAIL,self::STATUS_ADD_FAIL],
        self::STATUS_EXCEPTION => []
    ];

    protected function afterSave($oSavedModel) {
        $this->deleteCache($this->order_no);
        return parent::afterSave($oSavedModel);
    }

    /**
     * 添加新订单，并返回该订单实例
     * @param array $aInitData
     * @return \Deposit
     */
    public static function createDeposit(array $aInitData) {
        $oDeposit = new static($aInitData);
        if (!$bSucc = $oDeposit->save()) {
            return false;
        }
        return $oDeposit;
    }

    protected function beforeValidate() {
        $this->commission = $this->countCommission();
        return parent::beforeValidate();
    }

    /**
     * checkStatus 检查当前状态是否允许变更之后状态
     * @param $iToStatus 将要改变的状态值
     * @return boolean
     */
    public function checkStatus($iToStatus){
        return in_array($iToStatus,self::$statusChangeRules[$this->status]);
    }

    /**
     * updateStatus 更新提现记录状态
     * @param  Int $iToStatus   将要改变的状态值
     * @param  Array $aExtraData  额外需要更新的数据
     * @return boolean
     */
    public function updateStatus($iToStatus, array $aExtraData = []) {
        if(!$this->checkStatus($iToStatus)){
            return FALSE;
        }
        if (!$this->exists) {
            return FALSE;
        }
        if (!empty($aExtraData) && is_array($aExtraData)) {
            $this->fill($aExtraData);
        }
        $aExtraData['status'] = $iToStatus;
        $iAffectRows = static::where('id', '=', $this->id)->where('status', '=', $this->status)->where('status', '<>', $iToStatus)->update($aExtraData);
        $iAffectRows <= 0 or $this->status = $iToStatus;
        return $iAffectRows > 0;
    }

    /**
     * 设置已受理状态
     */
    public function setAccected($iAdminUserId) {
        $oAdminUser = AdminUser::find($iAdminUserId);
        $data = [
            'accepter_id' => $iAdminUserId,
            'accepter' => $oAdminUser->username,
            'accepted_at' => date('Y-m-d H:i:s'),
        ];
        return $this->updateStatus(self::STATUS_ACCEPTED,$data);
    }

    public function setVerifyAccected($iAdminUserId) {
        $oAdminUser = AdminUser::find($iAdminUserId);
        $data = [
            'auditor_id' => $iAdminUserId,
            'auditor' => $oAdminUser->username,
            'verified_at' => date('Y-m-d H:i:s'),
        ];
        return $this->updateStatus(self::STATUS_VERIFY_ACCEPTED,$data);
    }

    /**
     * 设置状态：添加游戏币失败
     * @param array $aExtraData  额外需要更新的数据
     * @return boolean
     */
    public function setAddFail(array $aExtraData = []) {
        return in_array($this->status, [self::STATUS_ACCEPTED, self::STATUS_RECEIVED, self::STATUS_VERIFY_REJECTED]) && $this->_updateStatus(self::STATUS_ADD_FAIL, $aExtraData);
    }

    /**
     * 向任务队列追加充值任务
     * @param int $id
     * @return bool
     */
    public static function addDepositTask($id) {
        return BaseTask::addTask('DoBankDeposit', ['id' => $id], 'deposit');
    }
//
    public function addCommissionTask() {
        if (!$this->commission || !$this->user_parent) {
            return true;
        }
        return BaseTask::addTask('SendDepositCommission', ['id' => $this->id], 'deposit');
    }


    /**
     * [getSerialNumberShortAttribute 获取序列号的截断格式]
     * @return int [type [4位序列号的截断格式]
     */
    protected function getCompanyOrderNumShortAttribute() {
        return substr($this->company_order_num, 0, 4) . '...';
    }

    /**
     * [getFormattedStatusAttribute 获取状态的翻译文本]
     * @return string [type] [状态的翻译文本]
     */
    protected function getFormattedStatusAttribute() {
        return __('_deposit.' . strtolower(Str::slug(static::$validStatuses[$this->attributes['status']])));
    }

    /**
     * [getFormattedStatusAttribute 获取状态的翻译文本]
     * @return string [type] [状态的翻译文本]
     */
    protected function getFormattedDepositModeAttribute() {
        return __('_deposit.bankcard');
    }

    protected function getAmountFormattedAttribute() {
        return $this->amount > 0 ? $this->getFormattedNumberForHtml('amount') : null;
    }

    protected function getFeetFormattedAttribute() {
        return $this->getFormattedNumberForHtml('fee');
    }

    protected function getRealAmountFormattedAttribute() {
        return $this->getFormattedNumberForHtml('real_amount');
    }

    protected function getFriendlyIsTesterAttribute() {
        return yes_no(intval($this->is_tester));
    }

    protected function getOrderNoShortAttribute(){
        return substr($this->attributes['order_no'],-6);
    }

    protected function getFriendlyApplyTimeAttribute() {
        return substr($this->attributes['created_at'], 5);
    }

    protected function getFriendlyPutTimeAttribute() {
        return substr($this->attributes['put_at'], 5);
    }

    protected function getFriendlyPayTimeAttribute() {
        return substr($this->attributes['service_time'], 5);
    }
//
    private function countCommission() {
        if (!SysConfig::check('deposit_commission_enabled', true)) {
            return null;
        }
        if (!$this->user_parent) {
            return null;
        }
        if ($this->amount < 200) {
            return 0;
        }
        if ($this->amount < 400) {
            return 1;
        }
        if ($this->amount < 1000) {
            return 2;
        }
        if ($this->amount < 3000) {
            return 6;
        }
        if ($this->amount < 5000) {
            return 18;
        }
        if ($this->amount < 10000) {
            return 33;
        }
        return 66;
    }
    /**
     * 前台model有调用
     */
    public function setNewFlag() {
        $key = static::makeNewFlagCacheKey();
        Cache::setDefaultDriver(static::$cacheDrivers[1]);
        Cache::put($key, $this->id, 1);
    }

    private static function makeNewFlagCacheKey() {
        return 'new-deposit';
    }

    /**
     * 向任务队列追加充值额统计任务
     * @param date $sDate
     * @param int $iUserId
     * @param float $fAmount
     * @return bool
     */
    public static function addProfitTask($sDate, $iUserId, $fAmount) {
        $aTaskData = [
            'type' => 'deposit',
            'user_id' => $iUserId,
            'amount' => $fAmount,
            'date' => substr($sDate, 0, 10),
        ];
        return BaseTask::addTask('StatUpdateProfit', $aTaskData, 'stat');
    }

}
