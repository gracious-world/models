<?php

class PaymentPlatform extends BaseModel {

    protected static $cacheLevel = self::CACHE_LEVEL_FIRST;

    const STATUS_NOT_AVAILABLE = 0;
    const STATUS_AVAILABLE_FOR_TESTER = 1;
    const STATUS_AVAILABLE_FOR_NORMAL_USER = 2;
    const STATUS_AVAILABLE = 3;
    const TYPE_BANK = 1;
    const TYPE_THIRD = 2;

    /**
     * 支付平台实例
     * @var object
     */
    public $platform;
    protected $table = 'payment_platforms';
    public static $resourceName = 'PaymentPlatform';
    public static $validStatus = [
        self::STATUS_NOT_AVAILABLE => 'Closed',
        self::STATUS_AVAILABLE_FOR_TESTER => 'Testing',
//        self::STATUS_AVAILABLE_FOR_NORMAL_USER => 'Available',
        self::STATUS_AVAILABLE => 'Available'
    ];
    //增加扫码的支付类型id
    public static $qrValidTypes = [2, 3, 5];
    public static $validTypes = [
        self::TYPE_BANK => 'type-bank',
        self::TYPE_THIRD => 'type-third'
    ];
    public static $htmlSelectColumns = [
        'status' => 'aValidStatus',
        'type' => 'aValidTypes',
        'payment_type_id' => 'aPaymentTypes'
    ];
    public static $columnForList = [
        'id',
        'name',
        'display_name',
        'web',
        'payment_type_id',
        'type',
        'status',
        'query_enabled',
        'is_default',
        'auto_query_enabled',
        'sequence',
    ];
    public static $listColumnMaps = [
        'is_default' => 'is_default_formatted',
        'query_enabled' => 'query_enabled_formatted',
        'status' => 'status_formatted',
        'payment_type_id' => 'payment_type_id_formatted',
    ];
    public static $viewColumnMaps = [
        'payment_type_id' => 'payment_type_id_formatted',
    ];
    protected $fillable = [
        'identifier',
        'name',
        'display_name',
        'web',
        'ip',
        'load_url',
        'test_load_url',
        'charset',
        'return_url',
        'notify_url',
        'unload_url',
        'query_enabled',
        'query_url',
        'relay_load_url',
        'relay_query_url',
        'check_ip',
        'query_on_callback',
        'need_bank',
        'status',
        'type',
        'is_default',
        'auto_query_enabled',
        'notice',
        'payer_name_enabled',
        'everyday_start_time',
        'everyday_end_time',
        'deposit_max_amount',
        'deposit_min_amount',
        'payment_type_id',
        //新增注册天数规则,存款金额规则,存款次数规则,提款次数规则,
        'register_day_rules',
        'deposit_amount_rules',
        'deposit_times_rules',
        'withdrawal_times_rules',
    ];
    public static $rules             = [
        'identifier'          => 'required|max:16',
        'name'                => 'required|max:50',
        'display_name'        => 'required|max:50',
        'is_default'          => 'required|integer|in:0,1',
        'web'                 => 'max:200',
        'ip'                  => 'max:200',
        'relay_load_url'      => 'max:200',
        'load_url'            => 'max:200',
        'test_load_url'       => 'max:200',
        'return_url'          => 'required|max:200',
        'notify_url'          => 'required|max:200',
        'relay_query_url'     => 'max:200',
        'charset'             => 'max:10',
        'unload_url'          => 'max:200',
        'query_url'           => 'max:200',
        'check_ip'            => 'integer|in:0,1',
        'query_on_callback'   => 'integer|in:0,1',
        'query_enabled'       => 'integer|in:0,1',
        'need_bank'           => 'integer|in:0,1',
        'status'              => 'integer|in:0,1,2,3,4',
        'type'                => 'required|integer',
        'payment_type_id'     => 'required|integer',
        'notice'              => '',
        'payer_name_enabled'  => 'integer|in:0,1',
        'auto_query_enabled'  => 'integer|in:0,1',
        'everyday_start_time' => 'min:0',
        'everyday_end_time'   => 'min:0',
        'deposit_max_amount'  => 'numeric',
        'deposit_min_amount'  => 'numeric',
        'register_day_rules'  => 'integer',
        'deposit_amount_rules'=> 'numeric',
        'deposit_times_rules' => 'integer',
        'withdrawal_times_rules' => 'integer',
    ];
    public static $sequencable = true;
    public $orderColumns = [
        'sequence' => 'asc',
        'name' => 'asc',
    ];
    public static $mainParamColumn = 'name';
    public static $titleColumn = 'name';

    protected function beforeValidate() {
        $this->everyday_start_time or $this->everyday_start_time = null;
        $this->everyday_end_time or $this->everyday_end_time = null;
        return parent::beforeValidate();
    }

    public static function getObject($sIdentifier) {
        $oOriginPayment = static::where('identifier', '=', $sIdentifier)->first();
        $sClass = 'Payment' . strtoupper($sIdentifier);
        $oOriginPayment->platform = new $sClass;
        return $oOriginPayment;
    }

    public function getLoadUrl($oPaymentAccount) {
        return $oPaymentAccount->relay_load_url ? $oPaymentAccount->relay_load_url : $this->load_url;
    }

    public function getQueryUrl($oPaymentAccount) {
        return $oPaymentAccount->relay_query_url ? $oPaymentAccount->relay_query_url : $this->query_url;
    }

    public function & compileInputData($oPaymentAccount, $oDeposit, $oBank, & $sSafeStr) {
        return $this->platform->compileInputData($this, $oPaymentAccount, $oDeposit, $oBank, $sSafeStr);
    }

    public function compileSign($oPaymentAccount, $aInputData, $aNeedKeys = []) {
        return $this->platform->compileSign($oPaymentAccount, $aInputData, $aNeedKeys);
    }

    public function compileSignReturn($oPaymentAccount, $aInputData, $aNeedKeys = []) {
        return $this->platform->compileSignReturn($oPaymentAccount, $aInputData, $aNeedKeys);
    }

    public function queryFromPlatform($oPaymentAccount, $sOrderNo, $sServiceOrderNo = null, & $aResonses) {
        return $this->platform->queryFromPlatform($this, $oPaymentAccount, $sOrderNo, $sServiceOrderNo, $aResonses);
    }

    public function qrCode($aInputData, $sRealUrl, $oPaymentAccount) {
        return $this->platform->qrCode($aInputData, $sRealUrl, $oPaymentAccount);
    }

    public function getSuffix() {
        return $this->platform->getSuffix();
    }

    private static function _getStatusArray($iNeedStatus) {
        $aStatus = [];
        foreach (static::$validStatus as $iStatus => $sTmp) {
            if (($iStatus & $iNeedStatus) == $iNeedStatus) {
                $aStatus[] = $iStatus;
            }
        }
        return $aStatus;
    }

    public static function getAvailabelPlatforms($iStatus, $aAuthIds = [], $iPaymentTypeId = null) {
        $aStatus = self::_getStatusArray($iStatus);
        $oQuery = static::whereIn('status', $aStatus);
        $oQuery = count($aAuthIds) ? $oQuery->whereIn('id', $aAuthIds) : $oQuery;
        !$iPaymentTypeId or $oQuery = $oQuery->where('payment_type_id', $iPaymentTypeId);
        $availabelPlatforms = $oQuery->orderBy('sequence')->get();
        //得到当前的用户信息
        $iUserId = Session::get('user_id');
        $oUser = User::find($iUserId);
        $iRegisterDays = Date::between($oUser->created_at, date('Y-m-d H:i:s'));
        //得到用户的充值总额
        $aInfo = UserProfit::getUserDepositAndWithdrawInfo($iUserId);

        foreach ($availabelPlatforms as $k => $platform) {

            if (!($bWhite = $oUser->isDepositWhiteMember())) {
                //增加注册天数检查
                if ($iRegisterDays < $platform->register_day_rules) {
                    unset($availabelPlatforms[$k]);
                }
                //增加充值金额检查
                if (formatNumber($aInfo['total_deposit'], 2) < $platform->deposit_amount_rules) {
                    unset($availabelPlatforms[$k]);
                }
                //增加充值次数检查
                if ($aInfo['total_deposit_times'] < $platform->deposit_times_rules) {
                    unset($availabelPlatforms[$k]);
                }
                //增加存款次数检查
                if ($aInfo['total_withdraw_times'] < $platform->withdrawal_times_rules) {
                    unset($availabelPlatforms[$k]);
                }
            }

            if (empty($platform->everyday_start_time) || empty($platform->everyday_end_time)) {
                continue;
            }
            $nowTime = date('H:i:s');
            $start_time = date('H:i:s', strtotime($platform->everyday_start_time));
            $end_time = date('H:i:s', strtotime($platform->everyday_end_time));
            if ($start_time == '00:00:00' || $end_time == '00:00:00') {
                continue;
            }
            if ($start_time > $nowTime || $end_time < $nowTime) {
                unset($availabelPlatforms[$k]);
            }
        }
        return $availabelPlatforms;
    }

    protected function getAvailableIpAttribute() {
        return explode(',', $this->attributes['ip']);
    }

    public function setDefault() {

        $aData = [
            'is_default' => 1,
            'status' => self::STATUS_AVAILABLE,
        ];
        $bSucc = static::where('id', '=', $this->id)->update($aData) && static::where('id', '<>', $this->id)->update(['is_default' => 0]);

        if ($bSucc) {
            static::deleteDefaultPlatformIdCache();
            static::deleteAllCache();
        }
        return $bSucc;
    }

    public function setDefaultPlatformIdCache() {
        $key = static::comaileDefaultPlatformIdCacheKey();
        Cache::setDefaultDriver(static::$cacheDrivers[static::$cacheLevel]);
        Cache::forever($key, $this->id);
        return true;
    }

    public static function getDefaultPlatformId() {
        $bWriteCache = $bReadDb = false;
        if (static::$cacheLevel == self::CACHE_LEVEL_NONE) {
            $bReadDb = true;
        } else {
            Cache::setDefaultDriver(static::$cacheDrivers[static::$cacheLevel]);
            $key = static::comaileDefaultPlatformIdCacheKey();
            if (!Cache::has($key)) {
                $bReadDb = true;
                $bWriteCache = true;
            } else {
                $iDefaultId = Cache::get($key);
//                $oPlatform = static::find($iDefaultId);
            }
        }
        if ($bReadDb) {
            $iDefaultId = static::where('is_default', '=', 1)->pluck('id');
        }
        !$bWriteCache or Cache::forever($key, $iDefaultId);
        return $iDefaultId;
    }

    public static function deleteDefaultPlatformIdCache() {
        $key = static::comaileDefaultPlatformIdCacheKey();
        Cache::setDefaultDriver(static::$cacheDrivers[static::$cacheLevel]);
        Cache::has($key) && Cache::forget($key);
    }

    public static function getDefaultPlatform() {
        return static::find(static::getDefaultPlatformId());
    }

    private static function comaileDefaultPlatformIdCacheKey() {
        return 'default-payment-platform-id';
    }

    protected function afterSave($oSavedModel) {
        static::deleteDefaultPlatformIdCache();
        if ($this->is_default == 1) {
            static::where('id', '<>', $this->id)->where('is_default', '=', 1)->update(['is_default' => 0]);
        }
        $this->deleteAllCache();
        $this->deleteDefaultPlatformIdCache();
        return parent::afterSave($oSavedModel);
    }

    protected function getIsDefaultFormattedAttribute() {
        return __('_basic.' . Config::get('var.boolean')[$this->attributes['is_default']]);
    }

    protected function getQueryEnabledFormattedAttribute() {
        return __('_basic.' . Config::get('var.boolean')[$this->attributes['query_enabled']]);
    }

    protected function getStatusFormattedAttribute() {
        return __('_paymentplatform.' . static::$validStatus[$this->attributes['status']]);
    }

    protected function getPaymentTypeIdFormattedAttribute() {
        $oPaymentType = PaymentType::find($this->attributes['payment_type_id']);
        return is_object($oPaymentType) ? $oPaymentType->name : '';
    }

    public function addCallBackHistory(& $data, $ip) {
        $aData = $this->platform->compileCallBackData($data, $ip);
        $aData['platform_id'] = $this->id;
        $aData['platform'] = $this->name;
        $aData['platform_identifier'] = $this->identifier;
        $oDepositCallback = new DepositCallback($aData);
        if ($oDepositCallback->save()) {
            return $oDepositCallback;
        } else {
            file_put_contents('/tmp/deposit-callback-error', $oDepositCallback->getValidationErrorString());
        }
        return false;
//        $aData = static::compileCallBackData($data, $ip);
//        $oDepositCallback = new DepositCallback($aData);
//        return $oDepositCallback->save();
    }

    public static function deleteAllCache() {
        if (static::$cacheLevel == self::CACHE_LEVEL_NONE) {
            return true;
        }
        Cache::setDefaultDriver(static::$cacheDrivers[static::$cacheLevel]);
        $oPlatforms = static::all();
        foreach ($oPlatforms as $oPlatform) {
            static::deletecache($oPlatform->id);
        }
        return true;
    }

    public static function getValidTypes() {
        return static::_getArrayAttributes(__FUNCTION__);
    }

    /*     * 设置支付渠道为关闭
     * @author zero
     * @param array $aExtra
     * @return bool
     */

    public function setToClosed($aExtra = []) {

        $iCurrentStatus = $this->status;
        if ($iCurrentStatus == self::STATUS_NOT_AVAILABLE) {
            return true;
        }
        return $this->setStatus(self::STATUS_NOT_AVAILABLE, $iCurrentStatus, $aExtra);
    }

    /*     * 设置支付渠道为打开
     * @author zero
     * @param array $aExtra
     * @return bool
     */

    public function setToOpened($aExtra = []) {

        $iCurrentStatus = $this->status;
        if ($iCurrentStatus == self::STATUS_AVAILABLE) {
            return true;
        }
        return $this->setStatus(self::STATUS_AVAILABLE, $iCurrentStatus, $aExtra);
    }

    /*     * 设置支付渠道为测试中
     * @author zero
     * @param array $aExtra
     * @return bool
     */

    public function setToTesting($aExtra = []) {

        $iCurrentStatus = $this->status;
        if ($iCurrentStatus == self::STATUS_AVAILABLE_FOR_TESTER) {
            return true;
        }
        return $this->setStatus(self::STATUS_AVAILABLE_FOR_TESTER, $iCurrentStatus, $aExtra);
    }

    /** 设置状态函数
     * @author zero
     * @param $iToStatus
     * @param $iFromStatus
     * @param array $aExtraData
     * @return bool
     */
    protected function setStatus($iToStatus, $iFromStatus, $aExtraData = []) {
        $aConditions = [
            'id' => ['=', $this->id],
            'status' => ['=', $iFromStatus],
        ];
        $data = [
            'status' => $iToStatus
        ];
        $data = array_merge($data, $aExtraData);
        return $this->strictUpdate($aConditions, $data) > 0;
    }

    /**
     * 获取符合限定规则渠道对象
     * @author zero
     * @param $iId
     * @return mixed
     */
    public static function getAllowedPaymentById($iId) {
        //得到当前的用户信息
        $iUserId = Session::get('user_id');
        $oUser = User::find($iUserId);
        //得到用户的充值总额
        $aInfo = UserProfit::getUserDepositAndWithdrawInfo($iUserId);
        //得到用户的注册天数
        $iRegisterDays = Date::between($oUser->created_at, date('Y-m-d H:i:s'));

        $oQuery = static::where('id', $iId);
        if (!($bWhite = $oUser->isDepositWhiteMember())) {
            $oQuery = $oQuery->where('register_day_rules', '<=', $iRegisterDays)
                    ->where('deposit_amount_rules', '<=', formatNumber($aInfo['total_deposit'], 2))
                    ->where('deposit_times_rules', '<=', $aInfo['total_deposit_times'])
                    ->where('withdrawal_times_rules', '<=', $aInfo['total_withdraw_times']);
        }
        $oPayment = $oQuery->first();

        return $oPayment;
    }

    public function getHtmlFormSubmitMethod() {
        return $this->platform->getHtmlFormSubmitMethod();
    }

    public function isNeedDirect() {
        return $this->platform->isNeedDirect();
    }

}
