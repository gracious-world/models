<?php

/**
 * 分红明细model
 *
 * @author Garin
 * @date 2016-11-24
 */
class GameTypeDividend extends BaseModel {

    protected $table = 'gt_dividends';
    public static $resourceName = 'GameTypeDividend';
    public static $treeable = false;
    public static $sequencable = false;
    protected $softDelete = false;

    protected $fillable = [
        'year',
        'month',
        'batch',
        'begin_date',
        'end_date',
        'user_id',
        'username',
        'is_tester',
        'turnover',
        'valid_sales',
        'prize',
        'bonus',
        'commission',
        'lose_commission',
        'profit',
        'rate',
        'amount',
        'game_type',
        'total_profit',
        'total_profit_origin'
    ];
    public static $columnForList = [
        'year',
        'month',
        'batch',
        'begin_date',
        'end_date',
        'game_type',
        'username',
        'is_tester',
        'turnover',
        'prize',
        'commission',
        'bonus',
        'lose_commission',
        'profit',
        'valid_sales',
        'total_profit',
        'total_profit_origin',
        'rate',
        'amount',

    ];
    public static $totalColumns = [
        'turnover',
        'valid_sales',
        'prize',
        'bonus',
        'commission',
        'profit',
        'total_profit_origin',
        'amount',
    ];
    public static $listColumnMaps = [
        'is_tester' => 'is_tester_formatted',
        'rate' => 'rate_formatted',
        'turnover' => 'turnover_formatted',
        'valid_sales' => 'valid_sales_formatted',
        'prize' => 'prize_formatted',
        'bonus' => 'bonus_formatted',
        'commission' => 'commission_formatted',
        'lose_commission' => 'lose_commission_formatted',
        'profit' => 'profit_formatted',
        'total_profit_origin' => 'total_profit_origin_formatted',
        'amount' => 'amount_formatted',
        'game_type'  => 'game_type_formatted'

    ];

    public static $viewColumnMaps            = [
        'is_tester' => 'is_tester_formatted',
        'rate' => 'rate_formatted',
        'turnover' => 'turnover_formatted',
        'valid_sales' => 'valid_sales_formatted',
        'prize' => 'prize_formatted',
        'bonus' => 'bonus_formatted',
        'commission' => 'commission_formatted',
        'lose_commission' => 'lose_commission_formatted',
        'profit' => 'profit_formatted',
        'total_profit_origin' => 'total_profit_origin_formatted',
        'amount' => 'amount_formatted',
        'game_type'  => 'game_type_formatted'
    ];
    public static $rules = [

    ];
    public static $htmlSelectColumns = [

    ];
    public $orderColumns = [
        'end_date' => 'desc',
    ];


    public static $classGradeFields = [
        'amount',
    ];
    protected $User;
    protected $Account;


    protected function getUserTypeFormattedAttribute() {
        if ($this->parent_user_id)
            $sUserType = User::$userTypes[$this->is_agent];
        else
            $sUserType = User::$userTypes[User::TYPE_TOP_AGENT];
        return __('_user.' . $sUserType);
    }

    protected function getGameTypeFormattedAttribute() {
        $sGameTypes = GameType::getGameTypesIdentifier();

        return __('_gametype.' . $sGameTypes[$this->game_type]);
    }

    protected function getRateFormattedAttribute() {
        return $this->attributes['rate'] * 100 . '%';
    }

    protected function getTurnoverFormattedAttribute() {
        return number_format($this->attributes['turnover'], 2);
    }

    protected function getValidSalesFormattedAttribute() {
        return number_format($this->attributes['valid_sales'], 2);
    }

    protected function getVerifiedAtFormattedAttribute() {
        return substr($this->attributes['verified_at'], 5);
    }

    protected function getSentAtFormattedAttribute() {
        return substr($this->attributes['sent_at'], 5);
    }

    protected function getPrizeFormattedAttribute() {
        return number_format($this->attributes['prize'], 2);
    }

    protected function getBonusFormattedAttribute() {
        return number_format($this->attributes['bonus'], 2);
    }

    protected function getCommissionFormattedAttribute() {
        return number_format($this->attributes['commission'], 2);
    }

    protected function getLoseCommissionFormattedAttribute() {
        return number_format($this->attributes['lose_commission'], 2);
    }

    protected function getProfitFormattedAttribute() {
        return number_format($this->attributes['profit'], 2);
    }

    protected function getTotalProfitOriginFormattedAttribute() {
        return number_format($this->attributes['total_profit_origin'], 2);
    }

    protected function getAmountFormattedAttribute() {
        return number_format($this->attributes['amount'], 2);
    }

    protected function getIsTesterFormattedAttribute() {
        return is_null($this->attributes['is_tester']) ? '' : __('_basic.' . strtolower(Config::get('var.boolean')[$this->attributes['is_tester']]));
    }

    /**
     * 计算总分红金额
     *
     * @param array $aWhere
     *
     * @return mixed
     */
    public static function getTotalAmount($aWhere = []) {
        $oTotalAmount = static::doWhere($aWhere)
            ->select(DB::raw('sum(amount) total_amount'))
            ->orderBy('user_id')
            ->first();
        return $oTotalAmount;
    }

    /**
     * 根据条件获取分红明细
     *
     * @param int    $iUserId
     * @param string $sBeginDate
     * @param string $sEndDate
     * @param int    $iGameType
     *
     * @return mixed
     */
    public static function getDividendByMonthUser($iUserId, $sBeginDate = '', $sEndDate = '', $iGameType = 0) {
        $aConditions = [
            'user_id' => ['=', $iUserId],
            'game_type' => ['=', $iGameType],
        ];
        !$sBeginDate or $aConditions['begin_date'] = ['=', $sBeginDate];
        !$sEndDate or $aConditions['end_date'] = ['=', $sEndDate];
        return static::doWhere($aConditions)->orderBy('end_date', 'desc')->first();
    }


}
