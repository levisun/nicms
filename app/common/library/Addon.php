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
    public static function switch(string $_namespace, bool $_status = false)
    {
        $file = root_path('extend/addon') . 'addon.json';
        if (is_file($file) && $addon = json_decode(file_get_contents($file), true)) {
            $_namespace = strtolower($_namespace);
            $_namespace = trim($_namespace, '\/');
            $_namespace = str_replace(['/', '\\'], '/', $_namespace);

            $addon['require'][$_namespace]['status'] = $_status ? 'open' : 'close';

            file_put_contents($file, json_encode($addon, JSON_UNESCAPED_UNICODE));
        }
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
        $result = [];
        $dir = root_path('extend/addon');
        if (is_file($dir . 'addon.json') && $addon = json_decode(file_get_contents($dir . 'addon.json'), true)) {
            foreach ($addon['require'] as $namespace => $config) {
                $addon_config = $dir . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $namespace)
                    . DIRECTORY_SEPARATOR . 'config.json';
                if (is_file($addon_config) && $addon_config = json_decode(file_get_contents($addon_config), true)) {
                    $result[$namespace] = [
                        'status'  => $config['status'],
                        'type'    => $config['type'],
                        'name'    => empty($addon_config['name']) ? '未命名' : $addon_config['name'],
                        'author'  => empty($addon_config['author']) ? '未知作者' : $addon_config['author'],
                        'version' => empty($addon_config['version']) ? '未知版本' : $addon_config['version'],
                        'date'    => empty($addon_config['date']) ? '未知发布日期' : $addon_config['date'],
                    ];
                } else {
                    $result[$namespace] = [
                        'status'  => 'down',
                        'type'    => '未知',
                        'name'    => '未命名',
                        'author'  => '未知作者',
                        'version' => '未知版本',
                        'date'    => '未知发布日期',
                    ];
                }
            }
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
        $result = [];
        $dir = root_path('extend/addon');
        if (is_file($dir . 'addon.json') && $addon = json_decode(file_get_contents($dir . 'addon.json'), true)) {
            foreach ($addon['require'] as $namespace => $config) {
                if ($config['status'] !== 'open') {
                    continue;
                }

                $addon_config = $dir . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $namespace)
                    . DIRECTORY_SEPARATOR . 'config.json';
                if (is_file($addon_config) && $addon_config = json_decode(file_get_contents($addon_config), true)) {
                    $result[$namespace] = [
                        'status'  => $config['status'],
                        'type'    => $config['type'],
                        'name'    => empty($addon_config['name']) ? '未命名' : $addon_config['name'],
                        'author'  => empty($addon_config['author']) ? '未知作者' : $addon_config['author'],
                        'version' => empty($addon_config['version']) ? '未知版本' : $addon_config['version'],
                        'date'    => empty($addon_config['date']) ? '未知发布日期' : $addon_config['date'],
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * 运行
     * @access public
     * @static
     * @return string
     */
    public static function exec(string &$_namespace, string &$_content): string
    {
        $class = '\addon\\' . str_replace(['/', '\\'], '\\', $_namespace) . '\Index';
        $file  = root_path('extend/addon') .
            str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim($_namespace, '\/')) .
            DIRECTORY_SEPARATOR . 'config.json';

        if (!class_exists($class) || !method_exists($class, 'run')) {
            trace('[addon] ' . $_namespace . '插件不存在或约定方法错误', 'error');
            return '';
        }

        if (!is_file($file) || !json_decode(file_get_contents($file), true)) {
            trace('[addon] ' . $_namespace . '配置文件不存在或格式错误', 'error');
            return '';
        }

        $addon = app($class);
        if ($result = (string) $addon->run()) {
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
