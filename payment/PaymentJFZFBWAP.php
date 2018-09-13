<?php

/**
 * jian fu
 *
 * @author sad
 *
 */
class PaymentJFZFBWAP extends BasePlatform
{

    public $successMsg = 'success';
    public $signColumn = 'sign';
    public $accountColumn = 'spid';
    public $orderNoColumn = 'spbillno';
    public $paymentOrderNoColumn = 'transaction_id';
    public $successColumn = 'retcode';
    public $successValue = 0;
    public $amountColumn = 'tran_amt';
//	public $bankNoColumn           = 'bankCode';
//    public $serviceOrderTimeColumn = 'endtime';

    public $payType = 'pay.alipay.wap';
    public $version = '1.0';

//    public $bankTimeColumn = "paytime";

    protected $paymentName = 'jfzfbwap';

    public $serviceOrderNo;
    //交易签名所需字段
    public $signNeedColumns = [
        'version',
        'spid',
        'spbillno',
        'tranAmt',
        'payType',
        'backUrl',
        'notifyUrl',
        'productName',
    ];

    public $querySignNeedColumns = [
        'version',
        'spid',
        'transaction_id',
    ];

    public $queryResultSignNeedColumns = [
        'retcode',
        'retmsg',
        'spid',
        'spbillno',
        'transaction_id',
        'out_transaction_id',
        'tran_amt',
        'result',
    ];
    //通知签名字段
    public $notifySignNeedColumns = [
        'retcode',
        'retmsg',
        'spid',
        'spbillno',
        'transaction_id',
        'out_transaction_id',
        'tran_amt',
        'result',
    ];

    protected function signStr($aInputData, $aNeedColumns = [])
    {
        $sSignStr = '';
        if (!$aNeedColumns) {
            $aNeedColumns = array_keys($aInputData);
        }
        sort($aNeedColumns);
//        var_dump($aNeedColumns);exit;
        foreach ($aNeedColumns as $sColumn) {
            if (isset($aInputData[$sColumn]) && $aInputData[$sColumn] != '') {
                $sSignStr .= $sColumn . '=' .$aInputData[$sColumn] . '&';
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

        $sSignStr = $this->signStr($aInputData, $aNeedKeys);
        $sSignStr = trim($sSignStr, '&');
        $sSignStr .= '&key='.$oPaymentAccount->safe_key;
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
        $this->serviceOrderNo = $aInputData['transaction_id'];
        return $this->compileSign($oPaymentAccount, $aInputData, $this->notifySignNeedColumns);
    }

    /**
     * query sign
     * notice difference with input sign
     * @see compileSign
     *
     * @param $oPaymentAccount
     * @param $aInputData
     * @param array $aNeedKeys
     * @return string
     */
    public function compileQuerySign($oPaymentAccount, $aInputData )
    {
        return $this->compileSign($oPaymentAccount, $aInputData, $this->querySignNeedColumns);
    }

    /**
     * query result sign
     * @param $oPaymentAccount
     * @param $aInputData
     * @return string
     */
    public function & compileQueryResultSign($oPaymentAccount,$aInputData){
        $sSign = $this->compileSign($oPaymentAccount,$aInputData,$this->queryResultSignNeedColumns);
        return $sSign;
    }

    /**
     * 充值请求表单数据组建
     *
     * @param $oPaymentPlatform
     * @param $oPaymentAccount
     * @param $oDeposit
     * @param $oBank
     * @param $sSafeStr
     *
     * @return array
     */
    public function & compileInputData($oPaymentPlatform, $oPaymentAccount, $oDeposit, $oBank, & $sSafeStr)
    {
        $aData = [
            'version' => $this->version,
            'spid' => $oPaymentAccount->account,
            'spbillno' => $oDeposit->order_no,
            'tranAmt' => $oDeposit->amount*100,
            'payType' => $this->payType,
            'backUrl' => $oPaymentPlatform->return_url,
            'notifyUrl' => $oPaymentPlatform->notify_url,
            'productName' => 'hz',
        ];

        ksort($aData);
        $aData['sign'] =  $this->compileSign($oPaymentAccount, $aData, $this->signNeedColumns);
        $req_data='<xml>';
        foreach($aData as $k=>$v){
            $req_data.="<$k>$v</$k>";
        }
        $req_data.='</xml>';
//        var_dump(__FILE__,__LINE__,$req_data);exit;
//        array_shift($aData);
        $data = ['req_data' => $req_data];
        return $data;
    }

    /**
     * 查询签名组建
     *
     * @param $oPaymentAccount
     * @param $sOrderNo
     * @param $sServiceOrderNo
     *
     */
    public function & compileQueryData($oPaymentAccount, $sOrderNo, $sServiceOrderNo)
    {
        $aData = [
            'version' => $this->version,
            'spid' => $oPaymentAccount->account,
            'transaction_id' => $sServiceOrderNo,
        ];
        ksort($aData);
        $aData['sign'] = $this->compileQuerySign($oPaymentAccount, $aData, $this->querySignNeedColumns);

        $req_data='<xml>';
        foreach($aData as $k=>$v){
            $req_data.="<$k>$v</$k>";
        }
        $req_data.='</xml>';
        $data = ['req_data'=>$req_data];
        return $data;
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
        $sServiceOrderNo = $this->serviceOrderNo;
        $sDataQuery = $this->compileQueryData($oPaymentAccount, $sOrderNo, $sServiceOrderNo);
        $ch = curl_init();
//        $url='http://www.my6688.com/test';
        $url=$oPaymentPlatform->getQueryUrl($oPaymentAccount);
//        var_dump($url);exit;
        curl_setopt($ch, CURLOPT_URL, $oPaymentPlatform->getQueryUrl($oPaymentAccount));
//        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //将数据传给变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, $sDataQuery);
//        curl_setopt($ch,CURLOPT_HEADER,
//            [
//                "Cache-Control: no-cache",
//                "Content-Type: text/xml"
//            ]
//        );
        $sResponse = curl_exec($ch); //接收返回信息
        file_put_contents('/tmp/' . $this->paymentName . '_' . $sOrderNo, $sResponse . "\n", FILE_APPEND);
        if (curl_errno($ch)) {//出错则显示错误信息
            print curl_error($ch);
        }

        curl_close($ch); //关闭curl链接
        $aResponses = (array)simplexml_load_string($sResponse);
        //返回格式不对
        if (!$aResponses || !isset($aResponses['retcode'])) {
            return self::PAY_QUERY_PARSE_ERROR;
        }
        if ($aResponses['retcode'] != 0) {
            //支付返回成功校验签名
            return self::PAY_NO_ORDER;
        }

        $sSign = $this->compileQueryResultSign($oPaymentAccount,$aResponses);
        $bSucc = $sSign == $aResponses['sign'];
        if(!$bSucc){
            return self::PAY_SIGN_ERROR;
        }

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
        $oDeposit = Deposit::getDepositByNo($aBackData['spbillno']);
        $aData = [
            'order_no' => $aBackData['spbillno'],
            'service_order_no' => $oDeposit ? date('YmdHis', strtotime($oDeposit->created_at)) : $aBackData['transaction_id'],
            'merchant_code' => $aBackData['spid'],
            'amount' => $aBackData['tran_amt']/100,
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
            'service_order_no' => $aResponses['transaction_id'],
            'order_no' => $aResponses['spbillno'],
        ];
        return $data;
    }

    /**
     * 从数组中取得金额
     *
     * @param array $data
     *
     * @return float
     */
    public function getPayAmount($data) {
        return $data[$this->amountColumn]/100;
    }
}
