<?php

/**
 *
 * API接口层
 * 安全设置
 *
 * @package   NICMS
 * @category  app\service\admin\settings
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\service\admin\settings;

use app\service\BaseService;

class Safe extends BaseService
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

        $result = [
            'app_debug' => $this->env->get('app_debug'),
            'app' => [
                'upload_type' => $this->env->get('app.upload_type'),
                'upload_size' => $this->env->get('app.upload_size'),
                'secretkey' => $this->env->get('app.secretkey'),
            ],
            'cache' => [
                'type'   => $this->env->get('cache.type'),
                'expire' => $this->env->get('cache.expire'),
            ],
            'database' => [
                'type'     => $this->env->get('database.type'),
                'hostname' => $this->env->get('database.hostname'),
                'database' => $this->env->get('database.database'),
                'username' => $this->env->get('database.username'),
                'password' => $this->env->get('database.password'),
                'hostport' => $this->env->get('database.hostport'),
                'prefix'   => $this->env->get('database.prefix'),
            ],
            'admin' => [
                'entry'   => $this->config->get('app.entry'),
                'theme'   => $this->config->get('app.theme'),
            ]
        ];

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'safe data',
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
        if ($result = $this->authenticate(__METHOD__, 'admin safe editor')) {
            return $result;
        }

        $receive_data = [
            'app_upload_size'   => $this->request->param('app.upload_size'),
            'app_upload_type'   => $this->request->param('app.upload_type'),
            'cache_expire'      => $this->request->param('cache.expire'),
            'app_debug'       => $this->request->param('app_debug'),
        ];
        if ($result = $this->validate(__METHOD__, $receive_data)) {
            return $result;
        }

        $receive_data['app_debug'] = $receive_data['app_debug'] ? 'true' : 'false';
        $result = 'APP_DEBUG = '  . $receive_data['app_debug'] . PHP_EOL .
            PHP_EOL . '[APP]' . PHP_EOL .
            'UPLOAD_SIZE = ' . $receive_data['app_upload_size'] . PHP_EOL .
            'UPLOAD_TYPE = ' . $receive_data['app_upload_type'] . PHP_EOL .
            'SECRETKEY = ' . $this->env->get('app.secretkey') . PHP_EOL .
            'DEFAULT_TIMEZONE = ' . $this->env->get('app.default_timezone') . PHP_EOL .

            PHP_EOL . '[DATABASE]' . PHP_EOL .
            'TYPE = ' . $this->env->get('database.type') . PHP_EOL .
            'HOSTNAME = ' . $this->env->get('database.hostname') . PHP_EOL .
            'DATABASE = ' . $this->env->get('database.database') . PHP_EOL .
            'USERNAME = ' . $this->env->get('database.username') . PHP_EOL .
            'PASSWORD = ' . $this->env->get('database.password') . PHP_EOL .
            'HOSTPORT = ' . $this->env->get('database.hostport') . PHP_EOL .
            'PREFIX   = ' . $this->env->get('database.prefix') . PHP_EOL .

            PHP_EOL . '[CACHE]' . PHP_EOL .
            'TYPE = ' . $this->env->get('cache.type') . PHP_EOL .
            'EXPIRE = ' . $receive_data['cache_expire'] . PHP_EOL .

            PHP_EOL . '[ADMIN]' . PHP_EOL .
            'ENTRY = ' . $this->env->get('admin.entry') . PHP_EOL .
            'THEME = ' . $this->env->get('admin.theme') . PHP_EOL .

            PHP_EOL . '[LANG]' . PHP_EOL .
            'DEFAULT_LANG = ' . $this->env->get('lang.default_lang');

        file_put_contents($this->app->getRootPath() . '.env', $result);

        $this->cache->tag('SYSTEM')->clear();

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'editor success'
        ];
    }
}
