<?php

/**
 * 聚鑫平台
 *
 * @author lucky
 *
 */
class PaymentYONGFUWY extends BasePlatform {
	protected $paymentName= 'huitianwy';
    // 保存二维码
    protected $saveQr = false;
    protected $isqrcode = 0;
    protected $qrDirName = 'huitianwy';
    // 回调处理成功时，输出的字符串
    protected $successMsg = 'SUCCESS';
    // 签名变量名
    protected $signColumn = 'pay_md5sign';
    // 帐号变量名
    protected $accountColumn = 'pay_memberid';
    // 订单号变量名
    protected $orderNoColumn = 'pay_orderid';
    // 渠道方订单号变量名
    protected $paymentOrderNoColumn = 'pay_orderid'; //通知结果中没有平台订单号，用商户号代替
    // 回调的数据中，可用于检验是否成功的变量名
    protected $successColumn = 'P_ErrCode';
    // 回调的数据中,标志成功的变量值
    protected $successValue = '0';
    // 金额变量名
    protected $amountColumn = 'pay_amount';
    protected $channelCode = 'BANK';

    // 回调数据中,平台订单时间变量名
    public $serviceOrderTimeColumn = 'datetime';
    // 银行类型 QQ 扫码：89  微信扫码：21
    /**
     * ALIPAY_WAP:支付宝WAP
     * ALIPAY:支付宝扫码
     * BANK:网银
     * BANK_WAP:网银快捷
     * WECHAT:微信扫码
     * WECHAT_WAP:微信 WAP
     * QQ:QQ 扫码
     * QQ_WAP:QQWAP
     * JD:京东扫码
     */
    protected $payment_type = 'BANK';

    // 参加签名的变量数组
    public $signNeedColumns = [ //充值请求
        'pay_memberid',
        'pay_orderid',
        'pay_amount',
        'pay_applydate',
        'pay_channelCode',
    ];

    // 通知需要验签的数组
    public $compileNofifySignColumns = [
        'memberid',
        'orderid',
        'amount',
        'datetime',
        'channelCode',
    ];

    //查询需要验签的数组
    public $querySignNeedColumns = [
        'pay_memberid',
        'pay_orderid',
    ];

    //查询结果需要验签的数组
    public $queryResultSignNeedColumns = [
        'pay_memberid',
        'pay_orderid',
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
	 *
	 * @param       $oPaymentAccount
	 * @param       $aInputData
	 * @param array $aNeedKeys
	 *
	 * @return string
	 */
	public function compileSign($oPaymentAccount, $aInputData, $aNeedKeys = []) {

		$sSignStr = $this->signStr($aInputData, $aNeedKeys);
		$sSignStr .= $oPaymentAccount->safe_key;

		return md5($sSignStr);
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
		$sSignStr = $this->signStr($aInputData, $aNeedKeys);
		$sSignStr .= $oPaymentAccount->safe_key;	

		return md5($sSignStr);
	}

	/**
	 * 通知签名组建
	 * @param       $oPaymentAccount
	 * @param       $aInputData
	 * @param array $aNeedKeys
	 * @return string
	 */
	public function compileSignReturn($oPaymentAccount, $aInputData) {
		$aData = [
			'merNo' => $oPaymentAccount->account,
			'orderNo' => $aInputData['orderNo'],
			'transAmt' => $aInputData['transAmt'],
			'respCode' => $aInputData['respCode'],
			'payId' => $aInputData['payId'],
			'payTime' => $aInputData['payTime'],
		];
		return $this->compileSign($oPaymentAccount, $aData, $this->returnSignNeedColumns);
	}

	/**
	 * 充值请求表单数据组建
	 *
	 */
    public function & compileInputData($oPaymentPlatform, $oPaymentAccount, $oDeposit, $oBank, & $sSafeStr) {
        $aSignData = [
            'pay_memberid' => $oPaymentAccount->account,
            'pay_orderid' => $oDeposit->order_no,
            'pay_amount' => $oDeposit->amount,
            'pay_applydate' => date('ymdHis',strtotime($oDeposit->created_at)),
            'pay_channelCode' => $this->channelCode,
            'pay_notifyurl' => $oPaymentPlatform->notify_url,
            //if pay_bankcode = null,then client select the method to pay
//            'pay_bankcode' => $oDeposit->amount,
//            '' => $oDeposit->username,
//            'P_Description' => $oBank->identifier    //ICBC,there s an self cash end in the page
        ];
        $aSignData['pay_md5sign'] = $this->compileSign($oPaymentAccount, $aSignData, $this->signNeedColumns);
        $aData = $aSignData;
        return $aData;
    }

	/**
	 * 查询签名组建
	 *
	 * @param $oPaymentAccount
	 * @param $sOrderNo
	 * @param $sServiceOrderNo
	 *
	 * @return array
	 * requestNo=2017041915340519401
	 * &version=V1.0
	 * &transId=04
	 * &merNo=Z00000000000***
	 * &orderDate=20170419
	 * &orderNo=16F8C3T868_35907_108
	 * &signature=1a577e01e3d125003ed0151d3edc3370
	 * requestNo+transId+merNo+orderNo
	 *
	 */
	public function & compileQueryData($oPaymentAccount, $sOrderNo, $sServiceOrderNo) {
		$oDeposit = UserDeposit::getDepositByNo($sOrderNo);
		$aData = [
				'pay_memberid' => $oPaymentAccount->account,
				'pay_orderid' => $oDeposit->order_no
		];
		$aData['pay_md5sign'] = $this->compileQuerySign($oPaymentAccount, $aData,$this->querySignNeedColumns);
		return $aData;
	}

	/**
	 * 查询结果验签组建
	 *
	 * @param $aResponse
	 * @return array
	 */
	public function & compileQueryReturnData($oPaymentAccount,$aResponse) {
		$sign = $this->compileQuerySign($oPaymentAccount, $aResponse,$this->queryResultSignNeedColumns);

		return $sign;
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
		$ch         = curl_init();
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
		if(!$aResponses || !isset($aResponses['respCode'])){
			return self::PAY_QUERY_PARSE_ERROR;
		}
		//todo
        switch($aResponses['returncode']){
            case '00' :
                    $sSign = $this->compileQueryReturnData($oPaymentAccount,$aResponses);
					if ($sSign != $aResponses['signature']) {
						return self::PAY_SIGN_ERROR;
					}
					return self::PAY_SUCCESS;

            default:
                return self::PAY_QUERY_FAILED;

        }
	}

	/**
	 * @param array  $aBackData
	 * @param string $sIp
	 *
	 * @return array
	 * 'merNo' => string 'Z00000000001104' (length=15)
	 * 'orderNo' => string '1230172261597c605172b2c' (length=23)
	 * 'transAmt' => string '1001' (length=4)
	 * 'realRequestAmt' => string '1001' (length=4)
	 * 'orderDate' => string '20170729' (length=8)
	 * 'respCode' => string '0000' (length=4)
	 * 'respDesc' => string '支付成功' (length=12)
	 * 'payId' => string 'ZT300120170729181546612418' (length=26)
	 * 'payTime' => string '20170729181633' (length=14)
	 * 'signature' => string '7f3669e6f4639fe2d032766e68eeb5cf' (length=32)
	 */
	public static function & compileCallBackData($aBackData, $sIp) {
		$oDeposit = Deposit::getDepositByNo($aBackData['orderNo']);
		$aData = [
				'order_no' => $oDeposit->order_no,
				'service_order_no' => $oDeposit ? date('YmdHis',strtotime($oDeposit->created_at)) : $aBackData['orderid'],
				'merchant_code' => $aBackData['memberid'],
				'amount' => $aBackData['amount'] / 100,
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

	public static function & getServiceInfoFromQueryResult(& $aResponses) {
		$data = [
				'service_order_no' => $aResponses['orderid'],
				'order_no' => $aResponses['orderid'],
		];
		return $data;
	}

	/**
     * 从数组中取得金额
     * @param array $data
     * @return float
     */
    public function getPayAmount($data) {
        return $data[$this->amountColumn] / 100;
    }
}
