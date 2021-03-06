<?php

/**
 *
 * 数据层
 * 访问表
 *
 * @package   NICMS
 * @category  app\common\model
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
/*
DROP TABLE IF EXISTS `nc_visit`;
CREATE TABLE IF NOT EXISTS `nc_visit` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `date` int(11) NOT NULL DEFAULT '0' COMMENT '日期',
    `name` varchar(100) NOT NULL DEFAULT '' COMMENT '搜索引擎名',
    `ip` varchar(15) NOT NULL DEFAULT '' COMMENT '访问IP',
    `ip_attr` varchar(100) NOT NULL DEFAULT '' COMMENT '访问IP地区',
    `user_agent` varchar(32) NOT NULL DEFAULT '' COMMENT '访问agent',
    `count` int(11) UNSIGNED NOT NULL DEFAULT '1' COMMENT '统计数量',
    PRIMARY KEY (`id`),
    INDEX `date` (`date`) USING BTREE,
    INDEX `name` (`name`) USING BTREE,
    INDEX `ip` (`ip`) USING BTREE,
    INDEX `user_agent` (`user_agent`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='访问表';
 */

namespace app\common\model;

use think\Model;

class Visit extends Model
{
    protected $name = 'visit';
    protected $autoWriteTimestamp = false;
    protected $updateTime = false;
    protected $pk = 'id';
    protected $type = [
        'count' => 'integer',
    ];
    protected $field = [
        'id',
        'date',
        'name',
        'ip',
        'ip_attr',
        'user_agent',
        'count',
    ];
}
