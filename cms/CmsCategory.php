<?php

class CmsCategory extends BaseModel {

    public static $resourceName = 'Category';

      /**
     * title field
     * @var string
     */
    public static $titleColumn = 'name';

    /**
     * the columns for list page
     * @var array
     */
    public static $columnForList = [
        'id',
        'name',
        'parent',
        'template'
    ];
    public static $treeable = true;

    /**
     * the main param for index page
     * @var string
     */
    public static $mainParamColumn = 'parent_id';

    /**
     * The rules to be applied to the data.
     *
     * @var array
     */
    public static $rules = [
        'name' => 'required|max:50',
        'template' => 'max:50'
    ];
    protected $table = 'cms_categories';
    public static $htmlSelectColumns = [
        'parent_id' => 'aCategoriesTree',
        'template'=> 'aTemplates',
    ];
    protected $fillable = [
        'parent_id',
        'parent',
        'name',
        'template',
    ];
    const HELP_CENTER = 'help';
    const HELP_ID  = '3';
    const AWARD_NOTICE_ID = '14';
    const FOOTBALL_NEWS = 12;
    const TYPE_SYSTEM_ANNOUMCEMENT = 15;
    //彩票推荐
    const TYPE_LOTTERY_RECOMMAND = 16;
    //行业咨询
    const TYPE_INFOS = 11;
    //新手指南
    const TYPE_NEWS_HELP = 17;
    
    public static function getHelpCenterCategories()
    {
        return CmsCategory::where('template', '=', static::HELP_CENTER)->get();
    }

    /**
     * 取得活动类别
     *
     * @author　okra
     * @date 2017-5-16
     * @param int $iParentId 父编号
     * @return Array 活动类别 $aActivityListName
     */
    protected function getByParentId($iParentId) {
        $oCmsCategoryList = CmsCategory::where('parent_id', $iParentId)->get();
        foreach ($oCmsCategoryList as $oActivityName) {
            $aTmp[] = $oActivityName->name;
        }
        $aActivityListName = array_unique($aTmp);

        return $aActivityListName;
    }

}
