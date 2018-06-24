<?php

/**
 * 此表 因为涉及到 竟彩多玩法..未加竟彩时,暂时约定, 此表中的 way_id=game_type,表示 这个 游戏种类  是 无细分玩法 返点的.
 * 等级 user_grade_sets 与游戏类型 game_types 的 关联 , 返点设置表. 队员和队长 投注 时 的返点值是从此表读取的
 * 会员投注 返点比例 设置表  不同等级,不同游戏类型(及 下单情况,比如 竟彩的单关,串关) , 返点比例不同
 * game_type 就是 game_types 表里面的 id号   1数字彩,2竟彩,3PT老虎机
 * way_id 就是 game_types 可能细分的.比如 竟彩 可能有 竟彩单关,竟彩串关 .如果 某个 游戏 没细分.那 way_id=game_type.   1数字彩,2001竟彩单关,2002竟彩串关,3PT老虎机
 * @author lucda
 */

class RebateSetting extends BaseModel {

    protected $table = 'rebate_settings';

    protected static $cacheUseParentClass = false;

    protected static $cacheLevel = self::CACHE_LEVEL_NONE;

    protected static $cacheMinutes = 0;

    public static $validStatuses     = [];

    protected $fillable = [
        'id',
        'grade_id',
        'grade',
        'game_type',
        'game_type_name',
        'way_id',
        'user_rebate',
        'parent_rebate',
        'created_at',
        'updated_at',
    ];

    public static $sequencable = false;

    public static $enabledBatchAction = false;

    protected $validatorMessages = [];

    protected $isAdmin = true;

    public static $resourceName = 'RebateSettings';

    protected $softDelete = false;

    protected $defaultColumns = [ '*' ];

    protected $hidden = [];

    protected $visible = [];

    public static $treeable = '';

    public static $foreFatherIDColumn = '';

    public static $foreFatherColumn = '';

    public static $columnForList = [
        'id',
        'grade_id',
        'grade',
        'game_type',
        'game_type_name',
        'way_id',
        'user_rebate',
        'parent_rebate',
        'created_at',
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

    public static $listColumnMaps = [
        'game_type'=>'game_type_formatted',
        'grade_id'=>'grade_id_formatted',
        'grade' => 'grade_formatted'
    ];

    public static $viewColumnMaps = [];


    /**
     * 下拉列表框字段配置
     * @var array
     */
    public static $htmlSelectColumns = [
        'game_type' => 'aGameTypes',
        'grade_id'    => 'grade_id_selection',
        'grade'    => 'grade_id_selection',
    ];


    public static $htmlTextAreaColumns = [];

    public static $htmlNumberColumns = [];

    public static $htmlOriginalNumberColumns = [];

    public static $amountAccuracy = 0;

    public static $originalColumns;

    public $orderColumns = [
        'grade'     => 'desc',
        'way_id'    =>'desc',
    ];

    public static $titleColumn = 'grade';

    public static $mainParamColumn = '';

    public static $rules = [
        'grade_id' => 'required|integer',
        'grade' => 'required',
        'game_type' => 'required|integer',
        'game_type_name' => 'required',

        'way_id' => 'required|integer',
        'user_rebate' => 'regex:/^\d*(\.\d{3})?$/',
        'parent_rebate' => 'regex:/^\d*(\.\d{3})?$/',
    ];

    protected function beforeValidate() {
        return parent::beforeValidate();
    }

    /**
     * 获取 等级 游戏类型 返点 设置
     * @return object
     */
    static function getRebateSettings(){
        $cachePrefix=static::getCachePrefix(true);
        $cacheKey=$cachePrefix."rebate-settings";
        $expire_at=Carbon::now()->addYears(3);
        if(!Cache::get($cacheKey)) Cache::put($cacheKey,RebateSetting::all(),$expire_at);
        return Cache::get($cacheKey)?Cache::get($cacheKey):RebateSetting::all();
    }


    /**
     * @param $iUserId
     * @param $iGameTypeId
     * @return bool
     * 主要就是 用于 /models/userClass/UserProject.php 里面 会员投注后,根据 这个会员的当前等级,投注的这个彩种  来 获取 grade_game_type_sets 表里面的 一行 对象
     */
    static function getRebateSettingByUserIdGameTypeId($iUserId, $iGameTypeId, $iWayId = NULL ){
        $oUserGrade = UserGrade::getUserGradeByUserId($iUserId);
        if(!$oUserGrade){
            return false;//说明 这个用户 没有在 等级表里面
        }
        $iGrade = $oUserGrade->grade;//用户的当前等级. 是直接从 user_grade 表里面 获取的
        $oGameType = GameType::find($iGameTypeId);
        if(!$oGameType){
            return false;//说明 彩种表里面有此 游戏类型..但是 游戏表里面没有
        }

        $aConditions['grade'] = ['=', $iGrade];
        $aConditions['game_type'] = ['=', $iGameTypeId];
        if ($iWayId) {
            $aConditions['way_id'] = ['=', $iWayId];
        }
        return RebateSetting::doWhere($aConditions)->first();
    }

    /**
     * 游戏类型选择
     * @author lucky
     * @created_at 2016-10-19
     * @return mixed
     *
     */
    protected function getGameTypeFormattedAttribute() {
        return $this->game_type;
    }

    /**
     * 等级选择
     * @author lucky
     * @created_at 2016-10-19
     * @return mixed
     *
     */
    protected function getGradeIdFormattedAttribute() {
        return $this->grade_id;
    }

    /**
     * 等级格式
     * @author lucky
     * @created_at 2016-10-19
     * @return mixed
     *
     */
    protected function getGradeFormattedAttribute() {
        return UserGradeSet::getGradeSets()[$this->grade_id]->name;
    }
}