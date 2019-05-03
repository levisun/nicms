<?php
/**
 *
 * 数据层
 * 会员等级表
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

class Level extends Model
{
    protected $name = 'level';
    protected $autoWriteTimestamp = false;
    protected $updateTime = false;
    protected $dateFormat = false;
    protected $pk = 'id';
    protected $type = [
        'credit' => 'integer',
        'status' => 'integer',
    ];
    protected $field = [
        'id',
        'name',
        'credit',
        'status',
        'remark',
    ];
}
