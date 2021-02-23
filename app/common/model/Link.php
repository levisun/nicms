<?php

/**
 *
 * 数据层
 * 友情链接表
 *
 * @package   NICMS
 * @category  app\common\model
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
/*
DROP TABLE IF EXISTS `nc_link`;
CREATE TABLE IF NOT EXISTS `nc_link` (
    `id` mediumint(9) UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` varchar(20) NOT NULL DEFAULT '' COMMENT '标题',
    `logo` varchar(100) NOT NULL DEFAULT '' COMMENT '标志',
    `url` varchar(100) NOT NULL DEFAULT '' COMMENT '跳转链接',
    `description` varchar(300) NOT NULL DEFAULT '' COMMENT '描述',
    `category_id` smallint(6) UNSIGNED NOT NULL DEFAULT '0' COMMENT '栏目ID',
    `type_id` smallint(6) UNSIGNED NOT NULL DEFAULT '0' COMMENT '类型ID',
    `admin_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '管理员ID',
    `is_pass` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '审核',
    `sort_order` smallint(6) UNSIGNED NOT NULL DEFAULT '0' COMMENT '排序',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '修改时间',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建时间',
    `lang` varchar(10) NOT NULL DEFAULT 'zh-cn' COMMENT '语言',
    PRIMARY KEY (`id`),
    INDEX `category_id` (`category_id`) USING BTREE,
    INDEX `type_id` (`type_id`) USING BTREE,
    INDEX `is_pass` (`is_pass`) USING BTREE,
    INDEX `sort_order` (`sort_order`) USING BTREE,
    INDEX `update_time` (`update_time`) USING BTREE,
    INDEX `lang` (`lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='友链表';
 */

namespace app\common\model;

use think\Model;

class Link extends Model
{
    protected $name = 'link';
    protected $autoWriteTimestamp = true;
    protected $updateTime = 'update_time';
    protected $pk = 'id';
    protected $type = [
        'category_id' => 'integer',
        'type_id'     => 'integer',
        'admin_id'    => 'integer',
        'user_id'     => 'integer',
        'is_pass'     => 'integer',
        'hits'        => 'integer',
    ];
    protected $field = [
        'id',
        'title',
        'logo',
        'url',
        'remark',
        'category_id',
        'type_id',
        'admin_id',
        'user_id',
        'is_pass',
        'hits',
        'sort_order',
        'update_time',
        'create_time',
        'lang',
    ];
}
