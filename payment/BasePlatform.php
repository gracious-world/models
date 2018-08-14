<?php

/**
 * 支付平台基类
 *
 * @author Winter
 */
class BasePlatform {

    /**
     * 支付成功
     */
    const PAY_SUCCESS = 1;

    /**
     * 查询失败
     */
    const PAY_QUERY_FAILED = -1;

    /**
     * 查询结果解析失败
     */
    const PAY_QUERY_PARSE_ERROR = -2;

    /**
     * 查询结果签名错误
     */
    const PAY_SIGN_ERROR = -3;

    /**
     * 订单不存在
     */
    const PAY_NO_ORDER = -4;

    /**
     * 订单未支付
     */
    const PAY_UNPAY = -5;

    /**
     * 金额错误
     */
    const PAY_AMOUNT_ERROR = -6;

    /**
     * 回调处理成功时，输出的字符串
     * @var string
     */
    public $successMsg = '';

    /**
     * 签名变量名
     * @var string
     */
    public $signColumn = '';

    /**
     * 帐号变量名
     * @var string
     */
    public $accountColumn = '';

    /**
     * 订单号变量名
     * @var string
     */
    public $orderNoColumn = '';

    /**
     * 渠道方订单号变量名
     * @var string
     */
    public $paymentOrderNoColumn = '';

    /**
     * 回调的数据中，可用于检验是否成功的变量名
     * @var string
     */
    public $successColumn = '';

    /**
     * 回调的数据中,标志成功的变量值
     * @var string
     */
    public $successValue = '';

    /**
     * 金额变量名
     * @var string
     */
    public $amountColumn = '';

    /**
     * 回调数据中,银行交易号变量名
     * @var string
     */
    public $bankNoColumn = '';

    /**
     * 回调数据中,银行交易时间变量名
     * @var string
     */
    public $bankTimeColumn = '';

    /**
     * 回调数据中,平台订单时间变量名
     * @var string
     */
    public $serviceOrderTimeColumn = '';

    /**
     * 查询结果中,标志状态的变量名
     * @var string
     */
    public $queryResultColumn = '';

    /**
     * 不参加签名的变量数组
     * @var string
     */
    public $unSignColumns = [];
    /*
     * 是否保存二维码
     */
    public $saveQr = false;

    /**
     * 2维码日志文件名
     * @var string
     */
    public $qrCodeLogFile = '';

    /**
     * 2维码文件完整路径
     * @var string
     */
    public $qrCodePath = '';

    /**
     *
     * @var 2维码文件路径
     */
    public $qrVisitPath = '';

    /**
     * 是否需要中转地址
     */
    public $bNeedDirect = true;

    /**
     * 表单提交提交方式
     * @var string 
     */
    public $sHtmlFormSubmitMethod = 'post';

    /**
     * view名称后缀
     * @var string 
     */
    protected $suffix = '';

    /**
     * 2维码文件保存目录名,按支付平台区分
     * @var string
     */
    public $qrDirName = '';

    public function compileSign($oPaymentAccount, $aInputData, $aNeedKeys = []) {
        
    }

    public function & compileInputData($oPaymentPlatform, $oPaymentAccount, $oDeposit, $oBank, & $sSafeStr) {
        $data = [];
        return $data;
    }

    public function getLoadUrl() {
        return $this->platform->relay_url ? $this->platform->relay_url : $this->platform->load_url;
    }

    public function & compileQueryData($oPaymentAccount, $sOrderNo, $sServiceOrderNo) {
        $data = [];
        return $data;
    }

    public function compileQueryUrl($data) {
        
    }

    /**
     * 向支付平台查询指定订单的信息
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
        
    }

    /**
     * 组装生成回调信息数组
     * @param array $data
     * @param string $ip
     * @return array &
     */
    protected static function & compileCallBackData($data, $ip) {
        $aData = [];
        return $aData;
    }

    /**
     * 保存回调历史
     *
     * @param array $data
     * @param string $ip
     * @return boolean| DepositCallback
     */
    public function addCallBackHistory(& $data, $ip) {
        $aData = $this->compileCallBackData($data, $ip);
        $oDepositCallback = new DepositCallback($aData);
        if ($oDepositCallback->save()) {
            return $oDepositCallback;
        } else {
            file_put_contents('/tmp/deposit-callback-error', $oDepositCallback->getValidationErrorString());
        }
        return false;
    }

    /**
     * 从数组中取得金额
     * @param array $data
     * @return float
     */
    public function getPayAmount($data) {
        return $data[$this->amountColumn];
    }

    /**
     * 从查询结果数组中,取得服务信息,用于更新订单
     * @param array & $aResponses
     * @return array &
     */
    public static function & getServiceInfoFromQueryResult(& $aResponses) {
        return $aResponses;
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
        $postData = "";
        foreach ($aInputData as $key => $value) {
            if ($value) {
                $postData .= $key . '=' . $value . '&';
            }
        }

        $postData = substr($postData, 0, -1);
        $oMyUrl = new MyCurl($sRealUrl);
        $oMyUrl->setPost($postData);
        $oMyUrl->createCurl();
        $oMyUrl->execute();
        $response = $oMyUrl->__tostring();
        //设置二维码存放路径
        $this->setQrPath();
        //设置二维码写日志路径
        $this->compileQrLogFile();
        $this->writeLog('post result: ' . $response);
        if ($response === '' || ! $response) {
            $this->writeLog('access the qrCode server failed');
            return '';
        }

        return $this->processQr($aInputData, $response, $oPaymentAccount);
    }

    /**
     * 二维码结果处理
     * @author zero
     * @param $aInputData
     * @param $sResponse
     * @param $oPaymentAccount
     * @return string
     */
    protected function processQr($aInputData, $sResponse, $oPaymentAccount) {
        $sWxPngPath = '';
        return $sWxPngPath;
    }

    /**
     * 生成并设置二维码保存路径
     */
    protected function setQrPath() {

        $this->qrVisitPath = '/qrCode/' . $this->qrDirName . DS . date('Ym/d/');
        $sQrCodePath = public_path() . $this->qrVisitPath;

        if (!file_exists($sQrCodePath)) {
            @mkdir($sQrCodePath, 0777, true);
            @chmod($sQrCodePath, 0777);
        }
        $this->qrCodePath = $sQrCodePath;
    }

    /**
     * 写日志
     * @param string $sLog
     * @param bool   $bExtraLn  默认为false
     */
    protected function writeLog($sLog, $bExtraLn = false) {
        $sLn = "\n";
        !$bExtraLn or $sLn .= "\n";
        @file_put_contents($this->qrCodeLogFile, $sLog . $sLn, FILE_APPEND);
    }

    /**
     * 生成并设置二维码日志文件路径(含文件名)
     * @return string
     */
    protected function compileQrLogFile() {
        $sLogPath = Config::get('log.root') . DIRECTORY_SEPARATOR . 'qrCode' . DIRECTORY_SEPARATOR . date('Ym/d');
        if (!file_exists($sLogPath)) {
            @mkdir($sLogPath, 0777, true);
            @chmod($sLogPath, 0777);
        }
        return $this->qrCodeLogFile = $sLogPath . DIRECTORY_SEPARATOR . $this->qrDirName;
    }

    /**
     * 保存二维码函数
     * @author zero
     * @param $sUrl
     * @param $fileName
     * @return  true|false
     */
    protected function saveImg($sUrl, $fileName) {

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $sUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        $img = curl_exec($ch);
        curl_close($ch);

        if ($img === '') {
            $this->writeLog('access the qrCode image server failed, url: ' . $sUrl);
            return false;
        }
        $handle = @fopen($this->qrCodePath . $fileName, 'a');
        if (!$handle) {
            $this->writeLog('qrCode save to the sever failed, path: ' . $this->qrCodePath . $fileName);
            return false;
        }
        fwrite($handle, $img);
        fclose($handle);
        return true;
    }

    public function getHtmlFormSubmitMethod() {
        return $this->sHtmlFormSubmitMethod;
    }

    public function isNeedDirect() {
        return $this->bNeedDirect;
    }

    public function getSuffix() {
        return $this->suffix;
    }

}
