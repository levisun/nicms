<?php
/**
 *
 * API接口层
 * 访问日志
 *
 * @package   NICMS
 * @category  app\logic\admin\extend
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
declare (strict_types = 1);

namespace app\logic\admin\extend;

use think\facade\Request;
use app\logic\admin\Base;
use app\model\Visit as ModelVisit;

class Visit extends Base
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

        if ($result = $this->check_params(['limit', 'date_format'])) {
            return $result;
        }

        $query_limit = (int)Request::param('limit/f', 10);

        $result = (new ModelVisit)
            ->order('date DESC')
            ->paginate($query_limit, false, ['path' => 'javascript:paging([PAGE]);']);

        $list = $result->toArray();
        $list['render'] = $result->render();

        $date_format = Request::param('date_format', 'Y-m-d');
        foreach ($list['data'] as $key => $value) {
            $value['date'] = date($date_format, $value['date']);
            $list['data'][$key] = $value;
        }

        return [
            'debug' => false,
            'cache' => true,
            'msg'   => 'visit log data',
            'data'  => [
                'list'         => $list['data'],
                'total'        => $list['total'],
                'per_page'     => $list['per_page'],
                'current_page' => $list['current_page'],
                'last_page'    => $list['last_page'],
                'page'         => $list['render'],
            ]
        ];
    }
}
