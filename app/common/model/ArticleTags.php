<?php

/**
 *
 * 数据层
 * 文章标签关联表
 *
 * @package   NICMS
 * @category  app\common\model
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
/*
DROP TABLE IF EXISTS `nc_article_tags`;
CREATE TABLE IF NOT EXISTS `nc_article_tags` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `tags_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '标签ID',
    `article_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '文章ID',
    PRIMARY KEY (`id`),
    INDEX `tags_id` (`tags_id`) USING BTREE,
    INDEX `article_id` (`article_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文章标签关联表';
 */

namespace app\common\model;

use think\Model;

class ArticleTags extends Model
{
    protected $name = 'article_tags';
    protected $autoWriteTimestamp = false;
    protected $updateTime = false;
    protected $pk = 'id';
    protected $type = [
        'tags_id'     => 'integer',
        'article_id'  => 'integer',
    ];
    protected $field = [
        'id',
        'tags_id',
        'article_id',
    ];
}
