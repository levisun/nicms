<?php

/**
 *
 * 数据层
 * 上传文件日志类
 *
 * @package   NICMS
 * @category  app\common\model
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
/*
DROP TABLE IF EXISTS `nc_upload_file_log`;
CREATE TABLE IF NOT EXISTS `nc_upload_file_log` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `file` varchar(100) NOT NULL DEFAULT '' COMMENT '文件路径',
    `type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '类型 0临时文件 1入库文件',
    `module_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '模块ID',
    `module_type` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '模块类型 1用户头像 2栏目图标 3文章缩略图 4文章内容插图 5...',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `type` (`type`) USING BTREE,
    KEY `module_id` (`module_id`) USING BTREE,
    KEY `module_type` (`module_type`) USING BTREE,
    KEY `create_time` (`create_time`) USING BTREE
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
        'module_id'   => 'integer',
        'module_type' => 'integer',
    ];
    protected $field = [
        'id',
        'file',
    ];
}
