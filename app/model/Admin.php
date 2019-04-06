<?php
/**
 *
 * 行为日志表 - 数据层
 *
 * @package   NICMS
 * @category  application\common\model
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2017/12
 */
namespace app\model;

use think\Model;

class Admin extends Model
{
    protected $name = 'admin';
    protected $autoWriteTimestamp = false;
    protected $updateTime = 'update_time';
    protected $pk = 'id';
    protected $field = [
        'id',
        'username',
        'password',
        'email',
        'salt',
        'last_login_ip',
        'last_login_ip_attr',
        'last_login_time',
        'update_time',
        'create_time'
    ];

}
