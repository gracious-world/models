<?php

/**
 * 汇元微信平台
 *
 * @author zero
 */
class PaymentHUIYUANWX extends BasePlatform {


    public $successMsg = 'OK';
    public $signColumn = 'sign';
    public $accountColumn = 'agent_id';
    public $orderNoColumn = 'agent_bill_id';
    public $paymentOrderNoColumn = 'jnet_bill_no';
    public $successColumn = 'result';
    public $successValue = '1';
    public $amountColumn = 'pay_amt';
    public $bankNoColumn = '';
    public $unSignColumns = ['sign'];
    public $serviceOrderTimeColumn = '';
    //接口版本号
    public $version = 1;
    public $payType = 30;
    public $remark = 'huiyuanwx_query';

    public $qrDirName='huiyuanwx';
    public $saveQr = true;
    public $signNeedColumns = [ //支付

        'version',
        'agent_id',
        'agent_bill_id',
        'agent_bill_time',
        'pay_type',
        'pay_amt',
        'notify_url',
        'user_ip',
    ];
    public $signNeedColumnsForNotify = [ //回调
        'result',
        'agent_id',
        'jnet_bill_no',
        'agent_bill_id',
        'pay_type',
        'pay_amt',//订单实际支付金额
        'remark',
    ];
    public $signNeedColumnsForQuery = [//查询
        'version',
        'agent_id',
        'agent_bill_id',
        'agent_bill_time',
        'return_mode',
    ];
    public $signNeedColumnsForQueryCheck = [ //查询检查
        'agent_id',
        'agent_bill_id',
        'jnet_bill_no',
        'pay_type',
        'result',
        'pay_amt',
        'pay_message',
        'remark',
    ];

    protected function signStr($aInputData, $aNeedColumns = []) {
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

    protected function queryCheckSignStr($aInputData, $aNeedColumns = []) {
        $aData = [];
        if (!$aNeedColumns) {
            $aNeedColumns = array_keys($aInputData);
        }
        foreach ($aNeedColumns as $sColumn) {
            if (isset($aInputData[$sColumn])) {
                $aData[$sColumn] = $sColumn . '=' . $aInputData[$sColumn];
            }
        }
        $sSignStr = implode('|', $aData);
        return $sSignStr;
    }

    public function compileSign($oPaymentAccount, $aInputData, $aNeedKeys = []) {

        $sSafeKey = $oPaymentAccount->safe_key;
        $sSignStr = $this->signStr($aInputData, $aNeedKeys);
        $sKey = '&key=' . $sSafeKey;
        $sSign = strtolower(md5($sSignStr . $sKey));
        return $sSign;
    }

    public function compileQueryCheckSign($oPaymentAccount, $aInputData, $aNeedKeys = []) {

        $sSafeKey = $oPaymentAccount->safe_key;
        $sSignStr = $this->queryCheckSignStr($aInputData, $aNeedKeys);
        $sKey = '|key=' . $sSafeKey;
        $sSign = strtolower(md5($sSignStr . $sKey));
        return $sSign;
    }

    /** 提交充值参数
     * @param $oPaymentPlatform
     * @param $oPaymentAccount
     * @param $oDeposit
     * @param $oBank
     * @param $sSafeStr
     * @return array
     */
    public function & compileInputData($oPaymentPlatform, $oPaymentAccount, $oDeposit, $oBank, & $sSafeStr) {

        $data = [
            'version' => $this->version,
            'agent_id' => $oPaymentAccount->account,
            'agent_bill_id' => $oDeposit->order_no,
            'agent_bill_time' => date('YmdHis', strtotime($oDeposit->created_at)),
            'pay_type' => $this->payType,
            'pay_amt' => $oDeposit->amount,
            'notify_url' => $oPaymentPlatform->notify_url,
            'user_ip' => $oDeposit->ip,
        ];
        $data['sign'] = $sSafeStr = $this->compileSign($oPaymentAccount, $data, $this->signNeedColumns);
        //追加不参与签名的字段
        $data['goods_name'] = 'Vitrual' . intval(mt_rand(1, 99999));
        $data['remark'] = $this->remark;// todo: 生成remark

        return $data;
    }

    public function & compileQueryData($oPaymentAccount, $sOrderNo, $sServiceOrderNo) {

        $iCreatedAt = 0;
        $oDeposit = Deposit::getDepositByNo($sOrderNo);
        if ($oDeposit) {
            $iCreatedAt = date('YmdHis', strtotime($oDeposit->created_at));
        }
        $data = [
            'version' => $this->version,
            'agent_id' => $oPaymentAccount->account,
            'agent_bill_id' => $sOrderNo,
            'agent_bill_time' => $iCreatedAt,
            'return_mode' => 1,
        ];
        $data['sign'] = $this->compileSign($oPaymentAccount,$data, $this->signNeedColumnsForQuery);
        return $data;
    }

    public function & compileSignReturn($oPaymentAccount, $aInputData, $aNeedKeys = []) {

        $aData['result']        = $aInputData['result'];
        $aData['agent_id']      = $aInputData['agent_id'];
        $aData['jnet_bill_no']  = $aInputData['jnet_bill_no'];
        $aData['agent_bill_id'] = $aInputData['agent_bill_id'];
        $aData['pay_type']      = $this->payType;
        $aData['pay_amt']       = $aInputData['pay_amt'];
        $aData['remark']        = $aInputData['remark'];

        $sSign = $this->compileSign($oPaymentAccount, $aData, $this->signNeedColumnsForNotify);
        return $sSign;
    }

    public function queryFromPlatform($oPaymentPlatform, $oPaymentAccount, $sOrderNo, $sServiceOrderNo = null, & $aResponse) {

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
        file_put_contents('/tmp/hywx_' . $sOrderNo, $response, FILE_APPEND);
        if (curl_errno($ch)) {
            //出错则显示错误信息
            print curl_error($ch);
        }
        curl_close($ch); //关闭curl链接

        return $this->_processQuery($oPaymentAccount,$response,$aResponse);

    }

    /**查询结果校验
     * @author zero
     * @param $response string
     * @param $oPaymentAccount object
     * @param $aResponses
     * @return int
     */
    protected function _processQuery($oPaymentAccount,$response,& $aResponses) {

        $aResponses = [];

        if ($response === '') {     // query failed
            return self::PAY_QUERY_FAILED;
        }
        $aQueryResponses = explode('|', $response);
        foreach ($aQueryResponses as $v) {
            $aResponse = explode('=', $v);
            if (count($aResponse) == 1){
                $aResponses = $aResponse;
                return self::PAY_NO_ORDER;
            }
            $aResponses[$aResponse[0]] = $aResponse[1];
        }

        $sSign = $this->compileQueryCheckSign($oPaymentAccount, $aResponses, $this->signNeedColumnsForQueryCheck);

        if ($sSign != $aResponses['sign']) {
            return self::PAY_SIGN_ERROR;
        }

        if ($aResponses['result'] == 1 && $aResponses['pay_amt'] > 0) {
            return self::PAY_SUCCESS;
        }
        return self::PAY_UNPAY;
    }

    public static function & compileCallBackData($data, $ip) {

        $aData = [
            'order_no' => $data['agent_bill_id'],
            'service_order_no' => $data['jnet_bill_no'],
            'merchant_code' => $data['agent_id'],
            'amount' => $data['pay_amt'],
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
            'service_order_no' => $aResponses['jnet_bill_no'],
            'order_no' => $aResponses['agent_bill_id'],
        ];
        return $data;
    }

    protected function processQr($aInputData,$sResponse,$oPaymentAccount){

        $sWxPngPath = $sMsg =  '';
        $aResponse = explode(',',$sResponse);
        $orderNo = $aInputData['agent_bill_id'];
        switch($aResponse[0]){

            case 'ERROR001':
                $sMsg = 'get qrCode failed,reason: merchant ID cant be empty,order_no:'.$orderNo;
                break;
            case 'ERROR002':
                $sMsg = 'get qrCode failed,reason:invalid username or username is not enabled,order_no:'.$orderNo;
                break;
            case 'ERROR004':
                $sMsg = 'get qrCode failed,reason: MD5 authentication failed,order_no:'.$orderNo;
                break;
            case 'ERROR005':
                $sMsg = 'get qrCode failed,reason: merchant order number cant be empty,order_no:'.$orderNo;
                break;
            case 'ERROR008':
                $sMsg = 'get qrCode failed,reason: the denomination or amount cant be null,order_no:'.$orderNo;
                break;
            case 'ERROR009':
                $sMsg = 'get qrCode failed,reason: repeat orders,order_no:'.$orderNo;
                break;
            case 'ERROR017':
                $sMsg = 'get qrCode failed,reason:channel emergency shutdown,please contact the business,order_no:'.$orderNo;
                break;
            case 'ERROR016':
                $sMsg = 'get qrCode failed,reason:merchant product is not open,order_no:'.$orderNo;
                break;
            case 'OK':
                $sMsg = 'qrCode success,result:' . $sResponse;
                //存取微信图片开始
                $sFileName = $aInputData['agent_bill_id'] . 'qrcode.png';
                $this->saveImg($aResponse[2], $sFileName);
                $sWxPngPath = $this->qrVisitPath . $sFileName;
                break;
        }
        $this->writeLog($sMsg);
        return $sWxPngPath;
    }
}
