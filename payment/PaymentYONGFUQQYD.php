<?php

/**
 * 聚鑫平台
 *
 * @author lucky
 *
 */
class PaymentYONGFUQQYD extends PaymentYONGFUQQ
{

    public $paymentName = 'yongfuqqyd';
    public $channelCode = 'QQ_WAP';
    public $isMobile = true;

    public function & compileInputData($oPaymentPlatform, $oPaymentAccount, $oDeposit, $oBank, & $sSafeStr)
    {
        $aSignData = [
            'pay_memberid' => $oPaymentAccount->account,
            'pay_orderid' => $oDeposit->order_no,
            'pay_amount' => $oDeposit->amount,
            'pay_applydate' => date('ymdHis', strtotime($oDeposit->created_at)),
            'pay_channelCode' => $this->channelCode,
            'pay_notifyurl' => $oPaymentPlatform->notify_url,
            'isMobile' => true
        ];

        $aSignData['pay_md5sign'] = $this->compileSign($oPaymentAccount, $aSignData, $this->signNeedColumns);
        return $aSignData;
    }
}

