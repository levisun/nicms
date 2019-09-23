<?php

/**
 *
 * 数据层
 * 访问表
 *
 * @package   NICMS
 * @category  app\model
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
/*
DROP TABLE IF EXISTS `nc_visit`;
CREATE TABLE IF NOT EXISTS `nc_visit` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `date` int(11) NOT NULL DEFAULT '0' COMMENT '日期',
  `ip` varchar(15) NOT NULL DEFAULT '' COMMENT '访问IP',
  `ip_attr` varchar(100) NOT NULL DEFAULT '' COMMENT '访问IP地区',
  `user_agent` varchar(255) NOT NULL DEFAULT '' COMMENT '访问agent',
  `count` int(11) UNSIGNED NOT NULL DEFAULT '1' COMMENT '统计数量',
  PRIMARY KEY (`id`),
  KEY `date` (`date`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='访问表';
 */

namespace app\model;

use think\Model;

class Visit extends Model
{
    protected $name = 'visit';
    protected $autoWriteTimestamp = false;
    protected $updateTime = false;
    protected $pk = 'id';
    protected $type = [
        'count' => 'integer',
    ];
    protected $field = [
        'id',
        'date',
        'ip',
        'ip_attr',
        'user_agent',
        'count',
    ];
}
