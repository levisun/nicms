<?php

/**
 *
 * 数据层
 * 管理员组表
 *
 * @package   NICMS
 * @category  app\common\model
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
/*
DROP TABLE IF EXISTS `nc_role_access`;
CREATE TABLE IF NOT EXISTS `nc_role_access` (
    `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT,
    `role_id` smallint(5) UNSIGNED NOT NULL DEFAULT '0' COMMENT '组ID',
    `node_id` smallint(5) UNSIGNED NOT NULL DEFAULT '0' COMMENT '节点ID',
    PRIMARY KEY (`id`),
    INDEX `role_id` (`role_id`) USING BTREE,
    INDEX `node_id` (`node_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='权限表';
*/
namespace app\common\model;

use think\Model;

class RoleAccess extends Model
{
    protected $name = 'role_access';
    protected $autoWriteTimestamp = false;
    protected $updateTime = false;
    protected $pk = 'id';
    protected $type = [
        'role_id' => 'integer',
        'node_id' => 'integer',
    ];
    protected $field = [
        'id',
        'role_id',
        'node_id',
    ];
}
