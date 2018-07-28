<?php

class AdInfo extends BaseModel {
    protected $table = 'ad_infos';
    public static $resourceName = 'AdInfo';
    public static $titleColumn = 'content';
    protected $hidden = [];
    protected $guarded = [];
    protected $softDelete = false;
    public static $mainParamColumn = 'ad_location_id';

    /**
     *  启用状态
     *  @var int
     */
    const CLOSE_FLAG = '0';

    /**
     *  广告图片
     *  @var int
     */
    const ID_AD = 3;

    /**
     *  彩种相关图片
     *  @var int
     */
    const ID_LOTTERY = 7;

    /**
     *  活动相关图片
     *  @var int
     */
    const ID_ACTIVITY = 8;

    /**
     *  活动类别
     *  @var int
     */
    const ID_ACTIVITY_TYPE = 18;

    protected $fillable = [
        'name',
        'ad_location_id',
        'pic_url',
        'pic_mini',
        'content',
        'redirect_url',
        'notice_id',
        'is_closed',
        'creator_id',
        'creator',
        'type',
    ];
    public static $columnForList = [
        'ad_location_id',
        'name',
//        'content',
        'pic_url',
        'pic_mini',
        'is_closed',
        'redirect_url',
        'notice_id',
        'creator',
        'created_at',
        'sequence',
        'type',
    ];
    public static $listColumnMaps = [
        'pic_url' => 'preview_picture',
        'pic_mini' => 'preview_picture_mini'
    ];
    public static $viewColumnMaps = [
        'pic_url' => 'preview_picture',
        'pic_mini' => 'preview_picture_mini'
    ];
    public static $htmlSelectColumns = [
        'ad_location_id' => 'aLocations',
    ];
    public $orderColumns = [
        'sequence' => 'asc',
        'id' => 'desc'
    ];
    public static $rules = [
//        'name'           => 'required|max:50',
        'ad_location_id' => 'required|integer',
        'pic_url' => '',
        'content' => 'required',
        'redirect_url' => 'required|max:255',
        'notice_id' => 'integer',
        'is_closed' => 'required|in:0,1',

    ];

    const NUM_AD_TYPE = 3;
    public static $sequencable = true;

    protected function beforeValidate() {
        if (!$this->id) {
            $this->creator_id = Session::get('admin_user_id');
            $this->creator = Session::get('admin_username');
        }
        $this->notice_id or $this->notice_id = null;
        return parent::beforeValidate();
    }

    public static function getLatestRecords() {
        $aColumns = ['id', 'pic_url', 'updated_at', 'redirect_url', 'ad_location_id', 'content'];
        // TODO 公告是否需要绑定用户待定
        // $iUserId = Session::get('user_id');
        // $oUser = User::find($iUserId);
        // if (Session::get('is_agent')) {
        //     $aUserIds = [];
        //     $aUsers = $oUser->getUsersBelongsToAgent();
        //     foreach ($aUsers as $oUser) {
        //         $aUserIds[] = $oUser->id;
        //     }
        //     $oQuery = static::whereIn('user_id', $aUserIds);
        // } else {
        //     $oQuery = static::where('user_id', '=', $iUserId);
        // }
        $aArticles = static::where('ad_location_id', '=', self::NUM_AD_TYPE)->orderBy('updated_at', 'desc')->get($aColumns);
        return $aArticles;
    }

    public static function getAdInfosByLocationId($iLocationId = null, $iCount = null) {
        $aColumns = ['id', 'content', 'pic_url', 'redirect_url', 'ad_location_id', 'notice_id'];
        $oQuery = static::where('ad_location_id', '=', $iLocationId)
            ->where('is_closed', '=', 0)
            ->orderBy('sequence', 'asc')
            ->orderBy('id', 'desc');
        empty($iCount) or $oQuery = $oQuery->take($iCount);
        return $oQuery->get($aColumns);
    }

    protected function getPreviewPictureAttribute() {
        return $this->pic_url ? '<img src="' . $this->pic_url . '" width="200px">' : '(无)';
    }

    /**
     * 图片展示用
     *
     * @author　okra
     * @date 2017-5-10
     * @param null
     * @return object <img src=.../>
     */
    protected function getPreviewPictureMiniAttribute() {
        return $this->pic_mini ? '<img src="' . $this->pic_mini . '" width="200px">' : '(无)';
    }

    /**
     * 取得汇众国际首页相关的的信息图片
     *
     * @author　okra
     * @date 2017-5-10
     * @param int $id 位置
     * @param string $sType 活动类别
     * @param $iIsClosed 是否启用 0:启用
     * @return object 活动的图片相关列表 $oAdInfoList
     */
    protected function getAdInfoList($id, $sType, $iIsClosed) {
            $oAdInfoList = AdInfo::Where('ad_location_id', $id)->Where('is_closed', $iIsClosed)->orderBy('sequence', 'desc')->get();

        return $oAdInfoList;
    }

    /**
     * 取得活动相关列表
     *
     * @author　okra
     * @date 2017-5-16
     * @param int $iAdLocationId 位置
     * @return object 活动相关列表 $oAdInfoList
     */
    protected function getByLocationId($iAdLocationId) {
        $oAdInfoList = AdInfo::where('ad_location_id', $iAdLocationId)->get();
        return $oAdInfoList;
    }

}