<?php

/**
 * chuangXin
 *
 * @author sad
 *
 */
class PaymentCXZFBWAP extends BasePlatform
{

    public $successMsg = '{"code": 1}';
    public $signColumn = 'sign';
    public $accountColumn = 'partner';
    public $orderNoColumn = 'ordernumber';
    public $paymentOrderNoColumn = 'sysnumber';
    public $successColumn = 'orderstatus';
    public $successValue = 1;
    public $amountColumn = 'paymoney';
//	public $bankNoColumn           = 'bankCode';
    public $serviceOrderTimeColumn = 'endtime';

    public $banktype = 21;
    public $version = 1.0;
    public $method = 'Gt.online.query';

    public $bankTimeColumn = "paytime";

    protected $paymentName = 'cxzfbwap';

    //交易签名所需字段
    public $signNeedColumns = [
        'version',
        'method',
        'partner',
        'banktype',
        'paymoney',
        'ordernumber',
        'callbackurl',
        'hrefbackurl',
    ];

    public $querySignNeedColumns = [
        'version',
        'method',
        'partner',
        'ordernumber',
        'sysnumber'
    ];
    public $queryResultSignNeedColumns = [
        'version',
        'partner',
        'ordernumber',
        'sysnumber',
        'status',
        'tradestate',
        'paymoney',
        'banktype',
        'paytime',
        'endtime'
    ];
    //通知签名字段
    public $notifySignNeedColumns = [
        'partner',
        'ordernumber',
        'orderstatus',
        'paymoney',
        'sysnumber',
        'sign'
    ];

    protected function signStr($aInputData, $aNeedColumns = [])
    {
        $sSignStr = '';
        if (!$aNeedColumns) {
            $aNeedColumns = array_keys($aInputData);
        }
//        sort($aNeedColumns);
//        var_dump($aNeedColumns);exit;
        foreach ($aNeedColumns as $sColumn) {
            if (isset($aInputData[$sColumn]) && $aInputData[$sColumn] != '') {
                $sSignStr .= $sColumn . '=' . urlencode($aInputData[$sColumn]) . '&';
            }
        }
        return $sSignStr;
    }

    /**
     * sign组建
     *
     * @param       $oPaymentAccount
     * @param       $aInputData
     * @param array $aNeedKeys
     *
     * @return string
     */
    public function compileSign($oPaymentAccount, $aInputData, $aNeedKeys = [])
    {

        $sSignStr = $this->signStr($aInputData, $aNeedKeys);
        $sSignStr = trim($sSignStr, '&');
        $sSignStr .= $oPaymentAccount->safe_key;
//        exit;
        return md5($sSignStr);
    }


    /**
     * 通知签名组建
     * @param       $oPaymentAccount
     * @param       $aInputData
     * @param array $aNeedKeys
     * @return string
     */
    public function compileSignReturn($oPaymentAccount, $aInputData)
    {
        return $this->compileSign($oPaymentAccount, $aInputData, $this->notifySignNeedColumns);
    }

    /**
     * query sign
     * notice difference with input sign
     * @see compileSign
     *
     * @param $oPaymentAccount
     * @param $aInputData
     * @param array $aNeedKeys
     * @return string
     */
    public function compileQuerySign($oPaymentAccount, $aInputData, $aNeedKeys = [])
    {
        $sSignStr = $this->signStr($aInputData, $aNeedKeys);
        $sSignStr = trim($sSignStr, '&');
        $sSignStr .= 'key=' . $oPaymentAccount->safe_key;
//        exit;
        return md5($sSignStr);
    }

    /**
     * 充值请求表单数据组建
     *
     * @param $oPaymentPlatform
     * @param $oPaymentAccount
     * @param $oDeposit
     * @param $oBank
     * @param $sSafeStr
     *
     * @return array
     */
    public function & compileInputData($oPaymentPlatform, $oPaymentAccount, $oDeposit, $oBank, & $sSafeStr)
    {
        $aData = [
            'version' => $this->version,
            'method' => $this->method,
            'partner' => $oPaymentAccount->account,
            'banktype' => $this->banktype,
            'paymoney' => $oDeposit->amount,
            'ordernumber' => $oDeposit->order_no,
            'callbackurl' => $oPaymentPlatform->notify_url,
        ];

//
//        ksort($aData);
        $aData['sign'] = $sSafeStr = $this->compileSign($oPaymentAccount, $aData, $this->signNeedColumns);
//        var_dump($aData);
//        exit;
        return $aData;
    }

    /**
     * 查询签名组建
     *
     * @param $oPaymentAccount
     * @param $sOrderNo
     * @param $sServiceOrderNo
     *
     */
    public function & compileQueryData($oPaymentAccount, $sOrderNo, $sServiceOrderNo)
    {
        $oDeposit = UserDeposit::getDepositByNo($sOrderNo);
        $aData = [
            'version' => $this->version,
            'method' => $this->method,
            'partner' => $oPaymentAccount->account,
            'ordernumber' => $sOrderNo,
            'sysnumber' => $sServiceOrderNo,
        ];
        $aData['sign'] = $this->compileQuerySign($oPaymentAccount, $aData, $this->querySignNeedColumns);
        return $aData;
    }

    public function & compileQueryResultSign($oPaymentAccount,$aInputData,$aNeedKeys=[]){
        $sSignStr = $this->signStr($aInputData, $aNeedKeys);
        $sSignStr = trim($sSignStr, '&');
        $sSignStr .= 'key=' . $oPaymentAccount->safe_key;
//        exit;
        return $aInputData['sign'] == md5($sSignStr);
    }


    /**
     * Query from Payment Platform
     *
     * @param PaymentPlatform $oPaymentPlatform
     * @param string $sOrderNo
     * @param string $sServiceOrderNo
     * @param array & $aResponses
     *
     * @return integer | boolean
     *  1: Success
     *  -1: Query Failed
     *  -2: Parse Error
     *  -3: Sign Error
     *  -4: No Order
     *  -5: Unpay
     */
    public function queryFromPlatform($oPaymentPlatform, $oPaymentAccount, $sOrderNo, $sServiceOrderNo = null, & $aResponses)
    {
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
        file_put_contents('/tmp/' . $this->paymentName . '_' . $sOrderNo, $sResponse . "\n", FILE_APPEND);
        if (curl_errno($ch)) {//出错则显示错误信息
            print curl_error($ch);
        }
        curl_close($ch); //关闭curl链接
        $aResponses = json_decode($sResponse, true);
        //返回格式不对
        if (!$aResponses || !isset($aResponses['status'])) {
            return self::PAY_QUERY_PARSE_ERROR;
        }

        if ($aResponses['status'] != 1) {
            //支付返回成功校验签名
            return self::PAY_NO_ORDER;
        }
        if($aResponses['tradestate']!=1){
            return self::PAY_UNPAY;
        }

        $bSucc = $this->compileQueryResultSign($oPaymentAccount,$aResponses,$this->queryResultSignNeedColumns);
        if(!$bSucc){
            return self::PAY_SIGN_ERROR;
        }

        return self::PAY_SUCCESS;
    }

    /**
     * @param array $aBackData
     * @param string $sIp
     *
     * @return array
     */
    public static function & compileCallBackData($aBackData, $sIp)
    {
        $oDeposit = Deposit::getDepositByNo($aBackData['ordernumber']);
        $aData = [
            'order_no' => $aBackData['ordernumber'],
            'service_order_no' => $oDeposit ? date('YmdHis', strtotime($oDeposit->created_at)) : $aBackData['sysnumber'],
            'merchant_code' => $aBackData['partner'],
            'amount' => $aBackData['paymoney'],
            'ip' => $sIp,
            'status' => DepositCallback::STATUS_CALLED,
            'post_data' => var_export($aBackData, true),
            'callback_time' => time(),
            'callback_at' => date('Y-m-d H:i:s'),
            'referer' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null,
            'http_user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null,
        ];
        return $aData;
    }

    public static function & getServiceInfoFromQueryResult(& $aResponses)
    {
        $data = [
            'service_order_no' => $aResponses['sysnumber'],
            'order_no' => $aResponses['ordernumber'],
        ];
        return $data;
    }

}
