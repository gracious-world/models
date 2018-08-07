<?php

/**
 * 百家平台
 * * 商户号：88882018070810001127
APPID:cs001
支付KEY:FGxcBhkvtOKKZcG0g7Hnpg%3D%3D		（验证使用）
支付秘钥：48d643efa82a43f1ab77d1860e75f18a    （签名使用）
 * @author sad
 *
 */
class PaymentBAIJIAZFBYD extends BasePlatform {


	public $successMsg             = 'SUCCESS';
	public $signColumn             = 'sign';
	public $accountColumn          = 'memberid';
	public $orderNoColumn          = 'orderid';
	public $paymentOrderNoColumn   = 'orderid';
	public $successColumn          = 'returncode';
    public $bankTimeColumn         = 'datetime';

	public $successValue           =  '00';
	public $amountColumn           = 'amount';
    public $payBankCode            =  904;

	protected $paymentName = 'baijiazfbyd';

    /**
     * @var array
     *
     */
	//交易签名所需字段
	public $signNeedColumns = [
     'pay_memberid',
     'pay_orderid',
     'pay_amount',
        'pay_applydate',
        'pay_bankcode',
        'pay_notifyurl',
        'pay_callbackurl'
	];

	//查询签名字段
	public $querySignNeedColumns = [
        'pay_memberid',
        'pay_orderid'
	];

    //query return sign columns
	public $queryReturnSignNeedColumns = [
		'memberid',
        'orderid',
        'amount',
        'time_end',
        'transaction_id',
        'returncode',
        'trade_state',
	];

	//通知签名字段
	public $returnSignNeedColumns = [
        'memberid',
        'orderid',
        'amount',
        'datetime',
        'returncode',
        'transaction_id',
	];

	protected function signStr($aInputData, $aNeedColumns = []) {
		$sSignStr = '';
		if (!$aNeedColumns) {
			$aNeedColumns = array_keys($aInputData);
		}
		foreach ($aNeedColumns as $sColumn) {
			if (isset($aInputData[$sColumn]) && $aInputData[$sColumn] != '') {
				$sSignStr .= $sColumn.'='.$aInputData[$sColumn].'&';
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
        ksort($aInputData);
        sort($aNeedKeys);
		$sSignStr = $this->signStr($aInputData, $aNeedKeys);
		$sSignStr .= "key=".$oPaymentAccount->safe_key;
//		var_dump($sSignStr);exit;
		return strtoupper(md5($sSignStr));
	}


	/**
	 * 通知签名组建
	 * @param       $oPaymentAccount
	 * @param       $aInputData
	 * @param array $aNeedKeys
	 * @return string
	 */
	public function compileSignReturn($oPaymentAccount, $aInputData) {
		return $this->compileSign($oPaymentAccount, $aInputData, $this->returnSignNeedColumns);
	}

	/**
	 * 充值请求表单数据组建
	 *
    * @return array
	 */
	public function & compileInputData($oPaymentPlatform, $oPaymentAccount, $oDeposit, $oBank, &$sSafeStr) {
		$aData = [
        "pay_memberid" => $oPaymentAccount->account,//商户号[参与签名]
		"pay_orderid" => $oDeposit->order_no,//订单号[参与签名]
        "pay_amount" => $oDeposit->amount,//交易金额(元)
		"pay_applydate" =>  date('Y-m-d H:i:s',strtotime($oDeposit->created_at)),//订单时间
        "pay_bankcode" => $this->payBankCode,
        "pay_notifyurl" => $oPaymentPlatform->notify_url,//通知地址
        "pay_callbackurl" => $oPaymentPlatform->return_url, //前端返回页面
		];
		$aData['pay_md5sign'] = $sSafeStr = $this->compileSign($oPaymentAccount, $aData,$this->signNeedColumns);
//		var_dump($aData);exit;
		return $aData;
	}

	/**
	 * 查询签名组建
	 *
	 * @param $oPaymentAccount
	 * @param $sOrderNo
	 * @param $sServiceOrderNo
	 *
	 *
	 */
	public function & compileQueryData($oPaymentAccount, $sOrderNo, $sServiceOrderNo) {
		$oDeposit = UserDeposit::getDepositByNo($sOrderNo);
		$aData = [
            "pay_memberid" => $oPaymentAccount->account,//商户号[参与签名]
            "pay_orderid" => $oDeposit->order_no,//订单号[参与签名]
		];
		$aData['pay_md5sign'] = $this->compileSign($oPaymentAccount, $aData,$this->querySignNeedColumns);
		return $aData;
	}

	/**
	 * 查询结果验签组建
	 *
	 * @param $aResponse
	 * @return array
	 */
	public function & compileQueryReturnSign($oPaymentAccount, $aResponse) {
		$sign = $this->compileSign($oPaymentAccount, $aResponse, $this->queryReturnSignNeedColumns);
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
		if(!$aResponses || (!isset($aResponses['status']) && !isset($aResponses['returncode']))){
			return self::PAY_QUERY_PARSE_ERROR;
		}

		if(isset($aResponses['status'])){
			return self::PAY_QUERY_FAILED;
		}
		if($aResponses['returncode'] == '00'){
					//支付返回成功校验签名
//					$sSign = $this->compileQueryReturnSign($oPaymentAccount,$aResponses);
//					if ($sSign != $aResponses['sign']) {
//						return self::PAY_SIGN_ERROR;
//					}
					return self::PAY_SUCCESS;
					//其他状态归结为未支付
		}else{
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
		$aData = [
				'order_no' => $aBackData['orderid'],
				'service_order_no' => $aBackData['transaction_id'],
				'merchant_code' => $aBackData['memberid'],
				'amount' => $aBackData['amount'],
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
            'service_order_no' => $aResponses['transaction_id'],
			'order_no' => $aResponses['orderid'],
		];
		return $data;
	}

}
