<?php
class SeriesMethod extends BaseModel {

    public static $resourceName = 'Series Method';

    protected $table = 'series_methods';

    public static $columnForList = [
        'id',
        'series_id',
        'name',
        'basic_method_id',
        'is_adjacent',
        'offset',
        'position',
        'hidden',
        'open',
    ];

    protected $fillable = [
        'series_id',
        'name',
        'basic_method_id',
        'is_adjacent',
        'offset',
        'position',
        'hidden',
        'open',
        'created_at',
    ];

    public static $htmlSelectColumns = [
        'series_id' => 'aSeries',
        'basic_method_id' => 'aBasicMethods',
    ];

    public $orderColumns = [
        'id' => 'asc'
    ];

    /**
     * the main param for index page
     * @var string
     */
    public static $mainParamColumn = 'series_id';

    /**
     * The rules to be applied to the data.
     *
     * @var array
     */
    public static $rules = [
        'series_id' => 'required|integer',
        'name'      => 'required|max:30',
        'basic_method_id' => 'required|integer',
        'is_adjacent' => 'required|in:0,1',
        'offset' => 'numeric',
        'position' => 'required|max:100',
        'hidden' => 'in:0,1',
        'open' => 'in:0,1'
    ];

    public static $customMessages = [];
    public static $titleColumn = 'name';
//    public $timestamps = false;

    /**
     * 检查是否存在相同的游戏名称
     *
     * @return boolean
     */
    private function _existName(){

    }

    /**
     * Write Cache
     */

    public function makeSeriesMethodCache(){
        // todo
    }

    public function getWinningNumber($sFullWinningNumber){
//        if ($this->series_id == 4){
//            return $this->getWinningNumberK3($sFullWinningNumber);
//        }
        $oBasicMethod   = BasicMethod::find($this->basic_method_id);
//        Log::info($this->name);
//        Log::info(var_export($this->attributes,1));
        $sWinningNumber = $oBasicMethod->getWnNumber($sFullWinningNumber,$this->is_adjacent,intval($this->offset),$this->position);
//        $sWinningNumber = substr($sFullWinningNumber,intval($this->offset),$oBasicMethod->digital_count);
//        Log::info(var_export($this->attributes,1));
//        Log::info($sFullWinningNumber);
//        Log::info($sWinningNumber);
//        Log::info(var_export($oBasicMethod->attributes,1));
        $a = $oBasicMethod->getWinningNumber($sWinningNumber);
//        Log::info('wn_number: ' . var_export($a,1));
        return $oBasicMethod->getWinningNumber($sWinningNumber);
    }

    public function getWinningNumberK3($sFullWinningNumber){
        
    }
    
    protected function beforeValidate() {
        if (!isset($this->offset) && !isset($this->position)){
            return false;
        }
//        if ($this->position != ''){
//            $oBasicMethod = BasicMethod::find($this->basic_method_id);
//            $a = str_split($this->position);
//            $span = max($a) - min($a);
//            $this->is_adjacent = intval($span == $oBasicMethod->buy_length - 1);
//        }
        if ($this->offset == ''){
            $this->offset = substr($this->position,0,1);
        }
//        !is_null($this->open) or $this->open = 1;
        return parent::beforeValidate();
    }
    
    public static function getAvailableMethods($iSeriesId){
        return static::where('series_id','=',$iSeriesId)->where('open','=',1)->get();
    }
}
