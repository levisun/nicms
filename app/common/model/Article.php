<?php

/**
 *
 * 数据层
 * 文章表
 *
 * @package   NICMS
 * @category  app\common\model
 * @author    失眠小枕头 [312630173@qq.com]
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
    `thumb` varchar(200) NOT NULL DEFAULT '' COMMENT '缩略图',
    `category_id` smallint(6) UNSIGNED NOT NULL DEFAULT '0' COMMENT '栏目ID',
    `type_id` smallint(6) UNSIGNED NOT NULL DEFAULT '0' COMMENT '类型ID',
    `admin_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '管理员ID',
    `user_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '发布人ID',
    `is_pass` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '审核',
    `attribute` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '1推荐 2置顶 3最热',
    `sort_order` smallint(6) UNSIGNED NOT NULL DEFAULT '0' COMMENT '排序',
    `hits` mediumint(9) UNSIGNED NOT NULL DEFAULT '0' COMMENT '点击量',
    `author` varchar(20) NOT NULL DEFAULT '' COMMENT '作者名',
    `show_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '显示时间',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '修改时间',
    `delete_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '删除时间',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建时间',
    `access_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '访问权限',
    `lang` varchar(10) NOT NULL DEFAULT 'zh-cn' COMMENT '语言',
    PRIMARY KEY (`id`),
    INDEX `title` (`title`) USING BTREE,
    INDEX `category_id` (`category_id`) USING BTREE,
    INDEX `type_id` (`type_id`) USING BTREE,
    INDEX `admin_id` (`admin_id`) USING BTREE,
    INDEX `user_id` (`user_id`) USING BTREE,
    INDEX `is_pass` (`is_pass`) USING BTREE,
    INDEX `attribute` (`attribute`) USING BTREE,
    INDEX `sort_order` (`sort_order`) USING BTREE,
    INDEX `show_time` (`show_time`) USING BTREE,
    INDEX `update_time` (`update_time`) USING BTREE,
    INDEX `delete_time` (`delete_time`) USING BTREE,
    INDEX `access_id` (`access_id`) USING BTREE,
    INDEX `lang` (`lang`) USING BTREE
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
        'attribute'   => 'integer',
        'sort_order'  => 'integer',
        'hits'        => 'integer',
        'admin_id'    => 'integer',
        'user_id'     => 'integer',
        'access_id'   => 'integer',
    ];
    protected $field = [
        'id',
        'title',
        'keywords',
        'description',
        'category_id',
        'type_id',
        'is_pass',
        'attribute',
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
