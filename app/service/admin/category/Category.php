<?php
/**
 *
 * API接口层
 * 网站栏目
 *
 * @package   NICMS
 * @category  app\service\admin\category
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
declare (strict_types = 1);

namespace app\service\admin\category;

use app\service\BaseService;
use app\model\Article as ModelArticle;
use app\model\Category as ModelCategory;
use app\model\Level as ModelLevel;
use app\model\Models as ModelModels;

class Category extends BaseService
{
    protected $auth_key = 'admin_auth_key';
    protected $cache_tag = 'admin';

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

        $result = (new ModelCategory)
            ->view('category', ['id', 'name', 'type_id', 'model_id', 'is_show', 'is_channel', 'sort_order'])
            ->view('model', ['name' => 'model_name'], 'model.id=category.model_id')
            ->where([
                ['category.pid', '=', 0],
                ['category.lang', '=', $this->lang->getLangSet()]
            ])
            ->order('category.type_id ASC, category.sort_order ASC, category.id DESC')
            ->select()
            ->toArray();

        foreach ($result as $key => $value) {
            $value['type_name'] = $this->typeName($value['type_id']);
            $value['url'] = [
                'added'  => url('category/category/added/' . $value['id']),
                'editor' => url('category/category/editor/' . $value['id']),
                'remove' => url('category/category/remove/' . $value['id']),
            ];
            $value['child'] = $this->child($value['id']);
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
        $result = (new ModelCategory)
            ->view('category', ['id', 'name', 'type_id', 'model_id', 'is_show', 'is_channel', 'sort_order'])
            ->view('model', ['name' => 'model_name'], 'model.id=category.model_id')
            ->where([
                ['category.pid', '=', $_pid],
                ['category.lang', '=', $this->lang->getLangSet()]
            ])
            ->order('category.sort_order ASC, category.id DESC')
            ->select()
            ->toArray();

        foreach ($result as $key => $value) {
            $value['type_name'] = $this->typeName($value['type_id']);
            $value['url'] = [
                'added'  => url('category/category/added/' . $value['id']),
                'editor' => url('category/category/editor/' . $value['id']),
                'remove' => url('category/category/remove/' . $value['id']),
            ];
            $value['child'] = $this->child($value['id']);
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
            return $this->lang->get('category top type');
        } elseif ($_tid === 2) {
            return $this->lang->get('category main type');
        } elseif ($_tid === 3) {
            return $this->lang->get('category foot type');
        } else {
            return $this->lang->get('category other type');
        }
    }

    /**
     * 添加
     * @access public
     * @param
     * @return array
     */
    public function added(): array
    {
        if ($result = $this->authenticate(__METHOD__, 'admin category added')) {
            return $result;
        }

        $pid = $pid = (int)$this->request->param('pid/f', 0);

        $receive_data = [
            'pid'         => $pid,
            'name'        => $this->request->param('name'),
            'aliases'     => $this->request->param('aliases'),
            'title'       => $this->request->param('title'),
            'keywords'    => $this->request->param('keywords'),
            'description' => $this->request->param('description'),
            'image'       => $this->request->param('image'),
            'model_id'    => (int)$this->request->param('model_id/f', 1),
            'type_id'     => (int)$this->request->param('type_id/f', 1),
            'is_show'     => (int)$this->request->param('is_show/f', 1),
            'is_channel'  => (int)$this->request->param('is_channel/f', 0),
            'sort_order'  => (int)$this->request->param('sort_order/f', 0),
            'access_id'   => (int)$this->request->param('access_id/f'),
            'url'         => $this->request->param('url'),
            'update_time' => time(),
            'create_time' => time(),
            'lang'        => $this->lang->getLangSet()
        ];
        if ($result = $this->validate(__METHOD__, $receive_data)) {
            return $result;
        }

        (new ModelCategory)->create($receive_data);

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'category added success',
        ];
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

        if ($id = (int)$this->request->param('id/f')) {
            $result = (new ModelCategory)
                ->view('category')
                ->view('model', ['name' => 'model_name'], 'model.id=category.model_id')
                ->where([
                    ['category.id', '=', $id],
                    ['category.lang', '=', $this->lang->getLangSet()]
                ])
                ->find();

            if ($result) {
                $result['parent'] = (new ModelCategory)
                    ->where([
                        ['id', '=', $result['pid']]
                    ])
                    ->value('name as parent');

                $result['type_name'] = [
                    ['id' => '1', 'name' => $this->lang->get('category top type')],
                    ['id' => '2', 'name' => $this->lang->get('category main type')],
                    ['id' => '3', 'name' => $this->lang->get('category foot type')],
                    ['id' => '4', 'name' => $this->lang->get('category other type')],
                ];

                $result['access_name'] = (new ModelLevel)
                    ->field('id, name')
                    ->order('id DESC')
                    ->select()
                    ->toArray();
            }
        } else {
            $result = [];
            if ($pid = (int)$this->request->param('pid/f', '0')) {
                $result['parent'] = (new ModelCategory)
                    ->where([
                        ['id', '=', $pid]
                    ])
                    ->value('name as parent');
            }

            $result['type_name'] = [
                ['id' => '1', 'name' => $this->lang->get('category top type')],
                ['id' => '2', 'name' => $this->lang->get('category main type')],
                ['id' => '3', 'name' => $this->lang->get('category foot type')],
                ['id' => '4', 'name' => $this->lang->get('category other type')],
            ];

            $result['access_name'] = (new ModelLevel)
                ->field('id, name')
                ->order('id DESC')
                ->select()
                ->toArray();

            $result['model_name'] = (new ModelModels)
                ->field('id, name')
                ->where([
                    ['status', '=', 1]
                ])
                ->order('id ASC')
                ->select()
                ->toArray();
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'category data',
            'data'  => $result
        ];
    }

    /**
     * 编辑
     * @access public
     * @param
     * @return array
     */
    public function editor(): array
    {
        if ($result = $this->authenticate(__METHOD__, 'admin category editor')) {
            return $result;
        }

        if ($id = (int)$this->request->param('id/f')) {
            $receive_data = [
                'name'        => $this->request->param('name'),
                'aliases'     => $this->request->param('aliases'),
                'title'       => $this->request->param('title'),
                'keywords'    => $this->request->param('keywords'),
                'description' => $this->request->param('description'),
                'image'       => $this->request->param('image'),
                'type_id'     => (int)$this->request->param('type_id/f'),
                'is_show'     => (int)$this->request->param('is_show/f'),
                'is_channel'  => (int)$this->request->param('is_channel/f'),
                'sort_order'  => (int)$this->request->param('sort_order/f'),
                'access_id'   => (int)$this->request->param('access_id/f'),
                'url'         => $this->request->param('url'),
                'update_time' => time()
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

    /**
     * 删除
     * @access public
     * @param
     * @return array
     */
    public function remove()
    {
        if ($result = $this->authenticate(__METHOD__, 'admin category remove')) {
            return $result;
        }

        if ($id = (int)$this->request->param('id/f')) {
            $result = (new ModelCategory)
                ->where([
                    ['id', '=', $id],
                    ['lang', '=', $this->lang->getLangSet()]
                ])
                ->find();

            $total = (new ModelArticle)
                ->where([
                    ['category_id', '=', $result['id']],
                ])
                ->count();
            if (0 === $total) {
                if ($result['image'] && $result['image'] = str_replace('/', DIRECTORY_SEPARATOR, $result['image'])) {
                    $result['image'] = $this->app->getRootPath() . 'public' . $result['image'];
                    if (is_file($result['image'])) {
                        @unlink($result['image']);
                    }
                }

                (new ModelCategory)
                    ->where([
                        ['id', '=', $id]
                    ])
                    ->delete();
                $msg = lang('remove category success');
            } else {
                $msg = lang('remove category not article');
            }
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => $msg
        ];
    }

    /**
     * 上传图片
     * @access public
     * @param
     * @return array
     */
    public function upload()
    {
        if ($result = $this->authenticate(__METHOD__, 'admin category upload image')) {
            return $result;
        }

        $result = $this->uploadFile('category');
        if (is_string($result)) {
            return [
                'debug' => false,
                'cache' => false,
                'code'  => 'ERROR',
                'msg'   => $result
            ];
        } else {
            return [
                'debug' => false,
                'cache' => false,
                'msg'   => 'upload success',
                'data'  => $result
            ];
        }
    }
}