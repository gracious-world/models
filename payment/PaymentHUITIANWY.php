<?php

/**
 * Created by PhpStorm.
 * User: simon
 * Date: 18-1-26
 * Time: 下午4:46
 */
class PaymentHUITIANWY extends PaymentHUITIANQQSM
{

    protected $paymentName = 'huitianwy';
    // 保存二维码
    public $saveQr = false;

    // 银行类型 QQ 扫码：89  微信扫码：21
    protected $payment_type = 1;

    /**
     * 充值请求表单数据组建
     *
     * @author james liang
     * @date 2017-06-13
     *
     * @param $oPaymentPlatform
     * @param $oPaymentAccount
     * @param $oDeposit
     * @param $oBank
     * @param $sSafeStr
     *
     * @return array
     */
    public function & compileInputData($oPaymentPlatform, $oPaymentAccount, $oDeposit, $oBank, & $sSafeStr) {
        $aSignData = [
            'P_UserID' => $oPaymentAccount->account,
            'P_OrderID' => $oDeposit->order_no,
            'P_CardID' => '',
            'P_CardPass' => '',
            'P_FaceValue' => $oDeposit->amount,
            'P_ChannelID' => $this->payment_type,
            'P_Price' => $oDeposit->amount,
            'P_Notic' => $oDeposit->username,
            'P_Result_URL' => $oPaymentPlatform->notify_url,
            'P_Notify_URL' => $oPaymentPlatform->notify_url,
            'P_Description' => $oBank->identifier    //ICBC,there s an self cash end in the page
        ];
        $aSignData['P_PostKey'] = $this->compileSign($oPaymentAccount, $aSignData, $this->signNeedColumns);
        $aData = $aSignData;
        return $aData;
    }
}
