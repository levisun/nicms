<?php

/**
 *
 * API接口层
 * 模块
 *
 * @package   NICMS
 * @category  app\admin\logic\category
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\admin\logic\category;

use app\common\controller\BaseLogic;
use app\common\model\Models as ModelModel;

class Model extends BaseLogic
{
    protected $authKey = 'admin_auth_key';

    /**
     * 查询
     * @access public
     * @return array
     */
    public function query(): array
    {
        $result = ModelModel::order('id ASC')
            ->select()->toArray();

        foreach ($result as $key => $value) {
            $value['url'] = [
                'editor' => url('category/model/editor/' . $value['id']),
                'remove' => url('category/model/remove/' . $value['id']),
            ];

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
}
