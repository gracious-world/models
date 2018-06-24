<?php

/**
 * 用户等级信息表
 *
 * @author ben
 */
class UserGrade extends BaseModel
{

    protected $table = 'user_grade';

    const STATSU_GRADE_UPGRADE = 1;       //可升级状态
    const STATSU_GRADE_KEEP = 0;          //维持当前等级不变状态
    const STATUS_ERRNO_DOWNGRADE = -1;    //可降级状态

    const LEVEL_ZERO = 0;
    const LEVEL_ONE = 1;
    const LEVEL_TWO = 2;
    const LEVEL_THREE = 3;
    const LEVEL_FOUR =  4;
    const LEVEL_FIVE =  5;

    const LEVEL_ZERO_POINT = 0;
    const LEVEL_ONE_POINT = 1000;
    const LEVEL_TWO_POINT = 10000;
    const LEVEL_THREE_POINT = 100000;
    const LEVEL_FOUR_POINT =  1000000;
    const LEVEL_FIVE_POINT =  5000000;

    const INIT_GRADE = 0; //初始等级
    const INIT_POINT = 0; //初始积分

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
        'account_id',
        'grade',
        'point',
        'created_at',
        'updated_at',
    ];

    public static $sequencable = false;

    public static $enabledBatchAction = false;

    protected $validatorMessages = [];

    protected $isAdmin = true;

    public static $resourceName = 'UserGrade';

    protected $softDelete = false;

    protected $defaultColumns = ['*'];

    protected $hidden = [];

    protected $visible = [];

    public static $treeable = '';

    public static $foreFatherIDColumn = '';

    public static $foreFatherColumn = '';

    public static $columnForList = [
        'id',
        'username',
        'grade',
        'point',

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

    public static $titleColumn = 'user_id';

    public static $mainParamColumn = '';

    public static $rules = [
        'user_id' => 'required',
        'username' => 'required|max:16',
        'account_id' => 'required',
        'grade' => 'required|integer|min:0|max:100',
        'point' => 'required',
    ];

    protected function beforeValidate()
    {
        return parent::beforeValidate();
    }

    public static function createUserGrade($oUser)
    {
        $oUserGrade = new static;
        $oUserGrade->user_id = $oUser->id;
        $oUserGrade->username = $oUser->username;
        $oUserGrade->account_id = $oUser->account_id;
        $oUserGrade->is_tester = $oUser->is_tester;
        $oUserGrade->is_agent = $oUser->is_agent;
        $oUserGrade->parent_user_id = $oUser->parent_id;
        $oUserGrade->parent_user = $oUser->parent;
        $oUserGrade->user_forefather_ids = $oUser->forefather_ids;
        $oUserGrade->user_forefathers = $oUser->forefathers;
        $oUserGrade->grade = self::INIT_GRADE;
        $oUserGrade->point = self::INIT_POINT;

        return $oUserGrade->save() ? $oUserGrade : false;
    }

    /**
     * 根据用户id获取用户等级模型
     * @param $iUserId
     * @return bool or object
     */
    public static function getUserGradeByUserId($iUserId)
    {
        if (!$iUserId) return false;
        return UserGrade::where('user_id', '=', $iUserId)->first();
    }

    /**
     * 获取用户的当前积分对应的等级对象
     * @param $iUserId
     */
    public static function pointToGrade($iUserId)
    {
        $oUserGrade = UserGrade::getUserGradeByUserId($iUserId);
        $oUserGradeSet = UserGradeSet::where('point', '<=', $oUserGrade->point)->orderBy('grade', 'desc')->first();
        return $oUserGradeSet;
    }

    /**
     * 获取用户当前等级状态
     * @param $iUserId
     */
    public static function getUserPointStatus($iUserId)
    {
        $oUserGrade = UserGrade::getUserGradeByUserId($iUserId);
        $oCurrentGradeSet = self::pointToGrade($iUserId);
        if ($oUserGrade->grade < $oCurrentGradeSet->grade) {
            return self::STATSU_GRADE_UPGRADE;
        } else if ($oUserGrade->grade == $oCurrentGradeSet->grade) {
            return self::STATSU_GRADE_KEEP;
        } else {
            return self::STATUS_ERRNO_DOWNGRADE;
        }
    }

    /**
     * 查找下级用户等级人数
     * @author lucky
     * @created_at
     * @param $oUser
     * @return array|mixed
     *
     */
    static function getChildrenGrade($children_ids,$iUserId)
    {
        $grade_sets = UserGradeSet::getGradeSets();
        //TODO IMPROVE
        /*$children_grade = [];
        $cachePrefix = static::getCachePrefix(true);
        $cacheKey = $cachePrefix . "childern-grade-".$iUserId;
        if ($children_grade = Cache::get($cacheKey)) return $children_grade;*/

        $children_grade = [];
        foreach ($grade_sets as $grade_set) {
            $children_grade[$grade_set->grade] ['count'] = UserGrade::whereIn("user_id", $children_ids)
                ->where("grade", "=", $grade_set['grade'])
                ->count();
            $children_grade[$grade_set->grade] ['name'] = $grade_set['name'];
        }
//        Cache::put($cacheKey,$children_grade,Carbon::now()->addYears(3));
        return $children_grade;
    }



    /**
     * 通过等级过滤下级
     * @author lucky
     * @created_at 2016-10-12
     * @param $iUserId
     * @param $grade
     * @return mixed
     */
    static function getChildrenByGrade($iUserId,$grade){
        return static::where("grade",'=',$grade)
                    ->where("parent_user_id",'=',$iUserId)
                    ->list("user_id");
    }

    /**
     * 根据用户id，批量获取用户等级(grade)
     * @author Garin
     *
     * @param array $iUserIds
     * @return mixed
     */
    public static function getUserGradeByUserIds($iUserIds){
        if (empty($iUserIds)) {
            return false;
        }
        $oUserGrade =  UserGrade::whereIn('user_id', $iUserIds)->select('grade', 'user_id')->get();
        $aUserGrade = $oUserGrade->toArray();
        if (is_array($aUserGrade)){
            return array_column($aUserGrade, 'grade', 'user_id');
        }else{
            return false;
        }
    }


    /**
     * 根据用户id，批量获取用户等级信息详情
     * @author Garin
     *
     * @param array $iUserIds
     * @return mixed
     */
    public static function getUserGradeListByUserIds($where = array()) {
        if (empty($where)) {
            return false;
        }
        $oUserGradeList = UserGrade::doWhere($where)
            ->select('grade', 'user_id', 'point', 'username')
            ->get()
            ->toArray();
        return $oUserGradeList;

    }


    /**
     * 将积分明细更新到用户等级记录表
     * @param $iUserId
     * @param $tTimeStart
     * @param $tTimeEnd
     * @param $msg
     * @return bool
     */
    public static function updateToUserGradeRecord($oUserGrade, $iOldGrade){
        $iUserId = $oUserGrade-> user_id;
        $aData = [
            'user_id' => $oUserGrade-> user_id,
            'username' => $oUserGrade -> username,
            'is_tester' => $oUserGrade ->is_tester,
            'is_agent'=> $oUserGrade ->is_agent,
            'top_agent_id' => $oUserGrade ->top_agent_id,
            'parent_user_id' => $oUserGrade ->parent_user_id,
            'parent_user'=> $oUserGrade ->parent_user,
            'user_forefather_ids' => $oUserGrade -> user_forefather_ids,
            'user_forefathers' => $oUserGrade ->user_forefathers,
            'old_grade' => $iOldGrade,
            'new_grade' => $oUserGrade -> grade ,
            'point' => $oUserGrade -> point,
            'date' => date("Y-m-d", time()),
        ];


        if(!$oUserGradeRecord = UserGradeRecord::where('date', '=', date("Y-m-d", strtotime(time())))->where('user_id', '=', $iUserId)->first()){
            $oUserGradeRecord = new UserGradeRecord();
        }

        $oUserGradeRecord->fill($aData);

        if(!$oUserGradeRecord->save()){
            $msg = $oUserGradeRecord->getValidationErrorString();
            Log::error("update user grade failed:" . $msg);
            return false;
        }
        return true;

    }




    /**
     * 根据传入分数计算用户能达到的等级
     *
     * @author  Gain
     * @date  2016-11-11
     *
     * @param $iPoint 当前用户分数
     *
     * @return bool|int|mixed
     */
    public static function CaculateGradeByPoint($iPoint) {
        $iGrade = 0;

        if ($iPoint < static::LEVEL_ZERO) {
            return false;
        }
        $aGradPointMap = [
            static::LEVEL_FIVE_POINT    => static::LEVEL_FIVE,
            static::LEVEL_FOUR_POINT    => static::LEVEL_FOUR,
            static::LEVEL_THREE_POINT   => static::LEVEL_THREE,
            static::LEVEL_TWO_POINT     => static::LEVEL_TWO,
            static::LEVEL_ONE_POINT     => static::LEVEL_ONE,
            static::LEVEL_ZERO_POINT    => static::LEVEL_ZERO

        ];
        foreach ($aGradPointMap as $key => $v) {

            if ($key <= $iPoint) {
                $iGrade = $v;
                break;
            }
        }
        return $iGrade;
    }



    /**
     * 计算变更后用户等级和变更需要扣掉的积分
     *
     * 当前积分大于当前级别 升级后 减去升级级别的基础分
     * 当前积分小于当前级别 降级后 减去下级别的基础分(不够减的直接归零了)
     * 当前积分等于当前级别 维持等级不变 减去当前级别基础分
     *
     * @author  Garin
     * @date 2016-10-17
     *
     * @param int $iPoint
     * @param int $iGrade
     *
     * @return array
     */
    public static function CaculateUserGradeAndPoint($iPoint, $iGrade) {
        $aGradeInfo = array('cal_grade'=>0, 'point'=>0, 'change_method' => '');
        $iSubPoint = 0;
        $iRelLevelOnePoint = 100;

        //根据当前用户已有分数计算等级
        $iCalGrade = static::CaculateGradeByPoint($iPoint);
        if ($iCalGrade === false) {
            return false;
        }

        //升级
        if ($iGrade < $iCalGrade) {
            $aGradeInfo['change_method'] = '升级';
            switch ($iCalGrade) {
                case static::LEVEL_ONE:
                    $iSubPoint = static::LEVEL_ONE_POINT;
                    $aGradeInfo['cal_grade'] = static::LEVEL_ONE;
                    break;
                case static::LEVEL_TWO:
                    $iSubPoint = static::LEVEL_TWO_POINT;
                    $aGradeInfo['cal_grade'] = static::LEVEL_TWO;
                    break;
                case static::LEVEL_THREE:
                    $iSubPoint = static::LEVEL_THREE_POINT;
                    $aGradeInfo['cal_grade'] = static::LEVEL_THREE;
                    break;
                case static::LEVEL_FOUR:
                    $iSubPoint = static::LEVEL_FOUR_POINT;
                    $aGradeInfo['cal_grade'] = static::LEVEL_FOUR;
                    break;
                case static::LEVEL_FIVE:
                    $iSubPoint = static::LEVEL_FIVE_POINT;
                    $aGradeInfo['cal_grade'] = static::LEVEL_FIVE;
                    break;
                default;
            }

        }

        //维持等级不变
        if ($iGrade == $iCalGrade) {
            $aGradeInfo['cal_grade'] = $iGrade;
            $aGradeInfo['change_method'] = '维持等级不变';
            switch ($iCalGrade) {
                case static::LEVEL_ZERO:
                    $iSubPoint = $iRelLevelOnePoint;
                    break;
                case static::LEVEL_ONE:
                    $iSubPoint = static::LEVEL_ONE_POINT;
                    break;
                case static::LEVEL_TWO:
                    $iSubPoint = static::LEVEL_TWO_POINT;
                    break;
                case static::LEVEL_THREE:
                    $iSubPoint = static::LEVEL_THREE_POINT;
                    break;
                case static::LEVEL_FOUR:
                    $iSubPoint = static::LEVEL_FOUR_POINT;
                    break;
                case static::LEVEL_FIVE:
                    $iSubPoint = static::LEVEL_FIVE_POINT;
                    break;
                default;
            }
        }

        //降级
        if ($iGrade > $iCalGrade) {
            $aGradeInfo['change_method'] = '降级';
            switch ($iGrade) {
                case static::LEVEL_ONE:
                    $iSubPoint = $iRelLevelOnePoint;
                    $aGradeInfo['cal_grade'] = static::LEVEL_ZERO;
                    break;
                case static::LEVEL_TWO:
                    $iSubPoint = static::LEVEL_ONE_POINT;
                    $aGradeInfo['cal_grade'] = static::LEVEL_ONE;
                    break;
                case static::LEVEL_THREE:
                    $iSubPoint = static::LEVEL_TWO_POINT;
                    $aGradeInfo['cal_grade'] = static::LEVEL_TWO;
                    break;
                case static::LEVEL_FOUR:
                    $iSubPoint = static::LEVEL_THREE_POINT;
                    $aGradeInfo['cal_grade'] = static::LEVEL_THREE;
                    break;
                case static::LEVEL_FIVE:
                    $iSubPoint = static::LEVEL_FOUR_POINT;
                    $aGradeInfo['cal_grade'] = static::LEVEL_FOUR;
                    break;
                default;
            }
        }

        $aGradeInfo['sub_point'] = $iSubPoint;
        return $aGradeInfo;
    }

    /**
     * 获取用户下一个等级积分
     * @param null $iUserId
     * @return mixed
     */
    static function getUserNextGradePoint($iUserId = null)
    {
        $oUserGrade = UserGrade::where("user_id",$iUserId)->first();
        $iUserGrade = $oUserGrade ? $oUserGrade->grade : 0;
        $oUserGradeSet = UserGradeSet::getNextGradeSet($iUserGrade) ? UserGradeSet::getNextGradeSet($iUserGrade) : UserGradeSet::getMaxGrade();
        $iNextPoint = $oUserGradeSet ? $oUserGradeSet->point: 0;
        return $iNextPoint;
    }

}