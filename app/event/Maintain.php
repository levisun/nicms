<?php
/**
 *
 * 维护
 *
 * @package   NICMS
 * @category  app\middleware
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
declare (strict_types = 1);

namespace app\event;

use Closure;
use think\Response;
use think\facade\Log;
use app\library\Accesslog;
use app\library\DataMaintenance;
use app\library\ReGarbage;
use app\library\Sitemap;

class Maintain
{

    public function __construct()
    {
        Log::record('[事件]', 'alert')->save();
    }
    public function handle()
    {
        Log::record('[事件]', 'alert')->save();
    }
}
