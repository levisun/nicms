<?php

/**
 *
 * 插件
 *
 * @package   NICMS
 * @category  app\common\middleware
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\common\middleware;

use Closure;
use think\Request;
use app\common\library\Addon as LibAddon;

class Addon
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
        $items = LibAddon::query();
        foreach ($items as $namespace => $config) {
            if ($config['status'] === 'headend' && $config['status'] === 'open') {
                $content = LibAddon::exec($namespace, $content);
            }
        }

        $response->content($content);

        return $response;
    }
}
