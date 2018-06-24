<?php

/**
 * 用户登录记录
 *
 * @author white
 */
class UserLogin extends BaseModel {

    static $cacheLevel = self::CACHE_LEVEL_FIRST;

    //新人活动送积分百分比
    const CHECK_IN_RATE = 0.01;

    protected $table            = 'user_logins';
    protected $softDelete       = false;
    protected $fillable         = [
        'date',
        'user_id',
        'username',
        'is_tester',
        'parent_user',
        'parent_user_id',
        'top_agent_id',
        'top_agent',
        'forefather_ids',
        'forefathers',
        'nickname',
        'terminal_id',
        'ip',
        'date',
        'signed_time',
        'session_id',
        'http_user_agent',
    ];
    public static $resourceName = 'UserLogin';
    public static $columnForList = [
        'date',
        'username',
        'terminal_id',
        'is_tester',
        'top_agent',
        'nickname',
        'ip',
        'signed_time',
//        'http_user_agent',
    ];
    public $orderColumns = [
        'signed_time' => 'desc',
        'username'    => 'asc',
    ];
    public static $listColumnMaps = [
        'signed_time' => 'formatted_signed_time',
        'is_tester'   => 'formatted_is_tester',
    ];
    public static $viewColumnMaps = [
        'signed_time' => 'formatted_signed_time',
        'is_tester'   => 'formatted_is_tester',
    ];
    public static $htmlSelectColumns   = [
        'terminal_id'    => 'aTerminals',
    ];

    /**
     * 创建用户登录记录
     * @param User $oUser
     * @return bool
     */
    public static function createLoginRecord($oUser) {
        $oUserLogin = new static;
        $oUserLogin->fill(
            [
                'date' => date('Y-m-d'),
                'user_id'         => $oUser->id,
                'username'        => $oUser->username,
                'is_tester'       => $oUser->is_tester,
                'nickname'        => $oUser->nickname,
                'parent_user'     => $oUser->parent,
                'parent_user_id'  => $oUser->parent_id,
                'forefather_ids'  => $oUser->forefather_ids,
                'forefathers'     => $oUser->forefathers,
                'top_agent_id'    => $oUser->getTopAgentId(),
                'top_agent'       => $oUser->getTopAgentUserName(),
                'ip'              => Tool::getClientIp(),
                'signed_time'     => time(),
                'session_id'      => Session::getId(),
                'http_user_agent' => $_SERVER['HTTP_USER_AGENT'],
                'terminal_id' => Session::get('terminal_id')
            ]
        );
        return $oUserLogin->save();
    }

    /**
     * 返回在指定日期范围登录数，去重
     * @param date $sBeginDate
     * @param date $sEndDate
     * @return int
     */
    public static function getLoginUserCount($sBeginDate, $sEndDate = null) {
        $sEndDate or $sEndDate = "$sBeginDate 23:59:59";
        $sSql     = "select count(distinct user_id) count from user_logins where created_at between '$sBeginDate' and '$sEndDate' and is_tester = 0";
        $aResults = DB::select($sSql);
        return $aResults[0]->count ? $aResults[0]->count : 0;
    }

    protected function getFormattedSignedTimeAttribute() {
        return date('Y-m-d H:i:s', $this->attributes['signed_time']);
    }

    protected function getFormattedIsTesterAttribute() {
        if ($this->attributes['is_tester'] !== null) {
            return __('_basic.' . strtolower(Config::get('var.boolean')[$this->attributes['is_tester']]));
        } else {
            return '';
        }
    }

    /**
     * 用户单月签到次数
     * @author lucky
     * @created_at 2016-10-10
     * @param  int $iUserId
     * @param date $dStart
     * @param date $dEnd
     * @return int
     *
     */
    static function getUserMonthCheckInTimes($iUserId, $dStart = null, $dEnd = null)
    {
        $oNow = Carbon::now();
        $dStart = $dStart ? $dStart : date("Y-m-1", $oNow->timestamp);
        $dEnd = $dEnd ? $dEnd : date("Y-m-t", $oNow->timestamp);
        $iUserLogins = static::select(DB::raw("distinct(date)"))->where("user_id", '=', $iUserId)->whereBetween("date", [$dStart, $dEnd])->get();
        return count($iUserLogins);
    }

    /**
     * 检测用户当天是否登陆
     *
     * @author lucky
     * @date 2016-10-22
     * @param object $oUser
     * @param int $iStart
     * @param int $iEnd
     * @return int
     */
    static function getUserCheckInTimes($oUser, $iStart = null, $iEnd = null)
    {
        //今天开始时间戳
        $iStart = $iStart ? $iStart : strtotime(Carbon::now()->toDateString());
        //今天结束时间戳
        $iEnd = $iEnd ? $iEnd : (strtotime(Carbon::now()->addDay(1)->toDateString()) - 1);
        return static::where("user_id", '=', $oUser->id)->whereBetween("signed_time", [$iStart, $iEnd])->count();
    }

    /**
     * 获取某时间段内的用户登陆记录
     *
     * @author Garin
     * @date 2016-10-26
     *
     * @param string $sStart 开始时间 yyy-mm-dd H:i:s
     * @param string $sEnd 结束时间 yyy-mm-dd H:i:s
     *
     * @return mixed
     */
    public static function & getUserLoginList($sStart = '', $sEnd = '') {
        $now = Carbon::now();

        //没有传开始时间以今天开始时间为界
        if (empty($sStart)) {
            $sStart = strtotime($now->toDateString());
        } else {
            $sStart = strtotime($sStart);
        }

        //没有传结束时间以今天结束时间为界
        if (empty($sEnd)) {
            $sEnd = strtotime($now->addDay(1)->toDateString());
        } else {
            $sEnd = strtotime($sEnd);
        }

        $aLoginList = static::where("signed_time", ">=", $sStart)
            ->where("signed_time", "<", $sEnd)
            ->groupBy("user_id")
            ->get()
            ->toArray();
        return $aLoginList;
    }

}
