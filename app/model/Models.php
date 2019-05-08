<?php
/**
 *
 * 数据层
 * 文章模型表
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

class Models extends Model
{
    protected $name = 'model';
    protected $autoWriteTimestamp = false;
    protected $updateTime = false;
    protected $pk = 'id';
    protected $type = [
        'status'     => 'integer'
    ];
    protected $field = [
        'id',
        'name',
        'table_name',
        'status',
        'remark'
    ];
}
