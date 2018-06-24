<?php

class WayGroupWay extends BaseModel {

    protected static $cacheLevel     = self::CACHE_LEVEL_FIRST;
    protected $table                 = 'way_group_ways';
    protected $softDelete            = false;
    protected $fillable              = [
        'series_id',
        'terminal_id',
        'group_id',
        'series_way_id',
        'title',
        'en_title',
        'for_display',
        'for_search',
        'sequence',
    ];
    public static $resourceName      = 'Way Group Way';
    public static $sequencable       = true;
    public static $columnForList     = [
        'series_id',
        'terminal_id',
        'group_id',
        'title',
        'en_title',
        'series_way_id',
        'for_display',
        'sequence',
    ];
    public static $htmlSelectColumns = [
        'series_id'     => 'aSeries',
        'group_id'      => 'aWayGroups',
        'series_way_id' => 'aSeriesWays',
        'terminal_id'   => 'aTerminals',
    ];
    public $orderColumns             = [
        'sequence' => 'asc',
        'id'       => 'asc'
    ];
    public static $mainParamColumn   = 'group_id';
    public static $rules             = [
        'group_id'      => 'required|integer',
        'terminal_id'   => 'integer',
        'sequence'      => 'integer',
        'title'         => 'max:20',
        'en_title'      => 'max:30',
        'series_way_id' => 'required|integer',
        'for_display'   => 'required|in:0,1',
        'for_search'    => 'required|in:0,1',
    ];
    public static $treeable = false;

    protected function afterSave($oSavedModel) {
        parent::afterSave($oSavedModel);
        WayGroup::deleteLotteryCache($oSavedModel->series_id, $oSavedModel->terminal_id);
    }

    protected function beforeValidate() {
        if ($this->group_id) {
            $oGroup          = WayGroup::find($this->group_id);
            $this->series_id = $oGroup->series_id;
        }
        if (!$this->title && $this->series_way_id) {
            $oSeriesWay  = SeriesWay::find($this->series_way_id);
            $this->title = $oSeriesWay->short_name;
        }
        parent::beforeValidate();
    }

}
