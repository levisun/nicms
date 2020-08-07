<?php

/**
 *
 * 控制层
 * 访问记录API
 *
 * @package   NICMS
 * @category  app\api\controller\tools
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\api\controller\tools;

use think\Response;
use app\common\library\api\Async;
use app\common\library\Ipinfo;
use app\common\model\Visit as ModelVisit;

class Record extends Async
{

    public function index()
    {
        if ($this->validate->referer()) {
            $user_agent = strtolower($this->request->server('HTTP_USER_AGENT'));
            $ip = Ipinfo::get($this->request->ip());
            $has = ModelVisit::where([
                ['ip', '=', $ip['ip']],
                ['user_agent', '=', md5($user_agent)],
                ['date', '=', strtotime(date('Y-m-d'))]
            ])->value('ip');
            if ($has) {
                ModelVisit::where([
                    ['ip', '=', $ip['ip']],
                    ['user_agent', '=', md5($user_agent)],
                    ['date', '=', strtotime(date('Y-m-d'))]
                ])->inc('count', 1)->update();
            } else {
                ModelVisit::create([
                    'ip'         => $ip['ip'],
                    'ip_attr'    => isset($ip['country']) ? $ip['country'] .  $ip['region'] . $ip['city'] .  $ip['area'] : '',
                    'user_agent' => md5($user_agent),
                    'date'       => strtotime(date('Y-m-d'))
                ]);
            }

            $timestamp = $this->request->time() + 3600 * 6;
            return Response::create()->allowCache(true)
                ->cacheControl('max-age=30,must-revalidate')
                ->expires(gmdate('D, d M Y H:i:s', $timestamp + 30) . ' GMT')
                ->lastModified(gmdate('D, d M Y H:i:s', $timestamp + 30) . ' GMT')
                ->contentType('application/javascript');
        }

        return miss(404, false);
    }
}
