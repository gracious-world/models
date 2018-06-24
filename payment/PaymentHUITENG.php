<?php

/**
 * 汇腾平台
 * @author Tino
 */
class PaymentHUITENG extends BasePlatform {

    public $successMsg = '0000';
    public $signColumn = 'signature';
    public $accountColumn = 'merNo';
    public $orderNoColumn = 'orderNo';
    public $paymentOrderNoColumn = 'orderNo';
    public $successColumn = 'respCode';
    public $successValue = '0000';
    public $amountColumn = 'transAmt';
    public $bankNoColumn = 'bankCode';
    public $unSignColumns = ['status', 'beginTime', 'endTime', 'orderAmt', 'signature'];
    public $serviceOrderTimeColumn = '';
    protected $payType = 1; //1网银,2支付宝,3微信
    public $bankTimeColumn = "OrderDate";
    protected $suffix = 'HT';
    public $queryResultColumn = 'respDesc';

    /**
     * 是否需要中转地址
     */
    public $bNeedDirect = false;

    /**
     * 表单提交提交方式
     * @var string 
     */
    public $sHtmlFormSubmitMethod = 'post';
    public $signNeedColumns = [//充值加密字段
        'orderNo',
        'transAmt',
        'merNo',
    ];
    public $signQueryColumns = [//查询加密字段
        'merchantNo',
    ];
    public $signQueryReturnColumns = [//查询响应信息加密字段
        'requestNo',
        'transId',
        'merNo',
    ];
    public $signNotifyColumns = [
        'orderNo',
        'orderDate',
    ];

    protected function signStr($aInputData, $aNeedColumns = []) {
        $sSignStr = '';
        if (!$aNeedColumns) {
            $aNeedColumns = array_keys($aInputData);
        }
        foreach ($aNeedColumns as $sColumn) {
            if (isset($aInputData[$sColumn]) && $aInputData[$sColumn] != '') {
                $sSignStr .= $aInputData[$sColumn];
            }
        }
        return $sSignStr;
    }

    /**
     * sign组建
     * @param $oPaymentAccount
     * @param $aInputData
     * @param array $aNeedKeys
     * @return string
     */
    public function compileSign($oPaymentAccount, $aInputData, $aNeedKeys = []) {

        $sSignStr = $this->signStr($aInputData, $aNeedKeys);
        $sSignStr .= $oPaymentAccount->safe_key;

        return strtoupper(md5($sSignStr));
    }

    /**
     * 查询sign组建
     * @param $aInputData
     * @param array $aNeedKeys
     * @return string
     */
    public function compileQuerySign($oPaymentAccount, $aInputData, $aNeedKeys = []) {
        $sSignStr = $this->signStr($aInputData, $aNeedKeys);
        $sSignStr .= $oPaymentAccount->safe_key;

        return md5($sSignStr);
    }
    /**
     * 通知签名组建
     * @param $oPaymentAccount
     * @param $aInputData
     * @param array $aNeedKeys
     * @return string
     */
    public function compileSignReturn($oPaymentAccount, $aInputData, $aNeedKeys = []) {
        return $this->compileSign($oPaymentAccount, $aInputData, $this->signNotifyColumns);
    }
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
            'orderNo' => $oDeposit->order_no,
            'merNo' => $oPaymentAccount->account,
            'transId' => '004',
            'notifyUrl' => $oPaymentPlatform->notify_url,
            'returnUrl' => $oPaymentPlatform->return_url,
            'transAmt' => '' . $oDeposit->amount * 100,
            'memo' => 'Vitrual' . intval(mt_rand(1, 99999)),
            'phoneNo' => '12345678901',
            'userType' => '1',
            'channel' => '1',
            'settleType' => '1',
            'bankSegment' => $oBank ? ''.$oBank->identifier : null,
            'cardtType' => '1',
            'spUserid' => '' . intval(mt_rand(1, 999999999)),
        ];
        $aData['signature'] = $sSafeStr = $this->compileSign($oPaymentAccount, $aData, $this->signNeedColumns);
        $aInputData = $this->compileData($aData);

        return $aInputData;
    }
    /**
     * 查询签名组建
     * @param $oPaymentAccount
     * @param $sOrderNo
     * @param $sServiceOrderNo
     * @return array
     */
    public function & compileQueryData($oPaymentAccount, $sOrderNo, $sServiceOrderNo) {
        $aData = [
            'merchantNo' => $oPaymentAccount->account,
            'userOrderNo' => $sOrderNo,
            'listid' => $sServiceOrderNo,
            'transId' => '003',
        ];
        $aData['signature'] = $sSafeStr = $this->compileQuerySign($oPaymentAccount, $aData, $this->signQueryColumns);
        $aInputData = $this->compileData($aData);

        return $aInputData;
    }

    /**
     * 查询结果验签组建
     * @param $aResponse
     * @return array
     */
    public function & compileQueryReturnData($oPaymentAccount, $aResponse) {
        $aData = [
            'merNo' => $oPaymentAccount->account,
            'orderNo' => $aResponse['orderNo'],
            'transAmt' => $aResponse['transAmt'],
            'orderDate' => $aResponse['orderDate'],
            'orderRespCode' => $aResponse['origRespCode'],
            'orderRespDesc' => $aResponse['origRespDesc'],
            'transId' => '004',
            'requestNo' => $aResponse['requestNo'],
        ];
        $sign = $this->compileQuerySign($oPaymentAccount, $aData, $this->signQueryReturnColumns);
        return $sign;
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
        $sResponse = $this->curl_post($oPaymentPlatform->getQueryUrl($oPaymentAccount), $aDataQuery);
        file_put_contents('/tmp/ht_' . $sOrderNo, $sResponse . "\n", FILE_APPEND);
        $aResonses = json_decode($sResponse, true);
        if (!count($aResonses)) {
            return self::PAY_QUERY_PARSE_ERROR;
        }
        if (empty(array_get($aResonses, 'respCode')) || $aResonses['respCode'] != '0000') {     // query failed
            return self::PAY_QUERY_FAILED;
        }
        switch ($aResonses['origRespCode']) {
            case '0000':
                //支付返回成功校验签名
                $sSign = $this->compileQueryReturnData($oPaymentAccount, $aResonses, $this->signQueryReturnColumns);
                if ($sSign != $aResonses['signature']) {
                    return self::PAY_SIGN_ERROR;
                    break;
                }
                return self::PAY_SUCCESS;
                break;
            default:
                //其他状态归结为未支付
                return self::PAY_UNPAY;
                break;
        }
    }

    public function getSuffix() {
        return $this->suffix;
    }

    public static function & compileCallBackData($aBackData, $sIp) {
        $aData = [
            'order_no' => $aBackData['orderNo'],
            'service_order_no' => $aBackData['signature'],
            'merchant_code' => key_exists('merNo', $aBackData) ? $aBackData['merNo'] : 'huiteng',
            'amount' => formatNumber($aBackData['transAmt'], 2),
            'ip' => $sIp,
            'status' => DepositCallback::STATUS_CALLED,
            'post_data' => var_export($aBackData, true),
            'callback_time' => time(),
            'callback_at' => date('Y-m-d H:i:s'),
            'referer' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null,
            'http_user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null
        ];
        return $aData;
    }

    public static function & getServiceInfoFromQueryResult(& $aResponses) {
        $data = [
            'service_order_no' => $aResponses['orderid'],
            'order_no' => $aResponses['billno'],
        ];
        return $data;
    }

    protected function processArrayKey($array) {
        $aNewArray = [];
        foreach ($array as $key => $value) {
            $aNewArray[strtolower($key)] = $value;
        }
        return $aNewArray;
    }

    /**
     * 从数组中取得金额
     * @param array $data
     * @return float
     */
    public function getPayAmount($data) {
        return $data[$this->amountColumn] / 100;
    }

    /**
     * 模拟post请求
     * @param $url
     * @param $data
     * @param bool $is_https
     * @return mixed
     */
    public function curl_post($url, $data, $is_https = false) {
        $ch = curl_init($url);
        if ($is_https) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        //为了支持cookie
//	curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Errno' . curl_error($ch); //捕抓异常
        }
        curl_close($ch);
        return $result;
    }
    /**
     * 组装符合汇腾的数据格式
     * @param array $aData
     * @return array
     */
    protected function compileData(array $aData) {
        $aRes = ['reqJson' => json_encode($aData, 256)];

        return $aRes;
    }
}
