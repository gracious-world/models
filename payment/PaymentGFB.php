<?php

/**
 * 国付通平台
 *
 * @author white
 */
class PaymentGFB extends BasePlatform {

    public $payRequestColumns = [
        'orderNo' => 'merOrderNum',
        'account' => 'merchantID',
        'amount' => 'tranAmt',
    ];
    public $notifyColumns = [
        'orderNo' => 'merOrderNum',
        'account' => 'merchantID',
        'amount' => 'tranAmt',
    ];
    public $successMsg = 'RespCode=0000|JumpURL=@url@';
    public $signColumn = 'signValue';
    public $accountColumn = 'merchantID';                // 通知接口中的变量名
//    public $returnAccountColumn = 'partnerID';
    public $orderNoColumn = 'merOrderNum';
    public $paymentOrderNoColumn = 'orderId';
    public $successColumn = 'respCode';
    public $successValue = '0000';
    public $amountColumn = 'tranAmt';
    public $bankNoColumn = '';
    public $serviceOrderTimeColumn = 'tranFinishTime';
    public $unSignColumns = [ 'signMsg'];
    public $queryResponseFormat = 'xml';
    public $queryResultColumn = 'respCode';
    public $signNeedColumns = [
        'version',
        'tranCode' ,
        'merchantID',
        'merOrderNum',
        'tranAmt',
        'feeAmt',
        'tranDateTime',
        'frontMerUrl',
        'backgroundMerUrl',
        'orderId',
        'gopayOutOrderId',
        'tranIP',
        'respCode',
        'gopayServerTime'
    ];
    public $signNeedColumnsForNotify = [
        'version',
        'tranCode' ,
        'merchantID',
        'merOrderNum',
        'tranAmt',
        'feeAmt',
        'tranDateTime',
        'frontMerUrl',
        'backgroundMerUrl',
        'orderId',
        'gopayOutOrderId',
        'tranIP',
        'respCode',
        'gopayServerTime'
    ];
    public $signNeedColumnsForQuery = [
        'tranCode',
        'merchantID',
        'merOrderNum',
        'tranAmt',
        'ticketAmt',
        'tranDateTime',
        'currencyType',
        'merURL',
        'customerEMail',
        'authID',
        'orgOrderNum',
        'orgtranDateTime',
        'orgtranAmt',
        'orgTxnType',
        'orgTxnStat',
        'msgExt',
        'virCardNo',
        'virCardNoIn',
        'tranIP',
        'isLocked',
        'feeAmt',
        'respCode',
        'gopayOrderId'
    ];
    
    public function compileSign($oPaymentAccount, $aInputData, $aNeedKeys = []) {
        $aData = '';
        foreach($this->signNeedColumns as $sColumn){
            $aData .= $sColumn . '=[' . (isset($aInputData[$sColumn]) ? $aInputData[$sColumn] : '') . ']';
        }
        return md5($aData . 'VerficationCode=[' . $oPaymentAccount->safe_key . ']');
    }

    public function compileQuerySign($oPaymentAccount, $aInputData, $aNeedKeys = []) {
        $aData = '';
        foreach($this->signNeedColumnsForQuery as $sColumn){
            $aData .= $sColumn . '=[' . (isset($aInputData[$sColumn]) ? $aInputData[$sColumn] : '') . ']';
        }
//        echo $aData;
        return md5($aData . 'VerficationCode=[' . $oPaymentAccount->safe_key . ']');
    }

    public function compileSignReturn($oPaymentAccount, $aInputData){
        $aData = '';
        foreach($this->signNeedColumnsForNotify as $sColumn){
            $aData .= $sColumn . '=[' . (isset($aInputData[$sColumn]) ? $aInputData[$sColumn] : '')  . ']';
//            !isset($aInputData[$sColumn]) or $aData[$sColumn] = $sColumn . '=' . $aInputData[$sColumn];
        }
        return md5($aData . 'VerficationCode=[' . $oPaymentAccount->safe_key . ']');
    }
    
    public function & compileInputData($oPaymentPlatform, $oPaymentAccount, $oDeposit, $oBank, & $sSafeStr) {
        $data       = $aInputData = [
            'version' => '2.1',
            'charset' => 2,
            'language' => '1',
            'signType' => 1,
            'tranCode' => 8888,
            'merchantID' => $oPaymentAccount->account,
            'merOrderNum' => $oDeposit->order_no,
            'tranAmt' => $oDeposit->amount,
            'feeAmt' => '',
            'currencyType' => 156,
            'frontMerUrl' => $oPaymentPlatform->return_url,
            'backgroundMerUrl' => $oPaymentPlatform->notify_url,
            'tranDateTime'=> date("YmdHis",strtotime($oDeposit->created_at)),
            'virCardNoIn' => $oPaymentAccount->card_number,
            'tranIP' => '127.0.0.1',
            'isRepeatSubmit' => 1,
            'goodsName' => '',
            'goodsDetail' => '',
            'buyerName' => '',
            'buyerContact' => '',
            'merRemark1' => '',
            'merRemark2' => '',
//            'userType' => 1,
//            'bankCode' => $oBank->identifier
        ];
        $data['signValue'] = $sSafeStr = $this->compileSign($oPaymentAccount, $data);
//        var_dump($data);
//        exit;
        return $data;
    }

    public function & compileQueryData($oPaymentAccount, $sOrderNo, $sServiceOrderNo) {
        $oUserDeposit    = Deposit::getDepositByNo($sOrderNo);
        $data  = [
            'version'   => '1.1',
            'tranCode'  => '4020',
            'merchantID'    => $oPaymentAccount->account,
            'merOrderNum'   => microtime(true)*10000,
            'tranDateTime' => date('YmdHis'),
            'orgOrderNum' => $sOrderNo,
            'orgtranDateTime' => date("YmdHis",strtotime($oUserDeposit->created_at)),
            'orgTxnType'    => '8888',
            'orgtranAmt'    => $oUserDeposit->amount,
            'orgTxnStat'    => '0000',
            'tranIP'        => $oUserDeposit->ip,
            'tranAmt' => '',
            'currencyType' => '',
            'merURL' => '',
            'customerEMail' => '',
            'virCardNo' => '',
            'virCardNoIn' => '',
            'feeAmt' => '',
            'respCode' => '',
            'authID' => '',
            'isLocked' => '',
            'ticketAmt' => '',
            'msgExt' => '',
            'gopayOrderId' => ''
        ];
//        pr($oPaymentPlatform->toArray());
//        exit;
        $data['signValue']      = $this->compileQuerySign($oPaymentAccount, $data);
        return $data;
    }

    private function & __doQuery($oPaymentPlatform, $oPaymentAccount, & $data, $sOrderNo){
        $ch       = curl_init();
        $url = $oPaymentPlatform->getQueryUrl($oPaymentAccount);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //将数据传给变量
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); //取消身份验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        $response = curl_exec($ch); //接收返回信息
//        file_put_contents('/tmp/xs_' . $sOrderNo, $response);
        if (curl_errno($ch)) {//出错则显示错误信息
            print curl_error($ch);
        }
        curl_close($ch); //关闭curl链接
        return $response;
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
     *  -4: Unpay
     *  -5: Amount Error
     */
    public function queryFromPlatform($oPaymentPlatform, $oPaymentAccount, $sOrderNo, $sServiceOrderNo = null, & $aResponses) {
//        return false;
        $data     = $this->compileQueryData($oPaymentAccount, $sOrderNo, $sServiceOrderNo);
//        pr($data);
        $response = $this->__doQuery($oPaymentPlatform, $oPaymentAccount, $data, $sOrderNo);
//        var_dump($response);
//        exit;
        if ($response === '') {     // query failed
            return self::PAY_QUERY_FAILED;
        }
        switch($this->queryResponseFormat){
            case 'xml':
                $resParser = xml_parser_create();
                if (!xml_parse_into_struct($resParser, $response, $values, $index)) {   // parse error
                    return self::PAY_QUERY_PARSE_ERROR;
                }
//                    pr($values);
        //            pr($index);
                $aResponses = [];
                foreach ($values as $aInfo) {
                    if ($aInfo['type'] != 'complete' || !isset($aInfo['value'])) {
                        continue;
                    }
                    $aResponses[strtolower($aInfo['tag'])] = $aInfo['value'];
                }
                break;
            case 'querystring':
                parse_str($response, $aResponses);
                break;
        }
//                ErrorCode=0401&serialID=16597060557e7c1d5f119557fcb20b14d94.25137105&mode=1&type=1&beginTime=&endTime=&partnerID=10056214294&remark=query&charset=1&signType=2&signMsg=db6026b6e2bd610714478815434d4695
        
//        pr($aResponses);
        if(!isset($aResponses['respcode'])){
            return self::PAY_UNPAY;
        }
        if ($aResponses['respcode'] == '9999'){
            return self::PAY_NO_ORDER;
        }
        switch($aResponses['orgtxnstat']){
            case '20000':
                return self::PAY_SUCCESS;
            case '20001':
            case '20003':
                return self::PAY_NO_ORDER;
            case '20002':
            default:
                return self::PAY_UNPAY;
        }
//        switch ($aResponses['respcode']) {
//            case '0009':
//            case '0411':
//                return self::PAY_NO_ORDER;
////                return self::PAY_UNPAY;
//            case '0000':
//                return self::PAY_SUCCESS;
//            default:
//                return self::PAY_UNPAY;
//        }
    }


    public static function & compileCallBackData($data,$ip){
        $aData = [
            'order_no' => $data['merOrderNum'],
            'service_order_no' => $data['orderId'],
            'merchant_code' => $data['merchantID'],
            'amount' => $data['tranAmt'],
            'ip' => $ip,
            'status' => DepositCallback::STATUS_CALLED,
            'post_data' => json_encode($data),
            'callback_time' => time(),
            'callback_at' => date('Y-m-d H:i:s'),
        ];
        return $aData;
    }

    public function getPayAmount($data){
        return formatNumber($data[$this->amountColumn], 2);
    }
    
    public static function & getServiceInfoFromQueryResult(& $aResponses){
        $aResponses = array();
        return $aResponses;
        $a = explode(',', $aResponses['queryDetails']);
        $data = [
                    'service_order_no' => $a[5],
                    'service_time' => date('Y-m-d H:i:s', strtotime($a[3])),
                    'service_order_status' => $a[6],
                    'pay_time' => date('Y-m-d H:i:s', strtotime($a[4])),
        ];
        return $data;
    }
}
