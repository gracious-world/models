<?php

class WayGroup extends BaseModel {

    protected static $cacheLevel     = self::CACHE_LEVEL_FIRST;
    protected $table                 = 'way_groups';
    protected $softDelete            = false;
    protected $fillable              = [
        'series_id',
        'terminal_id',
        'parent_id',
        'title',
        'en_title',
        'for_display',
        'sequence',
    ];
    public static $resourceName      = 'Way Group';
    public static $titleColumn       = 'title';
    public static $sequencable       = true;
    public static $columnForList     = [
        'series_id',
        'terminal_id',
        'parent',
        'title',
        'en_title',
        'for_display',
        'for_search',
        'sequence',
    ];
    public static $htmlSelectColumns = [
        'series_id'   => 'aSeries',
        'parent_id'   => 'aMainGroups',
        'terminal_id' => 'aTerminals',
    ];
    public $orderColumns             = [
        'sequence' => 'asc',
        'id'       => 'asc'
    ];
    public static $mainParamColumn   = 'series_id';
    public static $rules             = [
        'parent_id'   => 'integer',
        'terminal_id' => 'integer',
        'title'       => 'required|max:20',
        'en_title'    => 'max:20',
        'sequence'    => 'integer',
        'for_display' => 'required|in:0,1',
    ];
    public static $treeable          = true;

    protected function afterSave($oSavedModel) {
        parent::afterSave($oSavedModel);
        $oSavedModel->deleteLotteryCache($oSavedModel->series_id, $oSavedModel->terminal_id);
    }

    protected function afterUpdate() {
        $this->deleteCache($this->id);
        $this->deleteLotteryCache($this->series_id, $this->terminal_id);
    }

    protected function afterDelete($oDeletedModel) {
        $this->deleteCache($oDeletedModel->id);
        $this->deleteLotteryCache($oDeletedModel->series_id, $oDeletedModel->terminal_id);
        return true;
    }

    protected function beforeValidate() {
        if ($this->parent_id) {
            $oGroup          = WayGroup::find($this->parent_id);
            $this->series_id = $oGroup->series_id;
        }
        parent::beforeValidate();
    }

    public function getWays($bForBet = true) {
        $oWayGroupWay = new WayGroupWay;
        $sField       = $bForBet ? 'for_display' : 'for_search';
        $aConditions  = [
            'group_id' => ['=', $this->id],
            $sField    => ['=', 1]
        ];
        $oQuery       = $oWayGroupWay->doWhere($aConditions);
        $oQuery       = $oWayGroupWay->doOrderBy($oQuery, $oWayGroupWay->orderColumns);
        return $oQuery->get()->toArray();
    }

    /**
     * 取得玩法设置数组，供奖金页面使用
     * @param int $iGroupId
     * @return array &
     */
    public static function & getWaySettings($oLottery, $iPrizeGroupId) {
        $aPrizes = & PrizeGroup::getPrizeDetails($iPrizeGroupId);
        return WayGroup::getWayInfos($oLottery, $aPrizes);
    }

    public static function deleteLotteryCache($iSeriesId, $iTerminalId = 1) {
        if (static::$cacheLevel != self::CACHE_LEVEL_NONE) {
            Cache::setDefaultDriver(static::$cacheDrivers[static::$cacheLevel]);
            $sCacheKey = static::makeCacheKeyLottery($iSeriesId, true, $iTerminalId);
            Cache::forget($sCacheKey);
            $sCacheKey = static::makeCacheKeyLottery($iSeriesId, false, $iTerminalId);
            Cache::forget($sCacheKey);
        }
    }

    private static function & getWayGroups($iSeriesId, $bForBet = true, $iTerminalId = 1) {
        $bReadDb   = true;
        $bPutCache = false;
        if (static::$cacheLevel != self::CACHE_LEVEL_NONE) {
            Cache::setDefaultDriver(static::$cacheDrivers[static::$cacheLevel]);
            $sCacheKey  = static::makeCacheKeyLottery($iSeriesId, $bForBet, $iTerminalId);
            if ($aWayGroups = Cache::get($sCacheKey)) {
                $bReadDb = false;
            } else {
                $bPutCache = true;
            }
        }
//        $bReadDb=$bPutCache=1;
        if ($bReadDb) {
            $aWayGroups = static::getWayGroupsFromDB($iSeriesId, $bForBet, $iTerminalId);
        }
//        pr($aWayGroups);
//        exit;
        if ($bPutCache) {
            Cache::forever($sCacheKey, $aWayGroups);
        }
        return $aWayGroups;
    }

    private static function & getWayGroupsFromDB($iSeriesId, $bForBet = true, $iTerminalId = 1) {
        $oWayGroup   = new WayGroup;
        $aConditions = [
            'series_id'   => ['=', $iSeriesId],
            'terminal_id' => ['=', $iTerminalId],
            'for_display' => ['=', 1]
        ];
        $aMainGroups = & $oWayGroup->getSubObjectArray(null, $aConditions);
        foreach ($aMainGroups as $oMainGroup) {
            $data       = [
                'id'      => $oMainGroup->id,
                'pid'     => intval($oMainGroup->parent_id),
                'name_cn' => $oMainGroup->title,
                'name_en' => $oMainGroup->en_title,
            ];
            $aSubGroups = & $oWayGroup->getSubObjectArray($oMainGroup->id, $aConditions);
//            foreach($aSubGroups as $a){
//                pr($a->getAttributes());
//            }
//            continue;
//            exit;
            $aSubs      = [];
//            pr($aSubs);
            foreach ($aSubGroups as $oSubGroup) {
                $sub   = [
                    'id'      => $oSubGroup->id,
                    'pid'     => $oSubGroup->parent_id,
                    'name_cn' => $oSubGroup->title,
                    'name_en' => $oSubGroup->en_title,
                ];
                $aWays = $oSubGroup->getWays($bForBet);
                $ways  = [];
                foreach ($aWays as $aWay) {
//                    echo $aWay['series_way_id'],',';
                    $oSeriesWay      = SeriesWay::find($aWay['series_way_id']);
                    $sBasicMethodIds = $oSeriesWay->getAttribute('basic_methods');
                    $aBasicMethodIds = explode(',', $sBasicMethodIds);
//                    $aWayPrizes = [];
//                    foreach($aBasicMethodIds as $iBasicMethodId){
//                        $aWayPrizes[] = $aPrizes[$iBasicMethodId]['prize'];
//                    }
                    $waydata         = [
                        'id'            => $aWay['series_way_id'],
                        'pid'           => $aWay['group_id'],
                        'series_way_id' => $aWay['series_way_id'],
                        'name_cn'       => $aWay['title'],
                        'name_en'       => $aWay['en_title'],
                        'price'         => $oSeriesWay->price,
                        'bet_note'      => $oSeriesWay->bet_note,
                        'bonus_note'    => $oSeriesWay->bonus_note,
                    ];
//                    if ($fMaxPrize){
//                        $waydata['prize'] = min($aWayPrizes);
//                        $waydata['max_multiple'] = intval($fMaxPrize / min($aWayPrizes));
//                    }
//                    else{
//                        $waydata['prize'] = implode(',', $aWayPrizes);
//                    }

                    $ways[] = $waydata;

//                    pr($sBasicMethodIds->toArray());
//                    exit;
//        ->get(['basic_methods'])->first()->getAttribute('basic_methods');
//                    pr($sBasicMethodIds);
                    // prize
                }
                $sub['children'] = $ways;
                $aSubs[]         = $sub;
//                pr($sub);
//                break;
            }
            $data['children'] = $aSubs;
            $aWayGroups[]     = $data;
        }
        return $aWayGroups;
    }

    private static function makeCacheKeyLottery($iLotteryId, $bForBet = true, $iTerminalId = 1) {
        return static::getCachePrefix(true) . 'Lottery-' . $iLotteryId . '-' . ($bForBet ? 'true' : 'false') . '-' . $iTerminalId;
    }

    /**
     * 取得玩法设置数组，供渲染投注页面或奖金页面使用
     *
     * @param Lottery   $oLottery
     * @param array     $aPrizes
     * @param int       $fMaxPrize
     * @return array &
     */
    public static function & getWayInfos($iSeriesId, & $aPrizes, $fMaxPrize = null, $iTerminalId = 1, $bForBet = false ) {
        $aWayGroups = static::getWayGroups($iSeriesId, $bForBet, $iTerminalId);
//        pr($aWayGroups);
//        exit;
        for ($i = 0; $i < count($aWayGroups); $i++) {
            $aSubGroups = & $aWayGroups[$i]['children'];
            for ($j = 0; $j < count($aSubGroups); $j++) {
                $aWays = & $aSubGroups[$j]['children'];
                foreach ($aWays as $k => $aWay) {
//                    echo $aWay['series_way_id'],',';
                    $oSeriesWay      = SeriesWay::find($aWay['series_way_id']);
                    $sBasicMethodIds = $oSeriesWay->getAttribute('basic_methods');
                    $aBasicMethodIds = explode(',', $sBasicMethodIds);
                    $aWayPrizes      = [];
                    foreach ($aBasicMethodIds as $iBasicMethodId) {
                        $aWayPrizes[] = $aPrizes[$iBasicMethodId]['prize'];
                    }
                    if ($fMaxPrize) {
                        $aWays[$k]['prize']        = min($aWayPrizes);
                        $aWays[$k]['max_multiple'] = intval($fMaxPrize / min($aWayPrizes));
                    } else {
                        $aWays[$k]['prize']        = implode(',', $aWayPrizes);
                        $aWays[$k]['max_multiple'] = 0;
                    }
                }
            }
        }
        return $aWayGroups;
    }

    /**
     * 取得玩法设置数组，供奖金页面使用
     *
     * @param Lottery   $oLottery
     * @param array     $aPrizes
     * @param int       $fMaxPrize
     * @return array &
     */
    public static function & getWayInfosForPrizeList($iSeriesId, & $aPrizes, $iTerminalId = 1) {
//        pr($aPrizes);
//        exit;
        $aWayGroups = static::getWayGroups($iSeriesId, false, $iTerminalId);
//        pr($aWayGroups);
//        exit;
        for ($i = 0; $i < count($aWayGroups); $i++) {
            $aSubGroups = & $aWayGroups[$i]['children'];
            for ($j = 0; $j < count($aSubGroups); $j++) {
                $aWays = & $aSubGroups[$j]['children'];
                foreach ($aWays as $k => $aWay) {
//                    echo $aWay['series_way_id'],',';
                    $oSeriesWay      = SeriesWay::find($aWay['series_way_id']);
                    $sBasicMethodIds = $oSeriesWay->getAttribute('basic_methods');
                    $aBasicMethodIds = explode(',', $sBasicMethodIds);
                    $aWayPrizes      = [];
                    if (count($aBasicMethodIds) > 1) {
                        foreach ($aBasicMethodIds as $iBasicMethodId) {
//                            pr($aPrizes[$iBasicMethodId]['level'][1]);
//                            exit;
                            $aWayPrizes[1] = $aPrizes[$iBasicMethodId]['level'][1];
                        }
                    } else {
                        foreach ($aBasicMethodIds as $iBasicMethodId) {
                            foreach ($aPrizes[$iBasicMethodId]['level'] as $iLevel => $fPrize) {
                                $aWayPrizes[$iLevel] = $fPrize;
                            }
//                            $aWayPrizes[] = $aDetail;
                        }
                    }
//                    pr($aWayPrizes);
//                    exit;
                    $aWays[$k]['prize'] = $aWayPrizes;
//                    if ($fMaxPrize){
//                        $aWays[$k]['prize'] = min($aWayPrizes);
//                        $aWays[$k]['max_multiple'] = intval($fMaxPrize / min($aWayPrizes));
//                    }
//                    else{
//                        $aWays[$k]['prize'] = implode(',', $aWayPrizes);
//                        $aWays[$k]['max_multiple'] = 0;
//                    }
                }
            }
        }
//        pr($aWayGroups);
//        exit;
        return $aWayGroups;
    }

    public static function & getWayGroupSettings($iSeriesId, $iTerminalId = 1) {
        $aWayGroups = static::getWayGroups($iSeriesId, true, $iTerminalId);
        for ($i = 0; $i < count($aWayGroups); $i++) {
            $aSubGroups = & $aWayGroups[$i]['children'];
            for ($j = 0; $j < count($aSubGroups); $j++) {
                $aWays = & $aSubGroups[$j]['children'];
                foreach ($aWays as $k => $aWay) {
                    $oSeriesWay                 = SeriesWay::find($aWay['series_way_id']);
                    $aWays[$k]['basic_methods'] = $oSeriesWay->getAttribute('basic_methods');

                }

//                foreach($aWays as $k => $aWay){
////                    echo $aWay['series_way_id'],',';
//                    $oSeriesWay = SeriesWay::find($aWay['series_way_id']);
//                    $sBasicMethodIds = $oSeriesWay->getAttribute('basic_methods');
//                    $aBasicMethodIds = explode(',', $sBasicMethodIds);
//                    $aWayPrizes = [];
//                    foreach($aBasicMethodIds as $iBasicMethodId){
//                        $aWayPrizes[] = $aPrizes[$iBasicMethodId]['prize'];
//                    }
//                    if ($fMaxPrize){
//                        $aWays[$k]['prize'] = min($aWayPrizes);
//                        $aWays[$k]['max_multiple'] = intval($fMaxPrize / min($aWayPrizes));
//                    }
//                    else{
//                        $aWays[$k]['prize'] = implode(',', $aWayPrizes);
//                        $aWays[$k]['max_multiple'] = 0;
//                    }
//                }
            }
        }
        return $aWayGroups;
    }

    public static function & getWayGroupPrizeSettings($iSeriesId, $aPrizes, $iTerminalId = 1,$fMaxPrize=null) {
        $aWayGroups = static::getWayGroups($iSeriesId, true, $iTerminalId);
        for ($i = 0; $i < count($aWayGroups); $i++) {
            $aSubGroups = & $aWayGroups[$i]['children'];
            for ($j = 0; $j < count($aSubGroups); $j++) {
                $aWays = & $aSubGroups[$j]['children'];
                foreach ($aWays as $k => $aWay) {
                    $oSeriesWay                 = SeriesWay::find($aWay['series_way_id']);
                    $aWays[$k]['basic_methods'] = $oSeriesWay->getAttribute('basic_methods');
                    $sBasicMethodIds = $oSeriesWay->getAttribute('basic_methods');

                    $aBasicMethodIds = explode(',', $sBasicMethodIds);
                    $aBasicMethodIds = array_unique($aBasicMethodIds);

                    $aWayPrizes      = [];
                    foreach ($aBasicMethodIds as $iBasicMethodId) {
                        if(isset($aPrizes[$iBasicMethodId])) {
                            $aWayPrizes[] = $aPrizes[$iBasicMethodId]['prize'];
                        }
                    }

                    if ($fMaxPrize) {
                        $aWays[$k]['prize']        = min($aWayPrizes);
                        $aWays[$k]['max_multiple'] = intval($fMaxPrize / min($aWayPrizes));
                    } else {
                        $aWays[$k]['prize']        = implode(',', $aWayPrizes);
                        $aWays[$k]['max_multiple'] = 0;
                    }
                }
            }
        }
        return $aWayGroups;
    }
}
