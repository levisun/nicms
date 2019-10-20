<?php

/**
 *
 * 数据层
 * 管理员组表
 *
 * @package   NICMS
 * @category  app\common\model
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
/*
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
INSERT INTO `np_role` (`id`, `pid`, `name`, `status`, `remark`) VALUES
(1, 0, '创始人', 1, '创始人');

DROP TABLE IF EXISTS `nc_role_access`;
CREATE TABLE IF NOT EXISTS `nc_role_access` (
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

class Role extends Model
{
    protected $name = 'role';
    protected $autoWriteTimestamp = false;
    protected $updateTime = false;
    protected $pk = 'id';
    protected $type = [
        'pid'        => 'integer',
        'status'     => 'integer',
    ];
    protected $field = [
        'id',
        'pid',
        'name',
        'status',
        'remark',
    ];
}
