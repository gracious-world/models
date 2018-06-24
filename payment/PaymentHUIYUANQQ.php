<?php

/**
 * 汇元微信平台
 *
 * @author zero
 */
class PaymentHUIYUANQQ extends PaymentHUIYUANWX {

    //接口版本号
    public $version = 1;
    public $remark = 'huiyuanqq_query';
    public $isPhone = 1;
    public $payType = 60;
    public $saveQr = false;

    /** 提交充值参数
     * @param $oPaymentPlatform
     * @param $oPaymentAccount
     * @param $oDeposit
     * @param $oBank
     * @param $sSafeStr
     * @return array
     */
    public function & compileInputData($oPaymentPlatform, $oPaymentAccount, $oDeposit, $oBank, & $sSafeStr) {
        $data = & parent::compileInputData($oPaymentPlatform, $oPaymentAccount, $oDeposit, $oBank, $sSafeStr);
        $data['is_phone'] = $this->isPhone;

        return $data;
    }

    /**
     * @param PaymentPlatform $oPaymentPlatform
     * @param $oPaymentAccount
     * @param string $sOrderNo
     * @param null $sServiceOrderNo
     * @param array $aResponse
     * @return int
     */
    public function queryFromPlatform($oPaymentPlatform, $oPaymentAccount, $sOrderNo, $sServiceOrderNo = null, & $aResponse) {

        $data = $this->compileQueryData($oPaymentAccount, $sOrderNo, $sServiceOrderNo);
        $postDataTmp = [];
        foreach ($data as $k => $v) {
            $postDataTmp[$k] = $k . '=' . $v;
        }
        $postData = implode('&', $postDataTmp);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $oPaymentPlatform->getQueryUrl($oPaymentAccount));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //将数据传给变量
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); //取消身份验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        $response = curl_exec($ch); //接收返回信息
        file_put_contents('/tmp/hyqq_' . $sOrderNo, $response, FILE_APPEND);
        if (curl_errno($ch)) {
            //出错则显示错误信息
            print curl_error($ch);
        }
        curl_close($ch); //关闭curl链接

        return $this->_processQuery($oPaymentAccount,$response,$aResponse);

    }
}
