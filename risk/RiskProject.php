<?php

/**
 * 风险注单
 *
 * @author system
 */
class RiskProject extends BaseModel {

    protected $table = 'risk_projects';
    protected static $cacheUseParentClass = false;
    protected static $cacheLevel = self::CACHE_LEVEL_FIRST;
    protected static $cacheMinutes = 0;
    protected $fillable = [
        'project_id',
        'terminal_id',
        'serial_number',
        'trace_id',
        'user_id',
        'username',
        'is_tester',
        'user_forefather_ids',
        'account_id',
        'prize_group',
        'lottery_id',
        'issue',
        'end_time',
        'way_id',
        'title',
        'position',
        'bet_number',
        'way_total_count',
        'single_count',
        'bet_rate',
        'display_bet_number',
        'multiple',
        'coefficient',
        'single_amount',
        'amount',
        'winning_number',
        'prize',
        'prize_sale_rate',
        'status',
        'prize_set',
        'single_won_count',
        'won_count',
        'won_data',
        'ip',
        'proxy_ip',
        'bet_record_id',
        'bought_at',
        'counted_at',
        'bought_time',
        'bet_commit_time',
        'counted_time',
        'refuse_reason',
//        'created_at',
//        'updated_at',
    ];
    public static $sequencable = false;
    public static $enabledBatchAction = false;
    protected $validatorMessages = [];
    protected $isAdmin = true;
    public static $resourceName = 'RiskProject';
    protected $softDelete = false;
    protected $defaultColumns = [ '*'];
    protected $hidden = [];
    protected $visible = [];
    public static $treeable = '';
    public static $foreFatherIDColumn = '';
    public static $foreFatherColumn = '';
    public static $columnForList = [
        'id',
        'serial_number',
        'trace_id',
        'username',
        'multiple',
        'lottery_id',
        'issue',
        'end_time',
        'title',
        'display_bet_number',
        'coefficient',
        'amount',
        'prize',
        'prize_sale_rate',
        'bought_at',
        'ip',
        'status',
    ];
    public static $totalColumns = [];
    public static $totalRateColumns = [];
    public static $weightFields = [];
    public static $classGradeFields = [];
    public static $floatDisplayFields = [];
    public static $noOrderByColumns = [];
    public static $ignoreColumnsInView = [
    ];
    public static $ignoreColumnsInEdit = [
    ];
    public static $listColumnMaps = [
        'serial_number' => 'serial_number_short',
        'status' => 'formatted_status',
        'prize' => 'prize_formatted',
        'display_bet_number' => 'display_bet_number_short',
        'status_prize' => 'status_prize_formatted',
        'status_commission' => 'status_commission_formatted',
        'is_tester' => 'formatted_is_tester',
        'end_time' => 'friendly_end_time',
        'bought_at' => 'friendly_bought_at',
        'coefficient' => 'formatted_coefficient',
    ];
    public static $viewColumnMaps = [
        'status' => 'formatted_status',
        'prize' => 'prize_formatted',
        'prize_set' => 'prize_set_formatted',
        'status_prize' => 'status_prize_formatted',
        'status_commission' => 'status_commission_formatted',
        'display_bet_number' => 'display_bet_number_for_view',
        'is_tester' => 'formatted_is_tester',
        'bet_rate' => 'bet_rate_formatted',
        'bet_commit_time' => 'bet_commit_time_formatted',
        'coefficient' => 'formatted_coefficient',
        'end_time' => 'end_time_formatted',
    ];
    public static $htmlSelectColumns = [
        'lottery_id'  => 'aLotteries',
        'status'      => 'aValidStatuses',
        'terminal_id' => 'aTerminals',
    ];
    public static $htmlTextAreaColumns = [];
    public static $htmlNumberColumns = [
        'amount' => 4,
        'prize'  => 6
    ];
    public static $htmlOriginalNumberColumns = [
        'won_count',
        'single_won_count'
    ];
    public static $amountAccuracy = 0;
    public static $originalColumns;
    public $orderColumns = [
        'id' => 'desc'
    ];
    public static $titleColumn = 'serial_number';
    public static $mainParamColumn = 'project_id';
    public static $rules = [
        'project_id'          => 'required|integer|min:0',
        'terminal_id'         => 'required|integer|min:1',
        'serial_number'       => 'required|max:32',
        'trace_id'            => 'integer|min:0',
        'user_id'             => 'required|min:0',
        'username'            => 'required|max:32',
        'is_tester'           => 'integer',
        'user_forefather_ids' => 'max:1024',
        'account_id'          => 'required|min:0',
        'prize_group'         => 'required|max:20',
        'lottery_id'          => 'required|integer|min:0',
        'issue'               => 'required|max:15',
        'end_time'            => 'required|min:0',
        'way_id'              => 'required|min:0',
        'title'               => 'required|max:100',
//        'position'            => 'required|max:10',
        'bet_number'          => 'required',
        'way_total_count'     => 'required|integer|min:0',
        'single_count'        => 'required|min:0',
        'bet_rate'            => 'required|numeric|min:0',
        'display_bet_number'  => 'required',
        'multiple'            => 'required|min:0',
        'coefficient'         => 'required|numeric|min:0',
        'single_amount'       => 'required|numeric|min:0',
        'amount'              => 'required|numeric|min:0',
        'winning_number'      => 'required|max:60',
        'prize'               => 'required|numeric|min:0',
        'prize_sale_rate'     => 'required|numeric|min:0',
        'status'              => 'required|integer|min:0',
        'prize_set'           => 'required|max:1024',
        'single_won_count'    => 'required|min:0',
        'won_count'           => 'required|min:0',
        'won_data'            => 'required|max:10240',
        'ip'                  => 'required|max:15',
        'proxy_ip'            => 'required|max:15',
        'bet_record_id'       => 'required|integer|min:0',
        'bought_at'           => 'required',
        'counted_at'          => 'required',
        'bought_time'         => 'required|min:0',
//        'bet_commit_time'     => 'required|min:0',
//        'counted_time'        => 'required|min:0',
    ];

    const STATUS_NORMAL = 0;
    const STATUS_AUDITED = 1;
    const STATUS_RISKED = 2;
    public static $validStatuses      = [
        self::STATUS_NORMAL           => 'status-waiting',
        self::STATUS_AUDITED           => 'status-audited',
        self::STATUS_RISKED             => 'status-risked',
    ];
    /**
     * 风险状态改变规则
     * @var array
     */
    private $riskStatusChangeRules           = [
        self::STATUS_AUDITED     => [self::STATUS_NORMAL],
        self::STATUS_RISKED => [self::STATUS_NORMAL],
    ];

    protected function beforeValidate() {
        if (is_null($this->prize_sale_rate)){
            $this->prize_sale_rate = 0;
        }
        if (is_null($this->bet_record_id)){
            $this->bet_record_id = 0;
        }
        return parent::beforeValidate();
    }

    /**
     * 创建风险注单记录
     * @param Project $oProject
     * @return RiskProject | false
     */
    public static function addRiskProject($oProject){
        $data = $oProject->toArray();
        unset($data['id']);
        $data['project_id'] = $oProject->id;
        $obj = new static($data);
        $obj->status = 0;
//        pr($obj->toArray());
//        exit;
        if (!$bSucc = $obj->save()){
//            die('risk-error');
            pr($obj->getValidationErrorString());
            return false;
        }
        return $obj;
    }

    protected function getPrizeSetFormattedAttribute() {
        $aPrizeSets = json_decode($this->attributes['prize_set'], true);
        $aDisplay   = [];
//        pr($aPrizeSets);
//        exit;
        foreach ($aPrizeSets as $iBasicMethodId => $aPrizes) {
            $oBasicMethod = BasicMethod::find($iBasicMethodId);
            $oBasicMethod->name;
            $a            = [];
            foreach ($aPrizes as $iLevel => $fPrize) {
                $a[] = ChnNumber::getLevel($iLevel) . ': ' . $fPrize * $this->coefficient . '元';
            }
            $aDisplay[$oBasicMethod->name] = $oBasicMethod->name . ' : ' . implode(' ; ', $a);
        }
        return implode('<br /', $aDisplay);
    }

    protected function getFormattedStatusAttribute() {
        return __('_riskproject.' . strtolower(Str::slug(static::$validStatuses[$this->attributes['status']])));
    }

    protected function getPrizeFormattedAttribute() {
        return $this->attributes['prize'] ? $this->getFormattedNumberForHtml('prize') : null;
    }

    protected function getAmountFormattedAttribute() {
        return $this->getFormattedNumberForHtml('amount');
    }

    protected function getUpdatedAtTimeAttribute() {
        return substr($this->updated_at, 5, -3);
    }

    protected function getEndTimeFormattedAttribute() {
        return date('Y-m-d H:i:s', $this->attributes['end_time']);
    }

    protected function getFormattedCoefficientAttribute() {
        return !is_null($this->coefficient) ? Coefficient::getCoefficientText($this->coefficient) : '';
    }

    protected function getDisplayBetNumberForListAttribute() {
        $iMaxLen = 100;
        if (strlen($this->attributes['display_bet_number']) > $iMaxLen) {
            return substr($this->attributes['display_bet_number'], 0, $iMaxLen) . '...';
        } else {
            return $this->attributes['display_bet_number'];
        }
    }

    protected function getDisplayBetNumberShortAttribute() {
        return mb_strlen($this->attributes['display_bet_number']) > 10 ? mb_substr($this->attributes['display_bet_number'], 0, 10) . '...' : $this->attributes['display_bet_number'];
    }

    protected function getdisplayBetNumberForViewAttribute() {
        $iWidthScreen = 120;
        if (strlen($this->attributes['display_bet_number']) > $iWidthScreen) {
            $sSplitChar      = Config::get('bet.split_char');
            $aNumbers        = explode($sSplitChar, $this->attributes['display_bet_number']);
            $iWidthBetNumber = strlen($aNumbers[0]);
            $aMultiArray     = array_chunk($aNumbers, intval($iWidthScreen / $iWidthBetNumber));
            $aText           = [];
            foreach ($aMultiArray as $aNumberArray) {
                $aText[] = implode($sSplitChar, $aNumberArray);
            }
            return implode('<br />', $aText);
        } else {
            return $this->attributes['display_bet_number'];
        }
    }

    protected function getFormattedIsTesterAttribute() {
        if ($this->attributes['is_tester'] !== null) {
            return __('_basic.' . strtolower(Config::get('var.boolean')[$this->attributes['is_tester']]));
        } else {
            return '';
        }
    }

    protected function getFriendlyEndTimeAttribute() {
        return date('H:i:s', $this->attributes['end_time']);
    }

    protected function getFriendlyBoughtAtAttribute() {
        return substr($this->attributes['bought_at'], 11);
    }

    protected function getSerialNumberShortAttribute() {
        return substr($this->attributes['serial_number'], -6);
    }
    
    /**
     * 设置为有风险
     * @return bool
     */
    public function setRisk($sReason) {
        return $this->_setRiskStatus(self::STATUS_RISKED,['refuse_reason' => $sReason]);
    }

    /**
     * 设置为风险已审核
     * @return bool
     */
    public function setRiskAudited() {
        return $this->_setRiskStatus(self::STATUS_AUDITED);
    }

    /**
     * 设置风险状态
     * @param int       $iToStatus        目标状态
     * @param bool  $bSetOther   是否设置用户名等信息
     * @param array $aExtraData  要同时设置的其他数据数组
     * @return boolean
     */
    protected function _setRiskStatus($iToStatus, $aExtraData = [], $bSetOther = true){
        if (!in_array($this->status, $this->riskStatusChangeRules[$iToStatus])){
            return false;
        }
        $data = [
            'status' => $iToStatus
        ];
        if ($bSetOther){
            $data['auditor'] = Session::get('admin_username');
            $data['audited_at'] = date('Y-m-d H:i:s');
        }
        $data = array_merge($data, $aExtraData);
        $aConditions = [
            'id' => ['=', $this->id],
            'status' => ['<>', $this->risk_status],
        ];
        return $this->strictUpdate($aConditions, $data);
    }

    /**
     * 检查风险注单数量
     * @return int
     * @access public
     * @static
     */
    public static function checkNewFlag() {
        $key = static::compileNewFlagCacheKey();
        Cache::setDefaultDriver(static::$cacheDrivers[self::CACHE_LEVEL_SECOND]);
        return intval(Cache::get($key));
    }

    /**
     * 设置风险注单数量，加一
     * @access public
     * @static
     */
    public static function setNewFlag() {
        $key = static::compileNewFlagCacheKey();
        Cache::setDefaultDriver(static::$cacheDrivers[self::CACHE_LEVEL_SECOND]);
        Cache::has($key) or Cache::forever($key, 0);
        Cache::increment($key);
    }

    /**
     * 更新风险注单数量，减一
     * @access public
     * @static
     */
    public static function updateNewFlag() {
        $key = static::compileNewFlagCacheKey();
        Cache::setDefaultDriver(static::$cacheDrivers[self::CACHE_LEVEL_SECOND]);
        Cache::get($key) > 0 ? Cache::decrement($key) : Cache::forever($key, 0);
    }

    /**
     * 生成风险注单计数器Key
     * @return string
     * @access private
     * @static
     */
    private static function compileNewFlagCacheKey() {
        return static::getCachePrefix(true) . 'new-risk-project';
    }

    /**
     * 返回指定用户指定状态的风险注单的数量
     * @param int $iUserId
     * @param int $iStatus
     * @return int
     */
    public static function getCount($iUserId, $iStatus = self::STATUS_NORMAL){
        return self::where('status', '=', $iStatus)
            ->where('user_id', '=', $iUserId)
            ->count();
    }

    public static function getValidStatuses(){
        return static::_getArrayAttributes(__FUNCTION__);
    }
}
