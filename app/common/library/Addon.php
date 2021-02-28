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
     * @return array
     */
    public function added()
    {
        # code...
    }

    /**
     * 删除插件
     * @access public
     * @return array
     */
    public function remove(string &$_namespace)
    {
        # code...
    }

    /**
     * 开启关闭插件
     * @access public
     * @return void
     */
    public function switch(string &$_namespace, bool $_status = false): void
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
     * @return array
     */
    public function update()
    {
        # code...
    }

    /**
     * 运行
     * @access public
     * @return string
     */
    public function run(string &$_namespace, string &$_content, array &$_settings): string
    {
        $_namespace = '\\' . trim($_namespace, '\/.') . '\Index';

        if (!class_exists($_namespace) || !method_exists($_namespace, 'run')) {
            trace('[addon] ' . $_namespace . '插件不存在或约定方法错误', 'error');
            return $_content;
        }

        if (!$result = (string) (new $_namespace)->run($_settings)) {
            return $_content;
        }

        // 安全过滤
        $result = Filter::symbol($result);
        $result = Filter::space($result);
        // $result = Filter::html($result);
        $result = Filter::htmlAttr($result);
        $result = Filter::php($result);

        $_content = false !== strripos($_content, '</body>')
            ? str_replace('</body>', $result . '</body>', $_content)
            : $_content . $result;

        return $_content;
    }

    /**
     * 插件信息
     * @access public
     * @param  string $_namespace
     * @return array
     */
    public function find(string $_namespace): array
    {
        $_namespace = trim($_namespace, '\/.');
        $filename = root_path('extend/' . $_namespace) . 'config.json';
        if (!is_file($filename)) {
            return [];
        }

        $config = file_get_contents($filename);
        if (!$config = json_decode($config, true)) {
            return [];
        }

        $config['settings'] = isset($config['settings']) ? $config['settings'] : [];

        return $config;
    }

    /**
     * 获得开启的插件
     * @access public
     * @return array
     */
    public function getOpenList(): array
    {
        $cache_key = 'extend addon list';
        if (!Cache::has($cache_key) || !$result = Cache::get($cache_key)) {
            $result = $this->query();
            foreach ($result as $key => $value) {
                if ($value['settings']['status'] === 'close') {
                    unset($result[$key]);
                    continue;
                }
            }
            Cache::tag('request')->set($cache_key, $result);
        }

        return $result;
    }

    /**
     * 查询所有插件
     * @access public
     * @return array
     */
    public function query(): array
    {
        if (!$addon = glob(root_path('extend/addon') . '*')) {
            return [];
        }

        $result = [];
        foreach ($addon as $path) {
            $namespace = str_replace(root_path('extend'), '', $path);
            $namespace = trim($namespace, '\/.');
            $namespace = str_replace(['/', '\\'], '\\', $namespace);

            $result[$namespace] = [
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

            $config['settings']['status'] = isset($config['settings']['status'])
                ? $config['settings']['status']
                : 'close';

            $result[$namespace] = array_merge($result[$namespace], $config);
        }

        return $result;
    }
}
