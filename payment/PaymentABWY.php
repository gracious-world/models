<?php

/**
 * AO BANG WANG YIN
 *
 * @author sad
 *
 */
class PaymentABWY extends BasePlatform
{

    public $successMsg = 'SUCCESS';
    public $signColumn = 'sign';
    public $accountColumn = 'trx_key';
    public $orderNoColumn = 'request_id';
    public $paymentOrderNoColumn = 'pay_request_id';
    public $successColumn = 'rsp_code';
    public $successValue = "0000";
    public $amountColumn = 'rsp_code';
//	public $bankNoColumn           = 'bankCode';
    public $serviceOrderTimeColumn = 'trx_time';

    public $productType = '50103';
    public $bankCode = '1103';


    protected $paymentName = 'abwy';

    //交易签名所需字段
    public $signNeedColumns = [
        'trx_key',
        'ord_amount',
        'request_id',
        'request_ip',
        'product_type',
        'request_time',
        'goods_name',
        'bank_code',
        'return_url',
       'callback_url'
    ];

    public $querySignNeedColumns = [
        'trx_key',
        'request_id',
    ];
    public $queryResultSignNeedColumns = [
        'rsp_code',
        'ord_amount',
        'request_id',
        'ord_status',
        'pay_request_id',
        'complete_date',
        'rsp_msg',
    ];
    //通知签名字段
    public $notifySignNeedColumns = [
        'trx_key',
        'ord_amount',
        'request_id',
        'trx_status',
        'request_time',
        'goods_name',
        'trx_time',
        'pay_request_id',
    ];

    protected function signStr($aInputData, $aNeedColumns = [])
    {
        $sSignStr = '';
        if (!$aNeedColumns) {
            $aNeedColumns = array_keys($aInputData);
        }
        sort($aNeedColumns);
//        var_dump($aNeedColumns);exit;
        foreach ($aNeedColumns as $sColumn) {
            if (isset($aInputData[$sColumn]) && $aInputData[$sColumn] != '') {
                $sSignStr .= $sColumn . '=' .$aInputData[$sColumn] . '&';
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
        $sSignStr .='&secret_key='.$oPaymentAccount->safe_key;
        return strtoupper(md5($sSignStr));
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
        return $this->compileSign($oPaymentAccount, $aInputData, $this->notifySignNeedColumns);
    }

    public function & compileQueryResultSign($oPaymentAccount,$aInputData,$aNeedKeys=[])
    {
        return $this->compileSign($oPaymentAccount, $aInputData, $this->compileQueryResultSign());
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
            'trx_key' => $oPaymentAccount->account,
            'ord_amount' => $oDeposit->amount,
            'request_id' => $oDeposit->order_no,
            'request_ip' => Tool::getClientIp(),
            'product_type' => $this->productType,
            'goods_name' => 'hz' ,
            'request_time' => date('YmdHis') ,
            'bank_code' => $this->bankCode,
            'return_url' => $oPaymentPlatform->return_url,
            'callback_url' => $oPaymentPlatform->notify_url,

        ];

//
        ksort($aData);
        $aData['sign'] =  $this->compileSign($oPaymentAccount, $aData, $this->signNeedColumns);
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
            'trx_key' => $oPaymentAccount->account,
            'request_id' => $sOrderNo,
        ];
        $aData['sign'] = $this->compileQuerySign($oPaymentAccount, $aData, $this->querySignNeedColumns);
        return $aData;
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
        if (!$aResponses || !isset($aResponses['rsp_code'])) {
            return self::PAY_QUERY_PARSE_ERROR;
        }

        if ($aResponses['rsp_code'] != 1) {
            //支付返回成功校验签名
            return self::PAY_NO_ORDER;
        }

        if($aResponses['ord_status']!=1){
            return self::PAY_UNPAY;
        }

        $sSign = $this->compileQueryResultSign($oPaymentAccount, $aResponses, $this->queryResultSignNeedColumns);
        if ($sSign != $aResponses['sign']) {
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
        $oDeposit = Deposit::getDepositByNo($aBackData['request_id']);
        $aData = [
            'order_no' => $aBackData['request_id'],
            'service_order_no' => $oDeposit ? date('YmdHis', strtotime($oDeposit->created_at)) : $aBackData['request_id'],
            'merchant_code' => $aBackData['trx_key'],
            'amount' => $aBackData['ord_amount'],
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
            'service_order_no' => $aResponses['request_id'],
            'order_no' => $aResponses['request_id'],
        ];
        return $data;
    }

}
