<?php

/**
 * 团队盈亏表
 *
 * @author lucky
 * @created_at 2016-10-10
 */
class TeamTotalProfit extends BaseModel {


    const DIVIDENDS_STATUS_CREATED          = 0;
    const DIVIDENDS_STATUS_ACCEPTED         = 1;
    const DIVIDENDS_STATUS_AUDIT            = 2;
    const DIVIDENDS_STATUS_REJECT           = 3;
    const DIVIDENDS_STATUS_SENT             = 4;
    const DIVIDENDS_STATUS_SENT_FAILED      = 5;

    protected $table                         = 'team_total_profits';
    public static $resourceName              = 'TeamTotalProfit';
    public static $amountAccuracy            = 6;
    public static $htmlNumberColumns         = [
        'turnover'        => 4,
        'prize'           => 6,
        'profit'          => 6,
        'commission'      => 6,
    ];
    public static $htmlOriginalNumberColumns = [
        'prize_group'
    ];
    public static $columnForList             = [
        'id',
        'date',
        'user_leader_id',
        'user_leader_name',
        'turnover',
        'prize',
        'commission',
        'profit',
        'total_profit_origin',
        'total_profit',
        'dividends',
        'dividends_status',
    ];

    public static $htmlSelectColumns =[
            'dividends_status' => 'aDividendsStatus'
    ];
    public static $totalColumns              = [
        'turnover',
        'prize',
        'commission',
        'profit',
        'total_profit_origin',
        'total_profit',
        'dividends'
    ];
    public static $listColumnMaps            = [
        'date'  => 'date',
        'user_leader_id'  => 'user_leader_id',
        'user_leader_name'  => 'user_leader_name',
        'turnover'   => 'turnover_formatted',
        'prize'      => 'prize_formatted',
        'commission' => 'commission_formatted',
        'profit'     => 'profit_formatted',
        'total_profit_origin' => 'total_profit_origin_formatted',
        'total_profit'     => 'total_profit_formatted',
        'dividends'  =>  'dividends_formatted',
    ];

    public static $validDividendsStatus     = [
        self::DIVIDENDS_STATUS_CREATED          => 'dividends-status-created',    //待受理
        self::DIVIDENDS_STATUS_ACCEPTED         => 'dividends-status-accepted',   //受理
        self::DIVIDENDS_STATUS_AUDIT            => 'dividends-status-audit',   //审核
        self::DIVIDENDS_STATUS_REJECT           => 'dividends-status-reject',     //无效
        self::DIVIDENDS_STATUS_SENT             => 'dividends-status-sent',       //已发放
        self::DIVIDENDS_STATUS_SENT_FAILED      => 'dividends-status-sent-failed',//发放失败
    ];

    public static $dividendsStatusLang     = [
        0  => '待受理',
        1  => '受理',
        2  => '审核',
        3  => '无效',
        4  => '已发放',
        5  => '发放失败'
    ];

    public static $weightFields              = [
        'user_leader_name',
        'profit',
    ];
    public static $classGradeFields          = [
        'profit',
    ];
//    public static $noOrderByColumns          = [
//        'user_type'
//    ];
    protected $fillable                      = [
        'id',
        'date',
        'user_leader_id',
        'user_leader_name',
        'turnover',
        'prize',
        'commission',
        'profit',
        'total_profit',
        'dividends',
        'dividends_status',
        'created_at',
        'updated_at'
    ];
    public static $rules                     = [
        'date'                => 'required|date',
        'user_leader_id'      => 'required|integer',
        'user_leader_name'    => 'required|max:16',
        'turnover'            => 'numeric',
        'prize'               => 'numeric',
        'commission'          => 'numeric',
        'profit'              => 'numeric',
        'total_profit_origin' => 'numeric',
        'total_profit'        => 'numeric',
        'dividends'           => 'numeric',
        'dividends_status'    => 'required|integer|in:0,1,2,3,4,5'
    ];
    public $orderColumns                     = [
        'date'     => 'desc',
        'turnover' => 'desc',
    ];
    public static $mainParamColumn           = 'user_leader_id';
    public static $titleColumn               = 'user_leader_id';
//    public static $aUserTypes                = ['-1' => 'Top Agent', '0' => 'Agent'];

    // 按钮指向的链接，查询列名和实际参数来源的列名的映射
    // public static $aButtonParamMap = ['parent_user_id' => 'user_id'];

    /**
     * 累计盈亏
     * @param $date
     * @return mixed
     */
    static function getProfits($iUserLeaderId, $sStartDate, $sEndDate){
        $rs=static::whereBetween("date",[$sStartDate, $sEndDate])->where("user_leader_id",'=',$iUserLeaderId)->sum("profit");
        return $rs ? $rs : 0;
    }


    protected function getTurnoverFormattedAttribute() {
        return $this->getFormattedNumberForHtml('turnover');
    }

    protected function getCommissionFormattedAttribute() {
        return $this->getFormattedNumberForHtml('commission');
    }


    protected function getProfitFormattedAttribute() {
        return $this->getFormattedNumberForHtml('profit');
    }

    protected function getTotalProfitOriginFormattedAttribute() {
        return $this->getFormattedNumberForHtml('total_profit_origin');
    }

    protected function getTotalProfitFormattedAttribute() {
        return $this->getFormattedNumberForHtml('total_profit');
    }

    protected function getPrizeFormattedAttribute() {
        return $this->getFormattedNumberForHtml('prize');
    }

    protected function getDividendsFormattedAttribute() {
        return $this->getFormattedNumberForHtml('dividends');
    }

    public static function getValidDividendsStatus(){
        return static::_getArrayAttributes(__FUNCTION__);
    }

    public static function getProfitObject($date, $iUserId) {
        $aAttributes = [
            'date'    => $date,
            'user_leader_id' => $iUserId
        ];
        $obj         = static::firstOrCreate($aAttributes);
        return $obj;
    }

    /**
     * 获取当月分红
     * @param $date
     * @return mixed
     */
    static function getDividends($iUserLeaderId, $sStartDate, $sEndDate){
        return static::whereBetween("date",[$sStartDate, $sEndDate])->where("user_leader_id",'=',$iUserLeaderId)->get(["dividends"]);
    }
}
