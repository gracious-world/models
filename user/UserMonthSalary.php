<?php

/**
 * 用户工资,主要是 队长工资模型 lucda
 * 后台 仅仅只是 审核通过 审核不通过的 操作..没有进行 帐变的更改
 * 在 command 里面 进行了 帐变的更改,也就是 派发操作 updated 2016-10-10
 * @author lucda
 */

class UserMonthSalary extends BaseModel {

    protected $table = 'user_month_salaries';

    //const STATUS_DISTRIBUTED = 1;       //派发成功,也就是 写到了 user_month_salaries 表里面
    //const STATUS_DISTRIBUTED_FAILED = 2; //派发失败
    //const STATUS_REVIEW_PASSED = 4;          //审核通过,也就是 会员中心可以看到这行数据了. 且 帐变添加
    //const STATUS_REVIEW_PASSED_NOT = 3;    //审核不通过


    const SALARY_STATUS_CREATED        = 0; //待审核
    const SALARY_STATUS_ACCEPTED        = 1; //已受理
    const SALARY_STATUS_VERIRIED        = 2; //审核通过
    const SALARY_STATUS_REJECT        = 3; //审核未通过
    const SALARY_STATUS_SENT         = 4;  //已派发
    const SALARY_STATUS_SENT_FAILED         = 5; //派发失败



    protected static $cacheUseParentClass = false;

    protected static $cacheLevel = self::CACHE_LEVEL_FIRST;

    protected static $cacheMinutes = 0;

    public static $validStatuses     = [
        //self::STATUS_DISTRIBUTED => 'status-distributed',
        //self::STATUS_DISTRIBUTED_FAILED     => 'status-distributed-failed',
        //self::STATUS_REVIEW_PASSED     => 'status-review-passed',
        //self::STATUS_REVIEW_PASSED_NOT         => 'status-review-passed-not',

        self::SALARY_STATUS_CREATED         => 'salary-status-created',
        self::SALARY_STATUS_ACCEPTED         => 'salary-status-accepted',
        self::SALARY_STATUS_VERIRIED         => 'salary-status-veriried',
        self::SALARY_STATUS_REJECT         => 'salary-status-reject',
        self::SALARY_STATUS_SENT         => 'salary-status-sent',
        self::SALARY_STATUS_SENT_FAILED         => 'salary-status-sent-failed',
    ];

    protected $fillable = [
        'id',
        'year',
        'month',
        'user_id',
        'is_agent',
        'is_tester',
        'username',
        'level_three_user_count',
        'level_four_user_count',
        'level_five_user_count',
        'salary',
        'status',
        'created_at',
        'updated_at',
    ];

    public static $sequencable = false;

    public static $enabledBatchAction = false;

    protected $validatorMessages = [];

    protected $isAdmin = true;

    public static $resourceName = 'UserMonthSalaries';

    protected $softDelete = false;

    protected $defaultColumns = [ '*' ];

    protected $hidden = [];

    protected $visible = [];

    public static $treeable = '';

    public static $foreFatherIDColumn = '';

    public static $foreFatherColumn = '';

    public static $columnForList = [
        'id',
        'year',
        'month',
        'username',
        'level_three_user_count',
        'level_four_user_count',
        'level_five_user_count',
        'salary',
        'status',
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
        //'status',
    ];

    public static $listColumnMaps = [];

    public static $viewColumnMaps = [];

    public static $htmlSelectColumns = [
        'status'=>'aValidStatuses'
    ];

    public static $htmlTextAreaColumns = [];

    public static $htmlNumberColumns = [];

    public static $htmlOriginalNumberColumns = [];

    public static $amountAccuracy = 0;

    public static $originalColumns;

    public $orderColumns = [
        'year'     => 'desc',
        'month'    => 'desc',
        'salary' => 'desc',
        'username' => 'asc',
    ];

    public static $titleColumn = 'username';

    public static $mainParamColumn = 'user_id';

    public static $rules = [
        'year' => 'required',
        'month' => 'required',
        'user_id' => 'required',
        'username' => 'required|max:16',
        'level_three_user_count' => 'integer|min:0',
        'level_four_user_count' => 'integer|min:0',
        'level_five_user_count' => 'integer|min:0',
        'salary' => 'min:0',
        'status' => 'required|integer|in:0,1,2,3,4,5'//required|integer|in:0,1,2,3,4,5
    ];

    protected function beforeValidate() {
        if (!$this->username) {
            $oUser                     = User::find($this->user_id);
            $this->is_agent            = $oUser->is_agent;
            $this->is_tester           = $oUser->is_tester;
            $this->prize_group         = $oUser->prize_group;
            $this->user_level          = $oUser->user_level;
            $this->username            = $oUser->username;
            $this->parent_user_id      = $oUser->parent_id;
            $this->parent_user         = $oUser->parent;
            $this->user_forefather_ids = $oUser->forefather_ids;
            $this->user_forefathers    = $oUser->forefathers;
        }
        return parent::beforeValidate();
    }

    /**
     * 返回UserProfit对象
     *
     * @param string $sDate
     * @param string $iUserId
     * @return UserProfit
     */
    public static function getUserMonthSalaryObject($iYear, $iMonth, $iUserId) {
        $aAttributes = [
            'user_id' => $iUserId,
            'month'   => $iMonth,
            'year'    => $iYear,
        ];
        $obj         = static::firstOrCreate($aAttributes);
        return $obj;
    }

    public static function getValidStatuses() {
        return static::_getArrayAttributes(__FUNCTION__);
    }
}