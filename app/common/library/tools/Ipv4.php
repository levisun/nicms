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

namespace app\common\library\tools;

use think\facade\Cache;
use think\facade\Log;
use think\facade\Request;
use app\common\library\Filter;
use app\common\model\Ipv4 as ModelIpv4;
use app\common\model\Region as ModelRegion;

class Ipv4
{
    private $default = [
        'ip'          => '',
        'country'     => '',
        'province'    => '',
        'city'        => '',
        'area'        => '',
        'country_id'  => '',
        'province_id' => '',
        'city_id'     => '',
        'area_id'     => '',
        'region'      => '',
    ];

    private $api = 'http://ip.taobao.com/outGetIpInfo?ip=TXT&accessKey=alibaba-inc';

    public function get(string $_ip)
    {
        if (!Cache::has($_ip) || !$region = Cache::get($_ip)) {
            $region = $this->query($_ip);
            Cache::tag('request')->set($_ip, $region, 28800);
        }

        return $region ?: array_merge($this->default, ['ip' => $_ip]);
    }

    /**
     * 查询IP地址库
     * @access private
     * @param  string  $_ip
     * @return array|false
     */
    private function query(string &$_ip)
    {
        $result = $this->validate($_ip);
        if (true !== $result) {
            return array_merge($this->default, ['ip' => $_ip, 'country' => $result]);
        }

        $result = ModelIpv4::view('ipv4', ['id', 'update_time'])
            ->view('region country', ['id' => 'country_id', 'name' => 'country'], 'country.id=ipv4.country_id')
            ->view('region region', ['id' => 'region_id', 'name' => 'region'], 'region.id=ipv4.province_id')
            ->view('region city', ['id' => 'city_id', 'name' => 'city'], 'city.id=ipv4.city_id')
            ->view('region area', ['id' => 'area_id', 'name' => 'area'], 'area.id=ipv4.area_id', 'LEFT')
            ->where('ipv4.id', '=', bindec(Request::ip2bin($_ip)))
            ->find();
        if ($result && $result = $result->toArray()) {
            $result['ip'] = $_ip;

            // 更新信息
            if ($result['update_time'] < strtotime('-30 days')) {
                // $this->update($_ip);
            }

            unset($result['id'], $result['update_time']);
        } else {
            $result = $this->added($_ip);
        }

        return $result;
    }

    private function update(string &$_ip)
    {
        if ($id = ModelIpv4::where('id', '=', bindec(Request::ip2bin($_ip)))->value('id')) {
            $result = $this->get_curl(str_replace('TXT', $_ip, $this->api));
            $result = $result ? json_decode($result, true) : null;
            if (!is_array($result) || empty($result) || $result['code'] !== 0) {
                Log::warning('IP:' . $_ip . '抓取失败!');
                return false;
            }

            $result  = $result['data'];
            $isp     = !empty($result['isp']) ? Filter::strict($result['isp']) : '';
            $country = !empty($result['country']) ? $this->queryRegion($result['country'], 0) : '';
            if (!$country) {
                return false;
            }

            $province = $this->queryRegion($result['region'], $country);
            $city     = $this->queryRegion($result['city'], $province);
            $area     = !empty($result['area']) ? $this->queryRegion($result['area'], $city) : 0;

            ModelIpv4::where('id', '=', $id)->limit(1)->update([
                'country_id'  => $country,
                'province_id' => $province,
                'city_id'     => $city,
                'area_id'     => $area,
                'update_time' => time(),
            ]);
        }
    }

    private function added(string &$_ip)
    {
        $result = $this->default;
        if (!ModelIpv4::where('id', '=', bindec(Request::ip2bin($_ip)))->value('id')) {
            ModelIpv4::transaction(function () use (&$_ip, &$result) {
                $result = $this->get_curl(str_replace('TXT', $_ip, $this->api));
                $result = $result ? json_decode($result, true) : null;
                if (!is_array($result) || empty($result) || $result['code'] !== 0) {
                    Log::warning('IP:' . $_ip . '抓取失败!');
                    return false;
                }

                $result  = $result['data'];
                $isp     = !empty($result['isp']) ? Filter::strict($result['isp']) : '';
                $country = !empty($result['country']) ? $this->queryRegion($result['country'], 0) : '';
                if (!$country) {
                    return false;
                }

                $province = $this->queryRegion($result['region'], $country);
                $city     = $this->queryRegion($result['city'], $province);
                $area     = !empty($result['area']) ? $this->queryRegion($result['area'], $city) : 0;

                $temp = explode('.', $_ip, 4);
                unset($temp[3]);
                $temp = implode('.', $temp) . '.0';
                $temp = bindec(Request::ip2bin($temp));
                $all_data = [];
                for ($i = 0; $i <= 255; $i++) {
                    $all_data[] = [
                        'id'          => $temp + $i,
                        'country_id'  => $country,
                        'province_id' => $province,
                        'city_id'     => $city,
                        'area_id'     => $area,
                        'update_time' => time(),
                    ];
                }
                ModelIpv4::insertAll($all_data);
            });

            return $result;
        }
    }

    private function get_curl(string $_url): string
    {
        return file_get_contents($_url);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $_url);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 1);
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

    /**
     * 查询地址ID
     * @access private
     * @param  string  $_name
     * @param  int     $_pid
     * @return int
     */
    private function queryRegion(string &$_name, int $_pid): int
    {
        $_name = Filter::strict($_name);

        $result = ModelRegion::where('pid', '=', $_pid)
            ->where('name', 'LIKE', $_name . '%')
            ->value('id');

        return $result ? (int) $result : 0;
    }

    /**
     * 验证IP
     * @access private
     * @param  string  $_ip
     * @return bool
     */
    private function validate(string &$_ip)
    {
        // 判断合法IP
        if (false === Request::isValidIP($_ip, 'ipv4')) {
            return false;
        }

        $bin = bindec(Request::ip2bin($_ip));

        // a类 10.0.0.0~10.255.255.255
        if (167772160 <= $bin && 184549375 >= $bin) {
            return 'A类保留地址';
        }

        // 本地 127.0.0.0~127.255.255.255
        if (2130706432 <= $bin && 2147483647 >= $bin) {
            return '本地IP地址';
        }

        // b类 172.16.0.0~172.31.255.255
        if (2886729728 <= $bin && 2887778303 >= $bin) {
            return 'B类保留地址';
        }

        // c类 192.168.0.0~192.168.255.255
        if (3232235520 <= $bin && 3232301055 >= $bin) {
            return 'C类保留地址';
        }

        // d类 224.0.0.0~239.255.255.255
        if (3758096384 <= $bin && 4026531839 >= $bin) {
            return 'D类保留地址';
        }

        return true;
    }
}
