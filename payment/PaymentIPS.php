<?php

/**
 * 环讯平台
 */
class PaymentIPS {

    /**
     * 支付成功
     */
    const PAY_SUCCESS = 1;
    /**
     * 查询失败
     */
    const PAY_QUERY_FAILED = -1;
    /**
     * 查询结果解析失败
     */
    const PAY_QUERY_PARSE_ERROR = -2;
    /**
     * 查询结果签名错误
     */
    const PAY_SIGN_ERROR = -3;
    /**
     * 订单不存在
     */
    const PAY_NO_ORDER = -4;
    /**
     * 订单未支付
     */
    const PAY_UNPAY = -5;
    /**
     * 金额错误
     */
    const PAY_AMOUNT_ERROR = -6;


    public $successMsg = 'ipscheckok';
    public $signColumn = 'signature';
    public $accountColumn = 'mercode';
    public $orderNoColumn = 'merbillno';
    public $paymentOrderNoColumn = 'ipsbillno';
    public $successColumn = 'status';
    public $successValue = 'Y';
    public $amountColumn = 'amount';
    public $bankNoColumn = 'bankbillno';
    public $serviceOrderTimeColumn = '';
    public $unSignColumns = ['signature'];
    public $bankTimeColumn = 'ipsbilltime';


    //账户号,目前使用硬编码方式
    //protected $account = 1830350011;  //已经改到账户中card_number配置

    //请求报文模板
    public $sXmlReqTemplate = '<Ips><GateWayReq>_content</GateWayReq></Ips>';
    public $sXmlRspTemplate = '<Ips><GateWayRsp>_content</GateWayRsp></Ips>';
    public $sXmlOrderReqTemplate = '<Ips><OrderQueryReq>_content</OrderQueryReq></Ips>';
    public $sXmlOrderRspTemplate = '<Ips><OrderQueryRsp>_content</OrderQueryRsp></Ips>';

    public function compileSign($oPaymentAccount, $sXmlBody) {
        return md5($sXmlBody . $oPaymentAccount->account . $oPaymentAccount->safe_key);
    }

    public function compileSignReturn($oPaymentAccount, $sXmlBody) {
        return md5($sXmlBody . $oPaymentAccount->account . $oPaymentAccount->safe_key);
    }

    public function & compileInputData($oPaymentPlatform, $oPaymentAccount, $oDeposit, $oBank, & $sSafeStr) {
        $aXmlHead = [
            'Version' => 'v1.0.0',
            'MerCode' => $oPaymentAccount->account,
            'MerName' => '',
            'Account' => $oPaymentAccount->card_number,
            'MsgId' => '',
            'ReqDate' => date('YmdHis'),
            'Signature' => '',
        ];
        $aXmlBody = [
            'MerBillNo' => $oDeposit->order_no,
            'GatewayType' => '01',
            'Date' => str_replace('-', '', substr($oDeposit->created_at, 0, 10)),
            'CurrencyType' => 'RMB',
            'Amount' => $oDeposit->amount,
            'Lang' => '',
            'Merchanturl' => $oPaymentPlatform->return_url,
            'FailUrl' => '',
            'Attach' => '',
            'OrderEncodeType' => 5,
            'RetEncodeType' => 17,
            'RetType' => 1,
            'ServerUrl' => $oPaymentPlatform->notify_url,
            'BillEXP' => '',
            'GoodsName' => $oDeposit->order_no,
            'IsCredit' => $oBank ? 1 : '',
            'BankCode' => $oBank ? $oBank->identifier : '',
            'ProductType' => $oBank ? 1 : '',

        ];
        $data['pGateWayReq'] = $this->createXml($oPaymentAccount, $aXmlBody, $aXmlHead);
        return $data;
    }

    /**
     * 拼装订单查询数据
     *
     * @param $oPaymentAccount
     * @param $sOrderNo
     *
     * @return string
     */
    public function & compileQueryData($oPaymentAccount, $sOrderNo) {

        $oDeposit = Deposit::doWhere(['order_no' => ['=', $sOrderNo]])->first();
        $sOrderXml = '';
        if (!$oDeposit) {
            return $sOrderXml;
        }
        $aXmlHead = [
            'Version' => 'v1.0.0',
            'MerCode' => $oPaymentAccount->account,
            'MerName' => '',
            'Account' => $oPaymentAccount->card_number,
            'ReqDate' => str_replace(['-', ':', ' '], '', $oDeposit->created_at),
            'Signature' => '',
        ];
        $aXmlBody = [
            'MerBillNo' => $oDeposit->order_no,
            'Amount' => $oDeposit->amount,
            'Date' => str_replace('-', '', substr($oDeposit->created_at, 0, 10)),
        ];

        $sOrderXml = $this->createXml($oPaymentAccount, $aXmlBody, $aXmlHead, false, true);
        return $sOrderXml;
    }


    /**
     * 构造xml报文
     *
     * @param      $oPaymentAccount 账户信息
     * @param      $aXmlBody xml body 中字段
     * @param      $aXmlHead xml head 中字段
     * @param bool $bIsRsp 是否构造返回报文 false-请求报文
     * @param bool $bIsOrder 是否订单查询报文 false-请求报文
     *
     * @return string
     */
    public function createXml($oPaymentAccount, $aXmlBody, $aXmlHead, $bIsRsp = false, $bIsOrder = false) {
        $sXmlBody = '<body>';
        foreach ($aXmlBody as $key => $value) {
            if (strpos(strtolower($key), 'url') !== false) {
                $sXmlBody .= '<' . $key . '><![CDATA[' . $value . ']]></' . $key . '>';
            } else {
                $sXmlBody .= '<' . $key . '>' . $value . '</' . $key . '>';
            }
        }
        $sXmlBody .= '</body>';

        $aXmlHead['Signature'] = $sSafeStr = $this->compileSign($oPaymentAccount, $sXmlBody);
        $sXmlHead = '<head>';
        foreach ($aXmlHead as $key => $value) {
            $sXmlHead .= '<' . $key . '>' . $value . '</' . $key . '>';
        }
        $sXmlHead .= '</head>';

        if (!$bIsOrder) {
            if (!$bIsRsp) {
                return str_replace('_content', $sXmlHead . $sXmlBody, $this->sXmlReqTemplate);
            } else {
                return str_replace('_content', $sXmlHead . $sXmlBody, $this->sXmlRspTemplate);
            }
        } else {
            if (!$bIsRsp) {
                return str_replace('_content', $sXmlHead . $sXmlBody, $this->sXmlOrderReqTemplate);
            } else {

                return str_replace('_content', $sXmlHead . $sXmlBody, $this->sXmlOrderRsqTemplate);
            }
        }


    }

    /**
     * 解析xml为数组
     *
     * @param string $sXml
     *
     * @return array|bool
     */
    public function parseXml($sXml = '') {
        $aRepData = $aCleanData = [];
        $resParser = xml_parser_create();
        if (!xml_parse_into_struct($resParser, $sXml, $aRepData, $index)) {
            return false;
        }
        foreach ($aRepData as $aItem) {
            if (isset($aItem['value'])) {
                $aCleanData[strtolower($aItem['tag'])] = $aItem['value'];
            }

        }
        return $aCleanData;
    }

    /**
     * 从数组中取得金额
     *
     * @param array $data
     *
     * @return float
     */
    public function getPayAmount($data) {
        return $data[$this->amountColumn];
    }


    /**
     * Query from Payment Platform
     *
     * @param        $oPaymentPlatform
     * @param        $oPaymentAccount
     * @param string $sOrderNo
     * @param string $sTradeNo
     * @param array  & $aResponse
     *
     * @return integer | boolean
     *  1: Success
     *  -1: Query Failed
     *  -2: Parse Error
     *  -3: Sign Error
     *  -4: Unpay
     *  -5: Amount Error
     */
    public function queryFromPlatform($oPaymentPlatform, $oPaymentAccount, $sOrderNo, $sTradeNo = '', & $aResponse) {

        $sXml = $this->compileQueryData($oPaymentAccount, $sOrderNo);

        $sResponse = $this->__doQuery($oPaymentPlatform, $sXml);

        //获取数据失败
        if ($sResponse === '') {
            return self::PAY_QUERY_FAILED;
        }

        //解析xml失败
        $aResponse = $this->parseXml($sResponse);
        if (!$aResponse) {
            return self::PAY_QUERY_PARSE_ERROR;
        }

        //没有注单
        if ($aResponse['rspcode'] != '000000') {
            return self::PAY_NO_ORDER;
        }

        //校验签名
        if (!preg_match('#<body>.*</body>#is', $sResponse, $sXmlBody)) {
            return self::PAY_SIGN_ERROR;
        }
        $sSign = $this->compileSignReturn($oPaymentAccount, $sXmlBody[0]);
        if ($sSign != $aResponse[$this->signColumn]) {
            return self::PAY_SIGN_ERROR;
        }

        switch ($aResponse['status']) {
            case 'N':
            case 'P':
                return self::PAY_UNPAY;
            case 'Y':
                return self::PAY_SUCCESS;
        }
    }

    public static function & compileCallBackData($data, $ip) {
        $aData = [
            'order_no' => $data['merbillno'],
            'service_order_no' => $data['ipsbillno'],
            'merchant_code' => $data['mercode'],
            'amount' => $data['amount'],
            'ip' => $ip,
            'status' => DepositCallback::STATUS_CALLED,
            'post_data' => var_export($data, true),
            'callback_time' => time(),
            'callback_at' => date('Y-m-d H:i:s'),
            'referer' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null,
            'http_user_agent' => $_SERVER['HTTP_USER_AGENT'],
        ];
        return $aData;
    }


    /**
     * 保存回调历史
     *
     * @param array  $data
     * @param string $ip
     *
     * @return boolean| DepositCallback
     */
    public function addCallBackHistory(& $data, $ip) {
        $aData = $this->compileCallBackData($data, $ip);
        $oDepositCallback = new DepositCallback($aData);
        if ($oDepositCallback->save()) {
            return $oDepositCallback;
        } else {
            file_put_contents('/tmp/deposit-callback-error', $oDepositCallback->getValidationErrorString());
        }
        return false;
    }

    public function getLoadUrl() {
        return $this->platform->relay_url ? $this->platform->relay_url : $this->platform->load_url;
    }


    public static function & getServiceInfoFromQueryResult(& $aResponses){
        $data = [
            'service_order_no' => $aResponses['ipsbillno'],
            'service_time' => date('Y-m-d H:i:s', strtotime($aResponses['ipsbilltime'])),
            'service_order_status' => $aResponses['status']
        ];
        return $data;
    }


    /**
     * 写日志
     *
     * @param string $sLog
     * @param bool   $bExtraLn 默认为false
     */
    protected function writeLog($sLog, $bExtraLn = false) {
        $sLn = "\n";
        !$bExtraLn or $sLn .= "\n";
        @file_put_contents($this->qrCodeLogFile, $sLog . $sLn, FILE_APPEND);
    }

    /**
     * SOAP 查询订单信息
     *
     * @param $oPaymentPlatform
     * @param $sXml
     *
     * @return mixed
     */
    private function & __doQuery($oPaymentPlatform, & $sXml) {
        $oSoapClient = new  SoapClient($oPaymentPlatform->query_url);
        //$funs = $oSoapClient->__getFunctions();
        //'\<\?xml version=\'1.0\' encoding=\'UTF-8\' \?\>' . $sOrderXml;
        $response = $oSoapClient->getOrderByMerBillNo($sXml);
        file_put_contents('/tmp/IPS-query-result', $response, FILE_APPEND);
        return $response;
    }


}
