<?php

/**
 * 奖期规则模型
 *
 * @author winter
 * @date 2017-05-04
 */
class IssueRule extends BaseModel {

    public static $resourceName        = 'Issue Rule';
    protected $table                   = 'issue_rules';
    public static $columnForList       = [
        'lottery_id',
        'begin_time',
        'end_time',
        'cycle',
        'number_delay_time',
        'first_time',
        'stop_adjust_time',
        'encode_time',
        'issue_count',
        'enabled',
    ];
    public static $htmlSelectColumns   = [
        'lottery_id' => 'aLotteries',
    ];
    public $orderColumns               = [
        'begin_time' => 'asc'
    ];
    public static $mainParamColumn     = 'lottery_id';
    public static $ignoreColumnsInEdit = [
        'issue_count',
    ];
    protected $fillable                = [
        'id',
        'lottery_id',
        'begin_time',
        'end_time',
        'cycle',
        'number_delay_time',
        'first_time',
        'stop_adjust_time',
        'encode_time',
        'issue_count',
        'enabled',
    ];
    public static $rules               = [
        'lottery_id'        => 'required|integer',
        'begin_time'        => 'required|dateformat:H:i:s',
        'end_time'          => 'required|dateformat:H:i:s',
        'cycle'             => 'integer',
        'number_delay_time' => 'required|integer',
        'first_time'        => 'required|dateformat:H:i:s',
        'stop_adjust_time'  => 'integer',
        'encode_time'       => 'integer',
        'issue_count'       => 'integer',
        'enabled'           => 'in:0,1',
    ];
    public static $customMessages      = [];
    public static $titleColumn         = 'begin_time';

    public function setOpenClose() {
        $this->enabled == 1 - $this->enabled;
        return $this->save();
    }

    protected function beforeValidate() {
        $oLottery = Lottery::find($this->lottery_id);
        if ($oLottery->is_instant) {
            return false;
        }
        $iEndTime          = strtotime($this->end_time);
        $this->end_time > $this->first_time or $iEndTime          += 3600 * 24;
        $iTotalTime        = $iEndTime - strtotime($this->first_time);
        $this->issue_count = floor($iTotalTime / ($this->cycle + $this->number_delay_time)) + 1;
        return parent::beforeValidate();
    }

    protected function afterSave($oSavedModel) {
        $oRules           = static::where('lottery_id', '=', $this->lottery_id)->where('enabled', '=', 1)->get(['id', 'issue_count']);
        $iIssueCountOfDay = 0;
        foreach ($oRules as $oRule) {
            $iIssueCountOfDay += $oRule->issue_count;
        }
        $oLottery                    = Lottery::find($this->lottery_id);
        $bTraceEqual                 = $oLottery->daily_issue_count == $oLottery->trace_issue_count;
        $oLottery->daily_issue_count = $iIssueCountOfDay;
        !$bTraceEqual or $oLottery->trace_issue_count = $iIssueCountOfDay;
        $oLottery->save();
//        $oLottery->
        return parent::afterSave($oSavedModel);
    }

    /**
     * 根据彩种id获取对应的奖期规则信息
     * @param int $iLotteryId   彩种id
     * @return array  奖期规则对象数组
     */
    public static function getIssueRulesOfLottery($iLotteryId) {
        return static::where('lottery_id', '=', $iLotteryId)->get();
    }

    public function getIssueRules() {
        $aIssueRules    = DB::table($this->table)->get(['id', 'begin_time']);
        $aIssueRuleList = array();
        foreach ($aIssueRules as $val) {
            $aIssueRuleList[$val->id] = $val->begin_time;
        }
        return $aIssueRuleList;
    }

}
