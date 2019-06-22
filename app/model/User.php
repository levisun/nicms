<?php
/**
 *
 * 数据层
 * 会员等级表
 *
 * @package   NICMS
 * @category  app\model\
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
namespace app\model;

use think\Model;

class User extends Model
{
    protected $name = 'user';
    protected $autoWriteTimestamp = true;
    protected $updateTime = 'update_time';
    protected $dateFormat = false;
    protected $pk = 'id';
    protected $type = [
        'gender'      => 'integer',
        'birthday'    => 'integer',
        'level_id'    => 'integer',
        'province_id' => 'integer',
        'city_id'     => 'integer',
        'area_id'     => 'integer',
        'status'      => 'integer'
    ];
    protected $field = [
        'id',
        'username',
        'password',
        'email',
        'realname',
        'nickname',
        'portrait',
        'gender',
        'birthday',
        'level_id',
        'province_id',
        'city_id',
        'area_id',
        'address',
        'phone',
        'salt',
        'status',
        'last_login_ip',
        'last_login_ip_attr',
        'last_login_time',
        'update_time',
        'create_time'
    ];
}
