<?php

/**
 *
 * 数据层
 * 栏目表
 *
 * @package   NICMS
 * @category  app\model
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
/*
DROP TABLE IF EXISTS `nc_category`;
CREATE TABLE IF NOT EXISTS `nc_category` (
  `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pid` smallint(5) UNSIGNED NOT NULL DEFAULT '0' COMMENT '父ID',
  `name` varchar(20) NOT NULL DEFAULT '' COMMENT '栏目名',
  `aliases` varchar(20) NOT NULL DEFAULT '' COMMENT '别名',
  `title` varchar(50) NOT NULL DEFAULT '' COMMENT '标题',
  `keywords` varchar(100) NOT NULL DEFAULT '' COMMENT '关键词',
  `description` varchar(300) NOT NULL DEFAULT '' COMMENT '描述',
  `image` varchar(100) NOT NULL DEFAULT '' COMMENT '图标',
  `type_id` smallint(5) UNSIGNED NOT NULL DEFAULT '0' COMMENT '类型ID',
  `model_id` smallint(5) UNSIGNED NOT NULL COMMENT '模型ID',
  `is_show` tinyint(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT '显示',
  `is_channel` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '频道页',
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT '0' COMMENT '排序',
  `access_id` smallint(5) UNSIGNED NOT NULL DEFAULT '0' COMMENT '权限',
  `url` varchar(200) NOT NULL DEFAULT '' COMMENT '外链地址',
  `update_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '修改时间',
  `create_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建时间',
  `lang` varchar(10) NOT NULL DEFAULT 'zh-cn' COMMENT '语言',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `pid` (`pid`) USING BTREE,
  KEY `type_id` (`type_id`) USING BTREE,
  KEY `model_id` (`model_id`) USING BTREE,
  KEY `is_show` (`is_show`) USING BTREE,
  KEY `access_id` (`access_id`) USING BTREE,
  KEY `lang` (`lang`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='栏目表';
 */

namespace app\model;

use think\Model;

class Category extends Model
{
    protected $name = 'category';
    protected $autoWriteTimestamp = true;
    protected $updateTime = 'update_time';
    protected $pk = 'id';
    protected $type = [
        'type_id'    => 'integer',
        'model_id'   => 'integer',
        'is_show'    => 'integer',
        'is_channel' => 'integer',
        'sort_order' => 'integer',
        'access_id'  => 'integer',
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
        'type_id',
        'model_id',
        'is_show',
        'is_channel',
        'sort_order',
        'access_id',
        'url',
        'create_time',
        'update_time',
        'lang'
    ];
}
