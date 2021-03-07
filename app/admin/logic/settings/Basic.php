<?php

/**
 *
 * API接口层
 * 网站设置
 *
 * @package   NICMS
 * @category  app\admin\logic\settings
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\admin\logic\settings;

use app\common\controller\BaseLogic;
use app\common\library\Filter;
use app\common\model\Config as ModelConfig;

class Basic extends BaseLogic
{
    protected $authKey = 'admin_auth_key';

    /**
     * 查询
     * @access public
     * @return array
     */
    public function query(): array
    {
        $config = [];
        $result = ModelConfig::field(['name', 'value'])
            ->where('name', 'in', 'cms_sitename,cms_keywords,cms_description,cms_footer,cms_copyright,cms_beian,cms_script')
            ->where('lang', '=', $this->lang->getLangSet())
            ->select();
        $result = $result ? $result->toArray() : [];
        foreach ($result as $key => $value) {
            $value['value'] = Filter::htmlDecode($value['value']);
            $result[$value['name']] = $value['value'];
            unset($result[$key]);
        }
        $config['cms'] = $result;

        $result = ModelConfig::field(['name', 'value'])
            ->where('name', 'in', 'book_sitename,book_keywords,book_description,book_footer,book_copyright,book_beian,book_script')
            ->where('lang', '=', $this->lang->getLangSet())
            ->select();
        $result = $result ? $result->toArray() : [];
        foreach ($result as $key => $value) {
            $value['value'] = Filter::htmlDecode($value['value']);
            $result[$value['name']] = $value['value'];
            unset($result[$key]);
        }
        $config['book'] = $result;

        $this->cache->tag('system')->clear();

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success',
            'data'  => $config
        ];
    }

    /**
     * 编辑
     * @access public
     * @return array
     */
    public function editor(): array
    {
        $this->actionLog('admin basic editor');

        $receive_data = [
            'cms_sitename'    => $this->request->param('cms_sitename'),
            'cms_keywords'    => $this->request->param('cms_keywords'),
            'cms_description' => $this->request->param('cms_description'),
            'cms_footer'      => $this->request->param('cms_footer'),
            'cms_copyright'   => $this->request->param('cms_copyright', '', '\app\common\library\Filter::htmlEncode'),
            'cms_beian'       => $this->request->param('cms_beian', '', '\app\common\library\Filter::htmlEncode'),
            'cms_script'      => $this->request->param('cms_script', '', 'strip_tags,trim,htmlspecialchars'),

            'book_sitename'    => $this->request->param('book_sitename'),
            'book_keywords'    => $this->request->param('book_keywords'),
            'book_description' => $this->request->param('book_description'),
            'book_footer'      => $this->request->param('book_footer'),
            'book_copyright'   => $this->request->param('book_copyright', '', '\app\common\library\Filter::htmlEncode'),
            'book_beian'       => $this->request->param('book_beian', '', '\app\common\library\Filter::htmlEncode'),
            'book_script'      => $this->request->param('book_script', '', 'strip_tags,trim,htmlspecialchars'),

            'user_sitename'    => $this->request->param('user_sitename'),
            'user_keywords'    => $this->request->param('user_keywords'),
            'user_description' => $this->request->param('user_description'),
            'user_footer'      => $this->request->param('user_footer'),
            'user_copyright'   => $this->request->param('user_copyright', '', '\app\common\library\Filter::htmlEncode'),
            'user_beian'       => $this->request->param('user_beian', '', '\app\common\library\Filter::htmlEncode'),
            'user_script'      => $this->request->param('user_script', '', 'strip_tags,trim,htmlspecialchars'),
        ];
        if ($result = $this->validate($receive_data)) {
            return $result;
        }

        foreach ($receive_data as $key => $value) {
            ModelConfig::where('lang', '=', $this->lang->getLangSet())
                ->where('name', '=', $key)
                ->limit(1)
                ->update([
                    'value' => $value
                ]);
        }

        $this->cache->tag('system')->clear();

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success'
        ];
    }
}
