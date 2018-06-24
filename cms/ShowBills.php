<?php

/**
 * Class ShowBills
 * 晒单信息
 */
use Illuminate\Support\Facades\Redis;
class ShowBills extends BaseModel {
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'show_bills';

    protected $fillable = [
        'id',
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
        'single_won_count',
        'won_count',
        'won_data',
        'bought_time',
        'is_top',
        'is_super',
        'current_grade',
        'title_custom',
        'created_at',
        'updated_at',
    ];

    public static $rules = [
        'project_id'            => 'integer',
        'terminal_id'           => 'integer',
        'serial_number'         => 'max:32',
        'trace_id'              => 'integer',
        'user_id'               => 'required|integer',
        'username'              => 'required|between:0,500',
        'is_tester'             => 'in:0,1',
        'user_forefather_ids'   => 'max:1024',
        'account_id'            => 'required|integer',
        'prize_group'           => 'between:0,500',
        'lottery_id'            => 'required|integer',
        'issue'                 => 'required|max:13',
        'way_id'                => 'required|integer',
        'title'                 => 'required|max:100',
        'position'              => 'max:10',
        'bet_number'            => 'between:0,500',
        'way_total_count'       => 'integer',
        'single_count'          => 'between:0,500',
        'bet_rate'              => 'numeric',
        'display_bet_number'    => 'between:0,500',
        'multiple'              => 'integer',
        'coefficient'           => 'required',
        'single_amount'         => 'regex:/^[\d]+(\.[\d]{0,4})?$/',
        'amount'                => 'regex:/^[\d]+(\.[\d]{0,4})?$/',
        'winning_number'        => 'required',
        'prize'                 => 'numeric',
        'single_won_count'      => 'integer',
        'won_count'             => 'integer',
        'won_data'              => 'between:0,500',
        'bought_time'           => 'required',
        'is_top'                => 'in:0,1',
        'is_super'              => 'in:0,1',
        'current_grade'         => 'between:0,500',
        'title_custom'          => 'between:0,500',
    ];

    public static $resourceName = 'ShowBills';

    public static $columnForList = [
        'id',
        'project_id',
        //'terminal_id',
        //'serial_number',
        //'trace_id',
        'user_id',
        'username',
        'is_tester',
        //'user_forefather_ids',
        //'account_id',
        //'prize_group',
        'lottery_id',
        'issue',
        //'way_id',
        'title',
        //'position',
        'bet_number',
        //'way_total_count',
        //'single_count',
        //'bet_rate',
        //'display_bet_number',
        //'multiple',
        //'coefficient',
        //'single_amount',
        //'amount',
        'winning_number',
        'prize',
        //'single_won_count',
        //'won_count',
        //'won_data',
        //'bought_time',
        //'is_top',
        'is_super',
        //'current_grade',
        //'created_at',
        //'updated_at',
    ];

    public static $ignoreColumnsInEdit = [
        'created_at',
        'updated_at',
    ];

    public $orderColumns = [
        'created_at' => 'desc'
    ];

    protected function beforeValidate() {
        parent::beforeValidate();
    }

    public $fillableFromProject = [
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
        'single_won_count',
        'won_count',
        'won_data',
        'bought_time',
    ];

    /**
     * @param $aConditions
     * @param $iLimit
     * @return array
     * 根据 条件 来 查询 数据  目前主要是 UserUserProfitController.php 中使用
     */
    static function getShowbillsByConditions($aConditions,$iLimit=1){
        if ($aConditions) {
            $aShowBills = ShowBills::doWhere($aConditions)->select('id', 'is_super', 'user_id', 'username', 'current_grade', 'lottery_id', 'prize', 'title', 'title_custom', 'created_at', DB::raw('prize/amount as per'))->orderBy('created_at', 'desc')->limit($iLimit)->get()->toArray();
        } else {
            $aShowBills = ShowBills::select('id', 'is_super', 'user_id', 'username', 'current_grade', 'lottery_id', 'prize', 'title', 'title_custom', 'created_at', DB::raw('prize/amount as per'))->orderBy('created_at', 'desc')->limit($iLimit)->get()->toArray();
        }
        return $aShowBills;
    }

    /**
     * @param $aConditions
     * @param $iUserId
     * 根据条件 查询 晒单总数
     */
    static function countShowbillsByUserId($aConditions){
        $oShowBillsCount = ShowBills::doWhere($aConditions)->select(DB::raw('count(id) as countId'))->first();
        return $oShowBillsCount ? $oShowBillsCount->countId : 0;
    }
    
}
