<?php
/**
 *
 * 数据层
 * 文章扩展表
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

class ArticleExtend extends Model
{
    protected $name = 'article_extend';
    protected $autoWriteTimestamp = false;
    protected $updateTime = false;
    protected $pk = 'id';
    protected $type = [
        'article_id' => 'integer',
        'fields_id'  => 'integer',
    ];
    protected $field = [
        'id',
        'article_id',
        'fields_id',
        'data'
    ];
}
