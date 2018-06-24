<?php

/**
 * 聚鑫微信平台
 *
 * @author zero
 */
class PaymentJUXINWX extends PaymentJUXIN {

	public $productId = "0101";
	protected $paymentName= 'jxwx';
	public $saveQr                    = true;
    public $qrDirName = 'juxinwx';

    //交易签名所需字段
    public $signNeedColumns = [
        'requestNo',
        'productId',
        'transId',
        'merNo',
        'orderNo',
        'transAmt',
    ];

	/**
	 * 生成二维码
	 * @author lucky
	 * @date  2017-07-25
	 * @param $aInputData
	 * @param $sResponse
	 * @param $oPaymentAccount
	 *
	 * @return string
	 */
	public function processQr($aInputData, $sResponse, $oPaymentAccount) {
        $sWxPngPath = '';
        $res        = json_decode($sResponse);
        $resp_code  = $res->respCode;
        if ($resp_code == "Z000") {
	        @chmod($this->qrCodePath, 0777);
            $qrcode             = $res->codeUrl;
            $sQrcodeNoLogoPath  = $this->qrCodePath . $aInputData['orderNo'] . 'qrcode.png';
            $sQrocdeHasLogoPath = $this->qrCodePath . $aInputData['orderNo'] . 'output.png';
            if (file_exists($sQrcodeNoLogoPath) or file_exists($sQrocdeHasLogoPath)) {
                unlink($sQrcodeNoLogoPath);
                unlink($sQrocdeHasLogoPath);
            }
	        
	        @chmod($sQrocdeHasLogoPath, 0777);
	        @chmod($sQrcodeNoLogoPath,0777);
	        
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
            $sWxPngPath = $this->qrVisitPath . $aInputData['orderNo'] . 'output.png';
        }
        return $sWxPngPath;
    }

}
