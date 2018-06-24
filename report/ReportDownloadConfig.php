<?php

/**
 *  下载报表配置信息
 */
class ReportDownloadConfig extends BaseModel {

    const TYPE_TRANSACTION            = 0;
    const TYPE_TRANSACTION_DEPOSIT    = 1;
    const TYPE_TRANSACTION_WITHDRAWAL = 2;
    const TYPE_DEPOSIT                = 100;
    const TYPE_DEPOSIT_THE_THIRD_PART = 101;
    const TYPE_WITHDRAWAL             = 110;
    const TYPE_WITHDRAWAL_SUCCESS     = 111;
    const TYPE_ACCOUNT                = 120;
    const FREQ_TYPE_CUSTOM     = 0;
    const FREQ_TYPE_EVERYDAY   = 1;
    const FREQ_TYPE_EVERYWEEK  = 2;
    const FREQ_TYPE_EVERYMONTH = 3;
    const FREQ_TYPE_EVERYYEAR  = 4;

    protected $table                 = 'report_download_configs';
    public static $resourceName      = 'ReportDownloadConfig';
    public static $columnForList     = [
        'class_name',
        'report_type',
        'freq_type',
        'begin_time',
        'end_time',
        'is_enabled',
        'created_at',
    ];
    protected $fillable              = [
        'class_name',
        'report_type',
        'freq_type',
        'begin_time',
        'end_time',
        'is_enabled',
    ];
    public static $rules             = [
        'class_name'  => 'required|between:1,64',
        'report_type' => 'required|integer',
        'freq_type'   => 'integer',
        'begin_time'  => 'date',
        'end_time'    => 'date',
        'is_enabled'  => 'in:0,1',
    ];
    public static $aFreqTypes        = [
        self::FREQ_TYPE_CUSTOM     => 'custom',
        self::FREQ_TYPE_EVERYDAY   => 'every-day',
        self::FREQ_TYPE_EVERYWEEK  => 'every-week',
        self::FREQ_TYPE_EVERYMONTH => 'every-month',
        self::FREQ_TYPE_EVERYYEAR  => 'every-year',
    ];
    public static $aReportType       = [
        self::TYPE_TRANSACTION            => 'transaction',
        self::TYPE_TRANSACTION_DEPOSIT    => 'transaction-deposit',
        self::TYPE_TRANSACTION_WITHDRAWAL => 'transaction-withdrawal',
        self::TYPE_WITHDRAWAL             => 'withdrawal',
        self::TYPE_WITHDRAWAL_SUCCESS     => 'withdrawal-success',
        self::TYPE_DEPOSIT                => 'deposit',
        self::TYPE_ACCOUNT                => 'account',
        self::TYPE_DEPOSIT_THE_THIRD_PART => 'deposit-third-part',
    ];
    public static $htmlSelectColumns = [
        'freq_type'   => 'aFreqTypes',
        'report_type' => 'aReportType',
    ];
    public static $listColumnMaps    = [
        // 'account_available' => 'account_available_formatted',
        'report_type' => 'report_type_formatted',
        'freq_type'   => 'freq_type_formatted',
    ];

    /**
     * 获取可用的下载配置内容
     */
    public static function getEnableConfig() {
        return static::where('is_enabled', '=', 1)->get();
    }

    protected function getReportTypeFormattedAttribute() {
        return __('_reportdownloadconfig.' . static::$aReportType[$this->report_type]);
    }

    protected function getFreqTypeFormattedAttribute() {
        return __('_reportdownloadconfig.' . static::$aFreqTypes[$this->freq_type]);
    }

}
