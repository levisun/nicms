<?php

/**
 *
 * 数据层
 * 会员详细信息表
 *
 * @package   NICMS
 * @category  app\common\model
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
/*
DROP TABLE IF EXISTS `nc_user_info`;
CREATE TABLE IF NOT EXISTS `nc_user_info` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '用户ID',
    `realname` varchar(50) NOT NULL DEFAULT '' COMMENT '真实姓名',
    `nickname` varchar(50) NOT NULL DEFAULT '' COMMENT '昵称',
    `avatar` varchar(255) NOT NULL DEFAULT '' COMMENT '头像',
    `gender` tinyint(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT '性别',
    `birthday` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '生日',
    `country_id` mediumint(9) UNSIGNED NOT NULL DEFAULT '0' COMMENT '国家',
    `province_id` mediumint(9) UNSIGNED NOT NULL DEFAULT '0' COMMENT '省',
    `city_id` mediumint(9) UNSIGNED NOT NULL DEFAULT '0' COMMENT '市',
    `area_id` mediumint(9) UNSIGNED NOT NULL DEFAULT '0' COMMENT '区',
    `address` varchar(255) NOT NULL DEFAULT '' COMMENT '地址',
    PRIMARY KEY (`id`),
    INDEX `gender` (`gender`) USING BTREE,
    INDEX `country_id` (`country_id`) USING BTREE,
    INDEX `birthday` (`birthday`) USING BTREE,
    INDEX `province_id` (`province_id`) USING BTREE,
    INDEX `city_id` (`city_id`) USING BTREE,
    INDEX `area_id` (`area_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT '会员详细信息';
 */

namespace app\common\model;

use think\Model;

class UserInfo extends Model
{
    protected $name = 'user_info';
    protected $autoWriteTimestamp = true;
    protected $updateTime = false;
    protected $dateFormat = false;
    protected $pk = 'id';
    protected $type = [
        'user_id'     => 'integer',
        'gender'      => 'integer',
        'birthday'    => 'integer',
        'country_id'  => 'integer',
        'province_id' => 'integer',
        'city_id'     => 'integer',
        'area_id'     => 'integer',
    ];
    protected $field = [
        'id',
        'user_id',
        'realname',
        'nickname',
        'avatar',
        'gender',
        'birthday',
        'country_id',
        'province_id',
        'city_id',
        'area_id',
        'address',
    ];
}
