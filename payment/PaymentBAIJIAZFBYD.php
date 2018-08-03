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
	public $accountColumn          = 'merId';
	public $orderNoColumn          = 'orderNo';
	public $successColumn          = 'retCode';
	public $successValue           =  200;
	public $amountColumn           = 'orderPrice';

	public $appId                  = 'cs001';
	public $appSecret              = 'FGxcBhkvtOKKZcG0g7Hnpg%3D%3D';
    public $serviceType            = 'ALIPAY_H5PAY';

	public $bizCode                = "H0001";
    public $goodsName              = "bet";
    public $goodsTag               = "betTag";
    public $terminalIp             = "35.201.235.209";


	protected $paymentName = 'baijiazfbyd';

    /**
     * @var array
     * 	"version" => "100",//接口版本[参与签名]
    "appId" => $APP_KEY,//应用ID[参与签名]
    "appSecret" => $APP_SECRET,//应用密钥[参与签名]
    "merId" => $MERCHANT_ID,//商户号[参与签名]
    "bizCode" = > "C0001",//业务编号[参与签名]
    "serviceType" => "WEIXIN_F2F_PAY",//服务类别[参与签名]
    "orderPrice" => "10000",//交易金额(元)
    "orderNo" => "订单号商户自己传"//订单号[参与签名]
     */
	//交易签名所需字段
	public $signNeedColumns = [
        "version",//接口版本[参与签名]
        "appId" ,//应用ID[参与签名]
        "appSecret" ,//应用密钥[参与签名]
        "merId" ,//商户号[参与签名]
        "bizCode",//业务编号[参与签名]
		"serviceType",//服务类别[参与签名]
        "orderPrice",//交易金额(元)
		"orderNo"//订单号[参与签名]
	];

	//查询签名字段
	public $querySignNeedColumns = [
			'version',
			'appId',
			'appSecret',
			'merId',
        'bizCode',
        'orderNo'
	];

    //query return sign columns
	public $queryReturnSignNeedColumns = [
			'status',
			'retCode',
			'merOrderNo',
			'orderPrice'
	];
	//通知签名字段
	public $returnSignNeedColumns = [
			'status',
			'retCode',
			'merOrderNo',
			'orderPrice',
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
	 * @param $oPaymentPlatform
	 * @param $oPaymentAccount
	 * @param $oDeposit
	 * @param $oBank
	 * @param $sSafeStr
    "version" => "100",//接口版本[参与签名]
    "appId" => $APP_KEY,//应用ID[参与签名]
    "appSecret" => $APP_SECRET,//应用密钥[参与签名]
    "merId" => $MERCHANT_ID,//商户号[参与签名]
    "bizCode" = > "C0001",//业务编号[参与签名]
    "serviceType" => "WEIXIN_F2F_PAY",//服务类别[参与签名]
    "orderNo" => "订单号商户自己传",//订单号[参与签名]
    "orderPrice" => "10000",//交易金额(元)
    "goodsName" => "充值卡",//商品名称
    "goodsTag" => "TAG",//商品标签
    "orderTime" => $datestr,//订单时间
    "terminalIp" => "120.36.46.178",//终端IP
    "returnUrl" => "http://pay.baidu.com",//前端返回页面
    "notifyUrl" => "http://pay.baidu.com/notify",//通知地址
    "settleCycle"=>"D0"      *
     * 商户号：88882018070810001127
    APPID:cs001
    支付KEY:FGxcBhkvtOKKZcG0g7Hnpg%3D%3D		（验证使用）
    支付秘钥：48d643efa82a43f1ab77d1860e75f18a    （签名使用）

     * @return array
	 */
	public function & compileInputData($oPaymentPlatform, $oPaymentAccount, $oDeposit, $oBank, & $sSafeStr) {
		$aData = [
                "version" => "100",//接口版本[参与签名]
                "appId" => $this->appId,//应用ID[参与签名]
                "appSecret" => $oPaymentAccount->safe_key,//应用密钥[参与签名]
                "merId" => $oPaymentAccount->account,//商户号[参与签名]
                "bizCode" => $this->bizCode,//业务编号[参与签名]
		"serviceType" => $this->serviceType,//服务类别[参与签名]
		"orderNo" => $oDeposit->order_no,//订单号[参与签名]
        "orderPrice" => $oDeposit->amount,//交易金额(元)
        "goodsName" => $this->goodsName,//商品名称
        "goodsTag" => $this->goodsTag,//商品标签
		"orderTime" =>  date('Y-m-d H:i:s',strtotime($oDeposit->created_at)),//订单时间
        "terminalIp" => $this->terminalIp,//终端IP
        "returnUrl" => $oPaymentAccount->return_url, //前端返回页面
        "notifyUrl" => $oPaymentAccount->notify_url,//通知地址
//        "settleCycle" => "D0"
		];
		$aData['sign'] = $sSafeStr = $this->compileSign($oPaymentAccount, $aData,$this->signNeedColumns);
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
            "version" => "100",//接口版本[参与签名]
            "appId" => $this->appId,//应用ID[参与签名]
            "appSecret" => $this->appSecret,//应用密钥[参与签名]
            "merId" => $oPaymentAccount->account,//商户号[参与签名]
            "orderNo" => $oDeposit->order_no,//订单号[参与签名]
            "bizCode" => $this->bizCode,//业务编号[参与签名]
		];
		$aData['sign'] = $this->compileSign($oPaymentAccount, $aData,$this->querySignNeedColumns);
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
				'status' => $aResponse['requestNo'],
				'retCode' => $aResponse['transId'],
				'merOrderNo' => $aResponse['merNo'],
				'platOrderNo' => $aResponse['orderNo'],
				'orderPrice' => $aResponse['origRespCode'],
				'message' => $aResponse['respCode'],
		];
		$sign = $this->compileSign($oPaymentAccount, $aData, $this->queryReturnSignNeedColumns);

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
		if(!$aResponses || !isset($aResponses['status'])){
			return self::PAY_QUERY_PARSE_ERROR;
		}
		if($aResponses['status']!='01'){
			return self::PAY_QUERY_FAILED;
		}
		if($aResponses['retCode'] == 999 || trim($aResponses['errCode']) == "0021"){
			return self::PAY_NO_ORDER;
		}
		if($aResponses['status'] == '01'){
			switch ($aResponses['retCode']) {
				case 200:
					//支付返回成功校验签名
					$sSign = $this->compileQueryReturnData($oPaymentAccount,$aResponses);
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
		$oDeposit = Deposit::getDepositByNo($aBackData['merOrderNo']);

		$aData = [
				'order_no' => $aBackData['merOrderNo'],
				'service_order_no' => $oDeposit ? date('YmdHis',strtotime($oDeposit->created_at)) : $aBackData['merOrderNo'],
				'merchant_code' => $oDeposit->merchant_code,
				'amount' => $aBackData['orderPrice'],
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
            'service_order_no' => $aResponses['platOrderNo'],
			'order_no' => $aResponses['orderNo'],
		];
		return $data;
	}

}
