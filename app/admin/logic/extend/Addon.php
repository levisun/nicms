<?php

/**
 *
 * 扩展插件
 *
 * @package   NICMS
 * @category  app\admin\logic\extend
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\admin\logic\extend;

use app\common\controller\BaseLogic;
use app\common\library\Base64;
use app\common\library\Addon as LibAddon;

class Addon extends BaseLogic
{
    protected $authKey = 'admin_auth_key';

    /**
     * 查询
     * @access public
     * @return array
     */
    public function query(): array
    {
        $result = (new LibAddon)->query();

        foreach ($result as $key => $value) {
            $value['id'] = Base64::encrypt($key, date('Ymd'));

            $value['url'] = [
                'editor' => url('extend/addon/editor', ['id' => $value['id']]),
                'remove' => url('extend/addon/remove/' . $value['id']),
            ];

            $result[$key] = $value;
        }
        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success',
            'data'  => [
                'list'  => $result,
                'total' => count($result)
            ]
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
        if ($id = $this->request->param('id', 0)) {
            $namespace = Base64::decrypt($id, date('Ymd'));
            $result = (new LibAddon)->find($namespace);
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success',
            'data'  => $result
        ];
    }
}
