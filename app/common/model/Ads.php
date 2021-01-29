<?php

/**
 *
 * 数据层
 * 广告表
 *
 * @package   NICMS
 * @category  app\common\model
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
/*
DROP TABLE IF EXISTS `nc_ads`;
CREATE TABLE IF NOT EXISTS `nc_ads` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL DEFAULT '' COMMENT '广告名',
    `width` smallint(6) NOT NULL DEFAULT '0' COMMENT '图片宽',
    `height` smallint(6) NOT NULL DEFAULT '0' COMMENT '图片高',
    `image` varchar(255) NOT NULL DEFAULT '' COMMENT '图片',
    `url` varchar(500) NOT NULL DEFAULT '' COMMENT '跳转链接',
    `description` varchar(300) NOT NULL DEFAULT '' COMMENT '描述',
    `is_pass` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '审核',
    `hits` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '点击量',
    `start_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '开始时间',
    `end_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '结束时间',
    `update_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '修改时间',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建时间',
    `lang` varchar(20) NOT NULL DEFAULT 'zh-cn' COMMENT '语言',
    PRIMARY KEY (`id`),
    INDEX `start_time` (`start_time`),
    INDEX `end_time` (`end_time`),
    INDEX `lang` (`lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='广告表';
 */

namespace app\common\model;

use think\Model;

class Ads extends Model
{
    protected $name = 'ads';
    protected $autoWriteTimestamp = true;
    protected $updateTime = 'update_time';
    protected $pk = 'id';
    protected $type = [
        'is_pass' => 'integer',
        'width'   => 'integer',
        'height'  => 'integer',
        'hits'    => 'integer',
    ];
    protected $field = [
        'id',
        'name',
        'is_pass',
        'width',
        'height',
        'image',
        'url',
        'description',
        'hits',
        'start_time',
        'end_time',
        'update_time',
        'create_time',
        'lang',
    ];
}
