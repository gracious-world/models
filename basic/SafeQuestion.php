<?php

class SafeQuestion extends BaseModel {
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'safe_questions';

    /**
     * 资源名称
     * @var string
     */
    public static $resourceName = 'SafeQuestion';
    public static $titleColumn = 'content';
    public static $columnForList = [
        'id',
        'content',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'id',
        'content',
        'created_at',
        'updated_at'
    ];
    public static $rules = [
        'content'        => 'required|between:0,500',
    ];
    public $orderColumns = [
        'created_at' => 'desc'
    ];

}
