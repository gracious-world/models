<?php

/**
 * 投注记录
 *
 * @author system
 */

class BetRecord extends BaseModel {

    protected $table = 'bet_records';

    protected static $cacheUseParentClass = false;

    protected static $cacheLevel = self::CACHE_LEVEL_FIRST;

    protected static $cacheMinutes = 0;

    protected $fillable = [
        'id',
        'user_id',
        'username',
        'is_tester',
        'is_agent',
        'lottery_id',
        'bet_count',
        'is_trace',
        'bet_data',
        'compressed_data',
        'created_at',
        'updated_at',
    ];

    public static $sequencable = false;

    public static $enabledBatchAction = false;

    protected $validatorMessages = [];

    protected $isAdmin = true;

    public static $resourceName = 'BetRecord';

    protected $softDelete = false;

    protected $defaultColumns = [ '*' ];

    protected $hidden = [];

    protected $visible = [];

    public static $treeable = '';
    
    public static $foreFatherIDColumn = '';

    public static $foreFatherColumn = '';

    public static $columnForList = [
        'id',
        'username',
        'lottery_id',
        'bet_count',
        'created_at',
    ];

    public static $totalColumns = [];

    public static $totalRateColumns = [];

    public static $weightFields = [];

    public static $classGradeFields = [];

    public static $floatDisplayFields = [];

    public static $noOrderByColumns = [];

    public static $ignoreColumnsInView =[
        'user_id',
        'updated_at',
    ];

    public static $ignoreColumnsInEdit = [ ];

    public static $listColumnMaps = [];

    public static $viewColumnMaps = [];

    public static $htmlSelectColumns = [];

    public static $htmlTextAreaColumns = [];

    public static $htmlNumberColumns = [];

    public static $htmlOriginalNumberColumns = [];

    public static $amountAccuracy = 0;

    public static $originalColumns;

    public $orderColumns = [
        'id' => 'desc',
    ];

    public static $titleColumn = 'username';

    public static $mainParamColumn = 'user_id';

    public static $rules = [
        'user_id' => 'required|integer|min:0',
        'username' => 'required|max:16',
        'lottery_id' => 'required|integer|min:1',
        'bet_count' => 'required|integer|min:1',
        'bet_data' => 'required',
    ];

    protected function beforeValidate() {
        return parent::beforeValidate();
    }

    public static function createRecord($oUser, & $aData, & $sCompressedStr = null){
        $data = [
            'user_id' => $oUser->id,
            'username' => $oUser->username,
            'is_tester' => $oUser->is_tester,
            'is_agent' => $oUser->is_agent,
            'lottery_id' => $aData['gameId'],
            'bet_count' => count($aData['balls']),
            'is_trace' => $aData['isTrace'],
            'bet_data' => json_encode($aData),
            'compressed_data' => $sCompressedStr
        ];
        $obj = new static ($data);
        return $obj->save();
    }
}