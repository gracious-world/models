<?php

use Illuminate\Support\Facades\Redis;

class UserTrace extends Trace {

    protected static $cacheUseParentClass = true;
    public static $columnForList          = [
        'lottery_id',
        'serial_number',
        'start_issue',
        'title',
        'bet_number',
        'amount',
        'prize',
        'status',
    ];
    protected $fillable                   = [
        'terminal_id',
        'user_id',
        'username',
        'user_forefather_ids',
        'is_tester',
        'account_id',
        'prize_group',
        'prize_set',
        'prize_group',
        'total_issues',
        'finished_issues',
        'canceled_issues',
        'stop_on_won',
        'lottery_id',
        'way_id',
        'title',
        'position',
        'way_total_count',
        'single_count',
        'bet_rate',
        'bet_number',
        'display_bet_number',
        'start_issue',
        'won_issue',
        'prize',
        'won_count',
        'coefficient',
        'single_amount',
        'amount',
        'finished_amount',
        'canceled_amount',
        'status',
        'ip',
        'proxy_ip',
        'bought_at',
        'canceled_at',
        'stoped_at',
    ];
    public $orderColumns                  = [
        'id' => 'desc'
    ];

    /**
     * 建立追号任务
     *
     * @param array     $aDetails
     * @return int
     *   >0: 成功,为追号任务的ID;
     *   -1: 数据错误;
     *   -2: 账变保存失败;
     *   -3: 账户余额保存失败;
     *   -4: 余额不足
     *   -5: 注单保存失败
     *   -6: 佣金数据保存失败
     *   -7: 奖金数据保存失败
     *   -8: 预约状态更新失败
     *   -9: 追号任务保存失败
     *   -10: 追号预约保存失败
     */
    public function addTrace($aDetails, $aEndTimes, & $oFirstProject, $oBetTime = null) {
//        pr($aDetails);
//        pr($aEndTimes);
//        exit;
        if ($this->Account->available < $this->amount) {
            return self::ERRNO_TRACE_ERROR_LOW_BALANCE;
        }
        $rules = & static::compileRules();
        if (!$this->save($rules)) {
            pr($this->validationErrors->toArray());
            return self::ERRNO_TRACE_ERROR_SAVE_ERROR;
        }
        $aAttributes             = $this->getAttributes();
        $aAttributes['trace_id'] = $this->id;
        unset($aAttributes['id']);
        if (($iReturn                 = Transaction::addTransaction($this->User, $this->Account, TransactionType::TYPE_FREEZE_FOR_TRACE, $this->amount, $aAttributes)) != Transaction::ERRNO_CREATE_SUCCESSFUL) {
//            pr($iReturn);
            return $iReturn;
        }
        if ($this->saveDetails($aDetails, $aEndTimes)) {
            $mReturn = $this->generateProjectOfIssue($this->start_issue, $oBetTime);
            if (is_object($mReturn)) {
                $oFirstProject = $mReturn;
                return $this->id;
            }
            return $mReturn;
        } else {
            return self::ERRNO_TRACE_DETAIL_SAVE_FAILED;
        }
    }

    /**
     * 保存预约详情
     *
     * @param array $aDetails
     * @return bool
     */
    private function saveDetails(& $aDetails, & $aEndTimes) {
        return TraceDetail::addDetails($this, $aDetails, $aEndTimes);
    }

    protected function getSplittedWinningNumberAttribute() {
        if (!$this->winning_number) {
            return [];
        }

        $oLottery       = Lottery::find($this->lottery_id);
        return $oLottery->type = Lottery::LOTTERY_TYPE_DIGITAL ? str_split($this->winning_number, 1) : $aSplitted      = explode(' ', $this->winning_number);
    }

    protected function getUpdatedAtDayAttribute() {
        return substr($this->updated_at, 5, 5);
    }

    protected function getUpdatedAtTimeAttribute() {
        return substr($this->updated_at, 11, 5);
    }

    public static function createListCache($iUserId, $iPage = 1, $iPageSize = 20) {
        $redis    = Redis::connection();
        $sKey     = static::compileListCacheKey($iUserId, $iPage);
        $aColumns = ['id', 'lottery_id', 'status', 'updated_at', 'total_issues', 'finished_issues', 'stop_on_won', 'serial_number', 'bought_at', 'amount', 'finished_amount', 'canceled_amount', 'start_issue'];
//        $aColumns = ['id', 'lottery_id', 'amount', 'status', 'issue','title','multiple','display_bet_number','coefficient','winning_number','prize'];
        $oQuery   = static::where('user_id', '=', $iUserId);
        $iStart   = ($iPage - 1) * $iPageSize;
        $oTraces  = $oQuery->orderBy('bought_at', 'desc')->skip($iStart)->limit($iPageSize)->get($aColumns);
        $redis->multi();
        $redis->del($sKey);
        foreach ($oTraces as $oTrace) {
            $redis->rpush($sKey, json_encode($oTrace->toArray()));
        }
        $redis->exec();
    }

    public static function & getListOfPage($iUserId, $iPage = 1, $iPageSize = 20) {
        $redis       = Redis::connection();
        $sKey        = static::compileListCacheKey($iUserId, $iPage);
        if (!$bHasInRedis = $redis->exists($sKey)) {
            static::createListCache($iUserId, $iPage);
        }
        $aTracesFromRedis = $redis->lrange($sKey, 0, $redis->llen($sKey) - 1);
        $aTraces          = [];
        foreach ($aTracesFromRedis as $sTrace) {
            $obj       = new static;
            $obj       = $obj->newFromBuilder(json_decode($sTrace, true));
            $aTraces[] = $obj;
        }
        unset($aTracesFromRedis, $obj, $sKey, $redis);
//        pr($aTraces);
//        exit;
        return $aTraces;
    }

    public static function & getLatestRecords($iUserId = null, $iCount = 10) {
        $aTraces      = $aFirstTraces = & static::getListOfPage($iUserId, 1) ? array_slice($aFirstTraces, 0, $iCount) : [];
        return $aTraces;
    }

    public static function _getLatestRecords($iUserId = null, $iCount = 4) {
        $redis       = Redis::connection();
        $sKey        = static::compileListCacheKey($iUserId);
        if ($bHasInRedis = $redis->exists($sKey)) {
            $aTracesFromRedis = $redis->lrange($sKey, 0, $iCount - 1);
//            pr($aTracesFromRedis);
            $iNeedCount       = $iCount - count($aTracesFromRedis);
            foreach ($aTracesFromRedis as $sInfo) {
                $obj       = new static;
                $obj       = $obj->newFromBuilder(json_decode($sInfo, true));
                $aTraces[] = $obj;
            }
            unset($obj);
        } else {
            $iNeedCount = $iCount;
            $aTraces    = [];
        }

        if (!$bHasInRedis || $iNeedCount > 0) {
            $aColumns    = ['id', 'lottery_id', 'status', 'updated_at', 'total_issues', 'finished_issues'];
            $oQuery      = static::where('user_id', '=', $iUserId);
            $aMoreTraces = isset($oQuery) ? $oQuery->orderBy('bought_at', 'desc')->limit($iNeedCount)->get($aColumns) : [];
            foreach ($aMoreTraces as $oMoreTrace) {
                $aTraces[] = $oMoreTrace;
                $redis->rpush($sKey, json_encode($oMoreTrace->toArray()));
            }
        }
//        pr($aTraces);
//        exit;
        return $aTraces;
    }

//    public static function getLatestRecords($iCount = 4) {
//        $aColumns = ['id', 'lottery_id', 'status', 'updated_at', 'total_issues', 'finished_issues'];
//        $iUserId = Session::get('user_id');
//        $oQuery = static::where('user_id', '=', $iUserId);
//        $aTraces = isset($oQuery) ? $oQuery->orderBy('updated_at', 'desc')->limit($iCount)->get($aColumns) : [];
//        return $aTraces;
//    }

    /**
     * 生成追号任务属性数组
     * TODO generate user_parent_id
     * @param User $oUser
     * @param array $aTrace
     * @param SeriesWay $oSeriesWay
     * @param Lottery $oLottery
     * @param bool $bStopOnPrized
     * @return array &
     */
    public static function & compileTraceData($oUser, $aTrace, $oSeriesWay, $oLottery, $bStopOnPrized, $aExtraData = [], $oBetTime = null) {
        $fSingleAmount = $aTrace['bet']['single_count'] * $oSeriesWay->price * $aTrace['bet']['coefficient'];
        $aIssues       = array_keys($aTrace['issues']);
        sort($aIssues);
        $oBetTime or $oBetTime      = Carbon::now();
        $data          = [
            'terminal_id'         => Session::get('terminal_id'),
            'user_id'             => $oUser->id,
            'username'            => $oUser->username,
            'account_id'          => $oUser->account_id,
            'user_forefather_ids' => $oUser->forefather_ids,
            'is_tester'           => $oUser->is_tester,
            'prize_group'         => $aTrace['bet']['prize_group'],
            'prize_set'           => $aTrace['bet']['prize_set'],
            'total_issues'        => count($aTrace['issues']),
            'title'               => $oSeriesWay->name,
            'position'            => $aTrace['bet']['position'],
            'way_total_count'     => $oSeriesWay->total_number_count,
            'single_count'        => $aTrace['bet']['single_count'],
            'bet_rate'            => $aTrace['bet']['single_count'] / $oSeriesWay->total_number_count,
            'bet_number'          => $aTrace['bet']['bet_number'],
            'display_bet_number'  => isset($aTrace['bet']['display_bet_number']) ? $aTrace['bet']['display_bet_number'] : $aTrace['bet']['bet_number'],
            'note'                => '',
            'lottery_id'          => $oLottery->id,
            'start_issue'         => array_shift($aIssues),
            'way_id'              => $oSeriesWay->id,
            'coefficient'         => $aTrace['bet']['coefficient'],
            'single_amount'       => $fSingleAmount,
            'amount'              => $fSingleAmount * array_sum($aTrace['issues']),
            'status'              => UserTrace::STATUS_RUNNING,
            'stop_on_won'         => (bool) $bStopOnPrized,
            'ip'                  => $aExtraData['clientIP'],
            'proxy_ip'            => $aExtraData['proxyIP'],
            'bought_at'           => $oBetTime->toDateTimeString(),
//            'series_number' => UserProject::makeSeriesNumber(Session::get('user_id'))
        ];
//        pr($data);
//        exit;
        return $data;
    }

    public static function createTrace($oUser, $oAccount, $aTrace, $oSeriesWay, $oLottery, $bStopOnPrized, $aExtraData = [], $oBetTime = null, & $oFirstProject) {
        $aTraceAttributes = UserTrace::compileTraceData($oUser, $aTrace, $oSeriesWay, $oLottery, $bStopOnPrized, $aExtraData, $oBetTime);
        $oTrace           = new UserTrace($aTraceAttributes);
        $oTrace->setUser($oUser);
        $oTrace->setAccount($oAccount);
        $iReturn          = $oTrace->addTrace($aTrace['issues'], $aTrace['end_times'], $oFirstProject, $oBetTime);
        if ($iReturn < 0) {
            return $iReturn;
        }
        return $oTrace;
    }

}
