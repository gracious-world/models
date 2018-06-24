<?php

/**
 * 平水设置记录
 *
 * @author system
 */

class ZeroCommissionSet extends BaseModel {

    protected $table = 'zero_commission_sets';

    protected static $cacheUseParentClass = false;

    protected static $cacheLevel = self::CACHE_LEVEL_FIRST;

    protected static $cacheMinutes = 0;

    protected $fillable = [
        'id',
        'top_agent_id',
        'top_agent',
        'user_parent_id',
        'user_parent',
        'user_id',
        'username',
        'prize_group',
        'created_at',
        'updated_at',
    ];
    
    public static $sequencable = false;

    public static $enabledBatchAction = false;

    protected $validatorMessages = [];

    protected $isAdmin = true;

    public static $resourceName = 'ZeroCommissionSet';

    protected $softDelete = false;

    protected $defaultColumns = [ '*' ];

    protected $hidden = [];

    protected $visible = [];

    public static $treeable = '1';
    
    public static $foreFatherIDColumn = '';

    public static $foreFatherColumn = '';

    public static $columnForList = [
        'top_agent',
        'user_parent',
        'username',
        'prize_group',
        'created_at',
    ];

    public static $totalColumns = [];

    public static $totalRateColumns = [];

    public static $weightFields = [];

    public static $classGradeFields = [];

    public static $floatDisplayFields = [];

    public static $noOrderByColumns = [];

    public static $ignoreColumnsInView = [
        'top_agent_id',
        'user_parent_id',
        'user_id',
    ];

    public static $ignoreColumnsInEdit = [
        'id',
        'top_agent',
        'user_parent_id',
        'user_parent',
        'user_id',
        'username',
        'prize_group',
        'created_at',
        'updated_at',
    ];

    public static $listColumnMaps = [];

    public static $viewColumnMaps = [];

    public static $htmlSelectColumns = [];

    public static $htmlTextAreaColumns = [];

    public static $htmlNumberColumns = [];

    public static $htmlOriginalNumberColumns = [];

    public static $amountAccuracy = 0;

    public static $originalColumns;

    public $orderColumns = [];

    public static $titleColumn = 'username';

    public static $mainParamColumn = 'top_agent_id';

    public static $rules = [
        'top_agent_id' => 'required|min:0',
        'top_agent' => 'required|max:16',
        'user_parent_id' => 'required|min:0',
        'user_parent' => 'required|max:16',
        'user_id' => 'required|min:0',
        'username' => 'required|max:16',
        'prize_group' => 'required|min:0',
    ];

    protected function beforeValidate() {
        return parent::beforeValidate();
    }
    
    public static function & compileData($oUser){
        $data = [];
        if (!$oUser->parent_id){
            return $data;
        }
        $oAgent = UserUser::find($oUser->parent_id);
        if ($oAgent->prize_group != $oUser->prize_group){
            return $data;
        }
        $iTopId = $oAgent->getTopAgentId();
        $oTopAgent = User::find($iTopId);
        $data = [
            'top_agent_id' => $iTopId,
            'top_agent' => $oTopAgent->username,
            'user_parent_id' => $oUser->parent_id,
            'user_parent' => $oUser->parent,
            'user_id' => $oUser->id,
            'username' => $oUser->username,
            'prize_group' => $oUser->prize_group
        ];
        return $data;
    }
    
    public static function createRecord($oUser){
        if ($data = static::compileData($oUser)){
            $obj = new static($data);
            if (!$bSucc = $obj->save()){
                pr($obj->getValidationErrorString());
                return false;
            }
            return $bSucc;
        }
        else{
            return true;
        }
    }
}