<?php
/**
 *
 * API接口层
 * 安全设置
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
use think\facade\Env;
use think\facade\Request;
use app\logic\admin\Base;

class Safe extends Base
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

        $result = [
            'app' => [
                'upload_type' => Config::get('app.upload_type'),
                'upload_size' => Config::get('app.upload_size'),
            ],
            'cache' => [
                'type'   => Config::get('cache.type'),
                'expire' => Config::get('cache.expire'),
            ],
            'database' => [
                'type'     => Config::get('database.type'),
                'hostname' => Config::get('database.hostname'),
                'database' => Config::get('database.database'),
                'username' => Config::get('database.username'),
                'password' => Config::get('database.password'),
                'hostport' => Config::get('database.hostport'),
                'prefix'   => Config::get('database.prefix'),
            ],
            'admin' => [
                'authkey' => Config::get('app.authkey'),
                'debug'   => Config::get('app.debug'),
                'entry'   => Config::get('app.entry'),
                'theme'   => Config::get('app.theme'),
                'version' => Config::get('app.version'),
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
            'app_upload_size'   => Request::param('app.upload_size'),
            'app_upload_type'   => Request::param('app.upload_type'),
            // 'database_hostname' => Request::param('database.hostname'),
            // 'database_database' => Request::param('database.database'),
            // 'database_username' => Request::param('database.username'),
            // 'database_password' => Request::param('database.password'),
            // 'database_hostport' => Request::param('database.hostport'),
            // 'database_prefix'   => Request::param('database.prefix'),
            'cache_expire'      => Request::param('cache.expire'),
            'admin_debug'       => Request::param('admin.debug'),
            // 'admin_entry'       => Request::param('admin.entry'),
        ];
        if ($result = $this->validate(__METHOD__, $receive_data)) {
            return $result;
        }

        $result = '[app]' . PHP_EOL .
            'upload_size = ' . $receive_data['app_upload_size'] . PHP_EOL .
            'upload_type = ' . $receive_data['app_upload_type'] . PHP_EOL .

            PHP_EOL . '[database]' . PHP_EOL .
            'type     = ' . Config::get('database.type') . PHP_EOL .
            'hostname = ' . Config::get('database.hostname') . PHP_EOL .
            'database = ' . Config::get('database.database') . PHP_EOL .
            'username = ' . Config::get('database.username') . PHP_EOL .
            'password = ' . Config::get('database.password') . PHP_EOL .
            'hostport = ' . Config::get('database.hostport') . PHP_EOL .
            'prefix   = ' . Config::get('database.prefix') . PHP_EOL .

            PHP_EOL . '[cache]' . PHP_EOL .
            'type   = ' . Config::get('cache.type') . PHP_EOL .
            'expire = ' . $receive_data['cache_expire'] . PHP_EOL .

            PHP_EOL . '[admin]' . PHP_EOL .
            'authkey = ' . Config::get('app.authkey') . PHP_EOL .
            'debug   = ' . $receive_data['admin_debug'] . PHP_EOL .
            'entry   = ' . Config::get('app.entry') . PHP_EOL .
            'theme   = ' . Config::get('app.theme') . PHP_EOL .
            'version = ' . Config::get('app.version');

        file_put_contents(app()->getRootPath() . '.env', $result);

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'editor success'
        ];
    }
}
