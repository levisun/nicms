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
DROP TABLE IF EXISTS `nc_book_article`;
CREATE TABLE IF NOT EXISTS `nc_book_article` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `book_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '书ID',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '标题',
  `content` mediumtext NOT NULL DEFAULT '' COMMENT '内容',
  `is_pass` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '审核',
  `sort_order` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `hits` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '点击量',
  `show_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '显示时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '修改时间',
  `delete_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '删除时间',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `book_id` (`book_id`),
  KEY `is_pass` (`is_pass`),
  KEY `show_time` (`show_time`),
  KEY `delete_time` (`delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='书库文章表';
 */

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;

class BookArticle extends Model
{
    // use SoftDelete;
    protected $name = 'book_article';
    protected $autoWriteTimestamp = true;
    protected $updateTime = 'update_time';
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    protected $pk = 'id';
    protected $type = [
        'book_id'   => 'integer',
        'is_pass'     => 'integer',
        'sort_order'  => 'integer',
        'hits'        => 'integer',
    ];
    protected $field = [
        'id',
        'book_id',
        'title',
        'content',
        'is_pass',
        'sort_order',
        'hits',
        'show_time',
        'create_time',
        'update_time',
        'delete_time',
    ];
}
