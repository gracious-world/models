<?php

/**
 * 智付平台
 *
 * @author white
 */
class PaymentLEFU extends BasePlatform {

    const STATUS_DOING = 0;
    const STATUS_FINISHED = 1;
    const STATUS_FAILED = 2;
    const STATUS_UNKNOWN = 3;
    const PAY_FAILED = -7;

    public $bankTimeColumn = "request_time";
    public $feeColumn = "amount_fee";
    public $oDeposit;
    public $iTradeId;

    public $sCharSet = "utf-8";
    public $sServiceTrade = "gateway_pay";
    public $sServiceQuery = "find_trade";
//    public $sSignType = "MD5";
    public $sSignType = "SHA1WITHRSA";

    public $successMsg = 'SUCCESS';
    public $signColumn = 'sign';
    public $accountColumn = 'merchant_code';
    public $orderNoColumn = 'order_no';
    public $paymentOrderNoColumn = 'trade_id';
    public $successColumn = 'status';
    public $successValue = 1;
    public $amountColumn = 'amount_str';
    public $bankNoColumn = 'bank_seq_no';
    public $unSignColumns = ['sign_type', 'sign'];
    public $serviceOrderTimeColumn = 'request_time';
    public $queryResultColumn = 'status';
    public $aDataNotify = [];
    public $signNeedColumnsForPay = [
            'partner',
            'service',
            'out_trade_no',
            'amount_str',
            'tran_ip',
            'buyer_name',
            'buyer_contact',
            'good_name',
            'request_time',
            'return_url',
            'verfication_code'
    ];

    public $signNeedColumnsForNotify = [
            'trade_id',
            'out_trade_no',
            'amount_str',
            'amount_fee',
            'status',
            'for_trade_id',
            'bussiness_type',
            'create_time',
            'modified_time',
            'remark'
    ];

    public $queryReturnColumns = [
            'trade_id',
            'out_trade_no',
            'amount_str',
            'amount_fee',
            'status',
            'for_trade_id',
            'bussiness_type',
            'create_time',
            'modified_time',
            'remark'
    ];

    public $postData = [
            'service' => 'find_trade',
            'input_charset' => 'UTF-8',
            'sign_type' => 'SHA1WITHRSA',
            'partner' => '',
            'content' => '',
            'sign' => ''
    ];


    public $signNeedColumnsForQuery = [
            'service',
            'partner',
            'out_trade_no',
    ];
    public $signNeedColumnsForQueryCheck = [];

    /**
     * 整理 sign 字符串
     *
     * @author lucda
     * @date   2016-11-15
     *
     * @param $aInputData
     * @param $aNeedColumns
     *
     * @return string
     */
    private function signStr($aInputData, $aNeedColumns = []) {
        $aData = [];
        if (!$aNeedColumns) {
            $aNeedColumns = array_keys($aInputData);
        }
        foreach ($aNeedColumns as $sColumn) {
            if (isset($aInputData[$sColumn])) {
                $aData[$sColumn] = $sColumn . '=' . $aInputData[$sColumn];
            }
        }

        $sSignStr = implode('&', $aData);
        return $sSignStr;
    }

    /**
     * 组装 sign . $aNeedColumns 是需要组装成 sign 字符串 的数据
     *
     * @author lucky
     * @date   2016-11-15
     *
     * @param       $oPaymentAccount
     * @param       $aInputData
     * @param array $aNeedColumns
     *
     * @return string
     */
    public function compileSign($oPaymentAccount, $aInputData, $aNeedColumns = []) {
        $sSignStr = $this->signStr($aInputData, $aNeedColumns);
        $sSignStr .= '&verfication_code=' . $oPaymentAccount->safe_key;

        return md5($sSignStr);
    }

    /**
     * TODO IMPROVE
     * 比较回调通知的签名
     *
     * @param $aInputData
     * @param $sPostedSign
     * @param $sPubKey
     *
     * @return bool
     */
    public function compileSignReturn($aInputData, $aData) {
        return $aData[$this->signColumn];
    }


    /**
     * 组装支付数据
     *
     * @author  lucky
     * @date    2016-11-16
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
        $aInputData = [
                'partner' => $oPaymentAccount->account,
                'service' => $this->sServiceTrade,
                'out_trade_no' => $oDeposit->order_no,
                'amount_str' => $oDeposit->amount,
                'tran_ip' => $oDeposit->ip,
                'good_name' => 'Vitrual' . intval(mt_rand(1, 99999)),
                'request_time' => date("ymdHis", strtotime($oDeposit->created_at->toDateTimeString())),//TODO improve
                'return_url' => $oPaymentPlatform->notify_url,
        ];
        $aInputData['sign'] = $this->compileSign($oPaymentAccount, $aInputData, $this->signNeedColumnsForPay);
        $aInputData['sign_type'] = 'MD5';
        $aInputData['input_charset'] = $this->sCharSet;
        $aInputData['redirect_url'] = $oPaymentPlatform->return_url;
        $aInputData['goods_detail'] = 'Vitrual' . intval(mt_rand(1, 99999));
        $aInputData['bank_code'] = $oBank ? $oBank->identifier : null;

        return $aInputData;
    }

    public function getPayAmount($data) {
        return $this->oDeposit->amount;
    }

    /**
     * 组装 查询订单 数据
     *
     * @author  lucky
     * @date    2016-11-16
     *
     * @param $oPaymentAccount
     * @param $sOrderNo
     * @param $sServiceOrderNo
     *
     * @return array
     */
    public function & compileQueryData($oPaymentAccount, $sOrderNo, $sServiceOrderNo) {//查询
        $aContentData = [
                'service' => 'find_trade',
                'partner' => $oPaymentAccount->account,
                'out_trade_no' => $sOrderNo,
        ];

        $aData = $this->postData;
        //content加密
        $aData['content'] = $this->encryptContents($oPaymentAccount, $aContentData, $this->signNeedColumnsForQuery);
        //sign
        $aData['sign'] = $this->compileQuerySign($oPaymentAccount, $aContentData, $this->signNeedColumnsForQuery);
        $aData['partner'] = $oPaymentAccount->account;
        return $aData;
    }

    public function compileQuerySign($oPaymentAccount, $aInputData, $aNeedKeys = []) {
        ksort($aInputData);
        $sSignStr = $this->signStr($aInputData, $aNeedKeys);
        openssl_sign($sSignStr, $sSign, $oPaymentAccount->private_key);

        return urlencode(base64_encode($sSign));
    }


    /**
     * content参数加密
     *
     * @author zero
     *
     * @param $oPaymentAccount
     * @param $aData
     * @param $aNeedColumns
     *
     * @return string
     */
    public function encryptContents($oPaymentAccount, $aData, $aNeedColumns = []) {
        $encryptData = '';
        ksort($aData);

        $sData = $this->signStr($aData, $aNeedColumns);
        foreach (str_split($sData, 117) as $chunk) {
            if (openssl_public_encrypt($chunk, $encrypted, $oPaymentAccount->public_key)) {
                $encryptData .= $encrypted;
            }
        }

        return urlencode(base64_encode($encryptData));
    }


    /**
     * Query from Payment Platform
     *
     * @param PaymentPlatform $oPaymentPlatform
     * @param string          $sOrderNo
     * @param string          $sServiceOrderNo
     * @param array           & $aResonses
     *
     * @return integer | boolean
     *  1: Success
     *  -1: Query Failed
     *  -2: Parse Error
     *  -3: Sign Error
     *  -4: Unpay
     *  -5: Amount Error
     */
    public function queryFromPlatform($oPaymentPlatform, $oPaymentAccount, $sOrderNo, $sServiceOrderNo = null, & $aDecryptContents) {
        $aData = $this->compileQueryData($oPaymentAccount, $sOrderNo, $this->iTradeId);
        $postDataTmp = [];
        foreach ($aData as $k => $v) {
            $postDataTmp[$k] = $k . '=' . $v;
        }
        $postData = implode('&', $postDataTmp);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $oPaymentPlatform->getQueryUrl($oPaymentAccount));
//        curl_setopt($ch, CURLOPT_URL, "http://service.lepayle.com/api/gateway");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //将数据传给变量
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); //取消身份验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        $response = curl_exec($ch); //接收返回信息
        @file_put_contents('/tmp/lf_' . $sOrderNo, $response . "\n");
        if (curl_errno($ch)) {//出错则显示错误信息
            print curl_error($ch);
        }
        curl_close($ch); //关闭curl链接
        if ($response === '') {     // query failed
            return self::PAY_QUERY_FAILED;
        }
        //json decode
        $aResponse = (array) json_decode($response);
        if (!is_array($aResponse) || !isset($aResponse['is_succ'])) {
            return self::PAY_QUERY_PARSE_ERROR;
        }

        if ($aResponse['is_succ'] === 'F') {
            return self::PAY_QUERY_FAILED;
        }
        if($aResponse['response'] == null){
            return self::PAY_NO_ORDER;
        }
        $file = '/tmp/lf_' . $sOrderNo;
        @chmod($file, 0777);
        //decrypt content into ras
        $sDecryptContents = $this->decryptContents($oPaymentAccount, $aResponse['response'], false);
        if(!$sDecryptContents){
            return self::PAY_QUERY_PARSE_ERROR;
        }
        //验签
        if (!$this->validSign($oPaymentAccount, $sDecryptContents, $aResponse['sign'])) {
            @file_put_contents($file, "sign error" . "\n");
            return self::PAY_SIGN_ERROR;
        }

        //解密contents参数
        $aDecryptContents = (array) json_decode($sDecryptContents);
        if (!is_array($aDecryptContents)) {
            @file_put_contents($file, "query contents decrypt error" . "\n");
            return self::PAY_QUERY_PARSE_ERROR;
        }

        if ($aDecryptContents['status'] == self::STATUS_DOING) { // NO ORDER
            @file_put_contents($file, "order still doing\n", FILE_APPEND);
            return self::PAY_UNPAY;
        } elseif ($aDecryptContents['status'] == self::STATUS_FINISHED) {
            return self::PAY_SUCCESS;
        } elseif ($aDecryptContents['status'] == self::STATUS_FAILED) {
            @file_put_contents($file, "payed failed\n", FILE_APPEND);
            return self::PAY_UNPAY;//TODO IMPROVE
        } else {
            @file_put_contents($file . $sOrderNo, "order status known\n", FILE_APPEND);
            return self::PAY_UNPAY;// un known reason ez. 404
        }

    }


    /**
     * 解密content参数,并jsondecode处理
     *
     * @param $oPaymentAccount
     * @param $sContents
     * @param $bJsonDecode
     *
     * @return array|string
     */
    public static function decryptContents($oPaymentAccount, $sContents, $bJsonDecode = true) {
        $sRes = '';
        $sContents = base64_decode($sContents);

        foreach (str_split($sContents, 128) as $chunk) {
            openssl_private_decrypt($chunk, $decryptData, $oPaymentAccount->private_key);
            $sRes .= $decryptData;
        }

        return $bJsonDecode ? json_decode($sRes, true) : $sRes;
    }


    /**
     * 验签
     *
     * @param $oPaymentAccount
     * @param $sDecrypted
     * @param $sSign
     *
     * @return true|false
     */
    public function validSign($oPaymentAccount, $sDecrypted, $sSign) {
        $publicKey = openssl_get_publickey($oPaymentAccount->public_key);
        $sSign = base64_decode($sSign);
        return (bool) openssl_verify($sDecrypted, $sSign, $publicKey);
    }

    public static function & compileCallBackData($aData, $sIp) {
        $aContent = $aData['content'];
        $iOrderNo = $aData['out_trade_no'];
        $oDeposit = UserDeposit::where('order_no', $iOrderNo)->first();
        $aData = [
                'order_no' => $iOrderNo,               //TODO fix
                'service_order_no' => $aContent->trade_id,
                'merchant_code' => $oDeposit->merchant_code,
                'amount' => $oDeposit->amount,
                'ip' => $sIp,
                'status' => DepositCallback::STATUS_CALLED,
                'post_data' => var_export($aData, true),
                'callback_time' => time(),
                'callback_at' => date('Y-m-d H:i:s'),
                'referer' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null,
                'http_user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null
        ];
        return $aData;
    }

    public static function & getServiceInfoFromQueryResult(& $aResponses) {

        $data = [
                'service_order_no' => $aResponses['trade_id'],
                'order_no' => $aResponses['out_trade_no'],
        ];

        return $data;
    }


}
