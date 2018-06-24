<?php

/**
 * 广告分类模型
 * @author okra
 * @version 1.0
 * @date 2017-05-24
 */
class AdCategory extends BaseModel {

    protected $table = 'ad_categories';

    protected static $cacheUseParentClass = false;

    protected static $cacheLevel = self::CACHE_LEVEL_NONE;

    protected static $cacheMinutes = 0;

    protected $fillable = [
        'id',
        'name',
        'parent_id',
        'sub_parent_id',
        'parent',
        'template',
        'created_at',
        'updated_at',
    ];

    public static $sequencable = false;

    public static $enabledBatchAction = false;

    protected $validatorMessages = [];

    protected $isAdmin = true;

    public static $resourceName = 'AdCategory';

    protected $softDelete = false;

    protected $defaultColumns = ['*'];

    protected $hidden = [];

    protected $visible = [];

    public static $treeable = '1';

    public static $foreFatherIDColumn = '';

    public static $foreFatherColumn = '';

    public static $columnForList = [
        'id',
        'name',
        'parent_id',
        'sub_parent_id',
        'parent',
        //'template',
        'created_at',
        'updated_at',
    ];

    public static $totalColumns = [];

    public static $totalRateColumns = [];

    public static $weightFields = [];

    public static $classGradeFields = [];

    public static $floatDisplayFields = [];

    public static $noOrderByColumns = [];

    public static $ignoreColumnsInView = [
    ];

    public static $ignoreColumnsInEdit = [
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

    public static $titleColumn = 'id';

    public static $mainParamColumn = '';

    public static $rules = [
        'name' => 'required|max:50',
        'parent_id' => 'required|min:0',
        'sub_parent_id' => 'required|min:0',
        'parent' => 'required|max:100',
        //'template' => 'required|max:50',
    ];

    protected function beforeValidate() {
        return parent::beforeValidate();
    }

    /**
     * 取得活动类别
     *
     * @author　okra
     * @date 2017-5-25
     * @param int $iParentId 父编号
     * @return Array 活动类别 $aActivityListName
     */
    protected function getByParentId($iParentId) {
        $oAdCategoryList = AdCategory::where('parent_id', $iParentId)->get();
        $aTmp = [];
        foreach ($oAdCategoryList as $oAdCategory) {
            $aTmp[] = $oAdCategory->name;
        }
        $aActivityListName = array_unique($aTmp);

        return $aActivityListName;
    }

}