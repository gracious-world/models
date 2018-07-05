<?php

/**
 * 彩票模型
 */
class Lottery extends BaseModel
{

    static $cacheLevel = self::CACHE_LEVEL_FIRST;

    /**
     * 数字排列类型
     */
    const LOTTERY_TYPE_DIGITAL = 1;

    /**
     * 乐透类型
     */
    const LOTTERY_TYPE_LOTTO = 2;

    /**
     * 体育类型
     */
    const LOTTERY_TYPE_SPORT = 3;

    /**
     * 竞彩足球
     * football lottery id
     */
    const LOTTERY_JCZQ_ID = 31;

    /**
     * 汇众11选5
     * 11-5 lottery id
     */
    const LOTTERY_11_5 = 14;

    /**
     * 汇众秒秒彩 id
     * mango ssc id
     */
    const LOTTERY_SSC_ID = 26;

    /**
     * 汇众11选5秒秒彩
     * 11-5 ssc id
     */
    const LOTTERY_11_5_SSC_ID = 43;
    /**
     * 真人娱乐类型
     */
    const LOTTERY_TYPE_LIVE_CASINO = 4;

    /**
     * 单区乐透类型
     */
    const LOTTERY_TYPE_LOTTO_SINGLE = 1;

    /**
     * 双区乐透类型
     */
    const LOTTERY_TYPE_LOTTO_DOUBLE = 2;
    const WINNING_SPLIT_FOR_DOUBLE_LOTTO = '+';

    /**
     * 针对正式用户可用
     */
    const STATUS_AVAILABLE_FOR_NORMAL_USER = 2;

    /**
     * 针对测试用户可用
     */
    const STATUS_AVAILABLE_FOR_TESTER = 1;

    /**
     * 不可用
     */
    const STATUS_NOT_AVAILABLE = 0;

    /**
     * 测试状态（此状态下系统不接受自动录号）
     */
    const STATUS_TESTING = 4;

    /**
     * 永久关闭
     */
    const STATUS_CLOSED_FOREVER = 8;

    /**
     * 所有用户可用
     */
    const STATUS_AVAILABLE = 3;
    const ERRNO_LOTTERY_MISSING = -900;
    const ERRNO_LOTTERY_CLOSED  = -901;

    /**
     * @var string
     * 数字彩cache键值
     */
    static $lottery_list_digital_json_key = "lottery-list-digital-json-format";

    /**
     * @var string
     * 竞彩cache键值
     */
    static $lottery_list_sport_json_key = "lottery-list-sport-json-format";

    /**
     * @var string
     * 投注方式cache
     */
    static $lottery_ways_json_key = "lottery-ways-json-format";

    /**
     * @var int
     * 竞彩系列
     */
    static $sport_series_key = 6;

    /**
     * all types
     * @var array
     */
    public static $validTypes = [
        self::LOTTERY_TYPE_DIGITAL => 'Digital',
        self::LOTTERY_TYPE_LOTTO => 'Lotto',
        self::LOTTERY_TYPE_SPORT => 'Sport',
        self::LOTTERY_TYPE_LIVE_CASINO => 'Live-Casino',
    ];

    /**
     * all lotto types
     * @var array
     */
    public static $validLottoTypes = [
        self::LOTTERY_TYPE_LOTTO_SINGLE => 'Single',
        self::LOTTERY_TYPE_LOTTO_DOUBLE => 'Double',
    ];
    public static $validStatuses   = [
        self::STATUS_NOT_AVAILABLE        => 'Closed',
        self::STATUS_AVAILABLE_FOR_TESTER => 'For Tester',
//        self::STATUS_AVAILABLE_FOR_NORMAL_USER => 'Available',
        self::STATUS_AVAILABLE            => 'Available',
        self::STATUS_TESTING              => 'Testing',
        self::STATUS_CLOSED_FOREVER       => 'Closed Forever'
    ];
    public static $resourceName    = 'Lottery';
    protected $table               = 'lotteries';

    /**
     * 下拉列表框字段配置
     * @var array
     */
    public static $htmlSelectColumns = [
        'series_id'  => 'aSeries',
        'type'       => 'aValidTypes',
        'lotto_type' => 'aValidLottoTypes',
        'status'     => 'aValidStatus',
        'game_type'  => 'aGameTypes'
    ];
    public static $sequencable       = true;
    public static $listColumnMaps    = [
        'name' => 'friendly_name'
    ];

    /**
     * order by config
     * @var array
     */
    public $orderColumns = [
        'sequence' => 'asc'
    ];

    /**
     * the main param for index page
     * @var string
     */
    public static $mainParamColumn = 'type';
    public static $customMessages  = [];
    public static $titleColumn     = 'name';

    public function series() {
        return $this->belongsTo('Series');
    }

    protected function beforeValidate() {
        $this->lotto_type or $this->lotto_type = null;
        $this->begin_time or $this->begin_time = null;
        $this->end_time or $this->end_time   = null;
        return parent::beforeValidate();
    }

//    public static function getAllLotteryNameArray($aColumns = null)
//    {
//        $aColumns or $aColumns = ['id', 'name'];
//        $aLotteries = Lottery::all($aColumns);
//        $data = [];
//        foreach ($aLotteries as $key => $value) {
//            $data[$value->id] = $value->name;
//        }
//        return $data;
//    }
    protected static function compileLotteryListCacheKey($bOpen = null) {
        $sKey = static::getCachePrefix(true) . 'list';
        if (!is_null($bOpen)) {
            $sKey .= $bOpen ? '-open' : '-close';
        }
        return $sKey;
    }

    protected static function & getLotteryListByStatus($iStatus = null) {
        $bReadDb   = true;
        $bPutCache = false;
        if (static::$cacheLevel != self::CACHE_LEVEL_NONE) {
            Cache::setDefaultDriver(static::$cacheDrivers[static::$cacheLevel]);
            $sCacheKey  = static::compileLotteryListCacheKey($iStatus);
            if ($oLotteries = Cache::get($sCacheKey)) {
                $bReadDb = false;
            }
            else {
                $bPutCache = true;
            }
        }
//        $bReadDb = $bPutCache = true;
        if ($bReadDb) {
            if (!is_null($iStatus)) {
                $aStatus    = self::_getStatusArray($iStatus);
//                file_put_contents('/tmp/kkkkkk', var_export($aStatus, true));
                $oLotteries = Lottery::whereIn('status', $aStatus)->orderBy('sequence')->get();
            }
            else {
                $oLotteries = Lottery::orderBy('sequence')->get();
            }
        }
        if ($bPutCache) {
            Cache::forever($sCacheKey, $oLotteries);
        }
        return $oLotteries;
    }

    protected static function _getStatusArray($iNeedStatus) {
        $aStatus = [];
        foreach (static::$validStatuses as $iStatus => $sTmp) {
            if (($iStatus & $iNeedStatus) == $iNeedStatus) {
                $aStatus[] = $iStatus;
            }
        }
        return $aStatus;
    }

    /**
     * [getAllLotteries 获取所有彩种信息]
     * @param  [Boolean] $bOpen  [open属性]
     * @param  [Array] $aColumns [要获取的数据列名]
     * @return [Array]           [结果数组]
     */
    public static function getAllLotteries($iStatus = null, $aColumns = null) {
//        $aColumns or $aColumns = ['id', 'series_id', 'name'];
//        if (! is_null($bOpen)) {
//            $aLotteries = Lottery::where('open', '=', $bOpen)->orderBy('sequence')->get($aColumns);
//        } else {
//            $aLotteries = Lottery::orderBy('sequence')->get($aColumns);
//        }
        $oLotteries = static::getLotteryListByStatus($iStatus);
        $data       = [];
        foreach ($oLotteries as $key => $oLottery) {
            if ($aColumns) {
                foreach ($aColumns as $sColumn) {
                    $aTmpData[$sColumn] = $oLottery->$sColumn;
                }
            }
            else {
                $aTmpData = $oLottery->getAttributes(); // ['id' => $value->id, 'series_id' => $value->series_id, 'name' => $value->name];
            }
            $aTmpData['name'] = $oLottery->friendly_name;
            $data[]           = $aTmpData;
        }
        return $data;
    }

    /**
     * generate select widget
     * @return int or false   -1: path not writeable
     */
    public static function generateWidget() {
        $sCacheDataPath = Config::get('widget.data_path');
        if (!is_writeable($sCacheDataPath)) {
            return [
                'code'    => -1,
                'message' => __('_basic.file-write-fail-path', ['path' => $sCacheDataPath]),
            ];
        }
        $sFile = $sCacheDataPath . '/' . 'lotteries.blade.php';
        if (file_exists($sFile) && !is_writeable($sFile)) {
            return [
                'code'    => -1,
                'message' => __('_basic.file-write-fail-file', ['file' => $sFile]),
            ];
        }
        
        $aLotterys = static::getAllLotteries();
//        pr(json_encode($aLotterys));
        $iCode     = @file_put_contents($sFile, 'var lotteries = ' . json_encode($aLotterys));
        $sLangKey  = '_basic.' . ($iCode ? 'file-writed' : 'file-write-fail');
        return [
            'code'    => $iCode,
            'message' => __($sLangKey, ['resource' => $sFile]),
        ];
    }

    /**
     * 返回可用的数字数组
     *
     * @param string $sString
     * @param int $iLotteryType
     * @param int $iLottoType
     * @return array
     */
    public function & getValidNums($sString, $iLotteryType = self::LOTTERY_TYPE_DIGITAL, $iLottoType = self::LOTTERY_TYPE_LOTTO_SINGLE) {
        $data = [];
        if ($iLotteryType == self::LOTTERY_TYPE_LOTTO && $iLottoType != self::LOTTERY_TYPE_LOTTO_SINGLE) {
//            echo "$iLotteryType   New...\n";
            $aStringOfAreas = explode('|', $sString);
            $data           = [];
            foreach ($aStringOfAreas as $iArea => $sStr) {
                $data[$iArea] = & $this->getValidNums($sStr, self::LOTTERY_TYPE_LOTTO, self::LOTTERY_TYPE_LOTTO_SINGLE);
            }
//            return $data;
        }
        else {
            $a = explode(',', $sString);
            foreach ($a as $part) {
                $aPart = explode('-', $part);
                if (count($aPart) == 1) {
                    $data[] = $this->formatBall($aPart[0], $iLotteryType, $iLottoType);
                }
                else {
                    for ($i = $aPart[0]; $i <= $aPart[1]; $i++) {
                        $data[] = $this->formatBall($i, $iLotteryType, $iLottoType);
                    }
                }
            }
        }
        return $data;
    }

    /**
     * 格式化数字
     *
     * @param int $iNum
     * @param int $iLotteryType
     * @param int $iLottoType
     * @return string
     */
    public function formatBall($iNum, $iLotteryType = self::LOTTERY_TYPE_DIGITAL, $iLottoType = self::LOTTERY_TYPE_LOTTO_SINGLE) {
        switch ($iLotteryType) {
            case self::LOTTERY_TYPE_DIGITAL:
                return $iNum + 0;
                break;
            case self::LOTTERY_TYPE_LOTTO:
                switch ($iLottoType) {
                    case self::LOTTERY_TYPE_LOTTO_SINGLE:
                    case self::LOTTERY_TYPE_LOTTO_DOUBLE:
                    case self::LOTTERY_TYPE_LOTTO_MIXED:
                        return str_pad($iNum, 2, '0', STR_PAD_LEFT);
                        break;
                }
        }
    }

    protected function getFriendlyNameAttribute() {
        return __('_lotteries.' . strtolower($this->name), [], 1);
    }

    /**
     * 返回数据列表
     * @param boolean $bOrderByTitle  是否按标题排序，默认为false
     * @param integer $iGameType 游戏类型，默认为null
     * @return array &  键为ID，值为$$titleColumn
     */
    public static function & getTitleList($bOrderByTitle = false, $iGameType = null) {
        $aColumns     = ['id', 'name'];
        $sOrderColumn = $bOrderByTitle ? 'name' : 'sequence';
        if ($iGameType) {
            $oQuery = static::where('game_type', $iGameType);
        }
        else {
            $oQuery = new self;
        }
        $oModels = $oQuery->orderBy($sOrderColumn, 'asc')->get($aColumns);
        $data    = [];
        foreach ($oModels as $oModel) {
            $data[$oModel->id] = $oModel->friendly_name;
        }
        return $data;
    }

    /**
     * 返回人性化的游戏列表，游戏名称为已翻译的
     * @param boolean $bOrderByTitle
     * @return array &  键为ID，值为$$titleColumn
     */
    public static function & getLotteryList() {
        $bReadDb = false;
        $sLocale = App::getLocale();
        if (static::$cacheLevel != self::CACHE_LEVEL_NONE) {
            Cache::setDefaultDriver(static::$cacheDrivers[static::$cacheLevel]);
            $key        = static::compileListCaheKey($sLocale);
            if (!$aLotteries = Cache::get($key)) {
                $bReadDb = true;
            }
        }
        if ($bReadDb) {
            $aLotteries = static::getTitleList();
            !$key or Cache::forever($key, $aLotteries);
        }

        return $aLotteries;
    }

    /**
     * 从数据库提取游戏列表
     * @param bool $bOrderByTitle   是否按名字排序
     * @return array
     */
    protected static function & _getLotteryList($bOrderByTitle = true) {
        $aColumns     = ['id', 'name'];
        $sOrderColumn = $bOrderByTitle ? 'name' : 'sequence';
        $oModels      = static::orderBy($sOrderColumn, 'asc')->get($aColumns);
        $data         = [];
        foreach ($oModels as $oModel) {
            $data[$oModel->id] = $oModel->name;
        }
        return $data;
    }

    public static function & getIdentifierList($bOrderByTitle = false) {
        $aColumns     = ['id', 'identifier'];
        $sOrderColumn = $bOrderByTitle ? 'name' : 'sequence';
        $oModels      = static::orderBy($sOrderColumn, 'asc')->get($aColumns);
        $data         = [];
        foreach ($oModels as $oModel) {
            $data[$oModel->id] = $oModel->identifier;
        }
        return $data;
    }

    /**
     * 更新游戏列表配置
     * @return int  1: 成功 0:失败 -1: 文件不可写
     */
    public static function updateLotteryConfigs() {
        $aLotteries = & static::getIdentifierList();
//        pr($aLotteries);
        $sString    = "<?php\nreturn " . var_export($aLotteries, true) . ";\n";
        $sPath      = app_path('config');
        $sFile      = $sPath . DIRECTORY_SEPARATOR . 'lotteries.php';
        if (!is_writeable($sFile)) {
            return -1;
        }
        return file_put_contents($sFile, $sString) ? 1 : 0;
    }

    public static function updateLotteryListCache() {
        if (static::$cacheLevel == self::CACHE_LEVEL_NONE)
            return true;
        Cache::setDefaultDriver(static::$cacheDrivers[static::$cacheLevel]);
        $sLanguageSource = SysConfig::readDataSource('sys_support_languages');
        // pr($sLanguageSource);
        $aLanguages      = SysConfig::getSource($sLanguageSource);
        $aLotteries      = & self::_getLotteryList();
        foreach ($aLanguages as $sLocale => $sLanguage) {
            $aLotteriesOfLocale = array_map(function($value) use ($sLocale) {
                return __('_lotteries.' . strtolower($value), [], 1, $sLocale);
            }, $aLotteries);
            $key = static::compileListCaheKey($sLocale);
            Cache::forever($key, $aLotteriesOfLocale);
        }
        return true;
    }

    protected static function compileListCaheKey($sLocate) {
        return static::getCachePrefix() . 'lottery-list-' . $sLocate;
    }

    protected static function deleteOtherCache() {
        if (static::$cacheLevel == self::CACHE_LEVEL_NONE)
            return true;
        Cache::setDefaultDriver(static::$cacheDrivers[static::$cacheLevel]);
        $sKey = static::compileLotteryListCacheKey();
        !Cache::has($sKey) or Cache::forget($sKey);
        $sKey = static::compileLotteryListCacheKey(1);
        !Cache::has($sKey) or Cache::forget($sKey);
        $sKey = static::compileLotteryListCacheKey(0);
        !Cache::has($sKey) or Cache::forget($sKey);
        $sKey = static::compileListCaheKey('zh-CN');
        !Cache::has($sKey) or Cache::forget($sKey);
//        compileListCaheKey
    }

    protected function deleteCacheByIdentifier() {
        $sKey = static::compileCacheKeyByIdentifier($this->identifier);
        !Cache::has($sKey) or Cache::forget($sKey);
    }

    protected function afterSave($oSavedModel) {
        parent::afterSave($oSavedModel);
        $this->updateLotteryListCache();
        $this->deleteCacheByIdentifier();
        $this->deleteOtherCache();
        return true;
    }

    protected function afterDelete($oDeletedModel) {
        parent::afterDelete($oDeletedModel);
        $this->updateLotteryListCache();
        $this->deleteOtherCache();
        return true;
    }

    /**
     * 根据代码返回游戏对象
     * @param string $sIdentifier
     * @return Lottery | false
     */
    public static function getByIdentifier($sIdentifier) {
        $bReadDb = false;
        if (static::$cacheLevel != self::CACHE_LEVEL_NONE) {
            Cache::setDefaultDriver(static::$cacheDrivers[static::$cacheLevel]);
            $key         = static::compileCacheKeyByIdentifier($sIdentifier);
            if ($aAttributes = Cache::get($key)) {
                $obj = new static;
                $obj = $obj->newFromBuilder($aAttributes);
            }
            else {
                $bReadDb = true;
            }
        }
        if ($bReadDb) {
            $obj = static::where('identifier', '=', $sIdentifier)->first();
            if (!is_object($obj)) {
                return false;
            }
            !$key or Cache::forever($key, $obj->getAttributes());
        }

        return $obj;
    }

    protected static function compileCacheKeyByIdentifier($sIdentifier) {
        return static::getCachePrefix() . 'lottery-identifier-' . strtolower($sIdentifier);
    }

    /**
     * [getAllLotteriesGroupBySeries 根据彩系组织彩种]
     * @param  [Integer] $iOpen     [open属性]
     * @param  [boolean] $bNeedLink [是否需要判断彩系的link_to属性]
     * @return [Array]           [彩种数据]
     */
    public static function getAllLotteriesGroupBySeries($iStatus = null, $bNeedLink = true, $aLotteryColumns = null) {
        $aLotteries      = static::getAllLotteries($iStatus, $aLotteryColumns);
        $aLinkTo         = Series::getAllSeriesWithLinkTo();
        $aLotteriesArray = [];
        foreach ($aLotteries as $key => $aLottery) {
            if ($bNeedLink && $aLinkTo[$aLottery['series_id']]) {
                $aLottery['series_id'] = $aLinkTo[$aLottery['series_id']];
            }
            if (!isset($aLotteriesArray[$aLottery['series_id']])) {
                $aLotteriesArray[$aLottery['series_id']] = [];
            }
            $aLotteriesArray[$aLottery['series_id']][] = $aLottery;
        }
        return $aLotteriesArray;
    }

    /**
     * [getAllLotteryIdsGroupBySeries 生成彩种--彩系的映射数组, 彩系以linkTo属性为准]
     * @return [Array] [彩种--彩系的映射数组]
     */
    public static function getAllLotteryIdsGroupBySeries() {
        $aLotteries      = static::getAllLotteries();
        $aLinkTo         = Series::getAllSeriesWithLinkTo();
        $aLotteriesArray = [];
        foreach ($aLotteries as $key => $aLottery) {
            if ($aLinkTo[$aLottery['series_id']]) {
                $aLottery['series_id'] = $aLinkTo[$aLottery['series_id']];
            }
            $aLotteriesArray[$aLottery['id']] = $aLottery['series_id'];
        }
        return $aLotteriesArray;
    }

    protected static function getValidTypes() {
        return self::_getArrayAttributes(__FUNCTION__);
    }

    protected static function getValidStatuses() {
        return self::_getArrayAttributes(__FUNCTION__);
    }

    protected static function getValidLottoTypes() {
        return self::_getArrayAttributes(__FUNCTION__);
    }

    public static function getGroupPrizeLottery() {
        $aConditions = [
            'type' => ['in', [static::LOTTERY_TYPE_DIGITAL, static::LOTTERY_TYPE_LOTTO]]
        ];
        return static::doWhere($aConditions)->get();
    }

    /**
     * get lottery json format from cache
     * 彩种名称
     * if not exists then store it
     * @author lucky
     * @date 2016-11-10
     * @return String
     */
    static function getLotteryListJson($type = 1) {
        $key = ($type == 1 ? static::$lottery_list_digital_json_key : static::$lottery_list_sport_json_key);
        if (!Cache::get($key)) {
            $sLotteryListJson = static::generateLotteryJsonFormat($type);
            Cache::put($key, $sLotteryListJson, 0);
        }
        return Cache::get($key);
    }

    /**
     * 游戏玩法下拉框
     * if not exists then store it
     * @author lucky
     * @date 2016-11-10
     * @return String
     */
    static function getLotteryWaysJson() {
        $key = self::$lottery_ways_json_key;
        if (!Cache::get($key)) {
            $sLotteryListJson = static::generateWayJsonFormat();
            Cache::put($key, $sLotteryListJson, 0);
        }
        return Cache::get($key);
    }

    /**
     * 游戏名称下拉框
     * @author lucky
     * @date 2016-11-10
     * @param  $type 1 = digital,2=sport
     * return string
     */
    static function generateLotteryJsonFormat($type = 1) {
        $aLotteries = Lottery::getAllLotteriesGroupBySeries(static::STATUS_AVAILABLE);
        if (!isset($aLotteries[static::$sport_series_key])) {
            $aLotteries[static::$sport_series_key] = [];
        }

        $aLotteriesArr = [];
        if ($type == 1) {
            unset($aLotteries[static::$sport_series_key]);
            foreach ($aLotteries as $key => $lottery) {
                $aLotteriesArr = array_merge($aLotteriesArr, $lottery);
            }
        }
        elseif ($type == 2) {
            $aLotteriesArr = $aLotteries[static::$sport_series_key];
        }
//        dump($lotteries_digital,$lotteries_sport);
        $alotteriesSortArr = [];
        foreach ($aLotteriesArr as $lottery) {
            $aNewLottery              = [];
            $id                       = $aNewLottery['id']        = $lottery['id'];
            $aNewLottery['name']      = $lottery['name'];
            $aNewLottery['series_id'] = $lottery['series_id'];
            $aNewLottery['status']    = $lottery['status'];
            $alotteriesSortArr[$id]   = $aNewLottery;
        }

        asort($alotteriesSortArr);

        $aLotteryWayArr = [];
        foreach ($alotteriesSortArr as $key => $lottery) {
            $aLotteryWayArr[$key]['id']        = $key;
            $aLotteryWayArr[$key]['name']      = $lottery['name'];
            $aLotteryWayArr[$key]['series_id'] = $lottery['series_id'];
            $aLotteryWayArr[$key]['status']    = $lottery['status'];
            $lottery_way                       = LotteryWay::getLotteryWaysByLotteryId($key);
            $aLotteryWayArr[$key]['chilren']   = $lottery_way;
        }

        return json_encode($aLotteryWayArr);
//        dump($lottery_way_arr);
    }

    /**
     * 投注方式下拉框
     * @author lucky
     * @date 2016-11-10
     * return String
     */
    static function generateWayJsonFormat() {
        $series      = Lottery::getAllLotteriesGroupBySeries(static::STATUS_AVAILABLE);
        $aLotteryWay = [];
        foreach ($series as $series_id => $oSerieLottery) {
            $k                       = 'series_id_' . $series_id;
            $aLotteryWay[$k]         = [];
            $serie                   = Series::find($series_id);
            $aLotteryWay[$k]['id']   = $serie->id;
            $aLotteryWay[$k]['name'] = $serie->name;

            $aLotteryWay[$k]['children'] = [];

//            $way_methods=SeriesWayMethod::where('series_id','=',$series_id)
//                                        ->get();
            $aWayGroups = WayGroup::where('series_id', '=', $series_id)
                ->where('parent_id', '=', null)
                ->get();

            foreach ($aWayGroups as $oWayGroup) {

//                $lottery_way[$k]['children']['id']=$way_group->id;
//                $lottery_way[$k]['children']['name']=$way_group->title;

                $j = 'parent_id_' . $oWayGroup->id;

                $aLotteryWay[$k]['children'][$j]         = [];
                $aLotteryWay[$k]['children'][$j]['id']   = $oWayGroup->id;
                $aLotteryWay[$k]['children'][$j]['name'] = $oWayGroup->title;

//                dump($way_group->id,$way_group->series_id);

                //the groups to a big group like "后三"=>"[祖选,直选,其它]
                $aWayGroupIds  = WayGroup::where('parent_id', '=', $oWayGroup->id)
                    ->lists("id");
                $aWayGroupWays = WayGroupWay::whereIn("group_id", $aWayGroupIds)
                    ->get();

                $aLotteryWay[$k]['children'][$j]['children'] = [];
                foreach ($aWayGroupWays as $key => $oWayGroupWay) {
                    $aLotteryWay[$k]['children'][$j]['children'][] = [
                        'id'   => $oWayGroupWay->series_way_id,
                        'name' => $oWayGroupWay->title
                    ];
                }
            }
        }
        return json_encode($aLotteryWay);
//        return $lottery_way;
    }

    /**
     * 清除缓存，重新生成下拉框信息
     * @author lucky
     * @date 2016-11-10
     */
    static function removeCache() {
        Cache::forget(static::$lottery_list_digital_json_key);
        Cache::forget(static::$lottery_list_sport_json_key);
        Cache::forget(static::$lottery_ways_json_key);
        return static::generateLotteryJsonFormat(static::LOTTERY_TYPE_DIGITAL) && static::generateLotteryJsonFormat(static::LOTTERY_TYPE_SPORT) && static::getLotteryWaysJson();
    }

    /**
     * 获取游戏类别彩种ID
     * @author lucky
     * @date 2017-08-02
     * @param array $aType
     * @return mixed
     */
    public static function getLotteryIdsByType($aType = [1, 2]){
        return static::whereIn("type", $aType)
            ->where("status", static::STATUS_AVAILABLE)
            ->lists("id");
    }

    /**
     * 增加游戏类型ID
     * @author lucky
     * @date 2017-08-04
     * @param int $iLotteryId
     * @return mixed
     */
    public static function getGameTypeByLotteryId($iLotteryId = 1){
        $oLottery = static::find($iLotteryId);
        return $oLottery? $oLottery->game_type : 0;
    }

    /**
     * 取得同一彩系数据列表
     *
     * @author　okra
     * @date 2017-3-24
     * @param int $iSeriesId 父彩种编号
     * @return LotteryList $oLotteryList 同一彩种数据列表
     */
    public static function getBySeriesId($iSeriesId, $iStatus = null) {
        $oQuery = static::where('series_id', $iSeriesId);
        !($iStatus && array_key_exists($iStatus, static::$validStatuses)) or $oQuery = $oQuery->where('status', $iStatus);
        return $oQuery->get();
    }

}
