<?php

/**
 * 采集任务内容获取规则
 *
 * @author ben
 */
class CmsTaskContentRule extends BaseCmsTask {

    const PICK_TYPE_SUBSTR = 'substr';
    const PICK_TYPE_REGEX  = 'regex';

    protected $table = 'cms_task_content_rules';
    protected static $cacheUseParentClass = false;
    protected static $cacheLevel = self::CACHE_LEVEL_NONE;
    protected static $cacheMinutes = 0;
    protected $fillable = [
        'id',
        'task_id',
        'title',
        'article_column',
        'pick_type',
        'substr_begin',
        'substr_end',
        'regex_val',
        'replace_begin',
        'replace_end',
        'allowed_html_tags',
        'created_at',
        'updated_at',
    ];
    public static $sequencable = false;
    public static $enabledBatchAction = false;
    protected $validatorMessages = [];
    protected $isAdmin = true;
    public static $resourceName = 'CmsTaskContentRule';
    protected $softDelete = false;
    protected $defaultColumns = [ '*'];
    protected $hidden = [];
    protected $visible = [];
    public static $treeable = '';
    public static $foreFatherIDColumn = '';
    public static $foreFatherColumn = '';
    public static $columnForList = [
        'id',
        'task_id',
        'title',
        'article_column',
        'pick_type',
        'regex_val',
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
        'created_at',
        'updated_at',
    ];
    public static $listColumnMaps = [];
    public static $viewColumnMaps = [];
    public static $htmlSelectColumns = [
        'task_id' => 'aCmsTasks'
    ];
    public static $htmlTextAreaColumns = [];
    public static $htmlNumberColumns = [];
    public static $htmlOriginalNumberColumns = [];
    public static $amountAccuracy = 0;
    public static $originalColumns;
    public $orderColumns = [];
    public static $titleColumn = 'id';
    public static $mainParamColumn = '';
    public static $rules = [
        'task_id'           => 'required',
        'title'             => 'required|max:45',
        'article_column'    => 'required|max:45',
        'pick_type'         => 'required|max:45',
        'substr_begin'      => 'required|max:200',
        'substr_end'        => 'required|max:200',
        'replace_begin'     => 'max:200',
        'replace_end'       => 'max:200',
        'allowed_html_tags' => 'max:100',
        'regex_val'         => 'max:150',
    ];

    protected function beforeValidate() {
        return parent::beforeValidate();
    }

    public static function getCmsTaskContentRules($iCmsTaskId) {
        return self::where('task_id', '=', $iCmsTaskId)->get();
    }

    /**
     * 根据url 抓取指定列的内容
     * @param CmsTaskContentRule $oCmsTaskContentRule
     * @param $sUrl
     * @return bool
     */
    public static function getContent(CmsTaskContentRule $oCmsTaskContentRule, $sUrl) {
        $sHtmlSourece = self::curl($sUrl);

        if (!$sHtmlSourece)
            return false;
        if ($oCmsTaskContentRule->pick_type == self::PICK_TYPE_SUBSTR) {
            $sHtmlSourece = self::getStrRegion($sHtmlSourece, $oCmsTaskContentRule->substr_begin, $oCmsTaskContentRule->substr_end);

            if ($oCmsTaskContentRule->replace_begin != '') {
                //replace_begin
                $sHtmlSourece = self::replaceStrRegion($sHtmlSourece, $oCmsTaskContentRule->replace_begin, $oCmsTaskContentRule->replace_end);
            }
            $sHtmlSourece = strip_tags($sHtmlSourece, $oCmsTaskContentRule->allowed_html_tags);
        }
        return $sHtmlSourece;
    }

    public static function getAllContent($aCmsTaskContentRules, $sUrl) {
        $sHtmlSourece = self::curl($sUrl);
        $temp         = $sHtmlSourece;
        if (!$sHtmlSourece)
            return false;

        $aArticle = array();

        foreach ($aCmsTaskContentRules as $oCmsTaskContentRule) {
            if ($oCmsTaskContentRule->pick_type == self::PICK_TYPE_SUBSTR) {
                $sHtmlSourece = self::getStrRegion($sHtmlSourece, $oCmsTaskContentRule->substr_begin, $oCmsTaskContentRule->substr_end);

                if ($oCmsTaskContentRule->replace_begin != '') {
                    //replace_begin
                    $sHtmlSourece = self::replaceStrRegion($sHtmlSourece, $oCmsTaskContentRule->replace_begin, $oCmsTaskContentRule->replace_end);
                }
                $sHtmlSourece = strip_tags($sHtmlSourece, $oCmsTaskContentRule->allowed_html_tags);

                $aArticle[$oCmsTaskContentRule->article_column] = $sHtmlSourece;
                $sHtmlSourece                                   = $temp;
            }
        }

        return $aArticle;
    }

}
