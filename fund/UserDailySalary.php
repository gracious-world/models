<?php

/**
 * 日工资
 * @author lucky
 * @created_at 2016-10-21
 * Class UserDailysalary
 */
class UserDailySalary extends BaseModel {

    protected $table                   = 'user_daily_salaries';
    public static $resourceName        = 'UserDailySalary';
    public static $amountAccuracy      = 6;
    public static $htmlNumberColumns   = [
        'salary' => 2,
    ];
    protected $fillable                = [
        'id',
        'user_id',
        'username',
        'is_agent',
        'is_tester',
        'status',
        'user_level',
        'turnover',
        'salary',
        'year',
        'month',
        'date',
        'created_at',
        'updated_at'
    ];
    public static $rules               = [
        'user_id'    => 'required',
        'username'   => 'required',
        'is_agent'   => 'required|in:0,1',
        'is_tester'  => 'required|in:0,1',
        'status'     => 'required|integer',
        'user_level' => 'required|integer',
        'turnover'   => 'numeric',
        'salary'     => 'numeric',
        'date'       => 'required|date',
        'year'       => 'required|integer',
        'month'       => 'required|max:12|min:1',
    ];
    public static $ignoreColumnsInEdit = [
        'id',
        'user_id',
        'username',
        'is_agent',
        'is_tester',
        'status',
        'user_level',
        'date',
        'created_at',
        'updated_at'
    ];
    public static $ignoreColumnsInView = [
        'id',
        'user_id',
        'role_id',
        'account_id'
    ];

    const SALARY_STATUS_CREATED     = 0; //待审核
    const SALARY_STATUS_ACCEPTED    = 1; //已受理
    const SALARY_STATUS_VERIRIED    = 2; //审核通过
    const SALARY_STATUS_REJECT      = 3; //审核未通过
    const SALARY_STATUS_SENT        = 4;  //已派发
    const SALARY_STATUS_SENT_FAILED = 5; //派发失败

    public static $validStatus     = [
        self::SALARY_STATUS_CREATED     => 'salary-status-created',
        self::SALARY_STATUS_ACCEPTED    => 'salary-status-accepted',
        self::SALARY_STATUS_VERIRIED    => 'salary-status-verified',
        self::SALARY_STATUS_REJECT      => 'salary-status-reject',
        self::SALARY_STATUS_SENT        => 'salary-status-sent',
        self::SALARY_STATUS_SENT_FAILED => 'salary-status-sent-failed',
    ];
    public static $listColumnMaps  = [
        'status'     => 'status_formatted',
        'turnover'   => 'turnover_formatted',
        'salary'     => 'salary_formatted',
        'user_level' => 'user_level_formatted'
    ];
    public static $viewColumnMaps  = [
        'status'     => 'status_formatted',
        'turnover'   => 'turnover_formatted',
        'salary'     => 'salary_formatted',
        'user_level' => 'user_level_formatted'
    ];
    public static $columnForList   = [
        'year',
        'month',
        'date',
        'username',
        'role_name',
        'user_level',
        'status',
        'turnover',
        'salary',
    ];
    public $orderColumns           = [
        'date' => 'desc'
    ];
    public static $titleColumn     = 'date';
    public static $mainParamColumn = 'parent_id';

    /**
     * 获取审核和发放状态
     * @author lucky
     * @created_at 2016-10-21
     * @return string
     */
    protected function getStatusFormattedAttribute() {
        return __('_userdailysalary.' . strtolower(Str::slug(static::$validStatus[$this->status])));
    }

    /**
     * 用户等级显示
     * @author lucky
     * @created_at 2016-10-21
     * @return int
     */
    protected function getUserLevelFormattedAttribute() {
        return (int) $this->user_level;
    }

    /**
     * 投注金额显示
     * @author lucky
     * @created_at 2016-10-21
     * @return type
     */
    protected function getTurnoverFormattedAttribute() {
        return $this->getFormattedNumberForHtml('turnover', true);
    }

    /**
     * 工资金额显示
     * @author lucky
     * @created_at 2016-10-21
     * @return type
     */
    protected function getSalaryFormattedAttribute() {
        return $this->getFormattedNumberForHtml('salary', true);
    }

    /**
     * 日工资下载报表
     * @param $aData
     * @param $aFields
     * @param $aConvertFields
     * @param null $aBanks
     * @param null $aUser
     * @return array
     */
    public function makeData($aData, $aFields, $aConvertFields, $aBanks = null, $aUser = null) {
        $aResult = array();
        foreach ($aData as $oDeposit) {
            $a = [];
            foreach ($aFields as $key) {
                if ($oDeposit->$key === '') {
                    $a[] = $oDeposit->$key;
                    continue;
                }
                if (array_key_exists($key, $aConvertFields)) {
                    switch ($aConvertFields[$key]) {
                        case 'boolean':
                            $a[] = $oDeposit->$key ? __('Yes') : __('No');
                            break;
                    }
                } else {
                    $a[] = $oDeposit->$key;
                }
            }
            $aResult[] = $a;
        }
        return $aResult;
    }

    /**
     * 获取用户薪资记录
     *
     * @author  Garin
     * @date 2016-11-09
     *
     * @param $iUserId
     * @param $sExtraData
     * @param $sDate
     *
     * @return mixed
     */
    public static function getUserDailySalary($iUserId, $sExtraData, $sDate = '') {
        if (!$iUserId && empty($sExtraData) && empty($sDate)) {
            return false;
        }
        if ($iUserId) {
            $sWhere['user_id'] = ['=', $iUserId];
        }
        if (!empty($sExtraData)) {
            $sWhere['extra_data'] = ['=', $sExtraData];
        }
        if (!empty($sDate)) {
            $sWhere['date'] = ['=', $sDate];
        }
        return static::doWhere($sWhere)->first();
    }

    /**
     * 记录用户日薪
     *
     * @author  Garin
     * @date  2016-11-09
     *
     * @param      $oUser
     * @param      $aUserProfits
     * @param      $sDate
     * @param      $aExtraData
     * @param      $oRoles
     *
     * @return mixed
     */
    public static function createUserDailySalary($oUser, $aUserProfits, $sDate, $aExtraData, $oRole = false, & $sErrMsg = null) {

        DB::connection()->beginTransaction();
        $oAccount = Account::lock($oUser->account_id, $iLocker);
        $oUserDailySalary = new static();

        $oUserDailySalary->user_id    = $oUser->id;
        $oUserDailySalary->username   = $oUser->username;
        $oUserDailySalary->user_level = $oUser->user_level;
        $oUserDailySalary->is_agent   = $oUser->is_agent;
        $oUserDailySalary->is_tester  = $oUser->is_tester;
        $oUserDailySalary->turnover   = $aUserProfits['turnover'];
        $oUserDailySalary->salary     = $aUserProfits['salary'];
        $oUserDailySalary->date       = $sDate;
        $oUserDailySalary->year       = Date('Y', strtotime($sDate));
        $oUserDailySalary->month      = Date('m', strtotime($sDate));
        $oUserDailySalary->status     = static::SALARY_STATUS_SENT;
        $oUserDailySalary->note       = isset($aExtraData['note']) ? $aExtraData['note'] : '';
        $oUserDailySalary->extra_data = isset($aExtraData['extra_data']) ? $aExtraData['extra_data'] : '';
        //有角色信息，保存下来
        if ($oRole) {
            $oUserDailySalary->role_id   = $oRole->id;
            $oUserDailySalary->role_name = $oRole->name;
        }

        //只记录信息，不用锁钱包了，用于该字段不能为空，给个默认值0
        $oUserDailySalary->account_id = 0;
        $bReturn = $oUserDailySalary->save();
        if (!$bReturn) {
                $oUserDailySalary = new UserDailySalary;
                $sErrMsg=" Error: UserId " . $oUser->id . " save daily salary failed:" . $oUserDailySalary->getValidationErrorString();
//                $this->writeLog(" Error: UserId " . $iUserId . " save daily salary failed:" . $oUserDailySalary->getValidationErrorString());
                DB::connection()->rollback();
            }

        if (empty($oAccount)) {
            DB::connection()->rollback();
            $sErrMsg = __("basic.no-account-found");
            return false;
//            return $this->goBackToIndex("error", __("basic.no-account-found"));
        }

        if (Transaction::where("extra_data", '=', $oUserDailySalary->extra_data)->first()) {
            $sErrMsg = __("_userdailysalary.already-send-salary");
            DB::connection()->rollback();
            return false;
        }

        $aExtraData = [
            'note' => $oUserDailySalary->note,
            'extra_data' => $oUserDailySalary->extra_data,
        ];

        $iReturn = Transaction::addTransaction($oUser, $oAccount, TransactionType::TYPE_PROMOTIANAL_BONUS, $oUserDailySalary->salary, $aExtraData);
        Account::unlock($oAccount->id, $iLocker);

        if ($iReturn != Transaction::ERRNO_CREATE_SUCCESSFUL) {
            DB::connection()->rollback();
            $sErrMsg=sprintf("user_daily_salary add to transaction failed:user_id=%d date=%s extra_data=%s", $oUser->id, $oUserDailySalary->date, $oUserDailySalary->extra_data);
//            $this->writeLog(sprintf("user_daily_salary add to transaction failed:user_id=%d date=%s extra_data=%s", $oUser->id, $oUserDailySalary->date, $oUserDailySalary->extra_data));
            return false;
        }

        DB::connection()->commit();

       return true;
    }

    /**
     * 查询某天内用户的团队盈亏
     *
     * @author Garin
     * @date 2016-11-09
     *
     * @param $sDate 时间
     * @param $aUserIds 用户id
     * @param $iUserLevel 用户层级  [默认值false,不限制层级;其他和系统层级对应的值则限制生效]
     *
     * @return mixed
     */
    public static function getProfitByDateAndUserIds($sDate, $aUserIds, $iUserLevel = false) {
        if (empty($sDate)) {
            return false;
        }

        $aConditions['date'] = ['=', $sDate];
        //用户层级 默认为不限制层级
        if ($iUserLevel !== false && $iUserLevel >= 0) {
            $aConditions['user_level'] = ['=', $iUserLevel];
        }

        if (!empty($aUserIds)) {
            $aConditions['user_id'] = ['in', $aUserIds];
        }

        return static::doWhere($aConditions)->get(['id', 'user_id', 'turnover', 'date']);
    }

}
