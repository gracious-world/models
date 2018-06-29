<?php

/**
 * 聚鑫平台
 *
 * @author lucky
 *
 */
class PaymentYONGFU extends BasePlatform {


	public $successMsg             = 'SUCCESS';
	public $signColumn             = 'signature';
	public $accountColumn          = 'merNo';
	public $orderNoColumn          = 'orderNo';
	public $paymentOrderNoColumn   = 'payId';
	public $successColumn          = 'respCode';
	public $successValue           = '0000';
	public $amountColumn           = 'transAmt';
	public $bankNoColumn           = 'bankCode';
	public $unSignColumns          = [];
	public $serviceOrderTimeColumn = '';

	public $transId       = "01";
	public $queryTransId       = "04";
	public $version       = "V1.0";
	public $productId     = "0117";
	public $commodityName = 'charge';

	public    $bankTimeColumn = "OrderDate";

	protected $paymentName= 'jx';

	//交易签名所需字段
	public $signNeedColumns = [
		'requestNo',
		'productId',
		'transId',
		'merNo',
		'orderNo',
		'transAmt',
		'bankCode',
	];

	//查询签名字段
	public $querySignNeedColumns = [
			'requestNo',
			'transId',
			'merNo',
			'orderNo'
	];

	//通知签名字段
	public $returnSignNeedColumns = [
			'merNo',
			'orderNo',
			'transAmt',
			'respCode',
			'payId',
			'payTime'
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
	 * @param $oPaymentPlatform
	 * @param $oPaymentAccount
	 * @param $oDeposit
	 * @param $oBank
	 * @param $sSafeStr
	 *
	 * @return array
	 * version
	 * productId
	 * transId
	 * merNo
	 * orderDate
	 * orderNo
	 * notifyUrl
	 * transAmt
	 * bankCode
	 * commodityName
	 * signature
	 * requestNo+productId+transId+merNo+orderNo+transAmt+bankCode+key
	 * requestNo + transId + merNo
	 * 2017-07-06-14-13-26-384
	 */
	public function & compileInputData($oPaymentPlatform, $oPaymentAccount, $oDeposit, $oBank, & $sSafeStr) {
		$aData = [
				'requestNo' => date('YmdHis',strtotime($oDeposit->created_at)),
				'productId' => $this->productId,
				'transId' => $this->transId,
				'merNo' => $oPaymentAccount->account,
				'orderNo' => $oDeposit->order_no,
				'transAmt' => $oDeposit->amount * 100,
				'bankCode' => $oBank ? $oBank->identifier : null,
				'notifyUrl' => $oPaymentPlatform->notify_url,
				'version' => $this->version,
				'orderDate' => date('Ymd',strtotime($oDeposit->created_at)),
				'commodityName' => $this->commodityName,
		];
		$aData['signature'] = $sSafeStr = $this->compileSign($oPaymentAccount, $aData,$this->signNeedColumns);
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
				'requestNo' => date('YmdHis',strtotime($oDeposit->created_at)),
				'version' => $this->version,
				'transId' => $this->queryTransId,
				'merNo' => $oPaymentAccount->account,
				'orderDate' => date("Ymd", strtotime($oDeposit->created_at)),
				'orderNo' => $sOrderNo,
		];
		$aData['signature'] = $this->compileQuerySign($oPaymentAccount, $aData,$this->querySignNeedColumns);
		return $aData;
	}

	/**
	 * 查询结果验签组建
	 *
	 * @param $aResponse
	 * @return array
	 */
	public function & compileQueryReturnData($oPaymentAccount,$aResponse) {
		$aData = [
				'requestNo' => $aResponse['requestNo'],
				'transId' => $aResponse['transId'],
				'merNo' => $aResponse['merNo'],
				'orderNo' => $aResponse['orderNo'],
				'origRespCode' => $aResponse['origRespCode'],
				'respCode' => $aResponse['respCode'],
		];
		$sign = $this->compileQuerySign($oPaymentAccount, $aData);

		return $sign;
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
	 *  -4: No Order
	 *  -5: Unpay
	 */
	public function queryFromPlatform($oPaymentPlatform, $oPaymentAccount, $sOrderNo, $sServiceOrderNo = null, & $aResonses) {
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
		$aResonses = json_decode($sResponse, true);
		//返回格式不对
		if(!$aResonses || !isset($aResonses['respCode'])){
			return self::PAY_QUERY_PARSE_ERROR;
		}
		if($aResonses['respCode'] == '0028' || trim($aResonses['respDesc']) == "无法查到原交易"){
			return self::PAY_NO_ORDER;
		}
		if($aResonses['respCode'] == '0000'){
			switch ($aResonses['origRespCode']) {
				case '0000':
					//支付返回成功校验签名
					$sSign = $this->compileQueryReturnData($oPaymentAccount,$aResonses);
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
		$oDeposit = Deposit::getDepositByNo($aBackData['orderNo']);
		$aData = [
				'order_no' => $aBackData['orderNo'],
				'service_order_no' => $oDeposit ? date('YmdHis',strtotime($oDeposit->created_at)) : $aBackData['merNo'],
				'merchant_code' => $aBackData['merNo'],
				'amount' => $aBackData['transAmt'] / 100,
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
				'service_order_no' => $aResponses['requestNo'],
				'order_no' => $aResponses['orderNo'],
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
