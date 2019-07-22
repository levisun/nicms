<?php
/**
 *
 * API接口层
 * CMS主题
 *
 * @package   NICMS
 * @category  app\service\admin\theme
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
declare (strict_types = 1);

namespace app\service\admin\theme;

use app\service\BaseService;

class Cms extends BaseService
{
    protected $authKey = 'admin_auth_key';

    /**
     * 查询
     * @access public
     * @param
     * @return array
     */
    public function query()
    {
        if ($result = $this->authenticate(__METHOD__)) {
            return $result;
        }

        $file = (array)glob($this->app->getRootPath() . 'public' . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR . 'cms' . DIRECTORY_SEPARATOR . '*');
        rsort($file);
        foreach ($file as $key => $value) {
            $config = file_get_contents($value . DIRECTORY_SEPARATOR . 'config.json');
            $config = json_decode($config, true);
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
        if ($result = $this->authenticate(__METHOD__, 'admin theme cms editor')) {
            return $result;
        }
    }
}
