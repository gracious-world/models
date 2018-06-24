<?php

/*
 * 词汇模型类
 * 作用：生成语言包词汇以及导出语言包文件
 */

use Illuminate\Support\Facades\Redis;

class CmsUserReaded extends BaseModel {

    protected static $cacheLevel = self::CACHE_LEVEL_FIRST;
    protected static $cacheMinutes = 60;
    public static $resourceName = 'CmsUserReaded';
    private static $sSystemNoticeCacheKey = 'system_notice';

    /**
     * title field
     * @var string
     */
    public static $titleColumn = 'article_title';
    public $orderColumns = [
        'article_id' => 'desc',
        'created_at' => 'desc'
    ];

    /**
     * the columns for list page
     * @var array
     */
    public static $columnForList = [
        'category_id',
        'article_title',
        'username',
        'created_at',
        'updated_at'
    ];

    /**
     * The rules to be applied to the data.
     *
     * @var array
     */
    public static $rules = [
        'category_id' => 'required|integer',
        'article_id' => 'required|integer',
        'article_title' => 'required|max:50',
        'user_id' => 'required|integer',
        'username' => 'required|max:50'
    ];
    protected $table = 'cms_user_readed';
    public static $htmlSelectColumns = [
        'category_id' => 'aCategories'
    ];
    protected $fillable = [
        'category_id',
        'article_id',
        'article_title',
        'user_id',
        'username',
        'created_at',
        'updated_at',
    ];


    /**
     * ignore columns for edit
     * @var array
     */
    public static $ignoreColumnsInEdit = [
        'article_title'
    ];

    protected function afterSave($oSavedModel) {
        $this->deleteSystemNoticeRedisCache();
        parent::afterSave($oSavedModel);
    }

    protected function beforeValidate() {
        if(empty($this->article_title) || empty($this->category_id)){
            $oArticle = CmsArticle::find($this->article_id);
            $this->article_title = $oArticle->title;
            $this->category_id = $oArticle->category_id;
        }
        if(empty($this->username)){
            $oUser = User::find($this->user_id);
            $this->username = $oUser->username;
        }
        return parent::beforeValidate();
    }

    //是否已读
    public static function getReadedByUserId($iArticleId,$iUserId){
        $obj = self::where('article_id','=',$iArticleId)->where('user_id','=',$iUserId)->first();
        return $obj;
    }

    public static function setReaded($iArticleId,$iUserId){
        $data = [
            'article_id'  =>  $iArticleId,
            'user_id' =>  $iUserId
        ];
        $obj = new Self($data);
        $obj->save();
        CmsArticle::updateReadCount($iArticleId);
    }
    // protected function afterSave($bSucc) {
    //     // pr($this->aFiles);
    //     // exit;
    //     return parent::afterSave($bSucc);
    // }

   
}
