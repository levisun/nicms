<?php

/**
 *
 * 数据层
 * 搜索引擎
 *
 * @package   NICMS
 * @category  app\model
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
/*
DROP TABLE IF EXISTS `nc_searchengine`;
CREATE TABLE IF NOT EXISTS `nc_searchengine` (
  `date` int(11) NOT NULL DEFAULT '0' COMMENT '日期',
  `name` varchar(20) NOT NULL DEFAULT '' COMMENT '搜索引擎名',
  `user_agent` varchar(255) NOT NULL DEFAULT '' COMMENT '访问agent',
  `count` int(11) UNSIGNED NOT NULL DEFAULT '1' COMMENT '统计数量',
  KEY `date` (`date`) USING BTREE,
  KEY `name` (`name`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='搜索引擎';
 */

namespace app\model;

use think\Model;

class Searchengine extends Model
{
    protected $name = 'searchengine';
    protected $autoWriteTimestamp = false;
    protected $updateTime = false;
    // protected $pk = 'id';
    protected $type = [
        'count' => 'integer',
    ];
    protected $field = [
        // 'id',
        'date',
        'name',
        'user_agent',
        'count',
    ];
}
