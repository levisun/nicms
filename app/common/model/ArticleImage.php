<?php

/**
 *
 * 数据层
 * 文章图集
 *
 * @package   NICMS
 * @category  app\common\model
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
/*
DROP TABLE IF EXISTS `nc_article_image`;
CREATE TABLE `nc_article_image` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `article_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '文章ID',
  `image_url` varchar(255) DEFAULT '' COMMENT '图片存储路径',
  `image_width` smallint(5) DEFAULT '0' COMMENT '图片宽度',
  `image_height` smallint(5) DEFAULT '0' COMMENT '图片高度',
  `image_size` mediumint(8) unsigned DEFAULT '0' COMMENT '文件大小',
  `image_mime` varchar(50) DEFAULT '' COMMENT '图片类型',
  `sort_order` smallint(5) DEFAULT '0' COMMENT '排序',
  `create_time` int(10) unsigned DEFAULT '0' COMMENT '上传时间',
  PRIMARY KEY (`id`),
  KEY `article_id` (`article_id`) USING BTREE
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
