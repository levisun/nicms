<?php

/**
 *
 * 数据层
 * 配置表
 *
 * @package   NICMS
 * @category  app\common\model
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
/*
DROP TABLE IF EXISTS `nc_config`;
CREATE TABLE IF NOT EXISTS `nc_config` (
    `id` mediumint(9) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` varchar(30) NOT NULL DEFAULT '' COMMENT '名称',
    `value` varchar(3000) NOT NULL DEFAULT '' COMMENT '值',
    `lang` varchar(10) NOT NULL DEFAULT '' COMMENT '语言',
    PRIMARY KEY (`id`),
    INDEX `name` (`name`) USING BTREE,
    INDEX `lang` (`lang`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='设置表';
INSERT INTO `nc_config` (`name`, `value`, `lang`) VALUES
('cms_theme', 'default', 'zh-cn'),
('cms_sitename', '腐朽的木屋', 'zh-cn'),
('cms_keywords', 'php, javascript, js, html, css, thinkphp, tp', 'zh-cn'),
('cms_description', '开发WEB应用时的笔记、问题和学习资料。', 'zh-cn'),

('book_theme', 'default', 'zh-cn'),
('book_sitename', '腐朽的书屋', 'zh-cn'),
('book_keywords', '', 'zh-cn'),
('book_description', '', 'zh-cn'),

('user_theme', 'default', 'zh-cn'),
('user_sitename', '会员中心', 'zh-cn'),
('user_keywords', '', 'zh-cn'),
('user_description', '', 'zh-cn'),

('copyright', 'copyright &amp;copy; 2014-2015 &lt;a href=&quot;//www.niphp.com&quot; target=&quot;_blank&quot;&gt;niphp.com&lt;/a&gt;版权所有', 'zh-cn'),
('bottom', '', 'zh-cn'),
('beian', '陕icp备15001502号-1', 'zh-cn'),
('script', '', 'zh-cn');
 */

namespace app\common\model;

use think\Model;

class Config extends Model
{
    protected $name = 'config';
    protected $updateTime = false;
    protected $pk = 'id';
    protected $field = [
        'id',
        'name',
        'value',
        'lang'
    ];
}
