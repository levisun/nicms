<?php

/**
 *
 * 数据层
 * 订单表
 *
 * @package   NICMS
 * @category  app\common\model
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
/*
DROP TABLE IF EXISTS `nc_order`;
CREATE TABLE IF NOT EXISTS `nc_order` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `goods_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '商品ID',
    `user_id` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '用户ID',
    `order_no` varchar(32) NOT NULL DEFAULT '' COMMENT '订单号',
    `trade_no` varchar(32) NOT NULL DEFAULT '' COMMENT '支付交易号',
    `amount` mediumint(9) UNSIGNED NOT NULL DEFAULT '0' COMMENT '支付金额',
    `status` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '状态 0取消订单 1待支付 2已支付待 3退款 4过期',
    `pay_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '支付时间',
    `refund_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '退款时间',
    `delete_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '删除时间',
    `create_time` int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建时间',
    PRIMARY KEY (`id`),
    INDEX `goods_id` (`goods_id`) USING BTREE,
    UNIQUE `order_no` (`order_no`) USING BTREE,
    UNIQUE `trade_no` (`trade_no`) USING BTREE,
    INDEX `user_id` (`user_id`) USING BTREE,
    INDEX `status` (`status`) USING BTREE,
    INDEX `delete_time` (`delete_time`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='订单表';
 */

namespace app\common\model;

use think\Model;

class Order extends Model
{
    protected $name = 'order';
    protected $autoWriteTimestamp = true;
    protected $updateTime = false;
    protected $pk = 'id';
    protected $type = [
        'goods_id' => 'integer',
        'user_id'  => 'integer',
        'amount'   => 'integer',
        'status'   => 'integer',
    ];
    protected $field = [
        'id',
        'goods_id',
        'user_id',
        'order_no',
        'trade_no',
        'amount',
        'status',
        'pay_time',
        'refund_time',
        'delete_time',
        'create_time',
    ];
}
