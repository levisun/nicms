<?php

/**
 *
 * 数据层
 * 管理员
 *
 * @package   NICMS
 * @category  app\common\model
 * @author    失眠小枕头 [levisun.mail@gmail.com]
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
  `email` varchar(40) NOT NULL DEFAULT '' COMMENT '邮箱',
  `salt` char(6) NOT NULL DEFAULT '' COMMENT '佐料',
  `flag` varchar(40) NOT NULL DEFAULT '' COMMENT '登录标识',
  `last_login_ip` varchar(15) NOT NULL DEFAULT '' COMMENT '登录IP',
  `last_login_ip_attr` varchar(100) NOT NULL DEFAULT '' COMMENT '登录IP地区',
  `last_login_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '登录时间',
  `update_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '修改时间',
  `create_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `password` (`password`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员表';
INSERT INTO `nc_admin` (`id`, `username`, `password`, `email`, `salt`, `last_login_ip`, `last_login_ip_attr`, `last_login_time`, `update_time`, `create_time`) VALUES
(1, 'levisun', '$2y$11$d.FUHJoQT8EEsuJVv9GOQ.D6.GquPRJHb.7VfU89yugmVwzxzo6qG', 'levisun@mail.com', '0af476', '', '', 1556499533, 1556499533, 1556499533);

DROP TABLE IF EXISTS `nc_role_admin`;
CREATE TABLE IF NOT EXISTS `nc_role_admin` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '管理员ID',
  `role_id` smallint(5) UNSIGNED NOT NULL DEFAULT '0' COMMENT '组ID',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`) USING BTREE,
  KEY `group_id` (`role_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员组关系表';
INSERT INTO `nc_role_admin` (`id`, `user_id`, `role_id`) VALUES
(1, 1, 1);

DROP TABLE IF EXISTS `nc_role`;
CREATE TABLE IF NOT EXISTS `nc_role` (
  `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pid` smallint(5) UNSIGNED NOT NULL DEFAULT '0' COMMENT '父ID',
  `name` varchar(20) NOT NULL DEFAULT '' COMMENT '组名',
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '状态',
  `remark` varchar(100) NOT NULL DEFAULT '' COMMENT '描述',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`) USING BTREE,
  KEY `status` (`status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='组表';
INSERT INTO `nc_role` (`id`, `pid`, `name`, `status`, `remark`) VALUES
(1, 0, '创始人', 1, '创始人');

DROP TABLE IF EXISTS `nc_access`;
CREATE TABLE IF NOT EXISTS `nc_access` (
  `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id` smallint(5) UNSIGNED NOT NULL DEFAULT '0' COMMENT '组ID',
  `node_id` smallint(5) UNSIGNED NOT NULL DEFAULT '0' COMMENT '节点ID',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态',
  `level` tinyint(1) NOT NULL DEFAULT '0' COMMENT '节点等级',
  `module` varchar(20) NOT NULL DEFAULT '' COMMENT '节点名',
  PRIMARY KEY (`id`),
  KEY `role_id` (`role_id`) USING BTREE,
  KEY `node_id` (`node_id`) USING BTREE,
  KEY `status` (`status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='权限表';
 */

namespace app\common\model;

use think\Model;

class Admin extends Model
{
    protected $name = 'admin';
    protected $autoWriteTimestamp = false;
    protected $updateTime = 'update_time';
    protected $pk = 'id';
    protected $field = [
        'id',
        'username',
        'password',
        'email',
        'salt',
        'last_login_ip',
        'last_login_ip_attr',
        'last_login_time',
        'update_time',
        'create_time'
    ];
}
