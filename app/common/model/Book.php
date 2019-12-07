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
DROP TABLE IF EXISTS `nc_book`;
CREATE TABLE IF NOT EXISTS `nc_book` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(50) NOT NULL COMMENT '书名',
  `keywords` varchar(100) NOT NULL DEFAULT '' COMMENT '关键词',
  `description` varchar(300) NOT NULL DEFAULT '' COMMENT '描述',
  `image` varchar(255) NOT NULL DEFAULT '' COMMENT '封面',
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
  KEY `title` (`title`),
  KEY `type_id` (`type_id`),
  KEY `author_id` (`author_id`),
  KEY `is_pass` (`is_pass`),
  KEY `is_com` (`is_com`),
  KEY `is_top` (`is_top`),
  KEY `is_hot` (`is_hot`),
  KEY `delete_time` (`delete_time`),
  KEY `lang` (`lang`)
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
    ];
    protected $field = [
        'id',
        'title',
        'keywords',
        'description',
        'author_id',
        'type_id',
        'is_pass',
        'is_com',
        'is_top',
        'is_hot',
        'sort_order',
        'hits',
        'create_time',
        'update_time',
        'delete_time',
        'access_id',
        'lang'
    ];
}
