<?php

/**
 *   dianjiang
 * @author sad
 *
 */
class PaymentDIANJIANGZFBWAP extends BasePlatform
{
    const BLOCKSIZE = 16;

    public $successMsg = 'SUCCESS';
    public $signColumn = 'sign';
    public $accountColumn = 'merchantId';
    public $orderNoColumn = 'accessPayNo';
    public $paymentOrderNoColumn = 'payNo';
    public $successColumn = 'tradeStatus';
    public $bankTimeColumn = 'payTime';

    public $successValue = 1;
    public $amountColumn = 'tradeAmt';
    public $tradeType = 'ZFBWAP';

    protected $paymentName = 'dianjiangzfbwap';
    private $accessId = '100435698';

    /**
     * @var array
     *
     */
    //交易签名所需字段
    public $signNeedColumns = [
        'tradeAmt',
        'merchantId',
        'withdrawType',
        'goodsName',
        'tradeType',
        'accessPayNo',
        'payNotifyUrl'
    ];


    //通知签名字段
    public $notifySignNeedColumns = [
        'accessPayNo',
        'payNo',
        'tradeAmt',
        'actualAmt',
        'tradeStatus',
        'tradeType',
        'payTime'
    ];

    protected function signStr($aInputData, $aNeedColumns = [])
    {
        $sSignStr = '';
        if (!$aNeedColumns) {
            $aNeedColumns = array_keys($aInputData);
        }
        foreach ($aNeedColumns as $sColumn) {
            if (isset($aInputData[$sColumn]) && (!empty($aInputData[$sColumn]) || $aInputData[$sColumn] === 0)) {
                $sSignStr .= $sColumn . '=' . $aInputData[$sColumn] . '&';
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
    public function compileSign($oPaymentAccount, $aInputData, $aNeedKeys = [])
    {
        ksort($aInputData);
        sort($aNeedKeys);
        $sSignStr = $this->signStr($aInputData, $aNeedKeys);
        $sSignStr .= "key=" . $oPaymentAccount->safe_key;
        return strtoupper(md5($sSignStr));
    }


    /**
     * 通知签名组建
     * @param       $oPaymentAccount
     * @param       $aInputData
     * @param array $aNeedKeys
     * @return string
     */
    public function compileSignReturn($oPaymentAccount, $aInputData)
    {
        return $this->compileSign($oPaymentAccount, $aInputData, $this->notifySignNeedColumns);
    }


    /**
     * 充值请求表单数据组建
     *
     * @return array
     */
    public function & compileInputData($oPaymentPlatform, $oPaymentAccount, $oDeposit, $oBank, &$sSafeStr)
    {
        $aData = [
            'tradeAmt' => (string)($oDeposit->amount * 100),
            'merchantId' => $oPaymentAccount->account,
            'withdrawType' => 0,
            'goodsName' => 'gamecard',
            'tradeType' => $this->tradeType,
            'accessPayNo' => $oDeposit->order_no,
            'payNotifyUrl' => $oPaymentPlatform->notify_url
        ];

        $aData['sign'] = $sSafeStr = $this->compileSign($oPaymentAccount, $aData, $this->signNeedColumns);

        ksort($aData);
        $sEncryptContent = $this->encryptInputData($aData,$oPaymentAccount);
        $aInputData = [
            'accessId' => $this->accessId,
            'data' => $sEncryptContent
        ];
        return $aInputData;
    }

    private function encryptInputData($aData, $oPaymentAccount)
    {
        $sStr = json_encode($aData, JSON_UNESCAPED_UNICODE);
        $sKey = $this->getAESKey($oPaymentAccount->safe_key);
        $sEncryptContent = $this->pkcs5_pad($sStr);
        $sEncryptContent = bin2hex(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $sKey, $sEncryptContent, MCRYPT_MODE_ECB));
        return $sEncryptContent;
    }

    /**
     * 填充方式
     * 补足16位或16的倍数位
     * @return string //填充后的字符串
     */
    private function pkcs5_pad($sStr)
    {
        $pad = self::BLOCKSIZE - (strlen($sStr) % self::BLOCKSIZE);
        return $sStr . str_repeat(chr($pad), $pad);
    }

    /**
     * 获取商户密钥的前16位作为加密传输的密钥
     * @param string $key 商户密钥
     * @return string 商户密钥前16位数
     */
    public function getAESKey($key)
    {
        return substr($key, 0, 16);
    }

    /**
     * 解密方法
     * AES128 算法
     * ECB_MODE 模式
     * @return string //解密后字符串
     */
    public function decryptContent($sEncryptionStr, $oPaymentAccount)
    {
        $sKey = $this->getAESKey($oPaymentAccount->safe_key);
        $decrypted = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $sKey, hex2bin($sEncryptionStr), MCRYPT_MODE_ECB);
        $padSize   = ord(substr($decrypted, -1));
        return substr($decrypted, 0, $padSize * -1);
    }

    /**
     * Query from Payment Platform
     *
     * @param PaymentPlatform $oPaymentPlatform
     * @param string $sOrderNo
     * @param string $sServiceOrderNo
     * @param array & $aResponses
     *
     * @return integer | boolean
     *  1: Success
     *  -1: Query Failed
     *  -2: Parse Error
     *  -3: Sign Error
     *  -4: No Order
     *  -5: Unpay
     */
    public function queryFromPlatform($oPaymentPlatform, $oPaymentAccount, $sOrderNo, $sServiceOrderNo = null, & $aResponses)
    {
        return self::PAY_SUCCESS;
    }

    /**
     * @param array $aBackData
     * @param string $sIp
     *
     * @return array
     */
    public static function & compileCallBackData($aBackData, $sIp)
    {

        $sOrderNo = $aBackData['accessPayNo'];
        $oDeposit = UserDeposit::where('order_no', $sOrderNo)->first();

        $aData = [
            'order_no' => $sOrderNo,
            'service_order_no' => $aBackData['payNo'],
            'merchant_code' => $oDeposit->merchant_code,
            'amount' => $aBackData['tradeAmt'],
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

    public static function & getServiceInfoFromQueryResult(& $aResponses)
    {
        $data = [
            'service_order_no' => $aResponses['payNo'],
            'order_no' => $aResponses['accessPayNo'],
        ];
        return $data;
    }

    public function getPayAmount($data)
    {
        return $data[$this->amountColumn] / 100;
    }


}
