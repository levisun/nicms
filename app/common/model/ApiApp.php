<?php

/**
 *
 * 数据层
 * Api应用表
 *
 * @package   NICMS
 * @category  app\common\model
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
/*
DROP TABLE IF EXISTS `nc_api_app`;
CREATE TABLE `nc_api_app` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(20) DEFAULT '' COMMENT '应用名',
    `secret` varchar(40) DEFAULT '' COMMENT '密钥',
    `authkey` varchar(20) DEFAULT '' COMMENT '鉴权key',
    `status` tinyint(1) DEFAULT '1' COMMENT '0关闭1开启',
    `remark` varchar(500) DEFAULT '' COMMENT '备注',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '修改时间',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`) USING BTREE,
    INDEX `status` (`status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='api应用表';
INSERT INTO `nc_api_app` (`id`, `name`, `secret`, `authkey`, `status`, `remark`, `update_time`, `create_time`) VALUES
(1, 'admin', '962940cfbe94a64efcd1573cf6d7a175', 'admin_auth_key', 1, '', 1505898660, 1505898660),
(2, 'cms', '962940cfbe94a64efcd1573cf6d7a175', 'user_auth_key', 1, '', 1505898660, 1505898660),
(3, 'book', '962940cfbe94a64efcd1573cf6d7a175', 'user_auth_key', 1, '', 1505898660, 1505898660),
(4, 'user', '962940cfbe94a64efcd1573cf6d7a175', 'user_auth_key', 1, '', 1505898660, 1505898660);
 */

namespace app\common\model;

use think\Model;

class ApiApp extends Model
{
    protected $name = 'api_app';
    protected $autoWriteTimestamp = true;
    protected $updateTime = 'update_time';
    protected $pk = 'id';
    protected $type = [
        'remark' => 'integer',
    ];
    protected $field = [
        'id',
        'name',
        'secret',
        'module',
        'status',
        'remark',
        'update_time',
        'create_time'
    ];
}
