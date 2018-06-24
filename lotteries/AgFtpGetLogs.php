<?php

/**
 * AG xml文件抓取记录
 *
 * @author  Garin
 * @date  2016-11-21
 *
 */
class AgFtpGetLogs extends BaseModel {

    protected $table = 'ag_ftp_get_logs';

    /**
     * 获取文件信息
     *
     * @param string $sFileName
     *
     * @return Query
     */
    public static function getFileByFileName($sFileName = '') {
        return static::doWhere(['file_name' => ['=', $sFileName]])
            ->get()
            ->toArray();
    }

    /**
     * 获取下载文件记录列表
     *
     * @param $aWhere
     *
     * @return mixed
     */
    public static function & getFileList($aWhere = []) {
        $aCondition = [];

        if (isset($aWhere['type'])) {
            $aCondition['type'] = ['=', $aWhere['type']];
        }
        $aCondition['is_parse'] = ['=', 0];

        $aFileList = static::doWhere($aCondition)
            ->orderBy('created_at', 'ASC')
            ->get()
            ->toArray();

        return $aFileList;
    }

    /**
     * 记录抓取日志
     *
     * @param string $sfileName
     * @param string $sLocalFileName
     * @param string $sRemoteFileName
     * @param string $sType
     *
     * @return mixed
     */
    public static function createDataLogs($sfileName = '', $sLocalFileName = '', $sRemoteFileName = '', $sType = '') {
        $oAgFtpGetLogs = new static();
        $oAgFtpGetLogs->file_name = $sfileName;
        $oAgFtpGetLogs->type = $sType;
        $oAgFtpGetLogs->remote_path = $sRemoteFileName;
        $oAgFtpGetLogs->local_path = $sLocalFileName;
        return $oAgFtpGetLogs->save();
    }


}
