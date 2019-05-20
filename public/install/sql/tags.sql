DROP TABLE IF EXISTS `np_tags`;
CREATE TABLE IF NOT EXISTS `np_tags` (
  `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL DEFAULT '' COMMENT '标签名',
  `count` int(11) UNSIGNED NOT NULL DEFAULT '1' COMMENT '标签文章数量',
  `lang` varchar(20) NOT NULL DEFAULT 'zh-cn' COMMENT '语言',
  PRIMARY KEY (`id`),
  KEY `name` (`name`) USING BTREE,
  KEY `lang` (`lang`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='标签表';

DROP TABLE IF EXISTS `np_tags_article`;
CREATE TABLE IF NOT EXISTS `np_tags_article` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tags_id` smallint(5) UNSIGNED NOT NULL DEFAULT '0' COMMENT '标签ID',
  `article_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '文章ID',
  PRIMARY KEY (`id`),
  KEY `tags_id` (`tags_id`) USING BTREE,
  KEY `article_id` (`article_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='标签文章关联表';
