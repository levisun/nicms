<?php

/**
 *
 * 数据层
 * 上传文件日志类
 *
 * @package   NICMS
 * @category  app\common\model
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
/*
DROP TABLE IF EXISTS `nc_upload_file_log`;
CREATE TABLE IF NOT EXISTS `nc_upload_file_log` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` varchar(40) NOT NULL DEFAULT '' COMMENT '文件hash',
    `file` varchar(100) NOT NULL DEFAULT '' COMMENT '文件路径',
    `type` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '类型 0临时文件 1入库文件',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建时间',
    PRIMARY KEY (`id`),
    INDEX `name` (`name`) USING BTREE,
    INDEX `type` (`type`) USING BTREE,
    INDEX `create_time` (`create_time`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='上传文件日志';
 */

namespace app\common\model;

use think\Model;

class UploadFileLog extends Model
{
    protected $name = 'upload_file_log';
    protected $autoWriteTimestamp = true;
    protected $updateTime = false;
    protected $pk = 'id';
    protected $type = [
        'type'        => 'integer',
    ];
    protected $field = [
        'id',
        'name',
        'file',
        'type',
        'create_time',
    ];
}
