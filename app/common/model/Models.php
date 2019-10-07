<?php

/**
 *
 * 数据层
 * 文章模型表
 *
 * @package   NICMS
 * @category  app\common\model
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
/*
DROP TABLE IF EXISTS `nc_model`;
CREATE TABLE IF NOT EXISTS `nc_model` (
  `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL DEFAULT '' COMMENT '模型名',
  `table_name` varchar(20) NOT NULL DEFAULT '' COMMENT '表名',
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT '状态',
  `remark` varchar(50) NOT NULL DEFAULT '' COMMENT '备注',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='模型表';
INSERT INTO `nc_model` (`id`, `name`, `table_name`, `remark`, `status`) VALUES
(1, 'article', 'article', '文章模型', 1),
(2, 'picture', 'article_image', '图片模型', 1),
(3, 'download', 'article_file', '下载模型', 1),
(4, 'page', 'page', '单页模型', 1),
(5, 'feedback', 'feedback', '反馈模型', 1),
(6, 'message', 'message', '留言模型', 1),
(7, 'link', 'link', '友链模型', 1),
(8, 'external', 'external', '外部模型', 1);
 */

namespace app\common\model;

use think\Model;

class Models extends Model
{
    protected $name = 'model';
    protected $autoWriteTimestamp = false;
    protected $updateTime = false;
    protected $pk = 'id';
    protected $type = [
        'status' => 'integer'
    ];
    protected $field = [
        'id',
        'name',
        'table_name',
        'status',
        'remark'
    ];
}
