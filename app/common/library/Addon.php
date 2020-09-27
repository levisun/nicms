<?php

/**
 *
 * 插件
 *
 * @package   NICMS
 * @category  app\common\library
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2020
 */

declare(strict_types=1);

namespace app\common\library;

use think\facade\Cache;
use app\common\library\Filter;

class Addon
{

    /**
     * 添加插件
     * @access public
     * @static
     * @return array
     */
    public static function added()
    {
        # code...
    }

    /**
     * 删除插件
     * @access public
     * @static
     * @return array
     */
    public static function remove()
    {
        # code...
    }

    /**
     * 开启关闭插件
     * @access public
     * @static
     * @return void
     */
    public static function switch(string &$_namespace, bool $_status = false)
    {
        $_namespace = trim($_namespace, '\/.');
        $filename = root_path('extend/' . $_namespace) . 'config.json';
        if (!is_file($filename)) {
            return;
        }

        $config = file_get_contents($filename);
        if (!$config = json_decode($config, true)) {
            return;
        }

        $config['settings'] = isset($config['settings']) ? $config['settings'] : [];
        $config['settings']['status'] = $_status ? 'open' : 'close';

        $config = json_encode($config, JSON_UNESCAPED_UNICODE);

        file_put_contents($filename, $config);
    }

    /**
     * 更新插件
     * @access public
     * @static
     * @return array
     */
    public static function update()
    {
        # code...
    }



    /**
     * 查询所有插件
     * @access public
     * @static
     * @return array
     */
    public static function query(): array
    {
        if (!$addon = glob(root_path('extend/addon') . '*')) {
            return [];
        }

        $result = [];
        foreach ($addon as $path) {
            $namespace = str_replace(root_path('extend'), '', $path);
            $namespace = trim($namespace, '\/.');

            $result[$namespace] = [
                'status'   => 'down',
                'name'     => '未命名',
                'author'   => '未知作者',
                'version'  => '未知版本',
                'date'     => '未知发布日期',
                'type'     => '未知',
                'settings' => [],
            ];

            if (!is_file($path . DIRECTORY_SEPARATOR . 'config.json')) {
                continue;
            }

            $config = file_get_contents($path . DIRECTORY_SEPARATOR . 'config.json');
            if (!$config = json_decode($config, true)) {
                continue;
            }

            $config = array_map(function ($value) {
                return is_array($value)
                    ? array_map('strtolower', $value)
                    : ($value ? strtolower($value) : '未知');
            }, $config);

            $result[$namespace] = $config;
        }

        return $result;
    }

    /**
     * 获得开启的插件
     * @access public
     * @static
     * @return array
     */
    public static function getOpenList()
    {
        $cache_key = 'extend addon list';
        if (!Cache::has($cache_key) || !$result = Cache::get($cache_key)) {
            $result = self::query();
            foreach ($result as $key => $value) {
                if (!isset($value['settings']['status']) || $value['settings']['status'] !== 'open') {
                    unset($result[$key]);
                }
            }
            Cache::tag('system')->set($cache_key, $result);
        }

        return $result;
    }

    /**
     * 运行
     * @access public
     * @static
     * @return string
     */
    public static function run(string &$_namespace, string &$_content, array &$_settings): string
    {
        $_namespace = '\\' . trim($_namespace, '\/.') . '\Index';

        if (!class_exists($_namespace) || !method_exists($_namespace, 'run')) {
            trace('[addon] ' . $_namespace . '插件不存在或约定方法错误', 'error');
            return $_content;
        }

        if ($result = (string) (new $_namespace)->run($_settings)) {
            // 安全过滤
            $result = Filter::symbol($result);
            $result = Filter::space($result);
            $result = Filter::php($result);

            $pos = strripos($_content, '</body>');
            if (false !== $pos) {
                $_content = substr($_content, 0, $pos) . $result . substr($_content, $pos);
            } else {
                $_content = $_content . $result;
            }
        }

        return $_content;
    }
}
