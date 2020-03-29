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
use think\exception\HttpResponseException;
use think\Response;

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

        'compile_path'       => '',
        'compile_suffix'     => 'php',                  // 默认模板编译后缀
        'compile_prefix'     => '',                     // 模板编译前缀标识，可以动态改变
        'compile_time'       => 0,                      // 模板编译有效期 0 为永久，(以数字为值，单位:秒)
        'compile_id'         => '',                     // 模板编译ID
        'tpl_compile'        => true,                   // 是否开启模板编译,设为false则每次都会重新编译

        'tpl_begin'          => '{',                    // 模板引擎普通标签开始标记
        'tpl_end'            => '}',                    // 模板引擎普通标签结束标记
        'strip_space'        => true,                   // 是否去除模板文件里面的html空格与换行

        'layout_on'          => true,                   // 布局模板开关
        'layout_name'        => 'layout',               // 布局模板入口文件
        'layout_item'        => '{__CONTENT__}',        // 布局模板的内容替换标识

        'tpl_replace_string' => [
            '__THEME__'         => 'theme/',
            '__CSS__'           => 'css/',
            '__IMG__'           => 'img/',
            '__JS__'            => 'js/',
            '__STATIC__'        => 'static/',
            '__NAME__'          => 'NICMS',
            '__TITLE__'         => 'NICMS',
            '__KEYWORDS__'      => 'NICMS',
            '__DESCRIPTION__'   => 'NICMS',
            '__BOTTOM_MSG__'    => 'NICMS',
            '__COPYRIGHT__'     => 'NICMS',
        ],

        'tpl_config' => [
            'api_version'   => '1.0.1',
            'api_appid'     => '1000002',
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
        $this->app = &$app;

        // 系统配置
        $this->config['compile_path'] = app()->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . 'compile' . DIRECTORY_SEPARATOR;
        $this->config['view_path'] = app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . 'theme' . DIRECTORY_SEPARATOR;
        $this->config['tpl_compile'] = (bool) !env('app_debug', false);

        // 合并配置
        $_config = array_filter($_config);
        $this->config = array_merge($this->config, $_config);

        // 当前应用名
        $this->config['app_name'] = $this->app->http->getName() . DIRECTORY_SEPARATOR;

        // 分应用存储
        $this->config['compile_path'] .= $this->app->http->getName() . DIRECTORY_SEPARATOR;
    }

    /**
     * 模板引擎参数赋值
     * @access public
     * @param  array $_config
     * @return void
     */
    public function config(array $_config): void
    {
        foreach ($_config as $key => $value) {
            if (is_array($value)) {
                $this->config[$key] = array_merge($this->config[$key], $value);
            } else {
                $this->config[$key] = $value;
            }
        }
        $this->config['view_theme'] = trim($this->config['view_theme'], '\/') . DIRECTORY_SEPARATOR;
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
        $_template = DataFilter::filter($_template);

        if ('' == pathinfo($_template, PATHINFO_EXTENSION)) {
            // 获取模板文件名
            $_template = $this->parseTemplateFile($_template);
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
        /* eval('?>' . $_content); */
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
        $_template = DataFilter::filter($_template);
        if ('' == pathinfo($_template, PATHINFO_EXTENSION)) {
            // 获取模板文件名
            $_template = $this->parseTemplateFile($_template);
        }

        // 主题设置
        $tpl_config = $this->config['app_name'] . $this->config['view_theme'] . 'config.json';
        if (is_file($this->config['view_path'] . $tpl_config)) {
            $json = file_get_contents($this->config['view_path'] . $tpl_config);
            if ($json && $json = json_decode($json, true)) {
                $this->config['tpl_config'] = array_merge($this->config['tpl_config'], (array) $json);
            }
        }

        // 编译路径
        $compile_file = $this->config['compile_path'] . $this->config['view_theme'] .
            md5($this->config['layout_on'] . $this->config['layout_name'] . $_template) .
            '.' . $this->config['compile_suffix'];

        if (false === $this->checkCompiler($compile_file)) {
            // 编译无效 重新模板编译
            $content = trim(file_get_contents($_template));
            $this->compiler($content, $compile_file);
        }

        // 过滤变量内容
        $_data = DataFilter::filter($_data);

        // 模板Replace变量
        $replace = $this->getReplaceVars();
        $_data = !empty($replace) ? array_merge($_data, $replace) : $_data;
        // $_data['__DEBUG__'] = $this->app->config->get('app.debug');

        extract($_data, EXTR_OVERWRITE);

        //载入模版缓存文件
        include $compile_file;
    }

    /**
     * 模板Replace变量
     * @access private
     * @return void
     */
    private function getReplaceVars(): array
    {
        $path  = $this->app->config->get('app.cdn_host') . '/theme/';
        $path .= $this->config['app_name'] . $this->config['view_theme'];

        // 拼装移动端模板路径
        if ($this->app->request->isMobile()) {
            $mobile  = $this->config['view_path'] . $this->config['app_name'] . $this->config['view_theme'];
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
            '__SYS_VERSION__' => $this->app->config->get('app.version', '1.0.1'),
            '__STATIC__'      => $this->app->config->get('app.cdn_host') . '/static/',
            '__THEME__'       => $path,
            '__CSS__'         => $path . 'css/',
            '__IMG__'         => $path . 'img/',
            '__JS__'          => $path . 'js/',
        ];

        return array_merge($this->config['tpl_replace_string'], $replace);
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
        $pattern = '/' . $this->config['tpl_begin'] . '\$([a-zA-Z0-9_.]+)' . $this->config['tpl_end'] . '/si';

        $_content = preg_replace_callback($pattern, function ($matches) {
            $var_type = '';
            $var_name = $matches[1];
            if (false !== strpos($matches[1], '.')) {
                list($var_type, $var_name) = explode('.', $matches[1], 2);
                $var_type = strtoupper(trim($var_type));
            }

            // 常量
            if ('CONST' === $var_type) {
                $defined = get_defined_constants();
                $var_name = strtoupper($var_name);
                $vars = isset($defined[$var_name]) ? $var_name : '$' . $var_name;
            }

            // GET
            elseif ('GET' === $var_type) {
                $vars = 'input(\'' . $var_name . '\')';
            }

            // POST
            elseif ('POST' === $var_type) {
                $vars = 'input(\'post.' . $var_name . '\')';
            }

            // COOKIE
            elseif ('COOKIE' === $var_type) {
                $vars = 'input(\'cookie.' . $var_name . '\')';
            }

            // SESSION
            elseif ('SESSION' === $var_type) {
                $vars = '';
            }

            // SERVER
            elseif ('SERVER' === $var_type) {
                $vars = '';
            }

            // ENV
            elseif ('ENV' === $var_type) {
                $vars = '';
            }

            //
            elseif ('' !== $var_type) {
                $vars = '$' . strtolower($var_type);
                $arr = explode('.', $var_name);
                foreach ($arr as $name) {
                    $vars .= '[\'' . $name . '\']';
                }
            } else {
                $vars = '$' . $var_name;
            }

            if (0 === stripos($vars, '$')) {
                return '<?php echo isset(' . $vars . ') ? htmlspecialchars(' . $vars . ') : \'\';?>';
            } elseif ($vars) {
                return '<?php echo htmlspecialchars(' . $vars . ');?>';
            }
        }, $_content);

        $regex = $this->getReplaceVars();
        $regex = '/(' . implode('|', array_keys($regex)) . ')/si';
        $_content = preg_replace_callback($regex, function ($matches) {
            return '<?php echo isset($' . $matches[0] . ') ? $' . $matches[0] . ' : \'\';?>';
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
                'str_replace', 'strlen', 'mb_strlen', 'strtoupper', 'strtolower', 'date', 'lang', 'url', 'current', 'end', 'sprintf',
            ];
            if (in_array($matches[1], $safe_func) && function_exists($matches[1])) {
                return '<?php echo htmlspecialchars(' . $matches[1] . '(' . $matches[2] . '));?>';
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
        // JS引入
        foreach ($this->config['tpl_config']['js'] as $js) {
            // 过滤多余空格
            $js = preg_replace('/( ){2,}/si', '', $js);
            // 替换引号
            $js = str_replace('\'', '"', $js);
            // 添加defer属性
            $js = false === stripos($js, 'defer') && false === stripos($js, 'async')
                ? str_replace('"></', '" defer="defer"></', $js)
                : $js;

            $_content .= $js;
        }

        $_content .= '<script src="' . $this->app->config->get('app.api_host') . '/ip.do" async="async" ></script>';
        if ('admin' !== trim($this->config['app_name'], '\/')) {
            $_content .= '<script src="' . $this->app->config->get('app.api_host') . '/record.do?token=<?php echo md5(request()->url(true));?>' . '" async="async" ></script>';
        }

        // 解析模板中的JS, 移动到HTML文档底部
        $pattern = '/<script( type="(.*?)")?>(.*?)<\/script>/si';
        $_content = preg_replace_callback($pattern, function ($matches) {
            $pattern = [
                '/(\/\*)(.*?)(\*\/)/i',
                '/(\/\/)(.*?)(\n|\r)+/i',
                '/( ){2,}/s',
                '/(\s+\n|\r)/s',
                '/(\t|\n|\r|\0|\x0B)/s',
            ];
            $matches[3] = preg_replace($pattern, '', $matches[3]);
            $this->script .= $matches[3];
            return;
        }, $_content);

        if ($this->script) {
            $_content .= '<script type="text/javascript">window.onload = function(){' . $this->script . '};</script>';
        }
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

        // 判断模板中是否有body头标签
        $_content = false === stripos($_content, '<body')
            ? str_replace('</head>', '</head>' . PHP_EOL . '<body>', $_content)
            : $_content;

        $_content .= false === stripos($_content, '</body>') ? '</body>' : '';
        $_content .= false === stripos($_content, '</html>') ? '</html>' : '';
    }

    /**
     * 编译模板文件内容
     * @access private
     * @param  string $_content 模板内容
     * @param  string $_compiler_file 编译文件名
     * @return void
     */
    private function compiler(string &$_content, string $_compiler_file): void
    {
        // 模板解析
        $this->parse($_content);

        // 去除html空格与换行
        if ($this->config['strip_space']) {
            /* 去除html空格与换行 */
            $pattern = [
                '~>\s+<~'               => '><',
                '~>(\s+\n|\r)~'         => '>',
                '/( ){2,}/s'            => ' ',
                '/(\s+\n|\r)/s'         => '',
                '/(\t|\n|\r|\0|\x0B)/s' => '',
            ];
            $_content = preg_replace(array_keys($pattern), array_values($pattern), $_content);
        }

        // 优化生成的php代码
        $_content = preg_replace([
            '/\?>\s*<\?php\s(?!echo\b|\bend)/s',
            '/\?>\s*<\?php/s',
            // '/<\/script>\s*<script[a-z "\/=]*>/s',
            // '/<\/script>\s*<script (type=)*>/s',
        ], '', $_content);
        $_content = str_replace('\/', '/', $_content);

        // 添加安全代码及模板引用记录
        $_content = '<?php /*' . serialize($this->includeFile) . '*/ ?>' . PHP_EOL . trim($_content);

        // 编译存储
        $dir = dirname($_compiler_file);
        is_dir($dir) or mkdir($dir, 0755, true);

        file_put_contents($_compiler_file, $_content);

        $this->includeFile = [];
    }

    /**
     * 检查编译缓存是否有效
     * 如果无效则需要重新编译
     * @access private
     * @param  string $_compiler_file 文件名
     * @return bool
     */
    private function checkCompiler($_compiler_file): bool
    {
        if (!$this->config['tpl_compile'] || !is_file($_compiler_file) || !$handle = @fopen($_compiler_file, 'r')) {
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

        if (0 !== $this->config['compile_time'] && time() > filemtime($_compiler_file) + $this->config['compile_time']) {
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
        $request = $this->app->request;

        // 拼装模板文件名
        // 为空默认类方法名作为模板文件名
        $_template = $_template
            ? $_template . '.' . $this->config['view_suffix']
            : $request->action(true) . '.' . $this->config['view_suffix'];

        // 拼接应用名与主题名
        $_template = $this->config['app_name'] . $this->config['view_theme'] . $_template;

        // 拼装移动端或微信端模板路径
        if ($request->isMobile()) {
            $mobile = $this->config['view_path'] . $this->config['app_name'] . $this->config['view_theme'];

            // 微信端模板
            if (is_wechat() && is_file($mobile . 'wechat' . DIRECTORY_SEPARATOR . $_template)) {
                $_template = $this->config['app_name'] . $this->config['view_theme'] . 'wechat' . DIRECTORY_SEPARATOR . $_template;
            }

            // 移动端模板
            elseif (is_file($mobile . 'mobile' . DIRECTORY_SEPARATOR . $_template)) {
                $_template = $this->config['app_name'] . $this->config['view_theme'] . 'mobile' . DIRECTORY_SEPARATOR . $_template;
            }
            unset($mobile);
        }

        // 模板不存在 抛出异常
        if (!is_file($this->config['view_path'] . $_template)) {
            if (app()->isDebug()) {
                $error = 'template not exists:' . $_template;
                $response = Response::create($error, 'html', 200);
            } else {
                $response = miss(403);
            }
            throw new HttpResponseException($response);
        }

        // 记录模板文件的更新时间
        $this->includeFile[$_template] = filemtime($this->config['view_path'] . $_template);

        return $this->config['view_path'] . $_template;
    }
}
