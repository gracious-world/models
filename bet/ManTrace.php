<?php
use Illuminate\Support\Facades\Redis;

class ManTrace extends Trace {
    protected static $cacheUseParentClass = true;
    protected $fillable = [
        'finished_issues',
        'canceled_issues',
        'won_issue',
        'prize',
        'prized_count',
        'finished_amount',
        'canceled_amount',
        'status',
        'canceled_at',
        'stoped_at',
    ];
    public static $ignoreColumnsInView = [
        'account_id',
        'user_forefather_ids',
        'way_id',
        'won_issue',
        'won_count',
        'user_id',
        'bet_number',
        'prize_set',
    ];

    public static $rules = [
        'bought_at' => 'date_format:Y-m-d H:i:s',
        'canceled_at' => 'date_format:Y-m-d H:i:s',
        'stoped_at'   => 'date_format:Y-m-d H:i:s',
        'finished_amount' => 'regex:/^[\d]+(\.[\d]{0,4})?$/',
        'canceled_amount' => 'regex:/^[\d]+(\.[\d]{0,4})?$/',
        'status'    => 'in:0,1,2,3,4',
    ];
    public static $totalColumns = [
        'amount',
        'prize',
    ];

    public static $columnForList = [
        'id',
        'serial_number',
        'username',
        'is_tester',
        'lottery_id',
        'title',
        'display_bet_number',
        'coefficient',
        'stop_on_won',
        'start_issue',
        'total_issues',
        'finished_issues',
//        'canceled_issues',
        'amount',
        'prize',
        'status',
        'ip',
        'bought_at',
    ];
    public static $listColumnMaps = [
        'serial_number' => 'serial_number_short',
        'status'        => 'formatted_status',
        'display_bet_number'    => 'display_bet_number_short',
        'amount'        => 'amount_formatted',
        'finished_amount'        => 'finished_amount_formatted',
        'canceled_amount'        => 'canceled_amount_formatted',
        'bought_at' => 'friendly_bought_at',
        'coefficient' => 'formatted_coefficient',
        'is_tester' => 'formatted_is_tester',
        'stop_on_won' => 'stop_on_won_formatted',
    ];
    public static $floatDisplayFields = [
        'display_bet_number',
        'bought_at',
    ];

    public static $viewColumnMaps = [
        'status'        => 'formatted_status',
        'amount'        => 'amount_formatted',
        'bet_number'    => 'display_bet_number',
        'finished_amount'        => 'finished_amount_formatted',
        'canceled_amount'        => 'canceled_amount_formatted',
        'display_bet_number'    => 'display_bet_number_for_view',
        'stop_on_won'        => 'stop_on_won_formatted',
        'is_tester' => 'formatted_is_tester',
    ];

    /**
     * 更新任务中奖金额
     * @param bool $fPrize
     * @return bool
     */
    public function updatePrize($sIssue,$fPrize){
        $data = [
            'prize' => $this->prize + $fPrize,
            'won_issue' => $sIssue,
        ];
        if ($bSucc = $this->update($data)){
            $this->fill($data);
        }
        return $bSucc;
    }

    public function setGenerateTask(& $sRealQueue){
        return BaseTask::addTask('CreateProject', ['trace_id' => $this->id,'type' => 'manual'], 'trace',0,$sRealQueue);
    }

    protected function getDisplayBetNumberShortAttribute(){
        return mb_strlen($this->attributes[ 'display_bet_number' ]) > 10 ? mb_substr($this->attributes[ 'display_bet_number' ],0,10) . '...' : $this->attributes[ 'display_bet_number' ];
    }

    protected function getFriendlyBoughtAtAttribute() {
        return substr($this->attributes['bought_at'], 11);
    }

    protected function getDisplayBetNumberForViewAttribute(){
        $iWidthScreen = 120;
        if (strlen($this->attributes[ 'display_bet_number' ]) > $iWidthScreen){
            $sSplitChar = Config::get('bet.split_char');
            $aNumbers = explode($sSplitChar, $this->attributes[ 'display_bet_number' ]);
            $iWidthBetNumber = strlen($aNumbers[0]);
            $aMultiArray = array_chunk($aNumbers, intval($iWidthScreen / $iWidthBetNumber));
            $aText = [];
            foreach($aMultiArray as $aNumberArray){
                $aText[] = implode($sSplitChar, $aNumberArray);
            }
            return implode('<br />',$aText);
        }
        else{
            return $this->attributes[ 'display_bet_number' ];
        }
    }

    public function deleteUserTraceListCache(){
        $sCacheKey = static::compileListCacheKey($this->user_id);
        $redis = Redis::connection();
        $redis->del($sCacheKey);
    }

//    public function deleteUserDataListCache(){
//        $sCacheKey = static::compileListCacheKey($this->user_id);
//        $redis = Redis::connection();
//        $redis->del($sCacheKey);
//    }

//    public static function deleteAllUserBetListCache(){
//        $redis = Redis::connection();
//        $sKeyPrefix = static::compileListCacheKeyPrefix();
//        if ($aKeys = $redis->keys($sKeyPrefix . '*')){
//            foreach($aKeys as $sKey){
//                $redis->del($sKey);
//            }
//        }
//    }

    protected function getFormattedIsTesterAttribute() {
        if ($this->attributes['is_tester'] !== null) {
            return __('_basic.' . strtolower(Config::get('var.boolean')[$this->attributes['is_tester']]));
        } else {
            return '';
        }
    }

}
