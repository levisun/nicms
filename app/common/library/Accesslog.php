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

use think\facade\Request;
use app\common\library\DataFilter;
use app\common\library\Ipinfo;
use app\common\model\Searchengine as ModelSearchengine;
use app\common\model\Visit as ModelVisit;

class AccessLog
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

    /**
     * 记录访问
     * @access public
     * @return void
     */
    public function record(): void
    {
        $this->userAgent = strtolower(Request::server('HTTP_USER_AGENT'));
        $this->userAgent = DataFilter::filter($this->userAgent);

        if ($spider = $this->isSpider()) {
            $has = (new ModelSearchengine)->where([
                ['name', '=', $spider],
                ['user_agent', '=', $this->userAgent],
                ['date', '=', strtotime(date('Y-m-d'))]
            ])
                // ->cache(__METHOD__ . $spider . $this->userAgent)
                ->value('name');

            if ($has) {
                (new ModelSearchengine)->where([
                    ['name', '=', $spider],
                    ['user_agent', '=', $this->userAgent],
                    ['date', '=', strtotime(date('Y-m-d'))]
                ])
                    ->inc('count', 1, 60)
                    ->update();
            } else {
                (new ModelSearchengine)->save([
                    'name'       => $spider,
                    'user_agent' => $this->userAgent,
                    'date'       => strtotime(date('Y-m-d'))
                ]);
            }
        } else {
            $ip = (new Ipinfo)->get(Request::ip());
            $has = (new ModelVisit)->where([
                ['ip', '=', $ip['ip']],
                ['user_agent', '=', $this->userAgent],
                ['date', '=', strtotime(date('Y-m-d'))]
            ])
                // ->cache(__METHOD__ . $ip['ip'] . $this->userAgent)
                ->value('ip');
            if ($has) {
                (new ModelVisit)->where([
                    ['ip', '=', $ip['ip']],
                    ['user_agent', '=', $this->userAgent],
                    ['date', '=', strtotime(date('Y-m-d'))]
                ])
                    ->inc('count', 1, 60)
                    ->update();
            } else {
                (new ModelVisit)->save([
                    'ip'         => $ip['ip'],
                    'ip_attr'    => isset($ip['country']) ? $ip['country'] .  $ip['region'] . $ip['city'] .  $ip['area'] : '',
                    'user_agent' => $this->userAgent,
                    'date'       => strtotime(date('Y-m-d'))
                ]);
            }
        }

        if (1 === mt_rand(1, 9)) {
            (new ModelSearchengine)
                ->where([
                    ['date', '<=', strtotime('-30 days')]
                ])
                ->limit(100)
                ->delete();

            (new ModelVisit)
                ->where([
                    ['date', '<=', strtotime('-30 days')]
                ])
                ->limit(100)
                ->delete();
        }
    }


    /**
     * 判断搜索引擎蜘蛛
     * @access public
     * @return mixed
     */
    public function isSpider()
    {
        foreach ($this->searchengine as $key => $value) {
            if (preg_match('/(' . $value . ')/si', $this->userAgent)) {
                return $key;
            }
        }
        return false;
    }
}
