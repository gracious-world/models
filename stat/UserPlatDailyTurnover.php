<?php

/**
 * 用户平台日流水
 *
 * @author Damon
 */
class UserPlatDailyTurnover extends BaseModel {

    protected $table                 = 'user_plat_daily_turnovers';
    public static $resourceName      = 'UserPlatDailyTurnover';
    public static $htmlNumberColumns = [
        'turnover' => 4,
    ];
    public static $columnForList     = [
    ];
    public static $listColumnMaps = [
        'turnover' => 'turnover_formatted',
    ];
    protected $fillable  = [
        'plat_id',
        'plat_identity',
        'plat_name',
        'user_id',
        'is_agent',
        'is_tester',
        'user_level',
        'username',
        'parent_user_id',
        'parent_user',
        'user_forefather_ids',
        'user_forefathers',
        'turnover',
        'prize',
        'profit',
        'date',
    ];
    public static $rules = [
        'plat_id'             => 'integer',
        'plat_identity'       => 'max:50',
        'plat_name'           => 'max:50',
        'is_tester'           => 'in:0,1',
        'date'                => 'required|date',
        'user_id'             => 'required|integer',
        'is_agent '           => 'in:0,1',
        'user_level'          => 'required|min:0',
        'username'            => 'required|max:16',
        'parent_user_id'      => 'integer',
        'parent_user'         => 'max:16',
        'user_forefather_ids' => 'max:100',
        'user_forefathers'    => 'max:1024',
        'turnover'            => 'numeric',
        'prize'               => 'numeric',
        'profit'              => 'numeric',
    ];
    public $orderColumns = [
        'date' => 'desc'
    ];
    public static $mainParamColumn = 'user_id';
    public static $titleColumn     = 'username';

    /**
     * 返回用户平台日流水对象
     *
     * @param string $sDate
     * @param int $iPlatId
     * @param string $sUserName
     * @return UserProfit
     */
    public static function getUserPlatDailyTurnoverObject($oPlat, $sUserName, $sDate) {
        //todo mark 获取对象数据
        $obj = self::where('username', '=', $sUserName)->where('date', '=', $sDate)->where('plat_id', '=', $oPlat->id)->lockForUpdate()->first();
        if (!is_object($obj)) {
            $oUser = User::where('username', '=', $sUserName)->first();
            $data  = [
                'plat_id'             => $oPlat->id,
                'plat_identity'       => $oPlat->identity,
                'plat_name'           => $oPlat->name,
                'user_id'             => $oUser->id,
                'is_agent'            => $oUser->is_agent,
                'is_tester'           => $oUser->is_tester,
                'user_level'          => $oUser->user_level,
                'username'            => $oUser->username,
                'parent_user_id'      => $oUser->parent_id,
                'parent_user'         => $oUser->parent,
                'user_forefather_ids' => $oUser->forefather_ids,
                'user_forefathers'    => $oUser->forefathers,
                'date'                => $sDate
            ];
            $obj   = new static($data);
            if (!$obj->save()) {
                return false;
            }
//            $obj = self::getUserPlatDailyTurnoverObject($oPlat,$sUserName,$sDate);
        }
        return $obj;
    }

    protected function beforeValidate() {
        return parent::beforeValidate();
    }

    /**
     * 累加销售额
     * @param float $fAmount
     * @return boolean
     */
    public function addTurnover($fAmount) {
        $this->turnover += $fAmount;
//        pr($this->attributes);
        return $this->save();
    }

    public static function updateTurnoverData($oPlat, $sUserName, $sDate, $fAmount) {
        $oTurnover = static::getUserPlatDailyTurnoverObject($oPlat, $sUserName, $sDate);
        return $oTurnover->addTurnover($fAmount);
    }

    // protected function getUserTypeFormattedAttribute() {
    //     // return static::$aUserTypes[($this->parent_user_id != null ? 'not_null' : 'null')];
    //     return __('_userprofit.' . strtolower(static::$aUserTypes[intval($this->parent_user_id != null) - 1]));
    // }

    protected function getTurnoverFormattedAttribute() {
        return $this->getFormattedNumberForHtml('turnover');
    }

}
