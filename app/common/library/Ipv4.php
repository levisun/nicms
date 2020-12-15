<?php

/**
 *
 * IP信息类
 *
 * @package   NICMS
 * @category  app\common\library\tools
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\common\library;

use think\facade\Cache;
use think\facade\Request;
use app\common\library\Filter;
use app\common\model\IpInfo as ModelIpInfo;
use app\common\model\Region as ModelRegion;

class Ipv4
{
    public function query(string $_ip)
    {
        $result = ModelIpInfo::view('ipinfo', ['id', 'ip', 'isp', 'update_time'])
            ->view('region country', ['id' => 'country_id', 'name' => 'country'], 'country.id=ipinfo.country_id')
            ->view('region region', ['id' => 'region_id', 'name' => 'region'], 'region.id=ipinfo.province_id')
            ->view('region city', ['id' => 'city_id', 'name' => 'city'], 'city.id=ipinfo.city_id')
            ->view('region area', ['id' => 'area_id', 'name' => 'area'], 'area.id=ipinfo.area_id', 'LEFT')
            ->where('ipinfo.ip', '=', bindec(Request::ip2bin($_ip)))
            ->find();
        if ($result && $result = $result->toArray()) {
            $result['ip'] = $_ip;

            // 更新信息
            if ($result['update_time'] < strtotime('-90 days')) {
                // $this->getIpInfo($_ip);
            }

            unset($result['id'], $result['update_time']);
        }

        return $result;
    }


    /**
     * 查询地址ID
     * @access private
     * @param  string  $_name
     * @param  int     $_pid
     * @return int
     */
    private function queryRegion(string &$_name, int $_pid): int
    {
        $_name = Filter::safe($_name);

        $result = ModelRegion::where([
            ['pid', '=', $_pid],
            ['name', 'LIKE', $_name . '%']
        ])->value('id');

        return $result ? (int) $result : 0;
    }
}
