<?php

/**
 * 彩票系列模型
 */
class Series extends BaseModel {

    protected static $cacheLevel = self::CACHE_LEVEL_FIRST;
    public static $resourceName  = 'Series';
    protected $table             = 'series';

    const LOTTERY_SERIES_SSC  = 1;
    const LOTTERY_SERIES_11Y  = 2;
    const LOTTERY_SERIES_3D   = 3;
    const LOTTERY_SERIES_K3   = 4;
    const LOTTERY_SERIES_PK10 = 5;
    const LOTTERY_SERIES_JC   = 6;
    const LOTTERY_SERIES_KENO = 7;
    const LOTTERY_SERIES_12X5 = 8;
    const LOTTERY_SERIES_KLSF = 9;
    //奖金组返点类型
    const NORMAL_GROUP_TYPE   = 1;
    //百分比饭店类型
    const PERCENT_GROUP_TYPE  = 2;

    //返点类型
    public static $groupTypes                = [
        self::NORMAL_GROUP_TYPE  => 'group',
        self::PERCENT_GROUP_TYPE => 'percent',
    ];
    public static $htmlOriginalNumberColumns = [
        'lotto_type'
    ];
    public static $listColumnMaps            = [
        'group_type'     => 'grouptype_formatted',
        'default_way_id' => 'default_way_id_formatted',
    ];
    public static $viewColumnMaps            = [
        'group_type'     => 'grouptype_formatted',
        'default_way_id' => 'default_way_id_formatted',
    ];

    /**
     * the columns for list page
     * @var array
     */
    public static $columnForList = [
        'id',
        'game_type',
        'type',
        'lotto_type',
        'name',
        'identifier',
        'sort_winning_number',
        'digital_count',
        'classic_amount',
        'group_type',
        'max_percent_group',
        'max_prize_group',
        'max_real_group',
        'max_bet_group',
        'buy_length',
//        'wn_length',
////        'valid_nums',
//        'lotteries',
//        'default_way_id',
        'link_to',
    ];
    protected $fillable          = [
        'type',
        'lotto_type',
        'name',
        'identifier',
        'lotteries',
        'buy_length',
        'wn_length',
        'digital_count',
        'valid_nums',
        'offical_prize_rate',
        'classic_amount',
        'group_type',
        'max_percent_group',
        'max_prize_group',
        'max_real_group',
        'sort_winning_number',
        'default_way_id',
        'max_bet_group',
        'link_to',
        'plat_id',
        'plat_name',
        'game_type'
    ];

    /**
     * order by config
     * @var array
     */
    public $orderColumns             = [
        'id' => 'asc'
    ];
    public static $htmlSelectColumns = [
        'type'       => 'aLotteryTypes',
        'link_to'    => 'aSeries',
        'group_type' => 'aGroupTypes',
        'plat_id'    => 'aPlats',
        'game_type' => 'aGameTypes',
    ];

    /**
     * the main param for index page
     * @var string
     */
    public static $mainParamColumn = '';

    /**
     * The rules to be applied to the data.
     *
     * @var array
     */
    public static $rules = [
        'type'                => 'required|integer',
        'lotto_type'          => 'integer',
        'name'                => 'required|max:20',
        'identifier'          => 'required|max:20',
        'digital_count'       => 'required|numeric',
        'classic_amount'      => 'required|integer',
        'group_type'          => 'integer',
        'max_prize_group'     => 'required|integer',
        'max_real_group'      => 'required|integer',
        'max_bet_group'       => 'required|integer',
        'max_percent_group'   => 'integer',
        'buy_length'          => 'required',
        'wn_length'           => 'required',
        'valid_nums'          => 'required|max:250',
        'offical_prize_rate'  => 'numeric|max:0.99',
        'lotteries'           => 'max:200',
        'default_way_id'      => 'integer',
        'sort_winning_number' => 'in:0,1',
        'link_to'             => 'integer',
        'plat_id'             => 'integer',
        'game_type'           => 'integer',
    ];

    /**
     * The array of custom error messages.
     *
     * @var array
     */
    public static $customMessages = [];

    /**
     * title field
     * @var string
     */
    public static $titleColumn = 'name';
    public $timestamps         = false;

//    public function lotteries()
//    {
//        return $this->hasMany('Lottery');
//    }

    protected function getFriendlyNameAttribute() {
        return __('_series.' . strtolower($this->name));
    }

    protected function getGroupTypeFormattedAttribute() {
        return __('_series.' . self::$groupTypes[$this->group_type]);
    }

    /**
     * 检查是否存在相同的游戏名称
     *
     * @return boolean
     */
    private function _existName() {

    }

    protected function beforeValidate() {
        if (strpos($this->valid_nums, '-')) {
            list($iMin, $iMax) = explode('-', $this->valid_nums);
            $aValidNums = [];
            for ($i = $iMin; $i <= $iMax; $i++) {
                $aValidNums[] = $i;
            }
            $this->valid_nums = implode(',', $aValidNums);
        }
        if (!$this->max_real_group || $this->max_real_group > $this->max_prize_group) {
            $this->max_real_group = $this->max_prize_group;
        }
        if (!$this->max_bet_group || $this->max_bet_group > $this->max_prize_group) {
            $this->max_bet_group = $this->max_prize_group;
        }
        !empty($this->link_to) or $this->link_to        = null;
        !empty($this->lotto_type) or $this->lotto_type     = null;
        !empty($this->default_way_id) or $this->default_way_id = null;
        if ($this->type == Lottery::LOTTERY_TYPE_DIGITAL) {
            $this->sort_winning_number = null;
        } else {
            in_array($this->sort_winning_number, [0, 1]) or $this->sort_winning_number = null;
        }
        if ($this->plat_id) {
            $this->plat_name = ThirdPlat::where('id', '=', $this->plat_id)->pluck('name');
        } else {
            $this->plat_id = null;
        }
        return parent::beforeValidate();
    }

    private static function compileAllSeriesCacheKey() {
        return static::getCachePrefix(true) . 'all';
    }

    private static function & getAllSeries() {
        $bReadDb   = true;
        $bPutCache = false;
        if (static::$cacheLevel != self::CACHE_LEVEL_NONE) {
            Cache::setDefaultDriver(static::$cacheDrivers[static::$cacheLevel]);
            $sCacheKey = static::compileAllSeriesCacheKey();
            if ($aSeires   = Cache::get($sCacheKey)) {
                $bReadDb = false;
            } else {
                $bPutCache = true;
            }
        }
        if ($bReadDb) {
            $oSeires = static::all();
            $aSeires = [];
            foreach ($oSeires as $oSeires) {
                $aSeires[] = $oSeires->getAttributes();
            }
        }
        if ($bPutCache) {
            Cache::forever($sCacheKey, $aSeires);
        }

        return $aSeires;
    }

    /**
     * [getLotteriesGroupBySeries 获取带彩系信息的彩种数据]
     * @param  [Integer] $iOpen  [open属性]
     * @param  [Boolean] $bNeedLink  [是否需要判断彩系的link_to属性]
     * @param  [Array] $aColumns [要得到的数据列数组]
     * @return [Array]           [彩种数据]
     */
    public static function & getLotteriesGroupBySeries($iStatus = null, $bNeedLink = true, $aColumns = null) {
//        $aColumns or $aColumns = ['id', 'name', 'identifier', 'link_to'];
//        $aSeires = static::all($aColumns);
        $aAllSeires      = & static::getAllSeries();
        $data            = [];
        $aLotteriesArray = Lottery::getAllLotteriesGroupBySeries($iStatus, $bNeedLink);
//         pr($aLotteriesArray);exit;
        foreach ($aAllSeires as $aSeries) {
            if (isset($aLotteriesArray[$aSeries['id']])) {
                $oSeries                = static::find($aSeries['id']);
                $oSeries->children      = $aLotteriesArray[$oSeries->id];
                // 将模型的虚拟属性固定为对象的属性
                $oSeries->friendly_name = $oSeries->friendly_name;
                // if ($bNeedLink) {
                $data[]                 = $oSeries->getAttributes();
                // }
            }
        }
//         pr($data);exit;
        return $data; // $bNeedLink ? $data : $aSeires;
    }

    public static function & getSeriesLotteriesArray($bNeedLink, $aSeriesColumns = null, $aLotteryColumns = null) {
//        $aColumns or $aColumns = ['id', 'name', 'identifier', 'link_to'];
//        $aSeires = static::all($aColumns);
        $aAllSeires      = & static::getAllSeries();
        $data            = [];
        $aLotteriesArray = Lottery::getAllLotteriesGroupBySeries(null, $bNeedLink, $aLotteryColumns);
//        pr($aLotteriesArray);
//        exit;
//        $aSeriesColumns or $aSeriesColumns = ['*'];
        foreach ($aAllSeires as $aSeries) {
            if (isset($aLotteriesArray[$aSeries['id']])) {
                $oSeries                = static::find($aSeries['id'], $aSeriesColumns);
                $oSeries->children      = $aLotteriesArray[$oSeries->id];
                // 将模型的虚拟属性固定为对象的属性
                $oSeries->friendly_name = $oSeries->friendly_name;
                // if ($bNeedLink) {
                $data[]                 = $oSeries->getAttributes();
                // }
            }
        }
        // pr($data);exit;
        return $data; // $bNeedLink ? $data : $aSeires;
    }

    /**
     * [getAllSeriesWithLinkTo 获取所有带link_to属性的彩系信息]
     * @param  [Collection] $aSeires [彩系数据集合]
     * @return [Array]          [彩系之间关联数据数组]
     */
    public static function getAllSeriesWithLinkTo($aSeires = null) {
        $aAllSeires = static::getAllSeries();
//        if (!$aSeires) {
//            $aColumns = ['id', 'name', 'identifier', 'link_to'];
//            $aSeires = static::all($aColumns);
//        }
        $aLinkTo    = [];
        foreach ($aAllSeires as $key => $aSeries) {
            $aLinkTo[$aSeries['id']] = $aSeries['link_to'];
        }
        return $aLinkTo;
    }

    /**
     * 获取所有linkto指定的彩种系列
     * @param int $iSeriesId  彩种系列id
     * @return [Array]          [彩种系列数组]
     */
    public static function getSeriesByLinkTo($iSeriesId) {
        $aColumns = ['id', 'lotteries'];
        $results  = static::doWhere(['link_to' => ['=', $iSeriesId]])->get($aColumns);
        return $results;
    }

    public static function getRealSeriesId($iSeriesId) {
        $obj = static::find($iSeriesId);
        return $obj->link_to ? $obj->link_to : $iSeriesId;
    }

    public function deleteOtherCache() {
        if (static::$cacheLevel == self::CACHE_LEVEL_NONE)
            return true;
        Cache::setDefaultDriver(static::$cacheDrivers[static::$cacheLevel]);
        $sKey = static::compileAllSeriesCacheKey();
        !Cache::has($sKey) or Cache::forget($sKey);
    }

    protected function getDefaultWayIdFormattedAttribute() {
        if ($iWayId = $this->attributes['default_way_id']) {
            $oSeriesWay = SeriesWay::find($iWayId);
            $sName      = $oSeriesWay->name;
        } else {
            $sName = null;
        }
        return $sName;
    }

    public static function & getSeriesLotteriesForEncode() {
        $aAllSeires      = & static::getAllSeries();
        $data            = [];
        $aLotteriesArray = Lottery::getAllLotteriesGroupBySeries();
        foreach ($aAllSeires as $aSeries) {
            if (isset($aLotteriesArray[$aSeries['id']])) {
                $oSeries = static::find($aSeries['id']);
                $subdata = [
                    'id'       => $oSeries->id,
                    'name'     => $oSeries->friendly_name,
                    'children' => []
                ];
                foreach ($aLotteriesArray[$aSeries['id']] as $aLotteryInfo) {
                    if ($aLotteryInfo['status'] == Lottery::STATUS_CLOSED_FOREVER || $aLotteryInfo['is_instant'] || $aLotteryInfo['plat_id']) {
                        continue;
                    }
                    $subdata['children'][] = [
                        'id'   => $aLotteryInfo['id'],
                        'name' => $aLotteryInfo['name']
                    ];
                }
                $data[] = $subdata;
            }
        }
        return $data;
    }

    /**
     * [getLotteriesGroupBySeries 获取带彩系信息的彩种数据]
     * @param  [Integer] $iOpen  [open属性]
     * @param  [Boolean] $bNeedLink  [是否需要判断彩系的link_to属性]
     * @param  [Array] $aColumns [要得到的数据列数组]
     * @return [Array]           [彩种数据]
     */
    public static function & getLotteriesGroupBySeriesGroupType($aGroupTypes = [self::NORMAL_GROUP_TYPE]) {
//        $aColumns or $aColumns = ['id', 'name', 'identifier', 'link_to'];
//        $aSeires = static::all($aColumns);
        $aAllSeires      = & static::getAllSeries();
        $data            = [];
        $aLotteriesArray = Lottery::getAllLotteriesGroupBySeries(null, true);
//         pr($aLotteriesArray);exit;
        foreach ($aAllSeires as $aSeries) {
            if (!in_array($aSeries['group_type'], $aGroupTypes)) {
                continue;
            }
            if (isset($aLotteriesArray[$aSeries['id']])) {
                $oSeries                = static::find($aSeries['id']);
                $oSeries->children      = $aLotteriesArray[$oSeries->id];
                // 将模型的虚拟属性固定为对象的属性
                $oSeries->friendly_name = $oSeries->friendly_name;
                // if ($bNeedLink) {
                $data[]                 = $oSeries->getAttributes();
                // }
            }
        }
//         pr($data);exit;
        return $data; // $bNeedLink ? $data : $aSeires;
    }

}
