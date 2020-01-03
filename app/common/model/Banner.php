<?php

/**
 *
 * 数据层
 * 幻灯片(焦点图)表
 *
 * @package   NICMS
 * @category  app\common\model
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
/*
DROP TABLE IF EXISTS `nc_banner`;
CREATE TABLE IF NOT EXISTS `nc_banner` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `pid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '父ID',
    `name` varchar(255) NOT NULL DEFAULT '' COMMENT '幻灯片名',
    `title` varchar(255) NOT NULL DEFAULT '' COMMENT '图片标题',
    `width` smallint(4) NOT NULL DEFAULT '0' COMMENT '图片宽',
    `height` smallint(4) NOT NULL DEFAULT '0' COMMENT '图片高',
    `image` varchar(255) NOT NULL DEFAULT '' COMMENT '图片',
    `url` varchar(500) NOT NULL DEFAULT '' COMMENT '跳转链接',
    `hits` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '点击量',
    `sort_order` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
    `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '修改时间',
    `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
    `lang` varchar(20) NOT NULL DEFAULT 'zh-cn' COMMENT '语言',
    PRIMARY KEY (`id`),
    KEY `pid` (`pid`),
    KEY `lang` (`lang`)
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
        'pid'        => 'integer',
        'width'      => 'integer',
        'height'     => 'integer',
        'hits'       => 'integer',
        'sort_order' => 'integer',
    ];
    protected $field = [
        'id',
        'pid',
        'name',
        'title',
        'width',
        'height',
        'image',
        'url',
        'hits',
        'sort_order',
        'update_time',
        'create_time',
        'lang',
    ];
}
