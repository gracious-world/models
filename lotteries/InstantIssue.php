<?php
use Illuminate\Support\Facades\Redis;

class InstantIssue extends BaseModel {

    protected static $cacheLevel = self::CACHE_LEVEL_FIRST;
    public static $resourceName = 'InstantIssue';
    protected $table = 'instant_issues';
    protected $maxFutureListLength = 10;
    protected $maxFinishedListLength = 20;
    public static $columnForList = [];
    public $orderColumns = [
        'issue' => 'asc'
    ];
    public static $mainParamColumn = 'user_id';
    public static $htmlSelectColumns = [];
    public static $rules = [
        'user_id' => 'required|integer',
        'lottery_id' => 'required|integer',
        'issue' => 'required',
    ];

    public static $customMessages = [];
    public static $titleColumn = 'issue';

    protected $fillable                   = [
        'user_id',
        'lottery_id',
        'issue',
        'wn_number',
        'encoded_at',
        'status',
        'tag',
    ];
    /**
     * 中奖号码状态：等待开奖
     */
    const ISSUE_CODE_STATUS_WAIT_CODE = 1;

    /**
     * 中奖号码状态：已输入号码，等待审核
     */
    const ISSUE_CODE_STATUS_WAIT_VERIFY = 2;

    /**
     * 中奖号码状态：号码已审核
     */
    const ISSUE_CODE_STATUS_FINISHED = 4;

    /**
     * 中奖号码状态：号码已取消开奖
     */
    const ISSUE_CODE_STATUS_CANCELED = 8;

    /**
     * 中奖号码状态：提前开奖A，获取到开奖号码的时间早于官方理论开奖时间
     */
    const ISSUE_CODE_STATUS_ADVANCE_A = 32;

    /**
     * 中奖号码状态：提前开奖B，获取到开奖号码的时间早于销售截止时间
     */
    const ISSUE_CODE_STATUS_ADVANCE_B = 64;

    /**
     * 计奖状态
     */
    const CALCULATE_NONE = 0;
    const CALCULATE_PROCESSING = 1;
    const CALCULATE_PARTIAL = 2;
    const CALCULATE_FINISHED = 4;

    /**
     * 派奖状态
     */
    const PRIZE_NONE = 0;
    const PRIZE_PROCESSING = 1;
    const PRIZE_PARTIAL = 2;
    const PRIZE_FINISHED = 4;

    /**
     * 派佣金状态
     */
    const COMMISSION_NONE = 0;
    const COMMISSION_PROCESSING = 1;
    const COMMISSION_PARTIAL = 2;
    const COMMISSION_FINISHED = 4;

    /**
     * 追号单状态
     */
    const TRACE_PRJ_NONE = 0;
    const TRACE_PRJ_PROCESSING = 1;
    const TRACE_PRJ_PARTIAL = 2;
    const TRACE_PRJ_FINISHED = 4;

    /**
     * 中奖号码状态数组
     * @var array
     */
    public static $winningNumberStatus = [
        self::ISSUE_CODE_STATUS_WAIT_CODE => 'Waiting For Number',
        self::ISSUE_CODE_STATUS_WAIT_VERIFY => 'Waiting For Verify',
        self::ISSUE_CODE_STATUS_FINISHED => 'Verified',
        self::ISSUE_CODE_STATUS_CANCELED => 'Canceled',
    ];

    const ERRNO_ISSUE_MISSING = -910;
    const ERRNO_ISSUE_EXPIRED = -911;

    /**
     * 官方未开奖时的中奖号码
     */
    const ISSUE_CODE_CANCELED = 'XXXXX';

    /**
     * 录错号标识
     */
    const ISSUE_CODE_FAIL = 'FFFFF';
    const ISSUE_CODE_CLEAR = 'CCCCC';

    public static $specialFlags = [
        self::ISSUE_CODE_CANCELED,
        self::ISSUE_CODE_FAIL
    ];

    /**
     * 获取指定游戏的奖期对象
     * @param int $iLotteryId
     * @param int $iCount
     * @param int $iBeginTime
     * @return Collection
     */
    function getIssueObjects($iUserId,$iLotteryId, $iCount = null, $iBeginTime = null, $iEndTime = null, $bOrderDesc = false) {
//        $iBeginTime or $iBeginTime = time();
        $iCount or $iCount = 120;
        $aCondtions = [
            'user_id' => ['=', $iUserId],
            'lottery_id' => ['=', $iLotteryId],
        ];
//        if ($iBeginTime || $iEndTime) {
//            if ($iBeginTime && $iEndTime) {
//                $aCondtions['end_time'] = ['between', [$iBeginTime, $iEndTime]];
//            } else {
//                $sOperator = $iBeginTime ? '>=' : '<=';
//                $iTime = $iBeginTime ? $iBeginTime : $iEndTime;
//                $aCondtions['end_time'] = [ $sOperator, $iTime];
//            }
//        }
        $aOrderBy = ['issue' => $bOrderDesc ? 'desc' : 'asc'];
        $oQuery = $this->doWhere($aCondtions);
        $oQuery = $this->doOrderBy($oQuery, $aOrderBy);
        return $oQuery->paginate($iCount);
//        return $oQuery->take($iCount)->get();
    }

    private static function compileIssueListCacheKey($iUserId,$iLotteryId) {
        return static::getCachePrefix(true) . 'lists-lottery-' . $iUserId . '-' . $iLotteryId;
    }

    protected static function compileIssueCacheKey($iUserId,$iLotteryId, $sIssue){
        return static::getCachePrefix() . $iUserId . '-' . $iLotteryId . '-' . $sIssue;
    }

    /**
     * 返回指定游戏和奖期号的奖期对象
     * @param int $iLotteryId
     * @param string $sIssue
     * @return Issue
     */
    public static function getIssue($iUserId,$iLotteryId, $sIssue) {
        if (!$iUserId || !$iLotteryId || !$sIssue){
            return false;
        }
        $bReadDb = true;
        $bPutCache = false;
        if (static::$cacheLevel != self::CACHE_LEVEL_NONE){
            Cache::setDefaultDriver(static::$cacheDrivers[static::$cacheLevel]);
            $sCacheKey = static::compileIssueCacheKey($iUserId, $iLotteryId, $sIssue);
            if ($aIssueInfo = Cache::get($sCacheKey)) {
                $oIssue = new static;
                $oIssue = $oIssue->newFromBuilder($aIssueInfo);
                $bReadDb = false;
            }
            else{
                $bPutCache = true;
            }
        }
        if ($bReadDb){
            $oIssue = static::where('user_id', '=', $iUserId)
                    ->where('lottery_id', '=', $iLotteryId)
                    ->where('issue', '=', $sIssue)
                    ->first();
            if (!is_object($oIssue)) {
                return false;
            }
        }

        if ($bPutCache){
            Cache::put($sCacheKey, $oIssue->toArray(), intval($oIssue->cycle / 60));
        }
        return $oIssue;
    }

     public static function clearCacheByIssue($iUserId,$iLotteryId, $sIssue) {
        $sCacheKey = static::compileIssueCacheKey($iUserId,$iLotteryId, $sIssue);
        if (static::$cacheLevel != self::CACHE_LEVEL_NONE) {
            Cache::setDefaultDriver(static::$cacheDrivers[static::$cacheLevel]);
            !Cache::has($sCacheKey) or Cache::forget($sCacheKey);
        }
    }

    public static function & getRecentIssues($iUserId,$iLotteryId,$iCount = 20){
        $bReadDb = false;
        $bPutCache = false;
        if (static::$cacheLevel == self::CACHE_LEVEL_NONE){
            $bReadDb = true;
            $bPutCache = false;
        }
        else{
            Cache::setDefaultDriver(static::$cacheDrivers[static::$cacheLevel]);
            $key = static::compileRecentIssuesCacheKey($iUserId,$iLotteryId);
            if (!$aIssues = Cache::get($key)){
                $bReadDb = true;
                $bPutCache = true;
            }
        }
        if ($bReadDb){
            $oMoreIssues = static::getRecentIssuesFromDb($iUserId,$iLotteryId, $iCount, 0, null);
            $aIssues = [];
            foreach($oMoreIssues as $oIssue){
                $aIssues[] = $oIssue->toArray();
            }
        }
        !$bPutCache or Cache::forever($key, $aIssues);
//        pr(Cache::has($key));
//        pr($aIssues);
//        exit;
        return $aIssues;
    }

    public static function & getRecentIssuesRedis($iUserId, $iLotteryId,$iCount = 20){
        $redis = Redis::connection();
        $sCacheKey = static::compileRecentIssuesCacheKey($iUserId,$iLotteryId);
        $aIssues = [];
        if ($bHasInRedis = $redis->exists($sCacheKey)){
            $aIssues = static::getDataFromRedis($redis, $sCacheKey, $iCount, $iNeedCount);
        }
        else{
            $iNeedCount = $iCount;
        }
//        pr($iNeedCount);
        if (!$bHasInRedis || $iNeedCount > 0){
            $oMoreIssues = static::getRecentIssuesFromDb($iUserId,$iLotteryId, $iNeedCount, $iCount - $iNeedCount, null);
            $aMoreIssues = & static::pushToList($redis, $sCacheKey, $oMoreIssues, false);
//            $iOnSaleEndTime = static::getOnSaleIssueEndTime($iLotteryId);
//            $redis->expire($sCacheKey, $iOnSaleEndTime - time());
            $aIssues = array_merge($aIssues, $aMoreIssues);
        }
//        pr($aIssues);
//        exit;
        return $aIssues;
    }

    /**
     * 返回最近n期的开奖号码
     * @param int $iLotteryId
     * @return array | false            issue wn_number
     */
    public static function & getLatestWnNumbers($iUserId, $iLotteryId, $iCount = 1) {
        $redis = Redis::connection();
        $sCacheKey = static::compileRecentWnNumbersCacheKey($iLotteryId);
        $aIssues = [];
        if ($bHasInRedis = $redis->exists($sCacheKey)){
            $aIssues = static::getDataFromRedis($redis, $sCacheKey, $iCount, $iNeedCount);
        }
        else{
            $iNeedCount = $iCount;
        }
        if (!$bHasInRedis || $iNeedCount > 0){
            $oMoreIssues = static::getRecentIssuesFromDb($iLotteryId, $iNeedCount,count($aIssues), true);
            $aMoreIssues = & static::pushToList($redis, $sCacheKey, $oMoreIssues, false);
            $iOnSaleEndTime = static::getOnSaleIssueEndTime($iLotteryId);
            $redis->expire($sCacheKey, $iOnSaleEndTime - time());
            $aIssues = array_merge($aIssues, $aMoreIssues);
        }
//        pr($aIssues);
//        exit;
        return $aIssues;
    }

    protected static function compileFutureIssuesCacheKey($iLotteryId){
        return static::getCachePrefix(true) . 'future-issues-' . $iLotteryId;
    }

    /**
     * 返回当前期后最近n期的未开奖奖期
     * @param int $iLotteryId
     * @return array | false            issue wn_number
     */
    public static function & getFutureIssues($iLotteryId, $sIssue, $iCount = 6) {
        $redis = Redis::connection();
        $sCacheKey = static::compileFutureIssuesCacheKey($iLotteryId);
        $aIssues = [];
        if ($bHasInRedis = $redis->exists($sCacheKey)){
            $aIssues = static::getDataFromRedis($redis, $sCacheKey, $iCount, $iNeedCount);
//            pr($aIssues);
//            exit;
        }
        else{
            $iNeedCount = $iCount;
        }
        if (!$bHasInRedis || $iNeedCount > 0){
            $sStartIssue = !empty($aIssues) ? $aIssues[count($aIssues) - 1]['issue'] : $sIssue;
            $oMoreIssues = static::getFutureIssuesFromDb($iLotteryId, $sStartIssue, $iNeedCount);     // todo: 此处的奖期号存在问题
            $aMoreIssues = & static::pushToList($redis, $sCacheKey, $oMoreIssues, false);
            $iOnSaleEndTime = static::getOnSaleIssueEndTime($iLotteryId);
            $redis->expire($sCacheKey, $iOnSaleEndTime - time());
            $aIssues = array_merge($aIssues, $aMoreIssues);
        }
//        pr($aIssues);
//        exit;
        return $aIssues;
    }

    protected static function getRecentIssuesFromDb($iUserId,$iLotteryId, $iCount = 6, $iSkipCount = null, $iHasWnNumber = null){

        $aColumns = ['issue', 'wn_number', 'offical_time'];
        $aCondtions = [
            'user_id' => [ '=', $iUserId],
            'lottery_id' => [ '=', $iLotteryId],
            'end_time' => [ '<=', time()],
        ];
        if (!is_null($iHasWnNumber)){
            $iStatus = $iHasWnNumber ? self::ISSUE_CODE_STATUS_FINISHED : self::ISSUE_CODE_STATUS_WAIT_CODE;
            $aCondtions['status'] = ['=', $iStatus];
        }
        return static::doWhere($aCondtions)->orderBy('issue', 'desc')->skip($iSkipCount)->limit($iCount)->get($aColumns);

    }

    protected static function getFutureIssuesFromDb($iUserId, $iLotteryId, $sIssue, $iCount = 6){
        $aColumns = ['issue', 'wn_number', 'offical_time'];
        return static::where('user_id', '=', $iUserId)
                ->where('lottery_id', '=', $iLotteryId)
                ->where('issue', '>=', $sIssue)->orderBy('issue', 'asc')->limit($iCount)->get($aColumns);
    }

    protected static function compileRecentWnNumbersCacheKey($iUserId, $iLotteryId) {
        return static::getCachePrefix(true) . 'Recent-wnnumbers-' . $iUserId . '-' . $iLotteryId;
    }

    protected static function compileRecentIssuesCacheKey($iUserId,$iLotteryId) {
        return static::getCachePrefix(true) . 'Recent-issues-' . $iUserId . '-' . $iLotteryId;
    }

    protected static function compileLastWnNumberCacheKey($iUserId,$iLotteryId) {
        return Config::get('cache.prefix') . 'Last-wnnumber-' . $iUserId . '-' . $iLotteryId;
    }

    public static function getLastWnNumber($iUserId,$iLotteryId){
        Cache::setDefaultDriver(static::$cacheDrivers[static::$cacheLevel]);
        $key = static::compileLastWnNumberCacheKey($iUserId,$iLotteryId);
        if (!$aInfo = Cache::get($key)){
//            $aIssues = static::getLatestWnNumbers($iLotteryId,1);
            $aIssues = static::getRecentIssuesFromDb($iUserId,$iLotteryId, 1,0, true)->toArray();
            if ($aIssues){
                $aInfo = [
                    'issue' => $aIssues[0]['issue'],
                    'wn_number' => $aIssues[0]['wn_number'],
                    'offical_time' => $aIssues[0]['offical_time'],
                ];
                Cache::put($key, $aInfo, 1);
            }
            else{
                $aInfo = [
                    'issue' => '',
                    'wn_number' => '',
                    'offical_time' => ''
                ];
            }
        }
        return $aInfo;
    }

    /**
     * 返回最近没有开奖号码的奖期
     * @param int $iLotteryId
     * @return array
     */
    public static function getLatestIssueOfNoWnNumber($iUserId, $iLotteryId) {
        $aCondtions = [
            'user_id' => [ '=', $iUserId],
            'lottery_id' => [ '=', $iLotteryId],
            'end_time' => [ '<', time()],
            'status' => ['in', [self::ISSUE_CODE_STATUS_WAIT_CODE, self::ISSUE_CODE_STATUS_WAIT_VERIFY]],
        ];
//        $aOrderBy = ['end_time' => 'desc'];
        $oIssue = static::doWhere($aCondtions)->orderBy('end_time', 'asc')->limit(1)->get(['*'])->first();
        return $oIssue;
    }

    public static function getIssuesByLotteryId($iUserId,$iLotteryId) {
        $i = 0;
        $aData = [];
        $sStartTime = date('Y-m-d', (time() - 3600 * 24));
        $aColumns = ['id', 'issue'];
        $aLotteryWays = $oQuery = static::where('user_id', '=', $iUserId)
                ->where('lottery_id', '=', $iLotteryId)
                ->orderBy('issue', 'desc')->get($aColumns);
        foreach ($aLotteryWays as $id => $value) {
            $aData[$i]['id'] = $value->id;
            $aData[$i]['name'] = $value->issue;
            $i++;
        }
        return $aData;
    }

    /**
     * 根据彩种id和时间获取奖期信息
     * @param int $iLotteryId          彩种id
     * @param int $sLotteryName          彩种名称
     * @param int $iStartTime        开始时间
     * @param int $iEndTime         结束时间
     * @return array
     */
    public static function getIssuesByLotteryIdAndTime($iLotteryId, $iStartTime, $iEndTime, $sLotteryName) {
        $i = 0;
        $aData = [];
        $aColumns = ['issue', 'end_time', 'offical_time'];
        $aIssues = $oQuery = static::where('lottery_id', '=', $iLotteryId)->where('end_time', '<=', $iEndTime)->where('end_time', '>=', $iStartTime)->get($aColumns);
        foreach ($aIssues as $id => $value) {
            $aData[$i]['lottery'] = $sLotteryName;
            $aData[$i]['issue'] = $value->issue;
            $aData[$i]['drawTime'] = date('YmdHis', $value->offical_time);
            $aData[$i]['saleCloseTime'] = date('YmdHis', $value->end_time);
            $i++;
        }
        return $aData;
    }

    protected static function & getDataFromRedis($redis, $sCacheKey, $iCount, & $iNeedCount){
        $aFutureIssues = $redis->lrange($sCacheKey,0,$redis->llen($sCacheKey) - 1);
//            pr($aProjectsFromRedis);
        $iNeedCount = $iCount - count($aFutureIssues);
        $iNeedCount >= 0 or $iNeedCount = 0;
        $i = 0;
        foreach($aFutureIssues as $sIssueInfo){
//                $obj = new static;
//                $obj = $obj->newFromBuilder(json_decode($sIssueInfo, true));
            $aIssues[] = json_decode($sIssueInfo, true);
            if ($i++ == $iCount - 1){
                break;
            }
        }
        return $aIssues;
    }

    protected static function & pushToList($redis, $sCacheKey, $mMoreIssues, $bReverse = false){
        $sFunction = $bReverse ? 'lpush' : 'rpush';
        $data = is_object($mMoreIssues) ? $mMoreIssues->toArray() : $mMoreIssues;
//        if ($bReverse){
//            $aMoreIssues = array_reverse($aMoreIssues);
//        }
        $redis->multi();
        foreach($data as $aMoreIssue){
            $aIssues[] = $aMoreIssue;
            $redis->$sFunction($sCacheKey, json_encode($aMoreIssue));
        }
        $redis->exec();
        return $data;
    }

    protected function afterUpdate() {
        $this->deleteCache($this->id);
        if (static::$cacheLevel == self::CACHE_LEVEL_NONE) {
            return true;
        }
//        pr($sKeyData);
//        !empty($sKeyData) or $sKeyData = $this->id;
        $key = static::compileIssueCacheKey($this->lottery_id, $this->issue);
//        pr($key);
//        exit;
//        file_put_contents('/tmp/cachekey', $key . "\n", FILE_APPEND);
//        pr(static::$cacheDrivers[static::$cacheLevel]);
//        exit;
        Cache::setDefaultDriver(static::$cacheDrivers[static::$cacheLevel]);
        !Cache::has($key) or Cache::forget($key);
    }

    public static function createIssue($iUserId,$iLotteryId){
        $oLottery = ManLottery::find($iLotteryId);
        $sWnNumber = $oLottery->compileWinningNumber();
        $data = [
            'user_id' => $iUserId,
            'lottery_id' => $iLotteryId,
            'issue' => uniqid(),
            'wn_number' => $sWnNumber,
            'encoded_at' => date('Y-m-d H:i:s'),
        ];
        $obj = new static($data);
        return $obj->save() ? $obj : false;
    }
}
