<?php

/**
 * 用户等级变动记录模型
 * @created_at 2017-01-17
 * @author ben
 */
use Illuminate\Support\Facades\Redis;
class UserGradeRecord extends BaseModel {

    protected $table = 'user_grade_records';

    protected static $cacheUseParentClass = false;

    protected static $cacheLevel = self::CACHE_LEVEL_NONE;

    protected static $cacheMinutes = 0;

    protected $fillable = [
        'id',
        'user_id',
        'username',
        'is_tester',
        'is_agent',
        'top_agent_id',
        'parent_user_id',
        'parent_user',
        'user_forefather_ids',
        'user_forefathers',
        'old_grade',
        'new_grade',
        'point',
        'date',
        'created_at',
        'updated_at',
    ];
    
    public static $sequencable = false;

    public static $enabledBatchAction = false;

    protected $validatorMessages = [];

    protected $isAdmin = true;

    public static $resourceName = 'UserGradeRecord';

    protected $softDelete = false;

    protected $defaultColumns = [ '*' ];

    protected $hidden = [];

    protected $visible = [];

    public static $treeable = '';
    
    public static $foreFatherIDColumn = '';

    public static $foreFatherColumn = '';

    public static $columnForList = [
        'id',
        'username',
        'old_grade',
        'new_grade',
        'point',
        'date',
        'created_at'

    ];

    public static $totalColumns = [];

    public static $totalRateColumns = [];

    public static $weightFields = [];

    public static $classGradeFields = [];

    public static $floatDisplayFields = [];

    public static $noOrderByColumns = [];

    public static $ignoreColumnsInView = [
    ];

    public static $ignoreColumnsInEdit = [
    ];

    public static $listColumnMaps = [];

    public static $viewColumnMaps = [];

    public static $htmlSelectColumns = [];

    public static $htmlTextAreaColumns = [];

    public static $htmlNumberColumns = [];

    public static $htmlOriginalNumberColumns = [];

    public static $amountAccuracy = 0;

    public static $originalColumns;

    public $orderColumns = [];

    public static $titleColumn = 'current_grade';

    public static $mainParamColumn = '';

    public static $rules = [
        'user_id' => 'required',
        'username' => 'required|max:16',
        'old_grade' => 'required|integer|min:0|max:100',
        'new_grade' => 'required|integer|min:0|max:100',
        'date'        => 'required'
    ];

    protected function beforeValidate() {
        return parent::beforeValidate();
    }

    public static function createUserGradeRecord($oUser, $aUserGrade, $aCurrentGradeSet, $sDate = false) {
        $oUserGradeRecord = new static;
        $oUserGradeRecord->user_id = $oUser->id;
        $oUserGradeRecord->username = $oUser->username;
        $oUserGradeRecord->is_tester = $oUser->is_tester;
        $oUserGradeRecord->is_agent = $oUser->is_agent;
        $oUserGradeRecord->parent_user_id = $oUser->parent_id;
        $oUserGradeRecord->parent_user = $oUser->parent;

        $oUserGradeRecord->user_forefather_ids = $oUser->forefather_ids;
        $oUserGradeRecord->user_forefathers = $oUser->forefathers;
        $oUserGradeRecord->old_grade = $aUserGrade['grade'];
        $oUserGradeRecord->point = $aUserGrade['point'];
        $oUserGradeRecord->new_grade = $aCurrentGradeSet['grade'];
        $oUserGradeRecord->date = $sDate !== false ? $sDate : date("Y-m-t", strtotime("-1 month"));
        $r = $oUserGradeRecord->save() or Log::error("create user grade record error:" . $oUserGradeRecord->getValidationErrorString());
        return $r;
    }


    /**
     * 获取上一个月的等级记录for test
     * @author lucky
     * @created_at 2016-10-10
     * @param $iUserId
     * @return mixed
     */
    public static function getLastMonthGradeRecord($iUserId) {
        $oRecord = self::where('date','=',date("Y-m-t", strtotime("-1 month")))
                        ->where('user_id','=',$iUserId)
                        ->first();
        return $oRecord;
    }

    /**
     * 获取用户本月等级记录
     * @param null $iUserId
     * @return mixed
     */
    static function getUserCurrentMonthGrade($iUserId=null){
        $now=Carbon::now();
        $last_month_start=Date::getLastMonthBeginDate();
        $current_month_start=Date::getCurrentMonthStartDate();
        return static::where("user_id", "=", $iUserId)
                    ->where("date",">=",$last_month_start)
                    ->where("date","<",$current_month_start)
                    ->orderby("user_id", "asc")
                    ->first();
    }

    /**
     *上月用户积分记录 
     * @param $iUserId
     * @return mixed
     */
    
    static function getLastMonthPointRecord($iUserId){
        $current_month_start = Date::getCurrentMonthStartDate();
        $last_month_start=Date::getCurrentMonthEndDate();
        return static::where("user_id","=",$iUserId)
                    ->whereBetween("date",[$last_month_start,$current_month_start])
                    ->orderBy("id")
                    ->first();
    }

    /**
     * 获取下级用户等级
     * @author lucky
     * @created_at 2016-10-12
     * @param null $children_ids
     * @param null $after_grade
     * @param null $month_start_date
     * @param null $month_end_date
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    static function getChildrenGradeRecord($children_ids=null, $after_grade=null, $month_start_date=null, $month_end_date=null){
        $oQuery=static::query();
        if($children_ids) $oQuery->whereIn("user_id", $children_ids);
        if($after_grade) $oQuery->where("new_grade","=",$after_grade);
        if($month_start_date) $oQuery->where("date",">=",$month_start_date);
        if($month_end_date) $oQuery->where("date","<=",$month_end_date);
        return $oQuery->orderBy("user_id","asc")->get();
    }


    /**
     * 根据日期获取用户等级
     * @author lucky
     * @created_at 2016-10-13
     * @param $iUserId
     * @param $sBeginDate
     * @param $sEndDate
     * @return mixed
     */
    static function getUserGradeByDate($iUserId,$sBeginDate,$sEndDate){
        return static::where("user_id",'=',$iUserId)
            ->where("date",'>=',$sBeginDate)
            ->where("date","<",$sEndDate)
            ->first();
    }

    public function deleteUserPanelDataCache() {
        parent::deleteUserPanelDataCache(); // TODO: Change the autogenerated stub
        $oUser     = User::find($this->user_id);
        $iParentId = $oUser->parent_id;
        if($iParentId){
            $redis = Redis::connection();
            $sKey  = static::compileRedisCacheKey($iParentId);
            if($aKeys = $redis->keys($sKey . '*')){
                foreach ($aKeys as $sKey) {
                    $redis->del($sKey);
                }
            }
        }
    }

}