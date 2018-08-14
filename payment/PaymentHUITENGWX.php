<?php

/**
 * 汇腾平台微信支付
 * @author Tino
 */
class PaymentHUITENGWX extends PaymentHUITENG {

    public $signColumn = 'signature';
    public $accountColumn = 'merNo';
    public $orderNoColumn = 'orderNo';
    public $paymentOrderNoColumn = 'orderNo';
    public $successColumn = 'respCode';
    public $successValue = '1';
    public $amountColumn = 'transAmt';
    public $serviceOrderTimeColumn = '';
    public $bankTimeColumn = "orderDate";
    /*
     * wxpay:微信;qqpay:QQ;
     */
    protected $outChannelType = 'wxpay';
    public $qrDirName = 'huitengwx';
    protected $smName = 'wx';
    /*
     * 是否保存二维码
     */
    public $saveQr = true;

    /**
     * 是否需要中转地址
     */
    public $bNeedDirect = false;

    /**
     * 表单提交提交方式
     * @var string 
     */
    public $sHtmlFormSubmitMethod = 'get';
    public $signNeedColumns = [//充值请求
        'userOrderNo',
        'orderDate',
        'merchantNo',
    ];
    public $signQueryColumns = [//查询加密字段
        'merchantNo',
    ];

    public $signNotifyColumns = [
        'orderNo',
        'orderDate',
    ];
    /**
     * sign组建
     * @param $oPaymentAccount
     * @param $aInputData
     * @param array $aNeedKeys
     * @return string
     */
    public function compileSign($oPaymentAccount, $aInputData, $aNeedKeys = []) {

        $sSignStr = $this->signStr($aInputData, $aNeedKeys);
        $sSignStr .= $oPaymentAccount->safe_key;

        return strtoupper(md5($sSignStr));
    }
    /**
     * 通知签名组建
     * @param $oPaymentAccount
     * @param $aInputData
     * @param array $aNeedKeys
     * @return string
     */
    public function compileSignReturn($oPaymentAccount, $aInputData, $aNeedKeys = []) {
        return $this->compileSign($oPaymentAccount, $aInputData, $this->signNotifyColumns);
     }
    /**
     * 二维码生成函数,子类复写processQr方法三方支付平台处理返回结果
     * @author zero
     * @param $aInputData
     * @param $sRealUrl
     * @param $oPaymentAccount
     * @return string
     */
    public function qrCode($aInputData, $sRealUrl, $oPaymentAccount = null) {
        $response = $this->curl_post($sRealUrl, $aInputData);
        //设置二维码存放路径
        $this->setQrPath();
        //设置二维码写日志路径
        $this->compileQrLogFile();

        if ($response === '') {
            $this->writeLog('access the qrCode server failed');
            return '';
        }
        $this->writeLog('response result: '.$response);
        return $this->processQr($aInputData, $response, $oPaymentAccount);
    }

    public function processQr($aInputData, $sResponse, $oPaymentAccount) {
        $sWxPngPath = $resp_code = '';
        $aRes = json_decode($sResponse, true);
        !array_key_exists('retcode',$aRes) or  $resp_code = $aRes['retcode'];
        if($resp_code==''){
            !array_key_exists('retCode',$aRes) or $resp_code = $aRes['retCode'];
        }
        if ($resp_code == "00") {
            $qrcode = $aRes['qrcode'];
            $sQrcodeNoLogoPath = $this->qrCodePath . $aRes['sp_billno'] . 'qrcode.png';
            $sQrocdeHasLogoPath = $this->qrCodePath . $aRes['sp_billno'] . 'output.png';

            if (file_exists($sQrcodeNoLogoPath) or file_exists($sQrocdeHasLogoPath)) {
                unlink($sQrcodeNoLogoPath);
                unlink($sQrocdeHasLogoPath);
            }
            $errorCorrectionLevel = 'L';
            $matrixPointSize = 10;

            QRcode::png($qrcode, $sQrcodeNoLogoPath, $errorCorrectionLevel, $matrixPointSize, 2);
            $QR = $sQrcodeNoLogoPath;
            $QR = imagecreatefromstring(file_get_contents($QR));
            $QR_width = imagesx($QR);
            $QR_height = imagesy($QR);
            imagepng($QR, $sQrocdeHasLogoPath);
            imagedestroy($QR);
            //echo "处理获得的二维码为：" ."<br>"."<img src='".$sQrcodePath.$aInputData['order_no']."output.png'/>";
            $sWxPngPath = $this->qrVisitPath . $aRes['sp_billno'] . 'output.png';
        }
        return $sWxPngPath;
    }

    /**
     * 充值请求表单数据组建
     * @param $oPaymentPlatform
     * @param $oPaymentAccount
     * @param $oDeposit
     * @param $oBank
     * @param $sSafeStr
     * @return array
     */
    public function & compileInputData($oPaymentPlatform, $oPaymentAccount, $oDeposit, $oBank, & $sSafeStr) {
        $aData = [
            'userOrderNo' => $oDeposit->order_no,
            'merchantNo' => $oPaymentAccount->account,
            'transId' => '001',
            'orderDate' => date('YmdHis'),
            'notifyUrl' => $oPaymentPlatform->notify_url,
            'orderAmt' => '' . $oDeposit->amount * 100,
            'curType' => 'CNY',
            'commodityName' => 'Vitrual' . intval(mt_rand(1, 99999)),
            'outChannel' => $this->outChannelType,
            'subCommodityName' => 'Vitrual' . intval(mt_rand(1, 99999)),
            'subMerNo' => '' . intval(mt_rand(1, 9999999)),
            'payType' => '800201',
            'clientIp' => Tool::getClientIp(),
            'phoneNo' => '12345678901',
            'settleType' => '1'
        ];
        $aData['signature'] = $sSafeStr = $this->compileSign($oPaymentAccount, $aData, $this->signNeedColumns);
        $aInputData = $this->compileData($aData);

        return $aInputData;
    }

    /**
     * 查询签名组建
     * @param $oPaymentAccount
     * @param $sOrderNo
     * @param $sServiceOrderNo
     * @return array
     */
    public function & compileQueryData($oPaymentAccount, $sOrderNo, $sServiceOrderNo) {
        $aData = [
            'merchantNo' => $oPaymentAccount->account,
            'userOrderNo' => $sOrderNo,
            'listid' => $sServiceOrderNo,
            'transId' => '003',
        ];
        $aData['signature'] = $sSafeStr = $this->compileQuerySign($oPaymentAccount, $aData, $this->signQueryColumns);
        $aInputData = $this->compileData($aData);

        return $aInputData;
    }

    /**
     * 查询结果验签组建
     * @param $aResponse
     * @return array
     */
    public function & compileQueryReturnData($oPaymentAccount, $aResponse) {
        $sign = md5($oPaymentAccount->account.$oPaymentAccount->safe_key);
        return $sign;
    }

    /**
     * Query from Payment Platform
     * @param PaymentPlatform $oPaymentPlatform
     * @param string $sOrderNo
     * @param string $sServiceOrderNo
     * @param array & $aResponses
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
        $sResponse = $this->curl_post($oPaymentPlatform->getQueryUrl($oPaymentAccount), $aDataQuery);
        file_put_contents('/tmp/ht'.$this->smName.'_' . $sOrderNo, $sResponse . "\n", FILE_APPEND);
        if (empty($sResponse)) {
            return self::PAY_QUERY_FAILED;
        }
        $aResponses = json_decode($sResponse, true);
        if (!count($aResponses)) {
            return self::PAY_QUERY_PARSE_ERROR;
        }
        if(isset($aResponses['retCode']) && $aResponses['retCode']=='0004' && $aResponses['retMsg']=='订单信息不存在'){
            return self::PAY_NO_ORDER;
        }

        if ($aResponses['retcode']=='205233' && $aResponses['retmsg'] == '订单不存在') {
            return self::PAY_NO_ORDER;
        }
        switch ($aResponses['state']) {
            case '3':
                //支付返回成功校验签名
                $sSign = $this->compileQueryReturnData($oPaymentAccount, $aResponses);
                if ($sSign != $aResponses['signature']) {
                    return self::PAY_SIGN_ERROR;
                }
                return self::PAY_SUCCESS;
                break;
            default:
                //其他状态归结为未支付
                return self::PAY_UNPAY;
        }
    }

    public static function & compileCallBackData($aBackData, $sIp) {
        $aData = [
            'order_no' => $aBackData['orderNo'],
            'service_order_no' => $aBackData['orderNo'],//汇腾回调无渠道订单号,此处将渠道订单号等于充值订单号
            'merchant_code' => $aBackData['merNo'],
            'amount' => $aBackData['transAmt'] / 100,
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
            'service_order_no' => $aResponses['listid'],
            'order_no' => $aResponses['sp_billno'],
        ];
        return $data;
    }
}
