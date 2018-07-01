<?php

/**
 * Created by PhpStorm.
 * User: simon
 * Date: 18-1-26
 * Time: 下午4:46
 */
class PaymentHUITIANQQSM extends BasePlatform {
    protected $paymentName= 'huitianqqsm';
    // 保存二维码
    public $saveQr = false;
//    public $isqrcode = 'Y';
    public $qrDirName = 'huitianqq';
    // 回调处理成功时，输出的字符串
    public $successMsg = 'ErrCode=0';
    // 签名变量名
    public $signColumn = 'P_PostKey';
    // 帐号变量名
    public $accountColumn = 'P_UserId';
    // 订单号变量名
    public $orderNoColumn = 'P_OrderId';
    // 渠道方订单号变量名
    public $paymentOrderNoColumn = 'P_OrderId'; //通知结果中没有平台订单号，用商户号代替
    // 回调的数据中，可用于检验是否成功的变量名
    public $successColumn = 'P_ErrCode';
    // 回调的数据中,标志成功的变量值
    public $successValue = '0';
    // 金额变量名
    public $amountColumn = 'P_PayMoney';

    // 回调数据中,平台订单时间变量名
    public $serviceOrderTimeColumn = '';
    // 银行类型 QQ 扫码：89  微信扫码：21
    protected $payment_type = 89;
    // 回调数据中,银行交易时间变量名
    public $bankTimeColumn = "";
    // 参加签名的变量数组
    public $signNeedColumns = [ //充值请求
        'P_UserID',
        'P_OrderID',
        'P_CardID',
        'P_CardPass',
        'P_FaceValue',
        'P_ChannelID',
    ];

    // 通知需要验签的数组
    public $compileNofifySignColumns = [
        'P_UserID',
        'P_OrderID',
        'P_CardID',
        'P_CardPass',
        'P_FaceValue',
        'P_ChannelID',
        'P_PayMoney',
        'P_ErrCode',
    ];
    //查询需要验签的数组
    public $querySignNeedColumns = [
        'P_UserId',
        'P_OrderId',
        'P_ChannelId',
        'P_CardId',
        'P_FaceValue',
    ];
    //查询结果需要验签的数组
    public $queryResultSignNeedColumns = [
        'P_UserId',
        'P_OrderId',
        'P_ChannelId',
        'P_CardId',
        'P_payMoney',
        'P_flag',
        'P_status',
    ];

    //
    protected function signStr($aInputData, $aNeedColumns = []) {
        $aData = [];
        if (!$aNeedColumns) {
            $aNeedColumns = array_keys($aInputData);
        }
        foreach ($aNeedColumns as $sColumn) {
            if (isset($aInputData[$sColumn])) {
                $aData[$sColumn] = $aInputData[$sColumn];
            }
        }
        $sSignStr = implode('|', $aData);
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
    public function compileSign($oPaymentAccount, $aInputData, $aNeedKeys = []) {
        $sSignStr = $this->signStr($aInputData, $aNeedKeys);
        $sSignStr .= '|' . $oPaymentAccount->safe_key;
        return md5($sSignStr);
    }

    //
    protected function signQueryStr($aInputData, $aNeedColumns = []) {
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
     * 查询sign组建
     *
     * @param       $aInputData
     * @param array $aNeedKeys
     *
     * @return string
     */
    public function compileQuerySign($oPaymentAccount, $aInputData, $aNeedKeys = []) {
        $sSignStr = $this->signQueryStr($aInputData, $aNeedKeys);
        $sSignStr .= '&P_PostKey=' . $oPaymentAccount->safe_key;
        //$sSignStr = strtolower($sSignStr);
        // dd($sSignStr);
        return md5($sSignStr);
    }

    /**
     * 充值请求表单数据组建
     *
     * @author james liang
     * @date 2017-06-13
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
        $aSignData = [
            'P_UserID' => $oPaymentAccount->account,
            'P_OrderID' => $oDeposit->order_no,
            'P_CardID' => '',
            'P_CardPass' => '',
            'P_FaceValue' => $oDeposit->amount,
            'P_ChannelID' => $this->payment_type,
            'P_Price' => $oDeposit->amount,
            'P_Notic' => $oDeposit->username,
            'P_Result_URL' => $oPaymentPlatform->notify_url,
            'P_Notify_URL' => $oPaymentPlatform->notify_url,
        ];
        $aSignData['P_PostKey'] = $this->compileSign($oPaymentAccount, $aSignData, $this->signNeedColumns);
        $aData = $aSignData;
        return $aData;
    }

    /**
     * 通知签名组建
     *
     * @param       $oPaymentAccount
     * @param       $aInputData
     * @param array $aNeedKeys
     *
     * @return string
     */
    public function compileSignReturn($oPaymentAccount, $aInputData, $aNeedKeys = []) {
        $aData = [
            'P_UserID' => $aInputData['P_UserId'],
            'P_OrderID' => $aInputData['P_OrderId'],
            'P_CardID' => $aInputData['P_CardId'],
            'P_CardPass' => $aInputData['P_CardPass'],
            'P_FaceValue' => $aInputData['P_FaceValue'],
            'P_ChannelID' => $aInputData['P_ChannelId'],
            'P_PayMoney' => $aInputData['P_PayMoney'],
            'P_ErrCode' => $aInputData['P_ErrCode'],
            'P_Notic' => $aInputData['P_Notic'],
            //'P_ErrMsg' => $aInputData['P_ErrMsg'],
        ];
        return $this->compileSign($oPaymentAccount, $aData, $this->compileNofifySignColumns);
    }

    /**
     * 查询签名组建
     *
     * @param $oPaymentAccount
     * @param $sOrderNo
     * @param $sServiceOrderNo
     *
     * @return array
     */
    public function & compileQueryData($oPaymentAccount, $sOrderNo, $sServiceOrderNo) {
        $oDeposit = Deposit::getDepositByNo($sOrderNo);
        $aData = [
            'P_UserId' => $oPaymentAccount->account,
            'P_OrderId' => $sOrderNo,
            'P_ChannelId' => $this->payment_type,
            'P_CardId' => '',
            'P_FaceValue' => $oDeposit->amount,
        ];
        $aData['P_PostKey'] = $this->compileQuerySign($oPaymentAccount, $aData, $this->querySignNeedColumns);
        // dd($aData);
        return $aData;
    }


    /**
     * Query from Payment Platform
     *
     * @param PaymentPlatform $oPaymentPlatform
     * @param string          $sOrderNo
     * @param string          $sServiceOrderNo
     * @param array           & $aResponses
     *
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
        $url = $oPaymentPlatform->getQueryUrl($oPaymentAccount);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); //取消身份验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $sDataQuery);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //将数据传给变量

        $sResponse = curl_exec($ch); //接收返回信息
        file_put_contents('/tmp/huitian_' . $sOrderNo, $sResponse . "\n", FILE_APPEND);
        if (curl_errno($ch)) {//出错则显示错误信息
            print curl_error($ch);
        }
        curl_close($ch); //关闭curl链接
        if ($sResponse === '' || !isset($sResponse)) {     // query failed
            return self::PAY_QUERY_FAILED;
        }
        $aResponse = explode('&', $sResponse);
        $aResult = [];
        if (count($aResponse)) {
            foreach ($aResponse as $val) {
                $aval = explode('=', $val);
                $aResult[$aval[0]] = $aval[1];
            }
        }
        // 对返回数据验签
        $sSign = $this->compileQuerySign($oPaymentAccount, $aResult, $this->queryResultSignNeedColumns);
        if ($sSign != $aResult['P_PostKey']) {
            return self::PAY_SIGN_ERROR;
        }
        // 需要核实订单不存在的情况，到底归属于哪种状态
        if ($aResult['P_flag'] == 1 && $aResult['P_status'] == 1) {
            return self::PAY_SUCCESS;
        } else {
            return self::PAY_UNPAY;
        }

    }

    public static function & compileCallBackData($aBackData, $sIp) {
        $aData = [
            'order_no' => $aBackData['P_OrderId'],
            'service_order_no' => $aBackData['P_OrderId'],//没有平台的订单号 用商户订单号代替
            'merchant_code' => $aBackData['P_UserId'],
            'amount' => formatNumber($aBackData['P_PayMoney'], 2),
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
            'service_order_no' => $aResponses['P_OrderId'],
            'order_no' => $aResponses['P_OrderId'],
        ];
        return $data;
    }

    protected function processArrayKey($aResponse, $atradeResponse) {
        foreach ($atradeResponse as $key => $value) {
            $aResponse[strtolower($key)] = $value;
        }
        return $aResponse;
    }

}