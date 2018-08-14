<?php

/**
 * 云盛平台
 * @author zero
 */
class PaymentYUNSHENG extends BasePlatform {

    public $successMsg = 'ok';
    public $signColumn = 'sign';
    public $accountColumn = 'merchantId';
    public $orderNoColumn = 'billno';
    public $paymentOrderNoColumn = 'orderId';
    public $successColumn = 'success';
    public $successValue = 'Success';
    public $amountColumn = 'amount';
    public $bankNoColumn = 'bankCode';
    public $unSignColumns = [];
    public $serviceOrderTimeColumn = '';

    protected $payType = 1;//1网银,2支付宝,3微信
    public $bankTimeColumn = "OrderDate";
    public $signNeedColumns = [ //充值请求
        'billNo',
        'amount',
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

        return md5($sSignStr);
    }

    /**
     * 查询sign组建
     * @param $aInputData
     * @param array $aNeedKeys
     * @return string
     */
    public function compileQuerySign($aInputData,$aNeedKeys = []){
        $sSignStr = $this->signStr($aInputData, $aNeedKeys);

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
        $aData = [
            'success' => $aInputData['success'],
            'merchantId' => $aInputData['merchantId'],
            'billNo' => $aInputData['billno'],
            'amount' => $aInputData['amount'],
            'orderDate' => $aInputData['orderDate'],
        ];
        return $this->compileSign($oPaymentAccount, $aData, $aNeedKeys);
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
        $aSignData = [
            'billNo' => $oDeposit->order_no,
            'amount' => $oDeposit->amount,
            'orderDate' => date('YmdHis', strtotime($oDeposit->created_at)),
        ];
        $aUnSignData = [
            'notifyUrl' => $oPaymentPlatform->notify_url,
            'goodsName' => 'Vitrual' . intval(mt_rand(1, 99999)),
            'payType' => $this->payType,
            'merchantId' => $oPaymentAccount->account,
            'bankCode' => $oBank ? $oBank->identifier : null,
        ];
        $aUnSignData['sign'] = $sSafeStr = $this->compileSign($oPaymentAccount, $aSignData, $this->signNeedColumns);
        $aData = array_merge($aSignData, $aUnSignData);
        return $aData;
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
            'merchantId' => $oPaymentAccount->account,
            'billNo' => $sOrderNo,
        ];
        $aData['sign'] = $this->compileQuerySign($aData);

        return $aData;
    }

    /**
     * 查询结果验签组建
     * @param $aResponse
     * @return array
     */
    public function & compileQueryReturnData($aResponse) {
        $aData = [
            'merchantId' => $aResponse['merchantid'],
            'billNo' => $aResponse['billno'],
            'amount' => $aResponse['amount'],
            'orderDate' => $aResponse['orderdate'],
            'orderStatus' => $aResponse['orderstatus'],
            'orderDescription' => $aResponse['orderdescription'],
            'orderId' => $aResponse['orderid'],
            'createdDate' => $aResponse['createddate'],
            'payType' => $aResponse['paytype'],
        ];

        $sign = $this->compileQuerySign($aData);
        return $sign;
    }

    /**
     * Query from Payment Platform
     * @param PaymentPlatform $oPaymentPlatform
     * @param string $sOrderNo
     * @param string $sServiceOrderNo
     * @param array & $aResponses
     * @return integer | boolean
     *  1: Success
     *  -1: Query Failed
     *  -2: Parse Error
     *  -3: Sign Error
     *  -4: No Order
     *  -5: Unpay
     */
    public function queryFromPlatform($oPaymentPlatform, $oPaymentAccount, $sOrderNo, $sServiceOrderNo = null, & $aResponses) {
        $aDataQuery = $this->compileQueryData($oPaymentAccount, $sOrderNo, $sServiceOrderNo);
        $sDataQuery = http_build_query($aDataQuery);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $oPaymentPlatform->getQueryUrl($oPaymentAccount));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //将数据传给变量
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); //取消身份验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $sDataQuery);
        $sResponse = curl_exec($ch); //接收返回信息
        file_put_contents('/tmp/ys_' . $sOrderNo, $sResponse . "\n",FILE_APPEND);
        if (curl_errno($ch)) {//出错则显示错误信息
            print curl_error($ch);
        }

        curl_close($ch); //关闭curl链接
        if ($sResponse === '') {     // query failed
            return self::PAY_QUERY_FAILED;
        }

        if(trim($sResponse) == '订单不存在！'){
            return self::PAY_NO_ORDER;
        }
        $aQueryResonses = json_decode($sResponse, true);
        if (!count($aQueryResonses)) {
            return self::PAY_QUERY_PARSE_ERROR;
        }
        if($aQueryResonses['Success'] != 1){
            return self::PAY_QUERY_FAILED;
        }
        $aResponses = $this->processArrayKey($aQueryResonses['Order']);
        switch ($aResponses['orderstatus']) {
            //将查询结果处于等待支付和支付失败归为未支付
            case 'Waiting':
                return self::PAY_UNPAY;
                break;
            case 'Failure':
                return self::PAY_UNPAY;
                break;
            case 'Success':
                //支付返回成功校验签名
                $sSign = $this->compileQueryReturnData($aResponses);
                if ($sSign != $aResponses['sign']) {
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

    public static function & compileCallBackData($aBackData, $sIp) {
        $aData = [
            'order_no' => $aBackData['billno'],
            'service_order_no' => $aBackData['orderId'],
            'merchant_code' => $aBackData['merchantId'],
            'amount' => formatNumber($aBackData['amount'],2),
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
}
