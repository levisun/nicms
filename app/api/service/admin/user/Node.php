<?php

/**
 *
 * API接口层
 * 权限节点
 *
 * @package   NICMS
 * @category  app\service\admin\user
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\service\admin\user;

use app\service\BaseService;
use app\model\Node as ModelNode;

class Node extends BaseService
{
    protected $authKey = 'admin_auth_key';

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

        $result = (new ModelNode)
            ->where([
                ['pid', '=', 0],
            ])
            ->order('sort_order ASC, id ASC')
            ->select();

        $result = $result ? $result->toArray() : [];

        foreach ($result as $key => $value) {
            $value['level_name'] = $this->typeName($value['level']);
            $value['url'] = [
                'added'  => url('user/node/added/' . $value['id']),
                'editor' => url('user/node/editor/' . $value['id']),
                'remove' => url('user/node/remove/' . $value['id']),
            ];
            $value['child'] = $this->child((int) $value['id']);
            $result[$key] = $value;
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'node data',
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
        $result = (new ModelNode)
            ->where([
                ['pid', '=', $_pid],
            ])
            ->order('sort_order ASC, id ASC')
            ->select();

        $result = $result ? $result->toArray() : [];

        foreach ($result as $key => $value) {
            $value['level_name'] = $this->typeName($value['level']);
            $value['url'] = [
                'added'  => url('user/node/added/' . $value['id']),
                'editor' => url('user/node/editor/' . $value['id']),
                'remove' => url('user/node/remove/' . $value['id']),
            ];
            $value['child'] = $this->child((int) $value['id']);
            $result[$key] = $value;
        }

        return !empty($result) ? $result : false;
    }

    /**
     * 节点类型
     * @access private
     * @param  int $_lid
     * @return string
     */
    private function typeName(int $_lid): string
    {
        if ($_lid === 1) {
            return $this->lang->get('node app type');
        } elseif ($_lid === 2) {
            return $this->lang->get('node controller type');
        } elseif ($_lid === 3) {
            return $this->lang->get('node action type');
        } else {
            return $this->lang->get('node method type');
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
        if ($result = $this->authenticate(__METHOD__, 'admin node added')) {
            return $result;
        }

        $receive_data = [
            'name'       => $this->request->param('name'),
            'title'      => $this->request->param('title'),
            'remark'     => $this->request->param('remark'),
            'pid'        => (int) $this->request->param('pid/f'),
            'level'      => (int) $this->request->param('level/f'),
            'status'     => (int) $this->request->param('status/f'),
            'sort_order' => (int) $this->request->param('sort_order/f'),
        ];
        if ($result = $this->validate(__METHOD__, $receive_data)) {
            return $result;
        }

        (new ModelNode)->create($receive_data);

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'node added success',
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

        if ($id = (int) $this->request->param('id/f')) {
            $result = (new ModelNode)
                ->where([
                    ['id', '=', $id],
                ])
                ->find();

            if ($result && $result = $result->toArray()) {
                $result['parent'] = (new ModelNode)
                    ->where([
                        ['id', '=', $result['pid']]
                    ])
                    ->value('name as parent');

                $result['level_list'] = [
                    ['id' => '1', 'name' => $this->lang->get('node app  type')],
                    ['id' => '2', 'name' => $this->lang->get('node controller type')],
                    ['id' => '3', 'name' => $this->lang->get('node action type')],
                    ['id' => '4', 'name' => $this->lang->get('node method type')],
                ];
            }
        } else {
            $result = [];
            if ($pid = (int) $this->request->param('pid/f', '0')) {
                $result['pid'] = $pid;
                $result['parent'] = (new ModelNode)
                    ->where([
                        ['id', '=', $pid]
                    ])
                    ->value('name as parent');
            }
            $result['level_list'] = [
                ['id' => '1', 'name' => $this->lang->get('node app  type')],
                ['id' => '2', 'name' => $this->lang->get('node controller type')],
                ['id' => '3', 'name' => $this->lang->get('node action type')],
                ['id' => '4', 'name' => $this->lang->get('node method type')],
            ];
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'node data',
            'data'  => isset($result) ? $result : []
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
        if ($result = $this->authenticate(__METHOD__, 'admin node editor')) {
            return $result;
        }

        if (!$id = (int) $this->request->param('id/f')) {
            return [
                'debug' => false,
                'cache' => false,
                'code'  => 40001,
                'msg'   => '请求错误'
            ];
        }

        $receive_data = [
            'name'       => $this->request->param('name'),
            'title'      => $this->request->param('title'),
            'remark'     => $this->request->param('remark'),
            'id'         => (int) $this->request->param('id/f'),
            'pid'        => (int) $this->request->param('pid/f'),
            'level'      => (int) $this->request->param('level/f'),
            'status'     => (int) $this->request->param('status/f'),
            'sort_order' => (int) $this->request->param('sort_order/f'),
        ];
        if ($result = $this->validate(__METHOD__, $receive_data)) {
            return $result;
        }

        (new ModelNode)
            ->where([
                ['id', '=', $id]
            ])
            ->data($receive_data)
            ->update();

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'node editor success'
        ];
    }

    /**
     * 删除
     * @access public
     * @param
     * @return array
     */
    public function remove(): array
    {
        if ($result = $this->authenticate(__METHOD__, 'admin node remove')) {
            return $result;
        }

        if (!$id = (int) $this->request->param('id/f')) {
            return [
                'debug' => false,
                'cache' => false,
                'code'  => 40001,
                'msg'   => '请求错误'
            ];
        }

        (new ModelNode)
            ->where([
                ['id', '=', $id]
            ])
            ->delete();

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'remove node success'
        ];
    }
}
