<?php

/**
 *
 * 数据层
 * 会员等级表
 *
 * @package   NICMS
 * @category  app\common\model
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
/*
DROP TABLE IF EXISTS `nc_user`;
CREATE TABLE IF NOT EXISTS `nc_user` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `username` varchar(20) NOT NULL DEFAULT '' COMMENT '用户名',
    `password` varchar(60) NOT NULL DEFAULT '' COMMENT '密码',
    `email` varchar(40) NOT NULL DEFAULT '' COMMENT '邮箱',
    `realname` varchar(50) NOT NULL DEFAULT '' COMMENT '真实姓名',
    `nickname` varchar(50) NOT NULL DEFAULT '' COMMENT '昵称',
    `avatar` varchar(255) NOT NULL DEFAULT '' COMMENT '头像',
    `gender` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '性别',
    `birthday` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '生日',
    `level_id` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '等级ID',
    `province_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '省',
    `city_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '市',
    `area_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '区',
    `address` varchar(255) NOT NULL DEFAULT '' COMMENT '地址',
    `phone` varchar(11) NOT NULL DEFAULT '' COMMENT '电话',
    `salt` char(6) NOT NULL COMMENT '佐料',
    `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态',
    `last_login_ip` varchar(15) NOT NULL DEFAULT '' COMMENT '登录IP',
    `last_login_ip_attr` varchar(255) NOT NULL DEFAULT '' COMMENT '登录IP地区',
    `last_login_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '登录时间',
    `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '修改时间',
    `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `username` (`username`),
    UNIQUE KEY `email` (`email`),
    UNIQUE KEY `phone` (`phone`),
    KEY `password` (`password`) USING BTREE,
    KEY `gender` (`gender`) USING BTREE,
    KEY `birthday` (`birthday`) USING BTREE,
    KEY `level_id` (`level_id`) USING BTREE,
    KEY `province_id` (`province_id`) USING BTREE,
    KEY `city_id` (`city_id`) USING BTREE,
    KEY `area_id` (`area_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT '用户';

DROP TABLE IF EXISTS `nc_user_oauth`;
CREATE TABLE IF NOT EXISTS `nc_user_oauth` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
    `openid` varchar(50) NOT NULL DEFAULT '' COMMENT 'openid',
    `nick` varchar(50) NOT NULL DEFAULT '' COMMENT '昵称',
    `type` varchar(10) NOT NULL DEFAULT '' COMMENT '类型',
    `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`) USING BTREE,
    KEY `openid` (`openid`) USING BTREE,
    KEY `type` (`type`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT '第三方登录用户';

DROP TABLE IF EXISTS `nc_user_wechat`;
CREATE TABLE IF NOT EXISTS `nc_user_wechat` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
    `appid` varchar(32) NOT NULL DEFAULT '' COMMENT 'APPID',
    `appname` varchar(32) NOT NULL DEFAULT '' COMMENT 'APP NAME',
    `subscribe` tinyint(1) NOT NULL DEFAULT '0' COMMENT '关注状态',
    `openid` varchar(32) NOT NULL DEFAULT '' COMMENT '用户标识',
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
    KEY `user_id` (`user_id`) USING BTREE,
    KEY `appid` (`appid`) USING BTREE,
    KEY `appname` (`appname`) USING BTREE,
    UNIQUE KEY `openid` (`openid`),
    KEY `unionid` (`unionid`) USING BTREE
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
        'gender'      => 'integer',
        'birthday'    => 'integer',
        'level_id'    => 'integer',
        'province_id' => 'integer',
        'city_id'     => 'integer',
        'area_id'     => 'integer',
        'status'      => 'integer'
    ];
    protected $field = [
        'id',
        'username',
        'password',
        'email',
        'realname',
        'nickname',
        'portrait',
        'gender',
        'birthday',
        'level_id',
        'province_id',
        'city_id',
        'area_id',
        'address',
        'phone',
        'salt',
        'status',
        'last_login_ip',
        'last_login_ip_attr',
        'last_login_time',
        'update_time',
        'create_time'
    ];
}
