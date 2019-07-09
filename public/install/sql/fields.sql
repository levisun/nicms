DROP TABLE IF EXISTS `np_fields_type`;
CREATE TABLE IF NOT EXISTS `np_fields_type` (
  `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL DEFAULT '' COMMENT '类型名',
  `regex` varchar(100) NOT NULL DEFAULT '' COMMENT '验证方式',
  `remark` varchar(100) NOT NULL DEFAULT '' COMMENT '描述',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='自定义字段表';
INSERT INTO `np_fields_type` (`id`, `name`, `remark`, `regex`) VALUES
(1, 'text', '文本', 'require'),
(2, 'number', '数字', 'number'),
(3, 'email', '邮箱', 'email'),
(4, 'url', 'URL地址', 'url'),
(5, 'currency', '货币', 'currency'),
(6, 'abc', '字母', '/^[A-Za-z]+$/'),
(7, 'idcards', '身份证', '/^(\d{14}|\d{17})(\d|[xX])$/'),
(8, 'phone', '移动电话', '/^(1)[1-9][0-9]{9}$/'),
(9, 'landline', '固话', '/^\d{3,4}-\d{7,8}(-\d{3,4})?$/'),
(10, 'age', '年龄', '/^[1-9][0-9]?[0-9]?$/'),
(11, 'date', '日期', '/^\d{4}(\-|\/|\.)\d{1,2}\1\d{1,2}$/');

DROP TABLE IF EXISTS `np_fields`;
CREATE TABLE IF NOT EXISTS `np_fields` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '栏目ID',
  `type_id` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '类型ID',
  `name` varchar(20) NOT NULL DEFAULT '' COMMENT '字段名',
  `maxlength` smallint(5) NOT NULL DEFAULT '500' COMMENT '最大长度',
  `is_require` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '必填',
  `sort_order` smallint(5) NOT NULL DEFAULT '0' COMMENT '排序',
  `remark` varchar(100) NOT NULL DEFAULT '' COMMENT '描述',
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `type_id` (`type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='自定义字段表';
