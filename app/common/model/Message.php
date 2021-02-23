<?php

/**
 *
 * 数据层
 * 留言表
 *
 * @package   NICMS
 * @category  app\common\model
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
/*
DROP TABLE IF EXISTS `nc_message`;
CREATE TABLE IF NOT EXISTS `nc_message` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` varchar(20) NOT NULL DEFAULT '' COMMENT '标题',
    `username` varchar(20) NOT NULL DEFAULT '' COMMENT '作者名',
    `content` varchar(300) NOT NULL DEFAULT '' COMMENT '内容',
    `reply` varchar(300) NOT NULL DEFAULT '' COMMENT '回复',
    `category_id` smallint(6) UNSIGNED NOT NULL DEFAULT '0' COMMENT '栏目ID',
    `type_id` smallint(6) UNSIGNED NOT NULL DEFAULT '0' COMMENT '类型ID',
    `user_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '会员ID',
    `is_pass` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '审核',
    `ipv4_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '评论IP',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '修改时间',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建时间',
    `lang` varchar(10) NOT NULL DEFAULT 'zh-cn' COMMENT '语言',
    PRIMARY KEY (`id`),
    INDEX `category_id` (`category_id`) USING BTREE,
    INDEX `type_id` (`type_id`) USING BTREE,
    INDEX `is_pass` (`is_pass`) USING BTREE,
    INDEX `update_time` (`update_time`) USING BTREE,
    INDEX `lang` (`lang`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='留言表';
 */

namespace app\common\model;

use think\Model;

class Message extends Model
{
    protected $name = 'message';
    protected $autoWriteTimestamp = true;
    protected $updateTime = 'update_time';
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
        'create_time',
        'lang',
    ];
}
