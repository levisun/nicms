<?php

/**
 *
 * 插件
 *
 * @package   NICMS
 * @category  app\common\middleware
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\common\middleware;

use Closure;
use think\Request;
use app\common\library\Addon;

class Hook
{

    /**
     *
     * @access public
     * @param  Request $request
     * @param  Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next)
    {
        # TODO

        $response = $next($request);

        $content = $response->getContent();
        $items = Addon::getOpenList();
        foreach ($items as $namespace => $config) {
            $config = array_map('strtolower', $config);

            if ($config['status'] !== 'open') {
                continue;
            }

            if (!in_array(app('http')->getName(), explode(',', $config['type']))) {
                continue;
            }

            $content = Addon::exec($namespace, $content);
        }

        $response->content($content);

        return $response;
    }
}
