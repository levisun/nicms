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
use app\logic\admin\Base;
use app\model\Models as ModelModel;

class Model extends Base
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
