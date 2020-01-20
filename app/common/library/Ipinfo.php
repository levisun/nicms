<?php

/**
 *
 * IP信息类
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

use think\facade\Cache;
use think\facade\Request;
use app\common\library\DataFilter;
use app\common\model\IpInfo as ModelIpinfo;
use app\common\model\Region as ModelRegion;

class Ipinfo
{

    /**
     * 查询IP地址信息
     * @access public
     * @param  string 请求IP地址
     * @return array
     */
    public function get(string $_ip = ''): array
    {
        $region = [
            'ip'          => $_ip,
            'country'     => '',
            'province'    => '',
            'city'        => '',
            'area'        => '',
            'country_id'  => '',
            'province_id' => '',
            'city_id'     => '',
            'area_id'     => '',
            'region'      => '',
            'isp'         => '',
        ];

        if ($_ip && $this->validate($_ip)) {
            $cache_key = md5(__METHOD__ . $_ip);
            if (!Cache::has($cache_key)) {
                // 查询IP地址库
                $region = $this->query($_ip);

                // 存在更新信息
                if (!empty($region) && $region['update_time'] <= strtotime('-30 days')) {
                    $this->update($_ip);
                } else {
                    if ($result = $this->added($_ip)) {
                        $region = $result;
                    }
                }

                unset($region['id'], $region['update_time']);
                $region['ip'] = $_ip;

                Cache::tag([
                    'SYSTEM',
                    'ipinfo'
                ])->set($cache_key, $region);
            } else {
                $region = Cache::get($cache_key);
            }
        }

        return $region;
    }

    /**
     * 验证IP
     * @access private
     * @param  string  $_ip
     * @return bool
     */
    private function validate(string $_ip): bool
    {
        // 判断合法IP
        if (false === filter_var($_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }

        // 保留IP地址段
        $_ip = explode('.', $_ip);
        $_ip = array_map(function ($value) {
            return (int) $value;
        }, $_ip);

        // a类 10.0.0.0~10.255.255.255
        if (10 == $_ip[0]) {
            return false;
        }

        // b类 172.16.0.0~172.31.255.255
        if (172 == $_ip[0] && 16 <= $_ip[0] && 31 >= $_ip[1]) {
            return false;
        }

        // c类 192.168.0.0~192.168.255.255
        if (192 == $_ip[0] && 168 == $_ip[1]) {
            return false;
        }

        // d类 224.0.0.0 - 239.255.255.255
        if (224 <= $_ip[0] && 239 >= $_ip[0]) {
            return false;
        }

        return true;
    }

    /**
     * 查询IP地址库
     * @access private
     * @param  string  $_ip
     * @return array
     */
    private function query(string &$_ip): array
    {
        $result = (new ModelIpinfo)
            ->view('ipinfo', ['id', 'ip', 'isp', 'update_time'])
            ->view('region country', ['id' => 'country_id', 'name' => 'country'], 'country.id=ipinfo.country_id')
            ->view('region region', ['id' => 'region_id', 'name' => 'region'], 'region.id=ipinfo.province_id')
            ->view('region city', ['id' => 'city_id', 'name' => 'city'], 'city.id=ipinfo.city_id')
            ->view('region area', ['id' => 'area_id', 'name' => 'area'], 'area.id=ipinfo.area_id', 'LEFT')
            ->where([
                ['ipinfo.ip', '=', bindec(Request::ip2bin($_ip))]
            ])
            ->find();
        return $result ? $result->toArray() : [];
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
        $_name = DataFilter::filter($_name);

        $result = (new ModelRegion)
            ->where([
                ['pid', '=', $_pid],
                ['name', 'LIKE', $_name . '%']
            ])
            ->value('id');

        return $result ? (int) $result : 0;
    }

    /**
     * 写入IP地址库
     * @access private
     * @return array|false
     */
    private function added(string &$_ip)
    {
        $result = $this->get_curl('http://ip.taobao.com/service/getIpInfo.php?ip=' . $_ip);

        $result = $result ? json_decode($result, true) : null;

        if (!is_array($result) || empty($result)) {
            return false;
        }

        $result = $result['data'];
        $isp     = !empty($result['isp']) ? DataFilter::filter($result['isp']) : '';
        $country = !empty($result['country']) ? $this->queryRegion($result['country'], 0) : '';
        if (!$country) {
            return false;
        }

        $province = $this->queryRegion($result['region'], $country);
        $city     = $this->queryRegion($result['city'], $province);
        $area     = !empty($result['area']) ? $this->queryRegion($result['area'], $city) : 0;

        $binip = bindec(Request::ip2bin($_ip));

        $has = (new ModelIpinfo)
            ->where([
                ['ip', '=', $binip]
            ])
            ->value('id');

        if (!$has) {
            (new ModelIpinfo)
                ->save([
                    'ip'          => $binip,
                    'country_id'  => $country,
                    'province_id' => $province,
                    'city_id'     => $city,
                    'area_id'     => $area,
                    'isp'         => $isp,
                    'update_time' => time(),
                    'create_time' => time()
                ]);
        }

        return $this->query($_ip);
    }

    /**
     * 更新IP地址库
     * @access private
     * @return bool
     */
    private function update(string &$_ip)
    {
        $result = $this->get_curl('http://ip.taobao.com/service/getIpInfo.php?ip=' . $_ip);

        $result = $result ? json_decode($result, true) : null;
        if (!is_array($result) || empty($result) || $result['code'] == 0) {
            return false;
        }

        $result = $result['data'];
        $isp     = !empty($result['isp']) ? DataFilter::filter($result['isp']) : '';
        $country = !empty($result['country']) ? $this->queryRegion($result['country'], 0) : '';
        if (!$country) {
            return false;
        }

        $province = $this->queryRegion($result['region'], $country);
        $city     = $this->queryRegion($result['city'], $province);
        $area     = !empty($result['area']) ? $this->queryRegion($result['area'], $city) : 0;

        $has = (new ModelIpinfo)
            ->where([
                ['ip', '=', bindec(Request::ip2bin($_ip))]
            ])
            ->value('id');

        if ($has) {
            (new ModelIpinfo)
                ->where([
                    ['ip', '=', bindec(Request::ip2bin($_ip))],
                ])
                ->update([
                    'country_id'  => $country,
                    'province_id' => $province,
                    'city_id'     => $city,
                    'area_id'     => $area,
                    'isp'         => $isp,
                    'update_time' => time()
                ]);
        }

        return $this->query($_ip);
    }

    private function get_curl(string $_url): string
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $_url);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);

        $headers = array('content-type: application/x-www-form-urlencoded;charset=UTF-8');
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_USERAGENT, Request::server('HTTP_USER_AGENT'));
        $result = curl_exec($curl);

        if ($result) {
            curl_close($curl);
            return $result;
        } else {
            $error = curl_errno($curl);
            curl_close($curl);
            return 'curl出错,错误码:' . $error;
        }
    }
}
