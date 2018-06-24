<?php

class PercentWay extends BaseModel {

    const AG_PERCENT_WAY = 3;
    const GA_PERCENT_WAY = 4;

    protected static $cacheLevel = self::CACHE_LEVEL_FIRST;
    protected $table = 'percent_ways';
    protected $softDelete = false;
    protected $fillable = [
        'id',
        'series_id',
        'lottery_id',
        'identity',
        'name',
        'short_name',
        'top_agent_min_value',
        'top_agent_max_value'
    ];

    /**
     * number字段配置
     * @var array
     */
    public static $htmlNumberColumns = [
    ];
    public static $resourceName      = 'Percent Way';

    public static $titleColumn = 'name';

    /**
     * the columns for list page
     * @var array
     */
    public static $columnForList = [
        'series_id',
        'lottery_id',
        'identity',
//        'probability',
        'name',
        'short_name',
        'top_agent_min_value',
        'top_agent_max_value'
    ];

     public static $listColumnMaps      = [
        // 'account_available' => 'account_available_formatted',
        'top_agent_min_value'    => 'min_rate_formatted',
        'top_agent_max_value' => 'max_rate_formatted',
    ];

    public static $viewColumnMaps = [
        'top_agent_min_value' => 'min_rate_formatted',
        'top_agent_max_value' => 'max_rate_formatted',
    ];

    /**
     * 下拉列表框字段配置
     * @var array
     */
    public static $htmlSelectColumns = [
        'series_id' => 'aSeries',
        'lottery_id' => 'aLotteries',
    ];

    /**
     * order by config
     * @var array
     */
    public $orderColumns = [
        'id'     => 'desc',
    ];

    public static $jcWays = [
        'single' => 1,
        'multi' => 2
    ];

    /**
     * the main param for index page
     * @var string
     */
//    public static $mainParamColumn = 'group_id';
    public static $rules           = [
        'series_id' => 'required|integer',
        'lottery_id' => 'required|integer',
        'identity' => 'required|max:15',
        'name' => 'required|max:30',
        'short_name' => 'required|max:30',
        'top_agent_min_value' => 'required|numeric|max:1',
        'top_agent_max_value' => 'required|numeric|max:1'
    ];

    protected function beforeValidate() {
//        if (empty($this->basic_method_id)){
//            return false;
//        }
//        $oBasicMethod = BasicMethod::find($this->basic_method_id);
//        $this->lottery_type_id = $oBasicMethod->type;
        return parent::beforeValidate();
    }

    protected function getMinRateFormattedAttribute() {
        $number = $this->attributes['top_agent_min_value'] * 100;
        return $number.'%';
    }

    protected function getMaxRateFormattedAttribute() {
        $number = $this->attributes['top_agent_max_value'] * 100;
        return $number.'%';
    }

    public static function getPercentRateByIdentity($sIdentity){
        $row = static::where('identity','=',$sIdentity)->first();
        if(empty($row)){
            $result = [
                'min' => 0,
                'max' => 0
            ];
        }else{
            $result = [
                'min' => $row->top_agent_min_value,
                'max' => $row->top_agent_max_value
            ];
        }
        return $result;
    }
}
