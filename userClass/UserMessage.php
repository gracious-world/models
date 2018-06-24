<?php

class UserMessage extends MsgUser {

    protected static $cacheUseParentClass = true;

    protected $fillable = [];

    /**
     *  未登录消息
     *  @var string
     */
    const INFO_MESSAGE = '尚未登录';

    /**
     *  帮助类别编号
     *  @var string
     */
    const HELP_ID = 17;

    public static $columnForList = [
        'msg_title',
        'type_id',
        'updated_at',
    ];

    public static function getUserUnreadMessagesNum() {
        $iUserId = Session::get('user_id');
        $iNum = static::where('receiver_id', '=', $iUserId)->whereNull('readed_at')->count();
        return $iNum;
    }

    /**
     * 根据标识跟登录用户名称封装URL
     *
     * @param string $sIdentifier 标识
     * @param string $sName       用户名称
     * @return string $sUrlJc 封装URL
     */
    public static function compileUrlData($sIdentifier, $sName) {
        $sUrlJc = '';
        $oLottery = Lottery::getByIdentifier($sIdentifier);
        $oPlat = ThirdPlat::find($oLottery['plat_id']);
        $identity = $oPlat->plat_identity;
        $iApiKey = $oPlat->key;
        if ($oLottery->id == 49) {
            $sGameType = 'basketball';
        } else {
            $sGameType = 'football';
        }

        $aParams = [
            'identity' => $identity,
            'username' => $sName,
            //'game_type' => $sGameType
        ];
        ksort($aParams);
        $sQueryStr = http_build_query($aParams);
        $jcToken = md5($sQueryStr . $iApiKey);

        $sUrlJc = $oPlat->iframe_url . '/games?skin=1#' . $sName . ';' . $identity . ';' . $jcToken;
        return $sUrlJc;
    }

}
