<?php

/**
 *
 * 数据层
 * 书
 *
 * @package   NICMS
 * @category  app\common\model
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
/*
DROP TABLE IF EXISTS `nc_book`;
CREATE TABLE IF NOT EXISTS `nc_book` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `title` varchar(50) NOT NULL COMMENT '书名',
    `keywords` varchar(100) NOT NULL DEFAULT '' COMMENT '关键词',
    `description` varchar(300) NOT NULL DEFAULT '' COMMENT '描述',
    `image` varchar(200) NOT NULL DEFAULT '' COMMENT '封面',
    `origin` varchar(200) NOT NULL DEFAULT '' COMMENT '来源',
    `author_id` int(6) unsigned NOT NULL DEFAULT '0' COMMENT '作者ID',
    `type_id` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '类型ID',
    `is_pass` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '审核',
    `is_com` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '推荐',
    `is_top` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '置顶',
    `is_hot` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '最热',
    `sort_order` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
    `hits` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '点击量',
    `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态 1更新 2完结 3太监',
    `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '修改时间',
    `delete_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '删除时间',
    `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
    `lang` varchar(20) NOT NULL DEFAULT 'zh-cn' COMMENT '语言',
    PRIMARY KEY (`id`),
    INDEX `title` (`title`) USING BTREE,
    INDEX `type_id` (`type_id`) USING BTREE,
    INDEX `author_id` (`author_id`) USING BTREE,
    INDEX `is_pass` (`is_pass`) USING BTREE,
    INDEX `is_com` (`is_com`) USING BTREE,
    INDEX `is_top` (`is_top`) USING BTREE,
    INDEX `is_hot` (`is_hot`) USING BTREE,
    INDEX `sort_order` (`sort_order`) USING BTREE,
    INDEX `status` (`status`) USING BTREE,
    INDEX `update_time` (`update_time`) USING BTREE,
    INDEX `delete_time` (`delete_time`) USING BTREE,
    INDEX `lang` (`lang`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='书库表';
 */

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;

class Book extends Model
{
    // use SoftDelete;
    protected $name = 'book';
    protected $autoWriteTimestamp = true;
    protected $updateTime = 'update_time';
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    protected $dateFormat = false;
    protected $pk = 'id';
    protected $type = [
        'author_id'   => 'integer',
        'type_id'     => 'integer',
        'is_pass'     => 'integer',
        'is_com'      => 'integer',
        'is_top'      => 'integer',
        'is_hot'      => 'integer',
        'sort_order'  => 'integer',
        'hits'        => 'integer',
        'status'      => 'integer',
    ];
    protected $field = [
        'id',
        'title',
        'keywords',
        'description',
        'image',
        'origin',
        'author_id',
        'type_id',
        'is_pass',
        'is_com',
        'is_top',
        'is_hot',
        'sort_order',
        'hits',
        'status',
        'create_time',
        'update_time',
        'delete_time',
        'lang'
    ];
}
