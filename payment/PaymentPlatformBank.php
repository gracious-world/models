<?php

/**
 * 支付渠道支持的银行模型
 *
 * @author Winter
 */
class PaymentPlatformBank extends BaseModel {

    protected $table = 'payment_platform_banks';
    public static $resourceName = 'PaymentPlatformBank';
    protected $fillable = [
        'id',
        'platform_name',
        'platform_id',
        'platform_identifier',
        'bank_id',
        'bank_name',
        'bank_identifier',
        'identifier',
        'enabled',
        'created_at',
        'updated_at',
    ];
    public static $columnForList = [
        'platform_name',
        'bank_name',
        'bank_identifier',
        'identifier',
        'enabled',
        'created_at',
        'updated_at',
    ];
    public static $htmlSelectColumns = [
        'bank_id' => 'aBanks',
        'platform_id' => 'aPlatforms',
//        'enabled' =
    ];
    public $orderColumns = [
        'updated_at' => 'asc'
    ];
    public static $rules = [
        'bank_id' => 'required|integer',
        'platform_id' => 'required|integer',
        'identifier' => 'required|max:16',
        'enabled' => 'required|in:0,1'
    ];
    public static $titleColumn = 'name';
    public static $mainParamColumn = 'platform_id';

    protected function beforeValidate() {
        $oPaymentPlatform = PaymentPlatform::find($this->platform_id);
        if (is_object($oPaymentPlatform)) {
            $this->platform_name = $oPaymentPlatform->name;
            $this->platform_identifier = $oPaymentPlatform->identifier;
        } else {
            return false;
        }
        $oBank = Bank::find($this->bank_id);
        if (is_object($oPaymentPlatform)) {
            $this->bank_name = $oBank->name;
            $this->bank_identifier = $oBank->identifier;
            $this->identifier or $this->identifier = $this->bank_identifier;
        } else {
            return false;
        }
        return parent::beforeValidate();
    }

    public static function getBanks($iPlatformId){
        return static::where('platform_id','=',$iPlatformId)->where('enabled','=',1)->get();
    }
}
