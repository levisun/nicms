<?php

/**
 *
 * 解析模板
 *
 * @package   NICMS
 * @category  app\common\library\view
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\common\library\view;

use app\common\library\view\File;
use think\facade\Config;
use think\facade\Request;

class Replace
{
    private $config = [];
    private $tpl_begin = '';
    private $tpl_end = '';
    private $pattern = '';

    /**
     * 架构函数
     * @access public
     * @param  array  $_config
     * @return void
     */
    public function __construct(array &$_config)
    {
        $this->config = &$_config;
        $this->tpl_begin = $this->config['tpl_begin'];
        $this->tpl_end = $this->config['tpl_end'];

        $this->pattern = '/' . $this->tpl_begin . '[a-zA-Z]+:__REGEX__' . $this->tpl_end . '/si';
    }

    public function getContent(string &$_content)
    {
        $this->layout($_content);
        $this->include($_content);
        $this->script($_content);
        $this->func($_content);
        $this->TRepClosedTags($_content);
        $this->TRepAloneTags($_content);
        $this->TRepVars($_content);
        $this->vars($_content);
    }

    /**
     * 闭合标签
     * 格式{tags:方法 参数名=值}{tags:/标签名}
     * @access private
     * @param  string $content 要解析的模板内容
     * @return void
     */
    private function TRepClosedTags(string &$_content): void
    {
        $regex = str_replace('__REGEX__', '([a-zA-Z]+)([a-zA-Z0-9 $.=>\(\)\[\]"\'_]{0,})', $this->pattern);
        $_content = preg_replace_callback($regex, function ($matches) {
            $matches = array_map('strtolower', $matches);
            $matches = array_map('trim', $matches);
            $class = '\app\common\library\view\taglib\\Tags' . ucfirst($matches[1]);
            $params = str_replace(['"', '"', ' as', ' =>'], '', $matches[2]);
            $params = str_replace(' ', '&', $params);
            parse_str($params, $params);
            $params['expression'] = str_replace('&', ' ', $matches[2]);
            if (class_exists($class) && method_exists($class, 'closed')) {
                $object = new $class($params, $this->config);
                $str = $object->closed();
            } else {
                $str = '<!-- 无法解析:' . htmlspecialchars_decode($matches[0]) . ' -->';
            }
            return $str;
        }, $_content);

        $regex = str_replace('__REGEX__', '\/([a-zA-Z]+)', $this->pattern);
        $_content = preg_replace_callback($regex, function ($matches) {
            $matches = array_map('strtolower', $matches);
            $matches = array_map('trim', $matches);
            $class = '\app\common\library\view\taglib\\Tags' . ucfirst($matches[1]);
            if (class_exists($class) && method_exists($class, 'end')) {
                $object = new $class([], $this->config);
                $str = $object->end();
            } else {
                $str = '<!-- 无法解析:' . htmlspecialchars_decode($matches[0]) . ' -->';
            }
            return $str;
        }, $_content);
    }

    /**
     * 单标签解析
     * 格式{tags:方法 参数名=值 /}
     * @access private
     * @param  string $content 要解析的模板内容
     * @return void
     */
    private function TRepAloneTags(string &$_content): void
    {
        $regex = str_replace('__REGEX__', '([a-zA-Z]+)([a-zA-Z0-9 $.="\'_]+)\/', $this->pattern);
        $_content = preg_replace_callback($regex, function ($matches) {
            $matches = array_map('strtolower', $matches);
            $matches = array_map('trim', $matches);
            $class = '\app\common\library\view\taglib\\Tags' . ucfirst($matches[1]);
            $matches[2] = str_replace(['"', "'", ' '], ['', '', '&'], $matches[2]);
            parse_str($matches[2], $params);
            if (class_exists($class) && method_exists($class, 'alone')) {
                $object = new $class($params, $this->config);
                $str = $object->alone();
            } else {
                $str = '<!-- 无法解析:' . htmlspecialchars_decode($matches[0]) . ' -->';
            }
            return $str;
        }, $_content);
    }

    /**
     * 模板变量解析,支持使用函数 支持多维数组
     * 格式 {$类型.名称}
     * @access private
     * @param  string  $_content
     * @return void
     */
    private function vars(string &$_content): void
    {
        $regex = '/' . $this->tpl_begin . '\$([a-zA-Z0-9_.]+)' . $this->tpl_end . '/si';
        $_content = preg_replace_callback($regex, function ($matches) {
            $var_type = '';
            $var_name = $matches[1];
            if (false !== stripos($matches[1], '.')) {
                list($var_type, $var_name) = explode('.', $matches[1], 2);
                $var_type = strtolower(trim($var_type));
            }

            switch ($var_type) {
                case 'const':
                    // 常量
                    $defined = get_defined_constants();
                    $var_name = strtoupper($var_name);
                    $vars = isset($defined[$var_name]) ? $var_name : '$' . $var_name;
                    break;

                case 'get':
                case 'post':
                case 'cookie':
                    $vars = 'input(\'' . $var_type . '.' . $var_name . '\')';
                    break;

                case 'session':
                case 'server':
                case 'env':
                    $vars = '';
                    break;

                default:
                    $vars = '$';
                    $vars .= $var_type ?: '';
                    if ($var_type) {
                        $arr = explode('.', $var_name);
                        foreach ($arr as $name) {
                            $vars .= '[\'' . $name . '\']';
                        }
                    } else {
                        $vars .= $var_name;
                    }
                    break;
            }

            if (0 === stripos($vars, '$')) {
                return '<?php echo isset(' . $vars . ') ? ' . $vars . ' : \'\';?>';
            } elseif ($vars) {
                return '<?php echo ' . $vars . ';?>';
            }
        }, $_content);
    }

    /**
     * 模板Replace变量
     * @access private
     * @param  string  $_content
     * @return void
     */
    private function TRepVars(string &$_content): void
    {
        $path  = Config::get('app.cdn_host') . '/theme/';
        $path .= $this->config['app_name'] . $this->config['view_theme'] . '/';

        // 拼装移动端模板路径
        if (Request::isMobile()) {
            $mobile  = $this->config['view_path'];
            // 微信端模板
            if (is_wechat() && is_dir($mobile . 'wechat' . DIRECTORY_SEPARATOR)) {
                $path .= 'wechat' . DIRECTORY_SEPARATOR;
            }
            // 移动端模板
            elseif (is_dir($mobile . 'mobile' . DIRECTORY_SEPARATOR)) {
                $path .= 'mobile' . DIRECTORY_SEPARATOR;
            }
        }

        $path = str_replace(DIRECTORY_SEPARATOR, '/', $path);

        $replace = [
            '__SYS_VERSION__' => Config::get('app.version', '1.0.1'),
            '__STATIC__'      => Config::get('app.cdn_host') . '/static/',
            '__THEME__'       => $path,
            '__CSS__'         => $path . 'css/',
            '__IMG__'         => $path . 'img/',
            '__JS__'          => $path . 'js/',
        ];

        $replace = array_merge($this->config['tpl_replace_string'], $replace);
        $regex = '/(' . implode('|', array_keys($replace)) . ')/si';
        $_content = preg_replace_callback($regex, function ($matches) use ($replace) {
            return $replace[$matches[0]];
        }, $_content);
    }

    /**
     * 对模板中使用了函数进行解析
     * 格式 {:函数名(参数)}
     * @access private
     * @param  string  $_content
     * @return void
     */
    private function func(string &$_content): void
    {
        $pattern = '/' . $this->tpl_begin . ':([a-zA-Z0-9_]+)\((.*?)\)' . $this->tpl_end . '/si';

        $_content = preg_replace_callback($pattern, function ($matches) {
            $safe_func = [
                'str_replace', 'strlen', 'mb_strlen', 'strtoupper', 'strtolower', 'date', 'lang', 'url', 'current', 'end', 'sprintf',
            ];
            if (in_array($matches[1], $safe_func) && function_exists($matches[1])) {
                return '<?php echo ' . $matches[1] . '(' . $matches[2] . ');?>';
            } else {
                return '<!-- 无法解析:' . htmlspecialchars_decode($matches[0]) . ' -->';
            }
        }, $_content);
    }

    /**
     * 模板JS脚本解析, 脚本移至DOM底部
     * @access private
     * @param  string  $_content
     * @return void
     */
    private function script(string &$_content): void
    {
        // JS引入
        foreach ($this->config['tpl_config']['js'] as $js) {
            // 过滤多余空格
            $js = preg_replace('/( ){2,}/si', '', $js);
            // 替换引号
            $js = str_replace('\'', '"', $js);
            // 添加defer属性
            // $js = false === stripos($js, 'defer') && false === stripos($js, 'async')
            //     ? str_replace('></', ' defer="defer"></', $js)
            //     : $js;

            $_content .= $js;
        }

        $_content .= '<script src="' . Config::get('app.api_host') . '/ip.do" async></script>';
        if ('admin' !== trim($this->config['app_name'], '\/')) {
            $_content .= '<script src="' . Config::get('app.api_host') . '/record.do?token=<?php echo md5(request()->url(true));?>' . '" async></script>';
        }

        // 解析模板中的JS, 移动到HTML文档底部
        $script = '';
        $pattern = '/<script( type=["\']+.*?["\']+)?>(.*?)<\/script>/si';
        $_content = preg_replace_callback($pattern, function ($matches) use (&$script) {
            $pattern = [
                '/\/\/.*?(\r|\n)+/i',
                '/\/\*.*?\*\//i',

                '/( ){2,}/s',
                '/(\s+\n|\r)/s',
                '/(\t|\n|\r|\0|\x0B)/s',
            ];
            $matches[2] = preg_replace($pattern, '', $matches[2]);
            $script .= $matches[2];
            return;
        }, $_content);

        if ($script) {
            // $_content .= '<script type="text/javascript">window.onload=function(){' . $script . '}</script>';
            $_content .= '<script type="text/javascript">' . $script . '</script>';
        }
        $script = '';
    }

    /**
     * 引入文件
     * 格式 {include file="文件路径"}
     * @access private
     * @param  string  $_content
     * @return void
     */
    private function include(string &$_content): void
    {
        $pattern = '/' . $this->tpl_begin .
            'include file=["\']+([a-zA-Z_\.]+)["\']+' .
            $this->tpl_end . '/si';

        $_content = preg_replace_callback($pattern, function ($matches) {
            if ($matches[1] && $template = File::getTheme($this->config['view_path'], $matches[1])) {
                return file_get_contents($template);
            }

            return '<!-- 无法解析:' . $matches[1] . htmlspecialchars_decode($matches[0]) . ' -->';
        }, $_content);
    }

    /**
     * 解析布局
     * @access private
     * @param  string $_content
     * @return void
     */
    private function layout(string &$_content): void
    {
        // 判断是否启用布局
        if ($this->config['layout_on']) {
            if (false !== strpos($_content, '{__NOLAYOUT__}')) {
                // 可以单独定义不使用布局
                $_content = str_replace('{__NOLAYOUT__}', '', $_content);
            } else {
                // 读取布局模板
                $layout_file = $this->config['layout_name'] . '.' . $this->config['view_suffix'];
                if ($layout_file = File::getTheme($this->config['view_path'], $layout_file)) {
                    // 替换布局的主体内容
                    $_content = str_replace($this->config['layout_item'], $_content, file_get_contents($layout_file));
                }
            }
        } else {
            $_content = str_replace('{__NOLAYOUT__}', '', $_content);
        }
    }
}
