<?php

/**
 *
 * 数据层
 * 反馈表
 *
 * @package   NICMS
 * @category  app\common\model
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
/*
DROP TABLE IF EXISTS `nc_feedback`;
CREATE TABLE IF NOT EXISTS `nc_feedback` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `title` varchar(20) NOT NULL DEFAULT '' COMMENT '标题',
    `username` varchar(20) NOT NULL DEFAULT '' COMMENT '作者名',
    `content` varchar(300) NOT NULL DEFAULT '' COMMENT '内容',
    `category_id` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '栏目ID',
    `type_id` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '类型ID',
    `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '会员ID',
    `is_pass` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '审核',
    `ip` varchar(15) NOT NULL DEFAULT '' COMMENT 'IP',
    `ip_attr` varchar(100) NOT NULL DEFAULT '' COMMENT 'IP地区',
    `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '修改时间',
    `delete_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '删除时间',
    `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
    `lang` varchar(10) NOT NULL DEFAULT 'zh-cn' COMMENT '语言',
    PRIMARY KEY (`id`),
    KEY `category_id` (`category_id`),
    KEY `type_id` (`type_id`),
    KEY `is_pass` (`is_pass`),
    KEY `delete_time` (`delete_time`),
    KEY `lang` (`lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='反馈表';
 */

namespace app\common\model;

use think\Model;

class Feedback extends Model
{
    protected $name = 'feedback';
    protected $autoWriteTimestamp = true;
    protected $updateTime = 'update_time';
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    protected $dateFormat = false;
    protected $pk = 'id';
    protected $type = [
        'category_id' => 'integer',
        'type_id'     => 'integer',
        'user_id'     => 'integer',
        'is_pass'     => 'integer',
    ];
    protected $field = [
        'id',
        'title',
        'username',
        'content',
        'category_id',
        'type_id',
        'user_id',
        'is_pass',
        'ip',
        'ip_attr',
        'update_time',
        'delete_time',
        'create_time',
        'lang',
    ];
}
