<?php

/**
 *
 * API接口层
 * 留言
 *
 * @package   NICMS
 * @category  app\cms\logic\message
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\cms\logic\message;

use app\common\controller\BaseLogic;
use app\common\model\Message as ModelMessage;
use app\common\model\Fields as ModelFields;

class Form extends BaseLogic
{

    /**
     * 查询表单
     * @access public
     * @param
     * @return array
     */
    public function query(): array
    {
        $result = false;
        if ($category_id = $this->request->param('cid/d', 0, 'abs')) {
            $result = [
                [
                    'input_name' => 'title',
                    'input_type' => 'text',
                    'text_name' => $this->lang->get('message.title'),
                ],
                [
                    'input_name' => 'username',
                    'input_type' => 'text',
                    'text_name' => $this->lang->get('message.username'),
                ],
                [
                    'input_name' => 'content',
                    'input_type' => 'textarea',
                    'text_name' => $this->lang->get('message.content'),
                ]
            ];

            // 附加字段数据
            $fields = ModelFields::view('fields', ['id'])
                ->view('fields_extend', ['data'], 'fields_extend.fields_id=fields.id')
                // ->view('fields_type', ['name' => 'fields_type'])
                ->where([
                    ['fields.category_id', '=', $category_id],
                ])
                ->select()
                ->toArray();
            foreach ($fields as $value) {
                $result[] = [
                    'input_name' => $value['fields_name'],
                    // 'input_type' => $value['fields_type'],
                    'text_name'  => $value['data'],
                ];
            }
        }

        return [
            'debug' => false,
            'cache' => $result ? true : false,
            'msg'   => $result ? 'message' : 'error',
            'data'  => $result ?: []
        ];
    }

    /**
     * 添加
     * @access public
     * @param
     * @return array
     */
    public function record(): array
    {
        $result = false;
        if ($category_id = $this->request->param('cid/d', 0, 'abs')) {
            $receive_data = [
                'captcha'     => (string) $this->request->param('captcha'),
                'title'       => $this->request->param('title'),
                'username'    => $this->request->param('username'),
                'content'     => $this->request->param('content'),
                'category_id' => $category_id,
            ];

            if ($result = $this->validate(__METHOD__, $receive_data)) {
                return $result;
            }

            // 附加字段数据
            $fields = ModelFields::view('fields', ['id'])
                ->view('fields_extend', ['data'], 'fields_extend.fields_id=fields.id')
                // ->view('fields_type', ['name' => 'fields_type'])
                ->where([
                    ['fields.category_id', '=', $category_id],
                ])
                ->select()
                ->toArray();
            foreach ($fields as $value) {
                $receive_data[$value['fields_name']] = $this->request->param($value['fields_name']);
            }

            unset($receive_data['captcha']);

            $result = ModelMessage::create($receive_data);
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => $result ? 'success' : 'error',
        ];
    }
}
