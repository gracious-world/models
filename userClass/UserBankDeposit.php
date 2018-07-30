<?php
/**
 * Created by PhpStorm.
 * User: damon
 * Date: 9/15/15
 * Time: 5:48 PM
 */

class UserBankDeposit extends BankDeposit {

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

    private static function & compileData( $oUser, $oBank, $fAmount, $oPayment = null, $aBankcardInfo = null, $sPayerName = null, $fAmount=null) {
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
            'status'              => BankDeposit::STATUS_RECEIVED,
            'ip'                  => Tool::getClientIp(),
            'accept_card_num'     => $aBankcardInfo['account_no'],
            'accept_email'        => $aBankcardInfo['email'],
            'accept_acc_name'     => $aBankcardInfo['owner'],
            'postscript'          => self::compilePostscript(),
            'platform_id'         => $oPayment->id,
            'platform'            => $oPayment->name,
            'platform_identifier' => $oPayment->identifier,
            'amount'              => $fAmount

        ];

        if(!empty($sPayerName)){
            $aDepositInfo['payer_name'] = $sPayerName;
        }
        return $aDepositInfo;
    }

    public static function addDeposit($oUser, $oBank = null, $fAmount = null, $oPayment = null, $aBankcardInfo = null, $sPayerName=null, $fAmount=null) {
        $data     = & self::compileData($oUser, $oBank, $fAmount, $oPayment, $aBankcardInfo, $sPayerName, $fAmount);
        $oDeposit = new static($data);
        if ($bSucc = $oDeposit->save()){
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
