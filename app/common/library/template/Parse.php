<?php

declare(strict_types=1);

namespace app\common\library\template;

use Exception;

class Parse
{
    protected $config = [];
    protected $includeFile = [];

    public function __construct(array $_config)
    {
        $this->config = $_config;
    }

    /**
     * 解析模板中的脚本
     * @access protected
     * @param  string $_content 要解析的模板内容
     * @return void
     */
    protected function parseScript(string &$_content): void
    {
        $script = '';
        $pattern = '/<script( type=["\']+.*?["\']+)?>(.*?)<\/script>/si';
        $_content = (string) preg_replace_callback($pattern, function ($matches) use (&$script) {
            $matches[2] = \app\common\library\Filter::base($matches[2]);
            $script .= trim($matches[2]);
            return;
        }, $_content);
        $_content .= $script ? '<script type="text/javascript">' . $script . '</script>' : '';
    }

    /**
     * 解析模板中的变量
     * @access protected
     * @param  string $_content 要解析的模板内容
     * @return void
     */
    protected function parseVars(string &$_content): void
    {
        $_content = (string) preg_replace_callback($this->getRegex('vars'), function ($matches) {
            $matches[1] = trim($matches[1], '.');
            if (false === strpos($matches[1], '.')) {
                return '<?php echo isset($' . $matches[1] . ') ? $' . $matches[1] . ' : \'\';?>';
            }

            list($var_type, $var_name) = explode('.', $matches[1], 2);
            switch (strtolower($var_type)) {
                case 'get':
                    $var_type = '';
                case 'post':
                case 'cookie':
                    $var_type = $var_type ? $var_type . '.' : '';
                    $vars = 'input(\'' . $var_type . $var_name . '\')';
                    break;

                case 'const':
                    // 常量
                    $defined = get_defined_constants();
                    $var_name = strtoupper($var_name);
                    $vars = isset($defined[$var_name]) ? $var_name : '<!-- ' . $matches[1] . ' -->';
                    break;

                case 'session':
                case 'server':
                case 'env':
                    $vars = '';
                    break;

                default:
                    $vars = '$';
                    $vars .= $var_type ?: '';
                    $arr = explode('.', $var_name);
                    foreach ($arr as $name) {
                        $vars .= '[\'' . $name . '\']';
                    }
                    break;
            }

            return '<?php echo isset(' . $vars . ') ? ' . $vars . ' : \'\';?>';
        }, trim($_content));
    }

    protected function parseFunc(string &$_content): void
    {
        $_content = (string) preg_replace_callback($this->getRegex('function'), function ($matches) {
            $matches[2] = trim($matches[2]);
            if (!in_array($matches[2], explode(',', $this->config['tpl_deny_func_list'])) && function_exists($matches[2])) {
                $matches[3] = trim($matches[3], '()');
                echo $matches[3];
                $this->parseFunc($matches[3]);
                return $matches[2] . $matches[3];
            }
        }, trim($_content));
        echo $_content;
    }

    /**
     * 模板标签解析
     * @access protected
     * @param  string $_content 要解析的模板内容
     * @return void
     */
    protected function parseTags(string &$_content): void
    {
        $_content = (string) preg_replace_callback($this->getRegex('tags'), function ($matches) {
            $matches[1] = trim($matches[1]);
            if (in_array($matches[1], ['foreach', 'if', 'elseif', 'else'])) {
                if (isset($matches[2])) {
                    return '<?php ' . $matches[1] . '(' . $matches[2] . '): ?>';
                } else {
                    return '<?php end' . $matches[1] . '; ?>';
                }
            } elseif (class_exists('\extend\taglib\Tag' . ucfirst($matches[1]))) {
                # code...
            } else {
                return $matches[0];
            }
        }, trim($_content));
    }

    /**
     * 解析模板中的include标签
     * @access private
     * @param  string $_content 要解析的模板内容
     * @return void
     */
    protected function parseInclude(string &$_content): void
    {
        $_content = (string) preg_replace_callback($this->getRegex('include'), function ($matches) {
            # TODO 缺少变量文件地址

            $matches[1] = $this->parseTemplateFile($matches[1]);
            $str = file_get_contents($matches[1]);
            $this->parseInclude($str);
            return $str;
        }, trim($_content));
    }

    /**
     * 解析模板中的布局标签
     * @access private
     * @param  string $_content 要解析的模板内容
     * @return void
     */
    protected function parseLayout(string &$_content): void
    {
        if (preg_match($this->getRegex('layout'), $_content, $matches)) {
            $matches[1] = $this->parseTemplateFile($matches[1]);
            $str = file_get_contents($matches[1]);
            $_content = str_replace($this->config['layout_item'], $_content, $str);
        } else {
            $_content = str_replace('{__NOLAYOUT__}', '', $_content);
        }
    }

    /**
     * 分析标签属性
     * @access protected
     * @param  string   $str 属性字符串
     * @param  string   $name 不为空时返回指定的属性名
     * @return array|string
     */
    protected function parseAttr(string $str, string $name = null)
    {
        $regex = '/(?P<name>[\w-]+)=["\']+(?P<value>[\$\w\d\.\/\.\:@,\\\\]+)["\']+/is';
        $array = [];

        if (preg_match_all($regex, $str, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $array[$match['name']] = $match['value'];
            }
            unset($matches);
        }

        if (!empty($name) && isset($array[$name])) {
            return $array[$name];
        }

        return $array;
    }

    /**
     * 按标签生成正则
     * @access protected
     * @param  string $_tag_name 标签名
     * @return string
     */
    protected function getRegex(string $_tag_name): string
    {
        $regex = '';
        switch ($_tag_name) {
            case 'include':
            case 'layout':
                $regex = $_tag_name . '\s+file=["\']([\$\w\d\.\/\.\:@,\\\\]+)["\']\s+\/';
                break;

            case 'tags':
                $regex = '\/?([\w\_]+)\s?([\w\d\.\$\(\)\!=<> ]+)?\s?';
                break;

            case 'vars':
                $regex = '\$([\w\d_\.]+)';
                break;

            case 'function':
                $regex = '(:)?([\w\_]+)\s?([\w\d\.\$\(\)\!=<> ]+)?\s?';
                // $regex = ':?([\w\d]+)\(([\w\d\$\+\-]+)\)';
                break;

            default:
                # code...
                break;
        }

        return '/' . $this->config['tpl_begin'] . $regex  . $this->config['tpl_end'] . '/is';
    }

    /**
     * 自动定位模板文件
     * @access public
     * @param  string $_template 模板文件规则
     * @return string
     */
    public function parseTemplateFile(string $_template): string
    {
        if ('' == pathinfo($_template, PATHINFO_EXTENSION)) {
            // 获取视图根目录
            if (strpos($_template, '@')) {
                // 跨应用调用
                [$app, $_template] = explode('@', $_template);
            }

            $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim($this->config['view_path'], '\/.')) . DIRECTORY_SEPARATOR;

            // 应用
            $path .= isset($app) ? $app . DIRECTORY_SEPARATOR : app('http')->getName() . DIRECTORY_SEPARATOR;

            // 主题
            $path .= !empty($this->config['view_theme']) ? $this->config['view_theme'] . DIRECTORY_SEPARATOR : '';

            // 模板目录
            if (is_dir(app()->getAppPath() . $path)) {
                $path = app()->getAppPath() . $path;
            } elseif (is_dir(app()->getRootPath() . $path)) {
                $path = app()->getRootPath() . $path;
            } else {
                $path = app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . $path;
            }

            // 移动端目录
            if (request()->isMobile() && is_dir($path . 'mobile')) {
                $path .= 'mobile' . DIRECTORY_SEPARATOR;
            } elseif (false !== stripos(request()->server('HTTP_USER_AGENT'), 'MicroMessenger') && is_dir($path . 'wechat')) {
                $path .= 'wechat' . DIRECTORY_SEPARATOR;
            }

            // 如果模板文件名为空 按照默认规则定位
            if (!$_template) {
                $_template = request()->controller() . DIRECTORY_SEPARATOR . request()->action(true);
            } else {
                $depr = $this->config['view_depr'];
                $_template = str_replace(['/', ':'], $depr, trim($_template, '\/.'));
            }

            $_template = $path . ltrim($_template, '/') . '.' . ltrim($this->config['view_suffix'], '.');

            if (is_file($_template)) {
                // 记录模板文件的更新时间
                $this->includeFile[$_template] = filemtime($_template);

                return $_template;
            }
        }

        throw new Exception('template not exists:' . $_template);
    }
}
