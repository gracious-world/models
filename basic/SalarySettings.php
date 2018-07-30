<?php

/**
 * 日工资设置模型
 */
use Illuminate\Support\Facades\Redis;

class SalarySettings extends BaseModel {

    const STATUS_ACCEPTED_NOT = 0;
    const STATUS_ACCEPTED_YES = 1;
    const STATUS_ACCEPTED_DENY = 2;
public static $amountAccuracy = 3;
    public static $rules          = [
        'user_id'  => 'required',
        'username' => 'required|max:32',
//        'parent_id' => 'required',
//        'parent' =>'required',
//        'forefather_ids'=>'required',
//        'forefathers'=>'required',
        'turnover' => 'required',
//        'salary'   => 'required',
        'total_rate' => 'required|numeric|max:1|min:0',
        'rate' => 'required|numeric|max:1|min:0'
    ];

    protected $table              = 'salary_settings';
    public static $titleColumn     = 'username';

    public static $columnForList  = [
        'id',
        'parent',
        'forefathers',
        'username',
        'turnover',
        'total_rate',
        'rate',
        'created_at',
        'updated_at'
    ];

    public $orderColumns          = [
        'turnover' => 'asc'
    ];

    protected $fillable           = [
        'user_id',
        'username',
        'turnover',
        'total_rate',
        'rate',
        'created_at',
        'updated_at',
    ];

    public static $listColumnMaps = [
        'turnover' => 'friendly_turnover',
        'salary'   => 'friendly_salary'
    ];

    public static $ignoreColumnsInEdit = [
        'user_id',
        'forefather_ids',
        'parent_id',
        'parent',
        'forefathers'
    ];



    public static $aBasicSalarySettings = [
          1000 => 10,
          10000 => 50,
          100000 => 100
    ];

    protected function beforeValidate()
    {
        $iCount=static::where('username',$this->username)->count();
        if(!$this->id) {
            if ($iCount >= 5) {
                $this->validationErrors->add('count', __('_salarysettings.count-exceed-limit'));
                return false;
            }
        }
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
    public static function getSalaryTotalRateByUserObj($oUser) {
        $is_top_agent = !(bool)$oUser->parent_id;
        $iUserId = $oUser->id;
        if($is_top_agent){
            //for all top agent
            $oSalarySettings = static::where('username', 'topagent')
                ->first();
        }else {
            $oSalarySettings = static::where('user_id', $iUserId)
                ->first();
        }
        return  $oSalarySettings ? $oSalarySettings->total_rate : 0;
//        return array_column($oSalarySettings, 'rate', 'turnover');
    }

    public static function getTopAgentSalarySettings() {
        $aSalarySettings = static::$aBasicSalarySettings;
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
                $iDailySalary = $iTurnover*$v;
                break;
            }
        }
        return $iDailySalary;
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
    public static function getSalaryRateByTurnover($aSalarySettings, $iTurnover) {
        $iDailySalary = 0;
        if (empty($aSalarySettings) || empty($iTurnover)) {
            return $iDailySalary;
        }
        foreach ($aSalarySettings as $key => $v) {
            if ($key <= $iTurnover) {
                $iDailySalaryRate = $v;
                break;
            }
        }
        return $iDailySalaryRate;
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
            $oSalarySetting->salary = $fRate;
            $oSalarySetting->is_accepted = 0;
            if(!$oSalarySetting->save()) {
                DB::connection()->rollBack();
                return false;
            }

        }

        DB::connection()->commit();
        return static::where('user_id',$oUser->id)->get();
    }

    protected function setParentIdAttribute($iParentId) {
        $this->attributes['parent_id'] = $iParentId;
    }

}
