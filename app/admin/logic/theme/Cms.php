<?php

/**
 *
 * API接口层
 * CMS主题
 *
 * @package   NICMS
 * @category  app\admin\logic\theme
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\admin\logic\theme;

use app\common\controller\BaseLogic;
use app\common\model\Config as ModelConfig;
use app\common\library\Base64;

class Cms extends BaseLogic
{
    protected $authKey = 'admin_auth_key';

    /**
     * 查询
     * @access public
     * @return array
     */
    public function query()
    {
        $files = (array) glob($this->app->getRootPath() . 'public' . DIRECTORY_SEPARATOR . 'theme' . DIRECTORY_SEPARATOR . 'cms' . DIRECTORY_SEPARATOR . '*');
        rsort($files);
        foreach ($files as $key => $value) {
            if (is_file($value . DIRECTORY_SEPARATOR . 'config.json')) {
                $config = file_get_contents($value . DIRECTORY_SEPARATOR . 'config.json');
                $config = json_decode($config, true);
                if (!is_array($config)) {
                    unset($files[$key]);
                    continue;
                }
            } else {
                unset($files[$key]);
                continue;
            }

            $value = basename($value);
            $value = Base64::encrypt($value);
            $value = rtrim($value, '=');
            $files[$key] = [
                'id'          => $value,
                'img'         => isset($config['img']) ? $config['img'] : '',
                'name'        => $config['theme'],
                'version'     => $config['theme_version'],
                'api_version' => $config['api_version'],
            ];
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success',
            'data'  => [
                'list'  => $files,
                'total' => count($files)
            ]
        ];
    }

    public function editor()
    {
        $this->actionLog(__METHOD__, 'admin theme cms editor');

        $id = $this->request->param('id');
        if ($id && $id = Base64::decrypt($id)) {
            $path = $this->app->getRootPath() . 'public' . DIRECTORY_SEPARATOR . 'theme' . DIRECTORY_SEPARATOR . 'cms' . DIRECTORY_SEPARATOR;
            if (is_dir($path . $id)) {
                (new ModelConfig)
                    ->where([
                        ['name', '=', 'cms_theme']
                    ])
                    ->data([
                        'value' => $id
                    ])
                    ->update();
            }
        }

        $this->cache->tag('siteinfo')->clear();

        $this->result = call_user_func([
            $this->app->make('\app\admin\logic\content\Cache'),
            'request'
        ]);
        $this->result = call_user_func([
            $this->app->make('\app\admin\logic\content\Cache'),
            'compile'
        ]);

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success',
        ];
    }
}
