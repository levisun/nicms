<?php

/**
 *
 * 数据层
 * 书籍作者表
 *
 * @package   NICMS
 * @category  app\common\model
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
/*
DROP TABLE IF EXISTS `nc_book_author`;
CREATE TABLE IF NOT EXISTS `nc_book_author` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '用户ID',
    `author` varchar(50) NOT NULL DEFAULT '' COMMENT '作者名',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建时间',
    PRIMARY KEY (`id`),
    INDEX `user_id` (`user_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT '书籍作者表';
 */

namespace app\common\model;

use think\Model;

class BookAuthor extends Model
{
    protected $name = 'book_author';
    protected $autoWriteTimestamp = true;
    protected $updateTime = false;
    protected $dateFormat = false;
    protected $pk = 'id';
    protected $type = [
        'user_id'    => 'integer',
    ];
    protected $field = [
        'id',
        'user_id',
        'author',
        'create_time'
    ];
}
