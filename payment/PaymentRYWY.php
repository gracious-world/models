<?php

/**
 * 聚鑫平台
 *
 * @author lucky
 *
 */
class PaymentRYWY extends BasePlatform {

	public $paymentName= 'rywy';
	public $isMobile = false;

    // 保存二维码
    public $saveQr = false;
    public $isqrcode = 0;

    public $qrDirName;

    // 回调处理成功时，输出的字符串
    public $successMsg = 'stopNotify';
    // 签名变量名
    public $signColumn = 'signMsg';
    // 帐号变量名
    public $accountColumn = 'merid';
    // 订单号变量名
    public $orderNoColumn = 'merOrdId';
    // 渠道方订单号变量名
    public $paymentOrderNoColumn = 'sysOrdId'; //通知结果中没有平台订单号，用商户号代替
    // 回调的数据中，可用于检验是否成功的变量名
    public $successColumn = 'tradeStatus';
    // 回调的数据中,标志成功的变量值
    public $successValue = 2;
    // 金额变量名
    public $amountColumn = 'merOrdAmt';

    public $signType = 'MD5';

    public $payType = 10;

    // 回调数据中,平台订单时间变量名
    public $serviceOrderTimeColumn;

    // 银行类型 QQ 扫码：89  微信扫码：21
//    protected $payment_type = 'BANK';

    // 参加签名的变量数组
    public $signNeedColumns = [ //充值请求
        'merId',
        'merOrdId',
        'merOrdAmt',
        'payType',
        'bankCode',
        'remark',
        'returnUrl',
        'notifyUrl',
        'signType'
    ];

//notify sign
    public $notifySignNeedColumns=[
        'merId',
        'merOrdId',
        'merOrdAmt',
        'sysOrdId',
        'tradeStatus',
        'remark',
        'signType'
        ];


    //查询需要验签的数组
    public $querySignNeedColumns = [
        'merId',
        'merOrdId',
        'signType',
        'timeStamp',
    ];

    //查询结果需要验签的数组
    public $queryResultSignNeedColumns = [
        'retMsg',
        'tradeStatus',
        'merOrdId',
        'merOrdAmt',
        'sysOrdId',
        'merId'
    ];

    protected function signStr($aInputData, $aNeedColumns = []) {
		$sSignStr = '';
		if (!$aNeedColumns) {
			$aNeedColumns = array_keys($aInputData);
		}
		foreach ($aNeedColumns as $sColumn) {
			if (isset($aInputData[$sColumn]) && $aInputData[$sColumn] != '') {
                $sSignStr .= $sColumn . '^' . $aInputData[$sColumn] . '&';
			}
		}
		return $sSignStr;
	}

	/**
	 * sign组建
	 *
     *
	 * @param       $oPaymentAccount
	 * @param       $aInputData
	 * @param array $aNeedKeys
	 *
	 * @return string
	 */
	public function compileSign($oPaymentAccount, $aInputData, $aNeedKeys = []) {

		$sSignStr = $this->signStr($aInputData, $aNeedKeys);
        $sSignStr .= 'merKey=' . $oPaymentAccount->safe_key;
//        var_dump(__FILE__,__LINE__,$sSignStr);
		return strtolower(md5($sSignStr));
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
		return $this->compileSign($oPaymentAccount,$aInputData,$aNeedKeys);
	}

	/**
	 * 通知签名组建
	 * @param       $oPaymentAccount
	 * @param       $aInputData
	 * @param array $aNeedKeys
	 * @return string
	 */
	public function compileSignReturn($oPaymentAccount, $aInputData) {
		return $this->compileSign($oPaymentAccount, $aInputData, $this->notifySignNeedColumns);
	}

	/**
	 * 充值请求表单数据组建
	 *
	 */
    public function & compileInputData($oPaymentPlatform, $oPaymentAccount, $oDeposit, $oBank, & $sSafeStr) {
        $aData = [
            'merId'=>$oPaymentAccount->account,
            'merOrdId'=>$oDeposit->order_no,
            'merOrdAmt'=>$oDeposit->amount,
            'payType'=>$this->payType,
            'bankCode'=>$oDeposit->bank_code,
            'remark'=>'hz',
            'returnUrl'=>$oPaymentPlatform->return_url,
//        'pay_bankcode',
            'notifyUrl'=>$oPaymentAccount->notify_url,
            'signType'=>$this->signType
        ];

        $aData[$this->signColumn] = $this->compileSign($oPaymentAccount, $aData, $this->signNeedColumns);
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
	 */
	public function & compileQueryData($oPaymentAccount, $sOrderNo, $sServiceOrderNo) {
		$oDeposit = UserDeposit::getDepositByNo($sOrderNo);
		$aData = [
        'merId'=>$oPaymentAccount->account,
        'merOrdId'=>$sOrderNo,
        'signType'=>$this->signType,
        'timeStamp'=>time(),
		];
		$aData[$this->signColumn] = $this->compileQuerySign($oPaymentAccount, $aData,$this->querySignNeedColumns);
		return $aData;
	}

	/**
	 * 查询结果验签组建
	 *
	 * @param $aResponse
	 * @return array
	 */
	public function & compileQueryReturnSign($oPaymentAccount, $aResponse) {
		$sign = $this->compileSign($oPaymentAccount, $aResponse,$this->queryResultSignNeedColumns);

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
		if(!$aResponses || !isset($aResponses['tradeStatus'])){
			return self::PAY_QUERY_PARSE_ERROR;
		}
		//todo
        switch($aResponses['tradeStatus']){
            case 2 :
                    $sSign = $this->compileQueryReturnSign($oPaymentAccount,$aResponses);
					if ($sSign != $aResponses['sign']) {
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
	 */
	public static function & compileCallBackData($aBackData, $sIp) {
		$oDeposit = Deposit::getDepositByNo($aBackData['merOrdId']);
		$aData = [
				'order_no' => $oDeposit->order_no,
				'service_order_no' => $oDeposit ? date('YmdHis',strtotime($oDeposit->created_at)) : $aBackData['sysOrdId'],
				'merchant_code' => $aBackData['merid'],
				'amount' => $aBackData['merOrdAmt'],
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
				'service_order_no' => $aResponses['sysOrdId'],
				'order_no' => $aResponses['merOrdId'],
		];
		return $data;
	}

}
