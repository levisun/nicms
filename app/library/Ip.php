<?php
/**
 *
 * IP信息类
 *
 * @package   NICMS
 * @category  app\library
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
declare (strict_types = 1);

namespace app\library;

use think\facade\Request;
use app\library\Filter;
use app\model\IpInfo;
use app\model\Region;

class Ip
{

    /**
     * 查询IP地址信息
     * @access public
     * @param  string 请求IP地址
     * @return array
     */
    public function info(string $_ip = null)
    {
        $_ip = $_ip ? : Request::ip();

        if ($this->validate($_ip) === true) {
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
    private function validate(string $_ip): bool
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
    private function query($_ip): array
    {
        $result =
        (new IpInfo)->view('ipinfo i', ['id', 'ip', 'isp', 'update_time'])
        ->view('region country', ['id' => 'country_id', 'name' => 'country'], 'country.id=i.country_id')
        ->view('region region', ['id' => 'region_id', 'name' => 'region'], 'region.id=i.province_id')
        ->view('region city', ['id' => 'city_id', 'name' => 'city'], 'city.id=i.city_id')
        ->view('region area', ['id' => 'area_id', 'name' => 'area'], 'area.id=i.area_id', 'LEFT')
        ->where([
            ['i.ip', '=', bindec(Request::ip2bin($_ip))]
        ])
        ->cache(__METHOD__ . $_ip)
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
    private function queryRegion($_name, $_pid): int
    {
        $_name = Filter::default($_name, true);

        $result =
        (new Region)->where([
            ['pid', '=', $_pid],
            ['name', 'LIKE', $_name . '%']
        ])
        ->cache(__METHOD__ . $_name . $_pid, 28800)
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
    private function added($_ip)
    {
        $result = $this->get_curl('http://ip.taobao.com/service/getIpInfo.php?ip=' . $_ip);
        // $result = $this->get_curl('http://www.niphp.com/ipinfo.shtml?ip=' . $_ip);

        if ($result && $result = json_decode($result, true)) {
            if (!empty($result) && $result['code'] == 0) {
                $result = $result['data'];
                $isp     = !empty($result['isp']) ? Filter::default($result['isp'], true) : '';
                $country = $this->queryRegion($result['country'], 0);
                if ($country) {
                    $province = $this->queryRegion($result['region'], $country);
                    $city     = $this->queryRegion($result['city'], $province);
                    $area     = !empty($result['area']) ? $this->queryRegion($result['area'], $city) : 0;

                    $has =
                    (new IpInfo)->where([
                        ['ip', '=', bindec(Request::ip2bin($_ip))]
                    ])
                    ->value('id');

                    if (!$has) {
                        (new IpInfo)->create([
                            'ip'          => bindec(Request::ip2bin($_ip)),
                            'country_id'  => $country,
                            'province_id' => $province,
                            'city_id'     => $city,
                            'area_id'     => $area,
                            'isp'         => $isp,
                            'update_time' => time(),
                            'create_time' => time()
                        ]);
                    }

                    $result = $this->query($_ip);
                }
            }
        } else {
            $result = false;
        }

        return $result;
    }

    /**
     * 更新IP地址库
     * @access private
     *
     * @param
     * @return void
     */
    private function update($_ip): void
    {
        $result = $this->get_curl('http://ip.taobao.com/service/getIpInfo.php?ip=' . $_ip);
        // $result = $this->get_curl('http://www.niphp.com/ipinfo.shtml?ip=' . $_ip);

        if ($result && $result = json_decode($result, true)) {
            if (!empty($result) && $result['code'] == 0) {
                $result = $result['data'];
                $isp     = !empty($result['isp']) ? Filter::default($result['isp'], true) : '';
                $country = $this->queryRegion($result['country'], 0);
                if ($country) {
                    $province = $this->queryRegion($result['region'], $country);
                    $city     = $this->queryRegion($result['city'], $province);
                    $area     = !empty($result['area']) ? $this->queryRegion($result['area'], $city) : 0;

                    $has =
                    (new IpInfo)->where([
                        ['ip', '=', bindec(Request::ip2bin($_ip))]
                    ])
                    ->value('id');

                    if ($has) {
                        (new IpInfo)->where([
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

                    $result = $this->query($_ip);
                }
            }
        }
    }

    private function get_curl($_url): string
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
