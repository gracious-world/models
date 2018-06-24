<?php

/*
 * 词汇模型类
 * 作用：生成语言包词汇以及导出语言包文件
 */

use Illuminate\Support\Facades\Redis;

class CmsArticle extends BaseModel {

    protected static $cacheLevel = self::CACHE_LEVEL_FIRST;
    protected static $cacheMinutes = 60;
    public static $resourceName = 'CmsArticle';
    protected static $maxCacheLength = 20;

    /**
     * title field
     *
     * @var string
     */
    public static $titleColumn = 'title';
    public static $sequencable = true;

    public $orderColumns = [
        'sequence' => 'asc',
        'is_top' => 'desc',
        'updated_at' => 'desc'
    ];
    public static $mobileColumns = [
        'id',
        'title',
        'created_at'
    ];

    /**
     * the columns for list page
     *
     * @var array
     */
    public static $columnForList = [
        'category_id',
        'title',
        'summary',
        'search_text',
        'author',
        'need_read_log',
        'read_count',
        'is_top',
        'status',
        'auditor',
        'sequence',
        'created_at'
    ];

    /**
     * The rules to be applied to the data.
     *
     * @var array
     */
    public static $rules = [
        'category_id' => 'required|integer',
        'title' => 'required|max:50',
        'summary' => 'max:50',
        'content' => 'required',
        'search_text' => 'max:50000',
        'is_top' => 'required|in:0,1',
        'status' => 'required|in:0,1,2,3',
        'need_read_log' => 'required|in:0,1',
        'read_count' => 'required|integer',
    ];
    protected $table = 'cms_articles';
    public static $htmlSelectColumns = [
        'category_id' => 'aCategories',
        'author_id' => 'aAdmins',
        'auditor_id' => 'aAdmins',
        'status' => 'aStatus',
    ];
    protected $fillable = [
        'category_id',
        'title',
        'summary',
        'content',
        'search_text',
        'status',
        'author_id',
        'author',
        'auditor_id',
        'auditor',
        'is_top',
        'need_read_log',
        'read_count',
        'sequence',
        'created_at',
        'updated_at',
    ];

    const TYPE_HELP = 1;
    const TYPE_ANNOUMCEMENT = 2; //公告
    const TYPE_HELPCENTER = 3; //帮助中心
    const STATUS_NEW = 0; // 待审核
    const STATUS_AUDITED = 1; // 审核通过
    const STATUS_REJECTED = 2; // 审核未通过
    const STATUS_RETRACT = 3; // 公告下架
    const STATUS_TOP_ON = 1;
    const STATUS_TOP_OFF = 0;

    public static $aStatusDesc = [
        self::STATUS_NEW => 'new',
        self::STATUS_AUDITED => 'audited',
        self::STATUS_REJECTED => 'rejected',
        self::STATUS_RETRACT => 'retract',
    ];

    /**
     * ignore columns for edit
     *
     * @var array
     */
    public static $ignoreColumnsInEdit = [
//        'status',
        'is_top',
        'author_id',
        'author',
        'auditor_id',
        'auditor',
    ];

    protected function afterSave($oSavedModel) {
        parent::afterSave($oSavedModel);
        $oSavedModel->deleteListCache($oSavedModel->category_id);
    }

    protected function beforeValidate() {
        if (!Session::get('admin_user_id')) {
            return false;
        }
        if ($this->id) {
            $this->auditor_id = Session::get('admin_user_id');
            $this->auditor = Session::get('admin_username');
        } else {
            $this->author_id = Session::get('admin_user_id');
            $this->author = Session::get('admin_username');
        }
        if ($this->status == self::STATUS_AUDITED) {
            $this->auditor_id = Session::get('admin_user_id');
            $this->auditor = Session::get('admin_username');
        }
        isset($this->is_top) or $this->is_top = 0;
        isset($this->status) or $this->status = self::STATUS_NEW;
        return parent::beforeValidate();
    }

    // protected function afterSave($bSucc) {
    //     // pr($this->aFiles);
    //     // exit;
    //     return parent::afterSave($bSucc);
    // }

    protected static function createAnnouncementCache() {
        $redis = Redis::connection();
        $sKey = static::compileListCacheKey(CmsArticle::TYPE_ANNOUMCEMENT);
        $aColumns = ['id', 'title', 'created_at'];
        $aMoreArticles = static::where('category_id', CmsArticle::TYPE_ANNOUMCEMENT)->where('status', self::STATUS_AUDITED)->orderBy('is_top', 'desc')->orderBy('created_at', 'desc')->skip($iExistsCount)->take($iCount)->get($aColumns);
        foreach ($aMoreArticles as $oMoreArticle) {
            $aArticles[] = $oMoreArticle;
            $redis->rpush($sKey, json_encode($oMoreArticle->toArray()));
        }
    }

    public static function createListCache($iTypeId, $iPage = 1, $iPageSize = 20) {
        $redis = Redis::connection();
        $sKey = static::compileListCacheKey($iTypeId, $iPage);
        $aColumns = ['id', 'title', 'summary', 'created_at', 'content'];
        $iStart = ($iPage - 1) * $iPageSize;
        $oArticles = static::where('category_id', $iTypeId)->where('status', self::STATUS_AUDITED)->orderBy('sequence', 'asc')->orderBy('is_top', 'desc')->orderBy('created_at', 'desc')->skip($iStart)->limit($iPageSize)->get($aColumns);
        $redis->multi();
        $redis->del($sKey);
        foreach ($oArticles as $oArticle) {
            $redis->rpush($sKey, json_encode($oArticle->toArray()));
        }
        $redis->exec();
    }

    public static function & getListOfPage($iTypeId, $iPage = 1, $iPageSize = 20) {
        $redis = Redis::connection();
        $sKey = static::compileListCacheKey($iTypeId, $iPage);
        if (!$bHasInRedis = $redis->exists($sKey)) {
            static::createListCache($iTypeId, $iPage);
        }
        $aArticlesFromRedis = $redis->lrange($sKey, 0, $redis->llen($sKey) - 1);
        $aArticles = [];
        foreach ($aArticlesFromRedis as $sArticle) {
            $obj = new static;
            $obj = $obj->newFromBuilder(json_decode($sArticle, true));
            $aArticles[] = $obj;
        }
        unset($aArticlesFromRedis, $obj, $sKey, $redis);
        return $aArticles;
    }

    public static function & getLatestRecords($iTypeId = null, $iCount = 5) {
        $aArticles = $aFirstArticles = &static::getListOfPage($iTypeId, 1) ? array_slice($aFirstArticles, 0, $iCount) : [];
        return $aArticles;
    }

//    public static function getLatestRecords($iCount = 4) {
//        $redis = Redis::connection();
//        $sKey = static::compileListCacheKey();
//        if ($bHasInRedis = $redis->exists($sKey)){
//            $aArticlesFromRedis = $redis->lrange($sKey,0,$iCount - 1);
////            pr($aArticlesFromRedis);
//            $iExistsCount = count($aArticlesFromRedis);
//            $iNeedCount = $iCount - $iExistsCount;
//            foreach($aArticlesFromRedis as $sInfo){
//                $obj = new static;
//                $obj = $obj->newFromBuilder(json_decode($sInfo, true));
//                $aArticles[] = $obj;
//            }
//            unset($obj);
//        }
//        else{
//            $iNeedCount = $iCount;
//            $aArticles = [];
//            $iExistsCount = 0;
//        }
//        if (!$bHasInRedis || $iNeedCount > 0){
//            $aColumns = ['id', 'title', 'created_at'];
//            $aMoreArticles = static::where('category_id', CmsArticle::TYPE_ANNOUMCEMENT)->where('status', self::STATUS_AUDITED)->orderBy('is_top', 'desc')->orderBy('created_at', 'desc')->skip($iExistsCount)->take($iCount)->get($aColumns);
//            foreach($aMoreArticles as $oMoreArticle){
//                $aArticles[] = $oMoreArticle;
//                $redis->rpush($sKey, json_encode($oMoreArticle->toArray()));
//            }
//        }
////        pr($aArticles);
////        exit;
//        return $aArticles;
//    }

    private static function compileListCacheKey($iType = null, $iPage = 1) {
        $sKey = static::getCachePrefix(TRUE);
        is_null($iType) or $sKey .= $iType;
        is_null($iPage) or $sKey .= '-' . $iPage;
        return $sKey;
    }

    public static function deleteListCache($iTypeId) {
        $sKey = static::compileListCacheKey($iTypeId);
        $redis = Redis::connection();
        $redis->del($sKey);
    }

//    public static function getLatestRecords($iCount = 5) {
//        $aColumns = ['id', 'title', 'updated_at'];
//        $aArticles = static::where('category_id', CmsArticle::TYPE_ANNOUMCEMENT)->where('status', 1)->orderBy('is_top', 'desc')->orderBy('updated_at', 'desc')->limit($iCount)->get($aColumns);
////        pr($aArticles->toArray());
////        exit;
//        return $aArticles;
//    }

    public static function getHelpCenterArticles() {
        $aCategories = CmsCategory::getHelpCenterCategories();
        $aCategoryIds = [];
        foreach ($aCategories as $key => $value) {
            $aCategoryIds[] = $value->id;
        }

        return CmsArticle::whereIn('category_id', $aCategoryIds)
            ->where('status', '=', CmsArticle::STATUS_AUDITED)
            ->get();
    }

    public static function getArticlesByCaregoryId($iCategoryId) {
        return CmsArticle::where('category_id', '=', $iCategoryId)->orderBy("sequence")->get();
    }

    public function getUpdatedAtDayAttribute() {
        return date('m/d', strtotime($this->updated_at));
    }

    public function getCreatedAtDayAttribute() {
        return date('m/d', strtotime($this->created_at));
    }

    /**
     * 更新文章已读数量
     *
     * @param $iCmsArticleId
     * @return bool
     */
    public static function updateReadCount($iCmsArticleId) {
        $sql = "update cms_articles set read_count = read_count + 1 where id = $iCmsArticleId";
        return DB::update($sql);
    }

    /**
     * 更新文章点击率
     *
     * @param $iCmsArticleId
     * @return bool
     */
    public function updateClickCount() {
        $iCmsArticleId = $this->id;
        $sql = "update cms_articles set click_count = click_count + 1 where id = $iCmsArticleId";
        return DB::update($sql);
    }

    /**
     * 获取系统广播
     *
     * @author lucky
     * @date   2017-05-12
     *
*@param       $iUserId         int
     * @param $iCount         int
     * @param $needReadStatus int
     *
*@return array
     */
    public static function getSystemNotice($iUserId, $needReadStatus = 1) {
        $aConditions = [
                'category_id' => ['=', CmsCategory::TYPE_SYSTEM_ANNOUMCEMENT],
                'status' => ['=', static::STATUS_AUDITED]
        ];
        $aNotices = static::getUserPanelData($iUserId,$aConditions);
        $aNewNotices = [];
        $aArticleIds = [];
        foreach ($aNotices as $v) {
            $aArticleIds[] = $v['id'];
        }
        //获取已读
        $aUserReadedIds = [];
        if ($needReadStatus) {
            if (!empty($aArticleIds)) {
//                $oUserReadeds = CmsUserReaded::where('user_id', '=', $userId)->whereIn('article_id', $aArticleIds)->get();
                $aConditions = [
                  'user_id' => ['=', $iUserId],
                  'article_id' => ['in', $aArticleIds]
                ];
                $aUserReadeds = CmsUserReaded::getUserPanelData($iUserId, $aConditions);
//                $oUserReadeds=[];
                foreach ($aUserReadeds as $aUserReaded) {
                    $aUserReadedIds[] = $aUserReaded['article_id'];
                }
            }
            //is_readed
            if(!empty($aUserReadedIds)){
                foreach ($aNotices as $aNotice) {
                    if(in_array($aNotice['id'], $aUserReadedIds)){
                        continue;
                    }
                    $aNewNotices[] = $aNotice;
                }
            } else {
                foreach ($aNotices as $aNotice) {
                    $aNewNotices[] = $aNotice;
                }
            }
        } else {
            foreach ($aNotices as $aNotice) {
                $aNewNotices[] = $aNotice;
            }
        }
        return $aNewNotices;
    }

    /**
     * 获取最新奖励消息
     *
     * @author lucky
     * @date   2017-05-12
     * @param int $cms_article_type
     * @param int $iCount
     * @return array
     * @see    CmsArticle::getLatestRecords
     */
    public static function & getLatestBonusMsgRecords($cms_article_type = null, $iCount = 4) {
        $aArticles = $aFirstArticles = &static::getBonusMsgListOfPage($cms_article_type, 1) ? array_slice($aFirstArticles, 0, $iCount) : [];
        return $aArticles;
    }

    /**
     * 获取最新中奖消息
     *
     * @aurhor lucky
     * @date   2017-05-12
     * @param int $cms_article_type
     * @param int $iPage
     * @param int $iPageSize
     * @return array
     * @see    CmsArticle::getListOfPaGE
     */
    public static function & getBonusMsgListOfPage($cms_article_type, $iPage = 1, $iPageSize = 20) {
        $redis = Redis::connection();
        $sKey = static::compileListCacheKey($cms_article_type, $iPage);
        if (!$bHasInRedis = $redis->exists($sKey)) {
            static::createBonusMsgListCache($cms_article_type, $sKey, $iPage);
        }

        $aArticlesFromRedis = $redis->lrange($sKey, 0, $redis->llen($sKey) - 1);
        $aArticles = [];
        foreach ($aArticlesFromRedis as $sArticle) {
            $obj = new static;
            $obj = $obj->newFromBuilder(json_decode($sArticle, true));
            $aArticles[] = $obj;
        }
        unset($aArticlesFromRedis, $obj, $sKey, $redis);
        return $aArticles;
    }


    /**
     * @param $cms_article_type
     * @param int $iPage
     * @param int $iPageSize
     * @see CmsArticle::createListCache
     */
    static function createBonusMsgListCache($cms_article_type, $sKey, $iPage = 1, $iPageSize = 20) {
        $redis = Redis::connection();
        $aColumns = ['id', 'title', 'summary', 'content', 'created_at'];
        $iStart = ($iPage - 1) * $iPageSize;
        $oArticles = static::where('category_id', $cms_article_type)->where('status', self::STATUS_AUDITED)->orderBy('is_top', 'desc')->orderBy('created_at', 'desc')->skip($iStart)->limit($iPageSize)->get($aColumns);
        $redis->multi();
        foreach ($oArticles as $oArticle) {
            $redis->rpush($sKey, json_encode($oArticle->toArray()));
        }
        $redis->exec();
    }
}
