<?php

/**
 *
 * API接口层
 * 缓存
 *
 * @package   NICMS
 * @category  app\admin\logic\content
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\admin\logic\content;

use app\common\controller\BaseLogic;

class Cache extends BaseLogic
{
    protected $authKey = 'admin_auth_key';

    /**
     * 清除数据缓存
     * @access public
     * @return array
     */
    public function reCache(): array
    {
        $this->actionLog(__METHOD__, 'admin content cache reomve');

        $dir = $this->app->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR;
        $app = $this->app->config->get('app.domain_bind');
        $app = array_values($app);
        $app = array_unique($app);
        foreach ($app as $app_name) {
            $app_name = $dir . $app_name . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
            if (is_dir($app_name)) {
                $files = scandir($app_name);
                foreach ($files as $file_name) {
                    if ('.' == $file_name || '..' == $file_name) {
                        continue;
                    } elseif (is_file($app_name . $file_name)) {
                        @unlink($app_name . $file_name);
                    }
                }
            }
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success'
        ];
    }

    /**
     * 清除模板编译
     * @access public
     * @return array
     */
    public function reCompile(): array
    {
        $this->actionLog(__METHOD__, 'admin content compile reomve');

        $dir = $this->app->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR;
        $app = $this->app->config->get('app.domain_bind');
        $app = array_values($app);
        $app = array_unique($app);
        foreach ($app as $app_name) {
            $app_name = $dir . $app_name . DIRECTORY_SEPARATOR . 'compile' . DIRECTORY_SEPARATOR;
            if (is_dir($app_name)) {
                $files = scandir($app_name);
                foreach ($files as $dir_name) {
                    if ('.' == $dir_name || '..' == $dir_name) {
                        continue;
                    } elseif (is_dir($app_name . $dir_name)) {
                        $dir_name = $dir_name . DIRECTORY_SEPARATOR;
                        $_files = scandir($app_name . $dir_name);
                        foreach ($_files as $file_name) {
                            if ('.' == $file_name || '..' == $file_name) {
                                continue;
                            } elseif (is_file($app_name . $dir_name . $file_name)) {
                                @unlink($app_name . $dir_name . $file_name);
                            }
                        }
                        @rmdir($app_name . $dir_name);
                    }
                }
            }
        }

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success'
        ];
    }
}
