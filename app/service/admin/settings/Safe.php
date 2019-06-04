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
declare (strict_types = 1);

namespace app\service\admin\settings;

use app\service\BaseService;

class Safe extends BaseService
{
    protected $auth_key = 'admin_auth_key';
    protected $cache_tag = 'admin';

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
            'app' => [
                'upload_type' => $this->config->get('app.upload_type'),
                'upload_size' => $this->config->get('app.upload_size'),
            ],
            'cache' => [
                'type'   => $this->config->get('cache.type'),
                'expire' => $this->config->get('cache.expire'),
            ],
            'database' => [
                'type'     => $this->config->get('database.type'),
                'hostname' => $this->config->get('database.hostname'),
                'database' => $this->config->get('database.database'),
                'username' => $this->config->get('database.username'),
                'password' => $this->config->get('database.password'),
                'hostport' => $this->config->get('database.hostport'),
                'prefix'   => $this->config->get('database.prefix'),
            ],
            'admin' => [
                'authkey' => $this->config->get('app.secretkey'),
                'debug'   => $this->config->get('app.debug'),
                'entry'   => $this->config->get('app.entry'),
                'theme'   => $this->config->get('app.theme'),
                'version' => $this->config->get('app.version'),
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
            // 'database_hostname' => $this->request->param('database.hostname'),
            // 'database_database' => $this->request->param('database.database'),
            // 'database_username' => $this->request->param('database.username'),
            // 'database_password' => $this->request->param('database.password'),
            // 'database_hostport' => $this->request->param('database.hostport'),
            // 'database_prefix'   => $this->request->param('database.prefix'),
            'cache_expire'      => $this->request->param('cache.expire'),
            'admin_debug'       => $this->request->param('admin.debug'),
            // 'admin_entry'       => $this->request->param('admin.entry'),
        ];
        if ($result = $this->validate(__METHOD__, $receive_data)) {
            return $result;
        }

        $result = '[app]' . PHP_EOL .
            'upload_size = ' . $receive_data['app_upload_size'] . PHP_EOL .
            'upload_type = ' . $receive_data['app_upload_type'] . PHP_EOL .

            PHP_EOL . '[database]' . PHP_EOL .
            'type     = ' . $this->config->get('database.type') . PHP_EOL .
            'hostname = ' . $this->config->get('database.hostname') . PHP_EOL .
            'database = ' . $this->config->get('database.database') . PHP_EOL .
            'username = ' . $this->config->get('database.username') . PHP_EOL .
            'password = ' . $this->config->get('database.password') . PHP_EOL .
            'hostport = ' . $this->config->get('database.hostport') . PHP_EOL .
            'prefix   = ' . $this->config->get('database.prefix') . PHP_EOL .

            PHP_EOL . '[cache]' . PHP_EOL .
            'type   = ' . $this->config->get('cache.type') . PHP_EOL .
            'expire = ' . $receive_data['cache_expire'] . PHP_EOL .

            PHP_EOL . '[admin]' . PHP_EOL .
            'authkey = ' . $this->config->get('app.authkey') . PHP_EOL .
            'debug   = ' . $receive_data['admin_debug'] . PHP_EOL .
            'entry   = ' . $this->config->get('app.entry') . PHP_EOL .
            'theme   = ' . $this->config->get('app.theme') . PHP_EOL .
            'version = ' . $this->config->get('app.version');

        file_put_contents($this->app->getRootPath() . '.env', $result);

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'editor success'
        ];
    }
}
