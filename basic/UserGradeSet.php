<?php

/**
 * 用户积分等级设置
 *
 * @author test008
 */
class UserGradeSet extends BaseModel
{

    protected $table = 'user_grade_sets';

    protected static $cacheUseParentClass = false;

    protected static $cacheLevel = self::CACHE_LEVEL_NONE;

    protected static $cacheMinutes = 0;

    protected $fillable = [
        'id',
        'grade',
        'name',
        'point',
        'user_rebate',
        'parent_rebate',
        'icon',
        'created_at',
        'updated_at',
    ];

    public static $sequencable = false;

    public static $enabledBatchAction = false;

    protected $validatorMessages = [];

    protected $isAdmin = true;

    public static $resourceName = 'UserGradeSets';

    protected $softDelete = false;

    protected $defaultColumns = ['*'];

    protected $hidden = [];

    protected $visible = [];

    public static $treeable = '';

    public static $foreFatherIDColumn = '';

    public static $foreFatherColumn = '';

    public static $columnForList = [
        'id',
        'grade',
        'name',
        'point',
        'user_rebate',
        'parent_rebate',
        'created_at',
        'updated_at',
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
        'created_at',
        'updated_at',
    ];

    public static $listColumnMaps = [
        // 'account_available' => 'account_available_formatted',
        'user_rebate' => 'user_rebate_formatted',
        'parent_rebate' => 'parent_rebate_formatted',
    ];

    public static $viewColumnMaps = [];

    public static $htmlSelectColumns = [];

    public static $htmlTextAreaColumns = [];

    public static $htmlNumberColumns = [];

    public static $htmlOriginalNumberColumns = [];

    public static $amountAccuracy = 0;

    public static $originalColumns;

    public $orderColumns = [
        'grade' => 'asc'
    ];

    public static $titleColumn = 'grade';

    public static $mainParamColumn = '';

    public static $rules = [
        'grade' => 'required|integer|min:0|max:100',
        'name' => 'required|max:45',
        'point' => 'required|min:0',
        'icon' => 'required|max:45',
        'user_rebate' => 'required|numeric|max:1',
        'parent_rebate' => 'required|numeric|max:1'
    ];

    protected function getUserRebateFormattedAttribute()
    {
        $number = $this->attributes['user_rebate'] * 100;
        return $number . '%';
    }

    protected function getParentRebateFormattedAttribute()
    {
        $number = $this->attributes['parent_rebate'] * 100;
        return $number . '%';
    }

    protected function beforeValidate()
    {
        return parent::beforeValidate();
    }

    /**
     * 获取下一个等级
     */
    static function getNextGradeSet($iGrade)
    {
        return UserGradeSet::where('grade', '>', $iGrade)->orderBy('grade')->first();
    }

    /**
     * 获得当前等级配置
     * @param $iGrade
     * @return mixed
     */
    static function getGradeSet($iGrade)
    {
        return UserGradeSet::where('grade', '=', $iGrade)->first();
    }

    /**
     * 获取等级设置
     * @aurhor lucky
     * @created_at 2016-10-13
     * @return array
     */
    static function getGradeSets()
    {
        $cachePrefix = static::getCachePrefix(true);
        $cacheKey = $cachePrefix . "grade-sets";
        $oUserGradeSet = Cache::get($cacheKey, function() use ($cacheKey) {
            $oUserGradeSet = UserGradeSet::all();
            Cache::forever($cacheKey, $oUserGradeSet);
            return $oUserGradeSet;
        });
        // Cache::forget($cacheKey);
        return $oUserGradeSet;
    }

    /**
     * 等级选择
     * @return array
     */

    static function getGradeSetsByGrade(){
        $gradeSets=static::getGradeSets();
        $gradeSetsByGradeId=[];
        foreach($gradeSets as $gradeSet){
            $gradeSetsByGradeId[$gradeSet->grade]=$gradeSet->name;
        }
        return $gradeSetsByGradeId;
    }

    /**
     * 获取当前等级积分
     * @author lucky
     * @created_at 2016-10-13
     * @param int $user_point
     * @return mixed
     */
    static function getLevelPoint($iUserPoint = 0)
    {
        $iUserLevelPoint = 0;

        $aGradeSets = UserGradeSet::getGradeSets()->toArray();
        $aGradeSets = array_reverse($aGradeSets);

        foreach ($aGradeSets as $key=>$aGradeSet) {
            if ($iUserPoint >= $aGradeSet['point']){
                $iUserLevelPoint = $aGradeSets[$key == 0 ? 0 : ($key-1)]['point'];
                break;
            }
            continue;
        }

        return $iUserLevelPoint;
    }

    /**
     * 获取最大的等级设置
     * @return mixed
     */

    static function getMaxGrade()
    {
        return static::orderBy("grade", "desc")->first();
    }



}