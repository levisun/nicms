<?php

/**
 *
 * 数据层
 * IPV4地域信息表
 *
 * @package   NICMS
 * @category  app\common\model
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
/*
DROP TABLE IF EXISTS `nc_ipv4`;
CREATE TABLE IF NOT EXISTS `nc_ipv4` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `country_id` mediumint(9) UNSIGNED NOT NULL DEFAULT '0' COMMENT '国家',
    `province_id` mediumint(9) UNSIGNED NOT NULL DEFAULT '0' COMMENT '省',
    `city_id` mediumint(9) UNSIGNED NOT NULL DEFAULT '0' COMMENT '市',
    `area_id` mediumint(9) UNSIGNED NOT NULL DEFAULT '0' COMMENT '区',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '修改时间',
    PRIMARY KEY (`id`),
    INDEX `country_id` (`country_id`) USING BTREE,
    INDEX `province_id` (`province_id`) USING BTREE,
    INDEX `city_id` (`city_id`) USING BTREE,
    INDEX `area_id` (`area_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='IPV4地域信息';
 */

namespace app\common\model;

use think\Model;

class Ipv4 extends Model
{
    protected $name = 'ipv4';
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
        'country_id',
        'province_id',
        'city_id',
        'area_id',
        'isp',
        'update_time',
    ];
}
