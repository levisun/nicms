<?php
/**
 *
 * 数据层
 * 文章分类表
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

class Type extends Model
{
    protected $name = 'type';
    protected $autoWriteTimestamp = false;
    protected $updateTime = false;
    protected $pk = 'id';
    protected $type = [
        'category_id' => 'integer',
    ];
    protected $field = [
        'id',
        'category_id',
        'name',
        'remark',
    ];
}
