<?php
/**
 *
 * 模板驱动
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

use think\App;
use think\template\exception\TemplateNotFoundException;
use app\library\DataFilter;

class Template
{
    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * Config实例
     * @var \think\Config
     */
    protected $config;

    /**
     * Lang实例
     * @var \think\Lang
     */
    protected $lang;

    /**
     * request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * 模板路径
     * @var string
     */
    protected $view_path;
    protected $compile_path;

    /**
     * 主题
     * @var string
     */
    protected $theme;

    /**
     * 主题配置
     * @var array
     */
    protected $theme_config = [
        'layout' => false,
        'suffix' => 'html',
        'theme'  => 'default',
    ];

    /**
     * HTML
     * @var string
     */
    protected $content;

    /**
     * javascript
     * @var string
     */
    protected $script;

    protected $vars = [];

    protected $replace = [
        '__THEME__'       => 'theme/',
        '__CSS__'         => 'css/',
        '__IMG__'         => 'img/',
        '__JS__'          => 'js/',
        '__STATIC__'      => 'static/',
        '__NAME__'        => '',
        '__TITLE__'       => '',
        '__KEYWORDS__'    => '',
        '__DESCRIPTION__' => '',
        '__BOTTOM_MSG__'  => '',
        '__COPYRIGHT__'   => '',
    ];

    /**
     * 架构函数
     * @access public
     * @param  \think\App $_app
     * @return void
     */
    public function __construct(App $_app)
    {
        $this->app     = $_app;
        $this->config  = $this->app->config;
        $this->lang    = $this->app->lang;
        $this->request = $this->app->request;

        $this->view_path = $this->app->getRootPath() . 'public' . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR;
        $this->compile_path = $this->app->getRuntimePath() . 'compile' . DIRECTORY_SEPARATOR;
        if (!is_dir($this->compile_path)) {
            chmod(app()->getRuntimePath(), 0777);
            mkdir($this->compile_path, 0777, true);
        }

        $this->replace['__VERSION__'] = $this->config->get('app.version', '1.0.1');
        $this->replace['__STATIC__'] = $this->config->get('app.cdn_host') . '/static/';
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
        if (!$content = $this->compileRead($_template)) {
            $this->vars = array_merge($this->vars, $_data);

            $this->parseTemplate($_template);
            $this->parseConfig();
            $this->parseLayout();
            $this->parseInclude();
            $this->parseFunc();
            $this->parseTags();
            $this->parseVars();

            // 页面缓存
            ob_start();
            ob_implicit_flush(0);

            echo '<!DOCTYPE html>' .
                '<html lang="' . $this->lang->getLangSet() . '">' .
                '<head>' .
                '<meta charset="utf-8" />' .
                '<meta name="fragment" content="!" />' .                                // 支持蜘蛛ajax
                '<meta name="robots" content="all" />' .                                // 蜘蛛抓取
                '<meta name="revisit-after" content="1 days" />' .                      // 蜘蛛重访
                '<meta name="renderer" content="webkit" />' .                           // 强制使用webkit渲染
                '<meta name="force-rendering" content="webkit" />' .
                '<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,minimum-scale=1,user-scalable=no" />' .

                '<meta name="generator" content="nicms" />' .
                '<meta name="author" content="levisun.mail@gmail.com" />' .
                '<meta name="copyright" content="2013-' . date('Y') . ' nicms all rights reserved" />' .

                '<meta http-equiv="Window-target" content="_blank">' .
                '<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />' .

                '<meta http-equiv="Cache-Control" content="no-siteapp" />' .            // 禁止baidu转码
                '<meta http-equiv="Cache-Control" content="no-transform" />' .

                '<meta http-equiv="x-dns-prefetch-control" content="on" />' .           // DNS缓存
                '<link rel="dns-prefetch" href="' . $this->config->get('app.api_host') . '" />' .
                '<link rel="dns-prefetch" href="' . $this->config->get('app.cdn_host') . '" />' .

                '<link href="' . $this->config->get('app.cdn_host') . '/favicon.ico" rel="shortcut icon" type="image/x-icon" />';

            // 网站标题 关键词 描述
            echo '<title>' . $this->replace['__TITLE__'] . '</title>' .
                '<meta name="keywords" content="' . $this->replace['__KEYWORDS__'] . '" />' .
                '<meta name="description" content="' . $this->replace['__DESCRIPTION__'] . '" />' .
                '<meta property="og:title" content="' . $this->replace['__NAME__'] . '">' .
                '<meta property="og:type" content="website">' .
                '<meta property="og:url" content="' . $this->request->url(true) . '">' .
                '<meta property="og:image" content="">';
            echo '{:__TOKEN__}';

            // 自定义meta标签
            if (!empty($this->theme_config['meta'])) {
                foreach ($this->theme_config['meta'] as $m) {
                    echo '<meta ' . $m['type'] . ' ' . $m['content'] . ' />';
                }
            }
            // 自定义link标签
            if (!empty($this->theme_config['link'])) {
                foreach ($this->theme_config['link'] as $m) {
                    echo '<link rel="' . $m['rel'] . '" href="' . $m['href'] . '" />';
                }
            }

            // CSS引入
            if (!empty($this->theme_config['css'])) {
                foreach ($this->theme_config['css'] as $css) {
                    echo '<link rel="stylesheet" type="text/css" href="' . $css . '?v=' . $this->theme_config['theme_version'] . '" />';
                }
            }

            list($root) = explode('.', $this->request->rootDomain(), 2);

            echo '<script type="text/javascript">' .
                'var NICMS={' .
                'domain:"' . '//' . $this->request->subDomain() . '.' . $this->request->rootDomain() . '",' .
                'url:"' . $this->request->baseUrl(true) . '",' .
                'param:' . json_encode($this->request->param()) . ',' .
                'api:{' .
                'url:"' . $this->config->get('app.api_host') . '",' .
                'root:"' . $root . '",' .
                'version:"' . $this->theme_config['api_version'] . '",' .
                'appid:"' . $this->theme_config['api_appid'] . '",' .
                'appsecret:"' . $this->theme_config['api_appsecret'] . '",' .
                'authorization:"{:__AUTHORIZATION__}",' .
                'param:' . json_encode($this->request->param()) .
                '},' .
                'cdn:{' .
                'static:"' . $this->replace['__STATIC__'] . '",' .
                'theme:"' .  $this->replace['__THEME__'] . '",' .
                'css:"' .    $this->replace['__CSS__'] . '",' .
                'img:"' .    $this->replace['__IMG__'] . '",' .
                'js:"' .     $this->replace['__JS__'] . '"' .
                '}' .
                '};</script>';
            echo '</head>';
            echo false === stripos($this->content, '<body') ? '<body>' : '';

            // 解析JS
            // JS移至底部
            $this->content =
                preg_replace_callback('/<script( type="(.*?)")?>(.*?)<\/script>/si', function ($matches) {
                    $type = $matches[2] ?: 'text/javascript';
                    $matches[3] = DataFilter::string($matches[3]);
                    $pattern = [
                        '/\/\/.*?(\n|\r)+/i',
                        '/\n|\r|\f/'
                    ];
                    $matches[3] = preg_replace($pattern, '', $matches[3]);
                    $this->script .= '<script type="' . $type . '">' . $matches[3] . '</script>';
                    return '';
                }, $this->content);

            // 过滤非法信息
            echo DataFilter::string($this->content);

            // JS引入
            if (!empty($this->theme_config['js'])) {
                foreach ($this->theme_config['js'] as $js) {
                    echo '<script type="text/javascript" src="' . $js . '?v=' . $this->theme_config['theme_version'] . '"></script>';
                }
            }

            echo $this->script;
            echo '</body></html><!-- ' . $this->request->baseUrl(true) . ' -->';
            $content = ob_get_clean();
            $content = $this->parseReplace($content);
            $this->compileWrite($_template, $content);
        }

        $content = str_replace('{:__AUTHORIZATION__}', create_authorization(), $content);
        $content = str_replace('{:__TOKEN__}', token_meta(), $content);
        echo $content;
    }

    /**
     * 设置模板变量
     * @access public
     * @param  array $_vars
     * @return void
     */
    public function assign(array $_vars = [])
    {
        $this->vars = array_merge($this->vars, $_vars);
        return $this;
    }

    /**
     * 设置模板替换字符
     * @access public
     * @param  array $_replace
     * @return object
     */
    public function setReplace(array $_replace)
    {
        $rep = [];
        foreach ($_replace as $key => $value) {
            $rep[strtoupper($key)] = $value;
        }
        $this->replace = array_merge($this->replace, $rep);
        return $this;
    }

    /**
     * 设置模板主题
     * @access public
     * @param  string $_name
     * @return object
     */
    public function setTheme(string $_name)
    {
        $_name = trim($_name, '/');
        $_name = trim($_name, '\\');
        $_name = str_replace('/', DIRECTORY_SEPARATOR, $_name);
        $_name = str_replace('\\', DIRECTORY_SEPARATOR, $_name);
        $this->theme = $_name . DIRECTORY_SEPARATOR;
        return $this;
    }

    /**
     * 生成模板编译文件
     * @access private
     * @param  string $_template
     * @param  string $_content
     * @return void
     */
    private function compileWrite(string $_template, $_content): void
    {
        $_template .= $this->request->baseUrl(true);
        $_template .= $this->lang->getLangSet();
        if ($this->request->isMobile()) {
            $_template .= 'mobile';
        }

        $path = $this->compile_path . md5($this->request->controller(true) . $_template) . '.php';

        if (false === $this->config->get('app.debug')) {
            $_content = function_exists('gzcompress') ? gzcompress($_content) : $_content;
            file_put_contents($path, $_content);
        }
    }

    /**
     * 读取编译缓存
     * @access private
     * @param  string $_template
     * @return string|bool
     */
    private function compileRead(string $_template)
    {
        $_template .= $this->request->baseUrl(true);
        $_template .= $this->lang->getLangSet();
        if ($this->request->isMobile()) {
            $_template .= 'mobile';
        }

        $path = $this->compile_path . md5($this->request->controller(true) . $_template) . '.php';

        clearstatcache();
        if (true === $this->config->get('app.debug') && is_file($path)) {
            unlink($path);
        } elseif (is_file($path)) {
            $content = file_get_contents($path);
            $content = function_exists('gzcompress') ? gzuncompress($content) : $content;
            return $content;
        }

        return false;
    }

    /**
     * 解析模板替换字符
     * @access private
     * @param
     * @return string
     */
    private function parseReplace($_content): string
    {
        return str_replace(
            array_keys($this->replace),
            array_values($this->replace),
            $_content
        );
    }

    /**
     * 解析模板变量
     * @access private
     * @param
     * @return void
     */
    private function parseVars(): void
    {
        if (false !== preg_match_all('/({:\$)([a-zA-Z_.]+)(})/si', $this->content, $matches)) {
            foreach ($matches[2] as $key => $var_name) {
                if (false !== strpos($var_name, '.')) {
                    list($var_type, $var_name) = explode('.', $var_name, 2);
                    $var_type = strtoupper(trim($var_type));
                }

                switch ($var_type) {
                    case 'GET':
                        $vars = $_GET;
                        break;

                    case 'POST':
                        $vars = $_POST;
                        break;

                    case 'COOKIE':
                        $vars = $_COOKIE;
                        break;

                    case 'CONST':
                        $defined = get_defined_constants();
                        $vars = isset($defined[strtoupper($var_name)]) ? $defined[strtoupper($var_name)] : null;
                        break;

                    default:
                        $vars = $this->vars;
                        break;
                }

                $var_name = explode('.', $var_name);
                foreach ($var_name as $name) {
                    $vars = (is_array($vars) && isset($vars[$name])) ? $vars[$name] : '';
                }

                $this->content = str_replace($matches[0][$key], $vars, $this->content);
            }
        }
    }

    /**
     * 解析模板变量
     * @access private
     * @param
     * @return void
     */
    private function parseTags(): void
    {
        $this->content =
            preg_replace_callback('/{tag:([a-zA-Z0-9 =\'\"]+)}(.*?){\/tag}/si', function ($matches) {
                if (false !== strpos($matches[1], ' ')) {
                    list($action, $params) = explode(' ', $matches[1], 2);
                    $action = strtolower($action);
                    $params = str_replace(['"', "'", ' '], ['', '', '&'], $params);
                    parse_str($params, $params);
                    $params['content'] = $matches[2];
                } else {
                    $action = strtolower($matches[1]);
                    $params = [
                        'content' => $matches[2]
                    ];
                }

                if (false !== strpos($action, ':')) {
                    list($tags, $action) = explode(':', 2);
                    $tags = '\taglib\\' . ucfirst($tags);
                } else {
                    $tags = '\taglib\Core';
                }

                if (class_exists($tags) && method_exists($tags, $action)) {
                    $result = call_user_func([$tags, $action], $params);
                    $pattern = [
                        '/>(\n|\r|\f)+/'      => '>',
                        '/(\n|\r|\f)+</'      => '<',
                        '/<\!\-\-.*?\-\->/si' => '',
                        '/\/\*.*?\*\//si'     => '',
                        '/\/\/.*?(\n|\r)+/i'  => '',
                        '/\n|\r|\f/'          => '',
                        '/( ){2,}/si'         => '',
                    ];
                    $result = preg_replace(array_keys($pattern), array_values($pattern), $result);

                    if ('script' === $action) {
                        $this->script .= $result;
                    } else {
                        return $result;
                    }
                } else {
                    return '<!-- 无法解析:' . $matches[1] . htmlspecialchars_decode($matches[0]) . ' -->';
                }
            }, $this->content);
    }

    /**
     * 解析模板函数
     * @access private
     * @param
     * @return void
     */
    private function parseFunc(): void
    {
        $this->content =
            preg_replace_callback('/{:([a-zA-Z_]+)\((.*?)\)}/si', function ($matches) {
                $safe_func = [
                    'echo', 'str_replace', 'strlen', 'mb_strlen', 'strtoupper', 'date',
                    'cookie', 'lang', 'url'
                ];
                if (in_array($matches[1], $safe_func) && function_exists($matches[1])) {
                    $matches[2] = str_replace(['"', "'"], '', $matches[2]);
                    return call_user_func($matches[1], $matches[2]);
                } else {
                    return '<!-- 无法解析:' . $matches[1] . htmlspecialchars_decode($matches[0]) . ' -->';
                }
            }, $this->content);
    }

    /**
     * 引入文件
     * @access private
     * @param
     * @return void
     */
    private function parseInclude(): void
    {
        $this->content =
            preg_replace_callback('/{:include file=["|\']+([a-zA-Z_]+)["|\']+}/si', function ($matches) {
                if ($matches[1] && is_file($this->view_path . $this->theme . $matches[1] . '.html')) {
                    return file_get_contents($this->view_path . $this->theme . $matches[1] . '.html');
                } else {
                    return '<!-- 无法解析:' . $matches[1] . htmlspecialchars_decode($matches[0]) . ' -->';
                }
            }, $this->content);
    }

    /**
     * 布局模板
     * @access private
     * @param
     * @return void
     */
    private function parseLayout(): void
    {
        if (true === $this->theme_config['layout'] && false === stripos($this->content, '{:NOT_LAYOUT}')) {
            if (is_file($this->view_path . $this->theme . DIRECTORY_SEPARATOR . 'layout.html')) {
                $layout = file_get_contents($this->view_path . $this->theme . DIRECTORY_SEPARATOR . 'layout.html');
                $this->content = str_replace('{:__CONTENT__}', $this->content, $layout);
            } else {
                throw new TemplateNotFoundException('template layout not exists:' . $this->theme . DIRECTORY_SEPARATOR . 'layout.html');
            }
        } else {
            $this->content = str_replace('{:NOT_LAYOUT}', '', $this->content);
        }
    }

    /**
     * 获得模板配置
     * @access private
     * @param
     * @return void
     */
    private function parseConfig(): void
    {
        if (!is_file($this->view_path . $this->theme . 'config.json')) {
            throw new TemplateNotFoundException('template config not exists:' . $this->theme . 'config.json');
        }

        $config = file_get_contents($this->view_path . $this->theme . 'config.json');
        if (!$config = json_decode(strip_tags($config), true)) {
            throw new TemplateNotFoundException('template config error:' . $this->theme . 'config.json');
        }
        $this->theme_config = array_merge($this->theme_config, $config);
    }

    /**
     * 模板文件
     * @access private
     * @param  string $_template 模板文件规则
     * @return void
     */
    private function parseTemplate(string $_template): void
    {
        // 获取视图根目录
        if (strpos($_template, '@')) {
            // 跨模块调用
            list($model, $_template) = explode('@', $_template, 2);
        }

        $model = isset($model) ? $model : $this->request->controller(true);
        $this->view_path .= $model . DIRECTORY_SEPARATOR;
        $path = $this->view_path . $this->theme;

        $_template = str_replace(['/', ':'], DIRECTORY_SEPARATOR, $_template);
        $_template = $_template ?: $this->request->action(true);
        $_template = ltrim($_template, DIRECTORY_SEPARATOR) . '.' . $this->theme_config['suffix'];

        if ($this->request->isMobile() && is_file($path . 'mobile' . DIRECTORY_SEPARATOR . $_template)) {
            $this->view_path .= 'mobile' . DIRECTORY_SEPARATOR;
        }

        // 模板不存在 抛出异常
        if (!is_file($path . $_template)) {
            throw new TemplateNotFoundException('template not exists:' . $_template, $_template);
        }

        $this->content = file_get_contents($path . $_template);
    }
}
