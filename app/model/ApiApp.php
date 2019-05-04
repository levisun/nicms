<?php
/**
 *
 * 数据层
 * Api应用表
 *
 * @package   NICMS
 * @category  app\model
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
namespace app\model;

use think\Model;

class ApiApp extends Model
{
    protected $name = 'api_app';
    protected $autoWriteTimestamp = true;
    protected $updateTime = 'update_time';
    protected $pk = 'id';
    protected $type = [
        'remark' => 'integer',
    ];
    protected $field = [
        'id',
        'name',
        'secret',
        'module',
        'status',
        'remark',
        'update_time',
        'create_time'
    ];
}
