<?php

/**
 *
 * 数据层
 * 评论表
 *
 * @package   NICMS
 * @category  app\common\model
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
/*
DROP TABLE IF EXISTS `nc_discuss`;
CREATE TABLE IF NOT EXISTS `nc_discuss` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `pid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '父ID',
    `article_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '文章ID',
    `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
    `content` varchar(300) NOT NULL DEFAULT '' COMMENT '评论内容',
    `is_pass` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '审核',
    `is_report` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '举报',
    `support` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '支持',
    `report_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '举报时间',
    `ip` varchar(15) NOT NULL DEFAULT '' COMMENT '评论IP',
    `ip_attr` varchar(255) NOT NULL DEFAULT '' COMMENT '评论IP地区',
    `delete_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '删除时间',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `pid` (`pid`) USING BTREE,
    KEY `article_id` (`article_id`) USING BTREE,
    KEY `user_id` (`user_id`) USING BTREE,
    KEY `is_pass` (`is_pass`) USING BTREE,
    KEY `is_report` (`is_report`) USING BTREE,
    KEY `report_time` (`report_time`) USING BTREE,
    KEY `delete_time` (`delete_time`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='评论表';
 */

namespace app\common\model;

use think\Model;

class Discuss extends Model
{
    protected $name = 'discuss';
    protected $autoWriteTimestamp = true;
    protected $updateTime = 'update_time';
    protected $pk = 'id';
    protected $type = [
        'pid'        => 'integer',
        'article_id' => 'integer',
        'user_id'    => 'integer',
        'is_pass'    => 'integer',
        'is_report'  => 'integer',
    ];
    protected $field = [
        'id',
        'pid',
        'article_id',
        'user_id',
        'content',
        'is_pass',
        'is_report',
        'support',
        'report_time',
        'ip',
        'ip_attr',
        'delete_time',
        'create_time',
    ];
}
