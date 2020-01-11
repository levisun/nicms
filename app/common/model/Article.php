<?php

/**
 *
 * 数据层
 * 文章表
 *
 * @package   NICMS
 * @category  app\common\model
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
/*
DROP TABLE IF EXISTS `nc_article`;
CREATE TABLE IF NOT EXISTS `nc_article` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` varchar(50) NOT NULL DEFAULT '' COMMENT '标题',
    `keywords` varchar(100) NOT NULL DEFAULT '' COMMENT '关键词',
    `description` varchar(300) NOT NULL DEFAULT '' COMMENT '描述',
    `category_id` smallint(5) UNSIGNED NOT NULL DEFAULT '0' COMMENT '栏目ID',
    `type_id` smallint(5) UNSIGNED NOT NULL DEFAULT '0' COMMENT '类型ID',
    `admin_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '管理员ID',
    `user_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '发布人ID',
    `is_pass` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '审核',
    `is_com` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '推荐',
    `is_top` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '置顶',
    `is_hot` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '最热',
    `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT '0' COMMENT '排序',
    `hits` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '点击量',
    `username` varchar(20) NOT NULL DEFAULT '' COMMENT '作者名',
    `show_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '显示时间',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '修改时间',
    `delete_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '删除时间',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建时间',
    `access_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '访问权限',
    `lang` varchar(10) NOT NULL DEFAULT 'zh-cn' COMMENT '语言',
    PRIMARY KEY (`id`),
    KEY `title` (`title`) USING BTREE,
    KEY `category_id` (`category_id`) USING BTREE,
    KEY `type_id` (`type_id`) USING BTREE,
    KEY `is_pass` (`is_pass`) USING BTREE,
    KEY `is_com` (`is_com`) USING BTREE,
    KEY `is_top` (`is_top`) USING BTREE,
    KEY `is_hot` (`is_hot`) USING BTREE,
    KEY `sort_order` (`sort_order`) USING BTREE,
    KEY `show_time` (`show_time`) USING BTREE,
    KEY `update_time` (`update_time`) USING BTREE,
    KEY `delete_time` (`delete_time`) USING BTREE,
    KEY `lang` (`lang`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文章表';
 */

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;

class Article extends Model
{
    // use SoftDelete;
    protected $name = 'article';
    protected $autoWriteTimestamp = true;
    protected $updateTime = 'update_time';
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    protected $dateFormat = false;
    protected $pk = 'id';
    protected $type = [
        'category_id' => 'integer',
        'type_id'     => 'integer',
        'is_pass'     => 'integer',
        'is_com'      => 'integer',
        'is_top'      => 'integer',
        'is_hot'      => 'integer',
        'sort_order'  => 'integer',
        'hits'        => 'integer',
        'admin_id'    => 'integer',
        'user_id'     => 'integer',
    ];
    protected $field = [
        'id',
        'title',
        'keywords',
        'description',
        'category_id',
        'type_id',
        'is_pass',
        'is_com',
        'is_top',
        'is_hot',
        'sort_order',
        'hits',
        'username',
        'admin_id',
        'user_id',
        'show_time',
        'create_time',
        'update_time',
        'delete_time',
        'access_id',
        'lang'
    ];
}
