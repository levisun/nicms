DROP TABLE IF EXISTS `np_type`;
CREATE TABLE IF NOT EXISTS `np_type` (
  `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` smallint(5) UNSIGNED NOT NULL DEFAULT '0' COMMENT '栏目ID',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '分类名',
  `remark` varchar(300) NOT NULL DEFAULT '' COMMENT '描述',
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='分类';
