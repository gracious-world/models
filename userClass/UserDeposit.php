<?php

class UserDeposit extends Deposit {

    protected static $cacheUseParentClass = true;
    public static $columnForList = [];

    protected function beforeValidate() {
        if (empty($this->order_no)) {
            $this->order_no = $this->compileOrderNo();
        }
        return parent::beforeValidate();
    }

    /**
     * 生成平台充值订单号
     * @return string
     */
    public static function compileOrderNo() {
        return uniqid(mt_rand());
    }

    public static function compileSignZf($aInputData, $sMerchantKey) {
        ksort($aInputData);
//            pr($aInputData);
        $aInputValues = [];
        $sQueryString = '';
        $sForSafe     = '';
        foreach ($aInputData as $key => $value) {
            if (!empty($value)) {
                $aInputValues[] = "$key=$value";
            }
        }
        $sQueryString = implode('&', $aInputValues);
        return md5($sQueryString . '&key=' . $sMerchantKey);
    }

    private static function & compileData($iMode = self::DEPOSIT_MODE_THIRD_PART, $oUser, $oBank, $fAmount, $oPayment = null, $aBankcardInfo = null, $oPaymentAccount = null) {
        if ($oUser->parent_id) {
            $aForeFathers   = explode(',', $oUser->forefathers);
            $aForeFatherIds = explode(',', $oUser->forefather_ids);
            $iTopAgentId    = $aForeFatherIds[0];
            $sTopAgent      = $aForeFathers[0];
        } else {
            $iTopAgentId = $oUser->id;
            $sTopAgent   = $oUser->username;
        }
        $aDepositInfo = [
            'user_id'             => $oUser->id,
            'username'            => $oUser->username,
            'realname'            => $oUser->name,
            'is_tester'           => $oUser->is_tester,
            'is_agent'            => $oUser->is_agent,
            'user_parent'         => $oUser->parent,
            'user_forefather_ids' => $oUser->forefather_ids,
            'top_agent_id'        => $iTopAgentId,
            'top_agent'           => $sTopAgent,
            'bank_id'             => is_object($oBank) ? $oBank->id : null,
            'bank_no'             => is_object($oBank) ? $oBank->serial_number : null,
            'bank'                => is_object($oBank) ? $oBank->name : null,
            'bank_identifier'     => is_object($oBank) ? $oBank->identifier : null,
            'amount'              => $fAmount,
            'deposit_mode'        => $iMode,
            'status'              => Deposit::DEPOSIT_STATUS_RECEIVED,
            'ip'                  => Tool::getClientIp(),
        ];
        if ($iMode == self::DEPOSIT_MODE_BANK_CARD) {
            $aDepositInfo['accept_card_num'] = $aBankcardInfo['account_no'];
            $aDepositInfo['accept_email']    = $aBankcardInfo['email'];
            $aDepositInfo['accept_acc_name'] = $aBankcardInfo['owner'];
            $aDepositInfo['postscript']      = self::compilePostscript();
        } else {
            $aDepositInfo['platform_id'] =$oPayment->id;
            $aDepositInfo['platform'] =$oPayment->name;
            $aDepositInfo['platform_identifier'] =$oPayment->identifier;
            $aDepositInfo['query_enabled'] =$oPayment->query_enabled;
            $aDepositInfo['account_no']  = $oPaymentAccount->serial_number;
            $aDepositInfo['merchant_key']  = $oPaymentAccount->safe_key;
            $aDepositInfo['merchant_code'] = $oPaymentAccount->account;
        }
        return $aDepositInfo;
    }

    public static function addDeposit($iMode = self::DEPOSIT_MODE_THIRD_PART, $oUser, $oBank = null, $fAmount = null, $oPayment = null, $aBankcardInfo = null, $oPaymentAccount=null) {
        $data     = & self::compileData($iMode, $oUser, $oBank, $fAmount, $oPayment, $aBankcardInfo, $oPaymentAccount);
        $oDeposit = new UserDeposit($data);
        if (($bSucc = $oDeposit->save()) && $iMode == self::DEPOSIT_MODE_BANK_CARD){
            $oDeposit->setNewFlag();
        }
        return $bSucc ? $oDeposit : false;
    }

    private static function compilePostscript() {
        $a = mt_rand(0, 25);
        $c = chr(0x41 + $a);
        $n = mt_rand(0, pow(10, 9) - 1);
        return $c . str_pad($n, 9, 0, STR_PAD_LEFT);
    }

}
