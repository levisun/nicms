<?php

/**
 *
 * 数据层
 * 书籍分类表
 *
 * @package   NICMS
 * @category  app\common\model
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
/*
DROP TABLE IF EXISTS `nc_book_type`;
CREATE TABLE IF NOT EXISTS `nc_book_type` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `pid` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '父ID',
    `name` varchar(20) NOT NULL DEFAULT '' COMMENT '类名',
    `aliases` varchar(20) NOT NULL DEFAULT '' COMMENT '别名',
    `title` varchar(50) NOT NULL DEFAULT '' COMMENT '标题',
    `keywords` varchar(100) NOT NULL DEFAULT '' COMMENT '关键词',
    `description` varchar(300) NOT NULL DEFAULT '' COMMENT '描述',
    `image` varchar(100) NOT NULL DEFAULT '' COMMENT '图标',
    `is_show` tinyint(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT '显示',
    `sort_order` smallint(6) UNSIGNED NOT NULL DEFAULT '0' COMMENT '排序',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '修改时间',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建时间',
    `lang` varchar(10) NOT NULL DEFAULT 'zh-cn' COMMENT '语言',
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`),
    INDEX `pid` (`pid`) USING BTREE,
    INDEX `is_show` (`is_show`) USING BTREE,
    INDEX `sort_order` (`sort_order`) USING BTREE,
    INDEX `lang` (`lang`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='书籍分类表';
 */

namespace app\common\model;

use think\Model;

class BookType extends Model
{
    protected $name = 'book_type';
    protected $autoWriteTimestamp = true;
    protected $updateTime = 'update_time';
    protected $dateFormat = false;
    protected $pk = 'id';
    protected $type = [
        'is_show'    => 'integer',
    ];
    protected $field = [
        'id',
        'pid',
        'name',
        'aliases',
        'title',
        'keywords',
        'description',
        'image',
        'is_show',
        'sort_order',
        'create_time',
        'update_time',
        'lang'
    ];
}
