<?php

/**
 * 智付微信平台
 *
 * @author white
 */
class PaymentZHIFUWX extends BasePlatform {

    private $priKey;
    private $pubKey;
    public $successMsg                   = 'SUCCESS';
    public $signColumn                   = 'sign';
    public $accountColumn                = 'merchant_code';
    public $orderNoColumn                = 'order_no';
    public $paymentOrderNoColumn         = 'trade_no';
    public $successColumn                = 'trade_status';
    public $successValue                 = 'SUCCESS';
    public $amountColumn                 = 'order_amount';
    public $bankNoColumn                 = 'bank_seq_no';
    public $unSignColumns                = ['sign_type', 'sign'];
    public $serviceOrderTimeColumn       = 'trade_time';
    public $queryResultColumn            = 'trade_status';
    public $dataAll                      = [];
    public $qrDirName                    = 'zhifuwx';
    public $saveQr                    = true;
    public $signNeedColumns              = [
        "interface_version",
        "merchant_code",
        "notify_url",
        "order_amount",
        "order_no",
        "order_time",
        "product_name",
        "product_code",
        "product_num",
        "product_desc",
        "extra_return_param",
        "extend_param",
        "service_type",
    ];
    public $signNeedColumnsForNotify     = [
        'bank_seq_no',
        'extra_return_param',
        'interface_version',
        'merchant_code',
        'notify_id',
        'notify_type',
        'order_amount',
        'order_no',
        'order_time',
        'trade_no',
        'trade_status',
        'trade_time',
    ];
    public $signNeedColumnsForQuery      = [
        'interface_version',
        'merchant_code',
        'order_no',
        'service_type',
        'trade_no',
    ];
    public $signNeedColumnsForQueryCheck = [
        'merchant_code',
        'order_amount',
        'order_no',
        'order_time',
        'trade_no',
        'trade_status',
        'trade_time',
    ];

    private function signStr($aInputData) {
        $aData = [];
        foreach ($this->signNeedColumns as $sColumn) {
            if (isset($aInputData[$sColumn]) && $aInputData[$sColumn] != '') {
                $aData[$sColumn] = $sColumn . '=' . $aInputData[$sColumn];
            }
        }
        $sSignStr = implode('&', $aData);
        return $sSignStr;
    }

    public function compileSign($oPaymentAccount, $aInputData, $aNeedKeys = []) {
        $this->priKey = $oPaymentAccount->private_key;
        $this->pubKey = $oPaymentAccount->public_key;
        $sSignStr     = $this->signStr($aInputData);
        $priKey       = openssl_get_privatekey($this->priKey);
        openssl_sign($sSignStr, $sign_info, $priKey, OPENSSL_ALGO_MD5);
        $sSign        = urlencode(base64_encode($sign_info));
        return $sSign;
    }

    public function & compileInputData($oPaymentPlatform, $oPaymentAccount, $oDeposit, $oBank, & $sSafeStr) {
        $data         = $aInputData   = [
            'interface_version'  => 'V3.0',
            'merchant_code'      => $oPaymentAccount->account, // 一个 密钥私钥对应一个 merchant_code. 此测试用的 私钥 对应的是 1111110166
            'notify_url'         => $oPaymentPlatform->notify_url, // /dnotify/zfwx  // 'http://www.dinpay.com/Notify_Url.jsp',
            'order_amount'       => $oDeposit->amount,
            'order_no'           => $oDeposit->order_no,
            'order_time'         => $oDeposit->created_at,
            'product_name'       => 'wxtest',
            'product_code'       => '',
            'product_num'        => '',
            'product_desc'       => '',
            'extra_return_param' => '',
            'extend_param'       => '',
            'service_type'       => 'wxpay',
            'sign_type'          => 'RSA-S',
        ];
        $data['sign'] = $sSafeStr     = $this->compileSign($oPaymentAccount, $data);
        return $data;
    }

    private function signStrForNotify($aInputData) {
        $aData = [];
        foreach ($this->signNeedColumnsForNotify as $sColumn) {
            if (isset($aInputData[$sColumn]) && $aInputData[$sColumn] != '') {
                $aData[$sColumn] = $sColumn . '=' . $aInputData[$sColumn];
            }
        }
        $sSignStr = implode('&', $aData);
        return $sSignStr;
    }

    public function compileSignReturn($aInputData, $sPostedSign, $sPubKey) {
        $signStrForNotify = $this->signStrForNotify($aInputData);
        $dinpaySign       = base64_decode($aInputData['sign']);
        if (openssl_verify($signStrForNotify, $dinpaySign, $sPubKey, OPENSSL_ALGO_MD5)) {
            return true;
        } else {
            return false;
        }
    }

    private function signStrForQuery($aInputData) {
        $aData = [];
        foreach ($this->signNeedColumnsForQuery as $sColumn) {
            if (isset($aInputData[$sColumn]) && $aInputData[$sColumn] != '') {
                $aData[$sColumn] = $sColumn . '=' . $aInputData[$sColumn];
            }
        }
        $sSignStr = implode('&', $aData);
        return $sSignStr;
    }

    public function compileSignForQuery($oPaymentAccount, $aInputData, $aNeedKeys = []) {
        $sSignStr = $this->signStrForQuery($aInputData);
        $priKey   = openssl_get_privatekey($oPaymentAccount->private_key);
        openssl_sign($sSignStr, $sign_info, $priKey, OPENSSL_ALGO_MD5);
        $sSign    = urlencode(base64_encode($sign_info));
        return $sSign;
    }

    public function & compileQueryData($oPaymentAccount, $sOrderNo, $sServiceOrderNo) {
        $data         = [
            'trade_no'          => $sServiceOrderNo,
            'merchant_code'     => $oPaymentAccount->account,
            'interface_version' => 'V3.0',
            'service_type'      => 'single_trade_query',
            'sign_type'         => 'RSA-S',
            'order_no'          => $sOrderNo,
        ];
        $data['sign'] = $this->compileSignForQuery($oPaymentAccount, $data);
        return $data;
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
        !isset($this->dataAll['trade_no']) or $sServiceOrderNo = $this->dataAll['trade_no'];
        $data            = $this->compileQueryData($oPaymentAccount, $sOrderNo, $sServiceOrderNo);
        $postDataTmp     = [];
        foreach ($data as $k => $v) {
            $postDataTmp[$k] = $k . '=' . $v;
        }
        $postData = implode('&', $postDataTmp);
//        file_put_contents('/tmp/zfwx_' . $sOrderNo, "private key :$oPaymentAccount->private_key\r\n");
//        file_put_contents('/tmp/zfwx_' . $sOrderNo, 'post url :'.$oPaymentPlatform->getQueryUrl($oPaymentAccount)."\r\n",FILE_APPEND);
//        file_put_contents('/tmp/zfwx_' . $sOrderNo, 'post data :'.$postData."\r\n",FILE_APPEND);
        $ch       = curl_init();
        curl_setopt($ch, CURLOPT_URL, $oPaymentPlatform->getQueryUrl($oPaymentAccount));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //将数据传给变量
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); //取消身份验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        $response = curl_exec($ch); //接收返回信息
        file_put_contents('/tmp/zfwx_' . $sOrderNo, $response, FILE_APPEND);
        if (curl_errno($ch)) {//出错则显示错误信息
            print curl_error($ch);
        }
        curl_close($ch); //关闭curl链接
        if ($response === '') {     // query failed
            return self::PAY_QUERY_FAILED;
        }
        $resParser = xml_parser_create();
        if (!xml_parse_into_struct($resParser, $response, $values, $index)) {   // parse error
            return self::PAY_QUERY_PARSE_ERROR;
        }
        $aResponses = [];
        foreach ($values as $aInfo) {
            if ($aInfo['type'] != 'complete') {
                continue;
            }
            $aResponses[strtolower($aInfo['tag'])] = $aInfo['value'];
        }
        if ($aResponses['is_success'] == 'F') {      // NO ORDER
            return self::PAY_NO_ORDER;
        }

        $sDinpaySign = $aResponses['sign'];

        unset($aResponses['sign'], $aResponses['sign_type']);

        $aDataQueryCheck = [];
        foreach ($this->signNeedColumnsForQueryCheck as $sColumn) {
            if (isset($aResponses[$sColumn]) && $aResponses[$sColumn] != '') {
                $aDataQueryCheck[$sColumn] = $sColumn . '=' . $aResponses[$sColumn];
            }
        }
        $signStr = implode('&', $aDataQueryCheck);

        $dinpaySign = base64_decode($sDinpaySign);
        if (!openssl_verify($signStr, $dinpaySign, $oPaymentAccount->public_key, OPENSSL_ALGO_MD5)) {
            return self::PAY_SIGN_ERROR;
        }

        switch ($aResponses['trade_status']) {
            case 'UNPAY':
                return self::PAY_UNPAY;
            case 'SUCCESS':
                return self::PAY_SUCCESS;
        }
    }

    public static function & compileCallBackData($data, $ip) {
        $aData = [
            'order_no'         => $data['order_no'],
            'service_order_no' => $data['trade_no'],
            'merchant_code'    => $data['merchant_code'],
            'amount'           => $data['order_amount'],
            'ip'               => $ip,
            'status'           => DepositCallback::STATUS_CALLED,
            'post_data'        => var_export($data, true),
            'callback_time'    => time(),
            'callback_at'      => date('Y-m-d H:i:s'),
            'referer'          => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null,
            'http_user_agent'  => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null
        ];
        return $aData;
    }

    public static function & getServiceInfoFromQueryResult(& $aResponses) {
        $data = [
            'service_order_no' => $aResponses['trade_no'],
            'service_time'     => $aResponses['trade_time'],
        ];
        return $data;
    }

    public function processQr($aInputData, $sResponse, $oPaymentAccount) {

        $sWxPngPath = '';
        $res        = simplexml_load_string($sResponse);
        $resp_code  = $res->response->resp_code;

        if ($resp_code == "SUCCESS") {

            $qrcode             = $res->response->trade->qrcode;
            $sQrcodeNoLogoPath  = $this->qrCodePath . $aInputData['order_no'] . 'qrcode.png';
            $sQrocdeHasLogoPath = $this->qrCodePath . $aInputData['order_no'] . 'output.png';

            if (file_exists($sQrcodeNoLogoPath) or file_exists($sQrocdeHasLogoPath)) {
                unlink($sQrcodeNoLogoPath);
                unlink($sQrocdeHasLogoPath);
            }
            $errorCorrectionLevel = 'L';
            $matrixPointSize      = 10;

            QRcode::png($qrcode, $sQrcodeNoLogoPath, $errorCorrectionLevel, $matrixPointSize, 2);
            $QR         = $sQrcodeNoLogoPath;
            $QR         = imagecreatefromstring(file_get_contents($QR));
            $QR_width   = imagesx($QR);
            $QR_height  = imagesy($QR);
            imagepng($QR, $sQrocdeHasLogoPath);
            imagedestroy($QR);
            //echo "处理获得的二维码为：" ."<br>"."<img src='".$sQrcodePath.$aInputData['order_no']."output.png'/>";
            $sWxPngPath = $this->qrVisitPath . $aInputData['order_no'] . 'output.png';
        }
        return $sWxPngPath;
    }

}
