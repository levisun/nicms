<?php

/**
 *
 * 数据层
 * 自定义字段扩展表
 *
 * @package   NICMS
 * @category  app\common\model
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
/*
DROP TABLE IF EXISTS `nc_fields_extend`;
CREATE TABLE IF NOT EXISTS `nc_fields_extend` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `article_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '文章ID',
    `fields_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '字段ID',
    `data` varchar(500) NOT NULL COMMENT '内容',
    PRIMARY KEY (`id`),
    INDEX `article_id` (`article_id`) USING BTREE,
    INDEX `fields_id` (`fields_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='自定义字段扩展表';
 */

namespace app\common\model;

use think\Model;

class FieldsExtend extends Model
{
    protected $name = 'fields_extend';
    protected $autoWriteTimestamp = false;
    protected $updateTime = false;
    protected $pk = 'id';
    protected $type = [
        'article_id' => 'integer',
        'fields_id'  => 'integer',
    ];
    protected $field = [
        'id',
        'article_id',
        'fields_id',
        'data'
    ];
}
