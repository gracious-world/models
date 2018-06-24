<?php

/**
 * 汇元平台
 */
class PaymentHUIYUANZFB extends BasePlatform {

    public $successMsg             = 'OK';
    public $signColumn             = 'sign';
    public $accountColumn          = 'agent_id';
    public $orderNoColumn          = 'agent_bill_id';
    public $paymentOrderNoColumn   = 'jnet_bill_no';
    public $successColumn          = 'result';
    public $successValue           = '1';
    public $amountColumn           = 'pay_amt';
    public $bankNoColumn           = '';
    public $unSignColumns          = [ 'pay_message'];
    public $serviceOrderTimeColumn = '';
    public $queryResultColumn      = 'result';
    public $iPayType = 50;
    public $iVersion = 1;

    public function compileSign($oPaymentAccount, $aInputData, $aNeedKeys = []) {
        $sQueryString = '';
        foreach ($aInputData as $key => $value) {
            $sQueryString .= $key . '=' . $value . '&';
        }
        $sQueryString = $sQueryString . 'key=' . $oPaymentAccount->safe_key;
        return md5($sQueryString);
    }

    private function compileQuerySign($oPaymentAccount, $aInputData) {
        $sQueryString = '';
        foreach ($aInputData as $key => $value) {
            $sQueryString .= $key . '=' . $value . '|';
        }
        $sQueryString = $sQueryString . 'key=' . $oPaymentAccount->safe_key;
        return md5($sQueryString);
    }

    public function compileSignReturn($oPaymentAccount, $aInputData, $aNeedKeys = []) {
        $aData['result']        = $aInputData['result'];
        $aData['agent_id']      = $aInputData['agent_id'];
        $aData['jnet_bill_no']  = $aInputData['jnet_bill_no'];
        $aData['agent_bill_id'] = $aInputData['agent_bill_id'];
        $aData['pay_type']      = $this->iPayType;
        $aData['pay_amt']       = $aInputData['pay_amt'];
        $aData['remark']        = $aInputData['remark'];
        return $this->compileSign($oPaymentAccount, $aData, $aNeedKeys);
    }

    public function & compileInputData($oPaymentPlatform, $oPaymentAccount, $oDeposit, $oBank, & $sSafeStr) {
        $aData               = [
            'version'         => $this->iVersion,
            'agent_id'        => $oPaymentAccount->account,
            'agent_bill_id'   => $oDeposit->order_no,
            'agent_bill_time' => date('YmdHis', strtotime($oDeposit->created_at)),
            'pay_type'        => $this->iPayType,
            'pay_amt'         => $oDeposit->amount,
            'notify_url'      => $oPaymentPlatform->notify_url,
            'user_ip'         => Tool::getClientIp(),
        ];
        $aData['sign']       = $sSafeStr            = $this->compileSign($oPaymentAccount, $aData);
        $aData['is_phone']   = 1;
        $aData['goods_name'] = 'Vitrual' . intval(mt_rand(1, 99999));
        $aData['return_url'] = '';
        $aData['goods_note'] = '';
        return $aData;
    }

    public function & compileQueryData($oPaymentAccount, $sOrderNo, $sServiceOrderNo) {
        $iCreatedAt = 0;
        $oDeposit   = Deposit::getDepositByNo($sOrderNo);
        if ($oDeposit) {
            $iCreatedAt = date('YmdHis', strtotime($oDeposit->created_at));
        }
        $aData           = [
            'version'         => $this->iVersion,
            'agent_id'        => $oPaymentAccount->account,
            'agent_bill_id'   => $sOrderNo,
            'agent_bill_time' => $iCreatedAt,
            'return_mode'     => 1,
        ];
        $aData['sign']   = $this->compileSign($oPaymentAccount, $aData);
        $aData['remark'] = '';
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
        $aQueryStr  = [];
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
        file_put_contents('/tmp/hyzfb_' . $sOrderNo, $sResponse);
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

        if ($aResonses['result'] == '0') {      // NO ORDER
            return self::PAY_NO_ORDER;
        }

        $sDinpaySign = $aResonses['sign'];

        $aCompileSignNeed = [
            'agent_id'      => $aResonses['agent_id'],
            'agent_bill_id' => $aResonses['agent_bill_id'],
            'jnet_bill_no'  => $aResonses['jnet_bill_no'],
            'pay_type'      => 50,
            'result'        => $aResonses['result'],
            'pay_amt'       => $aResonses['pay_amt'],
            'pay_message'   => $aResonses['pay_message'],
            'remark'        => $aResonses['remark'],
        ];
        $sSign            = $this->compileQuerySign($oPaymentAccount, $aCompileSignNeed);

        if ($sSign != $sDinpaySign) {
            return self::PAY_SIGN_ERROR;
        }

        if ($aResonses['result'] == 1 && $aResonses['pay_amt'] > 0) {
            return self::PAY_SUCCESS;
        }

        return self::PAY_UNPAY;
    }

    public static function & compileCallBackData($aBackData, $sIp) {
        $aData = [
            'order_no'         => $aBackData['agent_bill_id'],
            'service_order_no' => $aBackData['jnet_bill_no'],
            'merchant_code'    => $aBackData['agent_id'],
            'amount'           => $aBackData['pay_amt'],
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
            'service_order_no' => $aResponses['jnet_bill_no'],
            'order_no'         => $aResponses['agent_bill_id'],
        ];
        return $data;
    }

}
