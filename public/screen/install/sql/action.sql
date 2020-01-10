DROP TABLE IF EXISTS `np_action`;
CREATE TABLE IF NOT EXISTS `np_action` (
  `id` smallint(6) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '行为唯一标识',
  `title` varchar(80) NOT NULL DEFAULT '' COMMENT '行为说明',
  `remark` varchar(140) NOT NULL DEFAULT '' COMMENT '描述',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='行为表';

DROP TABLE IF EXISTS `np_action_log`;
CREATE TABLE IF NOT EXISTS `np_action_log` (
  `action_id` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '行为ID',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '执行用户ID',
  `action_ip` varchar(255) NOT NULL COMMENT '执行行为者IP',
  `module` varchar(50) NOT NULL DEFAULT '' COMMENT '触发行为的模块',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '日志备注',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '执行行为的时间',
  KEY `action_ip` (`action_ip`) USING BTREE,
  KEY `action_id` (`action_id`) USING BTREE,
  KEY `user_id` (`user_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='行为日志表';
