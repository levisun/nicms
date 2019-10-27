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

        $this->app->console->call('clear', ['cache']);
        $this->app->console->call('clear', ['schema']);
        $this->app->console->call('clear', ['temp']);

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'remove cache success'
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

        $this->app->console->call('clear', ['compile']);

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'remove compile success'
        ];
    }
}
