<?php

/**
 *
 * API接口层
 * 缓存
 *
 * @package   NICMS
 * @category  app\admin\logic\content
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\admin\logic\content;

use app\common\controller\BaseLogic;
use app\common\library\template\Compiler;
use app\common\library\ClearGarbage;

class Cache extends BaseLogic
{
    protected $authKey = 'admin_auth_key';

    /**
     * 清除模板编译
     * @access public
     * @return array
     */
    public function compile(): array
    {
        $this->actionLog('compile remove');

        (new Compiler)->clear(runtime_path('compile'));

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success'
        ];
    }

    /**
     * 清除请求缓存
     * @access public
     * @return array
     */
    public function request(): array
    {
        $this->actionLog('request cache remove');

        $this->cache->tag('request')->clear();
        ClearGarbage::clearCache();

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success'
        ];
    }

    /**
     * 清除数据缓存
     * @access public
     * @return array
     */
    public function api(): array
    {
        $this->actionLog('api cache remove');

        if ($app = $this->request->param('app_name')) {
            $this->cache->tag($app)->clear();
        } else {
            $this->cache->clear();
        }
        ClearGarbage::clearCache();

        return [
            'debug' => false,
            'cache' => false,
            'msg'   => 'success'
        ];
    }
}
