DROP TABLE IF EXISTS `np_admin`;
CREATE TABLE IF NOT EXISTS `np_admin` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` varchar(20) NOT NULL DEFAULT '' COMMENT '用户名',
  `password` varchar(60) NOT NULL DEFAULT '' COMMENT '密码',
  `email` varchar(40) NOT NULL DEFAULT '' COMMENT '邮箱',
  `salt` char(6) NOT NULL DEFAULT '' COMMENT '佐料',
  `flag` varchar(40) NOT NULL DEFAULT '' COMMENT '登录标识',
  `last_login_ip` varchar(15) NOT NULL DEFAULT '' COMMENT '登录IP',
  `last_login_ip_attr` varchar(100) NOT NULL DEFAULT '' COMMENT '登录IP地区',
  `last_login_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '登录时间',
  `update_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '修改时间',
  `create_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `password` (`password`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员表';
INSERT INTO `np_admin` (`id`, `username`, `password`, `email`, `salt`, `last_login_ip`, `last_login_ip_attr`, `last_login_time`, `update_time`, `create_time`) VALUES
(1, 'levisun', '$2y$11$d.FUHJoQT8EEsuJVv9GOQ.D6.GquPRJHb.7VfU89yugmVwzxzo6qG', 'levisun@mail.com', '0af476', '', '', 1556499533, 1556499533, 1556499533);



DROP TABLE IF EXISTS `np_role_admin`;
CREATE TABLE IF NOT EXISTS `np_role_admin` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '管理员ID',
  `role_id` smallint(5) UNSIGNED NOT NULL DEFAULT '0' COMMENT '组ID',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`) USING BTREE,
  KEY `group_id` (`role_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员组关系表';
INSERT INTO `np_role_admin` (`id`, `user_id`, `role_id`) VALUES
(1, 1, 1);



DROP TABLE IF EXISTS `np_role`;
CREATE TABLE IF NOT EXISTS `np_role` (
  `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pid` smallint(5) UNSIGNED NOT NULL DEFAULT '0' COMMENT '父ID',
  `name` varchar(20) NOT NULL DEFAULT '' COMMENT '组名',
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '状态',
  `remark` varchar(100) NOT NULL DEFAULT '' COMMENT '描述',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`) USING BTREE,
  KEY `status` (`status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='组表';
INSERT INTO `np_role` (`id`, `pid`, `name`, `status`, `remark`) VALUES
(1, 0, '创始人', 1, '创始人');



DROP TABLE IF EXISTS `np_access`;
CREATE TABLE IF NOT EXISTS `np_access` (
  `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id` smallint(5) UNSIGNED NOT NULL DEFAULT '0' COMMENT '组ID',
  `node_id` smallint(5) UNSIGNED NOT NULL DEFAULT '0' COMMENT '节点ID',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态',
  `level` tinyint(1) NOT NULL DEFAULT '0' COMMENT '节点等级',
  `module` varchar(20) NOT NULL DEFAULT '' COMMENT '节点名',
  PRIMARY KEY (`id`),
  KEY `role_id` (`role_id`) USING BTREE,
  KEY `node_id` (`node_id`) USING BTREE,
  KEY `status` (`status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='权限表';



DROP TABLE IF EXISTS `np_node`;
CREATE TABLE IF NOT EXISTS `np_node` (
  `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pid` smallint(5) UNSIGNED NOT NULL DEFAULT '0' COMMENT '父ID',
  `level` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '等级',
  `name` varchar(20) NOT NULL DEFAULT '' COMMENT '节点操作名',
  `title` varchar(50) NOT NULL DEFAULT '' COMMENT '节点说明',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态',
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT '0' COMMENT '排序',
  `remark` varchar(100) NOT NULL DEFAULT '' COMMENT '描述',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`) USING BTREE,
  KEY `level` (`level`) USING BTREE,
  KEY `status` (`status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='节点表';
INSERT INTO `np_node` (`id`, `pid`, `level`, `name`, `title`, `status`, `sort_order`, `remark`) VALUES
(1, 0, 1, 'admin', '后台', 1, 0, '后台模块'),
  (2, 1, 2, 'settings', '设置', 1, 0, '设置控制器'),
    (21, 2, 3, 'info', '系统信息', 1, 0, '系统信息方法'),
    (22, 2, 3, 'basic', '基本设置', 1, 0, '基本设置方法'),
      (221, 22, 4, 'editor', '基本设置编辑', 1, 0, '基本设置编辑操作'),
    (23, 2, 3, 'lang', '语言设置', 1, 0, '语言设置方法'),
      (231, 23, 4, 'editor', '语言设置编辑', 1, 0, '语言设置编辑操作'),
    (24, 2, 3, 'safe', '安全设置', 1, 0, '安全与效率设置方法'),
      (241, 24, 4, 'editor', '安全设置编辑', 1, 0, '安全与效率设置编辑操作'),

  (3, 1, 2, 'theme', '界面', 1, 0, '界面控制器'),
    (31, 3, 3, 'cms', '网站界面设置', 1, 0, '网站界面设置方法'),
      (311, 31, 4, 'editor', '网站界面设置编辑', 1, 0, '网站界面设置编辑操作'),
    (32, 3, 3, 'member', '会员界面设置', 1, 0, '会员界面设置方法'),
      (321, 32, 4, 'editor', '会员界面设置编辑', 1, 0, '会员界面设置编辑操作'),

  (4, 1, 2, 'category', '栏目', 1, 0, '栏目控制器'),
    (41, 4, 3, 'category', '管理栏目', 1, 0, '管理栏目方法'),
      (411, 41, 4, 'added', '管理栏目添加', 1, 0, '管理栏目添加操作'),
      (412, 41, 4, 'remove', '管理栏目删除', 1, 0, '管理栏目删除操作'),
      (413, 41, 4, 'editor', '管理栏目编辑', 1, 0, '管理栏目编辑操作'),
      (414, 41, 4, 'upload', '管理栏目上传', 1, 0, '管理栏目上传操作'),
    (42, 4, 3, 'fields', '自定义字段', 1, 0, '自定义字段'),
      (421, 42, 4, 'added', '自定义字段添加', 1, 0, '自定义字段添加操作'),
      (422, 42, 4, 'remove', '自定义字段删除', 1, 0, '自定义字段删除操作'),
      (423, 42, 4, 'editor', '自定义字段编辑', 1, 0, '自定义字段编辑操作'),
    (43, 4, 3, 'type', '管理类别', 1, 0, '管理类别方法'),
      (431, 43, 4, 'added', '管理类别添加', 1, 0, '管理类别添加操作'),
      (432, 43, 4, 'remove', '管理类别删除', 1, 0, '管理类别删除操作'),
      (433, 43, 4, 'editor', '管理类别编辑', 1, 0, '管理类别编辑操作'),
    (44, 4, 3, 'model', '管理模型', 1, 0, '管理模型方法'),

  (5, 1, 2, 'content', '内容', 1, 0, '内容控制器'),
    (51, 5, 3, 'content', '管理内容', 1, 0, '管理内容方法'),
      (511, 51, 4, 'added', '管理内容添加', 1, 0, '管理内容添加操作'),
      (512, 51, 4, 'remove', '管理内容删除', 1, 0, '管理内容删除操作'),
      (513, 51, 4, 'editor', '管理内容编辑', 1, 0, '管理内容编辑操作'),
      (514, 51, 4, 'upload', '管理内容上传', 1, 0, '管理内容上传操作'),
    (52, 5, 3, 'banner', '管理幻灯片', 1, 0, '管理幻灯片方法'),
      (521, 52, 4, 'added', '管理幻灯片添加', 1, 0, '管理幻灯片添加操作'),
      (522, 52, 4, 'remove', '管理幻灯片删除', 1, 0, '管理幻灯片删除操作'),
      (523, 52, 4, 'editor', '管理幻灯片编辑', 1, 0, '管理幻灯片编辑操作'),
      (524, 52, 4, 'upload', '管理幻灯片上传', 1, 0, '管理幻灯片上传操作'),
    (53, 5, 3, 'ads', '管理广告', 1, 0, '管理广告方法'),
      (531, 53, 4, 'added', '管理广告添加', 1, 0, '管理广告添加操作'),
      (532, 53, 4, 'remove', '管理广告删除', 1, 0, '管理广告删除操作'),
      (533, 53, 4, 'editor', '管理广告编辑', 1, 0, '管理广告编辑操作'),
      (534, 53, 4, 'upload', '管理广告上传', 1, 0, '管理广告上传操作'),
    (54, 5, 3, 'comment', '管理评论', 1, 0, '管理评论方法'),
      (541, 54, 4, 'remove', '管理评论删除', 1, 0, '管理评论删除操作'),
      (542, 54, 4, 'editor', '管理评论编辑', 1, 0, '管理评论编辑操作'),
    (55, 5, 3, 'recycle', '内容回收站', 1, 0, '内容回收站方法'),
      (551, 55, 4, 'remove', '内容回收站删除', 1, 0, '内容回收站删除操作'),
      (552, 55, 4, 'recover', '内容回收站恢复', 1, 0, '内容回收站恢复操作'),
    (56, 5, 3, 'cache', '更新缓存或静态', 1, 0, '更新缓存或静态方法'),
      (561, 56, 4, 'reCompile', '编译与HTML静态缓存文件', 1, 0, '编译与HTML静态缓存文件操作'),
      (562, 56, 4, 'reCache', '数据缓存的文件', 1, 0, '数据缓存的文件操作'),

  (6, 1, 2, 'user', '用户', 1, 0, '用户控制器'),
    (61, 6, 3, 'user', '会员管理', 1, 0, '会员管理方法'),
      (611, 61, 4, 'added', '会员管理添加', 1, 0, '会员管理添加操作'),
      (612, 61, 4, 'remove', '会员管理删除', 1, 0, '会员管理删除操作'),
      (613, 61, 4, 'editor', '会员管理编辑', 1, 0, '会员管理编辑操作'),
      (614, 61, 4, 'upload', '会员管理上传', 1, 0, '会员管理上传操作'),
    (62, 6, 3, 'level', '会员等级管理', 1, 0, '会员等级管理方法'),
      (621, 62, 4, 'added', '会员等级管理添加', 1, 0, '会员等级管理添加操作'),
      (622, 62, 4, 'remove', '会员等级管理删除', 1, 0, '会员等级管理删除操作'),
      (623, 62, 4, 'editor', '会员等级管理编辑', 1, 0, '会员等级管理编辑操作'),




    (63, 6, 3, 'admin', '管理员管理', 1, 0, '管理员管理方法'),
      (631, 63, 4, 'added', '管理员管理添加', 1, 0, '管理员管理添加操作'),
      (632, 63, 4, 'remove', '管理员管理删除', 1, 0, '管理员管理删除操作'),
      (633, 63, 4, 'editor', '管理员管理编辑', 1, 0, '管理员管理编辑操作'),
    (64, 6, 3, 'role', '管理员组管理', 1, 0, '管理员组管理方法'),
      (641, 64, 4, 'added', '管理员组管理添加', 1, 0, '管理员组管理添加操作'),
      (642, 64, 4, 'remove', '管理员组管理删除', 1, 0, '管理员组管理删除操作'),
      (643, 64, 4, 'editor', '管理员组管理编辑', 1, 0, '管理员组管理编辑操作'),
    (65, 6, 3, 'node', '系统节点管理', 1, 0, '系统节点管理方法'),
      (651, 65, 4, 'added', '系统节点管理添加', 1, 0, '系统节点管理添加操作'),
      (652, 65, 4, 'remove', '系统节点管理删除', 1, 0, '系统节点管理删除操作'),
      (653, 65, 4, 'editor', '系统节点管理编辑', 1, 0, '系统节点管理编辑操作'),

  (7, 1, 2, 'wechat', '微信', 1, 0, '微信控制器'),
    (71, 7, 3, 'keyword', '关键词自动回复', 1, 0, '关键词自动回复方法'),
      (711, 71, 4, 'added', '关键词自动回复添加', 1, 0, '关键词自动回复添加操作'),
      (712, 71, 4, 'remove', '关键词自动回复删除', 1, 0, '关键词自动回复删除操作'),
      (713, 71, 4, 'editor', '关键词自动回复编辑', 1, 0, '关键词自动回复编辑操作'),
      (714, 71, 4, 'upload', '关键词自动回复上传', 1, 0, '关键词自动回复上传操作'),
    (72, 7, 3, 'auto', '默认自动回复', 1, 0, '默认自动回复方法'),
      (721, 72, 4, 'added', '默认自动回复添加', 1, 0, '默认自动回复添加操作'),
      (722, 72, 4, 'remove', '默认自动回复删除', 1, 0, '默认自动回复删除操作'),
      (723, 72, 4, 'editor', '默认自动回复编辑', 1, 0, '默认自动回复编辑操作'),
      (724, 72, 4, 'upload', '默认自动回复上传', 1, 0, '默认自动回复上传操作'),
    (73, 7, 3, 'attention', '关注自动回复', 1, 0, '关注自动回复方法'),
      (731, 73, 4, 'added', '关注自动回复添加', 1, 0, '关注自动回复添加操作'),
      (732, 73, 4, 'remove', '关注自动回复删除', 1, 0, '关注自动回复删除操作'),
      (733, 73, 4, 'editor', '关注自动回复编辑', 1, 0, '关注自动回复编辑操作'),
      (734, 73, 4, 'upload', '关注自动回复上传', 1, 0, '关注自动回复上传操作'),
    (74, 7, 3, 'config', '接口配置', 1, 0, '接口配置方法'),
      (741, 74, 4, 'editor', '接口配置编辑', 1, 0, '接口配置编辑操作'),
    (75, 7, 3, 'menu', '自定义菜单', 1, 0, '自定义菜单方法'),
      (751, 75, 4, 'editor', '自定义菜单编辑', 1, 0, '自定义菜单编辑操作'),
      (752, 75, 4, 'upload', '自定义菜单上传', 1, 0, '自定义菜单上传操作'),

  (8, 1, 2, 'book', '书库', 1, 0, '书库控制器'),
    (81, 8, 3, 'book', '管理书库', 1, 0, '管理书库方法'),
      (811, 81, 4, 'added', '管理书库添加', 1, 0, '管理书库添加操作'),
      (812, 81, 4, 'remove', '管理书库删除', 1, 0, '管理书库删除操作'),
      (813, 81, 4, 'editor', '管理书库编辑', 1, 0, '管理书库编辑操作'),
      (814, 81, 4, 'upload', '管理书库上传', 1, 0, '管理书库上传操作'),
    (82, 8, 3, 'article', '管理章节', 1, 0, '管理章节方法'),
      (821, 82, 4, 'added', '管理章节添加', 1, 0, '管理章节添加操作'),
      (822, 82, 4, 'remove', '管理章节删除', 1, 0, '管理章节删除操作'),
      (823, 82, 4, 'editor', '管理章节编辑', 1, 0, '管理章节编辑操作'),
    (83, 8, 3, 'type', '管理分类', 1, 0, '管理分类方法'),
      (831, 83, 4, 'added', '管理分类添加', 1, 0, '管理分类添加操作'),
      (832, 83, 4, 'remove', '管理分类删除', 1, 0, '管理分类删除操作'),
      (833, 83, 4, 'editor', '管理分类编辑', 1, 0, '管理分类编辑操作'),
    (84, 8, 3, 'author', '管理作者', 1, 0, '管管理作者方法'),
      (841, 84, 4, 'added', '管理作者添加', 1, 0, '管理作者添加操作'),
      (842, 84, 4, 'remove', '管理作者删除', 1, 0, '管理作者删除操作'),
      (843, 84, 4, 'editor', '管理作者编辑', 1, 0, '管理作者编辑操作'),

  (9, 1, 2, 'extend', '扩展', 1, 0, '扩展控制器'),
    (91, 9, 3, 'log', '系统日志', 1, 0, '系统日志方法'),
    (92, 9, 3, 'databack', '数据与备份', 1, 0, '数据与备份方法'),
      (921, 92, 4, 'reduction', '数据与备份还原', 1, 0, '数据与备份还原操作'),
      (922, 92, 4, 'backup', '数据与备份备份', 1, 0, '数据与备份备份操作'),
      (923, 92, 4, 'remove', '数据与备份删除', 1, 0, '数据与备份删除操作'),
    (93, 9, 3, 'elog', '错误日志', 1, 0, '错误日志方法'),
    (94, 9, 3, 'visit', '访问统计', 1, 0, '访问统计方法');





-- (16, 2, 3, 'image', '图片设置', 1, 0, '图片设置方法'),
-- (17, 16, 4, 'editor', '图片设置编辑', 1, 0, '图片设置编辑操作'),
-- (18, 16, 4, 'upload', '图片设置上传', 1, 0, '图片设置上传操作'),

-- (21, 2, 3, 'email', '邮件设置设置', 1, 0, '邮件设置方法'),
-- (22, 21, 4, 'editor', '邮件设置设置编辑', 1, 0, '邮件设置设置编辑操作'),

-- (27, 3, 3, 'mall', '商城界面设置', 1, 0, '商城界面设置方法'),
-- (28, 27, 4, 'editor', '商城界面设置编辑', 1, 0, '商城界面设置编辑操作'),

-- (108, 8, 3, 'goods', '管理商品', 1, 0, '管理商品方法'),
-- (109, 108, 4, 'added', '管理商品添加', 1, 0, '管理商品添加操作'),
-- (110, 108, 4, 'remove', '管理商品删除', 1, 0, '管理商品删除操作'),
-- (111, 108, 4, 'editor', '管理商品编辑', 1, 0, '管理商品编辑操作'),
-- (112, 108, 4, 'upload', '管理商品上传', 1, 0, '管理商品上传操作'),
-- (113, 8, 3, 'orders', '管理订单', 1, 0, '管理订单方法'),
-- (114, 113, 4, 'remove', '管理订单删除', 1, 0, '管理订单删除操作'),
-- (115, 113, 4, 'editor', '管理订单编辑', 1, 0, '管理订单编辑操作'),
-- (116, 8, 3, 'category', '管理商城导航', 1, 0, '管理商城导航方法'),
-- (117, 116, 4, 'added', '管理商城导航添加', 1, 0, '管理商城导航添加操作'),
-- (118, 116, 4, 'remove', '管理商城导航删除', 1, 0, '管理商城导航删除操作'),
-- (119, 116, 4, 'editor', '管理商城导航编辑', 1, 0, '管理商城导航编辑操作'),
-- (120, 116, 4, 'upload', '管理商城导航上传', 1, 0, '管理商城导航上传操作'),
-- (121, 8, 3, 'type', '管理商品分类', 1, 0, '管理商品分类方法'),
-- (122, 121, 4, 'added', '管理商品分类添加', 1, 0, '管理商品分类添加操作'),
-- (123, 121, 4, 'remove', '管理商品分类删除', 1, 0, '管理商品分类删除操作'),
-- (124, 121, 4, 'editor', '管理商品分类编辑', 1, 0, '管理商品分类编辑操作'),
-- (125, 8, 3, 'brand', '管理商品品牌', 1, 0, '管理商品品牌方法'),
-- (126, 125, 4, 'added', '管理商品品牌添加', 1, 0, '管理商品品牌添加操作'),
-- (127, 125, 4, 'remove', '管理商品品牌删除', 1, 0, '管理商品品牌删除操作'),
-- (128, 125, 4, 'editor', '管理商品品牌编辑', 1, 0, '管理商品品牌编辑操作'),
-- (129, 125, 4, 'upload', '管理商品品牌上传', 1, 0, '管理商品品牌上传操作'),
-- (130, 8, 3, 'comment', '管理商品评论', 1, 0, '管理商品评论方法'),
-- (131, 130, 4, 'remove', '管理商品评论删除', 1, 0, '管理商品评论删除操作'),
-- (132, 130, 4, 'editor', '管理商品评论编辑', 1, 0, '管理商品评论编辑操作'),
-- (133, 8, 3, 'account', '账户流水', 1, 0, '账户流水方法'),
-- (134, 8, 3, 'grecycle', '商品回收站', 1, 0, '商品回收站方法'),
-- (135, 134, 4, 'remove', '商品回收站删除', 1, 0, '商品回收站删除操作'),
-- (136, 134, 4, 'recover', '商品回收站恢复', 1, 0, '商品回收站恢复操作'),
-- (137, 8, 3, 'settings', '商城设置', 1, 0, '商城设置方法'),
-- (138, 137, 4, 'editor', '商城设置编辑', 1, 0, '商城设置编辑操作'),
