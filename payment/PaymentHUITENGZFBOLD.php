<?php

/**
 * 汇腾平台支付宝支付
 * @author Tino
 */
class PaymentHUITENGZFBOLD extends PaymentHUITENGWX {

    public $qrDirName = 'huitengzfbold';
    public $successMsg = '0000';
    public $signColumn = 'signature';
    public $accountColumn = 'merchantId';
    public $orderNoColumn = 'userOrderNo';
    public $paymentOrderNoColumn = 'orderNo';
    public $successColumn = 'status';
    public $successValue = '02';
    public $amountColumn = 'orderAmt';
    public $bankNoColumn = 'bankCode';
    public $unSignColumns = ['status', 'beginTime', 'endTime', 'orderAmt', 'signature'];
    public $serviceOrderTimeColumn = '';
    protected $payCode = 'ZFBSMZF';//支付宝:'ZFBSMZF',微信:'WXSMZF',QQ:'QQZF'
    public $bankTimeColumn = "OrderDate";
    public $saveQr = true;
    public $bNeedDirect = false;
    public $sHtmlFormSubmitMethod = 'get';

    public $signNeedColumns = [//充值请求
        'userOrderNo',
        'payCode',
        'merchantNo',
    ];
    public $signQueryColumns = [//查询加密字段
        'merchantNo',
    ];
    public $signQueryReturnColumns = [//查询响应信息加密字段
        'merchantNo',
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
     * @param $oPaymentAccount
     * @param $aInputData
     * @param array $aNeedKeys
     * @return string
     */
    public function compileSign($oPaymentAccount, $aInputData, $aNeedKeys = []) {

        $sSignStr = $this->signStr($aInputData, $aNeedKeys);
        $sSignStr .= $oPaymentAccount->safe_key;
        return md5($sSignStr);
    }

    /**
     * 通知签名组建
     * @param $oPaymentAccount
     * @param $aInputData
     * @param array $aNeedKeys
     * @return string
     */
    public function compileSignReturn($oPaymentAccount, $aInputData, $aNeedKeys = []) {
        return md5($aInputData['orderNo'] . $aInputData['userOrderNo'] . $oPaymentAccount->safe_key);
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
            'payCode' => $this->payCode,
            'merchantNo' => (string)$oPaymentAccount->account,
            'transId' => '001',
            'returnUrl' => $oPaymentPlatform->return_url,
            'notifyUrl' => $oPaymentPlatform->notify_url,
            'orderAmt' => (string) ($oDeposit->amount),
            'orderTitle' => 'Vitrual' . intval(mt_rand(1, 99999)),
            'settleType' => '1',
            'phoneNo' => '134'.intval(mt_rand(1, 99999999)),
        ];
        $aData['signature'] = $sSafeStr = $this->compileSign($oPaymentAccount, $aData, $this->signNeedColumns);
        $aNewData = [
            'reqJson' => json_encode($aData),
        ];
        return $aNewData;
    }

    /**
     * 查询结果验签组建
     * @param $aResponse
     * @return array
     */
    public function & compileQueryReturnData($oPaymentAccount, $aResponse) {
        $aData = [
            'merchantNo' => $oPaymentAccount->account,
        ];

        $sign = $this->compileQuerySign($oPaymentAccount, $aData, $this->signQueryReturnColumns);
        return $sign;
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

        return $this->processQr($aInputData, $response, $oPaymentAccount);
    }

    public function processQr($aInputData, $sResponse, $oPaymentAccount) {
        $sWxPngPath = '';
        $aRes = json_decode($sResponse, true);
        $resp_code = array_get($aRes, 'respCode');

        if ($resp_code == "000000") {

            $qrcode = $aRes['payStr'];
            $sQrcodeNoLogoPath = $this->qrCodePath . $aRes['orderNo'] . 'qrcode.png';
            $sQrocdeHasLogoPath = $this->qrCodePath . $aRes['orderNo'] . 'output.png';

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
            $sWxPngPath = $this->qrVisitPath . $aRes['orderNo'] . 'output.png';
        }
        return $sWxPngPath;
    }
    /**
     * Query from Payment Platform
     * @param PaymentPlatform $oPaymentPlatform
     * @param string $sOrderNo
     * @param string $sServiceOrderNo
     * @param array & $aResonses
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
        file_put_contents('/tmp/htzfb_' . $sOrderNo, var_export($aDataQuery, true) . "\n", FILE_APPEND);

        $sResponse = $this->curl_post($oPaymentPlatform->getQueryUrl($oPaymentAccount), $aDataQuery);
        file_put_contents('/tmp/htzfb_' . $sOrderNo, $sResponse . "\n", FILE_APPEND);
        if (empty($sResponse)) {
            return self::PAY_QUERY_PARSE_ERROR;
        }
        $aResonses = json_decode($sResponse, true);
        if (!count($aResonses)) {
            return self::PAY_QUERY_PARSE_ERROR;
        }
        if (!key_exists('orderStatus', $aResonses)) {
            return self::PAY_QUERY_PARSE_ERROR;
        }
        switch ($aResonses['orderStatus']) {
            //将查询结果处于等待支付和支付失败归为未支付
            case '00':
                return self::PAY_UNPAY;
                break;
            case '03':
                return self::PAY_UNPAY;
                break;
            case '02':
                //支付返回成功校验签名
                $sSign = $this->compileQueryReturnData($oPaymentAccount, $aResonses);
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
    }

    public static function & compileCallBackData($aBackData, $sIp) {
        $aData = [
            'order_no' => $aBackData['userOrderNo'],
            'service_order_no' => $aBackData['orderNo'],
            'merchant_code' => key_exists('merchantId', $aBackData) ? $aBackData['merchantId'] : 'thzfb',
            'amount' => formatNumber($aBackData['orderAmt'], 2),
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

    /**
     * 从数组中取得金额
     * @param array $data
     * @return float
     */
    public function getPayAmount($data) {
        return $data[$this->amountColumn];
    }
}
