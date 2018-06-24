<?php

/**
 * 汇腾平台QQ支付
 * @author Tino
 */
class PaymentHUITENGQQ extends PaymentHUITENGWX {

    protected $outChannelType = 'qqpay';
    public $qrDirName = 'huitengqq';
    protected $smName = 'qq';
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
            'transId' => '001',
            'orderDate' => date('YmdHis'),
            'notifyUrl' => $oPaymentPlatform->notify_url,
            'orderAmt' => '' . $oDeposit->amount * 100,
            'curType' => 'CNY',
            'commodityName' => 'Vitrual' . intval(mt_rand(1, 99999)),
            'outChannel' => $this->outChannelType,
            'subCommodityName' => 'Vitrual' . intval(mt_rand(1, 99999)),
            'subMerNo' => '' . intval(mt_rand(1, 9999999)),
            'payType' => '800201',
            'clientIp' => Tool::getClientIp(),
            'spUdid' => '' . intval(mt_rand(1, 9999999)),
            'phoneNo' => '12345678901',
            'settleType' => '1'
        ];
        $aData['signature'] = $sSafeStr = $this->compileSign($oPaymentAccount, $aData, $this->signNeedColumns);
        $aInputData = $this->compileData($aData);

        return $aInputData;
    }
}
