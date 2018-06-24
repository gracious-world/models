<?php

/**
 * 云盛qq扫码平台
 * @author zero
 */
class PaymentYUNSHENGQQ extends PaymentYUNSHENG {

    protected $payType = 4;//1网银,2支付宝,3微信
    /**
     * 充值请求表单数据重写
     * @param $oPaymentPlatform
     * @param $oPaymentAccount
     * @param $oDeposit
     * @param $oBank
     * @param $sSafeStr
     * @return array
     */
    public function & compileInputData($oPaymentPlatform, $oPaymentAccount, $oDeposit, $oBank, & $sSafeStr) {
        $aData = & parent::compileInputData($oPaymentPlatform, $oPaymentAccount, $oDeposit, $oBank, $sSafeStr);
        //修改bankCode
        $aData['bankCode'] = 'QQPAY';

        return $aData;
    }

}
