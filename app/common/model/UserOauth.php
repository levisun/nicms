<?php

/**
 *
 * 数据层
 * 第三方登录用户表
 *
 * @package   NICMS
 * @category  app\common\model
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
/*
DROP TABLE IF EXISTS `nc_user_oauth`;
CREATE TABLE IF NOT EXISTS `nc_user_oauth` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '用户ID',
    `open_id` varchar(50) NOT NULL DEFAULT '' COMMENT 'open_id',
    `nick` varchar(50) NOT NULL DEFAULT '' COMMENT '昵称',
    `type` varchar(10) NOT NULL DEFAULT '' COMMENT '类型',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建时间',
    PRIMARY KEY (`id`),
    INDEX `user_id` (`user_id`) USING BTREE,
    INDEX `open_id` (`open_id`) USING BTREE,
    INDEX `type` (`type`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT '第三方登录用户';
 */

namespace app\common\model;

use think\Model;

class UserOauth extends Model
{
    protected $name = 'user_oauth';
    protected $autoWriteTimestamp = true;
    protected $updateTime = false;
    protected $dateFormat = false;
    protected $pk = 'id';
    protected $type = [
        'user_id' => 'integer',
    ];
    protected $field = [
        'id',
        'user_id',
        'open_id',
        'nick',
        'type',
        'create_time',
    ];
}
