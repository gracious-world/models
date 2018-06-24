<?php

/**
 * 资讯任务父类
 *
 * @author ben
 */

class BaseCmsTask extends BaseModel {

    public static function curl($url)
    {
        $curl = new MyCurl($url);
        $curl->createCurl();
        $curl->execute();
        return self::characet($curl->__tostring());
    }

    public static function myTrim($str)
    {
        $search = array(" ","　","\n","\r","\t");
        $replace = array("","","","","");
        return str_replace($search, $replace, $str);
    }

    /*
     * 获取字符串中间部分
     */
    public static function getStrRegion($sString,$sStartStr,$sEndStr)
    {
        $iStartStrIndex = strpos($sString,$sStartStr) + strlen($sStartStr);
        $sStartStrIndexStr = substr($sString,$iStartStrIndex);

        $iEndStrIndex = strpos($sStartStrIndexStr,$sEndStr);

        return substr($sString,$iStartStrIndex,$iEndStrIndex);
    }

    /*
     *  替换字符串区间
     */
    public static function replaceStrRegion($sString,$sStartStr,$sEndStr,$replace = '')
    {
        $sStr = self::getStrRegion($sString,$sStartStr,$sEndStr);

        return str_replace($sStr,$replace,$sString);
    }


    public static function characet($data){
        if( !empty($data) ){
            $fileType = mb_detect_encoding($data , array('UTF-8','GBK','LATIN1','BIG5')) ;
            if( $fileType != 'UTF-8'){
                $data = mb_convert_encoding($data ,'utf-8' , $fileType);
            }
        }
        return $data;
    }
}