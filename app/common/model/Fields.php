<?php

/**
 *
 * 数据层
 * 附加字段表
 *
 * @package   NICMS
 * @category  app\common\model
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
/*
DROP TABLE IF EXISTS `nc_fields`;
CREATE TABLE IF NOT EXISTS `nc_fields` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '栏目ID',
  `type_id` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '类型ID',
  `name` varchar(20) NOT NULL DEFAULT '' COMMENT '字段名',
  `maxlength` smallint(5) NOT NULL DEFAULT '500' COMMENT '最大长度',
  `is_require` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '必填',
  `sort_order` smallint(5) NOT NULL DEFAULT '0' COMMENT '排序',
  `remark` varchar(100) NOT NULL DEFAULT '' COMMENT '描述',
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`) USING BTREE,
  KEY `type_id` (`type_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='自定义字段表';
 */

namespace app\common\model;

use think\Model;

class Fields extends Model
{
    protected $name = 'fields';
    protected $autoWriteTimestamp = false;
    protected $updateTime = false;
    protected $pk = 'id';
    protected $type = [
        'category_id' => 'integer',
        'type_id'     => 'integer',
        'is_require'  => 'integer'
    ];
    protected $field = [
        'id',
        'category_id',
        'type_id',
        'name',
        'description',
        'is_require',
    ];
}
