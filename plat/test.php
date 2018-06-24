<?php
/**
 * Created by PhpStorm.
 * User: rex
 * Date: 4/5/17
 * Time: 5:59 PM
 */

$oUser = User::find(21);
$oAccount = Account::where('user_id', $oUser->id)->first();
var_dump($oAccount->balance);

$oAccount->balance = '0.000000';
$oAccount->available = '0.000000';
$oAccount->save();

$oAccount = Account::where('user_id', $oUser->id)->first();
var_dump($oAccount->balance);

exit();


$oUser = User::find(5);
$oAccount = Account::where('user_id', $oUser->id)->first();
var_dump($oAccount->balance);

$oAccount->balance = '997720.000000';
$oAccount->available = '997720.000000';
$oAccount->save();

$oAccount = Account::where('user_id', $oUser->id)->first();
var_dump($oAccount->balance);

exit();



