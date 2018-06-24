<?php

class MethodType extends BaseModel {

    protected $table                 = 'method_types';
    protected $softDelete            = false;
    protected $fillable              = [
        'lottery_type',
        'name',
        'attribute_code',
        'wn_function',
        'sequencing',
        'digital_count',
        'unique_count',
        'max_repeat_time',
        'min_repeat_time',
        'shaped',
        'shape',
    ];
    public static $resourceName      = 'MethodType';
    public static $sequencable       = false;
    public static $columnForList     = [
        'id',
        'lottery_type',
        'name',
//        'attribute_code',
        'wn_function',
        'sequencing',
//        'digital_count',
//        'unique_count',
//        'max_repeat_time',
//        'min_repeat_time',
//        'shaped',
//        'shape',
    ];
    public static $titleColumn       = 'name';
    public static $htmlSelectColumns = [
        'lottery_type' => 'aLotteryTypes',
    ];
    public $orderColumns             = [
        'lottery_type' => 'asc',
        'id'           => 'asc',
    ];
    public static $mainParamColumn   = 'lottery_type';
    public $digitalCounts            = [];
    public static $rules             = [
        'lottery_type'   => 'required|integer',
        'name'           => 'required|max:20',
        'attribute_code' => 'max:20',
        'wn_function'    => 'required|max:64',
        'sequencing'     => 'required|in:0,1',
//        'unique_count'    => 'integer|min:0|max:5',
//        'max_repeat_time' => 'integer|min:0|max:5',
//        'min_repeat_time' => 'integer|min:0|max:5',
//        'digital_count'   => 'integer|min:0|max:5',
//        'shaped'          => 'required|in:0,1',
//        'shape'           => 'max:30',
    ];
    public static $validAttributeCodes = [
        'A', // 区间
        'S', // 大小单双
        'I', // 趣味
        'U', // 和值
        'O' // 原始数字
    ];
    public static $validNums = [
        'A' => '0-4', // 区间
        'S' => '0-3', // 大小单双
        'D' => '0-1', // 单双
        'O' => '0-9', // 原始
        'B' => '0-1', // 大小
        'U' => '0-27'
    ];

    protected function beforeValidate() {
        $this->sequencing or $this->sequencing      = 0;
        $this->shaped or $this->shaped          = 0;
        $this->digital_count or $this->digital_count   = null;
        $this->unique_count or $this->unique_count    = null;
        $this->max_repeat_time or $this->max_repeat_time = null;
        $this->min_repeat_time or $this->min_repeat_time = null;
//        pr($this->getAttributes());
//        exit;
        return parent::beforeValidate();
    }

}