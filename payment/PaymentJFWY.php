<?php
/**
 * Created by PhpStorm.
 * User: mac
 * Date: 9/13/18
 * Time: 11:46
 */

class PaymentJFWY extends PaymentJFZFBWAP
{

    public $cardType =1;//借记卡

    public $userType = 1;//个人
    public $channel = 1; // 1 pc ; 2 mobile
    public $bankSegment = 1002;//ABC


    public $signNeedColumns=[
        'version',
            'spid',
            'spbillno',
            'tranAmt',
            'cardType',
            'channel',
            'userType',
            'bankSegment',
            'backUrl',
            'notifyUrl',
            'productName',
            'productDesc'
    ];
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
            'cardType' => $this->cardType,
            'channel' => $this->channel,
            'userType' => $this->userType,
            'bankSegment'=>$oBank ? $oBank->identifier : $this->bankCode,
            'backUrl' => $oPaymentPlatform->return_url,
            'notifyUrl' => $oPaymentPlatform->notify_url,
            'productName' => 'hz',
            'productDesc' => 'hz deposits',
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

        return $req_data;
    }

}