<?php

/**
 *
 * 数据层
 * 会员等级表
 *
 * @package   NICMS
 * @category  app\common\model
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
/*
DROP TABLE IF EXISTS `nc_level`;
CREATE TABLE IF NOT EXISTS `nc_level` (
    `id` smallint(6) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` varchar(20) NOT NULL DEFAULT '' COMMENT '组名',
    `credit` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '积分',
    `status` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '状态',
    `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '描述',
    PRIMARY KEY (`id`),
    INDEX `credit` (`credit`) USING BTREE,
    INDEX `status` (`status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户组';
INSERT INTO `nc_level` (`id`, `name`, `credit`, `status`, `remark`) VALUES
(1, '钻石会员', 500000000, 1, ''),
(2, '黄金会员', 30000000, 1, ''),
(3, '白金会员', 500000, 1, ''),
(4, 'VIP会员', 3000, 1, ''),
(5, '高级会员', 500, 1, ''),
(6, '普通会员', 0, 1, '');
 */

namespace app\common\model;

use think\Model;

class Level extends Model
{
    protected $name = 'level';
    protected $autoWriteTimestamp = false;
    protected $updateTime = false;
    protected $dateFormat = false;
    protected $pk = 'id';
    protected $type = [
        'credit' => 'integer',
        'status' => 'integer',
    ];
    protected $field = [
        'id',
        'name',
        'credit',
        'status',
        'remark',
    ];
}
