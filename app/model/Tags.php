<?php

/**
 *
 * 数据层
 * 标签表
 *
 * @package   NICMS
 * @category  app\model
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
/*
DROP TABLE IF EXISTS `nc_tags`;
CREATE TABLE IF NOT EXISTS `nc_tags` (
  `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL DEFAULT '' COMMENT '标签名',
  `count` int(11) UNSIGNED NOT NULL DEFAULT '1' COMMENT '标签文章数量',
  `lang` varchar(20) NOT NULL DEFAULT 'zh-cn' COMMENT '语言',
  PRIMARY KEY (`id`),
  KEY `name` (`name`) USING BTREE,
  KEY `lang` (`lang`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='标签表';
 */

namespace app\model;

use think\Model;

class Tags extends Model
{
    protected $name = 'tags';
    protected $autoWriteTimestamp = false;
    protected $updateTime = false;
    protected $pk = 'id';
    protected $type = [
        'count' => 'integer',
    ];
    protected $field = [
        'id',
        'name',
        'count',
    ];
}
