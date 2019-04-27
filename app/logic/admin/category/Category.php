<?php
/**
 *
 * API接口层
 * 网站栏目
 *
 * @package   NICMS
 * @category  app\logic\admin\category
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
declare (strict_types = 1);

namespace app\logic\admin\category;

use think\facade\Lang;
use app\library\Base64;
use app\logic\admin\Base;
use app\model\Category as ModelCategory;

class Category extends Base
{

    /**
     * 查询
     * @access public
     * @param
     * @return array
     */
    public function query(): array
    {
        $result =
        (new ModelCategory)->view('category', ['id', 'name', 'type_id', 'model_id', 'is_show', 'is_channel', 'sort_order'])
        ->view('model', ['name' => 'model_name'], 'model.id=category.model_id')
        ->where([
            ['category.pid', '=', 0],
            ['category.lang', '=', Lang::getLangSet()]
        ])
        ->order('category.sort_order ASC, category.id DESC')
        ->select()
        ->toArray();

        foreach ($result as $key => $value) {
            $value['type_name'] = $this->typeName($value['type_id']);
            $value['child'] = $this->child($value['id']);
            $value['id'] = Base64::encrypt($value['id']);
            $result[$key] = $value;
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'category data',
            'data'  => [
                'list'  => $result,
                'total' => count($result),
            ]
        ];
    }

    /**
     * 子导航
     * @access private
     * @param  int $_pid
     * @return bool|array
     */
    private function child(int $_pid)
    {
        $result =
        (new ModelCategory)->view('category', ['id', 'name', 'type_id', 'model_id', 'is_show', 'is_channel', 'sort_order'])
        ->view('model', ['name' => 'model_name'], 'model.id=category.model_id')
        ->where([
            ['category.pid', '=', $_pid],
            ['category.lang', '=', Lang::getLangSet()]
        ])
        ->order('category.sort_order ASC, category.id DESC')
        ->select()
        ->toArray();

        foreach ($result as $key => $value) {
            $value['type_name'] = $this->typeName($value['type_id']);
            $value['child'] = $this->child($value['id']);
            $value['id'] = Base64::encrypt($value['id']);
            $result[$key] = $value;
        }

        return $result ? $result : false;
    }

    /**
     * 导航类型
     * @access private
     * @param  int $_tid
     * @return string
     */
    private function typeName(int $_tid): string
    {
        if ($_tid === 1) {
            return Lang::get('category top type');
        } elseif ($_tid === 2) {
            return Lang::get('category main type');
        } elseif ($_tid === 3) {
            return Lang::get('category foot type');
        } else {
            return Lang::get('category other type');
        }
    }
}
