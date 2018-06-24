<?php

/**
 * Ag Projects Record
 *
 * @author garin
 */
class AgProjectRecord extends BaseModel {

    protected $table = 'ag_project_record';
    public static $resourceName = 'AgProjectRecord';

    protected static $cacheUseParentClass = false;

    protected static $cacheLevel = self::CACHE_LEVEL_NONE;

    protected static $cacheMinutes = 0;

    protected $fillable = [
        'id',
        'data_type',
        'bill_no',
        'user_id',
        'username',
        'is_tester',
        'is_agent',
        'user_level',
        'agent_code',
        'game_code',
        'net_amount',
        'bet_time',
        'game_type',
        'bet_amount',
        'valid_bet_amount',
        'flag',
        'play_type',
        'currency',
        'login_ip',
        'recalcu_time',
        'platform_type',
        'remark',
        'round',
        'result',
        'before_credit',
        'device_type',
        'table_code',
        'mainbillno',
        'other_data',
        'created_at',
        'updated_at',
    ];

    public static $sequencable = false;

    public static $enabledBatchAction = false;

    protected $validatorMessages = [];

    protected $isAdmin = true;


    protected $softDelete = false;

    protected $defaultColumns = ['*'];

    protected $hidden = [];

    protected $visible = [];

    public static $treeable = '';

    public static $foreFatherIDColumn = '';

    public static $foreFatherColumn = '';

    public static $columnForList = [
        'data_type',
        'bill_no',
        'username',
        //'agent_code',
        'game_code',
        'table_code',
        'game_type',
        'play_type',
        'bet_amount',
        'net_amount',
        'bet_time',
        //'valid_bet_amount',
        'flag',
        'currency',
        'login_ip',
        'recalcu_time',
        'platform_type',
        'round',
        //'result',
        'before_credit',
        'device_type',
        'mainbillno',
        //'other_data',
        //'plat_turnovers_used',
        //'ftp_get_logs_id',
        'remark',
        'created_at',
    ];

    public static $totalColumns = [];

    public static $totalRateColumns = [];

    public static $weightFields = [];

    public static $classGradeFields = [];

    public static $floatDisplayFields = [];

    public static $noOrderByColumns = [];

    public static $ignoreColumnsInView = [
        'id',
        'user_id',
        'is_tester',
        'is_agent',
        'user_level',
    ];

    public static $ignoreColumnsInEdit = [
    ];

    public static $listColumnMaps = [
        'flag' => 'flag_formatted',
        'round' => 'round_formatted',
        'device_type' => 'device_type_formatted',
        'platform_type' => 'platform_type_formatted',
        'game_type' => 'game_type_formatted',
        'mainbillno' => 'mainbillno_formatted',
        'play_type' => 'play_type_formatted',
        'game_code' => 'game_code_formatted',
        'table_code' => 'table_code_formatted',
        'remark' => 'remark_formatted',
        'data_type' => 'data_type_formatted',

    ];

    public static $viewColumnMaps = [];

    public static $htmlSelectColumns = [];

    public static $htmlTextAreaColumns = [];

    public static $htmlNumberColumns = [];

    public static $htmlOriginalNumberColumns = [];

    public static $amountAccuracy = 0;

    public static $originalColumns;

    public $orderColumns = [
        'id' => 'desc'
    ];

    public static $titleColumn = 'data_type';
    public static $mainParamColumn = '';

    public static $rules = [

    ];

    protected function beforeValidate() {
        return parent::beforeValidate();
    }

    protected function getFlagFormattedAttribute() {
        $aFlag = Config::get('ag.bet_record_flag');
        return isset($aFlag[$this->flag]) ? $aFlag[$this->flag] : $this->flag;
    }

    protected function getDeviceTypeFormattedAttribute() {
        $aDeviceType = Config::get('ag.bet_record_device_type');
        return isset($aDeviceType[$this->device_type]) ? $aDeviceType[$this->device_type] : $this->device_type;
    }

    protected function getRoundFormattedAttribute() {
        $aRound = Config::get('ag.bet_record_round');
        return isset($aRound[$this->round]) ? $aRound[$this->round] : $this->round;
    }

    protected function getPlatformTypeFormattedAttribute() {
        $aPlatformType = Config::get('ag.bet_record_platform_type');
        return isset($aPlatformType[$this->platform_type]) ? $aPlatformType[$this->platform_type] : $this->platform_type;
    }

    protected function getGameTypeFormattedAttribute() {
        $aGameType = Config::get('ag.bet_record_game_type');
        return isset($aGameType[$this->game_type]) ? $aGameType[$this->game_type] : $this->game_type;
    }

    protected function getPlayTypeFormattedAttribute() {
        $aPlayType = Config::get('ag.bet_record_play_type');
        if (empty($this->play_type)) {
            return '无';
        } else {
            return isset($aPlayType[$this->play_type]) ? $aPlayType[$this->play_type] : $this->play_type;
        }

    }

    protected function getDataTypeFormattedAttribute() {
        $aDataType = Config::get('ag.bet_record_data_type');
        if (empty($this->data_type)) {
            return '无';
        } else {
            return isset($aDataType[$this->data_type]) ? $aDataType[$this->data_type] : $this->data_type;
        }

    }

    protected function getMainbillnoFormattedAttribute() {
        return !$this->mainbillno ? '无' : $this->mainbillno;
    }

    protected function getGameCodeFormattedAttribute() {
        return !$this->game_code ? '无' : $this->game_code;
    }

    protected function getTableCodeFormattedAttribute() {
        return !$this->table_code ? '无' : $this->table_code;
    }

    protected function getRemarkFormattedAttribute() {
        return !$this->remark ? '无' : $this->remark;
    }

    /**
     * 获取游戏记录
     *
     * @param array $aCondition
     *
     * @return mixed
     */
    public static function & getProjectRecord($aCondition = []) {
        $aProjectRecords = static::doWhere($aCondition)
            ->orderBy('created_at', 'DESC')
            ->get()
            ->toArray();
        return $aProjectRecords;

    }

    /**
     * 统计AG的盈亏信息
     *
     * @param $sDate
     * @param $aGroupBy
     * @param $bIsTest
     *
     * @return array
     */
    public static function & getAgLotteryData($sDate, $aGroupBy = [], $bIsTest = false) {
        $sDateFrom = $sDate . ' 00:00:00';
        $sDateTo = $sDate . ' 23:59:59';

        $aResults = static::whereBetween('bet_time', [$sDateFrom, $sDateTo]);

        if ($bIsTest) {
            $aResults = $aResults->where('is_tester', '=', 1);
        }

        if (empty($aGroupBy)) {
            $aGroupBy = ['user_id'];
        }

        $sSqlRaw = implode(',', $aGroupBy) . ', count(*) prj_count, sum(bet_amount) turnover, sum(net_amount) net_amount';
        $aResults = $aResults->select(DB::raw($sSqlRaw));

        foreach ($aGroupBy as $item) {
             $aResults = $aResults->groupBy($item);
        }

        $aResults = $aResults->get();

        $aData = [];
        foreach ($aResults as $obj) {
            $aData[$obj->user_id] = [
                'turnover' => $obj->turnover,
                'prize' =>  $obj->net_amount > 0 ? $obj->net_amount : 0,
                'prj_count' => $obj->prj_count,
            ];
        }

        return $aData;
    }

    /**
     * 存入游戏记录
     *
     * @param array $aRecords
     *
     * @return mixed
     */
    public static function createDataProjectRecord($aRecords = []) {
        $oAgProjectRecord = new static();
        foreach ($aRecords as $key => $v) {
            $oAgProjectRecord->{$key} = $v;
        }
        return $oAgProjectRecord->save();
        //return $oAgProjectRecord->id;
    }

}