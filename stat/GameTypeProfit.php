<?php

/**
 * 游戏类别盈亏
 *
 * @author garin
 */
class GameTypeProfit extends BaseModel {

    protected $table = 'gt_profits';

    protected static $cacheUseParentClass = false;

    protected static $cacheLevel = self::CACHE_LEVEL_FIRST;

    protected static $cacheMinutes = 0;

    protected $fillable = [
        'date',
        'game_type',
        'prj_count',
        'tester_prj_count',
        'net_prj_count',
        'turnover',
        'prize',
        'bonus',
        'share',
        'commission',
        'lose_commission',
        'profit',
        'tester_turnover',
        'tester_prize',
        'tester_bonus',
        'tester_commission',
        'tester_lose_commission',
        'tester_profit',
        'tester_share',
        'net_share',
        'net_turnover',
        'net_prize',
        'net_bonus',
        'net_commission',
        'net_lose_commission',
        'net_profit',
        'profit_margin',
        'bought_users',
        'user_avg_turnover',
        'prj_avg_turnover'
    ];

    public static $sequencable = false;

    public static $enabledBatchAction = false;

    protected $validatorMessages = [];

    protected $isAdmin = true;

    public static $resourceName = 'GameTypeProfit';

    protected $softDelete = false;

    protected $defaultColumns = ['*'];

    protected $hidden = [];

    protected $visible = [];

    public static $treeable = '';

    public static $foreFatherIDColumn = '';

    public static $foreFatherColumn = '';

    public static $columnForList = [
        'date',
        'game_type',
        //'prj_count',
        //'tester_prj_count',
        'net_prj_count',
        'turnover',
        'prize',
        'bonus',
        'commission',
        'lose_commission',
        'profit',
        //'tester_turnover',
        //'tester_prize',
        //'tester_bonus',
        //'tester_commission',
        //'tester_lose_commission',
        //'tester_profit',
        'net_turnover',
        'net_prize',
        'net_bonus',
        'net_commission',
        'net_lose_commission',
        'net_profit',
        //'profit_margin',
        //'bought_users',
        'user_avg_turnover',
        'prj_avg_turnover',
    ];

    public static $totalColumns = [
        'bought_count',
        'net_prj_count',
        'net_turnover',
        'net_prize',
        'net_bonus',
        'net_commission',
        'net_lose_commission',
        'net_share',
        'net_profit',
    ];

    public static $totalRateColumns = [
        'profit_margin' => ['net_profit', 'net_turnover']
    ];

    public static $weightFields = [
        'net_turnover',
        'net_profit',
        'profit_margin'
    ];

    public static $classGradeFields = [
        'net_profit',
        'profit_margin'
    ];

    public static $floatDisplayFields = [];

    public static $noOrderByColumns = [];

    public static $ignoreColumnsInView = [
        'id',
        'created_at',
        'updated_at',
        'bought_users',
    ];

    public static $ignoreColumnsInEdit = [
        'id',
        'created_at',
        'updated_at',
    ];

    public static $listColumnMaps = [
        'profit_margin' => 'profit_margin_formatted',
        'game_type'  => 'game_type_formatted'
    ];

    public static $viewColumnMaps = [
        'profit_margin' => 'profit_margin_formatted',
        'game_type'  => 'game_type_formatted'
    ];

    public static $htmlSelectColumns = [];

    public static $htmlTextAreaColumns = [];

    public static $htmlNumberColumns = [
        'bought_count' => 0,
        'net_prj_count' => 0,
        'prj_count' => 0,
        'tester_prj_count' => 0,
        'turnover' => 4,
        'prize' => 6,
        'bonus' => 2,
        'commission' => 6,
        'share' => 2,
        'profit' => 6,
        'tester_turnover' => 4,
        'tester_prize' => 6,
        'tester_bonus' => 2,
        'tester_commission' => 6,
        'tester_share' => 2,
        'tester_profit' => 6,
        'net_turnover' => 4,
        'net_prize' => 6,
        'net_bonus' => 2,
        'net_commission' => 6,
        'net_lose_commission' => 0,
        'net_share' => 2,
        'net_profit' => 6,
        'prj_avg_turnover' => 2,
        'user_avg_turnover' => 2,
    ];

    public static $htmlOriginalNumberColumns = [];

    public static $amountAccuracy = 0;

    public static $originalColumns;

    public $orderColumns = [
        'date' => 'desc',
    ];

    public static $titleColumn = 'game_type';

    public static $mainParamColumn = 'date';

    public static $rules = [
        'date' => 'required|date',
        'bought_count' => 'integer',
        'prj_count' => 'integer',
        'turnover' => 'numeric',
        'prize' => 'numeric|min:0',
        'commission' => 'numeric|min:0',
        'lose_commission' => 'numeric|min:0',
        'bonus' => 'numeric|min:0',
        'profit' => 'numeric',
        'tester_prj_count' => 'integer',
        'tester_turnover' => 'numeric',
        'tester_prize' => 'numeric|min:0',
        'tester_commission' => 'numeric|min:0',
        'tester_lose_commission' => 'numeric|min:0',
        'tester_bonus' => 'numeric|min:0',
        'tester_profit' => 'numeric',
        'net_prj_count' => 'integer',
        'net_turnover' => 'numeric',
        'net_prize' => 'numeric|min:0',
        'net_commission' => 'numeric|min:0',
        'net_lose_commission' => 'numeric|min:0',
        'net_bonus' => 'numeric|min:0',
        'net_profit' => 'numeric',
        'profit_margin' => 'numeric',
        'prj_avg_turnover' => 'numeric',
        'user_avg_turnover' => 'numeric',
    ];

    protected function beforeValidate() {
        $this->bought_count or $this->bought_count = null;
        return parent::beforeValidate();
    }

    /**
     * 返回对象
     *
     * @param string $sDate
     * @param int    $iUserId
     * @param int    $iGameType
     *
     * @return bool|GameTypeProfit
     */
    public static function getGameTypeProfitObject($sDate, $iGameType) {
        $obj = self::where('date', '=', $sDate)
            ->where('game_type', '=', $iGameType)
            ->lockForUpdate()->first();
        if (!is_object($obj)) {
            $data = [
                'date' => $sDate,
                'game_type' => $iGameType
            ];
            $obj = new static($data);
            if (!$obj->save()) {
                return false;
            }
            $obj = self::where('date', '=', $sDate)
                ->where('game_type', '=', $iGameType)
                ->lockForUpdate()->first();
        }
        return $obj;
    }

    /**
     * 累加销售额
     *
     * @param float  $fAmount
     * @param object $oUser
     *
     * @return boolean
     */
    public function addTurnover($fAmount, $oUser) {
        $this->turnover += $fAmount;
        $fAmount > 0 ? $this->prj_count++ : $this->prj_count--;
        if ($oUser->is_tester) {
            $this->tester_turnover += $fAmount;
            $fAmount > 0 ? $this->tester_prj_count++ : $this->tester_prj_count--;
        }
        $this->net_prj_count = $this->prj_count - $this->tester_prj_count;
        $this->calculateProfit($oUser->is_tester);
        $oUser->is_tester or $this->updateBoughtCount($oUser);
        $bSucc = $this->save();
        if (!$bSucc) {
            file_put_contents('/tmp/profit', var_export($this->validationErrors->toArray(), 1));
        }
        return $bSucc;
    }

    /**
     * 累加奖金
     *
     * @param float  $fAmount
     * @param object $oUser
     *
     * @return boolean
     */
    public function addPrize($fAmount, $oUser) {
        $this->prize += $fAmount;
        !$oUser->is_tester or $this->tester_prize += $fAmount;
        $this->calculateProfit($oUser->is_tester);
        return $this->save();
    }

    /**
     * 累加佣金
     *
     * @param float  $fAmount
     * @param object $oUser
     *
     * @return boolean
     */
    public function addCommission($fAmount, $oUser) {
        $this->commission += $fAmount;
        !$oUser->is_tester or $this->tester_commission += $fAmount;
        $this->calculateProfit($oUser->is_tester);
        return $this->save();
    }

    /**
     * 累加输值佣金
     *
     * @param float  $fAmount
     * @param object $oUser
     *
     * @return boolean
     */
    public function addLoseCommission($fAmount, $oUser) {
        $this->lose_commission += $fAmount;
        !$oUser->is_tester or $this->tester_lose_commission += $fAmount;
        $this->calculateProfit($oUser->is_tester);
        return $this->save();
    }

    /**
     * 添加促销派奖
     *
     * @param float  $fAmount
     * @param object $oUser
     *
     * @return boolean
     */
    public function addBonus($fAmount, $oUser) {
        $this->bonus += $fAmount;
        !$oUser->is_tester or $this->tester_bonus += $fAmount;
        $this->calculateProfit($oUser->is_tester);
        return $this->save();
    }

    /**
     * 累加分红
     *
     * @param float  $fAmount
     * @param object $oUser
     *
     * @return boolean
     */
    public function addShare($fAmount, $oUser) {
        $this->share += $fAmount;
        !$oUser->is_tester or $this->tester_share += $fAmount;
        $this->net_share = $this->share - $this->tester_share;
        $this->calculateProfit($oUser->is_tester);
        return $this->save();
    }

    public function calculateProfit($bTester = false) {
        $this->net_prj_count = $this->prj_count - $this->tester_prj_count;
        $this->net_turnover = $this->turnover - $this->tester_turnover;
        $this->net_prize = $this->prize - $this->tester_prize;
        $this->net_commission = $this->commission - $this->tester_commission;
        $this->net_lose_commission = $this->lose_commission - $this->tester_lose_commission;
        $this->net_bonus = $this->bonus - $this->tester_bonus;
        $this->net_share           = $this->share - $this->tester_share;
        $this->profit = $this->turnover - $this->prize - $this->commission - $this->lose_commission - $this->bonus - $this->share;
        $this->tester_profit = $this->tester_turnover - $this->tester_prize - $this->tester_commission - $this->tester_lose_commission - $this->tester_bonus - $this->tester_share;
        $this->net_profit = $this->net_turnover - $this->net_prize - $this->net_commission - $this->net_lose_commission - $this->net_bonus - $this->net_share;
        $this->profit_margin = $this->net_turnover ? $this->net_profit / $this->net_turnover : 0;
        $this->prj_avg_turnover = $this->net_prj_count > 0 ? $this->net_turnover / $this->net_prj_count : null;
        $this->user_avg_turnover = $this->bought_count ? $this->net_turnover / $this->bought_count : null;
    }

    public static function updateProfitData($sType, $sDate, $iGameType, $oUser, $fAmount) {
        if (in_array($sType, [ 'deposit', 'withdrawal' ])){
            return true;
        }
        $sFunction = 'add' . ucfirst(String::camel($sType));
        $oProfit = self::getGameTypeProfitObject($sDate, $iGameType);
        return $oProfit->$sFunction($fAmount, $oUser);
    }

    public function updateBoughtCount($oUser) {
        if ($oUser->is_tester) {
            return true;
        }
        if ($sBoutghtUsers = $this->attributes['bought_users']) {
            $aBoughtUsers = explode(',', $sBoutghtUsers);
            $bIncrement = !in_array($oUser->id, $aBoughtUsers);
        } else {
            $aBoughtUsers = [];
            $bIncrement = true;
        }
        if ($bIncrement) {
            $aBoughtUsers[] = $oUser->id;
            $this->attributes['bought_users'] = implode(',', $aBoughtUsers);
            $this->attributes['bought_count']++;
        }
        return true;
    }

    protected function getGameTypeFormattedAttribute() {
        $sGameTypes = GameType::getGameTypesIdentifier();

        return __('_gametype.' . $sGameTypes[$this->game_type]);
    }


}
