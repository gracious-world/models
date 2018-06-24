<?php

/**
 * 智付平台
 *
 * @author white
 */
class PaymentZHIFU extends BasePlatform {

    public $successMsg                   = 'SUCCESS';
    public $signColumn                   = 'sign';
    public $accountColumn                = 'merchant_code';
    public $orderNoColumn                = 'order_no';
    public $paymentOrderNoColumn         = 'trade_no';
    public $successColumn                = 'trade_status';
    public $successValue                 = 'SUCCESS';
    public $amountColumn                 = 'order_amount';
    public $bankNoColumn                 = 'bank_seq_no';
    public $unSignColumns                = ['sign_type', 'sign'];
    public $serviceOrderTimeColumn       = 'trade_time';
    public $queryResultColumn            = 'trade_status';
    public $signNeedColumnsForPay        = [
        "bank_code",
        "client_ip",
        "extend_param",
        "extra_return_param",
        "input_charset",
        "interface_version",
        "merchant_code",
        "notify_url",
        "order_amount",
        "order_no",
        "order_time",
        "pay_type",
        "product_code",
        "product_desc",
        "product_name",
        "product_num",
        "redo_flag",
        "return_url",
        "service_type",
        "show_url",
    ];
    public $signNeedColumnsForNotify     = [
        'bank_seq_no',
        'extra_return_param',
        'interface_version',
        'merchant_code',
        'notify_id',
        'notify_type',
        'order_amount',
        'order_no',
        'order_time',
        'trade_no',
        'trade_status',
        'trade_time',
    ];
    public $signNeedColumnsForQuery      = [
        'interface_version',
        'merchant_code',
        'order_no',
        'service_type',
        'trade_no',
    ];
    public $signNeedColumnsForQueryCheck = [
        'merchant_code',
        'order_amount',
        'order_no',
        'order_time',
        'trade_no',
        'trade_status',
        'trade_time',
    ];

    /**
     * 整理 sign 字符串
     * @author lucda
     * @date 2016-11-15
     * @param $aInputData
     * @param $aNeedColumns
     * @return string
     */
    private function signStr($aInputData, $aNeedColumns = []) {
        $aData = [];
        if (!$aNeedColumns) {
            $aNeedColumns = array_keys($aInputData);
        }
        foreach ($aNeedColumns as $sColumn) {
            if (isset($aInputData[$sColumn]) && $aInputData[$sColumn] != '') {
                $aData[$sColumn] = $sColumn . '=' . $aInputData[$sColumn];
            }
        }
        return implode('&', $aData);
    }

    /**
     * 组装 sign . $aNeedColumns 是需要组装成 sign 字符串 的数据
     * @author lucda
     * @date 2016-11-15
     * @param $oPaymentAccount
     * @param $aInputData
     * @param array $aNeedColumns
     * @return string
     */
    public function compileSign($oPaymentAccount, $aInputData, $aNeedColumns = []) {
        $sSignStr = $this->signStr($aInputData, $aNeedColumns);

        $sMerchantPrivateKey = $oPaymentAccount->private_key;
        $sMerchantPrivateKey = openssl_get_privatekey($sMerchantPrivateKey);
        openssl_sign($sSignStr, $sSignInfo, $sMerchantPrivateKey, OPENSSL_ALGO_MD5);
        $sSign               = base64_encode($sSignInfo);
        return $sSign;
    }

    /**
     * 组装支付数据
     * @author lucda
     * @date    2016-11-16
     * @param $oPaymentPlatform
     * @param $oPaymentAccount
     * @param $oDeposit
     * @param $oBank
     * @param $sSafeStr
     * @return array
     */
    public function & compileInputData($oPaymentPlatform, $oPaymentAccount, $oDeposit, $oBank, & $sSafeStr) {
        $aInputData = [
            'bank_code'         => $oBank ? $oBank->identifier : null,
            'input_charset'     => 'UTF-8',
            'interface_version' => 'V3.0',
            'merchant_code'     => $oPaymentAccount->account,
            'notify_url'        => $oPaymentPlatform->notify_url,
            'order_amount'      => $oDeposit->amount,
            'order_no'          => $oDeposit->order_no,
            'order_time'        => date('Y-m-d H:i:s'),
            'product_name'      => 'Vitrual' . intval(mt_rand(1, 99999)),
            'return_url'        => $oPaymentPlatform->return_url, // 可选，同步回调地址
            'service_type'      => 'direct_pay',
        ];

        $aInputData['sign']      = $this->compileSign($oPaymentAccount, $aInputData, $this->signNeedColumnsForPay);
        $aInputData['sign_type'] = 'RSA-S';

        return $aInputData;
    }

    /**
     * 回调 验证 给到的sign是否正确
     * @author lucda
     * @date    2016-11-16
     * @param $oPaymentAccount
     * @param $aInputData
     * @param $aNeedColumns
     * @return bool
     */
    public function validateSign($oPaymentAccount, $aInputData, $aNeedColumns) {
        $sSignStr         = $this->signStr($aInputData, $aNeedColumns);
        $sDinpaySign      = base64_decode($aInputData['sign']);
        $sDinpayPublicKey = $oPaymentAccount->public_key;
        $sDinpayPublicKey = openssl_get_publickey($sDinpayPublicKey);
        if (openssl_verify($sSignStr, $sDinpaySign, $sDinpayPublicKey, OPENSSL_ALGO_MD5)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 组装 查询订单 数据
     * @author lucda
     * @date    2016-11-16
     * @param $oPaymentAccount
     * @param $sOrderNo
     * @param $sServiceOrderNo
     * @return array
     */
    public function & compileQueryData($oPaymentAccount, $sOrderNo, $sServiceOrderNo) {
        $aData              = [
            'interface_version' => 'V3.0',
            'merchant_code'     => $oPaymentAccount->account,
            'order_no'          => $sOrderNo,
            'service_type'      => 'single_trade_query',
            'trade_no'          => $sServiceOrderNo,
        ];
        $aData['sign']      = urlencode($this->compileSign($oPaymentAccount, $aData, $this->signNeedColumnsForQuery));
        $aData['sign_type'] = 'RSA-S';
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
        $aData     = $this->compileQueryData($oPaymentAccount, $sOrderNo, $sServiceOrderNo);
        $sPostData = $this->signStr($aData);
        $ch        = curl_init();
        curl_setopt($ch, CURLOPT_URL, $oPaymentPlatform->getQueryUrl($oPaymentAccount));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //将数据传给变量
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); //取消身份验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $sPostData);
        $response  = curl_exec($ch); //接收返回信息
        @file_put_contents('/tmp/zf_' . $sOrderNo, $response);

        if (curl_errno($ch)) {//出错则显示错误信息
            print curl_error($ch);
        }
        curl_close($ch); //关闭curl链接

        if ($response === '') {     // query failed
            return self::PAY_QUERY_FAILED;
        }
        $resParser = xml_parser_create();
        if (!xml_parse_into_struct($resParser, $response, $values, $index)) {   // parse error
            return self::PAY_QUERY_PARSE_ERROR;
        }

        $aResonses = [];
        foreach ($values as $aInfo) {
            if ($aInfo['type'] != 'complete') {
                continue;
            }
            $aResonses[strtolower($aInfo['tag'])] = $aInfo['value'];
        }
        if ($aResonses['is_success'] == 'F') {      // NO ORDER
            return self::PAY_NO_ORDER;
        }

        if (!$this->validateSign($oPaymentAccount, $aResonses, $this->signNeedColumnsForQueryCheck)) {
            return self::PAY_SIGN_ERROR;
        }

        switch ($aResonses['trade_status']) {
            case 'UNPAY':
                return self::PAY_UNPAY;
            case 'SUCCESS':
                return self::PAY_SUCCESS;
        }
    }

    public static function & compileCallBackData($aData, $sIp) {
        $aData = [
            'order_no'         => $aData['order_no'],
            'service_order_no' => $aData['trade_no'],
            'merchant_code'    => $aData['merchant_code'],
            'amount'           => $aData['order_amount'],
            'ip'               => $sIp,
            'status'           => DepositCallback::STATUS_CALLED,
            'post_data'        => var_export($aData, true),
            'callback_time'    => time(),
            'callback_at'      => date('Y-m-d H:i:s'),
            'referer'          => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null,
            'http_user_agent'  => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null
        ];
        return $aData;
    }

    public static function & getServiceInfoFromQueryResult(& $aResponses) {
        $aData = [
            'service_order_no' => $aResponses['trade_no'],
            'service_time'     => $aResponses['trade_time'],
        ];
        return $aData;
    }

}
