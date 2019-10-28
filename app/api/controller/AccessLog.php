<?php

/**
 *
 * 控制层
 * Api
 *
 * @package   NICMS
 * @category  app\api\controller
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\api\controller;

use app\common\controller\AsyncController;
use app\common\library\Ipinfo;
use app\common\model\Searchengine as ModelSearchengine;
use app\common\model\Visit as ModelVisit;

class AccessLog extends AsyncController
{
    private $searchengine = [
        'GOOGLE'         => 'googlebot',
        'GOOGLE ADSENSE' => 'mediapartners-google',
        'BAIDU'          => 'baiduspider',
        'MSN'            => 'msnbot',
        'YODAO'          => 'yodaobot',
        'YAHOO'          => 'yahoo! slurp;',
        'Yahoo China'    => 'yahoo! slurp china;',
        'IASK'           => 'iaskspider',
        'SOGOU'          => 'sogou web spider',
        'SOGOU'          => 'sogou push spider',
        'YISOU'          => 'yisouspider',
    ];

    private $userAgent = '';

    public function index()
    {
        if ($this->request->server('HTTP_REFERER')) {
            $this->userAgent = strtolower($this->request->server('HTTP_USER_AGENT'));

            if ($spider = $this->isSpider()) {
                $searchengine = new ModelSearchengine;
                $has = $searchengine
                    ->where([
                        ['name', '=', $spider],
                        ['user_agent', '=', $this->userAgent],
                        ['date', '=', strtotime(date('Y-m-d'))]
                    ])
                    ->cache(__METHOD__ . $spider . $this->userAgent)
                    ->value('name');

                if ($has) {
                    $searchengine
                        ->where([
                            ['name', '=', $spider],
                            ['user_agent', '=', $this->userAgent],
                            ['date', '=', strtotime(date('Y-m-d'))]
                        ])
                        ->inc('count', 1, 60)
                        ->update();
                } else {
                    $searchengine
                        ->create([
                            'name'       => $spider,
                            'user_agent' => $this->userAgent,
                            'date'       => strtotime(date('Y-m-d'))
                        ]);
                }
            } else {
                $ip = Ipinfo::get($this->request->ip());
                $visit = new ModelVisit;
                $has = $visit
                    ->where([
                        ['ip', '=', $ip['ip']],
                        ['user_agent', '=', $this->userAgent],
                        ['date', '=', strtotime(date('Y-m-d'))]
                    ])
                    // ->cache(__METHOD__ . $ip['ip'] . $this->userAgent)
                    ->value('ip');
                if ($has) {
                    $visit
                        ->where([
                            ['ip', '=', $ip['ip']],
                            ['user_agent', '=', $this->userAgent],
                            ['date', '=', strtotime(date('Y-m-d'))]
                        ])
                        ->inc('count', 1, 60)
                        ->update();
                } else {
                    $visit
                        ->create([
                            'ip'         => $ip['ip'],
                            'ip_attr'    => $ip['country'] .  $ip['region'] . $ip['city'] .  $ip['area'],
                            'user_agent' => $this->userAgent,
                            'date'       => strtotime(date('Y-m-d'))
                        ]);
                }
            }
        }

        if (1 === mt_rand(1, 9)) {
            (new ModelSearchengine)
                ->where([
                    ['date', '<=', strtotime('-90 days')]
                ])
                ->limit(100)
                ->delete();

            (new ModelVisit)
                ->where([
                    ['date', '<=', strtotime('-90 days')]
                ])
                ->limit(100)
                ->delete();
        }
    }

    private function isSpider()
    {
        foreach ($this->searchengine as $key => $value) {
            if (preg_match('/(' . $value . ')/si', $this->userAgent)) {
                return $key;
            }
        }
        return false;
    }
}
