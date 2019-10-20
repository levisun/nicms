<?php

/**
 *
 * 数据层
 * SESSION表
 *
 * @package   NICMS
 * @category  app\common\model
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
/*
DROP TABLE IF EXISTS `nc_session`;
CREATE TABLE IF NOT EXISTS `nc_session` (
    `session_id` varchar(40) NOT NULL,
    `data` text NOT NULL COMMENT '内容',
    `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '刷新时间',
    PRIMARY KEY (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='session';
 */

namespace app\common\model;

use think\Model;

class Session extends Model
{
    protected $name = 'session';
    protected $autoWriteTimestamp = false;
    protected $updateTime = 'update_time';
    protected $pk = 'session_id';
    protected $type = [
        // 'count' => 'integer',
    ];
    protected $field = [
        'session_id',
        'data',
        'update_time',
    ];
}
