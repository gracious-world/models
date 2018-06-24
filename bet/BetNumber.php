<?php

/**
 * 投注号码情况类
 *
 * @author winter
 */
class BetNumber extends BaseModel {

    protected $table = 'bet_numbers';
    public static $resourceName = 'BetNumber';
    public static $amountAccuracy = 4;
    public static $htmlNumberColumns = [
        'multiple' => 2,
    ];
    public static $columnForList = [
        'lottery_id',
        'issue',
        'offset',
        'number',
        'multiple',
        'prize',
    ];
    protected $fillable = [
        'lottery_id',
        'issue',
        'offset',
        'len',
        'number',
        'multiple',
        'prize',
        'created_at',
        'updated_at',
    ];
    
    public static $rules = [
        'lottery_id' => 'required|integer',
        'issue' => 'required|max:15',
        'offset' => 'required|integer|min:0',
        'number' => 'required',
        'multiple' => 'required|numeric|min:0',
    ];
    
    public $orderColumns = [
        'id' => 'desc'
    ];

    /**
     * 下拉列表框字段配置
     * @var array
     */
    public static $htmlSelectColumns = [
        'lottery_id' => 'aLotteries',
    ];

    public static function init($iLotteryId, $sIssue, $iOffset, $iLen, $aNumbers){
        $sql = "insert ignore into bet_numbers (lottery_id,issue,offset,len,number,multiple,created_at)"
                . " values ";
        $p = "($iLotteryId, '$sIssue', '$iOffset','$iLen','%number%',0,now())";
        $subs = [];
        foreach($aNumbers as $sNumber){
            $subs[] = preg_replace('/%number%/i', $sNumber, $p);
        }
        $sql .= implode(",\n",$subs);
        return DB::insert($sql);
    }
    
    public static function updateNumber($iLotteryId, $sIssue, $iOffset, $iLen, $aNumbers, $fMultiple,$fPrize){
        $sql = "update bet_numbers set multiple = multiple + $fMultiple, prize = prize + $fPrize, updated_at = now() where lottery_id = $iLotteryId"
                . " and issue = '$sIssue' and offset = '$iOffset' and len = '$iLen' and number in ('" . implode("','", $aNumbers) . "')";
        return DB::update($sql);
    }
    
    private static function compileRiskCalelCatchKey(){
        return static::getCachePrefix() . 'risk-level' ;
    }
    
    public static function setRiskLevel($iLevel = 3, $iSeconds = 3600){
        $sKey = static::compileRiskCalelCatchKey();
        Cache::setDefaultDriver(static::$cacheDrivers[self::CACHE_LEVEL_FIRST]);
        return Cache::put($sKey, $iLevel, $iSeconds);
    }
    
}
