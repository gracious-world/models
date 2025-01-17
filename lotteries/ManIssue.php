<?php

/**
 * 奖期管理模型
 */
use Illuminate\Support\Facades\Redis;

class ManIssue extends Issue {

    public static $cacheLevel             = self::CACHE_LEVEL_FIRST;
    protected static $cacheUseParentClass = true;
    public static $columnForList          = [
        'lottery_id',
        'issue',
        'begin_time',
        'end_time',
        'offical_time',
        'wn_number',
        'encoder',
        'encoded_at',
        'status',
        'status_count',
        'status_prize',
        'status_commission',
        'status_trace_prj',
        'status_withdrawalable_set',
    ];

    const ISSUE_OPERATE_TYPE_REVISE   = 1;
    const ISSUE_OPERATE_TYPE_CANCEL   = 2;
    const ISSUE_OPERATE_TYPE_ADVANCED = 3;

    public static $aIssueOperateType = [
        self::ISSUE_OPERATE_TYPE_REVISE   => 'revise',
        self::ISSUE_OPERATE_TYPE_CANCEL   => 'cancel',
        self::ISSUE_OPERATE_TYPE_ADVANCED => 'advanced',
    ];
    public static $weightFields      = [
        'wn_number',
    ];

    /**
     * 视图显示时使用，用于某些列有特定格式，且定义了虚拟列的情况
     * @var array
     */
    public static $listColumnMaps = [
        'status_count'              => 'formatted_status_count',
        'status'                    => 'formatted_status',
        'begin_time'                => 'formatted_begin_time',
        'end_time'                  => 'formatted_end_time',
        'offical_time'              => 'formatted_offical_time',
        'encoded_at'                => 'formatted_encoded_at',
        'status_prize'              => 'formatted_status_prize',
        'status_commission'         => 'formatted_status_commission',
        'status_trace_prj'          => 'formatted_status_trace_prj',
        'status_withdrawalable_set' => 'formatted_status_withdrawalable_set',
    ];

    /**
     * 视图显示时使用，用于某些列有特定格式，且定义了虚拟列的情况
     * @var array
     */
    public static $viewColumnMaps      = [
        'status_count'              => 'formatted_status_count',
        'status'                    => 'formatted_status',
        'begin_time'                => 'formatted_begin_time',
        'end_time'                  => 'formatted_end_time',
        'offical_time'              => 'formatted_offical_time',
        'allow_encode_time'         => 'formatted_allow_encode_time',
        'status_prize'              => 'formatted_status_prize',
        'status_commission'         => 'formatted_status_commission',
        'status_trace_prj'          => 'formatted_status_trace_prj',
        'status_withdrawalable_set' => 'formatted_status_withdrawalable_set',
    ];
    public static $ignoreColumnsInView = [
        'end_time2',
        'locker',
        'locker_fund',
    ];
    protected $fillable                = [
        'lottery_id',
        'issue',
        'issue_rule_id',
        'begin_time',
        'offical_time',
        'end_time',
        'end_time2',
        'cycle',
        'wn_number',
        'allow_encode_time',
        'encoder_id',
        'encoder',
        'encoded_at',
        'tag',
        'locker',
        'locker_fund',
        'calculated_at',
        'prize_sent_at',
        'commission_sent_at',
        'prj_created_at',
        'status',
        'status_count',
        'status_prize',
        'status_commission',
        'status_trace_prj',
        'status_withdrawalable_set',
        'created_at',
        'updated_at',
    ];

    /**
     * order by config
     * @var array
     */
    public $orderColumns = [
        'lottery_id' => 'asc',
        'issue'      => 'asc'
    ];

    /**
     * the main param for index page
     * @var string
     */
    public static $mainParamColumn = 'lottery_id';

    /**
     * 下拉列表框字段配置
     * @var array
     */
    public static $htmlSelectColumns = [
        'lottery_id' => 'aLotteries',
        'status'     => 'aWnNumberStatus'
    ];

    /**
     * 获取最后一期已经存在的奖期
     * @param int $iLotteryId 彩种id
     */
    public function getLastIssueInfo($iLotteryId, $mBeforeTime = null) {
        $oQuery = DB::table($this->table)->where('lottery_id', '=', $iLotteryId);
        !$mBeforeTime or $oQuery = $oQuery->where('end_time', '<', $mBeforeTime);
        $aIssue = $oQuery->orderBy('end_time', 'desc')->take(1)->get();
        return count($aIssue) > 0 ? objectToArray($aIssue[0]) : '';
    }

    /**
     * 返回指定时间之前的最后一期奖期号
     *
     * @param int $iLotteryId
     * @param int or string $mBeforeTime time int or datetime
     * @return type
     */
    function getLastIssue($iLotteryId, $mBeforeTime = null) {
        $aInfo = $this->getLastIssueInfo($iLotteryId, $mBeforeTime);
        return $aInfo ? $aInfo['issue'] : '';
    }

    /**
     * 获取下一期奖期
     *
     * @param string $sIssueRule 	奖期规则
     * @param string $sLastIssue	 上一期奖期
     * @param string $iStarDate	 	奖期开始时间
     * @return string				下一期奖期
     */
    public static function getNextIssue($sIssueRule, $sLastIssue = null, $iStarDate, $bIsLow = false) {
        $sIssue            = str_replace('(M)', date('m', $iStarDate), $sIssueRule);
        $sIssue            = str_replace('(D)', date('d', $iStarDate), $sIssue);
        preg_match_all("/\([N,T,C](.*)\)/", $sIssue, $aIssueOrder);
        $iIssueOrderLength = $aIssueOrder[1][0];
        if ($bIsLow) {
            $bAccumulatingOfYear = strpos($sIssueRule, 'T') !== false;
            if ($bAccumulatingOfYear) {
                $iOldYear = date('Y', $iStarDate);
                date('md', $iStarDate) > '0101' or $iOldYear--;
                $iNewYear = date('Y', $iStarDate + 3600 * 24);
                if ($iNewYear > $iOldYear) {
                    $iYear           = $iNewYear;
                    $sNextIssueOrder = 1;
                }
                else {
                    $iYear           = $iOldYear;
                    $sLastIssueOrder = substr($sLastIssue, strlen($sLastIssue) - $iIssueOrderLength, $iIssueOrderLength);
                    $sNextIssueOrder = $sLastIssueOrder + 1;
                }
            }
        }
        else {
            $iYear           = date('Y', $iStarDate);
            $sLastIssueOrder = substr($sLastIssue, strlen($sLastIssue) - $iIssueOrderLength, $iIssueOrderLength);
            $sNextIssueOrder = $sLastIssueOrder + 1;
        }
        $sIssue = str_replace('(Y)', $iYear, $sIssue);
        $sIssue = str_replace('(y)', substr($iYear, 2), $sIssue);
//        $sNextIssueOrder = (string) $sNextIssueOrder;

        $sNextIssueOrder = str_pad($sNextIssueOrder, $iIssueOrderLength, 0, STR_PAD_LEFT);
//        pr($sNextIssueOrder);
        return preg_replace("/\([N,T,C](.*)\)/", $sNextIssueOrder, $sIssue);
    }

    public static function compileIssueNumber($sIssueRule, $sLastIssue = null, $iStarDate, $bIsLow = false) {
        $sIssue            = str_replace('(M)', date('m', $iStarDate), $sIssueRule);
        $sIssue            = str_replace('(D)', date('d', $iStarDate), $sIssue);
        preg_match_all("/\([N,T,C](.*)\)/", $sIssue, $aIssueOrder);
        $iIssueOrderLength = $aIssueOrder[1][0];
        if ($bIsLow) {
            $bAccumulatingOfYear = strpos($sIssueRule, 'T') !== false;
            if ($bAccumulatingOfYear) {
                $iOldYear = date('Y', $iStarDate);
                date('md', $iStarDate) > '0101' or $iOldYear--;
                $iNewYear = date('Y', $iStarDate + 3600 * 24);
                if ($iNewYear > $iOldYear) {
                    $iYear           = $iNewYear;
                    $sNextIssueOrder = 1;
                }
                else {
                    $iYear           = $iOldYear;
                    $sLastIssueOrder = substr($sLastIssue, strlen($sLastIssue) - $iIssueOrderLength, $iIssueOrderLength);
                    $sNextIssueOrder = $sLastIssueOrder + 1;
                }
            }
        }
        else {
            $iYear           = date('Y', $iStarDate);
            $sLastIssueOrder = substr($sLastIssue, strlen($sLastIssue) - $iIssueOrderLength, $iIssueOrderLength);
            $sNextIssueOrder = $sLastIssueOrder + 1;
        }
        $sIssue = str_replace('(Y)', $iYear, $sIssue);
        $sIssue = str_replace('(y)', substr($iYear, 2), $sIssue);
//        $sNextIssueOrder = (string) $sNextIssueOrder;

        $sNextIssueOrder = str_pad($sNextIssueOrder, $iIssueOrderLength, 0, STR_PAD_LEFT);
//        pr($sNextIssueOrder);
        return preg_replace("/\([N,T,C](.*)\)/", $sNextIssueOrder, $sIssue);
    }

    /**
     * 获取期号中的日期标记信息
     *
     * @param 	string $sIssueRule    			奖期规则
     * @param 	string $sIssue					指定奖期
     * @param   int    $sDate					检测时间
     * @return 	string $sIssueDateMessage		期号中的日期标记信息
     */
    function getIssueDateMessage($sIssueRule, $sIssue = '', $sDate = '') {
        $sIssueDateMessage  = '';
        $iDateMessageLength = 0;
        strpos($sIssueRule, 'Y') === false or $iDateMessageLength += 4;
        strpos($sIssueRule, 'y') === false or $iDateMessageLength += 2;
        strpos($sIssueRule, 'M') === false or $iDateMessageLength += 2;
        strpos($sIssueRule, 'D') === false or $iDateMessageLength += 2;
        if ($sDate) {
            $iDate             = strtotime($sDate);
            $sIssueDateMessage = str_replace('(Y)', date('Y', $iDate), $sIssueRule);
            $sIssueDateMessage = str_replace('(y)', date('y', $iDate), $sIssueDateMessage);
            $sIssueDateMessage = str_replace('(M)', date('m', $iDate), $sIssueDateMessage);
            $sIssueDateMessage = str_replace('(D)', date('d', $iDate), $sIssueDateMessage);
            $sIssueDateMessage = substr($sIssueDateMessage, 0, $iDateMessageLength);
        }
        elseif ($sIssue) {
            $sIssueDateMessage = substr($sIssue, 0, $iDateMessageLength);
        }
        return $sIssueDateMessage;
    }

    /**
     * 保存奖期，用于奖期生成程序
     * @param array $aData
     * @return bool
     */
    public static function saveAllIssues($aData) {
        return static::insert($aData);
    }

    function findAllIssuesByCond($iLotteryId, $iBeginTime, $iEndTime) {
        return static::where('lottery_id', $iLotteryId)->where('end_time', '>=', $iBeginTime)->where('end_time', '<=', $iEndTime)->orderBy('end_time', 'asc')->get();
    }

    function deleteAll($aConditions) {
        $oQuery       = $this->doWhere($aConditions);
        $oQueryProfit = IssueProfit::doWhere($aConditions);
        return $oQuery->delete() && $oQueryProfit->delete();
    }

    function field($sField, $aConditions) {
        $oModels = $this->doWhere($aConditions)->get([$sField]);
        return ($oModels != null && isset($oModels[0])) ? $oModels[0]->$sField : null;
    }

    /**
     * 返回第一个没有号码的奖期对象
     * @param int $iLotteryId
     * @return Issue
     */
    public static function getFirstNonNumberIssue($iLotteryId) {
        $time  = date('Y-m-d H:i:s', strtotime('-7 days'));
        $time2 = date('Y-m-d H:i:s');
//        return static::whereBetween('end_time2',[$time,$time2])
        return static::whereBetween('end_time2', [$time, $time2])
                ->where('lottery_id', '=', $iLotteryId)
                ->where('allow_encode_time', '<', time())
                ->where('status', '=', self::ISSUE_CODE_STATUS_WAIT_CODE)
                ->orderBy('issue', 'asc')
                ->first();
    }

    /**
     * 返回最早的没有号码的奖期对象集
     * @param int $iLotteryId
     * @param int $iCount 数量
     * @return Collection
     */
    public static function getNonNumberIssues($iLotteryId, $iCount) {
        $time  = date('Y-m-d H:i:s', strtotime('-7 days'));
        $time2 = date('Y-m-d H:i:s');
//        return static::whereBetween('end_time2',[$time,$time2])
        return static::whereBetween('end_time2', [$time, $time2])
                ->where('lottery_id', '=', $iLotteryId)
                ->where('allow_encode_time', '<', time())
                ->where('status', '=', self::ISSUE_CODE_STATUS_WAIT_CODE)
                ->orderBy('issue', 'asc')
                ->take($iCount)->get();
    }

    public function setCalulated($bFinished = true) {
        $iToStatus = $bFinished ? self::CALCULATE_FINISHED : self::CALCULATE_PARTIAL;
        $iCount    = $this->where('id', '=', $this->id)->where('status_count', '=', self::CALCULATE_PROCESSING)->update(['status_count' => $iToStatus]);
        if ($bSucc     = $iCount > 0) {
            $this->status_count = $iToStatus;
        }
        return $bSucc;
    }

    public function SetWithdrawalableSeted($bFinished = true, $bForce = false) {
        $iToStatus = $bFinished ? self::WITHDRAWABLE_SET_FINISHED : self::WITHDRAWABLE_SET_PARTIAL;
        $oQuery    = $this->where('id', '=', $this->id);
        $bForce or $oQuery    = $oQuery->where('status_withdrawalable_set', '=', self::WITHDRAWABLE_SET_PROCESSING);
        $iCount    = $oQuery->update(['status_withdrawalable_set' => $iToStatus]);
        if ($bSucc     = $iCount > 0) {
            $this->status_withdrawalable_set = $iToStatus;
        }
        return $bSucc;
    }

    public function setPrizeFinishStatus($bFinished = true) {
        $iToStatus   = $bFinished ? self::PRIZE_FINISHED : self::PRIZE_PARTIAL;
        $aFromStatus = [self::PRIZE_PROCESSING, self::PRIZE_PARTIAL];
        $iCount      = $this->where('id', '=', $this->id)->whereIn('status_prize', $aFromStatus)->update(['status_prize' => $iToStatus]);
        if ($bSucc       = $iCount > 0) {
            $this->status_prize = $iToStatus;
        }
        return $bSucc;
    }

    public function setCommissionFinishStatus($bFinished = true) {
        $iToStatus   = $bFinished ? self::COMMISSION_FINISHED : self::COMMISSION_PARTIAL;
        $aFromStatus = [self::COMMISSION_PROCESSING, self::COMMISSION_PARTIAL];
        $iCount      = $this->where('id', '=', $this->id)->whereIn('status_commission', $aFromStatus)->update(['status_commission' => $iToStatus]);
        if ($bSucc       = $iCount > 0) {
            $this->status_commission = $iToStatus;
        }
        return $bSucc;
    }

    public function setTracePrjFinishStatus($bFinished = true) {
        $iToStatus   = $bFinished ? self::TRACE_PRJ_FINISHED : self::TRACE_PRJ_PARTIAL;
        $aFromStatus = [self::TRACE_PRJ_NONE, self::TRACE_PRJ_PROCESSING, self::TRACE_PRJ_PARTIAL];
        $iCount      = $this->where('id', '=', $this->id)->whereIn('status_trace_prj', $aFromStatus)->update(['status_trace_prj' => $iToStatus]);
        if ($bSucc       = $iCount > 0) {
            $this->status_trace_prj = $iToStatus;
        }
        return $bSucc;
    }

    public function setPrizeProcessing() {
        if ($bSucc = $this->where('id', '=', $this->id)->where('status_prize', '=', self::PRIZE_NONE)->update(['status_prize' => self::PRIZE_PROCESSING]) > 0) {
            $this->status_prize = self::PRIZE_PROCESSING;
        }
        return $bSucc;
    }

    public function setCommissionProcessing() {
        if ($bSucc = $this->where('id', '=', $this->id)->where('status_commission', '=', self::COMMISSION_NONE)->update(['status_commission' => self::COMMISSION_PROCESSING]) > 0) {
            $this->status_commission = self::COMMISSION_PROCESSING;
        }
        return $bSucc;
    }

    public function setTracePrjProcessing() {
        if ($bSucc = $this->where('id', '=', $this->id)->where('status_trace_prj', '=', self::TRACE_PRJ_NONE)->update(['status_trace_prj' => self::TRACE_PRJ_PROCESSING]) > 0) {
            $this->status_trace_prj = self::TRACE_PRJ_PROCESSING;
        }
        return $bSucc;
    }

    public static function getIssueObject($iLotteryId, $sIssue) {
        $aConditions = [
            'lottery_id' => ['=', $iLotteryId],
            'issue'      => ['=', $sIssue],
        ];
        return static::doWhere($aConditions)->get()->first();
    }

    /**
     * 将计奖状态写为CALCULATE_PROCESSING
     * @return bool
     */
    public function lockCalculate() {
        $aConditions = [
            'id'           => ['=', $this->id],
//            'wn_number' => [ '<>', ''],
            'status'       => ['=', self::ISSUE_CODE_STATUS_FINISHED],
            'status_count' => ['in', [self::CALCULATE_NONE, self::CALCULATE_PARTIAL]],
            'end_time'     => ['<', time()]
        ];
        $data        = [
            'status_count' => self::CALCULATE_PROCESSING,
            'locker'       => $iLocker       = DbTool::getDbThreadId()
        ];
//        if ($bSucc = self::doWhere($aConditions)->update($data) > 0) {
        if ($bSucc       = $this->strictUpdate($aConditions, $data)) {
            $this->status_count = self::CALCULATE_PROCESSING;
            $this->locker       = $iLocker;
        }
        return $bSucc;
    }

    /**
     * 将提现设置状态写为WITHDRAWABLE_SET_PROCESSING
     * @return bool
     */
    public function lockWithdrawSet() {
        $aConditions = [
            'id'                        => ['=', $this->id],
            'status_count'              => ['=', self::CALCULATE_FINISHED],
            'status_withdrawalable_set' => ['in', [self::WITHDRAWABLE_SET_NONE, self::WITHDRAWABLE_SET_PARTIAL]],
            'end_time'                  => ['<', time()]
        ];
        $data        = [
            'status_withdrawalable_set' => self::WITHDRAWABLE_SET_PROCESSING,
            'locker_fund'               => $iLocker                    = DbTool::getDbThreadId()
        ];
        if ($bSucc       = $this->strictUpdate($aConditions, $data)) {
            $this->status_withdrawalable_set = self::WITHDRAWABLE_SET_PROCESSING;
            $this->locker                    = $iLocker;
        }
        return $bSucc;
    }

    /**
     * 将计奖状态由CALCULATE_PROCESSING改为CALCULATE_NONE
     * @param int $iLotteryId
     * @param string $sIssue
     * @return Issue|false
     */
    public static function unlockCalculate($iLotteryId, $sIssue, $iLocker, $bReturnObject = true) {
        $oIssue      = self::getIssue($iLotteryId, $sIssue);
        $aConditions = [
            'lottery_id'   => ['=', $iLotteryId],
            'issue'        => ['=', $sIssue],
            'status'       => ['=', self::ISSUE_CODE_STATUS_FINISHED],
            'status_count' => ['=', self::CALCULATE_PROCESSING],
            'locker'       => ['=', $iLocker],
        ];
//        pr($aConditions);
        $data        = [
            'status_count' => self::CALCULATE_NONE,
            'locker'       => 0,
        ];
        if ($bSucc       = $oIssue->strictUpdate($aConditions, $data)) {
            return $bReturnObject ? self::find($oIssue->id) : true;
        }
        return false;
    }

    /**
     * 将提现设置状态由 WITHDRAWABLE_SET_PROCESSING 改为 WITHDRAWABLE_SET_NONE
     * @param int $iLotteryId
     * @param string $sIssue
     * @return Issue|false
     */
    public static function unlockWithdrawSet($iLotteryId, $sIssue, $iLocker, $bReturnObject = true) {
        $oIssue      = self::getIssue($iLotteryId, $sIssue);
        $aConditions = [
            'lottery_id'                => ['=', $iLotteryId],
            'issue'                     => ['=', $sIssue],
            'status_count'              => ['=', self::CALCULATE_FINISHED],
            'status_withdrawalable_set' => ['=', self::WITHDRAWABLE_SET_PROCESSING],
            'locker_fund'               => ['=', $iLocker],
        ];
//        pr($aConditions);
        $data        = [
            'status_withdrawalable_set' => self::WITHDRAWABLE_SET_NONE,
            'locker_fund'               => 0,
        ];
        if ($bSucc       = $oIssue->strictUpdate($aConditions, $data)) {
            return $bReturnObject ? self::find($oIssue->id) : true;
        }
        return false;
    }

    public function unlockCalculateEx() {
        $aConditions = [
            'id'           => ['=', $this->id],
            'status'       => ['=', self::ISSUE_CODE_STATUS_FINISHED],
            'status_count' => ['=', self::CALCULATE_PROCESSING],
            'locker'       => ['=', $this->locker],
        ];
        $data        = [
            'status_count' => self::CALCULATE_NONE,
            'locker'       => 0,
        ];
        return $this->strictUpdate($aConditions, $data);
    }

    /**
     * 设置中奖号码
     * @param string $sWinningNumber
     * @param CodeCenter $oCodeCenter
     * @return boolean
     */
    public function setWinningNumber($sWinningNumber, $oCodeCenter = null, $bGet = false) {
        if (time() < $this->allow_encode_time) {
            return -1;
        }
        $aConditions = [
            'id'        => ['=', $this->id],
            'wn_number' => ['=', ''],
            'status'    => ['=', self::ISSUE_CODE_STATUS_WAIT_CODE],
        ];
        if ($oCodeCenter) {
            $sEncoder = $oCodeCenter->name;
            !$bGet or $sEncoder .= '[get]';
        }
        else {
            $sEncoder = Session::get('admin_username');
        }
        $data  = [
            'wn_number'  => $sWinningNumber,
            'status'     => self::ISSUE_CODE_STATUS_FINISHED,
            'encoded_at' => Carbon::now()->toDateTimeString(),
            'encoder_id' => $oCodeCenter ? 60000 + $oCodeCenter->id : Session::get('admin_user_id'),
            'encoder'    => $sEncoder
        ];
//        pr($data);
//        exit;
//        $iCount = static::where('id', '=', $this->id)->where('wn_number', '=', '')->where('status', '=', self::ISSUE_CODE_STATUS_WAIT_CODE)->update($data);
        if ($bSucc = $this->strictUpdate($aConditions, $data)) {
//            $this->fill($data);
            foreach ($data as $key => $value) {
                $this->$key = $value;
            }
            $this->updateCaches();
        }
        return $bSucc;
    }

    protected function updateCaches() {
        $this->updateRecentIssuesList();
        $this->updateWnNumberCache();
//            $this->updateFutureIssueList();
//            $this->updateRecentWnNumbersList();
    }

    public function setTag($sTag) {
        if ($bSucc = $this->update(['tag' => $sTag])) {
            $this->tag = $sTag;
        }
        return $bSucc;
    }

    protected function getFormattedStatusCountAttribute() {
        return __('_issue.' . strtolower(Str::slug(static::$calculateStatus[$this->attributes['status_count']])));
    }

    protected function getFormattedStatusPrizeAttribute() {
        return __('_issue.' . strtolower(Str::slug(static::$prizeStatus[$this->attributes['status_prize']])));
    }

    protected function getFormattedStatusCommissionAttribute() {
        return __('_issue.' . strtolower(Str::slug(static::$commissionStatus[$this->attributes['status_commission']])));
    }

    protected function getFormattedStatusTracePrjAttribute() {
        return __('_issue.' . strtolower(Str::slug(static::$tracePrjStatus[$this->attributes['status_trace_prj']])));
    }

    protected function getFormattedStatusWithdrawalableSetAttribute() {
        return __('_issue.' . strtolower(Str::slug(static::$withdrawableSetStatus[$this->attributes['status_withdrawalable_set']])));
    }

    protected function getFormattedStatusAttribute() {
        return __('_issue.' . strtolower(Str::slug(static::$winningNumberStatus[$this->attributes['status']])));
    }

    protected function getFormattedBeginTimeAttribute() {
        $oCarbon = Carbon::createFromTimestamp($this->attributes['begin_time']);
        $oCarbon->setToStringFormat('m-d H:i:s');
        return $oCarbon->__toString();
    }

    protected function getFormattedEncodedAtAttribute() {
        return substr($this->attributes['encoded_at'], 5);
    }

    protected function getFormattedEndTimeAttribute() {
        $oCarbon = Carbon::createFromTimestamp($this->attributes['end_time']);
        $oCarbon->setToStringFormat('m-d H:i:s');
        return $oCarbon->__toString();
    }

    protected function getFormattedOfficalTimeAttribute() {
        $oCarbon = Carbon::createFromTimestamp($this->attributes['offical_time']);
        $oCarbon->setToStringFormat('m-d H:i:s');
        return $oCarbon->__toString();
    }

    protected function getFormattedAllowEncodeTimeAttribute() {
        $oCarbon = Carbon::createFromTimestamp($this->attributes['allow_encode_time']);
        $oCarbon->setToStringFormat('m-d H:i:s');
        return $oCarbon->__toString();
    }

    public function addCalculateTask(& $sRealQueue = null) {
        $aJobData = [
            'lottery_id' => $this->lottery_id,
            'issue'      => $this->issue,
        ];
        return BaseTask::addTask('CalculatePrize', $aJobData, 'calculate', 0, $sRealQueue);
    }

    public function addWithdrawableSetTask(& $sRealQueue = null) {
        $aJobData = [
            'lottery_id' => $this->lottery_id,
            'issue'      => $this->issue,
        ];
        return BaseTask::addTask('SetWithdrawable', $aJobData, 'withdraw', 0, $sRealQueue);
    }

    /**
     * 发起计奖任务
     * @return bool
     */
    public function setCalculateTask(& $sRealQueue = null) {
        $aConditions = [
            'id'     => ['=', $this->id],
            'status' => ['=', self::ISSUE_CODE_STATUS_FINISHED],
//            'status_count' => ['<>',self::CALCULATE_PROCESSING]
        ];
        if ($bSucc       = $this->strictUpdate($aConditions, ['status_count' => self::CALCULATE_NONE])) {
//            $aJobData = [
//                'lottery_id' => $this->lottery_id,
//                'issue' => $this->issue,
//            ];
            $bSucc = $this->addCalculateTask($sRealQueue);
        }
        return $bSucc;
    }

    /**
     * 发起计奖任务
     * @return bool
     */
    public function setWithdrawableSetTask(& $sRealQueue = null) {
        $aConditions = [
            'id'           => ['=', $this->id],
            'status_count' => ['=', self::CALCULATE_FINISHED]
        ];
        if ($bSucc       = $this->strictUpdate($aConditions, ['status_withdrawalable_set' => self::WITHDRAWABLE_SET_NONE])) {
//            $aJobData = [
//                'lottery_id' => $this->lottery_id,
//                'issue' => $this->issue,
//            ];
            $bSucc = $this->addWithdrawableSetTask($sRealQueue);
        }
        return $bSucc;
    }

    /**
     * 发起未开奖撤单任务
     * @return bool
     */
    public function setCancelTask($sBeginTime = null) {
        if ($this->status == self::ISSUE_CODE_STATUS_CANCELED) {
            $bSucc = true;
        }
        else {
            $aConditions = [
                'id' => ['=', $this->id],
            ];
            if ($sBeginTime == null) {
                $aConditions['status'] = ['=', self::ISSUE_CODE_STATUS_WAIT_CODE];
            }
            else {
                $aConditions['status'] = ['in', [self::ISSUE_CODE_STATUS_WAIT_CODE, self::ISSUE_CODE_STATUS_FINISHED]];
            }
            $data  = [
                'status'       => self::ISSUE_CODE_STATUS_CANCELED,
                'status_count' => self::CALCULATE_FINISHED
            ];
//            $bSucc = self::doWhere($aConditions)->update($data) > 0;
            $bSucc = $this->strictUpdate($aConditions, $data);
        }
        if ($bSucc) {
            $aJobData               = [
                'lottery_id' => $this->lottery_id,
                'issue'      => $this->issue,
            ];
            $sBeginTime == null or $aJobData['begin_time'] = $sBeginTime;
            $bSucc                  = BaseTask::addTask('CancelIssue', $aJobData, 'calculate');
        }
        return $bSucc;
    }

    /**
     * 发起计奖重新开奖任务
     * @return boolean
     */
    public function setCancelPriceTask($iCodeCenterId, $sNewCode) {
        $aConditions = [
            'id'     => ['=', $this->id],
            'status' => ['=', self::ISSUE_CODE_STATUS_FINISHED],
        ];
        $data        = [
            'status' => self::ISSUE_CODE_STATUS_CANCELED,
//            'status_count' => self::CALCULATE_NONE,
        ];
//        if ($bSucc = self::doWhere($aConditions)->update($data) > 0) {
        if ($bSucc       = $this->strictUpdate($aConditions, $data)) {
            self::clearCacheByIssue($this->lottery_id, $this->issue);
            $aJobData = [
                'lottery_id'     => $this->lottery_id,
                'issue'          => $this->issue,
                'new_code'       => $sNewCode,
                'code_center_id' => $iCodeCenterId,
            ];
            $bSucc    = BaseTask::addTask('CancelPrize', $aJobData, 'calculate');
        }
        return $bSucc;
    }

    /**
     * 返回第一个在当前期以前尚未计奖完成的奖期对象
     * @param int $iLotteryId
     * @return Issue
     */
    public function getFirstUnCalculatedIssueBeforeIssue() {
        return static::where('lottery_id', '=', $this->lottery_id)->where('issue', '<', $this->issue)->where('status_count', '<>', self::CALCULATE_FINISHED)->orderBy('issue', 'asc')->limit(1)->get()->first();
    }

    public function updateWnNumberCache() {
        if (static::$cacheLevel == self::CACHE_LEVEL_NONE) {
            return true;
        }
        Cache::setDefaultDriver(static::$cacheDrivers[static::$cacheLevel]);
        $key = $this->compileLastWnNumberCacheKey($this->lottery_id);
//        $data = [
//            'issue' => $this->issue,
//            'wn_number' => $this->wn_number,
//            'offical_time' => $this->offical_time,
//        ];
        Cache::forget($key);
//        Cache::put($key, $data,1);
        return true;
    }

    /**
     * 发起计奖重新开奖任务
     * @return boolean
     */
    public function reset() {
        $aConditions = [
            'id' => ['=', $this->id],
        ];
        $data        = [
            'status'            => self::ISSUE_CODE_STATUS_WAIT_CODE,
//            'status_count' => self::CALCULATE_NONE,
            'status_prize'      => self::PRIZE_NONE,
            'status_commission' => self::COMMISSION_NONE,
            'status_trace_prj'  => self::TRACE_PRJ_NONE,
            'wn_number'         => '',
        ];
//        $bSucc = self::doWhere($aConditions)->update($data) > 0;
        $bSucc       = $this->strictUpdate($aConditions, $data);
        return $bSucc;
    }

    public static function getNeedCalculateIssues($iCount = 20, $iLotteryId = null) {
        $aConditions               = [
            'status'       => ['=', ISSUE::ISSUE_CODE_STATUS_FINISHED],
            'status_count' => ['in', [ISSUE::CALCULATE_NONE, ISSUE::CALCULATE_PARTIAL]],
        ];
        empty($iLotteryId) or $aConditions['lottery_id'] = ['=', $iLotteryId];
        return static::dowhere($aConditions)->orderBy('lottery_id', 'asc')->orderBy('issue', 'asc')->limit($iCount)->get(['id', 'lottery_id', 'issue']);
    }

    public static function generateIssues($oLottery, $sBeginDate = null, $sEndDate = null, $iLastEndTime = null, $sExistsLastIssue = null, $sBeginIssue = null, & $iCount) {
        $bAccumulating = $oLottery->isAccumulating();
        $iBeginDate    = strtotime($sBeginDate);
        $iEndDate      = strtotime($sEndDate);
        $oIssueRules   = IssueRule::getIssueRulesOfLottery($oLottery->id);
        $sLastIssue    = $sExistsLastIssue;
        $sFunction     = $oLottery->high_frequency ? '_generateIssuesForHigh' : '_generateIssuesForLow';
        return static::$sFunction($oLottery, $oIssueRules, $bAccumulating, $iBeginDate, $iEndDate, $sLastIssue, $iLastEndTime, $iCount);
    }

    private static function _generateIssuesForHigh($oLottery, $oIssueRules, $bAccumulating, $iBeginDate, $iEndDate, $sLastIssue, $iLastEndTime, & $iCount) {
        $aRestSettings = RestSetting::getRestSettings($oLottery->id);
        $bSucc         = true;
        for ($i = 0, $iDate = $iBeginDate; $iDate <= $iEndDate; $i++, $iDate += 3600 * 24) {
            $sDate = date('Y-m-d', $iDate);
            foreach ($aRestSettings as $aRest) {
                if ($aRest['periodic']) {
                    continue;
                }
                if ($sDate >= $aRest['begin_date'] && $sDate <= $aRest['end_date']) {
                    continue 2;
                }
            }
            unset($aRest);
            $aIssues       = [];
            $aIssueProfits = [];
            $j             = 0;
            foreach ($oIssueRules as $oIssueRule) {
                $iLastEndTime or $iLastEndTime       = strtotime($sDate . ' ' . $oIssueRule->begin_time);
                $sFirstIssueEndTime = $sDate . $oIssueRule->first_time;
                $iFirstIssueEndTime = strtotime($sFirstIssueEndTime);
                $iStopTime          = strtotime($sDate . ' ' . $oIssueRule->end_time);
                $iStopTime > $iFirstIssueEndTime or $iStopTime          += 3600 * 24;
//                pr($iFirstIssueEndTime);
//                pr($iStopTime);
//                exit;
                for ($iTime = $iFirstIssueEndTime; $iTime <= $iStopTime; $iTime += $oIssueRule->cycle + $oIssueRule->number_delay_time) {
                    $iEndTime = $iTime - $oIssueRule->stop_adjust_time;
//                    $sIssue = $sLastIssue = static::getNextIssue($oLottery->issue_format, $sLastIssue, $iDate, false);

                    $iYear             = date('Y', $iDate);
                    $sIssue            = str_replace('(M)', date('m', $iDate), $oLottery->issue_format);
                    $sIssue            = str_replace('(D)', date('d', $iDate), $sIssue);
                    $sIssue            = str_replace('(Y)', $iYear, $sIssue);
                    $sIssue            = str_replace('(y)', substr($iYear, 2), $sIssue);
                    preg_match_all("/\([N,T,C](.*)\)/", $sIssue, $aIssueOrder);
                    $iIssueOrderLength = $aIssueOrder[1][0];
//                    pr($sExistsLastIssue);
                    if ($bAccumulating) {
                        $sLastIssueOrder = $sLastIssue;
                    }
                    else {
                        $sLastIssueOrder = ($j == 0 && $iTime == $iFirstIssueEndTime) ? 0 : substr($sLastIssue, - $iIssueOrderLength);
                    }
                    $sNextIssueOrder = $sLastIssueOrder + 1;

                    $sNextIssueOrder = str_pad($sNextIssueOrder, $iIssueOrderLength, 0, STR_PAD_LEFT);
                    //        pr($sNextIssueOrder);
                    $sIssue          = $sLastIssue      = preg_replace("/\([N,T,C](.*)\)/", $sNextIssueOrder, $sIssue);

                    $aIssues[]       = [
                        'lottery_id'        => $oLottery->id,
                        'issue'             => $sIssue,
                        'issue_rule_id'     => $oIssueRule->id,
                        'begin_time'        => $iLastEndTime + $oIssueRule->number_delay_time,
                        'end_time'          => $iEndTime,
                        'end_time2'         => date('Y-m-d H:i:s', $iEndTime),
                        'offical_time'      => $iTime,
                        'cycle'             => $oIssueRule->cycle,
                        'allow_encode_time' => $iTime + $oIssueRule->encode_time,
                        'status'            => self::ISSUE_CODE_STATUS_WAIT_CODE,
                        'created_at'        => $sCreatedTime       = date('Y-m-d H:i:s'),
                        'updated_at'        => $sCreatedTime,
                    ];
                    $aIssueProfits[] = [
                        'lottery_id' => $oLottery->id,
                        'issue'      => $sIssue,
                        'end_time'   => $iEndTime,
                        'end_time2'  => date('Y-m-d H:i:s', $iEndTime),
                    ];
                    $iLastEndTime    = $iEndTime;
                }
                $j++;
            }
//            pr($aIssues);
            if (!$bSucc = static::saveAllIssues($aIssues) && IssueProfit::saveAllIssues($aIssueProfits)) {
                break;
            }
            $iCount += count($aIssues);
        }
        return $bSucc;
    }

    private static function _generateIssuesForLow($oLottery, $oIssueRules, $bAccumulating, $iBeginDate, $iEndDate, $sLastIssue, $iLastEndTime, & $iCount = null) {
//        pr(date('Y-m-d', $iEndDate));
//        $iEndDate = strtotime('2017-01-05');
        $aIssues       = [];
        $aIssueProfits = [];
        $aRestSettings = RestSetting::getRestSettings($oLottery->id);
        $bSucc         = true;
        for ($i = 0, $iDate = $iBeginDate; $iDate <= $iEndDate; $i++, $iDate += 3600 * 24) {
            $w          = date('N', $iDate);
            $iNumForDay = pow(2, $w - 1);
            $sDate      = date('Y-m-d', $iDate);
            foreach ($aRestSettings as $aRest) {
                if ($aRest['periodic']) {
                    continue;
                }
                if ($sDate >= $aRest['begin_date'] && $sDate <= $aRest['end_date']) {
                    continue 2;
                }
            }
            unset($aRest);
            if (($iNumForDay & $oLottery->days) != $iNumForDay) {
                continue;
            }
            $iEndTime          = strtotime($sDate . ' ' . $oLottery->end_time);
            $iYear             = date('Y', $iDate);
//            pr($iYear);
            $sIssue            = str_replace('(M)', date('m', $iDate), $oLottery->issue_format);
            $sIssue            = str_replace('(D)', date('d', $iDate), $sIssue);
            $sIssue            = str_replace('(Y)', $iYear, $sIssue);
            $sIssue            = str_replace('(y)', substr($iYear, 2), $sIssue);
            preg_match_all("/\([N,T,C](.*)\)/", $sIssue, $aIssueOrder);
//            pr($aIssueOrder);
            $iIssueOrderLength = $aIssueOrder[1][0];
//            pr('len: ' . $iIssueOrderLength);
//            pr('last: ' . $sLastIssue);
            if (date('md', $iDate) == '0101') {
                $sNextIssueOrder = 1;
            }
            else {
                $sLastIssueOrder = substr($sLastIssue, - $iIssueOrderLength);
//                if ($bAccumulating) {
//                } else {
//                    $sLastIssueOrder = $iTime == $iFirstIssueEndTime) ? 0 : substr($sLastIssue, - $iIssueOrderLength);
//                }
                $sNextIssueOrder = $sLastIssueOrder + 1;
            }
            $sNextIssueOrder = str_pad($sNextIssueOrder, $iIssueOrderLength, 0, STR_PAD_LEFT);

//            pr($sNextIssueOrder);
            $sIssue          = $sLastIssue      = preg_replace("/\([N,T,C](.*)\)/", $sNextIssueOrder, $sIssue);
            pr($sIssue);
//            continue;
            $aIssues[]       = [
                'lottery_id'        => $oLottery->id,
                'issue'             => $sIssue,
                'issue_rule_id'     => null,
                'begin_time'        => $iLastEndTime,
                'end_time'          => $iEndTime,
                'end_time2'         => date('Y-m-d H:i:s', $iEndTime),
                'offical_time'      => $iEndTime,
                'cycle'             => null,
                'allow_encode_time' => $iEndTime + 900,
                'status'            => self::ISSUE_CODE_STATUS_WAIT_CODE,
                'created_at'        => $sCreatedTime       = date('Y-m-d H:i:s'),
                'updated_at'        => $sCreatedTime,
            ];
            $aIssueProfits[] = [
                'lottery_id' => $oLottery->id,
                'issue'      => $sIssue,
                'end_time'   => $iEndTime,
                'end_time2'  => date('Y-m-d H:i:s', $iEndTime),
            ];
            $iLastEndTime    = $iEndTime;
        }
        if ($bSucc = static::saveAllIssues($aIssues) && IssueProfit::saveAllIssues($aIssueProfits)) {
            $iCount = count($aIssues);
        }
        return $bSucc;
    }

    public static function compileIssueNo($sIssueRule, $sLastIssueNo) {
        $sIssue = str_replace('(M)', date('m', $iStarDate), $sIssueRule);
        $sIssue = str_replace('(D)', date('d', $iStarDate), $sIssue);
        preg_match_all("/\([N,T,C](.*)\)/", $sIssue, $aIssueOrder);
    }

    public static function getLastIssueObject($iLotteryId, $iBeforeTime = null) {
        $oQuery = static::where('lottery_id', '=', $iLotteryId);
        !$iBeforeTime or $oQuery = $oQuery->where('end_time', '<', $iBeforeTime);
        return $oQuery->orderBy('end_time', 'desc')->take(1)->first();
    }

    public function updateFutureIssueList() {
        $redis     = Redis::connection();
        $sCacheKey = static::compileFutureIssuesCacheKey($this->lottery_id);
        $redis->del($sCacheKey);
    }

    public function updateRecentIssuesListRedis() {
        $redis     = Redis::connection();
        $sCacheKey = static::compileRecentIssuesCacheKey($this->lottery_id);
        $redis->del($sCacheKey);
    }

    public function updateRecentIssuesList() {
        if (static::$cacheLevel == self::CACHE_LEVEL_NONE) {
            return true;
        }
        Cache::setDefaultDriver(static::$cacheDrivers[static::$cacheLevel]);
        $sCacheKey = static::compileRecentIssuesCacheKey($this->lottery_id);
        Cache::forget($sCacheKey);
    }

    public function updateRecentWnNumbersList() {
        $redis     = Redis::connection();
        $sCacheKey = static::compileRecentWnNumbersCacheKey($this->lottery_id);
        for ($iCurrentCount = $redis->llen($sCacheKey); $iCurrentCount >= $this->maxFinishedListLength; $iCurrentCount--) {
            $redis->rpop($sCacheKey);
        }

        if (($iCurrentCount = $redis->llen($sCacheKey)) < $this->maxFinishedListLength) {
            $oMoreIssues = static::getRecentIssuesFromDb($this->lottery_id, $this->maxFinishedListLength - $iCurrentCount, true);
            static::pushToList($redis, $sCacheKey, $oMoreIssues, true);
        }
        $redis->ltrim($sCacheKey, 0, $this->maxFinishedListLength - 1);
    }

    private function removeExpiredIssuesFromFutureCache($redis, $sCacheKey, $bFromRight = false, $bExpiredByTime = false) {
        $sFunction     = $bFromRight ? 'rpop' : 'lpop';
        $sPushFunction = $bFromRight ? 'rpush' : 'lpush';
        $i             = 0;
        $aIssueInfos   = $redis->lrange($sCacheKey, 0, $redis->llen($sCacheKey) - 1);
        !$bFromRight or $aIssueInfos   = array_reverse($aIssueInfos);
        foreach ($aIssueInfos as $sIssueInfo) {
            $aIssueInfo = json_decode($sIssueInfo, true);
            if ($bExpired   = $bExpiredByTime ? $aIssueInfo['offical_time'] <= time() : $this->issue > $aIssueInfo['issue']) {
                $redis->$sFunction($sCacheKey);
            }
        }
    }

    public static function getIssueOperateTypes() {
        $aResult = [];
        foreach (static::$aIssueOperateType as $key => $val) {
            $aResult[$key] = __('_issue.' . $val);
        }
        return $aResult;
    }

}
