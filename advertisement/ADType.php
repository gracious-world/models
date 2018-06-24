<?php

class AdType extends BaseModel {
    protected $table = 'ad_types';
    public static $resourceName = 'AdType';
    public static $titleColumn = 'type_name';
    protected $hidden = [];
    protected $guarded = [];
    protected $softDelete = false;
    protected $fillable = [
        'type_name',
        'created_at',
        'updated_at',
    ];
    public static $columnForList = [
        'id',
        'type_name',
        'created_at',
        'updated_at',
    ];
    public static $htmlSelectColumns = [];
    public $orderColumns = [
        'updated_at' => 'asc'
    ];
    public static $rules = [
        // 'name'       => 'required|max:50',
        // 'type_id'    => 'max:50',
        // 'description'=> 'required|max:50',
        // 'text_length'=> 'max:50',
        // 'pic_width'  => 'max:50',
        // 'pic_height' => 'max:50',
        // 'is_closed'  => 'max:50',
        // 'roll_time'  => 'max:50',
        'type_name'  => 'required|max:50',
    ];

    public static function & getAllAdTypeArray()
    {
        $aAdType = AdType::lists('type_name','id');
        return $aAdType;
    }
}