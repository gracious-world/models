<?php

class UserUser extends User
{

    protected static $cacheUseParentClass = true;
    protected $isAdmin = false;

    public static $customMessages = [
        'username.required' => '请填写用户名',
        'username.alpha_num' => '用户名只能由大小写字母和数字组成',
        'username.between' => '用户名长度有误，请输入 :min - :max 位字符',
        'username.unique' => '用户名已被注册',
        'username.custom_first_character' => '首字符必须是英文字母',
        'nickname.required' => '请填写昵称',
        'nickname.between' => '用户昵称长度有误，请输入 :min - :max 位字符',
        'password.custom_password' => '密码由字母和数字组成, 且需同时包含字母和数字, 不允许连续三位相同',
//        'password.confirmed'              => '密码两次输入不一致',
        'fund_password.custom_password' => '资金密码由字母和数字组成, 且需同时包含字母和数字, 不允许连续三位相同',
        'fund_password.confirmed' => '资金密码两次输入不一致',
        // 'email.required'                  => '请填写邮箱地址',
    ];

    /**
     * 生成用户唯一标识
     * @return string
     */
    protected function getUserFlagAttribute()
    {
        $iUserId = $this->id;
        // $sRange = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $sRange = 'GqNbzewIF6kfx5mYaAnBEUvMuJyH8o9D7XcWt0hiQKOgRLdlSPpsC2jZ143rTV'; // 使用乱序字串
        if ($iUserId == 0) {
            return $sRange[0];
        }
        $iLength = strlen($sRange);
        $sStr = ''; // 最终生成的字串
        while ($iUserId > 0) {
            $sStr = $sRange[$iUserId % $iLength] . $sStr;
            $iUserId = floor($iUserId / $iLength);
        }
        return $sStr;
    }

    /**
     * [getRegistPrizeGroup 获取注册用户的奖金组信息]
     * @param  [String] $sPrizeGroup [链接开户特征码]
     * @param  &      $aPrizeGroup [奖金组数组的引用]
     * @param  &      $oPrizeGroup [奖金组对象的引用]
     * @return [type]              [description]
     */
    public static function getRegistPrizeGroup($sPrizeGroup = null, & $aPrizeGroup, & $oPrizeGroup, & $aPrizeSetQuota)
    {
        // pr($sPrizeGroup);exit;
        // 如果不是链接开户的注册，提供默认奖金组供注册用
        if (!$sPrizeGroup) {
            $aLotteries = &Lottery::getTitleList();
            $oExpirenceAgent = User::getExpirenceAgent();
            if (!$oExpirenceAgent) {
                return false;
            }
            $iPrizeGroup = $oExpirenceAgent->prize_group;
            // $aPrizeGroup = [];
            foreach ($aLotteries as $key => $value) {
                $aPrizeGroup[] = arrayToObject(['lottery_id' => $key, 'prize_group' => $iPrizeGroup]);
            }
            // 模拟oPrizeGroup对象
            $oPrizeGroup = $oExpirenceAgent;
            $oPrizeGroup->is_top = 0;
            $oPrizeGroup->is_agent = 0;
            $oPrizeGroup->user_id = $oExpirenceAgent->id;
        } else {
            $oPrizeGroup = UserRegisterLink::getRegisterLinkByPrizeKeyword($sPrizeGroup);
            // TODO 此处注册失败的具体条件后续可以改进
            if (!$oPrizeGroup) {
                return false;
            }
            $aPrizeSetQuota = objectToArray(json_decode($oPrizeGroup->agent_prize_set_quota));
            // 总代开户链接只能使用一次
            if ($oPrizeGroup->is_top && $oPrizeGroup->created_count) {
                return false;
                // return Redirect::back()->withInput()->with('error', '该链接已被使用。');
            }
            $aPrizeGroup = json_decode($oPrizeGroup->prize_group_sets);

        }
        return true;
    }


    /**
     * 直接开户
     * @author lucda
     * /app/controllers/user/UserUserController.php 里面的 doCreate 函数, 不考虑 新会员的 各配额之类的信息..直接 开新会员及新会员的帐号.. 涉及到 users , accounts 表
     * /app/controllers/AuthorityController.php 里面的 postSignup 函数
     */
    public static function createUserDirect(& $aData, $iParentId, $iRegisterLinkId, $bConfirmPasswd = false, & $oUser, & $iErrno, & $sErrmsg)
    {
        if($iRegisterLinkId > 0){
            $oPrizeGroup = PrizeGroup::find($aData['prize_group_id']);
            if (!$oPrizeGroup) {
                $iErrno = User::REGISTER_ERROR_PRIZE_GROUP_ERROR;
                return false;
            }
            $sPrizeGroup = $oPrizeGroup->name;
        }else{
            $sPrizeGroup = SysConfig::readValue('direct_user_default_prize_group');//注册时玩家讲金组,1850

        }
//        
        //奖金组等啥都没有管,,直接 开户
        if (!$data = static::compileUserDataDirect($aData, $sPrizeGroup, $iParentId, $iRegisterLinkId, $iErrNo)) {
            return false;
        }
        $oUser = new static($data);
        if (!$oUser->compilePasswordString(self::PASSWD_TYPE_LOGIN, $bConfirmPasswd)) {
            $iErrno = self::REGISTER_ERROR_PASSWD_WRONG;
            return false;
        }
        if (!$oUser->save()) {
            $iErrNo = self::REGISTER_ERROR_USER_SAVE_ERROR;
            $sErrmsg = $oUser->getValidationErrorString();
            return false;
        }
        if (!$oUser->createAccount()) {
            $iErrno = User::REGISTER_ERROR_CREATE_ACCOUNT_FAILED;
            return false;
        }

        //初始化用户奖金组
        if (!$bSucc = UserPrizeSet::initUserPrizeGroup($oUser, $sPrizeGroup)) {
            $iErrno = User::REGISTER_ERROR_CREATE_PRIZE_GROUP_SET;
            return false;
        }

        //建立初始用户等级
        if (!UserGrade::createUserGrade($oUser)) {
            return false;
        }

        //发放任务
        $aNeedAddEvents = [];

        $aEvents = Events::getValidEvents($iIsReceive = 0);//获取 非领取型的所有的任务
        foreach($aEvents as $aEvent){
           $aNeedAddEvents[ $aEvent['id'] ] = $aEvent;
        }
        if ($aNeedAddEvents) {
            EventUsers::addEventsToGoalsEventUser($oUser->id, $aNeedAddEvents);
        }
         //设置返点
        if(!empty($aData['fb_single']) && !empty($aData['fb_all'])){
            //获取当前用户返点
            $aData['fb_single'] <= 1 or $aData['fb_single'] /= 100;
            $aData['fb_all'] <= 1 or $aData['fb_all'] /= 100;
            $fUserSinglePercentValue = UserPercentSet::getPercentValueByUser($oUser->parent_id,UserPercentSet::$iFootBallLotteryId,PercentWay::$jcWays['single']);
                $fUserMultiPercentValue = UserPercentSet::getPercentValueByUser($oUser->parent_id,UserPercentSet::$iFootBallLotteryId,PercentWay::$jcWays['multi']);
                if($aData['fb_single'] > $fUserSinglePercentValue || $aData['fb_all'] > $fUserMultiPercentValue){
                    $iErrno = User::REGISTER_ERROR_CREATE_REBATE;
                    return false;
                }
                $aPercentSet = [
                    [
                        'percent_identity' => 'single',
                        'percent_value' => $aData['fb_single']
                    ],
                    [
                        'percent_identity' => 'multi',
                        'percent_value' => $aData['fb_all']
                    ]
                ];
                $bSucc = UserPercentSet::initUserPercentSet($oUser,$aPercentSet);
                if(!$bSucc){
                    $iErrno = User::REGISTER_ERROR_CREATE_REBATE;
                    return false;
                }
        }
        
        if($iRegisterLinkId > 0){
            $oRegisterLink = RegisterLink::find($iRegisterLinkId);
            if(!empty($oRegisterLink->percent_sets)){
                $aPercentSet = json_decode($oRegisterLink->percent_sets,true);
                $bSucc = UserPercentSet::initUserPercentSet($oUser,$aPercentSet);
                if(!$bSucc){
                    $iErrno = User::REGISTER_ERROR_CREATE_REBATE;
                    return false;
                }
            }
        }


        return true;
    }

    /**
     * @param $aData
     * @param $sPrizeGroup
     * @param $iParentId
     * @param null $iRegisterLinkId
     * @param $iErrno
     * @return array
     * @author lucda
     * 直接开户使用
     */
    public static function & compileUserDataDirect($aData, $sPrizeGroup, $iParentId, $iRegisterLinkId = null, & $iErrno)
    {
        $data = [
            'username' => $aData['username'],
            'password' => $aData['password'],
            'nickname' => isset($aData['nickname']) ? $aData['nickname'] : $aData['username'],
            'password_strength' => isset($aData['password_strength']) ? $aData['password_strength'] : 0,
//            'name' => empty($aData['name']) ? null : $aData['name'],
            'is_agent' => $aData['is_agent'],
            'email' => isset($aData['email']) ? $aData['email'] : null,
            'qq' => isset($aData['qq']) ? $aData['qq'] : null,
            'mobile' => isset($aData['mobile']) ? $aData['mobile'] : null,
            'skype' => isset($aData['skype']) ? $aData['skype'] : null,
            'prize_group' => $sPrizeGroup,
            'register_ip' => Tool::getClientIp(),
            'register_at' => ($sCurTime = Carbon::now()->toDateTimeString()),
            'activated_at' => $sCurTime,
        ];
        if ($iParentId) {
            $oAgent = static::find($iParentId);
            $data['parent_id'] = $oAgent->id;
            $data['forefather_ids'] = $oAgent->forefather_ids ? $oAgent->forefather_ids . ',' . $oAgent->id : $oAgent->id;
            $data['parent'] = $oAgent->username;
            $data['forefathers'] = $oAgent->forefathers ? $oAgent->forefathers . ',' . $oAgent->username : $oAgent->username;
            $data['is_tester'] = $oAgent->is_tester;
            $data['is_from_link'] = intval($iRegisterLinkId > 0);
        } else {
            $data['is_tester'] = $aData['is_tester'];
            $data['is_from_link'] = intval($iRegisterLinkId > 0);
            $data['parent'] = $data['forefather_ids'] = '';
        }
        !isset($aData['is_from_direct']) or $data['is_from_direct'] = $aData['is_from_direct'];
        return $data;
    }


    /**
     * 查找下级用户在线人数
     * @author lucky
     * @date 2016-11-24
     * @param $children_ids
     * @return mixed
     */
    static function getOnlineChildren($children_ids)
    {
        $r = Statistics::whereBetween("updated_at", [date("Y-m-d H:i:s", time() - 60 * 5), date("Y-m-d H:i:s", time())])
            ->whereIn("user_id", $children_ids)
            ->groupBy("user_id")
            ->get();
        return count($r);
    }

    static function getOnlineStatus($iUserId)
    {
        return Statistics::where("user_id", $iUserId)
            ->where("updated_at", ">", date("Y-m-d H:i:s", time() - 60 * 5))
            ->first();
    }

    /**
     * 所有队友
     * @author lucky
     * @create_at 2016-09-09
     * @param null $parent_id
     * @return mixed
     *
     */
    static function getTeamMate($parent_id = null)
    {
        return static::where("id", "!=", Session::get("user_id"))
            ->where("parent_id", "=", $parent_id)
            ->where("deleted_at", '=', null)
            ->get();

    }

    /**
     * 获取下级用户本月注册人数
     * @author lucky
     * @create_time 2016-10-09
     * @param $current_month_start
     * @param $children_ids
     *
     */
    static function getRegisterUsersCount($children_ids, $tTimeStart = null, $tTimeEnd = null)
    {
        $tTimeStart = $tTimeStart ? $tTimeStart : Date::getCurrentMonthStartDate();
        $tTimeEnd = $tTimeEnd ? $tTimeEnd : date("Y-m-d H:i:s");
        return static::whereIn("id", $children_ids)
            ->whereBetween("register_at", [$tTimeStart, $tTimeEnd])
            ->count();
    }

}