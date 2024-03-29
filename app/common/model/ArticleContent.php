<?php

/**
 *
 * 数据层
 * 文章内容
 *
 * @package   NICMS
 * @category  app\common\model
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
/*
DROP TABLE IF EXISTS `nc_article_content`;
CREATE TABLE `nc_article_content` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `article_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '文章ID',
    `origin` varchar(200) NOT NULL DEFAULT '' COMMENT '来源',
    `content` longtext NOT NULL COMMENT '内容详情',
    PRIMARY KEY (`id`),
    UNIQUE KEY `article_id` (`article_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文章内容表';
 */

namespace app\common\model;

use think\Model;

class ArticleContent extends Model
{
    protected $name = 'article_content';
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
        'origin',
        'content',
    ];
}
