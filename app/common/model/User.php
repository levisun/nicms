<?php

/**
 *
 * 数据层
 * 会员表
 *
 * @package   NICMS
 * @category  app\common\model
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
/*
DROP TABLE IF EXISTS `nc_user`;
CREATE TABLE IF NOT EXISTS `nc_user` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` varchar(20) NOT NULL DEFAULT '' COMMENT '用户名',
    `password` varchar(60) NOT NULL DEFAULT '' COMMENT '密码',
    `phone` varchar(11) NOT NULL DEFAULT '' COMMENT '电话',
    `email` varchar(40) NOT NULL DEFAULT '' COMMENT '邮箱',
    `salt` char(6) NOT NULL COMMENT '佐料',
    `level_id` smallint(6) UNSIGNED NOT NULL DEFAULT '0' COMMENT '等级ID',
    `status` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '状态',
    `last_login_ip` varchar(15) NOT NULL DEFAULT '' COMMENT '登录IP',
    `last_login_ip_attr` varchar(255) NOT NULL DEFAULT '' COMMENT '登录IP地区',
    `last_login_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '登录时间',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '修改时间',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `username` (`username`),
    UNIQUE KEY `phone` (`phone`),
    UNIQUE KEY `email` (`email`),
    INDEX `password` (`password`) USING BTREE,
    INDEX `level_id` (`level_id`) USING BTREE,
    INDEX `status` (`status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT '会员';

DROP TABLE IF EXISTS `nc_user_wechat`;
CREATE TABLE IF NOT EXISTS `nc_user_wechat` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '用户ID',
    `appid` varchar(32) NOT NULL DEFAULT '' COMMENT 'APPID',
    `appname` varchar(32) NOT NULL DEFAULT '' COMMENT 'APP NAME',
    `subscribe` tinyint(1) NOT NULL DEFAULT '0' COMMENT '关注状态',
    `open_id` varchar(32) NOT NULL DEFAULT '' COMMENT '用户标识',
    `nickname` varchar(50) NOT NULL DEFAULT '' COMMENT '昵称',
    `gender` tinyint(1) NOT NULL DEFAULT '0' COMMENT '性别 1男 2女 0未知',
    `city` varchar(10) NOT NULL DEFAULT '' COMMENT '城市',
    `country` varchar(10) NOT NULL DEFAULT '' COMMENT '国家',
    `province` varchar(10) NOT NULL DEFAULT '' COMMENT '省份',
    `language` varchar(10) NOT NULL DEFAULT '' COMMENT '语言',
    `avatar_url` varchar(500) NOT NULL DEFAULT '' COMMENT '头像',
    `subscribe_time` int(11) NOT NULL DEFAULT '0' COMMENT '关注时间',
    `scene_id` varchar(100) NOT NULL DEFAULT '' COMMENT '二维码场景值',
    `unionid` varchar(32) NOT NULL DEFAULT '' COMMENT '',
    `remark` varchar(50) NOT NULL DEFAULT '' COMMENT '备注',
    `groupid` varchar(50) NOT NULL DEFAULT '' COMMENT '分组ID',
    `tagid_list` varchar(500) NOT NULL DEFAULT '' COMMENT '标签ID',
    PRIMARY KEY (`id`),
    INDEX `user_id` (`user_id`) USING BTREE,
    INDEX `appid` (`appid`) USING BTREE,
    INDEX `appname` (`appname`) USING BTREE,
    UNIQUE KEY `openid` (`openid`),
    INDEX `unionid` (`unionid`) USING BTREE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COMMENT '微信用户信息表';
 */

namespace app\common\model;

use think\Model;

class User extends Model
{
    protected $name = 'user';
    protected $autoWriteTimestamp = true;
    protected $updateTime = 'update_time';
    protected $dateFormat = false;
    protected $pk = 'id';
    protected $type = [
        'level_id' => 'integer',
        'status'   => 'integer'
    ];
    protected $field = [
        'id',
        'username',
        'password',
        'email',
        'salt',
        'level_id',
        'status',
        'last_login_ip',
        'last_login_ip_attr',
        'last_login_time',
        'update_time',
        'create_time'
    ];
}
