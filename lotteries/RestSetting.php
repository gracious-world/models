<?php

/*
 * 休市管理模型类
 * 作用：生成彩种奖期时，按照休市信息配置奖期生成的逻辑(奖期是否连续，指定时间段不需要生成奖期等)
 */

class RestSetting extends BaseModel {

    public static $resourceName      = 'RestSetting';
    public static $columnForList     = [
        'lottery_id',
        'periodic',
        'begin_date',
        'end_date',
        'week',
        'begin_time',
        'end_time',
        'issue_successive',
    ];
    public static $htmlSelectColumns = [
        'lottery_id' => 'aLotteries',
//        'periodic' => 'aRestTypes',
    ];
    public static $rules = [
        'periodic' => 'required|in:0,1',
        'issue_successive' => 'required|in:0,1',
        'begin_date'       => 'date|dateformat:Y-m-d',
        'end_date'         => 'date|dateformat:Y-m-d',
        'begin_time'       => 'date|dateformat:Y-m-d H:i:s',
        'end_time'         => 'date|dateformat:Y-m-d H:i:s',
        'week'             => 'max:20'
    ];
    protected $fillable  = [
        'id',
        'lottery_id',
        'periodic',
        'begin_date',
        'end_date',
        'week',
        'begin_time',
        'end_time',
        'issue_successive',
    ];

    const TYPE_DRAW_TIME = 0;
    const TYPE_REPEATE   = 1;

    public static $restTypes = [
        self::TYPE_DRAW_TIME => 'Draw Time',
        self::TYPE_REPEATE   => 'Repeat'
    ];
    protected $table         = 'rest_settings';

    /**
     * 根据彩种id,获取休市信息
     * @param int $iLotteryId  彩种id
     * @return array
     */
    public static function getRestSettings($iLotteryId) {
        return static::where('lottery_id', $iLotteryId)->get()->toArray();
    }

    /**
     * 从对象中提取以指定字段组成的数组
     * @param object $oModelData  对象数据
     * @param array $aFields     字段数组
     * @return type 数组
     */
    private static function generateArrayByObject($oModelData, $aFields = array()) {
        if (empty($aFields)) {
            return objectToArray($oModelData);
        } else if (is_array($aFields)) {
            $aModel = array();
            foreach ($aFields as $v) {
                $aModel[$v] = $oModelData->$v;
            }
            return $aModel;
        } else {
            return array();
        }
    }

    protected function beforeValidate() {
        $this->periodic or $this->periodic = 0;
        foreach ($this->columnSettings as $sColumn => $aSettings) {
            switch ($sColumn) {
                case 'end_date':
                case 'begin_date':
                case 'begin_time':
                case 'end_time':
                    !empty($this->$sColumn) or $this->$sColumn = null;
                    break;
            }
        }

        return parent::beforeValidate();
    }

    public static function getRestTypes() {
        return self::_getArrayAttributes(__FUNCTION__);
    }

}
