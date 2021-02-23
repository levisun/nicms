<?php

/**
 *
 * 数据层
 * 幻灯片(焦点图)表
 *
 * @package   NICMS
 * @category  app\common\model
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
/*
DROP TABLE IF EXISTS `nc_banner`;
CREATE TABLE IF NOT EXISTS `nc_banner` (
    `id` mediumint(9) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL DEFAULT '' COMMENT '幻灯片名',
    `description` varchar(300) NOT NULL DEFAULT '' COMMENT '描述',
    `width` smallint(6) UNSIGNED NOT NULL DEFAULT '0' COMMENT '图片宽',
    `height` smallint(6) UNSIGNED NOT NULL DEFAULT '0' COMMENT '图片高',
    `image_url` varchar(255) NOT NULL DEFAULT '' COMMENT '图片',
    `url` varchar(500) NOT NULL DEFAULT '' COMMENT '跳转链接',
    `hits` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '点击量',
    `is_pass` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '审核',
    `sort_order` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '排序',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '修改时间',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建时间',
    `lang` varchar(20) NOT NULL DEFAULT 'zh-cn' COMMENT '语言',
    PRIMARY KEY (`id`),
    INDEX `pid` (`pid`) USING BTREE,
    INDEX `sort_order` (`sort_order`) USING BTREE,
    INDEX `update_time` (`update_time`) USING BTREE,
    INDEX `lang` (`lang`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='幻灯片表';
 */

namespace app\common\model;

use think\Model;

class Banner extends Model
{
    protected $name = 'banner';
    protected $autoWriteTimestamp = true;
    protected $updateTime = 'update_time';
    protected $pk = 'id';
    protected $type = [
        'width'      => 'integer',
        'height'     => 'integer',
        'hits'       => 'integer',
        'is_pass'    => 'integer',
        'sort_order' => 'integer',
    ];
    protected $field = [
        'id',
        'name',
        'description',
        'width',
        'height',
        'image_url',
        'url',
        'hits',
        'is_pass',
        'sort_order',
        'update_time',
        'create_time',
        'lang',
    ];
}
