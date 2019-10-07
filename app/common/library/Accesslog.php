<?php

/**
 *
 * 访问日志
 *
 * @package   NICMS
 * @category  app\common\library
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\common\library;

use app\common\library\Ip;
use app\common\model\Searchengine as ModelSearchengine;
use app\common\model\Visit as ModelVisit;

class Accesslog
{
    private $userAgent;
    private $ip;

    /**
     * 记录访问
     * @access public
     * @param
     * @return void
     */
    public function record(): void
    {
        $this->userAgent = app('request')->server('HTTP_USER_AGENT');
        $this->ip = Ip::info(app('request')->ip());

        // 蜘蛛
        $spider = $this->isSpider();
        if (is_string($spider)) {
            $searchengine = new ModelSearchengine;
            $has = $searchengine
                ->where([
                    ['name', '=', $spider],
                    ['user_agent', '=', $this->userAgent],
                    ['date', '=', strtotime(date('Y-m-d'))]
                ])
                ->cache(__METHOD__ . sha1($spider . $this->userAgent))
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
        }

        // 访问
        if (false === $spider) {
            $visit = new ModelVisit;
            $has = $visit
                ->where([
                    ['ip', '=', $this->ip['ip']],
                    ['user_agent', '=', $this->userAgent],
                    ['date', '=', strtotime(date('Y-m-d'))]
                ])
                ->cache(__METHOD__ . sha1($this->ip['ip'] . $this->userAgent))
                ->value('ip');

            if ($has) {
                $visit
                    ->where([
                        ['ip', '=', $this->ip['ip']],
                        ['user_agent', '=', $this->userAgent],
                        ['date', '=', strtotime(date('Y-m-d'))]
                    ])
                    ->inc('count', 1, 60)
                    ->update();
            } else {
                $visit
                    ->create([
                        'ip'         => $this->ip['ip'],
                        'ip_attr'    => $this->ip['country'] .  $this->ip['region'] . $this->ip['city'] .  $this->ip['area'],
                        'user_agent' => $this->userAgent,
                        'date'       => strtotime(date('Y-m-d'))
                    ]);
            }
        }

        // 删除过期信息
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


    /**
     * 判断搜索引擎蜘蛛
     * @access public
     * @param
     * @return mixed
     */
    public function isSpider()
    {
        $searchengine = [
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
        $this->userAgent = $this->userAgent ?: app('request')->server('HTTP_USER_AGENT');

        $user_agent = strtolower($this->userAgent);
        foreach ($searchengine as $key => $value) {
            if (preg_match('/(' . $value . ')/si', $user_agent)) {
                return $key;
            }
        }
        return false;
    }
}
