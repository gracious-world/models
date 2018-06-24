<?php

/**
 * 总积分记录
 *
 * @author saraphp
 */

class UserPoint extends BaseModel {

    protected $table = 'user_points';

    protected static $cacheUseParentClass = false;

    protected static $cacheLevel = self::CACHE_LEVEL_NONE;

    protected static $cacheMinutes = 0;

    protected $fillable = [
        'id',
        'user_id',
        'username',
        'total_point',
        'total_point_added',
        'total_point_used',
        'created_at',
        'updated_at',
    ];
    
    public static $sequencable = false;

    public static $enabledBatchAction = false;

    protected $validatorMessages = [];

    protected $isAdmin = true;

    public static $resourceName = 'UserPoint';

    protected $softDelete = false;

    protected $defaultColumns = [ '*' ];

    protected $hidden = [];

    protected $visible = [];

    public static $treeable = '';
    
    public static $foreFatherIDColumn = '';

    public static $foreFatherColumn = '';

    public static $columnForList = [
        'id',
        'user_id',
        'username',
        'total_point',
        'total_point_added',
        'total_point_used',
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

    public static $titleColumn = 'total_point';

    public static $mainParamColumn = '';

    public static $rules = [
        'user_id' => 'required|min:0',
        'username' => 'required|max:16',
        'total_point' => 'required|min:0',
        'total_point_added' => 'required|min:0',
        'total_point_used' => 'required|min:0',
    ];

    protected function beforeValidate() {
        return parent::beforeValidate();
    }

    protected function getTotalPoint($iUserId){
        $aConditions['user_id'] = ['=', $iUserId];
        return static::doWhere($aConditions)->first();
    }

    /**
     * 更新总积分
     *
     * @param $oUser
     * @param $iPoint
     * @param $bIsIncome
     * @return array|bool
     */
    protected function updateUserPoint($oUser, $iPoint, $bIsIncome){
        if (!is_object($oUser)) {
            return false;
        }
        $oUserPoint = $this->getTotalPoint($oUser->id);
        if(is_object($oUserPoint)){
            if($bIsIncome){
                $oUserPoint->total_point += $iPoint;
                $oUserPoint->total_point_added += $iPoint;
            }else
            {
                $oUserPoint->total_point -= $iPoint;
                $oUserPoint->total_point_used -= $iPoint;
            }
            if (!$oUserPoint->save()) {
                return false;
            }
        }else{
            //组装积分变动详情数据
            $aAttributes = [
                'user_id' => $oUser->id,
                'username' => $oUser->username,
                'total_point' => $bIsIncome ? $iPoint : '-'.$iPoint,
                'total_point_added' => $bIsIncome ? $iPoint : 0,
                'total_point_used' => $bIsIncome ? 0 : $iPoint,
            ];

            $oUserPointDetail = new self($aAttributes);
            if (!$oUserPointDetail->save(static::$rules)) {
                return false;
            }
        }

        return true;
    }
}