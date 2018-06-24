<?php
/**
 * 功能关系类
 * 
 * @author Winter 2016-03-22
 */
class FunctionalityRelation extends BaseModel {

    const POS_ITEM = 1;
    const POS_PAGE = 2;
    const POS_BATCH = 3;
    
    protected $table = 'functionality_relations';
    public static $resourceName = 'FunctionalityRelation';
    public static $sequencable = true;
    public static $ignoreColumnsInEdit = [
        'realm'
    ];

    public static $columnForList = [
        'functionality_id',
        'r_functionality_id',
        'label',
        'realm',
        'precondition',
        'params',
        'position',
        'new_window',
        'use_redirector',
        'disabled',
        'sequence',
        'updated_at',
    ];

    /**
     * 可用的按钮位置数组
     * @var array
     */
    public static $validPositions = [
        self::POS_ITEM => 'for-item',
        self::POS_PAGE => 'for-page',
        self::POS_BATCH => 'for-batch'
    ];
    
    public static $listColumnMaps = [
        'position' => 'position_formatted'
    ];

    public static $viewColumnMaps = [
        'position' => 'position_formatted'
    ];

    public $orderColumns = [
        'sequence' => 'asc'
    ];

    public static $treeable = false;
    public static $htmlSelectColumns = [
        'functionality_id' => 'aFunctionalities',
        'r_functionality_id' => 'aFunctionalities',
        'position' => 'aValidPositions',
        'realm' => 'aValidRealms',
    ];

    /**
     * The rules to be applied to the data.
     *
     * @var array
     */
    public static $rules = [
        'functionality_id'      => 'integer',
        'r_functionality_id'    => 'integer',
        'realm'                 => 'integer',
        'precondition'          => 'max:200',
        'params'                => 'max:200',
        'label'                 => 'between:0,50',
        'position'              => 'required|in:1,2,3',
        'button_onclick'        => 'max:64',
        'confirm_msg_key'       => 'max:64',
        'new_window'            => 'in:0, 1',
        'use_redirector'        => 'in:0, 1',
        'disabled'              => 'in:0, 1',
        'sequence'              => 'integer',
    ];

    protected $softDelete = false;
    protected $fillable = [
        'id',
        'functionality_id',
        'r_functionality_id',
        'realm',
        'position',
        'button_onclick',
        'confirm_msg_key',
        'label',
        'precondition',
        'params',
        'new_window',
        'use_redirector',
        'disabled',
        'sequence',
    ];

    public static $mainParamColumn = 'functionality_id';

    public $autoPurgeRedundantAttributes = true;

    public function functionality_relations()
    {
        return $this->hasMany('FunctionalityRelation');
    }

    public function roles()
    {
        return $this->belongsToMany('AdminRole')->withTimestamps();
    }

    public function admin_menus()
    {
        return $this->hasMany('AdminMenu', 'functionality_id')->withTimestamps();
    }

    /**
     * Explode the rules into an array of rules.
     *
     * @param  string|array  $rules
     * @return array
     */
    protected function explodeRules($rules)
    {
        foreach ($rules as $key => &$rule)
        {
            $rule = (is_string($rule)) ? explode('|', $rule) : $rule;
        }

        return $rules;
    }

    protected function beforeValidate(){
//        if (!$this->label){
        $oRightFunctionality = Functionality::find($this->r_functionality_id);
        $this->realm = $oRightFunctionality->realm;
        $this->label or $this->label = $oRightFunctionality->title;
//        }
        return parent::beforeValidate();
    }

    /**
     * 根据前置条件来判断是否显示
     * @param model $model
     * @return bool
     */
    public function isAvailable($model){
        if (!$this->precondition) return true;
        $this->precondition = str_replace('.', '->', $this->precondition);
        $function = '$valid = ' . $this->precondition . ';';
       // pr($function);exit;
        eval($function);
        return $valid;
    }

    public function compileItemButtonHref($model){
        if (!$this->isAvailable($model)){
            return false;
        }
        $sOnclick = '';
        if (isset($this->querystring)){
            $sHref = $this->route_name ? route($this->route_name) : 'javascript:void(0);';
            $p = "/\{([\w\d_]+)\}/";
            if (preg_match_all($p, $this->querystring, $matches)){
                $aSearch = $aReplace = [];
                foreach($matches[1] as $k => $sColumn){
                    $aSearch[] = $matches[0][$k];
                    $aReplace[] = $model->$sColumn;
                }
            }
//            pr($matches);
//            pr($aSearch);
//            pr($aReplace);
            $sHref .= str_replace($aSearch, $aReplace, $this->querystring);
//            pr($sHref);
//            exit;
//            or $sHref .= '?' . $this->querystring;
        }
        else{
            $sParaColumn = $this->para_column or $sParaColumn = 'id';
            $mParamsOfRoute = $this->button_type == Functionality::BUTTON_TYPE_NORMAL ? [$this->para_name => $model->$sParaColumn] : $model->$sParaColumn;
            $sHref = $this->route_name ? route($this->route_name, $mParamsOfRoute) : 'javascript:void(0);';
        }
        if ($this->button_type == Functionality::BUTTON_TYPE_DANGEROUS){
            $sUrl = $sHref;
            $sHref = 'javascript:void(0)';
            $class = get_class($model);
            $titleColumn = $class::$titleColumn;
            $sOnclick = "javascript:{$this->button_onclick}('$sUrl', '{$model->{$titleColumn}}')";  
        }
        return [
            'href' => $sHref,
            'onclick' => $sOnclick
        ];
    }
    
    public function compilePageButtonHref($aParams){
//        if (!$this->isAvailable($model)){
//            return false;
//        }
        $sOnclick = '';
        pr($aParams);
        pr($this->para_name);
        $this->url or $this->url = $this->para_name && isset($aParams[$this->para_name]) ? route($this->route_name, $aParams[$this->para_name]) : route($this->route_name);
        if ($this->button_type == Functionality::BUTTON_TYPE_DANGEROUS){
//            $class = get_class($model);
//            $titleColumn = $class::$titleColumn;
            $sOnclick = "javascript:{$this->button_onclick}('$this->url')";  
        }
        return [
            'url' => $this->url,
            'onclick' => $sOnclick
        ];
    }

    protected function getPositionFormattedAttribute() {
        return __('_functionalityrelation.' . strtolower(Str::slug(static::$validPositions[$this->attributes['position']])));
    }

    /**
     * 存入记录
     *
     * @param array $aRecords
     *
     * @return mixed
     */
    public static function createDataRecord($aRecords = []) {

        $oRecord = new static();

        $oRecord->fill($aRecords);

        $bReturn = $oRecord->save();
        if ($bReturn) {
            return $oRecord->id;
        } else {
            return false;
        }

    }

}
