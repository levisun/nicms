<?php
/**
 *
 * API接口层
 * 网站设置
 *
 * @package   NICMS
 * @category  app\logic\admin\settings
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
declare (strict_types = 1);

namespace app\logic\admin\settings;

use think\facade\Config;
use think\facade\Lang;
use think\facade\Request;
use app\logic\admin\Base;
use app\model\Config as ModelConfig;

class Basic extends Base
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

        $result =
        (new ModelConfig)
        ->field(['name', 'value'])
        ->where([
            ['lang', '=', Lang::getLangSet()],
            ['name', 'in', 'cms_sitename,cms_keywords,cms_description,cms_footer,cms_copyright,cms_beian,cms_script']
        ])
        ->select()
        ->toArray();

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
        if ($result = $this->authenticate(__METHOD__)) {
            return $result;
        }

        $receive_data = [
            'cms_sitename'    => Request::post('cms_sitename'),
            'cms_keywords'    => Request::post('cms_keywords'),
            'cms_description' => Request::post('cms_description'),
            'cms_footer'      => Request::post('cms_footer'),
            'cms_copyright'   => Request::post('cms_copyright', '', 'safe_con_filter'),
            'cms_beian'       => Request::post('cms_beian'),
            'script'          => Request::post('script', '', 'trim,htmlspecialchars'),
        ];

        if (true || !$result = $this->__validate('config', $receive_data)) {
            foreach ($receive_data as $key => $value) {
                (new ModelConfig)->where([
                    ['name', '=', $key]
                ])
                ->data([
                    'value' => $value
                ])
                ->update();
            }
            $result = 'editor basic success';
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => $result,
        ];
    }
}
