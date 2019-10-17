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
    public static function get(string $_ip = ''): array
    {
        $_ip = $_ip ?: app('request')->ip();

        if ($_ip && false !== filter_var($_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && true === self::validate($_ip)) {
            $cache_key = md5(__METHOD__ . $_ip);
            if (!app('cache')->has($cache_key)) {
                // 查询IP地址库
                $region = self::query($_ip);

                // 存在更新信息
                if (!empty($region) && $region['update_time'] <= strtotime('-30 days')) {
                    self::update($_ip);
                } else {
                    if ($result = self::added($_ip)) {
                        $region = $result;
                    }
                }

                unset($region['id'], $region['update_time']);
                $region['ip'] = $_ip;

                app('cache')->tag('SYSTEM')->set($cache_key, $region);
            } else {
                $region = app('cache')->get($cache_key);
            }

            return $region;
        } else {
            return [
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
        }
    }

    /**
     * 验证IP
     * @access private
     *
     * @param  string  $_ip
     * @return array
     */
    private static function validate(string $_ip): bool
    {
        $_ip = explode('.', $_ip);
        if (count($_ip) == 4) {
            foreach ($_ip as $key => $value) {
                if ($value != '') {
                    $_ip[$key] = (int) $value;
                } else {
                    return false;
                }
            }

            // 保留IP地址段
            // a类 10.0.0.0~10.255.255.255
            // b类 172.16.0.0~172.31.255.255
            // c类 192.168.0.0~192.168.255.255
            if ($_ip[0] == 0 || $_ip[0] == 10 || $_ip[0] == 255) {
                return false;
            } elseif ($_ip[0] == 172 && $_ip[1] >= 16 && $_ip[1] <= 31) {
                return false;
            } elseif ($_ip[0] == 127 && $_ip[1] == 0) {
                return false;
            } elseif ($_ip[0] == 192 && $_ip[1] == 168) {
                return false;
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

    /**
     * 查询IP地址库
     * @access private
     *
     * @param
     * @return array
     */
    private static function query($_ip): array
    {
        $result = (new ModelIpinfo)
            ->view('ipinfo', ['id', 'ip', 'isp', 'update_time'])
            ->view('region country', ['id' => 'country_id', 'name' => 'country'], 'country.id=ipinfo.country_id')
            ->view('region region', ['id' => 'region_id', 'name' => 'region'], 'region.id=ipinfo.province_id')
            ->view('region city', ['id' => 'city_id', 'name' => 'city'], 'city.id=ipinfo.city_id')
            ->view('region area', ['id' => 'area_id', 'name' => 'area'], 'area.id=ipinfo.area_id', 'LEFT')
            ->where([
                ['ipinfo.ip', '=', bindec(app('request')->ip2bin($_ip))]
            ])
            ->find();

        return $result ? $result->toArray() : [];
    }

    /**
     * 查询地址ID
     * @access private
     *
     * @param  string  $_name
     * @return int
     */
    private static function queryRegion($_name, $_pid): int
    {
        $_name = DataFilter::default($_name);

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
     *
     * @param
     * @return array|false
     */
    private static function added($_ip)
    {
        $result = self::get_curl('http://ip.taobao.com/service/getIpInfo.php?ip=' . $_ip);
        // $result = self::get_curl('http://www.niphp.com/ipinfo.shtml?ip=' . $_ip);

        $result = $result ? json_decode($result, true) : null;

        if (!is_array($result) || empty($result)) {
            return false;
        }

        $result = $result['data'];
        $isp     = !empty($result['isp']) ? DataFilter::default($result['isp']) : '';
        $country = self::queryRegion($result['country'], 0);
        if (!$country) {
            return false;
        }

        $province = self::queryRegion($result['region'], $country);
        $city     = self::queryRegion($result['city'], $province);
        $area     = !empty($result['area']) ? self::queryRegion($result['area'], $city) : 0;

        $binip = bindec(app('request')->ip2bin($_ip));

        $has = (new ModelIpinfo)
            ->where([
                ['ip', '=', $binip]
            ])
            ->value('id');

        if (!$has) {
            (new ModelIpinfo)
                ->create([
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

        return self::query($_ip);
    }

    /**
     * 更新IP地址库
     * @access private
     *
     * @param
     * @return bool
     */
    private static function update($_ip)
    {
        $result = self::get_curl('http://ip.taobao.com/service/getIpInfo.php?ip=' . $_ip);
        // $result = self::get_curl('http://www.niphp.com/ipinfo.shtml?ip=' . $_ip);
        $result = $result ? json_decode($result, true) : null;
        if (!is_array($result) || empty($result) || $result['code'] == 0) {
            return false;
        }

        $result = $result['data'];
        $isp     = !empty($result['isp']) ? DataFilter::default($result['isp']) : '';
        $country = self::queryRegion($result['country'], 0);
        if (!$country) {
            return false;
        }

        $province = self::queryRegion($result['region'], $country);
        $city     = self::queryRegion($result['city'], $province);
        $area     = !empty($result['area']) ? self::queryRegion($result['area'], $city) : 0;

        $has = (new ModelIpinfo)
            ->where([
                ['ip', '=', bindec(app('request')->ip2bin($_ip))]
            ])
            ->value('id');

        if ($has) {
            (new ModelIpinfo)
                ->where([
                    ['ip', '=', bindec(app('request')->ip2bin($_ip))],
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

        return self::query($_ip);
    }

    private static function get_curl($_url): string
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $_url);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);

        $headers = array('content-type: application/x-www-form-urlencoded;charset=UTF-8');
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
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
