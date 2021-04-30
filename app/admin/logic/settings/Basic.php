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
            ->where('name', 'in', 'cms_sitename,cms_keywords,cms_description,cms_copyright')
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
            ->where('name', 'in', 'book_sitename,book_keywords,book_description,book_copyright')
            ->where('lang', '=', $this->lang->getLangSet())
            ->select();
        $result = $result ? $result->toArray() : [];
        foreach ($result as $key => $value) {
            $value['value'] = Filter::htmlDecode($value['value']);
            $result[$value['name']] = $value['value'];
            unset($result[$key]);
        }
        $config['book'] = $result;

        $result = ModelConfig::field(['name', 'value'])
            ->where('name', 'in', 'user_sitename,user_keywords,user_description,user_copyright')
            ->where('lang', '=', $this->lang->getLangSet())
            ->select();
        $result = $result ? $result->toArray() : [];
        foreach ($result as $key => $value) {
            $value['value'] = Filter::htmlDecode($value['value']);
            $result[$value['name']] = $value['value'];
            unset($result[$key]);
        }
        $config['user'] = $result;

        $result = ModelConfig::field(['name', 'value'])
            ->where('name', 'in', 'copyright,bottom,beian,script')
            ->where('lang', '=', $this->lang->getLangSet())
            ->select();
        $result = $result ? $result->toArray() : [];
        foreach ($result as $key => $value) {
            $value['value'] = Filter::htmlDecode($value['value']);
            $config[$value['name']] = $value['value'];
            unset($result[$key]);
        }

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

            'book_sitename'    => $this->request->param('book_sitename'),
            'book_keywords'    => $this->request->param('book_keywords'),
            'book_description' => $this->request->param('book_description'),

            'user_sitename'    => $this->request->param('user_sitename'),
            'user_keywords'    => $this->request->param('user_keywords'),
            'user_description' => $this->request->param('user_description'),

            'footer'      => $this->request->param('footer'),
            'copyright'   => $this->request->param('copyright', '', '\app\common\library\Filter::htmlEncode'),
            'beian'       => $this->request->param('beian', '', '\app\common\library\Filter::htmlEncode'),
            'script'      => $this->request->param('script', '', 'strip_tags,trim,htmlspecialchars'),
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
