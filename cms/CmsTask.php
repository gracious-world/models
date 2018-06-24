<?php

/**
 * 咨询抓取任务
 *
 * @author ben
 */
class CmsTask extends BaseCmsTask {

    protected $table                         = 'cms_tasks';
    protected static $cacheUseParentClass    = false;
    protected static $cacheLevel             = self::CACHE_LEVEL_NONE;
    protected static $cacheMinutes           = 0;
    protected $fillable                      = [
        'id',
        'name',
        'category_id',
        'cycle',
        'last_executed_at',
        'base_url',
        'get_url_type',
        'auto_url_rule',
        'manu_url_rule',
        'begin_region',
        'end_region',
        'filter_rule',
        'filter_include',
        'filter_not_include',
        'is_content_page',
        'default_article_status',
        'status',
        'created_at',
        'updated_at',
    ];
    public static $sequencable               = false;
    public static $enabledBatchAction        = false;
    protected $validatorMessages             = [];
    protected $isAdmin                       = true;
    public static $resourceName              = 'CmsTask';
    protected $softDelete                    = false;
    protected $defaultColumns                = [ '*'];
    protected $hidden                        = [];
    protected $visible                       = [];
    public static $treeable                  = '';
    public static $foreFatherIDColumn        = '';
    public static $foreFatherColumn          = '';
    public static $columnForList             = [
        'id',
        'name',
        'status',
        'cycle',
        'last_executed_at',
        'created_at',
        'updated_at',
    ];
    public static $totalColumns              = [];
    public static $totalRateColumns          = [];
    public static $weightFields              = [];
    public static $classGradeFields          = [];
    public static $floatDisplayFields        = [];
    public static $noOrderByColumns          = [];
    public static $ignoreColumnsInView       = [
    ];
    public static $ignoreColumnsInEdit       = [
        'created_at',
        'updated_at',
    ];
    public static $listColumnMaps            = [
        'last_executed_at' => 'formatted_last_exe',
    ];
    public static $viewColumnMaps            = [];
    public static $htmlSelectColumns         = [
        'category_id'            => 'aCategories',
        'default_article_status' => 'aArticleStatus',
    ];
    public static $htmlTextAreaColumns       = [];
    public static $htmlNumberColumns         = [];
    public static $htmlOriginalNumberColumns = [];
    public static $amountAccuracy            = 0;
    public static $originalColumns;
    public $orderColumns                     = [];
    public static $titleColumn               = 'name';
    public static $mainParamColumn           = '';
    public static $rules                     = [
        'name'                   => 'required|max:45',
        'category_id'            => 'required',
        'status'                 => 'required|in:0,1',
        'default_article_status' => 'required|in:0,1,2,3',
        'cycle'                  => 'integer',
        'base_url'               => 'required|max:100',
        'get_url_type'           => 'required|max:45',
        'auto_url_rule'          => 'max:100',
        'manu_url_rule'          => 'max:45',
        'begin_region'           => 'max:200',
        'end_region'             => 'max:200',
        'filter_rule'            => 'max:45',
        'filter_include'         => 'max:100',
        'filter_not_include'     => 'max:100',
        'is_content_page'        => 'required|integer|in:0,1',
    ];

    /*
      public function cmsTaskurlRule() {
      return $this->hasOne('CmsTaskurlRule', 'task_id', 'id');
      }
     */

    protected function getFormattedLastExeAttribute() {
        return date('Y-m-d H:i:s', $this->last_executed_at);
    }

    public static function getCmsTaskurlRule($iTaskId) {
        return CmsTaskurlRule::where('task_id', '=', $iTaskId)->first();
    }

    protected function beforeValidate() {
        return parent::beforeValidate();
    }

    /**
     * 根据规则获取链接列表
     * @param CmsTask $oCmsTask
     * @return array|bool
     */
    public static function getLinks(CmsTask $oCmsTask) {
        $sHtmlSource  = self::curl($oCmsTask->base_url);
        if (!$sHtmlSource)
            return false;
        $sHtmlContent = substr($sHtmlSource, strpos($sHtmlSource, $oCmsTask->begin_region), strpos($sHtmlSource, $oCmsTask->end_region) - strpos($sHtmlSource, $oCmsTask->begin_region));

        $regexp = $oCmsTask->auto_url_rule;

        $aLinks = array();
        if (preg_match_all("/$regexp/siU", $sHtmlContent, $matches)) {
            // $matches[2] = array of link addresses
            // $matches[3] = array of link text - including HTML code
            $matches = $matches[2];
        }

        if (count($matches) > 0) {
            foreach ($matches as $aLink) {
                if ($oCmsTask->filter_include != '') {
                    $aFiterStr = explode(';', $oCmsTask->filter_include);
                    $bSuccess  = true;
                    foreach ($aFiterStr as $sFiter) {
                        if (!strstr($aLink, trim($sFiter))) {
                            $bSuccess = false;
                            break;
                        }
                    }
                    if (!$bSuccess)
                        continue;
                }
                $aLinks [] = str_replace("'", "", $aLink);
            }
        }
        return $aLinks;
    }

    /*
     * 获取当前时间可执行的任务
     */

    public static function getCmsTasksPedding() {
        $now       = time();
        $aCmsTasks = CmsTask::where('status', '=', '1')
            ->whereRaw("(last_executed_at + cycle < $now)")
            ->get();
        return $aCmsTasks;
    }

}
