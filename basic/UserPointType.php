<?php

/*+*
 * 积分类型
 *
 * @author test008
 */

class UserPointType extends BaseModel {


    protected $table = 'user_point_types';

    protected static $cacheUseParentClass = false;

    protected static $cacheLevel = self::CACHE_LEVEL_FIRST;

    protected static $cacheMinutes = 0;

    const TYPES_BET = 1;         //数字彩投注
    const TYPES_lOGON = 2;       //登陆积分
    const TYPES_TASK = 3;        //任务积分
    const TYPES_UNGRADED = 4;    //评级
    const TYPES_GIFT_SYS = 5;    //奖励积分
    const TYPES_GIFT_FRIEND = 6; //朋友赠送
    const TYPES_NOT_LOGON = 7;   //昨日未签到
    const TYPES_FOR_FRIEND = 8;  //送给朋友
    const TYPES_LOTTERY = 9;     //抽奖
    const TYPES_CHECK_IN = 10;   //签到积分
    const TYPES_SHOW_BILLS = 11; //晒单
    const TYPES_IS_SUPER = 12;   //设为神单
    const TYPES_JC_BET = 13;     //竞彩投注

    public static $validTypes = [
        self::TYPES_BET => 'types-bet',
        self::TYPES_lOGON => 'types-logon',
        self::TYPES_TASK => 'types-task',
        self::TYPES_GIFT_SYS => 'types-gift-sys',
        self::TYPES_GIFT_FRIEND => 'types-gift-friend',
        self::TYPES_UNGRADED => 'types-ungraded',
        self::TYPES_NOT_LOGON => 'types-not-logon',
        self::TYPES_FOR_FRIEND => 'types-for-friend',
        self::TYPES_LOTTERY => 'types-lottery',
        self::TYPES_CHECK_IN => 'types-check-in',
        self::TYPES_SHOW_BILLS => 'types-show-bills',
        self::TYPES_IS_SUPER => 'types-is-super',
        self::TYPES_JC_BET => 'types-jc-bet',
    ];

    protected $fillable = [
        'id',
        'identification',
        'cn_title',
        'en_title',
        'is_income',
        'note',
        'created_at',
        'updated_at',
    ];
    
    public static $sequencable = false;

    public static $enabledBatchAction = false;

    protected $validatorMessages = [];

    protected $isAdmin = true;

    public static $resourceName = 'UserPointType';

    protected $softDelete = false;

    protected $defaultColumns = [ '*' ];

    protected $hidden = [];

    protected $visible = [];

    public static $treeable = '';
    
    public static $foreFatherIDColumn = '';

    public static $foreFatherColumn = '';

    public static $columnForList = [
        'id',
        'identification',
        'cn_title',
        'en_title',
        'is_income',
        'note',
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

    public static $listColumnMaps = [];

    public static $viewColumnMaps = [];

    public static $htmlSelectColumns = [];

    public static $htmlTextAreaColumns = [];

    public static $htmlNumberColumns = [];

    public static $htmlOriginalNumberColumns = [];

    public static $amountAccuracy = 0;

    public static $originalColumns;

    public $orderColumns = [];

    public static $titleColumn = 'cn_title';

    public static $mainParamColumn = '';

    public static $rules = [
        'identification' => 'required|max:45',
        'cn_title' => 'required|max:45',
        'en_title' => 'required|max:45',
        'is_income' => 'in:0,1',
        'note' => 'max:45',
    ];

    protected function beforeValidate() {
        return parent::beforeValidate();
    }

}