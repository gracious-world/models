<?php

/**
 * 错误代码
 *
 * @author winter
 */
class SysError {

    const SERVICE_DOWN                      = 1000;
    const NET_ABNORMAL                      = 1100;
    const MISSING_PACKET                    = 2000;
    const MISSING_ACTION                    = 2001;
    const PACKET_ERROR                      = 2002;
    const ACTION_NOT_DEFINED                = 2003;
    const METHOD_NEED_GET                   = 2010;
    const METHOD_NEED_POST                  = 2011;
    const MISSING_TOKEN                     = 2012;
    const MISSING_PARAMS                    = 2013;
    const MISSING_SIGN                      = 2014;
    const SIGN_ERROR                        = 2015;
    const TERMINAL_ERROR                    = 2016;
    const TIME_SO_EARLY                     = 2100;
    const USER_NOT_EXISTS                   = 3000;
    const PWD_ERROR                         = 3001;
    const LOGIN_FAILED                      = 3002;
    const USER_BE_BLOCKED                   = 3003;
    const NON_LOGINED                       = 3004;
    const SAME_TO_FUND_PWD                  = 3005;
    const SAME_TO_LOGIN_PWD                 = 3006;
    const CHANGE_PWD_FAILED                 = 3007;
    const CHANGE_FUND_PWD_FAILED            = 3008;
    const FUND_PWD_ERROR                    = 3009;
    const FUND_PWD_NOT_SET                  = 3010;
    const SET_FUND_PWD_FAILED               = 3035;
    const FUND_PWD_ALREADY_SETED            = 3036;
    const USER_BE_BLOCKED_LOGIN             = 3030;
    const USER_BE_BLOCKED_BUY               = 3031;
    const USER_BE_BLOCKED_FUND              = 3032;
    const USER_BE_BLOCKED_SAFE              = 3033;
    const USER_BE_BLOCKED_PWD_ERROR         = 3034;
    const PROJECT_NOT_EXISTS                = 4000;
    const PROJECT_NOT_YOURS                 = 4001;
    const PROJECT_DROP_FAILED               = 4010;
    const TRACE_NOT_EXISTS                  = 4020;
    const TRACE_NOT_YOURS                   = 4021;
    const TRACE_DETAIL_CANCEL_FAILED        = 4030;
    const TRACE_STOP_FAILED                 = 4040;
    const LOTTERY_NOT_EXISTS                = 4050;
    const LOTTERY_NOT_AVAILABLE             = 4051;
    const LOTTERY_MUST_BE_NOT_INSTANT       = 4052;
    const LOTTERY_MUST_BE_INSTANT           = 4053;
    const PRIZE_GROUP_ERROR                 = 4054;
    const ISSUE_EXPIRED                     = 4055;
    const BET_DATA_ERROR                    = 4056;
    const BET_PRICE_ERROR                   = 4057;
    const BET_NUMBER_ERROR                  = 4058;
    const BET_COUNT_ERROR                   = 4059;
    const BET_PRIZE_OVERFLOW                = 4060;
    const BET_LOW_AMOUNT                    = 4061;
    const BET_LOW_BALANCE                   = 4062;
    const BET_PARTLY_CREATED                = 4070;
    const BET_FAILED                        = 4071;
    const USER_CANT_BET                     = 4100;
    const AGENT_CANT_BET                    = 4101;
    //zero updated
    const GAME_TYPE_ERROR                   = 4102;
    const MISSING_THIRD_PLAT                = 4103;
    const LOTTERY_INIT_ERROR                = 4104;

    const ACCOUNT_LOCK_FAILED               = 5000;
    const TRANSACTION_NOT_EXISTS            = 5001;
    const TRANSACTION_NOT_YOURS             = 5002;
    const MISSING_USER_BANKCARD             = 5010;
    const NEW_USER_BANKCARD                 = 5011;
    const WITHDRAW_TOO_MANY_TIMES           = 5012;
    const WITHDRAW_AMOUNT_FORMAT_ERROR      = 5013;
    const WITHDRAW_OUT_OF_RANGE             = 5014;
    const WITHDRAW_OVERFLOW                 = 5015;
    const WITHDRAW_FAILED                   = 5016;

    /**
     * 以下是充值相关错误码
     */
    const DEPOSIT_PLATFORM_ERROR            = 5017;
    const DEPOSIT_AMOUNT_FORMAT_ERROR       = 5018;
    const DEPOSIT_PAYMENT_ACCOUNT_ERROR     = 5019;
    const USER_DEPOSIT_SAVE_FAILED          = 5020;
    const DEPOSIT_PLATFORM_NOT_AVAILABLE    = 5021;
    const DEPOSIT_OUT_OF_RANGE              = 5022;
    #zero add
    const DEPOSIT_ORDER_NOT_EXISTS          = 5023;
    const DEPOSIT_WAITING_LOAD              = 5024;
    const DEPOSIT_SUCCESS                   = 5025;
    const DEPOSIT_ADD_FAILED                = 5026;
    const DEPOSIT_EXCEPTION                 = 5027;
    const DEPOSIT_CS_PROCESSING             = 5028;
    const DEPOSIT_PLATFORM_QUERY_FAILED     = 5029;
    const DEPOSIT_PLATFORM_ORDER_NOT_EXISTS = 5030;
    const DEPOSIT_ORDER_UNPAY               = 5031;
    const DEPOSIT_AMOUNT_ERROR              = 5032;
    const DEPOSIT_ADD_STATUS_SETTING_FAILED = 5033;
    const DEPOSIT_FAILED                    = 5034;
    const DEPOSIT_GET_QR_CODE_FAILED        = 5035;
    #绑卡相关 zero add
    const BIND_CARD_FIRST                   = 5035; //玩家未绑定银行卡
    const SAFE_RESET_FUND_PASSWORD          = 5036;
    const ACCOUNT_LOCKED_FOR_WRONG_FUND_PWD = 5037;
    const WRONG_FUND_PWD_TRY_TIMES          = 5038;
    const LOCK_BANK_CARD_FAILED             = 5039;
    const WRONG_BANK_CARD_TRY_TIMES         = 5040;
    const USER_BANK_CARD_NOT_EXISTS         = 5041;
    const USER_BANK_CARD_OUT_OF_RANGE       = 5042;
    const USER_CHECKED_TOKEN_INVALID        = 5043;
    const USER_BANK_CARD_ALREDY_LOCKED      = 5044;
    const USER_BIND_CARD_FAILED             = 5045;
    const USER_BANK_CARD_EXISTS             = 5046;
    const USER_BANK_CARD_DELETE_FAILED      = 5047;
    const CHECK_TYPE_ERROR                  = 5048;
    const DISTRICT_NOT_EXISTS               = 5049;
    const DISTRICT_RELATION_ERROR           = 5050;
    const PAYMENT_TYPE_ERROR                = 5051;
    const WITHDRAW_NOT_ALLOWED              = 5052;
    const GET_QR_FAILED                     = 5053;

    /**
     * 以下是返点相关错误码
     */
    const CANNOT_RAISE_COMMISSION        = 6021;
    const CANNOT_LOWER_COMMISSION        = 6022;
    const COMMISSION_OVERFLOW            = 6023;
    const COMMISSION_OVERFLOW_UP_LEVEL   = 6024;
    const COMMISSION_OVERFLOW_DOWN_LEVEL = 6025;

    /**
     * 以下是CMS相关错误码
     */
    const ARTICLE_NOT_EXISTS = 9000;
    const ARTICLE_CLOSED     = 9001;

//    const PROJECT_DROP_FAILED = 4030;

    static $messages = [
        self::SERVICE_DOWN                      => '_apierror.service-down',
        self::NET_ABNORMAL                      => '_system.connection-error',
        self::MISSING_PACKET                    => '_apierror.missing-packet',
        self::MISSING_ACTION                    => '_apierror.missing-action',
        self::PACKET_ERROR                      => '_apierror.packet-error',
        self::ACTION_NOT_DEFINED                => '_apierror.action-not-defined',
        self::METHOD_NEED_GET                   => '_apierror.must-be-get',
        self::METHOD_NEED_POST                  => '_apierror.must-be-post',
        self::MISSING_TOKEN                     => '_apierror.missing-token',
        self::MISSING_PARAMS                    => '_apierror.missing-params',
        self::MISSING_SIGN                      => '_apierror.missing-signature',
        self::SIGN_ERROR                        => '_apierror.signature-error',
        self::TERMINAL_ERROR                    => '_apierror.invalid-terminal',
        self::TIME_SO_EARLY                     => '_apierror.time-so-early',
        self::USER_NOT_EXISTS                   => '_user.missing-user',
        self::PWD_ERROR                         => '_user.password-error',
        self::LOGIN_FAILED                      => '_basic.login-fail-wrong',
        self::USER_BE_BLOCKED                   => '_user.user-was-blocked',
        self::NON_LOGINED                       => '_basic.need-login',
        self::SAME_TO_FUND_PWD                  => '_user.same-with-fund-password',
        self::SAME_TO_LOGIN_PWD                 => '_user.same-with-password',
        self::CHANGE_PWD_FAILED                 => '_user.update-password-fail',
        self::CHANGE_FUND_PWD_FAILED            => '_user.update-fund-password-fail',
        self::FUND_PWD_ERROR                    => '_user.fund_password_error',
        self::SET_FUND_PWD_FAILED               => '_user.fund_password_set_failed',
        self::FUND_PWD_ALREADY_SETED            => '_user.fund_password_already_seted',
        self::USER_BE_BLOCKED_LOGIN             => '_basic.locked-contact-custom-service-to-unlocked',
        self::USER_BE_BLOCKED_BUY               => '_user.block-bet',
        self::USER_BE_BLOCKED_FUND              => '_user.block-fund',
        self::USER_BE_BLOCKED_SAFE              => '_basic.safe-locked-contact-custom-service-to-unlocked',
        self::USER_BE_BLOCKED_PWD_ERROR         => '_basic.locked-with-passeord-error-contact-custom-service-to-unlocked',
        self::PROJECT_NOT_EXISTS                => '_project.missing',
        self::PROJECT_NOT_YOURS                 => '_project.not-yours',
        self::PROJECT_DROP_FAILED               => '_project.drop-failed',
        self::TRACE_NOT_EXISTS                  => '_trace.missing',
        self::TRACE_NOT_YOURS                   => '_trace.not-yours',
        self::TRACE_DETAIL_CANCEL_FAILED        => '_trace.detail-not-canceled',
        self::TRACE_STOP_FAILED                 => '_trace.stop-failed',
        self::LOTTERY_NOT_EXISTS                => '_lottery.missing',
        self::LOTTERY_NOT_AVAILABLE             => '_lottery.not-available',
        self::LOTTERY_MUST_BE_NOT_INSTANT       => '_lottery.must-be-not-instant',
        self::LOTTERY_MUST_BE_INSTANT           => '_lottery.must-be-instant',
        self::PRIZE_GROUP_ERROR                 => '_bet.prize-group-error',
        self::ISSUE_EXPIRED                     => '_issue.expired',
        self::BET_DATA_ERROR                    => '_bet.data-error',
        self::BET_PRICE_ERROR                   => '_bet.price-error',
        self::BET_NUMBER_ERROR                  => '_bet.number-error',
        self::BET_COUNT_ERROR                   => '_bet.count-error',
        self::BET_PRIZE_OVERFLOW                => '_bet.prize-overflow',
        self::BET_LOW_AMOUNT                    => '_bet.low-amount',
        self::BET_LOW_BALANCE                   => '_fund.balance-not-enough',
        self::BET_PARTLY_CREATED                => '_bet.partly-created',
        self::BET_FAILED                        => '_bet.failed',
        self::USER_CANT_BET                     => '_user.bet-not-allowed',
        self::AGENT_CANT_BET                    => '_bet.not-player',
        self::ACCOUNT_LOCK_FAILED               => '_account.locked-failed',
        self::TRANSACTION_NOT_EXISTS            => '_transaction.missing',
        self::TRANSACTION_NOT_YOURS             => '_transaction.not-yours',
        self::MISSING_USER_BANKCARD             => '_basic.data-not-exists',
        self::NEW_USER_BANKCARD                 => '_userbankcard.too_short_time_after_binded',
        self::WITHDRAW_TOO_MANY_TIMES           => '_withdrawal.overtimes',
        self::WITHDRAW_AMOUNT_FORMAT_ERROR      => '_withdrawal.amount-format-error',
        self::WITHDRAW_OUT_OF_RANGE             => '_withdrawal.out-of-range',
        self::WITHDRAW_OVERFLOW                 => '_withdrawal.overflow',
        self::WITHDRAW_FAILED                   => '_withdrawal.withdrawal-failed',
        self::ARTICLE_NOT_EXISTS                => '_cmsarticle.missing',
        self::ARTICLE_CLOSED                    => '_cmsarticle.closed',
        self::DEPOSIT_PLATFORM_ERROR            => '_deposit.platform_error',
        self::DEPOSIT_AMOUNT_FORMAT_ERROR       => '_deposit.amount-error',
        self::DEPOSIT_PAYMENT_ACCOUNT_ERROR     => '_deposit.payment_account_error',
        self::USER_DEPOSIT_SAVE_FAILED          => '_deposit.deposit_save_failed',
        self::DEPOSIT_PLATFORM_NOT_AVAILABLE    => '_deposit.platform_not_available',
        self::DEPOSIT_OUT_OF_RANGE              => '_deposit.out-of-range',
        #zero add
        self::DEPOSIT_ORDER_NOT_EXISTS          => '_deposit.order-not-exists',
        self::DEPOSIT_WAITING_LOAD              => '_deposit.waiting-load',
        self::DEPOSIT_SUCCESS                   => '_deposit.success',
        self::DEPOSIT_ADD_FAILED                => '_deposit.add-failed',
        self::DEPOSIT_EXCEPTION                 => '_deposit.exception-deposit',
        self::DEPOSIT_CS_PROCESSING             => '_deposit.admin-processing',
        self::DEPOSIT_PLATFORM_QUERY_FAILED     => '_deposit.platform-query-failed',
        self::DEPOSIT_PLATFORM_ORDER_NOT_EXISTS => '_deposit.platform-oder-not-exists',
        self::DEPOSIT_ORDER_UNPAY               => '_deposit.order-unpay',
        self::DEPOSIT_AMOUNT_ERROR              => '_deposit.pay-amount-error',
        self::DEPOSIT_ADD_STATUS_SETTING_FAILED => '_deposit.set-wait-status-failed',
        self::DEPOSIT_FAILED                    => '_deposit.deposit-failed',
        self::DEPOSIT_GET_QR_CODE_FAILED        => '_deposit.init-qr-failed',
        #绑卡相关 zero add
        self::BIND_CARD_FIRST                   => '_userbankcard.bind-card-first',
        self::SAFE_RESET_FUND_PASSWORD          => 'users.safe-reset-fund-password',
        self::ACCOUNT_LOCKED_FOR_WRONG_FUND_PWD => '_account.locked-contact-custom-service-to-unlocked',
        self::WRONG_FUND_PWD_TRY_TIMES          => '_account.fund-pwd-is-wrong-differ-times',
        self::LOCK_BANK_CARD_FAILED             => '_userbankcard.locked-bankcards-fail',
        self::WRONG_BANK_CARD_TRY_TIMES         => '_account.user-bank-info-wrong-times',
        self::USER_BANK_CARD_NOT_EXISTS         => '_userbankcard.missing-data',
        self::USER_BANK_CARD_OUT_OF_RANGE       => '_userbankcard.user-bank-cards-count-full',
        self::USER_CHECKED_TOKEN_INVALID        => '_userbankcard.checked-token-invalid',
        self::USER_BANK_CARD_ALREDY_LOCKED      => '_userbankcard.user-bank-cards-locked',
        self::USER_BIND_CARD_FAILED             => '_userbankcard.bind-card-fail',
        self::USER_BANK_CARD_EXISTS             => '_userbankcard.user-bank-card-exist',
        self::USER_BANK_CARD_DELETE_FAILED      => '_userbankcard.delete-fail',
        self::CHECK_TYPE_ERROR                  => '_userbankcard.check_type_error',
        self::DISTRICT_NOT_EXISTS               => '_district.missing-data',
        self::DISTRICT_RELATION_ERROR           => '_district.relation-error',
        self::PAYMENT_TYPE_ERROR                => '_district.payment-type-error',
        self::WITHDRAW_NOT_ALLOWED              => '_user.withdraw-not-allowed',
        self::GET_QR_FAILED                     => '_deposit.withdraw-not-allowed',
        self::GAME_TYPE_ERROR                   => '_deposit.game-type-error',
        self::MISSING_THIRD_PLAT                => '_thirdplat.missing-plat',
        self::LOTTERY_INIT_ERROR                => '_lottery.get-lottery-list-failed',
    ];

    static function & getMessage($iErrno, $aLangVars = [], $sExtra = null) {
//        App::setLocale('zh-CN');
        $sMsg = __(static::$messages[$iErrno], $aLangVars);
        !$sExtra or $sMsg .= ': ' . var_export($sExtra, true);
        return $sMsg;
    }

}
