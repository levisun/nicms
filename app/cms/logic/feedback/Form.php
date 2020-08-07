<?php

/**
 *
 * API接口层
 * 反馈列表
 *
 * @package   NICMS
 * @category  app\cms\logic\feedback
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\cms\logic\feedback;

use app\common\controller\BaseLogic;

class Form extends BaseLogic
{

    /**
     * 查询列表
     * @access public
     * @param
     * @return array
     */
    public function query()
    {
        if ($category_id = $this->request->param('cid/d', 0, 'abs')) {
            // 附加字段数据
            $fields = (new ModelFieldsExtend)
                ->view('fields_extend', ['data'])
                ->view('fields', ['name' => 'fields_name'], 'fields.id=fields_extend.fields_id')
                ->where([
                    ['fields.category_id', '=', $category_id],
                ])
                ->select()
                ->toArray();
            foreach ($fields as $val) {
                $value[$val['fields_name']] = $val['data'];
            }
        }
    }
}
