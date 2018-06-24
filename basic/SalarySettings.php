<?php

/**
 * 日工资设置模型
 */
use Illuminate\Support\Facades\Redis;

class SalarySettings extends BaseModel {

    const STATUS_ACCEPTED_NOT = 0;
    const STATUS_ACCEPTED_YES = 1;
    const STATUS_ACCEPTED_DENY = 2;

    public static $rules          = [
        'username' => 'required|max:32',
        'turnover' => 'required',
        'salary'   => 'required'
    ];
    protected $table              = 'salary_settings';
    public static $columnForList  = [
        'username',
        'turnover',
        'salary'
    ];
    public $orderColumns          = [
        'turnover' => 'asc'
    ];

    protected $fillable           = [
        'user_id',
        'username',
        'turnover',
        'salary',
        'created_at',
        'updated_at',
    ];

    public static $listColumnMaps = [
        'turnover' => 'friendly_turnover',
        'salary'   => 'friendly_salary'
    ];

    public static $aBasicSalarySettings = [
          1000 => 10,
          10000 => 50,
          100000 => 100
    ];

    protected function beforeValidate()
    {
        $oUser = User::getUserByUsername($this->username);
        if (!$oUser) {
            $this->validationErrors['user_id']=__('_user.missing-user');
            return false;
        }
        $this->user_id = $oUser->id;
        return parent::beforeValidate();
    }
    /**
     * 获取投注额和日薪对应关系数据
     *
     * @author  Garin
     * @date 2016-11-09
     *
     * @return mixed
     */
    public static function getSalarySettingsByUserId($iUserId) {
        $aSalarySettings = static::select('turnover', 'salary')
            ->where('user_id',$iUserId)
            ->orderBy('turnover', 'desc')
            ->get()
            ->toArray();
        return array_column($aSalarySettings, 'salary', 'turnover');
    }

    /**
     * 根据投注额计算用户日工资
     *
     * @author Garin
     * @date 2016-11-09
     *
     * @param $aSalarySettings 日工资发放配置数组
     * @param $iTurnover 用户投注额
     *
     * @return int
     */
    public static function getSalaryByTurnover($aSalarySettings, $iTurnover) {
        $iDailySalary = 0;
        if (empty($aSalarySettings) || empty($iTurnover)) {
            return $iDailySalary;
        }
        foreach ($aSalarySettings as $key => $v) {
            if ($key <= $iTurnover) {
                $iDailySalary = $v;
                break;
            }
        }
        return $iDailySalary;
    }

    protected function getFriendlyTurnoverAttribute() {
        return ($this->turnover / 10000) . '万';
    }

    protected function getFriendlySalaryAttribute() {
        return $this->salary . '元';
    }

    static function createDefaultSalarySetting($oUser){
        DB::connection()->beginTransaction();

        foreach(static::$aBasicSalarySettings as $iTurnover => $fRate){
            $oSalarySetting = new static;
            $oSalarySetting->user_id = $oUser->id;
            $oSalarySetting->username = $oUser->username;
            $oSalarySetting->game_type = 1;
            $oSalarySetting->game_type_name = '数字彩';
            $oSalarySetting->turnover = $iTurnover;
            $oSalarySetting->rate = $fRate;
            $oSalarySetting->is_accepted = 1;
            if(!$oSalarySetting->save()) {
                DB::connection()->rollBack();
                return false;
            }

        }

        DB::connection()->commit();
        return static::where('user_id',$oUser->id)->get();
    }

}
