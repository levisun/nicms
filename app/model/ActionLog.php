<?php
/**
 *
 * 数据层
 * 行为日志表
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

class ActionLog extends Model
{
    protected $name = 'action_log';
    protected $autoWriteTimestamp = true;
    protected $updateTime = false;
    // protected $pk = 'id';
    protected $type = [
        'action_id' => 'integer',
        'user_id'   => 'integer',
    ];
    protected $field = [
        // 'id',
        'action_id',
        'user_id',
        'action_ip',
        'module',
        'remark',
        'create_time'
    ];
}
