<?php

/**
 *
 * 数据层
 * 行为日志表
 *
 * @package   NICMS
 * @category  app\common\model
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
/*
DROP TABLE IF EXISTS `nc_action_log`;
CREATE TABLE IF NOT EXISTS `nc_action_log` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `action_id` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '行为ID',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '执行用户ID',
  `action_ip` varchar(255) NOT NULL COMMENT '执行行为者IP',
  `module` varchar(50) NOT NULL DEFAULT '' COMMENT '触发行为的模块',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '日志备注',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '执行行为的时间',
  PRIMARY KEY (`id`),
  KEY `action_id` (`action_id`) USING BTREE,
  KEY `user_id` (`user_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='行为日志表';
 */

namespace app\common\model;

use think\Model;

class ActionLog extends Model
{
    protected $name = 'action_log';
    protected $autoWriteTimestamp = true;
    protected $updateTime = false;
    protected $pk = 'id';
    protected $type = [
        'action_id' => 'integer',
        'user_id'   => 'integer',
    ];
    protected $field = [
        'id',
        'action_id',
        'user_id',
        'action_ip',
        'module',
        'remark',
        'create_time'
    ];
}
