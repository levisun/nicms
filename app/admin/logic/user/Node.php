<?php

/**
 *
 * API接口层
 * 权限节点
 *
 * @package   NICMS
 * @category  app\admin\logic\user
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\admin\logic\user;

use app\common\controller\BaseLogic;
use app\common\model\Node as ModelNode;

class Node extends BaseLogic
{
    protected $authKey = 'admin_auth_key';

    /**
     * 查询
     * @access public
     * @return array
     */
    public function query(): array
    {
        $result = ModelNode::where('pid', '=', 0)->order('sort_order ASC, id ASC')->select();

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
            'msg'   => 'success',
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
        $result = ModelNode::where('pid', '=', $_pid)
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
            return $this->lang->get('button.node app type');
        } elseif ($_lid === 2) {
            return $this->lang->get('button.node controller type');
        } elseif ($_lid === 3) {
            return $this->lang->get('button.node action type');
        } else {
            return $this->lang->get('button.node method type');
        }
    }

    /**
     * 添加
     * @access public
     * @return array
     */
    public function added(): array
    {
        $receive_data = [
            'name'       => $this->request->param('name'),
            'title'      => $this->request->param('title'),
            'remark'     => $this->request->param('remark'),
            'pid'        => $this->request->param('pid/d', 0, 'abs'),
            'level'      => $this->request->param('level/d', 0, 'abs'),
            'status'     => $this->request->param('status/d', 0, 'abs'),
            'sort_order' => $this->request->param('sort_order/d', 0, 'abs'),
        ];
        if ($result = $this->validate($receive_data)) {
            return $result;
        }

        $this->actionLog('admin node added');
        ModelNode::create($receive_data);

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success',
        ];
    }

    /**
     * 查询
     * @access public
     * @return array
     */
    public function find(): array
    {
        $result = [];
        if ($id = $this->request->param('id/d', 0, 'abs')) {
            $result = ModelNode::where('id', '=', $id)->find();

            if (null !== $result && $result = $result->toArray()) {
                $result['parent'] = ModelNode::where('id', '=', $result['pid'])->value('name as parent');
            }
        } else {
            if ($pid = $this->request->param('pid/d', 0, 'abs')) {
                $result['pid'] = $pid;
                $result['parent'] = ModelNode::where('id', '=', $pid)->value('name as parent');
            }
        }

        $result['level_list'] = [
            ['id' => '1', 'name' => $this->lang->get('button.node app type')],
            ['id' => '2', 'name' => $this->lang->get('button.node controller type')],
            ['id' => '3', 'name' => $this->lang->get('button.node action type')],
            ['id' => '4', 'name' => $this->lang->get('button.node method type')],
        ];

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success',
            'data'  => isset($result) ? $result : []
        ];
    }

    /**
     * 编辑
     * @access public
     * @return array
     */
    public function editor(): array
    {
        if (!$id = $this->request->param('id/d', 0, 'abs')) {
            return [
                'debug' => false,
                'cache' => false,
                'code'  => 40001,
                'msg'   => 'error'
            ];
        }

        $receive_data = [
            'name'       => $this->request->param('name'),
            'title'      => $this->request->param('title'),
            'remark'     => $this->request->param('remark'),
            'id'         => $this->request->param('id/d', 0, 'abs'),
            'pid'        => $this->request->param('pid/d', 0, 'abs'),
            'level'      => $this->request->param('level/d', 0, 'abs'),
            'status'     => $this->request->param('status/d', 0, 'abs'),
            'sort_order' => $this->request->param('sort_order/d', 0, 'abs'),
        ];
        if ($result = $this->validate($receive_data)) {
            return $result;
        }

        $this->actionLog('admin node editor ID:' . $id);
        ModelNode::where('id', '=', $id)->limit(1)->update($receive_data);

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success'
        ];
    }

    /**
     * 删除
     * @access public
     * @return array
     */
    public function remove(): array
    {
        if (!$id = $this->request->param('id/d', 0, 'abs')) {
            return [
                'debug' => false,
                'cache' => false,
                'code'  => 40001,
                'msg'   => 'error'
            ];
        }

        $this->actionLog('admin node remove ID:' . $id);
        ModelNode::where('id', '=', $id)->limit(1)->delete();

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success'
        ];
    }
}
