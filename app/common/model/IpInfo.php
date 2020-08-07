<?php

/**
 *
 * 数据层
 * IP地域信息表
 *
 * @package   NICMS
 * @category  app\common\model
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
/*
DROP TABLE IF EXISTS `nc_ipinfo`;
CREATE TABLE IF NOT EXISTS `nc_ipinfo` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `ip` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'IP',
    `country_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '国家',
    `province_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '省',
    `city_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '市',
    `area_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '区',
    `isp` varchar(20) NOT NULL DEFAULT '' COMMENT '运营商',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '修改时间',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `ip` (`ip`),
    INDEX `country_id` (`country_id`) USING BTREE,
    INDEX `province_id` (`province_id`) USING BTREE,
    INDEX `city_id` (`city_id`) USING BTREE,
    INDEX `area_id` (`area_id`) USING BTREE,
    INDEX `update_time` (`update_time`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='IP地域信息';
 */

namespace app\common\model;

use think\Model;

class IpInfo extends Model
{
    protected $name = 'ipinfo';
    protected $autoWriteTimestamp = true;
    protected $updateTime = 'update_time';
    protected $dateFormat = false;
    protected $pk = 'id';
    protected $type = [
        'country_id'  => 'integer',
        'province_id' => 'integer',
        'city_id'     => 'integer',
        'area_id'     => 'integer'
    ];
    protected $field = [
        'id',
        'ip',
        'country_id',
        'province_id',
        'city_id',
        'area_id',
        'isp',
        'update_time',
        'create_time'
    ];
}
