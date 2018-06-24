<?php

/**
 * 云盛微信平台
 * @author zero
 */
class PaymentYUNSHENGWX extends PaymentYUNSHENG {

    protected $payType = 3;//1网银,2支付宝,3微信
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
        $aData['bankCode'] = 'WECHAT';

        return $aData;
    }

}
