<?php

class UserWithdrawal extends Withdrawal {

    protected static $cacheUseParentClass = true;
    public static $columnForList = [
        'serial_number',
        'request_time',
        // 'account',
        'account_name',
        'amount',
        'transaction_charge',
        'transaction_amount',
        'status'
    ];
    // protected $fillable = [
    //     'id',
    //     'serial_number',
    //     'user_id',
    //     'username',
    //     'request_time',
    //     'amount',
    //     'is_large',
    //     'account',
    //     'account_name',
    //     'province',
    //     'bank_id',
    //     'bank',
    //     'branch',
    //     'branch_address',
    //     'error_msg',
    //     'status',
    //     'auditor_id',
    //     'auditor',
    //     'verified_time',
    //     'transaction_charge',
    //     'transaction_amount',
    // ];

    protected $isAdmin = false;
    public static $customMessages = [
        'amount.regex' => '提现金额必须是数字，且只保留2位小数',
        'province.required' => '该银行卡所属省份信息缺失',
        'province.between' => '该银行卡所属省份长度有误，请输入 :min - :max 位字符',
        'branch.required' => '该银行卡的开户行名称缺失',
        'branch.between' => '该银行卡的开户行名称长度有误，请输入 :min - :max 位字符',
        'branch_address.between' => '该银行卡支行地址长度有误，请输入 :min - :max 位字符',
        'account_name.required' => '该银行卡的开户人信息缺失',
        'account_name.between' => '该银行卡的开户人信息长度有误，请输入 :min - :max 位数字',
        'account.required' => '该银行卡账号信息缺失',
        'account.numeric' => '该银行卡账号必须为数字',
        'transaction_charge.regex' => '手续费必须是数字，且只保留2位小数',
        'transaction_amount.regex' => '实际提现金额必须是数字，且只保留2位小数',
    ];

    // const WITHDRAWAL_INFO    = 0;
    // const WITHDRAWAL_CONFIRM = 1;
    const IS_LARGE_AMOUNT = 50000; // TODO 大额提现的判断标准，待定

    public static function getWithdrawalNumPerDay($user_id) {
        $sNow = date('Y-m-d');
        $iNum = UserWithdrawal::where('user_id', '=', $user_id)->where('request_time', 'like', date('Y-m-d') . '%')->count();
        return $iNum;
    }

    /**
     * [generateSerialNumber 序列号生成器]
     * @return [String] [序列号]
     * we will change six year later
     */
    public static function generateSerialNumber() {
        $year_code = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J');
        $order_sn = $year_code[intval(date('Y')) - 2010] .
            strtoupper(dechex(date('m'))) . date('d') .
            substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
        return $order_sn;
    }

    public static function createWithdrawal($iCardId, $fAmount) {
        $data = & self::compileData($iCardId, $fAmount);
        $oWidthdrawal = new static($data);
        if (!$oWidthdrawal->save()) {
            return false;
        }
        return $oWidthdrawal;
    }

    /**
     * [fillWithdrawalData 生成提现记录的数据数组]
     * @param  [Int] $iCardId [银行卡id ]
     * @param  [Int] $iAmount [提现金额 ]
     * @return [Array]          [数据数组]
     */
    public static function & compileData($iCardId, $fAmount) {
        $oBankCard = UserUserBankCard::getUserCardInfoById($iCardId);
        $oBank = Bank::find($oBankCard->bank_id);
        // $iTransactionCharge = self::countTransactionCharge($fAmount);
        $bAutoVerify = SysConfig::readValue('withdraw_amount_divide') >= $fAmount;
        $data = [
            'serial_number' => self::generateSerialNumber(),
            'user_id' => Session::get('user_id'),
            'username' => Session::get('username'),
            'is_tester' => Session::get('is_tester'),
            'request_time' => Carbon::now()->toDateTimeString(),
            'amount' => $fAmount,
            'is_large' => self::isLarge($fAmount),
            'bank_id' => $oBankCard->bank_id,
            'bank_no' => $oBank->serial_number,
            'bank' => $oBankCard->bank,
            'bank_identifier' => $oBank->identifier,
            'account' => $oBankCard->account,
            'account_name' => $oBankCard->account_name,
            'province' => $oBankCard->province,
            'branch' => $oBankCard->branch,
            'branch_address' => $oBankCard->branch_address,
            'status' => $bAutoVerify ? self::WITHDRAWAL_STATUS_VERIFIED : self::WITHDRAWAL_STATUS_RECEIVED,
            'ip' => Tool::getClientIp()
        ];
        return $data;
    }

    /**
     * 检查是否是大额提现
     * @param float $fAmount
     * @return int 1,0
     */
    public static function isLarge($fAmount) {
        return (int)SysConfig::check('withdraw_amount_divide', $fAmount);
    }

    /**
     * 返回指定用户的进行中的提现请求的数量
     * @param integer $iUserId
     * @return integer
     */
    public static function getInProgressCount($iUserId){
        $aStatus = [
            self::WITHDRAWAL_STATUS_NEW, // 待审核
            self::WITHDRAWAL_STATUS_RECEIVED, //申请成功
            self::WITHDRAWAL_STATUS_VERIFY_ACCEPTED, //已受理审核
//            self::WITHDRAWAL_STATUS_REFUSE, // 未通过审核（审核拒绝）
            self::WITHDRAWAL_STATUS_VERIFIED, // 审核通过，待处理
            self::WITHDRAWAL_STATUS_WITHDRAWAL_ACCEPTED, //受理提现
            self::WITHDRAWAL_STATUS_SUCCESS, // 汇款成功，即已提交汇款凭证
            self::WITHDRAWAL_STATUS_REFUND, // 已退游戏币（审核拒绝情况下）
        ];
        $oContaioner = static::select(DB::raw('count(*) as withdraw_count'))
            ->where('user_id','=',$iUserId)
            ->whereIn('status',$aStatus)
            ->get();
        return $oContaioner->count() ? $oContaioner[0]->withdraw_count : 0;
    }
}
