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
        $file = (array) glob($this->app->getRootPath() . 'public' . DIRECTORY_SEPARATOR . 'theme' . DIRECTORY_SEPARATOR . 'cms' . DIRECTORY_SEPARATOR . '*');
        rsort($file);
        foreach ($file as $key => $value) {
            if (is_file($value . DIRECTORY_SEPARATOR . 'config.json')) {
                $config = file_get_contents($value . DIRECTORY_SEPARATOR . 'config.json');
                $config = json_decode($config, true);
            } else {
                $config = [
                    'theme'         => '未知',
                    'theme_version' => '未知',
                    'api_version'   => '未知',
                ];
            }

            $file[$key] = [
                'id'          => basename($value),
                'name'        => $config['theme'],
                'version'     => $config['theme_version'],
                'api_version' => $config['api_version'],
            ];
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'theme cms data',
            'data'  => [
                'list'  => $file,
                'total' => count($file)
            ]
        ];
    }

    public function editor()
    {
        $this->actionLog(__METHOD__, 'admin theme cms editor');
    }
}
