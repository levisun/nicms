DROP TABLE IF EXISTS `np_article`;
CREATE TABLE IF NOT EXISTS `np_article` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(20) NOT NULL DEFAULT '' COMMENT '标题',
  `keywords` varchar(100) NOT NULL DEFAULT '' COMMENT '关键词',
  `description` varchar(300) NOT NULL DEFAULT '' COMMENT '描述',
  `category_id` smallint(5) UNSIGNED NOT NULL DEFAULT '0' COMMENT '栏目ID',
  `type_id` smallint(5) UNSIGNED NOT NULL DEFAULT '0' COMMENT '类型ID',
  `admin_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '管理员ID',
  `user_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '发布人ID',
  `is_pass` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '审核',
  `is_com` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '推荐',
  `is_top` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '置顶',
  `is_hot` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '最热',
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT '0' COMMENT '排序',
  `hits` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '点击量',
  `username` varchar(20) NOT NULL DEFAULT '' COMMENT '作者名',
  `show_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '显示时间',
  `update_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '修改时间',
  `delete_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '删除时间',
  `create_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建时间',
  `access_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '访问权限',
  `lang` varchar(10) NOT NULL DEFAULT 'zh-cn' COMMENT '语言',
  PRIMARY KEY (`id`),
  KEY `title` (`title`) USING BTREE,
  KEY `category_id` (`category_id`) USING BTREE,
  KEY `type_id` (`type_id`) USING BTREE,
  KEY `is_pass` (`is_pass`) USING BTREE,
  KEY `is_com` (`is_com`) USING BTREE,
  KEY `is_top` (`is_top`) USING BTREE,
  KEY `is_hot` (`is_hot`) USING BTREE,
  KEY `show_time` (`show_time`) USING BTREE,
  KEY `delete_time` (`delete_time`) USING BTREE,
  KEY `lang` (`lang`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文章表';

DROP TABLE IF EXISTS `np_article_content`;
CREATE TABLE `np_article_content` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `article_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '文章ID',
  `thumb` varchar(200) NOT NULL DEFAULT '' COMMENT '缩略图',
  `content` longtext COMMENT '内容详情',
  `origin` varchar(200) NOT NULL DEFAULT '' COMMENT '来源',
  PRIMARY KEY (`id`),
  KEY `article_id` (`article_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文章内容表';

DROP TABLE IF EXISTS `np_article_extend`;
CREATE TABLE IF NOT EXISTS `np_article_extend` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `article_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '文章ID',
  `fields_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '字段ID',
  `data` longtext NOT NULL COMMENT '内容',
  PRIMARY KEY (`id`),
  KEY `article_id` (`article_id`) USING BTREE,
  KEY `fields_id` (`fields_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文章扩展表';

DROP TABLE IF EXISTS `np_article_file`;
CREATE TABLE `np_article_file` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `article_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '文章ID',
  `file_url` varchar(255) DEFAULT '' COMMENT '文件存储路径',
  `file_size` varchar(10) DEFAULT '' COMMENT '文件大小',
  `file_ext` varchar(50) DEFAULT '' COMMENT '文件后缀名',
  `file_name` varchar(100) DEFAULT '' COMMENT '文件名',
  `file_mime` varchar(50) DEFAULT '' COMMENT '文件类型',
  `uhash` varchar(200) DEFAULT '' COMMENT '自定义的一种加密方式，用于文件下载权限验证',
  `md5file` varchar(200) DEFAULT '' COMMENT 'md5_file加密，可以检测上传/下载的文件包是否损坏',
  `sort_order` smallint(5) DEFAULT '0' COMMENT '排序',
  `create_time` int(10) unsigned DEFAULT '0' COMMENT '上传时间',
  PRIMARY KEY (`id`),
  KEY `article_id` (`article_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文章下载附件表';

DROP TABLE IF EXISTS `np_article_image`;
CREATE TABLE `np_article_image` (
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



DROP TABLE IF EXISTS `np_link`;
CREATE TABLE IF NOT EXISTS `np_link` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(20) NOT NULL DEFAULT '' COMMENT '标题',
  `logo` varchar(100) NOT NULL DEFAULT '' COMMENT '标志',
  `url` varchar(100) NOT NULL DEFAULT '' COMMENT '跳转链接',
  `remark` varchar(300) NOT NULL DEFAULT '' COMMENT '描述',
  `category_id` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '栏目ID',
  `type_id` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '类型ID',
  `admin_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '管理员ID',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '发布人ID',
  `is_pass` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '审核',
  `hits` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '点击量',
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '修改时间',
  `delete_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '删除时间',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `lang` varchar(10) NOT NULL DEFAULT 'zh-cn' COMMENT '语言',
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`) USING BTREE,
  KEY `type_id` (`type_id`) USING BTREE,
  KEY `is_pass` (`is_pass`) USING BTREE,
  KEY `delete_time` (`delete_time`) USING BTREE,
  KEY `lang` (`lang`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='友情链接表';

DROP TABLE IF EXISTS `np_feedback`;
CREATE TABLE IF NOT EXISTS `np_feedback` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(20) NOT NULL DEFAULT '' COMMENT '标题',
  `username` varchar(20) NOT NULL DEFAULT '' COMMENT '作者名',
  `content` varchar(300) NOT NULL DEFAULT '' COMMENT '内容',
  `category_id` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '栏目ID',
  `type_id` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '类型ID',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '发布人ID',
  `is_pass` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '审核',
  `ip` varchar(15) NOT NULL DEFAULT '' COMMENT '评论IP',
  `ip_attr` varchar(100) NOT NULL DEFAULT '' COMMENT '评论IP地区',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '修改时间',
  `delete_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '删除时间',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `lang` varchar(10) NOT NULL DEFAULT 'zh-cn' COMMENT '语言',
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `type_id` (`type_id`),
  KEY `is_pass` (`is_pass`),
  KEY `delete_time` (`delete_time`),
  KEY `lang` (`lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='反馈表';

DROP TABLE IF EXISTS `np_message`;
CREATE TABLE IF NOT EXISTS `np_message` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(20) NOT NULL DEFAULT '' COMMENT '标题',
  `username` varchar(20) NOT NULL DEFAULT '' COMMENT '作者名',
  `content` varchar(300) NOT NULL DEFAULT '' COMMENT '内容',
  `reply` varchar(300) NOT NULL DEFAULT '' COMMENT '回复',
  `category_id` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '栏目ID',
  `type_id` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '类型ID',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '发布人ID',
  `is_pass` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '审核',
  `ip` varchar(15) NOT NULL DEFAULT '' COMMENT '评论IP',
  `ip_attr` varchar(100) NOT NULL DEFAULT '' COMMENT '评论IP地区',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '修改时间',
  `delete_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '删除时间',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `lang` varchar(10) NOT NULL DEFAULT 'zh-cn' COMMENT '语言',
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `type_id` (`type_id`),
  KEY `is_pass` (`is_pass`),
  KEY `delete_time` (`delete_time`),
  KEY `lang` (`lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='留言表';






DROP TABLE IF EXISTS `np_article_product_attr`;
CREATE TABLE `np_article_product_attr` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `article_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '文章ID',
  `attr_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '属性id',
  `attr_value` text COMMENT '属性值',
  `attr_price` varchar(255) DEFAULT '' COMMENT '属性价格',
  `create_time` int(11) DEFAULT '0' COMMENT '新增时间',
  `update_time` int(11) DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `article_id` (`article_id`) USING BTREE,
  KEY `attr_id` (`attr_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='产品表单属性值';



DROP TABLE IF EXISTS `ey_article_product_attribute`;
CREATE TABLE `ey_article_product_attribute` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(60) DEFAULT '' COMMENT '属性名称',
  `typeid` int(11) unsigned DEFAULT '0' COMMENT '栏目id',
  `attr_index` tinyint(1) unsigned DEFAULT '0' COMMENT '0不需要检索 1关键字检索 2范围检索',
  `attr_input_type` tinyint(1) unsigned DEFAULT '0' COMMENT ' 0=文本框，1=下拉框，2=多行文本框',
  `attr_values` text COMMENT '可选值列表',
  `sort_order` int(11) unsigned DEFAULT '0' COMMENT '属性排序',
  `lang` varchar(50) DEFAULT 'cn' COMMENT '语言标识',
  `is_del` tinyint(1) DEFAULT '0' COMMENT '是否已删除，0=否，1=是',
  `add_time` int(11) DEFAULT '0' COMMENT '新增时间',
  `update_time` int(11) DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`attr_id`),
  KEY `cat_id` (`typeid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='产品表单属性表';
