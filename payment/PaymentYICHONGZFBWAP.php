<?php

/**
 *
 *
 * @author sad
 *
 */
class PaymentYICHONGZFBWAP extends BasePlatform
{

    public $successMsg = '{"code": 1}';
    public $signColumn = 'sign';
    public $accountColumn = 'id';
    public $orderNoColumn = 'pay_no';
    public $paymentOrderNoColumn = 'trade_no';
    public $successColumn = 'status';
    public $successValue = 1;
    public $amountColumn = 'money';
//	public $bankNoColumn           = 'bankCode';
    public $serviceOrderTimeColumn = 'paytime';

    public $type = 21;

    public $bankTimeColumn = "createtime";

    protected $paymentName = 'yichongzfbwap';

    //交易签名所需字段
    public $signNeedColumns = [
        'id',
        'pay_id',                 //这是默认的充值用户 因为我们演示的数据库充值 只有该用户名 如正式使用请为空
        'price',                    //充值金额
        'order_no',  //充值订单
        'timestamp',                  //当前时间戳
        'type'
    ];

    public $querySignNeedColumns = [
        'id',
        'order_no'
    ];

    //通知签名字段
    public $notifySignNeedColumns = [
        'pay_no',
        'trade_no',
        'type',
        'pay_id',
        'money',
        'status',
        'param',
        'createtime',
        'endtime',
        'paytime',
        'nonce',
        'timestamp'
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
                $sSignStr .= $sColumn.'='.urlencode($aInputData[$sColumn]).'&';
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
        $sSignStr = trim($sSignStr,'&');
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
            'id' => $oPaymentAccount->account,
            'pay_id' => 'hzadmin',                 //这是默认的充值用户 因为我们演示的数据库充值 只有该用户名 如正式使用请为空
            'price' => $oDeposit->amount,                    //充值金额
            'order_no' => $oDeposit->order_no,  //充值订单
            'timestamp' => time(),                  //当前时间戳
            'type' => 4
        ];

//        $aData=[
//            'id' => $oPaymentAccount->account,
//            'pay_id' => 'yawuyu',                 //这是默认的充值用户 因为我们演示的数据库充值 只有该用户名 如正式使用请为空
//            'price' => 100.01,                    //充值金额
//            'order_no' => 30000000009650, //充值订单
//            'timestamp' => time(),                  //当前时间戳
//            'type' => 4
//        ];

        ksort($aData);
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
            'id' => $oPaymentAccount->account,
            'order_no' => $sOrderNo,
        ];
        $aData['sign'] = $this->compileSign($oPaymentAccount, $aData, $this->querySignNeedColumns);
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
        if (!$aResponses || !isset($aResponses['code'])) {
            return self::PAY_QUERY_PARSE_ERROR;
        }

        if ($aResponses['code'] != 1) {
            //支付返回成功校验签名
                return self::PAY_QUERY_FAILED;
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
        $oDeposit = Deposit::getDepositByNo($aBackData['pay_no']);
        $aData = [
            'order_no' => $aBackData['pay_no'],
            'service_order_no' => $oDeposit ? date('YmdHis', strtotime($oDeposit->created_at)) : $aBackData['trade_no'],
            'merchant_code' => $aBackData['pay_id'],
            'amount' => $aBackData['money'],
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
            'service_order_no' => $aResponses['trade_no'],
            'order_no' => $aResponses['pay_no'],
        ];
        return $data;
    }

}
