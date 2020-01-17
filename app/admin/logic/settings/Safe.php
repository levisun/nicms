<?php

/**
 *
 * API接口层
 * 安全设置
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

class Safe extends BaseLogic
{
    protected $authKey = 'admin_auth_key';

    /**
     * 查询
     * @access public
     * @return array
     */
    public function query(): array
    {
        $env = $this->app->env;

        $result = [
            'app_debug' => $env->get('app_debug'),
            'app' => [
                'upload_type' => $env->get('app.upload_type'),
                'upload_size' => $env->get('app.upload_size'),
                'secretkey' => $env->get('app.secretkey'),
            ],
            'cache' => [
                'type'   => $env->get('cache.type'),
                'expire' => $env->get('cache.expire'),
            ],
            'database' => [
                'type'     => $env->get('database.type'),
                'hostname' => $env->get('database.hostname'),
                'database' => $env->get('database.database'),
                'username' => $env->get('database.username'),
                'password' => $env->get('database.password'),
                'hostport' => $env->get('database.hostport'),
                'prefix'   => $env->get('database.prefix'),
            ],
            'admin' => [
                'entry'   => $this->config->get('app.entry') . '.' . $this->request->rootDomain(),
                'theme'   => $this->config->get('app.theme'),
            ]
        ];

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success',
            'data'  => $result
        ];
    }

    /**
     * 编辑
     * @access public
     * @return array
     */
    public function editor(): array
    {
        $this->actionLog(__METHOD__, 'admin safe editor');

        $receive_data = [
            'app_upload_size' => $this->request->param('app.upload_size/d', 1),
            'app_upload_type' => $this->request->param('app.upload_type'),
            'cache_expire'    => $this->request->param('cache.expire/d', 28800),
            'app_debug'       => $this->request->param('app_debug/d'),
        ];
        if ($result = $this->validate(__METHOD__, $receive_data)) {
            return $result;
        }

        $env = $this->app->env;

        $receive_data['app_debug'] = $receive_data['app_debug'] ? 'true' : 'false';
        $result = 'APP_DEBUG = '  . $receive_data['app_debug'] . PHP_EOL .
            PHP_EOL . '[APP]' . PHP_EOL .
            'UPLOAD_SIZE = ' . $receive_data['app_upload_size'] . PHP_EOL .
            'UPLOAD_TYPE = ' . $receive_data['app_upload_type'] . PHP_EOL .
            'SECRETKEY = ' . $env->get('app.secretkey') . PHP_EOL .
            'DEFAULT_TIMEZONE = ' . $env->get('app.default_timezone') . PHP_EOL .

            PHP_EOL . '[DATABASE]' . PHP_EOL .
            'TYPE = ' . $env->get('database.type') . PHP_EOL .
            'HOSTNAME = ' . $env->get('database.hostname') . PHP_EOL .
            'DATABASE = ' . $env->get('database.database') . PHP_EOL .
            'USERNAME = ' . $env->get('database.username') . PHP_EOL .
            'PASSWORD = ' . $env->get('database.password') . PHP_EOL .
            'HOSTPORT = ' . $env->get('database.hostport') . PHP_EOL .
            'PREFIX   = ' . $env->get('database.prefix') . PHP_EOL .

            PHP_EOL . '[CACHE]' . PHP_EOL .
            'TYPE = ' . $env->get('cache.type') . PHP_EOL .
            'EXPIRE = ' . $receive_data['cache_expire'] . PHP_EOL .

            PHP_EOL . '[ADMIN]' . PHP_EOL .
            'ENTRY = ' . $env->get('admin.entry') . PHP_EOL .
            'THEME = ' . $env->get('admin.theme') . PHP_EOL .

            PHP_EOL . '[LANG]' . PHP_EOL .
            'DEFAULT_LANG = ' . $env->get('lang.default_lang');

        file_put_contents($this->app->getRootPath() . '.env', $result);

        $this->cache->tag('SYSTEM')->clear();

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success'
        ];
    }
}
