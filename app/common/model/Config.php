<?php

/**
 *
 * 数据层
 * 配置表
 *
 * @package   NICMS
 * @category  app\common\model
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
/*
DROP TABLE IF EXISTS `nc_config`;
CREATE TABLE IF NOT EXISTS `nc_config` (
    `id` smallint(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` varchar(30) NOT NULL DEFAULT '' COMMENT '名称',
    `value` varchar(500) NOT NULL DEFAULT '' COMMENT '值',
    `lang` varchar(10) NOT NULL DEFAULT '' COMMENT '语言 niphp为全局设置',
    PRIMARY KEY (`id`),
    KEY `name` (`name`) USING BTREE,
    KEY `value` (`value`) USING BTREE,
    KEY `lang` (`lang`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='设置表';
 */

namespace app\common\model;

use think\Model;

class Config extends Model
{
    protected $name = 'config';
    protected $updateTime = false;
    protected $pk = 'id';
    protected $field = [
        'id',
        'name',
        'value',
        'lang'
    ];
}
