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
     * @param  Request  $request
     * @param  Closure  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next)
    {
        # TODO

        $response = $next($request);

        $content = $response->getContent();

        $type = app('http')->getName() . '.' . $request->controller(true) . '.' . $request->action(true);

        $addon = new Addon;
        $items = $addon->getOpenList();
        foreach ($items as $namespace => $config) {
            if ($config['type'] !== 'all' && false === stripos($type, $config['type'])) {
                continue;
            }

            $content = $addon->run($namespace, $content, $config['settings']);
        }

        $response->content($content);

        return $response;
    }
}
