<?php

/**
 * 乐付微信平台
 *
 *
 * @author zero
 */
class PaymentLEFUWX extends BasePlatform {


    public $successMsg = 'success';
    public $signColumn = 'sign';
    public $accountColumn = 'partner';
    public $orderNoColumn = 'out_trade_no';
    public $paymentOrderNoColumn = 'trade_id';
    public $successColumn = 'status';
    public $successValue = '1';
    public $amountColumn = 'amount_str';
    public $bankNoColumn = '';
    public $unSignColumns = [];
    public $serviceOrderTimeColumn = '';

    public $qrDirName = 'lefuwx';
    public $saveQr = true;
    private $sOrderNo;
    /*
     * 用于充值请求和充值外层数组
     */
    public $postData = [

        'input_charset' => 'UTF-8',
        'sign_type' => 'SHA1WITHRSA',
        'partner' => '',
        'content' => '',
        'sign' => ''
    ];

    public $signNeedColumns = [ //充值请求
        'amount_str',
//        'input_charset',
        'out_trade_no',
        'partner',
//        'request_time',
        'return_url',
        'service',
//        'sign_type',
        'sub_body',
        'subject',
        'wx_pay_type',
    ];
    public $signNeedColumnsForNotify = [ //回调
        'amount_fee',
        'amount_str',
        'business_type',
        'created_at',
        'out_trade_no',
        'status',
        'trade_id',
    ];
    public $signNeedColumnsForQuery = [//查询
        'service',
        'partner',
        'out_trade_no',
    ];

    private function signStr($aInputData, $aNeedColumns = []) {
        $aData = [];
        if (!$aNeedColumns) {
            $aNeedColumns = array_keys($aInputData);
        }
        foreach ($aNeedColumns as $sColumn) {
            if (isset($aInputData[$sColumn]) && $aInputData[$sColumn] != '') {
                $aData[$sColumn] = $sColumn . '=' . $aInputData[$sColumn];
            }
        }
        $sSignStr = implode('&', $aData);
        return $sSignStr;
    }

    public function compileSign($oPaymentAccount, $aInputData, $aNeedKeys = []) {

        ksort($aInputData);
        $sSignStr = $this->signStr($aInputData, $aNeedKeys);
        openssl_sign($sSignStr, $sSign, $oPaymentAccount->private_key);

        return urlencode(base64_encode($sSign));
    }

    /**
     * 验签
     * @param $oPaymentAccount
     * @param $sDecrypted
     * @param $sSign
     * @return true|false
     */
    public function validSign($oPaymentAccount, $sDecrypted, $sSign) {
        $publicKey = openssl_get_publickey($oPaymentAccount->public_key);
        $sSign = base64_decode($sSign);
        return (bool)openssl_verify($sDecrypted, $sSign, $publicKey);
    }

    /**
     * content参数加密
     * @author zero
     * @param $oPaymentAccount
     * @param $aData
     * @param $aNeedColumns
     * @return string
     */
    public function encryptContents($oPaymentAccount, $aData, $aNeedColumns = []) {

        $encryptData = '';
        ksort($aData);

        $sData = $this->signStr($aData, $aNeedColumns);
        foreach (str_split($sData, 117) as $chunk) {
            if (openssl_public_encrypt($chunk, $encrypted, $oPaymentAccount->public_key)) {
                $encryptData .= $encrypted;
            }
        }

        return urlencode(base64_encode($encryptData));
    }

    /**
     * 解密content参数,并jsondecode处理
     * @param $oPaymentAccount
     * @param $sContents
     * @param $bJsonDecode
     * @return array|string
     */
    public static function decryptContents($oPaymentAccount, $sContents, $bJsonDecode = true) {
        $sRes = '';
        $sContents = base64_decode($sContents);

        foreach (str_split($sContents, 128) as $chunk) {

            openssl_private_decrypt($chunk, $decryptData, $oPaymentAccount->private_key);
            $sRes .= $decryptData;
        }

        return $bJsonDecode ? json_decode($sRes, true) : $sRes;
    }

    /** 提交充值参数
     * @param $oPaymentPlatform
     * @param $oPaymentAccount
     * @param $oDeposit
     * @param $oBank
     * @param $sSafeStr
     * @return array
     */
    public function & compileInputData($oPaymentPlatform, $oPaymentAccount, $oDeposit, $oBank, & $sSafeStr) {//充值

        $sMerchantCode = $oPaymentAccount->account;
        $aParams = [
//            'input_charset' => 'UTF-8',
            'partner' => $sMerchantCode,
            'service' => 'wx_pay',
//            'sign_type' => 'SHA1WITHRSA',
            'return_url' => $oPaymentPlatform->notify_url,
//            'request_time' => date('YmdHis', strtotime($oDeposit->created_at)),
            'out_trade_no' => $this->sOrderNo = $oDeposit->order_no,
            'amount_str' => $oDeposit->amount,
            'wx_pay_type' => 'wx_sm',
            'subject' => 'Vitrual' . intval(mt_rand(1, 99999)),
            'sub_body' => 'Vitrual' . intval(mt_rand(1, 99999)),
        ];

        $aData = $this->postData;
        $aData['partner'] = $sMerchantCode;
        //content加密
        $aData['content'] = $this->encryptContents($oPaymentAccount, $aParams, $this->signNeedColumns);
        //sign
        $aData['sign'] = $sSafeStr = $this->compileSign($oPaymentAccount, $aParams, $this->signNeedColumns);

        return $aData;
    }

    /**
     *
     * @param $oPaymentAccount
     * @param $sOrderNo
     * @param $sServiceOrderNo
     * @return array
     */
    public function & compileQueryData($oPaymentAccount, $sOrderNo, $sServiceOrderNo) {//查询

        $aContentData = [
            'service' => 'find_trade',
            'partner' => $oPaymentAccount->account,
            'out_trade_no' => $sOrderNo,
        ];

        $aData = $this->postData;
        //content加密
        $aData['content'] = $this->encryptContents($oPaymentAccount, $aContentData, $this->signNeedColumnsForQuery);
        //sign
        $aData['sign'] = $this->compileSign($oPaymentAccount, $aContentData, $this->signNeedColumnsForQuery);

        return $aData;
    }

    /**
     * 回调生成sign
     * @param $oPaymentAccount
     * @param $sContent
     * @param $sSign
     * @return string
     */
    public function & compileSignReturn($oPaymentAccount, $sContent, $sSign) {//回调签名参数

        $sSign = $this->validSign($oPaymentAccount, $sContent, $sSign);

        return $sSign;
    }

    /**
     * 查询处理函数
     * @author zero
     * @param $oPaymentPlatform
     * @param $oPaymentAccount
     * @param $sOrderNo
     * @param $sServiceOrderNo
     * @param $aResponses
     * @return int
     */
    public function queryFromPlatform($oPaymentPlatform, $oPaymentAccount, $sOrderNo, $sServiceOrderNo = null, & $aResponses) {

        $data = $this->compileQueryData($oPaymentAccount, $sOrderNo, $sServiceOrderNo);
        $postDataTmp = [];
        foreach ($data as $k => $v) {
            $postDataTmp[$k] = $k . '=' . $v;
        }
        $postData = implode('&', $postDataTmp);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $oPaymentPlatform->getQueryUrl($oPaymentAccount));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //将数据传给变量
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); //取消身份验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        $response = curl_exec($ch); //接收返回信息
        @file_put_contents('/tmp/lfwx_outer' . $sOrderNo, $response, FILE_APPEND);
        if (curl_errno($ch)) {
            //出错则显示错误信息
            print curl_error($ch);
        }
        curl_close($ch); //关闭curl链接

        return $this->_processResponse($oPaymentAccount, $sOrderNo, $response,$aResponses);
    }

    /**
     * 查询响应处理
     * @author zero
     * @param $oPaymentAccount
     * @param $sOrderNo
     * @param $response
     * @param $aDecrypted
     * @return int
     */
    private function _processResponse($oPaymentAccount, $sOrderNo, $response,& $aDecrypted) {

        /*
         * 先判断外层数据,然后判断内层数据
         */
        if ($response === '') {     // query failed
            return self::PAY_QUERY_FAILED;
        }
        $aResponses = objectToArray(json_decode($response, true));

        if (!is_array($aResponses) || !count($aResponses)) {
            return self::PAY_QUERY_PARSE_ERROR;
        }

        if ($aResponses['is_succ'] === 'F') {
            return self::PAY_QUERY_FAILED;
        }
        if($aResponses['response'] == null){
            return self::PAY_NO_ORDER;
        }
        //内层参数判断开始
        $sContent = static::decryptContents($oPaymentAccount, $aResponses['response'], false);
        if(!$sContent){
            return self::PAY_QUERY_PARSE_ERROR;
        }
        if (!$bValidSign = $this->validSign($oPaymentAccount, $sContent, $aResponses['sign'])){
            return self::PAY_SIGN_ERROR;
        }
        $aDecrypted = json_decode($sContent, true);
        @file_put_contents('/tmp/lfwx_inner' . $sOrderNo, var_export($aDecrypted,true), FILE_APPEND);
        switch ($aDecrypted['status']) {
            case 0:
                return self::PAY_UNPAY;
                break;
            case 2:
                return self::PAY_QUERY_FAILED;
                break;
            case 1:
                return self::PAY_SUCCESS;
                break;
            default:
                return self::PAY_UNPAY;
                break;
        }
    }

    public static function & compileCallBackData($data, $ip) {

        $oDeposit = Deposit::getDepositByNo($data['out_trade_no']);
        $aContentData = $data['content'];

        $aData = [
            'order_no' => $aContentData['trade_id'],
            'service_order_no' => $aContentData['out_trade_no'],
            'merchant_code' => $oDeposit->merchant_code,
            'amount' => $aContentData['amount_str'],
            'ip' => $ip,
            'status' => DepositCallback::STATUS_CALLED,
            'post_data' => var_export($data, true),
            'callback_time' => time(),
            'callback_at' => date('Y-m-d H:i:s'),
            'referer' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null,
            'http_user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null
        ];
        return $aData;
    }

    public static function & getServiceInfoFromQueryResult(& $aResponses) {

        $data = [
            'service_order_no' => $aResponses['trade_id'],
            'order_no' => $aResponses['out_trade_no'],
        ];

        return $data;
    }

    protected function processQr($aInputData, $sResponse, $oPaymentAccount) {

        $sWxPngPath = $sMsg = '';
        $this->writeLog('lefuwx qrCode result : ' . $sResponse . ',order: ' . $this->sOrderNo);
        $aResponse = objectToArray(json_decode($sResponse, true));
        if (!is_array($aResponse) || !count($aResponse)) {
            return $sWxPngPath;
        }
        if ($aResponse['is_succ'] === 'T') {
            //解密content
            $sContent = static::decryptContents($oPaymentAccount, $aResponse['response'], false);
            $bValidSign = false;

            if ($sContent && $bValidSign = $this->validSign($oPaymentAccount, $sContent, $aResponse['sign'])) {
                $sQrCodePath = $this->qrCodePath . $this->sOrderNo . 'output.png';
                $qrCode = json_decode($sContent, true)['base64QRCode'];
                file_put_contents($sQrCodePath, file_get_contents($qrCode));
                $sWxPngPath = $this->qrVisitPath . $this->sOrderNo . 'output.png';
            } else {
                $this->writeLog('lefuwx qrCode  decrypt failed,content:' . $sContent . ';bValidSign:' . ($bValidSign ? 1 : 0));
            }
            return $sWxPngPath;
        }
        $this->writeLog('get qrCode failed reason: ' . $aResponse['fault_reason']);
        return $sWxPngPath;
    }
}
