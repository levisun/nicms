<?php
/**
 *
 * API接口层
 * 模块
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
use app\model\Models as ModelModel;

class Model extends BaseService
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

        $result = (new ModelModel)
            ->order('id ASC')
            ->select()
            ->toArray();

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
            'msg'   => 'model data',
            'data'  => [
                'list'  => $result,
                'total' => count($result),
            ]
        ];
    }
}
