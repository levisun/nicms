<?php

/**
 *
 * 数据层
 * 文章分类表
 *
 * @package   NICMS
 * @category  app\common\model
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
/*
DROP TABLE IF EXISTS `nc_type`;
CREATE TABLE IF NOT EXISTS `nc_type` (
    `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT,
    `category_id` smallint(5) UNSIGNED NOT NULL DEFAULT '0' COMMENT '栏目ID',
    `name` varchar(100) NOT NULL DEFAULT '' COMMENT '分类名',
    `remark` varchar(300) NOT NULL DEFAULT '' COMMENT '描述',
    PRIMARY KEY (`id`),
    INDEX `category_id` (`category_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='分类';
 */

namespace app\common\model;

use think\Model;

class Type extends Model
{
    protected $name = 'type';
    protected $autoWriteTimestamp = false;
    protected $updateTime = false;
    protected $pk = 'id';
    protected $type = [
        'category_id' => 'integer',
    ];
    protected $field = [
        'id',
        'category_id',
        'name',
        'remark',
    ];
}
