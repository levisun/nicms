<?php

/**
 *
 * API接口层
 * 留言列表
 *
 * @package   NICMS
 * @category  app\cms\logic\message
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\cms\logic\message;

use app\common\controller\BaseLogic;
use app\common\model\Message as ModelMessage;
use app\common\model\Fields as ModelFields;

class Catalog extends BaseLogic
{

    /**
     * 查询列表
     * @access public
     * @param
     * @return array
     */
    public function query()
    {
        $list = false;
        if ($category_id = $this->request->param('category_id', 0, '\app\common\library\Base64::url62decode')) {

            $query_page = $this->request->param('page/d', 1, 'abs');
            if ($query_page > $this->ERPCache()) {
                return [
                    'debug' => false,
                    'cache' => true,
                    'msg'   => 'error',
                ];
            }

            $cache_key = $this->getCacheKey('cms message catalog');
            if (!$this->cache->has($cache_key) || !$list = $this->cache->get($cache_key)) {
                $result = ModelMessage::where('is_pass', '=', 1)
                    ->where('category_id', '=', $category_id)
                    ->order('id DESC')
                    ->paginate([
                        'list_rows' => $this->getQueryLimit(),
                        'path' => 'javascript:paging([PAGE]);',
                    ], true);

                if ($result && $list = $result->toArray()) {
                    if (empty($list['data'])) {
                        $this->ERPCache($query_page);
                    }

                    $list['render'] = $result->render();

                    $date_format = $this->request->param('date_format', 'Y-m-d');
                    foreach ($list['data'] as $key => $value) {
                        $value['create_time'] = date($date_format, (int) $value['create_time']);
                        $value['update_time'] = date($date_format, (int) $value['update_time']);

                        // 附加字段数据
                        $fields = ModelFields::view('fields', ['id'])
                            ->view('fields_extend', ['data'], 'fields_extend.fields_id=fields.id')
                            // ->view('fields_type', ['name' => 'fields_type'])
                            ->where('fields.category_id', '=', $category_id)
                            ->select()
                            ->toArray();
                        foreach ($fields as $val) {
                            $value[$val['fields_name']] = $val['data'];
                        }

                        $list['data'][$key] = $value;
                    }

                    $this->cache->tag('cms message list' . $category_id)->set($cache_key, $list);
                }
            }
        }

        return [
            'debug' => false,
            'cache' => $list ? true : false,
            'msg'   => $list ? 'category' : 'error',
            'data'  => $list ? [
                'list'         => $list['data'],
                'per_page'     => $list['per_page'],
                'current_page' => $list['current_page'],
                'page'         => $list['render'],
            ] : []
        ];
    }
}
