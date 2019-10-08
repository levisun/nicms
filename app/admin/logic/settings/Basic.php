<?php

/**
 *
 * API接口层
 * 网站设置
 *
 * @package   NICMS
 * @category  app\admin\logic\settings
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\admin\logic\settings;

use app\common\controller\BaseLogic;
use app\common\model\Config as ModelConfig;

class Basic extends BaseLogic
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

        $result = (new ModelConfig)
            ->field(['name', 'value'])
            ->where([
                ['lang', '=', $this->lang->getLangSet()],
                ['name', 'in', 'cms_sitename,cms_keywords,cms_description,cms_footer,cms_copyright,cms_beian,cms_script']
            ])
            ->select();

        $result = $result ? $result->toArray() : [];

        foreach ($result as $key => $value) {
            $value['value'] = htmlspecialchars_decode($value['value']);
            $result[$value['name']] = $value['value'];
            unset($result[$key]);
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'basic data',
            'data'  => $result
        ];
    }

    /**
     * 编辑
     * @access public
     * @param
     * @return array
     */
    public function editor(): array
    {
        if ($result = $this->authenticate(__METHOD__, 'admin basic editor')) {
            return $result;
        }

        $receive_data = [
            'cms_sitename'    => $this->request->param('cms_sitename'),
            'cms_keywords'    => $this->request->param('cms_keywords'),
            'cms_description' => $this->request->param('cms_description'),
            'cms_footer'      => $this->request->param('cms_footer'),
            'cms_copyright'   => $this->request->param('cms_copyright', '', 'content_filter'),
            'cms_beian'       => $this->request->param('cms_beian'),
            'script'          => $this->request->param('script', '', 'trim,htmlspecialchars'),
        ];
        if ($result = $this->validate(__METHOD__, $receive_data)) {
            return $result;
        }

        foreach ($receive_data as $key => $value) {
            (new ModelConfig)
                ->where([
                    ['name', '=', $key]
                ])
                ->data([
                    'value' => $value
                ])
                ->update();
        }

        $this->cache->tag('SYSTEM')->clear();

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'editor success'
        ];
    }
}
