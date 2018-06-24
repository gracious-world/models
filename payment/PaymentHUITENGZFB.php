<?php

/**
 * 汇腾平台支付宝支付
 * @author Tino
 */
class PaymentHUITENGZFB extends PaymentHUITENGWX {

    public $qrDirName = 'huitengzfb';
    /**
     * 充值请求表单数据组建
     * @param $oPaymentPlatform
     * @param $oPaymentAccount
     * @param $oDeposit
     * @param $oBank
     * @param $sSafeStr
     * @return array
     */
    public function & compileInputData($oPaymentPlatform, $oPaymentAccount, $oDeposit, $oBank, & $sSafeStr) {
        $aData = [
            'userOrderNo' => $oDeposit->order_no,
            'merchantNo' => $oPaymentAccount->account,
            'transId' => '002',
            'orderDate' => date('YmdHis'),
            'notifyUrl' => $oPaymentPlatform->notify_url,
            'orderAmt' => '' . $oDeposit->amount * 100,
            'curType' => 'CNY',
            'payType' => '800201',
            'clientIp' => Tool::getClientIp(),
            'phoneNo' => '12345678901',
            'settleType' => '1'
        ];
        $aData['signature'] = $sSafeStr = $this->compileSign($oPaymentAccount, $aData, $this->signNeedColumns);
        $aInputData = $this->compileData($aData);

        return $aInputData;
    }
}
