<?php

/**
 * Dividend Model
 *
 */
class Dividend extends BaseModel {

    const STATUS_WAITING_AUDIT = 0;
    const STATUS_AUDITED       = 1;
    const STATUS_REJECTED      = 2;
    const STATUS_SENT          = 3;
    const TOP_AGENT            = 0;
    const NORMAL_AGENT         = 1;

    protected $table                 = 'dividends';
    public static $resourceName      = 'Dividend';
    public static $treeable          = false;
    public static $sequencable       = false;
    protected $softDelete            = false;
    public static $aStatus           = [
        self::STATUS_WAITING_AUDIT => 'waiting audit',
        self::STATUS_AUDITED       => 'audited',
        self::STATUS_REJECTED      => 'rejected',
        self::STATUS_SENT          => 'bonus sent',
    ];
    public static $aAgentLevel       = [
        self::TOP_AGENT    => 'top agent',
        self::NORMAL_AGENT => 'agent',
    ];
    protected $fillable              = [
        'year',
        'month',
        'batch',
        'begin_date',
        'end_date',
        'user_id',
        'username',
        'parent_id',
        'parent',
        'user_forefather_ids',
        'user_forefathers',

        'is_tester',
        'turnover',
        'valid_sales',
        'prize',
        'bonus',
        'commission',
        'lose_commission',
        'profit',
        'rate',
        'amount',
        'status',
        'auditor_id',
        'auditor',
        'verified_at',
        'sent_at',
        'note',
    ];
    public static $columnForList     = [
        'year',
        'month',
        'batch',
        'begin_date',
        'end_date',
        'username',
        'is_tester',
        'turnover',
        'prize',
        'commission',
        'bonus',
        'lose_commission',
        'profit',
        'valid_sales',
        'rate',
        'amount',
        'status',
        'auditor',
//        'note',
        'verified_at',
        'sent_at',
        'total_profit_origin',
        'total_profit',
    ];
    public static $totalColumns      = [
        'turnover',
        'valid_sales',
        'prize',
        'bonus',
        'commission',
        'profit',
        'amount',
    ];
    public static $listColumnMaps = [
        'is_tester'       => 'is_tester_formatted',
        'rate'            => 'rate_formatted',
        'turnover'        => 'turnover_formatted',
        'valid_sales'     => 'valid_sales_formatted',
        'prize'           => 'prize_formatted',
        'bonus'           => 'bonus_formatted',
        'commission'      => 'commission_formatted',
        'total_profit'    => 'total_profit_formatted',
        'total_profit_origin'    => 'total_profit_origin_formatted',
        'lose_commission' => 'lose_commission_formatted',
        'profit'          => 'profit_formatted',
        'amount'          => 'amount_formatted',
        'status'          => 'friendly_status',
        'verified_at'     => 'verified_at_formatted',
        'sent_at'         => 'sent_at_formatted',
    ];
    public static $rules             = [
        'note' => 'between:0,100',
    ];
    public static $htmlSelectColumns = [
        'status' => 'aStatus',
    ];
    public $orderColumns             = [
        'end_date' => 'desc',
    ];
    protected $User;
    protected $Account;

    public static function & getUserIdsByDate($sBeginDate, $sEndDate) {
        $data = static::where('begin_date', '=', $sBeginDate)->where('end_date', '=', $sEndDate)->orderBy('user_id')->lists('user_id');
        return $data;
    }

    protected function getRateFormattedAttribute() {
        return $this->attributes['rate'] * 100 . '%';
    }

    protected function getTurnoverFormattedAttribute() {
        return number_format($this->attributes['turnover'], 2);
    }

    protected function getValidSalesFormattedAttribute() {
        return number_format($this->attributes['valid_sales'], 2);
    }

    protected function getVerifiedAtFormattedAttribute() {
        return substr($this->attributes['verified_at'], 5);
    }

    protected function getSentAtFormattedAttribute() {
        return substr($this->attributes['sent_at'], 5);
    }

    protected function getPrizeFormattedAttribute() {
        return number_format($this->attributes['prize'], 2);
    }

    protected function getBonusFormattedAttribute() {
        return number_format($this->attributes['bonus'], 2);
    }

    protected function getCommissionFormattedAttribute() {
        return number_format($this->attributes['commission'], 2);
    }

    protected function getLoseCommissionFormattedAttribute() {
        return number_format($this->attributes['lose_commission'], 2);
    }

    protected function getProfitFormattedAttribute() {
        return number_format($this->attributes['profit'], 2);
    }

    protected function getTotalProfitFormattedAttribute() {
        return number_format($this->attributes['total_profit'], 4);
    }

    protected function getTotalProfitOriginFormattedAttribute() {
        return number_format($this->attributes['total_profit_origin'], 4);
    }

    protected function getAmountFormattedAttribute() {
        return number_format($this->attributes['amount'], 2);
    }

    protected function getFriendlyStatusAttribute() {
        return __('_bonus.' . static::$aStatus[$this->status]);
    }

    public static function getDividendByMonthUser($iUserId, $sBeginDate = null, $sEndDate = null) {
        $aConditions               = [
            'user_id' => ['=', $iUserId],
        ];
        !$sBeginDate or $aConditions['begin_date'] = ['=', $sBeginDate];
        !$sEndDate or $aConditions['end_date']   = ['=', $sEndDate];
        return static::doWhere($aConditions)->orderBy('end_date', 'desc')->first();
    }

    protected function getIsTesterFormattedAttribute() {
        return yes_no(intval($this->is_tester));
    }

    public function addStatTask() {
        $aTaskData = [
            'type'    => 'share',
            'user_id' => $this->user_id,
            'amount'  => $this->amount,
            'date'    => substr($this->sent_at, 0, 10),
        ];
        return BaseTask::addTask('StatUpdateProfit', $aTaskData, 'stat');
    }

    public function addSendTask() {
        $aTaskData = [
            'id' => $this->id,
        ];
        return BaseTask::addTask('SendDividend', $aTaskData, 'send_money');
    }

    public function send() {
        $aExtraData['note'] = "分红：$this->begin_date 至 $this->end_date";
        $sType=TransactionType::TYPE_SEND_DIVIDEND;
        $this->amount>=0 or $sType = TransactionType::TYPE_CANCEL_DIVIDEND;
        $this->amount = abs($this->amount);
        $bSucc              = Transaction::addTransaction($this->User, $this->Account, $sType, $this->amount, $aExtraData, $oTransaction) == Transaction::ERRNO_CREATE_SUCCESSFUL;
        !$bSucc or $bSucc              = $this->setToSent();
        return $bSucc;
    }

    public function setUser($oUser) {
        $this->User = $oUser;
    }

    public function setAccount($oAccount) {
        $this->Account = $oAccount;
    }

    protected function setToSent($aExtraData = []) {
        $aExtraData['sent_at'] = date('Y-m-d H:i:s');
        return $this->setStatus(self::STATUS_SENT, self::STATUS_AUDITED, $aExtraData);
    }

    /**
     * 审核通过
     * @param integer   $iAdminUserId
     * @param array     $aExtraInfo
     * @return boolean
     */
    public function setToAudited($iAdminUserId, $aExtraInfo = []) {
        $oAdminUser = AdminUser::find($iAdminUserId);
        $data       = [
            'auditor_id'  => $iAdminUserId,
            'auditor'     => $oAdminUser->username,
            'verified_at' => date('Y-m-d H:i:s'),
        ];
        $data       = array_merge($data, $aExtraInfo);
        return $this->setStatus(self::STATUS_AUDITED, self::STATUS_WAITING_AUDIT, $data);
    }

    /**
     * 审核拒绝
     * @param integer   $iAdminUserId
     * @param array     $aExtraInfo
     * @return boolean
     */
    public function setToReject($iAdminUserId, $aExtraInfo = []) {
        $oAdminUser = AdminUser::find($iAdminUserId);
        $data       = [
            'auditor_id'  => $iAdminUserId,
            'auditor'     => $oAdminUser->username,
            'verified_at' => date('Y-m-d H:i:s'),
        ];
        $data       = array_merge($data, $aExtraInfo);
        return $this->setStatus(self::STATUS_REJECTED, self::STATUS_WAITING_AUDIT, $data);
    }

    protected function setStatus($iToStatus, $iFromStatus, $aExtraData = []) {
        $aConditions = [
            'id'     => ['=', $this->id],
            'status' => ['=', $iFromStatus],
        ];
        $data        = [
            'status' => $iToStatus
        ];
        $data        = array_merge($data, $aExtraData);
        return $this->strictUpdate($aConditions, $data) > 0;
    }

    protected function setParentIdAttribute($iParentId) {
        $this->attributes['parent_id'] = $iParentId ? $iParentId : 0;
    }
}
