<?php

/**
 *
 * 模板驱动
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

use think\App;
use think\contract\TemplateHandlerInterface;
use think\template\exception\TemplateNotFoundException;
//
class Template implements TemplateHandlerInterface
{
    /**
     * 应用实例
     * @var \think\App
     */
    private $app;

    /**
     * 模板配置参数
     * @var array
     */
    private $config = [
        'app_name'           => '',
        'view_path'          => '',                     // 模板路径
        'view_theme'         => '',                     // 模板主题
        'view_suffix'        => 'html',                 // 默认模板文件后缀

        'cache_path'         => '',
        'cache_suffix'       => 'php',                  // 默认模板缓存后缀
        'cache_prefix'       => '',                     // 模板缓存前缀标识，可以动态改变
        'cache_time'         => 0,                      // 模板缓存有效期 0 为永久，(以数字为值，单位:秒)
        'cache_id'           => '',                     // 模板缓存ID
        'tpl_cache'          => true,                   // 是否开启模板编译缓存,设为false则每次都会重新编译

        'tpl_begin'          => '{',                    // 模板引擎普通标签开始标记
        'tpl_end'            => '}',                    // 模板引擎普通标签结束标记
        'strip_space'        => true,                   // 是否去除模板文件里面的html空格与换行

        'layout_on'          => true,                   // 布局模板开关
        'layout_name'        => 'layout',               // 布局模板入口文件
        'layout_item'        => '{__CONTENT__}',        // 布局模板的内容替换标识

        'tpl_replace_string' => [
            '{__AUTHORIZATION__}' => '<?php echo create_authorization();?>',
            '{__TOKEN__}'         => '<?php echo token_field();?>',
            '__THEME__'           => 'theme/',
            '__CSS__'             => 'css/',
            '__IMG__'             => 'img/',
            '__JS__'              => 'js/',
            '__STATIC__'          => 'static/',
            '__NAME__'            => 'NICMS',
            '__TITLE__'           => 'NICMS',
            '__KEYWORDS__'        => 'NICMS',
            '__DESCRIPTION__'     => 'NICMS',
            '__BOTTOM_MSG__'      => 'NICMS',
            '__COPYRIGHT__'       => 'NICMS',
        ],

        'tpl_config' => [
            'api_version'   => '1.0.1',
            'api_appid'     => '1000001',
            'api_appsecret' => '962940cfbe94a64efcd1573cf6d7a175',
        ],
    ];

    /**
     * JS脚本内容
     * @var string
     */
    private $script = '';

    /**
     * 架构函数
     * @access public
     * @param  \think\App $_app
     * @param  array      $_config
     * @return void
     */
    public function __construct(App $app, array $_config = [])
    {
        $this->app    = $app;
        $this->config = array_merge($this->config, $_config);
    }

    /**
     * 模板引擎参数赋值
     * @access public
     * @param  array $_config
     * @return void
     */
    public function config(array $_config): void
    {
        $this->config = array_merge($this->config, $_config);
    }

    /**
     * 获取模板引擎配置
     * @access public
     * @param  string $_name 参数名
     * @return mixed
     */
    public function getConfig(string $_name)
    {
        return $this->config[$_name] ?? null;
    }

    /**
     * 检测是否存在模板文件
     * @access public
     * @param  string $_template 模板文件或者模板规则
     * @return bool
     */
    public function exists(string $_template): bool
    {
        if ('' == pathinfo($_template, PATHINFO_EXTENSION)) {
            // 获取模板文件名
            $_template = $this->parseTemplate($_template);
        }

        return is_file($_template);
    }

    /**
     * 渲染模板内容
     * @access public
     * @param  string $_content 模板内容
     * @param  array  $_data 模板变量
     * @return void
     */
    public function display(string $_content, array $_data = []): void
    {

        extract($_data, EXTR_OVERWRITE);
        eval('?>' . $_content);
    }

    /**
     * 渲染模板文件
     * @access public
     * @param  string $_template 模板文件
     * @param  array  $_data     模板变量
     * @return void
     */
    public function fetch(string $_template, array $_data = []): void
    {
        if ('' == pathinfo($_template, PATHINFO_EXTENSION)) {
            // 获取模板文件名
            $_template = $this->parseTemplateFile($_template);
        }

        // 主题设置
        $tpl_config = $this->config['app_name'] . $this->config['view_theme'] . 'config.json';
        if (is_file($this->config['view_path'] . $tpl_config)) {
            $json = file_get_contents($this->config['view_path'] . $tpl_config);
            if ($json && $json = json_decode($json, true)) {
                $this->config['tpl_config'] = array_merge($this->config['tpl_config'], $json);
            }
        }

        // 缓存路径
        $cache_file  = $this->config['cache_path'] .
            $this->config['view_theme'] .
            md5(
                $this->config['layout_on'] .
                $this->config['layout_name'] .
                str_replace($this->app->getRootPath() . 'public', '', $_template)
            ) . '.' .
            trim($this->config['cache_suffix'], '.');

        if (!$this->checkCache($cache_file)) {
            // 缓存无效 重新模板编译
            $content = file_get_contents($_template);
            $this->compiler($content, $cache_file);
        }

        extract($_data, EXTR_OVERWRITE);

        //载入模版缓存文件
        include $cache_file;
    }

    /**
     * 模板过滤输出
     * @access private
     * @param  string $content 要解析的模板内容
     * @return void
     */
    private function paresReplace(string &$_content): void
    {
        $theme  = $this->app->config->get('app.cdn_host') . '/theme/' .
            $this->config['app_name'] . $this->config['view_theme'];

        // 拼装移动端模板路径
        $mobile = 'mobile' . DIRECTORY_SEPARATOR;
        if ($this->app->request->isMobile() && is_dir($this->config['view_path'] . $this->config['app_name'] . $this->config['view_theme'] . $mobile)) {
            $theme .= $mobile;
        }

        $theme = str_replace(DIRECTORY_SEPARATOR, '/', $theme);

        $replace = [
            '__SYS_VERSION__' => $this->app->config->get('app.version', '1.0.1'),
            '__STATIC__'      => $this->app->config->get('app.cdn_host') . '/static/',
            '__THEME__'       => $theme,
            '__CSS__'         => $theme . 'css/',
            '__IMG__'         => $theme . 'img/',
            '__JS__'          => $theme . 'js/',
        ];

        $replace = array_merge($this->config['tpl_replace_string'], $replace);
        $_content = str_replace(array_keys($replace), array_values($replace), $_content);
    }

    /**
     * 模板变量解析,支持使用函数 支持多维数组
     * 格式： {$类型.名称}
     * @access private
     * @param  string  $_content 要解析的模板内容
     * @return void
     */
    private function parseVars(string &$_content): void
    {
        $pattern = '/' . $this->config['tpl_begin'] .
            '\$([a-zA-Z0-9_.]+)' .
            $this->config['tpl_end'] . '/si';

        $_content = preg_replace_callback($pattern, function ($matches) {
            $var_type = '';
            $var_name = $matches[1];
            if (false !== strpos($matches[1], '.')) {
                list($var_type, $var_name) = explode('.', $matches[1], 2);
                $var_type = strtoupper(trim($var_type));
            }

            if ('CONST' === $var_type) {
                $defined = get_defined_constants();
                $var_name = strtoupper($var_name);
                return isset($defined[$var_name]) ? '<?php echo ' . $var_name . '; ?>' : $var_name;
            }

            if ('GET' === $var_type) {
                $vars = 'request()->param("' . $var_name . '")';
            } elseif ('POST' === $var_type) {
                $vars = 'request()->post("' . $var_name . '")';
            } elseif ('COOKIE' === $var_type) {
                $vars = 'request()->cookie("' . $var_name . '")';
            } elseif (isset($var_type)) {
                $vars = '$' . strtolower($var_type);
                $arr = explode('.', $var_name);
                foreach ($arr as $name) {
                    $vars .= '[\'' . $name . '\']';
                }
                $vars = 'isset(' . $vars . ') ? ' . $vars . ' : \'\'';
            } else {
                $vars = '$' . $var_name;
            }

            return '<?php echo ' . $vars . ';?>';
        }, $_content);
    }

    /**
     * 模板标签解析
     * 格式： {标签名:方法 参数名=值}{/标签名}
     * @access private
     * @param  string $content 要解析的模板内容
     * @return void
     */
    private function parseTags(string &$_content): void
    {
        // 单标签解析
        $pattern = '/' . $this->config['tpl_begin'] .
            '([a-zA-Z]+):([a-zA-Z]+)([a-zA-Z0-9 $.="\'_]+)\/' .
            $this->config['tpl_end'] . '/si';

        if (false !== preg_match_all($pattern, $_content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $tags = '\taglib\\' . ucfirst($match[1]);
                $action = strtolower($match[2]);

                $match[3] = $match[3] ? trim($match[3]) : null;
                if ($match[3]) {
                    $params = str_replace(['"', "'", ' '], ['', '', '&'], $match[3]);
                    parse_str($params, $params);
                } else {
                    $params = [];
                }

                if (!class_exists($tags) || !method_exists($tags, $action)) {
                    $str = '<!-- 无法解析:' . htmlspecialchars_decode($match[0]) . ' -->';
                } else {
                    $str = call_user_func([$tags, $action], $params, $this->config);
                }

                $_content = str_replace($match[0], $str, $_content);
            }
        }

        // 闭合标签解析
        $pattern = '/' . $this->config['tpl_begin'] .
            '([a-zA-Z]+):([a-zA-Z0-9]+)([a-zA-Z0-9 $.=>"\'_]+)' .
            $this->config['tpl_end'] . '(.*?)' .
            $this->config['tpl_begin'] . '\/([a-zA-Z]+)' . $this->config['tpl_end'] . '/si';

        if (false !== preg_match_all($pattern, $_content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $tags = '\taglib\\' . ucfirst($match[1]);
                $action = strtolower($match[2]);
                $tags_content = trim($match[4]);

                $match[3] = $match[3] ? trim($match[3]) : null;
                if ($match[3]) {
                    $params = str_replace(['"', "'", '=>', ' = ', ' '], ['', '', '', '=', '&'], $match[3]);
                    parse_str($params, $params);
                    $params['expression'] = trim($match[3]);
                } else {
                    $params = [];
                }

                if (!class_exists($tags) || !method_exists($tags, $action)) {
                    $str = '<!-- 无法解析:' . htmlspecialchars_decode($match[0]) . ' -->';
                } else {
                    $str = call_user_func([$tags, $action], $params, $tags_content, $this->config);
                }

                $_content = str_replace($match[0], $str, $_content);
            }
        }
    }

    /**
     * 对模板中使用了函数进行解析
     * 格式 {:函数名(参数)}
     * @access private
     * @param  string  $_content 要解析的模板内容
     * @return void
     */
    private function parseFunc(string &$_content): void
    {
        $pattern = '/' . $this->config['tpl_begin'] .
            ':([a-zA-Z0-9_]+)\((.*?)\)' .
            $this->config['tpl_end'] . '/si';

        $_content = preg_replace_callback($pattern, function ($matches) {
            $safe_func = [
                'str_replace', 'strlen', 'mb_strlen', 'strtoupper', 'strtolower', 'date', 'lang', 'url', 'current', 'end', 'sprintf', 'token_field', 'token', 'get_img_url',
            ];
            if (in_array($matches[1], $safe_func) && function_exists($matches[1])) {
                return '<?php echo ' . $matches[1] . '(' . $matches[2] . ');?>';
            } else {
                return '<!-- 无法解析:' . htmlspecialchars_decode($matches[0]) . ' -->';
            }
        }, $_content);
    }

    /**
     * 模板JS脚本解析.脚本移至DOM底部
     * @access private
     * @param  string  $_content 要解析的模板内容
     * @return void
     */
    private function paresScript(string &$_content): void
    {
        $pattern = '/<script( type="(.*?)")?>(.*?)<\/script>/si';

        $_content = preg_replace_callback($pattern, function ($matches) {
            $type = $matches[2] ?: 'text/javascript';
            $pattern = [
                '/\/\/.*?(\n|\r)+/i',
                '/\n|\r|\f/'
            ];
            // $matches[3] = preg_replace($pattern, '', $matches[3]);
            $this->script .= '<script type="' . $type . '">' . $matches[3] . '</script>';
            return '';
        }, $_content);

        $_content .= '<!-- -->' . PHP_EOL . $this->script;
        $this->script = '';
    }

    /**
     * 引入文件
     * @access private
     * @param  string  $_content 要解析的模板内容
     * @return void
     */
    private function parseInclude(string &$_content): void
    {
        $pattern = '/' . $this->config['tpl_begin'] .
            'include file=["|\']+([a-zA-Z_]+)["|\']+' .
            $this->config['tpl_end'] . '/si';

        $_content = preg_replace_callback($pattern, function ($matches) {
            if ($matches[1] && $template = $this->parseTemplateFile($matches[1])) {
                return file_get_contents($template);
            }

            return '<!-- 无法解析:' . $matches[1] . htmlspecialchars_decode($matches[0]) . ' -->';
        }, $_content);
    }

    /**
     * 解析模板中的布局标签
     * @access private
     * @param  string  $_content 要解析的模板内容
     * @return void
     */
    private function parseLayout(string &$_content): void
    {
        // 判断是否启用布局
        if ($this->config['layout_on']) {
            if (false !== strpos($_content, '{__NOLAYOUT__}')) {
                // 可以单独定义不使用布局
                $_content = str_replace('{__NOLAYOUT__}', '', $_content);
            } else {
                // 读取布局模板
                if ($layout_file = $this->parseTemplateFile($this->config['layout_name'])) {
                    // 替换布局的主体内容
                    $_content = str_replace($this->config['layout_item'], $_content, file_get_contents($layout_file));
                }
            }
        } else {
            $_content = str_replace('{__NOLAYOUT__}', '', $_content);
        }
    }

    /**
     * 模板解析入口
     * 支持普通标签和TagLib解析 支持自定义标签库
     * @access private
     * @param  string $_content 要解析的模板内容
     * @return void
     */
    private function parse(string &$_content): void
    {
        // 解析布局
        $this->parseLayout($_content);

        // 检查include语法
        $this->parseInclude($_content);

        // 解析JS脚本
        $this->paresScript($_content);

        // 解析函数
        $this->parseFunc($_content);

        // 解析标签
        $this->parseTags($_content);

        // 解析变量
        $this->parseVars($_content);

        // 模板过滤输出
        $this->paresReplace($_content);

        if (false === stripos($_content, '<body')) {
            $_content = str_replace('</head>', '</head>' . PHP_EOL . '<body>', $_content);
        }
        $_content .= false === stripos($_content, '</body>') ? '</body>' : '';
        $_content .= false === stripos($_content, '</html>') ? '</html>' : '';
    }

    /**
     * 编译模板文件内容
     * @access private
     * @param  string $_content 模板内容
     * @param  string $_cache_file 缓存文件名
     * @return void
     */
    private function compiler(string &$_content, string $_cache_file): void
    {
        // 模板解析
        $this->parse($_content);

        // 去除html空格与换行
        if ($this->config['strip_space']) {
            /* 去除html空格与换行 */
            $find    = ['~>\s+<~', '~>(\s+\n|\r)~'];
            $replace = ['><', '>'];
            $_content = preg_replace($find, $replace, $_content);
        }

        // 优化生成的php代码
        $_content = preg_replace([
            /* '/\?>\s*<\?php\s(?!echo\b|\bend)/s', */
            '/( ){2,}/s',
            '/(\r|\n){3,}/s',
            '/\?>\s*<\?php/s',
            '/<\/script>\s*<script[a-z "\/=]*>/s'
        ], '', $_content);
        $_content = str_replace('\/', '/', $_content);

        // 添加安全代码及模板引用记录
        $_content = '<?php /*' . serialize($this->includeFile) . '*/ ?>' . PHP_EOL . $_content;

        // 编译存储
        $dir = dirname($_cache_file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($_cache_file, $_content);

        $this->includeFile = [];
    }

    /**
     * 检查编译缓存是否有效
     * 如果无效则需要重新编译
     * @access private
     * @param  string $_cache_file 文件名
     * @return bool
     */
    private function checkCache($_cache_file): bool
    {
        if (!$this->config['tpl_cache'] || !is_file($_cache_file) || !$handle = @fopen($_cache_file, 'r')) {
            return false;
        }

        // 读取第一行
        preg_match('/\/\*(.+?)\*\//', fgets($handle), $matches);
        if (!isset($matches[1])) {
            return false;
        }

        $include_file = unserialize($matches[1]);
        if (!is_array($include_file)) {
            return false;
        }

        // 检查模板文件是否有更新
        foreach ($include_file as $path => $time) {
            $path = $this->config['view_path'] . $path;
            if (is_file($path) && filemtime($path) > $time) {
                // 模板文件如果有更新则缓存需要更新
                return false;
            }
        }

        if (0 !== $this->config['cache_time'] && time() > filemtime($_cache_file) + $this->config['cache_time']) {
            // 缓存是否在有效期
            return false;
        }

        return true;
    }

    /**
     * 自动定位模板文件
     * @access private
     * @param  string $_template 模板文件规则
     * @return string
     */
    private function parseTemplateFile(string $_template = ''): string
    {
        if (empty($this->config['view_path'])) {
            $this->config['view_path'] = $this->app->getRootPath() . 'public' .
                DIRECTORY_SEPARATOR . 'theme' . DIRECTORY_SEPARATOR;
        }

        $request = $this->app->request;

        // 拼装多应用
        $this->config['app_name'] = $this->config['app_name']
            ? trim($this->config['app_name'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
            : '';

        // 拼装模板主题
        $this->config['view_theme'] = $this->config['view_theme']
            ? trim($this->config['view_theme'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
            : '';

        // 拼装模板文件名
        // 为空默认类方法名作为模板文件名
        $_template = $_template
            ? rtrim(trim($_template, '\/'), '.') . '.' . $this->config['view_suffix']
            : $request->action(true);
        $_template = $this->config['app_name'] . $this->config['view_theme'] . $_template;

        // 拼装移动端模板路径
        $mobile = 'mobile' . DIRECTORY_SEPARATOR;
        if ($request->isMobile() && is_file($this->config['view_path'] . $this->config['app_name'] . $this->config['view_theme'] . $mobile . $_template)) {
            $_template = $this->config['app_name'] . $this->config['view_theme'] . $mobile . $_template;
        }

        // 模板不存在 抛出异常
        if (!is_file($this->config['view_path'] . $_template)) {
            throw new TemplateNotFoundException('template not exists:' . $_template, $_template);
        }

        // 记录模板文件的更新时间
        $this->includeFile[$_template] = filemtime($this->config['view_path'] . $_template);

        return $this->config['view_path'] . $_template;
    }
}