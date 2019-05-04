DROP TABLE IF EXISTS `np_api_app`;
CREATE TABLE `np_api_app` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(20) DEFAULT '' COMMENT '应用名',
  `secret` varchar(40) DEFAULT '' COMMENT '密钥',
  `status` tinyint(1) DEFAULT '1' COMMENT '0关闭1开启',
  `remark` varchar(500) DEFAULT '' COMMENT '备注',
  `update_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '修改时间',
  `create_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`) USING BTREE,
  KEY `status` (`status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='api应用表';
INSERT INTO `np_api_app` (`id`, `name`, `secret`, `status`, `remark`, `update_time`, `create_time`) VALUES
(1, '游客', '962940cfbe94a64efcd1573cf6d7a175', 1, '', 1505898660, 1505898660);
