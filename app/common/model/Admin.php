<?php

/**
 *
 * 数据层
 * 管理员
 *
 * @package   NICMS
 * @category  app\common\model
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
/*
DROP TABLE IF EXISTS `nc_admin`;
CREATE TABLE IF NOT EXISTS `nc_admin` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` varchar(20) NOT NULL DEFAULT '' COMMENT '用户名',
    `password` varchar(60) NOT NULL DEFAULT '' COMMENT '密码',
    `salt` char(6) NOT NULL DEFAULT '' COMMENT '佐料',
    `phone` varchar(11) NOT NULL DEFAULT '' COMMENT '电话',
    `email` varchar(40) NOT NULL DEFAULT '' COMMENT '邮箱',
    `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态 1正常 0停用',
    `flag` varchar(40) NOT NULL DEFAULT '' COMMENT '登录标识',
    `last_login_ip` varchar(15) NOT NULL DEFAULT '' COMMENT '登录IP',
    `last_login_ip_attr` varchar(100) NOT NULL DEFAULT '' COMMENT '登录IP地区',
    `last_login_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '登录时间',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '修改时间',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `username` (`username`),
    UNIQUE KEY `phone` (`phone`),
    UNIQUE KEY `email` (`email`),
    INDEX `password` (`password`) USING BTREE,
    INDEX `status` (`status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员表';
INSERT INTO `nc_admin` (`id`, `username`, `password`, `phone`, `email`, `salt`, `last_login_ip`, `last_login_ip_attr`, `last_login_time`, `update_time`, `create_time`) VALUES
(1, 'levisun', '$2y$11$d.FUHJoQT8EEsuJVv9GOQ.D6.GquPRJHb.7VfU89yugmVwzxzo6qG', '18629503709', 'levisun@mail.com', '0af476', '', '', 1556499533, 1556499533, 1556499533);
 */

namespace app\common\model;

use think\Model;

class Admin extends Model
{
    protected $name = 'admin';
    protected $autoWriteTimestamp = true;
    protected $updateTime = 'update_time';
    protected $pk = 'id';
    protected $field = [
        'id',
        'username',
        'password',
        'phone',
        'email',
        'salt',
        'last_login_ip',
        'last_login_ip_attr',
        'last_login_time',
        'update_time',
        'create_time'
    ];
}
