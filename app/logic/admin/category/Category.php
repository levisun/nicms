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
use think\facade\Request;
use app\library\Base64;
use app\logic\admin\Base;
use app\model\Category as ModelCategory;
use app\model\Level as ModelLevel;

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
        if ($result = $this->authenticate(__METHOD__)) {
            return $result;
        }

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
            $value['url'] = [
                'editor' => url('category/category/editor/' . $value['id']),
                'remove' => url('category/category/remove/' . $value['id']),
            ];
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
            $value['url'] = [
                'editor' => url('category/category/editor/' . $value['id']),
                'remove' => url('category/category/remove/' . $value['id']),
            ];
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

    /**
     * 查询
     * @access public
     * @param
     * @return array
     */
    public function find(): array
    {
        if ($result = $this->authenticate(__METHOD__)) {
            return $result;
        }

        if ($id = Request::param('id')) {
            $id = (int) Base64::decrypt($id);
            $result =
            (new ModelCategory)->view('category')
            ->view('model', ['name' => 'model_name'], 'model.id=category.model_id')
            ->where([
                ['category.id', '=', $id],
                ['category.lang', '=', Lang::getLangSet()]
            ])
            ->find();

            if ($result) {
                $result['parent'] =
                (new ModelCategory)
                ->where([
                    ['id', '=', $result['pid']]
                ])
                ->value('name as parent');

                $result['id'] = Base64::encrypt($result['id']);
                $result['type_name'] = [
                    ['id' => '1', 'name' => Lang::get('category top type')],
                    ['id' => '2', 'name' => Lang::get('category main type')],
                    ['id' => '3', 'name' => Lang::get('category foot type')],
                    ['id' => '4', 'name' => Lang::get('category other type')],
                ];

                $result['access_name'] =
                (new ModelLevel)->field('id, name')
                ->order('id DESC')
                ->select()
                ->toArray();
            }
        } else {
            $result = 'request param error';
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'category data',
            'data'  => $result
        ];
    }

    public function editor()
    {
        if ($result = $this->authenticate(__METHOD__, 'admin category editor')) {
            return $result;
        }

        if ($id = Request::param('id')) {
            $id = Base64::decrypt($id);

            $receive_data = [
                'name'        => Request::param('name'),
                'aliases'     => Request::param('aliases'),
                'title'       => Request::param('title'),
                'keywords'    => Request::param('keywords'),
                'description' => Request::param('description'),
                'image'       => Request::param('image'),
                'type_id'     => (int) Request::param('type_id/f'),
                'model_id'    => (int) Request::param('model_id/f'),
                'is_show'     => (int) Request::param('is_show/f'),
                'is_channel'  => (int) Request::param('is_channel/f'),
                'sort_order'  => (int) Request::param('sort_order/f'),
                'access_id'   => (int) Request::param('access_id/f'),
                'url'         => Request::param('url'),
            ];
            if ($result = $this->validate(__METHOD__, $receive_data)) {
                return $result;
            }

            (new ModelCategory)
            ->where([
                ['id', '=', $id]
            ])
            ->data($receive_data)
            ->update();

            $msg = 'category editor success';
        } else {
            $msg = 'category editor error';
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => $msg
        ];
    }
}
