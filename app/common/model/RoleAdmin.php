<?php

/**
 *
 * 数据层
 * 管理员组关系表
 *
 * @package   NICMS
 * @category  app\common\model
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
/*
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
*/
namespace app\common\model;

use think\Model;

class RoleAdmin extends Model
{
    protected $name = 'role_access';
    protected $autoWriteTimestamp = false;
    protected $updateTime = false;
    protected $pk = 'id';
    protected $type = [
        'user_id' => 'integer',
        'role_id' => 'integer',
    ];
    protected $field = [
        'id',
        'user_id',
        'role_id',
    ];
}
