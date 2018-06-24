<?php

/**
 * 系列投注方式模型类
 */
class SeriesWay extends BaseModel {

    protected static $cacheLevel = self::CACHE_LEVEL_FIRST;

    const ERRNO_SERIES_WAY_MISSING      = -930;
    const ERRNO_SERIES_WAY_CLOSED       = -931;
    const ERRNO_SERIES_BET_NUMBER_WRONG = -932;

    protected $table            = 'series_ways';
    protected $softDelete       = false;
    protected $fillable         = [
        'series_id',
        'name',
        'short_name',
        'need_split',
        'way_maps',
        'series_way_method_id',
        'basic_way_id',
        'basic_methods',
        'series_methods',
        'way_function',
        'wn_function',
        'digital_count',
        'price',
        'offset',
        'position',
        'buy_length',
        'wn_length',
        'wn_count',
        'area_count',
        'area_config',
        'valid_nums',
        'rule',
        'all_count',
        'bet_note',
        'bonus_note'
    ];
    public static $resourceName = 'Series Way';

    /**
     * the columns for list page
     * @var array
     */
    public static $columnForList     = [
        'id',
        'name',
        'short_name',
        'need_split',
        'basic_way_id',
//        'basic_methods',
        'series_methods',
        'digital_count',
//        'price',
        'offset',
        'buy_length',
        'position',
        'wn_length',
        'wn_count',
        'area_count',
        'area_config',
        'valid_nums',
    ];
    public static $htmlSelectColumns = [
        'series_id'            => 'aSeries',
        'basic_way_id'         => 'aBasicWays',
//        'basic_methods' => 'aBasicMethods',
        'series_methods'       => 'aSeriesMethods',
        'series_way_method_id' => 'aSeriesWayMethods',
    ];
    public $orderColumns             = [
        'id' => 'asc'
    ];
    public static $mainParamColumn   = 'series_id';
    public static $titleColumn       = 'name';
    public static $rules             = [
        'series_id'            => 'required|integer',
        'series_way_method_id' => 'required|integer',
//        'basic_methods' => 'required|max:200',
        'name'                 => 'required|max:30',
        'short_name'           => 'required|max:30',
        'way_maps'             => 'max:1024',
        'need_split'           => 'required|integer|in:0,1',
        'digital_count'        => 'required|numeric',
        'price'                => 'required|numeric',
//        'shape' => 'max:100',
        'offset'               => 'max:100',
        'position'             => 'max:100',
        'buy_length'           => 'required|numeric',
        'wn_length'            => 'required|numeric',
        'wn_count'             => 'required|numeric',
        'area_count'           => 'integer',
        'area_config'          => 'max:20',
        'valid_nums'           => 'max:50',
        'rule'                 => 'max:50',
        'all_count'            => 'max:100',
    ];
    public $WinningNumber;

    protected function afterSave($oSavedModel) {
        WayGroup::deleteLotteryCache($oSavedModel->series_id);
        return parent::afterSave($oSavedModel);
    }

    protected function afterUpdate() {
        WayGroup::deleteLotteryCache($this->series_id);
        return parent::afterUpdate();
    }

    protected function afterDelete($oDeletedModel) {
        WayGroup::deleteLotteryCache($oDeletedModel->series_id);
        return parent::afterDelete($oDeletedModel);
    }

    protected function beforeValidate() {
        parent::beforeValidate();
        $oBasicWay          = BasicWay::find($this->basic_way_id);
        $oWayMethod         = SeriesWayMethod::find($this->series_way_method_id);
        $this->basic_way_id = $oWayMethod->basic_way_id;
        $this->need_split or $this->need_split   = 0;
//        if (!strlen($this->shape)){
        $aSeriesMethodId    = explode(',', $this->series_methods);
        $aOffsets           = $aAllCount          = $aBasicMethods      = $aPositions         = [];
        foreach ($aSeriesMethodId as $iSeriesMethodId) {
            $oSeriesMethod = SeriesMethod::find($iSeriesMethodId);
            if (empty($iSeriesMethodId)) {
                return false;
            }
            $oBasicMethod    = BasicMethod::find($oSeriesMethod->basic_method_id);
            $aBasicMethods[] = $oSeriesMethod->basic_method_id;
//            $aShapes[] = $oBasicMethod->shape;
            $aPositions[]    = $oSeriesMethod->position;
            $aOffsets[]      = $oSeriesMethod->offset;
            $aAllCount[]     = $oBasicMethod->all_count;
        }
//        pr($aOffsets);
//        pr($aPositions);
//        exit;
        $this->way_function  = $oBasicWay->function;
        $this->wn_function   = $oBasicMethod->wn_function;
        $this->basic_methods = implode(',', $aBasicMethods);
        $this->all_count     = implode(',', $aAllCount);
        $this->offset        = $aOffsets ? implode(',', $aOffsets) : null;
        $this->position      = $aPositions ? implode(',', $aPositions) : null;
        $this->price or $this->price         = $oBasicMethod->price;
        $this->buy_length or $this->buy_length    = $oBasicMethod->buy_length;
        $this->wn_length or $this->wn_length     = $oBasicMethod->wn_length;
        $this->wn_count or $this->wn_count      = $oBasicMethod->wn_count;
        strlen($this->valid_nums) or $this->valid_nums    = $oBasicMethod->valid_nums;
        strlen($this->rule) or $this->rule          = $oBasicMethod->rule;
        $this->area_count or $this->area_count    = null;
        unset($aSeriesMethodId, $iSeriesMethodId);
        return true;
    }

    /**
     * 从基础方式、基础玩法、方式与玩法的关联、系列与玩法的关联等四个模型，进行数据转换生成系列方式数据并保存
     *
     * 本方法主要用于本对象的数据初始化，一旦数据整理完成后，请慎用
     *
     * @param int $iSeriesId
     * @return true | validationErrors->toArray
     */
    function makeSeriesWayData($iSeriesId = 1) {
        $oSeriesMethods = SeriesMethod::where('series_id', '=', $iSeriesId)->get();
        $oSeries        = Series::find($iSeriesId);
        $aBasicMethods  = $aSeriesMethods = [];
        foreach ($oSeriesMethods as $oSeriesMethod) {
            $aSeriesMethods[$oSeriesMethod->id] = $oSeriesMethod->getAttributes();
        }
        $oBasicMethods = BasicMethod::where('series_id', '=', $iSeriesId)->get();
        foreach ($oBasicMethods as $oMethod) {
            $aBasicMethods[$oMethod->id] = $oMethod->getAttributes();
        }
        $oWayMethods = SeriesWayMethod::where('series_id', '=', $iSeriesId)->get();
        $oBasicWays  = BasicWay::all();
        $aBasicWays  = [];
        foreach ($oBasicWays as $oBasicWay) {
            $aBasicWays[$oBasicWay->id] = $oBasicWay->getAttributes();
        }

//        pr($aBasicMethods);
//        exit;
        foreach ($oWayMethods as $oWayMethod) {
//            if (!$oWayMethod->single){
//                continue;
//            }
//            pr($oWayMethod->getAttributes());
            $aSeriesMethodId = explode(',', $oWayMethod->series_methods);
            if ($oWayMethod->single) {
//                continue;
//                pr($aSeriesMethodId);
                $aAllCount      = $aOffset        = $aDigitalCount  = $aBasicMethodID = $aPosition      = [];
                foreach ($aSeriesMethodId as $iSeriesMethodId) {
//                    pr($aBasicMethods);
//                    exit;
                    $aSeriesMethod    = & $aSeriesMethods[$iSeriesMethodId];
//                    pr($aSeriesMethod);
                    $iBasicMethodId   = $aSeriesMethod['basic_method_id'];
//                    $aShape[] = $aBasicMethods[$iBasicMethodId]['shape'];
                    $aAllCount[]      = $aBasicMethods[$iBasicMethodId]['all_count'];
                    $aPosition[]      = $aSeriesMethod['position'];
                    $aOffset[]        = $aSeriesMethod['offset'];
                    $aDigitalCount[]  = $aBasicMethods[$iBasicMethodId]['digital_count'];
                    $aBasicMethodID[] = $iBasicMethodId;
                }

                $aSeriesWays[] = [
                    'series_id'            => $oWayMethod->series_id,
                    'basic_way_id'         => $oWayMethod->basic_way_id,
                    'series_way_method_id' => $oWayMethod->id,
//                            'name' => $aName[$i] . $aBasicWays[$oWayMethod->basic_way_id]['name'],
                    'name'                 => $oWayMethod->name,
                    'short_name'           => $aBasicMethods[$iBasicMethodId]['name'],
//                    'digital_count' => $aBasicMethods[$iBasicMethodId]['digital_count'],
                    'digital_count'        => max($aDigitalCount),
                    'price'                => $aBasicMethods[$iBasicMethodId]['price'],
//                    'shape'          => implode(',',$aShape),
                    'offset'               => $aOffset ? implode(',', $aOffset) : null,
                    'position'             => $aPosition ? implode(',', $aPosition) : null,
//                    'offset' => $aSeriesMethod['offset'],
                    'buy_length'           => $aBasicMethods[$iBasicMethodId]['buy_length'],
                    'wn_length'            => $aBasicMethods[$iBasicMethodId]['wn_length'],
                    'wn_count'             => $aBasicMethods[$iBasicMethodId]['wn_count'],
                    'valid_nums'           => $aBasicMethods[$iBasicMethodId]['valid_nums'],
                    'rule'                 => $aBasicMethods[$iBasicMethodId]['rule'],
                    'all_count'            => implode(',', $aAllCount),
//                    'all_count' =>  $aBasicMethods[$iBasicMethodId]['all_count'],
                    'basic_methods'        => implode(',', $aBasicMethodID),
                    'series_methods'       => $oWayMethod->series_methods,
                ];
//                pr($aShape);
//                pr($aAllCount);
//                pr($aName);
//                break;
            } else {
                foreach ($aSeriesMethodId as $iSeriesMethodId) {
                    $aSeriesMethod  = & $aSeriesMethods[$iSeriesMethodId];
//                    pr($aSeriesMethod);
                    $iBasicMethodId = $aSeriesMethod['basic_method_id'];
//                    continue;
                    $aSeriesWays[]  = [
                        'series_id'            => $oWayMethod->series_id,
                        'basic_way_id'         => $oWayMethod->basic_way_id,
                        'series_way_method_id' => $oWayMethod->id,
//                            'name' => $aName[$i] . $aBasicWays[$oWayMethod->basic_way_id]['name'],
                        'name'                 => $aSeriesMethod['name'] . $aBasicWays[$oWayMethod->basic_way_id]['name'],
//                        'name' => $oWayMethod->name,
//                        'short_name' => $aBasicWays[$oWayMethod->basic_way_id]['name'],
                        'short_name'           => $aBasicMethods[$iBasicMethodId]['name'],
                        'digital_count'        => $aBasicMethods[$iBasicMethodId]['digital_count'],
                        'price'                => $aBasicMethods[$iBasicMethodId]['price'],
//                        'shape' => $aBasicMethods[$iBasicMethodId]['shape'],
                        'offset'               => $aSeriesMethod['offset'],
                        'position'             => $aSeriesMethod['position'],
                        'buy_length'           => $aBasicMethods[$iBasicMethodId]['buy_length'],
                        'wn_length'            => $aBasicMethods[$iBasicMethodId]['wn_length'],
                        'wn_count'             => $aBasicMethods[$iBasicMethodId]['wn_count'],
                        'valid_nums'           => $aBasicMethods[$iBasicMethodId]['valid_nums'],
                        'rule'                 => $aBasicMethods[$iBasicMethodId]['rule'],
                        'all_count'            => $aBasicMethods[$iBasicMethodId]['all_count'],
                        'basic_methods'        => $iBasicMethodId,
                        'series_methods'       => $iSeriesMethodId,
                    ];
                }
            }
        }
//        pr($aSeriesWays);
//        exit;
        $bSucc = true;
        foreach ($aSeriesWays as $aSeriesWay) {
            $oSeriesWay  = new SeriesWay($aSeriesWay);
            $aConditions = [
                'series_id'      => ['=', $aSeriesWay['series_id']],
                'basic_way_id'   => ['=', $aSeriesWay['basic_way_id']],
                'basic_methods'  => ['=', $aSeriesWay['basic_methods']],
                'series_methods' => ['=', $aSeriesWay['series_methods']],
//                'offset' => ['=', $aSeriesWay['offset']],
                'position'       => ['=', $aSeriesWay['position']],
            ];
            if ($aSeriesWay['name'] == '万千十位直选单式') {
                pr($aSeriesWay['name']);
                pr($aConditions);
//                exit;
            }
            if ($oSeriesWay->doWhere($aConditions)->get(['id'])->first()) {
                echo 'pass:' . $oSeriesWay->name . '<br>';
                continue;
//                $oSeriesWay->exists = true;
            }
            if (!$bSucc = $oSeriesWay->save()) {
                break;
            }
        }
        return $bSucc ? $bSucc : var_export($oSeriesWay->validationErrors->toArray(), true);
    }

    public function compileBetNumberNew($sBetNumber) {
//        pr($this->toArray());
        $oBasicWay    = BasicWay::find($this->basic_way_id);
        $oBasicMethod = BasicMethod::find($this->basic_methods);
//        pr($oBasicWay->toArray());
//        pr($oBasicMethod->toArray());
        $sClass       = 'Way' . ucfirst(Str::camel($oBasicWay->function)) . ucfirst(Str::camel($oBasicMethod->wn_function));
//        pr($sClass);
        return $sClass::compileBetNumber($sBetNumber);
//        exit;
    }

    public function count(& $aOrder) {
        $oBasicWay       = BasicWay::find($this->basic_way_id);
        $aBasicMethodIds = explode(',', $this->basic_methods);
        if (count($aBasicMethodIds) == 1) {
            $iBasicMethodId = $aBasicMethodIds[0];
        } else {
            $iBasicMethodId   = 0;
            $iMaxDigitalCount = 0;
            foreach ($aBasicMethodIds as $iTmpBasicMethodId) {
                $oTmpBasicMethod = BasicMethod::find($iTmpBasicMethodId);
                if ($oTmpBasicMethod->digital_count > $iMaxDigitalCount) {
                    $iMaxDigitalCount = $oTmpBasicMethod->digital_count;
                    $iBasicMethodId   = $iTmpBasicMethodId;
                }
            }
            unset($iMaxDigitalCount);
        }
        $oBasicMethod = BasicMethod::find($iBasicMethodId);
        unset($aBasicMethodIds);

//        pr($oBasicWay->toArray());
//        pr($oBasicMethod->toArray());
        $sClass = $this->getWayClass($oBasicWay, $oBasicMethod);
//        pr($sClass);
//        pr($aOrder['position']);
        if ($iCount = $sClass::count($aOrder['bet_number'], $sDisplayBetNumber, $oBasicMethod, $aOrder['position'])) {
            $sDisplayBetNumber == '' or $aOrder['display_bet_number'] = $sDisplayBetNumber;
        }
        return $iCount;
    }

    public static function getWayClass($oBasicWay, $oBasicMethod) {
        return 'Way' . ucfirst(Str::camel($oBasicWay->function)) . ucfirst(Str::camel($oBasicMethod->wn_function));
    }

//    public function count3D(& $aOrder){
//        return $this->countNew($aOrder);
//    }
//
//    public function countK3(& $aOrder) {
//        return $this->countNew($aOrder);
//    }
//
//    public function countKENO(& $aOrder) {
//        return $this->countNew($aOrder);
//    }
//
//    public function countSSC(& $aOrder) {
////        pr($aOrder);
////        exit;
//        return $this->countNew($aOrder);
//    }
//
//    public function countPK10(& $aOrder){
//        return $this->countNew($aOrder);
//    }
//
//    public function countL115(& $aOrder){
//        return $this->countNew($aOrder);
//    }

    /**
     * 整理投注号码，将不必要的分隔符及占位符删除
     *
     * @param string $sBetNumber
     * @return string
     */
    public function compileBetNumber($sBetNumber) {
        if (in_array($this->series_id, Config::get('way_classed'))) {
            return $this->compileBetNumberNew($sBetNumber);
        }

        $sSplitChar = Config::get('bet.split_char') or $sSplitChar = '|';
        if ($this->area_count == 1) {
            $sBetNumber = str_replace(str_split(Config::get('bet.possible_split_chars')), '', $sBetNumber);
        } else {
            if ($this->area_count > -1) {
                $aParts = explode($sSplitChar, $sBetNumber);
                if (count($aParts) > $this->area_count) {
//                    if (count($aParts) > $this->digital_count){
                    foreach ($aParts as $i => $sNumber) {
                        if (!preg_match('/^\d*$/', $sNumber)) {
                            unset($aParts[$i]);
                        } else {
                            $aParts = DigitalNumber::getCombinNumber($sNumber);
                        }
                    }
//                    }
                    $sBetNumber = implode($sSplitChar, $aParts);
                }
                if (count($aParts) != $this->area_count) {
                    $sBetNumber = '';
                }
            }
        }
        return $sBetNumber;
    }

    /**
     * 计算注数
     *
     * @param array $aOrder
     * @return int
     */
    public function countOld(& $aOrder) {
        $oBasicWay = BasicWay::find($this->basic_way_id);
        if ($this->area_count == 1) {
            $aOrder['bet_number'] = str_replace('|', '', $aOrder['bet_number']);
        }
        if ($this->basic_way_id == 7) {  // 直选组合
            $sSplitChar  = Config::get('bet.split_char') or $sSplitChar  = '|';
            $aNumbers    = explode($sSplitChar, $aOrder['bet_number']);
            $iCount      = $this->digital_count;
            $aNetNumbers = [];
            foreach ($aNumbers as $sNumber) {
                $aNums      = array_unique(str_split($sNumber));
                $aNumbers[] = implode($aNums);
                $iCount *= count($aNums);
            }
            $sNumber = implode($sSplitChar, $aNumbers);
            return $iCount;
        }
        switch ($oBasicWay->function) {
            case 'BigSmallOddEven':
                $aOrder['display_bet_number'] = $oBasicWay->getDisplayBetNumberOfBsde($aOrder['bet_number']);
                break;
            case 'SpecialConstituted':
                $aOrder['display_bet_number'] = $oBasicWay->getDisplayBetNumberOfSpecial($aOrder['bet_number']);
                break;
            case 'FunSeparatedConstituted':
                $bInterest                    = true;
            case 'SectionalizedSeparatedConstituted':
                isset($bInterest) or $bInterest                    = false;
                $aOrder['display_bet_number'] = $oBasicWay->getDisplayBetNumberOfAreaOrInterest($bInterest, $this->valid_nums, $aOrder['bet_number']);
                break;
            case 'LottoConstituted':
                $oBasicMethod                 = BasicMethod::find($this->basic_methods);
                if ($oBasicMethod->wn_function == 'LottoOddEven') {
                    $aOrder['display_bet_number'] = $oBasicWay->getDisplayBetNumberOfLottoOddEven($aOrder['bet_number']);
                }
//            default:
//                $aOrder['display_bet_number'] = $aOrder['bet_number']
        }
//        pr($aOrder);
//        exit;
        return $oBasicWay->count($aOrder['bet_number'], $this);
    }

    /**
     * 获取中奖号码
     *
     * @param string $sBaseWinningNumber
     * @return string|array
     */
//    public function getWinningNumber($sBaseWinningNumber){
//        $oBasicWay = BasicWay::find($this->basic_way_id);
//        $sWinningNumber = substr($sBaseWinningNumber, intval($this->offset), $this->digital_count);
//        return $oBasicWay->getWinningNumber($sWinningNumber);
//    }

    public function & getWinningNumber(& $aWnNumberOfMethods) {
//        pr($aWnNumberOfMethods);
//        exit;
        $aWnNumbers = [];
        foreach ($this->series_method_ids as $iSeriesMethodId) {
            if ($aWnNumberOfMethods[$iSeriesMethodId] === false) {
                continue;
            }
            $aWnNumbers[$iSeriesMethodId] = $aWnNumberOfMethods[$iSeriesMethodId];
        }
        $this->setWinningNumber($aWnNumbers);
        return $aWnNumbers;
//        $oBasicWay      = BasicWay::find($this->basic_way_id);
//        return $oBasicWay->getWinningNumber($sWinningNumber);
    }

    public function checkPrize($sBetNumber) {
        $oBasicWay = BasicWay::find($this->basic_way_id);
        switch ($this->series_id) {
//            case 4:
//                return $oBasicWay->checkPrizeK3($this,$sBetNumber);
//                break;
            case 1:
            case 3:
            case 2:
            case 5:
            case 4:
            case 7:
            case 8:
            case 9:
            case 10:
                return $this->checkPrizeSSC($sBetNumber);
                break;
            default:
                return $oBasicWay->checkPrizeSSC($sBetNumber);
        }
    }

    public function checkPrizeSSC($sBetNumber) {
        $sWayClass = $this->getWayClassNew();
        return $aPrized   = $sWayClass::checkPrize($this->WinningNumber, $sBetNumber, $this->digital_count);
//        pr($this->WinningNumber);
//        exit;
//        die($sWayClass);
//        list($iWayId, $sWnNumber) = each($this->WinningNumber);
//        pr($sBetNumber);
//        exit;
//        list($iSeriesMethodId, $sWnNumber) = each($this->WinningNumber);
//        $oSeriesMethod = SeriesMethod::find($iSeriesMethodId);
//        pr($oSeriesMethod->toArray());
//        $oBasicMethod  = BasicMethod::find($oSeriesMethod->basic_method_id);
//        exit;
//        return $aPrized = $sWayClass::checkPrize($this, $sBetNumber);
//        return $aPrized   = $sWayClass::checkPrize($this->WinningNumber, $sBetNumber, $this->digital_count);
//        pr($aPrized);
//        exit;
    }

    private function getWayClassNew() {
        return 'Way' . ucfirst(Str::camel($this->way_function)) . ucfirst(Str::camel($this->wn_function));
    }

//    public function checkPrizeK3($sBetNumber){
//        $oBasicWay = BasicWay::find($this->basic_way_id);
//        $oBasicMethod = BasicMethod::find($this->basic_methods);
////        pr($oBasicWay->toArray());
////        pr($oBasicMethod->toArray());
////        $sWayClass = 'Way' . ucfirst(Str::camel($oBasicWay->function)) . ucfirst(Str::camel($oBasicMethod->wn_function));
////        $sWayClass::checkPrize();
//
////        if ($iCount = $sClass::count($aOrder['bet_number'], $sDisplayBetNumber)){
////            !$sDisplayBetNumber or $aOrder['display_bet_number'] = $sDisplayBetNumber;
////        }
////        return $iCount;
//    }

    public function setWinningNumber($aWinningNumber) {
        $this->WinningNumber = count($aWinningNumber) > 0 ? $aWinningNumber : false;
    }

    protected function getBasicMethodIdsAttribute() {
        return explode(',', $this->attributes['basic_methods']);
    }

    protected function getSeriesMethodIdsAttribute() {
        return explode(',', $this->attributes['series_methods']);
    }

    protected function getTotalNumberCountAttribute() {
        $aAllCount = explode(',', $this->all_count);
        return $this->basic_way_id == BasicWay::WAY_MULTI_SEQUENCING ?
            max($aAllCount) * $this->digital_count :
            array_sum(explode(',', $this->all_count));
    }

}
