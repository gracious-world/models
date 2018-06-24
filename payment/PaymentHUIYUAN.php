<?php

/**
 * 汇元平台
 */
class PaymentHUIYUAN extends BasePlatform {

    public $successMsg             = 'OK';
    public $signColumn             = 'hmac';
    public $accountColumn          = 'merchantId';
    public $orderNoColumn          = 'orderId';
    public $paymentOrderNoColumn   = 'orderId';
    public $successColumn          = 'payResult';
    public $successValue           = '1';
    public $amountColumn           = 'realAmount';
    public $bankNoColumn           = 'bankId';
    public $unSignColumns          = [ 'remark', 'noticePage'];
    public $serviceOrderTimeColumn = '';
    public $queryResultColumn      = 'payResult';

    public function compileSign($oPaymentAccount, $aInputData, $aNeedKeys = []) {
        $sQueryString = '';
        foreach ($aInputData as $key => $value) {
            $sQueryString .= $value . '|';
        }
        $sQueryString = $sQueryString . $oPaymentAccount->safe_key;
        return md5($sQueryString);
    }

    public function compileSignReturn($oPaymentAccount, $aInputData, $aNeedKeys = []) {
        $aData['merchantId'] = $aInputData['merchantId'];
        $aData['orderId']    = $aInputData['orderId'];
        $aData['realAmount'] = $aInputData['realAmount'];
        $aData['payResult']  = $aInputData['payResult'];
        return $this->compileSign($oPaymentAccount, $aData, $aNeedKeys);
    }

    public function & compileInputData($oPaymentPlatform, $oPaymentAccount, $oDeposit, $oBank, & $sSafeStr) {
        $aData               = [
            'merchantId'  => $oPaymentAccount->account,
            'orderId'     => $oDeposit->order_no,
            'productName' => 'Vitrual' . intval(mt_rand(1, 99999)),
            'bankId'      => $oBank ? $oBank->identifier : null,
            'notifyUrl'   => $oPaymentPlatform->notify_url,
            'amount'      => $oDeposit->amount,
        ];
        $aData['hmac']       = $sSafeStr            = $this->compileSign($oPaymentAccount, $aData);
        $aData['remark']     = '';
        $aData['noticePage'] = '';
        return $aData;
    }

    public function & compileQueryData($oPaymentAccount, $sOrderNo, $sServiceOrderNo) {
        $aData         = [
            'merchantId' => $oPaymentAccount->account,
            'orderId'    => $sOrderNo,
        ];
        $aData['hmac'] = $this->compileSign($oPaymentAccount, $aData);
        return $aData;
    }

    /**
     * Query from Payment Platform
     * @param PaymentPlatform $oPaymentPlatform
     * @param string $sOrderNo
     * @param string $sServiceOrderNo
     * @param array & $aResonses
     * @return integer | boolean
     *  1: Success
     *  -1: Query Failed
     *  -2: Parse Error
     *  -3: Sign Error
     *  -4: No Order
     *  -5: Unpay
     */
    public function queryFromPlatform($oPaymentPlatform, $oPaymentAccount, $sOrderNo, $sServiceOrderNo = null, & $aResonses) {
        $aDataQuery = $this->compileQueryData($oPaymentAccount, $sOrderNo, $sServiceOrderNo);
        foreach ($aDataQuery as $k => $v) {
            $aQueryStr[] = $k . '=' . $v;
        }
        $sDataQuery = implode('&', $aQueryStr);
        $ch         = curl_init();
        curl_setopt($ch, CURLOPT_URL, $oPaymentPlatform->getQueryUrl($oPaymentAccount));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //将数据传给变量
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); //取消身份验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $sDataQuery);
        $sResponse  = curl_exec($ch); //接收返回信息
        file_put_contents('/tmp/hy_' . $sOrderNo, $sResponse);
        if (curl_errno($ch)) {//出错则显示错误信息
            print curl_error($ch);
        }

        curl_close($ch); //关闭curl链接
        if ($sResponse === '') {     // query failed
            return self::PAY_QUERY_FAILED;
        }

        $aQueryResonses = explode('|', $sResponse);
        $aResonses      = [];
        foreach ($aQueryResonses as $v) {
            $aResonse                = explode('=', $v);
            if (count($aResonse) == 1){
                return self::PAY_NO_ORDER;
            }
            $aResonses[$aResonse[0]] = $aResonse[1];
        }

        if ($aResonses['payResult'] == '0') {      // NO ORDER
            return self::PAY_NO_ORDER;
        }

        $sDinpaySign      = $aResonses['hmac'];
        $aCompileSignNeed = [
            'merchantId' => $aResonses['merchantId'],
            'orderId'    => $aResonses['orderId'],
            'realAmount' => $aResonses['realAmount'],
            'payResult'  => $aResonses['payResult'],
        ];
        $sSign            = $this->compileSign($oPaymentAccount, $aCompileSignNeed);

        if ($sSign != $sDinpaySign) {
            return self::PAY_SIGN_ERROR;
        }

        if ($aResonses['payResult'] == 1 && $aResonses['realAmount'] > 0) {
            return self::PAY_SUCCESS;
        }

        return self::PAY_UNPAY;
    }

    public static function & compileCallBackData($aBackData, $sIp) {
        $aData = [
            'order_no'         => $aBackData['orderId'],
            'service_order_no' => $aBackData['bankId'],
            'merchant_code'    => $aBackData['merchantId'],
            'amount'           => $aBackData['realAmount'],
            'ip'               => $sIp,
            'status'           => DepositCallback::STATUS_CALLED,
            'post_data'        => var_export($aBackData, true),
            'callback_time'    => time(),
            'callback_at'      => date('Y-m-d H:i:s'),
            'referer'          => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null,
            'http_user_agent'  => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null
        ];
        return $aData;
    }

    public static function & getServiceInfoFromQueryResult(& $aResponses) {
        $data = [
            'service_order_no' => $aResponses['orderId'],
        ];
        return $data;
    }

}
