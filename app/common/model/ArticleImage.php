<?php

/**
 *
 * 数据层
 * 文章图集
 *
 * @package   NICMS
 * @category  app\common\model
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
/*
DROP TABLE IF EXISTS `nc_article_image`;
CREATE TABLE `nc_article_image` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `article_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '文章ID',
    `image_url` text NOT NULL COMMENT '图片存储路径',
    `image_width` smallint(6) DEFAULT '0' COMMENT '图片宽度',
    `image_height` smallint(6) DEFAULT '0' COMMENT '图片高度',
    PRIMARY KEY (`id`),
    INDEX `article_id` (`article_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文章图集图片表';
 */

namespace app\common\model;

use think\Model;

class ArticleImage extends Model
{
    protected $name = 'article_image`';
    // protected $autoWriteTimestamp = true;
    protected $updateTime = false;
    // protected $defaultSoftDelete = 0;
    protected $pk = 'id';
    protected $type = [
        'article_id' => 'integer',
    ];
    protected $field = [
        'id',
        'article_id',
        'image_url',
        'image_width',
        'image_height',
        'image_size',
        'image_mime',
        'sort_order',
        'create_time',
    ];
}
