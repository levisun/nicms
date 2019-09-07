<?php

/**
 *
 * API接口层
 * 访问日志
 *
 * @package   NICMS
 * @category  app\service\admin\extend
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\service\admin\extend;

use app\service\BaseService;
use app\model\Visit as ModelVisit;

class Visit extends BaseService
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

        $query_limit = (int) $this->request->param('limit/f', 10);
        $date_format = $this->request->param('date_format', 'Y-m-d');

        $result = (new ModelVisit)
            ->order('date DESC')
            ->paginate([
                'list_rows'=> $query_limit,
                'path' => 'javascript:paging([PAGE]);',
            ]);

        $list = $result->toArray();
        $list['render'] = $result->render();

        foreach ($list['data'] as $key => $value) {
            $value['date'] = date($date_format, $value['date']);
            $list['data'][$key] = $value;
        }

        return [
            'debug' => false,
            'cache' => false,
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
