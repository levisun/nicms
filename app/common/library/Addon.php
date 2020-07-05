<?php

/**
 *
 * 插件
 *
 * @package   NICMS
 * @category  app\common\library
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2020
 */

declare(strict_types=1);

namespace app\common\library;

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
     * 设置插件
     * @access public
     * @static
     * @return void
     */
    public static function set(string $_namespace, bool $_status = false)
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
            foreach ($addon['require'] as $namespace => $value) {
                $file = $dir . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $namespace)
                    . DIRECTORY_SEPARATOR . 'config.json';
                if (is_file($file) && $config = json_decode(file_get_contents($file), true)) {
                    $result[$namespace] = [
                        'status'  => $value['status'],
                        'type'    => $value['type'],
                        'name'    => empty($config['name']) ? '未命名'    : $config['name'],
                        'version' => empty($config['version']) ? '未知版本' : $config['version'],
                        'date'    => empty($config['date']) ? '未知发布日期' : $config['date'],
                    ];
                } else {
                    $result[$namespace] = [
                        'status'  => 'down',
                        'type'    => '未知',
                        'name'    => '未命名',
                        'version' => '未知版本',
                        'date'    => '未知发布日期',
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
        if (
            class_exists($class) && method_exists($class, 'run') &&
            is_file($file) && $config = json_decode(file_get_contents($file), true)
        ) {
            $addon = app($class, [$config, $_content]);
            $addon->run();
            $result = (string) $addon;
            $result = $result ?: $_content;
        }

        return $result;
    }
}