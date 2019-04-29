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

use think\facade\Env;
use think\facade\Lang;
use think\facade\Request;
use app\logic\admin\Base;
use app\model\Config as ModelConfig;

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
                'upload_type' => Env::get('app.upload_type'),
                'upload_size' => Env::get('app.upload_size'),
            ],
            'cache' => [
                'type'   => Env::get('cache.type'),
                'expire' => Env::get('cache.expire'),
            ],
            'database' => [
                'type'     => Env::get('database.type'),
                'hostname' => Env::get('database.hostname'),
                'database' => Env::get('database.database'),
                'username' => Env::get('database.username'),
                'password' => Env::get('database.password'),
                'hostport' => Env::get('database.hostport'),
                'prefix'   => Env::get('database.prefix'),
            ],
            'admin' => [
                'authkey' => Env::get('admin.authkey'),
                'debug'   => Env::get('admin.debug'),
                'entry'   => Env::get('admin.entry'),
                'theme'   => Env::get('admin.theme'),
                'version' => Env::get('admin.version'),
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
        if ($result = $this->authenticate(__METHOD__)) {
            return $result;
        }
        $this->writeLog(__METHOD__, 'admin safe editor');

        $result = '[app]' . PHP_EOL .
                    'upload_size = ' . Request::param('app.upload_size') . PHP_EOL .
                    'upload_type = ' . Request::param('app.upload_type') . PHP_EOL .

                    PHP_EOL . '[database]' . PHP_EOL .
                    'type     = ' . Env::get('database.type') . PHP_EOL .
                    'hostname = ' . Request::param('database.hostname') . PHP_EOL .
                    'database = ' . Request::param('database.database') . PHP_EOL .
                    'username = ' . Request::param('database.username') . PHP_EOL .
                    'password = ' . Request::param('database.password') . PHP_EOL .
                    'hostport = ' . Request::param('database.hostport') . PHP_EOL .
                    'prefix   = ' . Request::param('database.prefix') . PHP_EOL .

                    PHP_EOL . '[cache]' . PHP_EOL .
                    'type   = ' . Env::get('cache.type') . PHP_EOL .
                    'expire = ' . Request::param('cache.expire') . PHP_EOL .

                    PHP_EOL . '[admin]' . PHP_EOL .
                    'authkey = ' . Env::get('admin.authkey') . PHP_EOL .
                    'debug   = ' . Request::param('admin.debug') . PHP_EOL .
                    'entry   = ' . Request::param('admin.entry') . PHP_EOL .
                    'theme   = ' . Env::get('admin.theme') . PHP_EOL .
                    'version = ' . Env::get('admin.version');


        file_put_contents(app()->getRootPath() . '.env', $result);

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'editor success'
        ];
    }
}
