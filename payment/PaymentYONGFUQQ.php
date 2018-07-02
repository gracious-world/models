<?php

/**
 * 聚鑫平台
 *
 * @author lucky
 *
 */
class PaymentYONGFUQQ extends PaymentYONGFUWY
{

    public $paymentName = 'yongfuqq';
    public $channelCode = 'QQ';

    public $signNeedColumns = [ //充值请求
        'pay_memberid',
        'pay_orderid',
        'pay_amount',
        'pay_applydate',
        'pay_channelCode',
        'pay_notifyurl'
    ];

    public function & compileInputData($oPaymentPlatform, $oPaymentAccount, $oDeposit, $oBank, & $sSafeStr) {
        $aSignData = [
            'pay_memberid' => $oPaymentAccount->account,
            'pay_orderid' => $oDeposit->order_no,
            'pay_amount' => $oDeposit->amount,
            'pay_applydate' => date('YmdHis',strtotime($oDeposit->created_at)),
            'pay_channelCode' => $this->channelCode,
            'pay_notifyurl' => $oPaymentPlatform->notify_url,
        ];
        !$this->isMobile or $aSignData['isMobile'] = 'true';

        $aSignData['pay_md5sign'] = $this->compileSign($oPaymentAccount, $aSignData, $this->signNeedColumns);
//        var_dump(__FILE__,__LINE__,$aSignData);exit;
        return $aSignData;
    }
}

