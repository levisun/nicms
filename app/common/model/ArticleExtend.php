<?php

/**
 *
 * 数据层
 * 文章扩展表
 *
 * @package   NICMS
 * @category  app\common\model
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
/*
DROP TABLE IF EXISTS `nc_article_extend`;
CREATE TABLE IF NOT EXISTS `nc_article_extend` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `article_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '文章ID',
  `fields_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '字段ID',
  `data` longtext NOT NULL COMMENT '内容',
  PRIMARY KEY (`id`),
  KEY `article_id` (`article_id`) USING BTREE,
  KEY `fields_id` (`fields_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文章扩展表';
 */

namespace app\common\model;

use think\Model;

class ArticleExtend extends Model
{
    protected $name = 'article_extend';
    protected $autoWriteTimestamp = false;
    protected $updateTime = false;
    protected $pk = 'id';
    protected $type = [
        'article_id' => 'integer',
        'fields_id'  => 'integer',
    ];
    protected $field = [
        'id',
        'article_id',
        'fields_id',
        'data'
    ];
}
