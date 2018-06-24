<?php

/**
 * User Point Detail
 *
 * @author ben
 */
use Illuminate\Support\Facades\Redis;
class UserPointDetail extends BaseModel
{

    protected $table = 'user_point_details';
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
        'type_id',
        'is_income',
        'point',
        'previous_point',
        'trace_id',
        'project_id',
        'project_no',
        'lottery_id',
        'issue',
        'way_id',
        'remarks',
        'admin_user_id',
        'administrator',
        'created_at',
        'updated_at',
    ];

    const ERRNO_CREATE_SUCCESSFUL = -100;
    const ERRNO_CREATE_ERROR_DATA = -101;
    const ERRNO_CREATE_ERROR_SAVE = -102;
    const ERRNO_CREATE_ERROR_POINT = -103;

    public static $sequencable = false;
    public static $enabledBatchAction = false;
    protected $validatorMessages = [];
    protected $isAdmin = true;
    public static $resourceName = 'UserPointDetails';
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
        'type_id',
        'is_income',
        'point',
        'previous_point',
        'remarks',
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
        'user_id',
        'username',
        'is_tester',
        'is_agent',
        'top_agent_id',
        'parent_user_id',
        'user_forefather_ids',
        'user_forefathers',
        'account_id',
        'admin_user_id',
    ];
    public static $ignoreColumnsInEdit = [
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
        'admin_user_id',
        'administrator',
    ];
    public static $listColumnMaps = [
        'type_id' => 'formatted_types'
    ];
    public static $viewColumnMaps = [
        'type_id' => 'formatted_types'
    ];
    public static $htmlSelectColumns = [];
    public static $htmlTextAreaColumns = [];
    public static $htmlNumberColumns = [];
    public static $htmlOriginalNumberColumns = [];
    public static $amountAccuracy = 0;
    public static $originalColumns;
    public $orderColumns = [
        'created_at' => 'desc'
    ];
    public static $titleColumn = 'user_id';
    public static $mainParamColumn = '';
    public static $rules = [
        'user_id' => 'required',
        'username' => 'required|max:16',
        'is_tester' => 'required|integer',
        'type_id' => 'required',
        'is_income' => 'in:0,1',
        'point' => 'required',
        'previous_point' => 'required',
        'parent_user' => 'max:16',
        'user_forefather_ids' => 'max:100',
        'user_forefathers' => 'max:1024',
        'remarks' => 'max:100',
        'administrator' => 'max:16',
    ];

    /**
     * 组装积分变动详情数据
     *
     * @param $oUser
     * @param $iPoint
     * @param $iTypeId
     * @param array $aExtraData
     * @return array|bool
     */
    public static function _compileData($oUser, $iPoint, $iTypeId, $aExtraData = [])
    {

        //是否已经传递了is_income和remarks 没有传递在从数据库取出
        if (!isset($aExtraData['is_income']) || !isset($aExtraData['remarks'])) {
            $oUserPointType = UserPointType::find($iTypeId);

            if (!$oUserPointType) {
                return false;
            }

            $aExtraData['is_income'] = $oUserPointType->is_income;
            $aExtraData['remarks'] = $oUserPointType->note;
        }


        //是否已经传递了旧的积分[old_point]， 没有传递从数据库取出
        if (! isset($aExtraData['old_point'])) {
            $oUserGrade = UserGrade::where('user_id', '=', $oUser->id)->first();
            if (!$oUserGrade) {
                return false;
            }
            $aExtraData['old_point'] = $oUserGrade->point;
        }

        isset($aExtraData['trace_id']) or $aExtraData['trace_id'] = null;
        isset($aExtraData['project_id']) or $aExtraData['project_id'] = null;
        isset($aExtraData['project_no']) or $aExtraData['project_no'] = null;
        isset($aExtraData['lottery_id']) or $aExtraData['lottery_id'] = null;
        isset($aExtraData['issue']) or $aExtraData['issue'] = null;
        isset($aExtraData['way_id']) or $aExtraData['way_id'] = null;
        isset($aExtraData['remarks']) or $aExtraData['remarks'] = null;
        isset($aExtraData['admin_user_id']) or $aExtraData['admin_user_id'] = null;
        isset($aExtraData['administrator']) or $aExtraData['administrator'] = null;

        $aAttributes = [
            'user_id' => $oUser->id,
            'username' => $oUser->username,
            'is_tester' => $oUser->is_tester,
            'is_agent' => $oUser->is_agent,
            'top_agent_id' => $oUser->top_agent_id,
            'parent_user_id' => $oUser->parent_user_id,
            'parent_user' => $oUser->parent_user,
            'user_forefather_ids' => $oUser->user_forefather_ids?$oUser->user_forefather_ids:"",
            'user_forefathers' => $oUser->user_forefathers,
            'account_id' => $oUser->account_id,
            'type_id' => $iTypeId,
            'is_income' => $aExtraData['is_income'],
            'point' => $iPoint,
            'previous_point' => $aExtraData['old_point'],
            'trace_id' => $aExtraData['trace_id'],
            'project_id' => $aExtraData['project_id'],
            'project_no' => $aExtraData['project_no'],
            'lottery_id' => $aExtraData['lottery_id'],
            'issue' => $aExtraData['issue'],
            'way_id' => $aExtraData['way_id'],
            'remarks' => $aExtraData['remarks'],
            'admin_user_id' => $aExtraData['admin_user_id'],
            'administrator' => $aExtraData['administrator'],
        ];
        return $aAttributes;
    }

    /**
     * 根据投注额获取积分数
     * @param null $fAmount
     * @return int
     */
    public static function _getBetPoints($fAmount)
    {
        $iAmount = intval($fAmount);

        if ($iAmount <= 0) {
            return 0;
        }

        //获取积分比例
        $fPara = SysConfig::readValue('bet_yuan_point');
        if ($fPara <= 0) {
            return 0;
        }
        return intval($iAmount / $fPara);
    }

    protected function beforeValidate()
    {
        return parent::beforeValidate();
    }

    public function getFormattedTypesAttribute()
    {
        return __('_userpointtype.' . UserPointType::$validTypes[$this->type_id]);
    }

    /**
     * 今日积分
     * @param int $iUserId
     * @return mixed
     */
    static function getUserTodayPoints($iUserId = 0, $dDateStart = null, $dDateEnd = null)
    {
        $dDateStart = $dDateStart ? $dDateStart :Date::todayDate();
        $dDateEnd = $dDateEnd ? $dDateEnd : Date::tommorrowDate();

        $iAddPoints = static::where("user_id", '=', $iUserId)
            ->where("created_at", ">=", $dDateStart)
            ->where("created_at", "<", $dDateEnd)
            ->where("is_income", "=", 1)
            ->sum("point");

        $iSubPoints = static::where("user_id", '=', $iUserId)
                ->where("created_at", ">=", $dDateStart)
                ->where("created_at", "<", $dDateEnd)
                ->where("is_income", "=", 0)
                ->sum("point");

        return $iAddPoints - $iSubPoints;
    }

    /**
     * 本月获得积分
     * @aurhot lucky
     * @date 2016-10-28
     * @param int $iUserId
     * @return mixed
     */
    static function getUserPointsByDate($iUserId = 0, $sBeginDate = null, $sEndDate = null)
    {
        $oQuery = static::query();
        if ($iUserId) $oQuery->where("user_id", "=", $iUserId);
        if ($sBeginDate) $oQuery->where("created_at", ">=", $sBeginDate);
        if ($sEndDate) $oQuery->where("created_at", "<=", $sEndDate);

        return $oQuery->sum("point");
    }

    /**
     * 获取下级积分
     * @param $children_ids
     * @param $time_start
     * @param $time_end
     * @return Object Query
     */

    static function getChildrenPoints($children_ids, $time_start = null, $time_end = null)
    {
        $oQuery = static::query();
        $oQuery->select(DB::raw("sum(point) as point,user_id"));
        if ($children_ids) $oQuery->whereIn("user_id", $children_ids);
        if ($time_start) $oQuery->where("created_at", ">", $time_start);
        if ($time_end) $oQuery->whereIn("created_at", $time_end);
        $oQuery->groupBy("user_id");
        return $oQuery->get();
    }

    /**
     * 记录用户积分明细
     *
     * @author  Garin
     * @date  2016-10-24
     *
     * @param $oUser 用户对象
     * @param $iPoint 积分数值
     * @param $iTypeId 积分类型
     * @param $aExtraData 额外需要更改的字段
     * @param $bIsAdd  加积分还是减积分
     * @return bool   true成功  |false失败
     */
    static function recordUserPointDetail($oUser, $iPoint, $iTypeId, $aExtraData)
    {
        if (!is_object($oUser)) {
            return false;
        }
        //组装积分变动详情数据
        if (!$aAttributes = static::_compileData($oUser, $iPoint, $iTypeId, $aExtraData)) {
            return false;
        }
        $oUserPointDetail = new self($aAttributes);
        if (!$oUserPointDetail->save(static::$rules)) {
//            Log::info($oUserPointDetail->getValidationErrorString());
            return false;
        }
        return true;
    }

     /**
     * 检查积分是否发放
     * @author lucky
     * @date 2016-10-24
     * @param int $iUserId
     * @param int $iPointType
     * @param int $remarks
     * @return mixed
     */
    static function checkBonusPoints($iUserId, $iPointType, $remarks){
        return static::where("user_id", "=", $iUserId)
               ->where("type_id", "=", $iPointType)
               ->where("remarks", "=", $remarks)
               ->first();

    }


    /**
     * 获取某个时间段 内 获取的 各种类型的积分 和
     *
     * @author lucda
     * @date 2016-10-26
     * @param  $iUserId
     * @param  $sTimeBegin
     * @param  $sTimeEnd
     * @return array  type_id=>sumPoint
     */
    static function userSumPointsUseType($iUserId, $sTimeBegin, $sTimeEnd)
    {
        $aSumPointsUseType = [];
        $aConditions['user_id'] = ['=',$iUserId];
        $aSumUserPoints = static::doWhere($aConditions)->whereBetween('created_at',[$sTimeBegin,$sTimeEnd])->select('type_id',DB::raw('sum(point) sumPoint'))->groupBy('type_id')->get()->toArray();
        foreach($aSumUserPoints as $aSumUserPoint){
            $aSumPointsUseType[$aSumUserPoint['type_id']] = $aSumUserPoint['sumPoint'];
        }
        return $aSumPointsUseType;
    }

    /**
     * 将 各个积分 类型 的积分和.. 根据 is_income 字段 ,求 总和
     * 只显示增加的积分,评级积分等等不计算在内
     * @author lucda
     * @date    2016-12-01
     * @param $aSumPointsUseType
     */
    static function totalPoint($aSumPointsUseType) {
        $iTotalPoint = 0;
        $aUserPointTypes = UserPointType::all()->lists('is_income', 'id');//所有的积分类型
        foreach ($aSumPointsUseType as $iType=>$iPoint) {
            if(!isset($aUserPointTypes[$iType])) continue; 
            !$aUserPointTypes[$iType] or $iTotalPoint += $iPoint;//积分总和
        }
        return $iTotalPoint;
    }

}
