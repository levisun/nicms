<?php
/**
 *
 * 应用公共文件
 *
 * @package   NICMS
 * @category  app
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

use think\Image;
use think\facade\Config;
use think\facade\Cookie;
use think\facade\Lang;
use think\facade\Request;
use think\facade\Route;
use think\facade\Session;
use app\library\Base64;
use app\library\DataFilter;
use app\library\Jwt;

/**
 * 是否微信请求
 * @param
 * @return boolean
 */
function isWechat(): bool
{
    return strpos(Request::server('HTTP_USER_AGENT'), 'MicroMessenger') !== false ? true : false;
}

if (!function_exists('client_mac')) {
    /**
     * 客户端网卡物理MAC值
     * @return string
     */
    function client_mac()
    {
        $os = strtolower(PHP_OS);
        if (!in_array($os, ['unix', 'solaris', 'aix'])) {
            if ('linux' == $os) {
                @exec('ifconfig -a', $result);
            } else {
                @exec('ipconfig /all', $result);
                if (!$result) {
                    $ipconfig = DIRECTORY_SEPARATOR . 'system32' . DIRECTORY_SEPARATOR . 'ipconfig.exe';
                    if(is_file($_SERVER['WINDIR'] . $ipconfig)) {
                        @exec($_SERVER['WINDIR'] . $ipconfig." /all", $result);
                    } else {
                        @exec($_SERVER['WINDIR'] . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'ipconfig.exe /all', $result);
                    }
                }
            }

            foreach ($result as $value) {
                if (preg_match('/([0-9a-f]{2}[\:\-]{1}){5}[0-9a-f]{2}/i', $value, $mac)) {
                    return $mac[0];
                }
            }
        }
    }
}

if (!function_exists('get_img_url')) {
    /**
     * 拼接图片地址
     * 生成缩略图
     * @param  string      $_img   图片路径
     * @param  int|integer $_size  缩略图宽高
     * @param  string      $_water 水印文字
     * @return string
     */
    function get_img_url(string $_img, int $_size = 300, string $_water = ''): string
    {
        $root_path = app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR;
        $font_path = $root_path . 'static' . DIRECTORY_SEPARATOR . 'font' . DIRECTORY_SEPARATOR . 'simhei.ttf';

        if (false === $_img && stripos($_img, 'http')) {
            // 规定缩略图大小
            $_size = $_size >= 800 ? 800 : round($_size / 100) * 100;
            $_size = (int)$_size;

            // URL路径转换目录路径
            $img_path = trim($_img, '/');
            $img_path = str_replace('/', DIRECTORY_SEPARATOR, $img_path);
            $img_ext = '.' . pathinfo($root_path . $img_path, PATHINFO_EXTENSION);

            // 修正原始图片名
            $new_img = str_replace($img_ext, '_skl_' . $img_ext, $img_path);
            if (is_file($root_path . $img_path) && !is_file($root_path . $new_img)) {
                rename($root_path . $img_path, $root_path . $new_img);
            }
            $img_path = $new_img;
            unset($new_img);

            if (is_file($root_path . $img_path) && $_size) {
                $thumb_path = str_replace($img_ext, '', $img_path) . $_size . 'x' . $_size . $img_ext;
                if (!is_file($root_path . $thumb_path)) {

                    // 修正原始图片名带尺寸
                    $image = Image::open($root_path . $img_path);
                    $newname = str_replace($img_ext, '', $img_path) . $image->width() . 'x' . $image->height() . $img_ext;
                    if (!is_file($root_path . $newname)) {
                        $_water = $_water ? $_water : Request::rootDomain();
                        $image->text($_water, $font_path, 15, '#00000000', Image::WATER_SOUTHEAST);
                        $image->save($root_path . $newname, null, 50);
                    }
                    unset($image);

                    // 原始尺寸大于指定缩略尺寸,生成缩略图
                    $image = Image::open($root_path . $img_path);
                    if ($image->width() > $_size) {
                        $image->thumb($_size, $_size, Image::THUMB_SCALING);
                    }

                    // 添加水印
                    $_water = $_water ? $_water : Request::rootDomain();
                    $image->text($_water, $font_path, 15, '#00000000', Image::WATER_SOUTHEAST);

                    $image->save($root_path . $thumb_path, null, 40);
                    unset($image);
                }

                $_img = '/' . str_replace(DIRECTORY_SEPARATOR, '/', $thumb_path);
            } elseif (is_file($root_path . $img_path)) {
                $_img = '/' . str_replace(DIRECTORY_SEPARATOR, '/', $img_path);
            } else {
                $_img = Config::get('app.default_img');
            }
        }

        return Config::get('app.cdn_host') . $_img;
    }
}

if (!function_exists('lang')) {
    /**
     * 获取语言变量值
     * @param  string $_name 语言变量名
     * @param  array  $_vars 动态变量值
     * @param  string $_lang 语言
     * @return mixed
     */
    function lang(string $_name, array $_vars = [], string $_lang = ''): string
    {
        return Lang::get($_name, $_vars, $_lang);
    }
}

if (!function_exists('url')) {
    /**
     * Url生成
     * @param  string $_url  路由地址
     * @param  array  $_vars 变量
     * @param  string $_sub  子域名
     * @return string
     */
    function url(string $_url = '', array $_vars = [], string $_sub = 'www')
    {
        $_url = '/' . $_url;
        $_url = Route::buildUrl($_url, $_vars, true, true);

        if ($referer = Request::server('HTTP_REFERER', false)) {
            $host = parse_url($referer, PHP_URL_HOST);
            list($_sub) = explode('.', $host, 2);
        }

        $replace = [
            Request::scheme() . '://' => '//',
            'api.'                    => $_sub . '.',
        ];

        return str_replace(array_keys($replace), array_values($replace), $_url);
    }
}

if (!function_exists('emoji_encode')) {
    /**
     * Emoji原形转换为String
     * @param  string $_str
     * @return string
     */
    function emoji_encode($_str): string
    {
        return json_decode(preg_replace_callback('/(\\\u[ed][0-9a-f]{3})/si', function ($matches) {
            return '[EMOJI:' . base64_encode($matches[0]) . ']';
        }, json_encode($_str)));
    }
}

if (!function_exists('emoji_decode')) {
    /**
     * Emoji字符串转换为原形
     * @param  string $_str
     * @return string
     */
    function emoji_decode($_str)
    {
        return json_decode(preg_replace_callback('/(\[EMOJI:[A-Za-z0-9]{8}\])/', function ($matches) {
            return base64_decode(str_replace(['[EMOJI:', ']'], '', $matches[0]));
        }, json_encode($_str)));
    }
}

if (!function_exists('emoji_clear')) {
    /**
     * Emoji字符串清清理
     * @param  string $_str
     * @return string
     */
    function emoji_clear($_str): string
    {
        return preg_replace_callback('/./u', function (array $matches) {
            return strlen($matches[0]) >= 4 ? '' : $matches[0];
        }, $_str);
    }
}

if (!function_exists('content_filter')) {
    /**
     * 内容过滤
     * @param  mixed $_data
     * @return mixed
     */
    function content_filter($_data)
    {
        return DataFilter::content($_data);
    }
}

if (!function_exists('defalut_filter')) {
    /**
     * 默认过滤
     * @param  mixed $_data
     * @return mixed
     */
    function defalut_filter($_data)
    {
        return DataFilter::defalut($_data);
    }
}

if (!function_exists('create_authorization')) {
    /**
     * API授权字符串
     * @param
     * @return string
     */
    function create_authorization(): string
    {
        return (new Jwt)->getToken();
    }
}

if (!function_exists('validate')) {
    /**
     * 验证数据
     * @param string $_validate 验证器名或者验证规则数组
     * @param array  $_data 数据
     * @return bool
     */
    function validate(string $_validate, array $_data = [])
    {
        if (strpos($_validate, '.')) {
            // 支持场景
            list($_validate, $scene) = explode('.', $_validate);
        }

        $class = app()->parseClass('validate', $_validate);
        $v     = new $class;

        if (!empty($scene)) {
            $v->scene($scene);
        }

        if (false === $v->batch(false)->failException(false)->check($_data)) {
            return $v->getError();
        } else {
            return false;
        }
    }
}

if (!function_exists('cookie')) {
    /**
     * Cookie管理
     * @param  string|array  $_name   cookie名称，如果为数组表示进行cookie设置
     * @param  mixed         $_value  cookie值
     * @param  mixed         $_option 参数
     * @return mixed
     */
    function cookie($_name, $_value = '', $_option = null)
    {
        if (is_null($_value)) {
            // 删除
            Cookie::delete($_name);
        } elseif ('' === $_value) {
            // 获取
            return 0 === strpos($_name, '?') ? Cookie::has(substr($_name, 1)) : Base64::decrypt(Cookie::get($_name));
        } else {
            // 设置
            return Cookie::set($_name, Base64::encrypt($_value), $_option);
        }
    }
}

if (!function_exists('session')) {
    /**
     * Session管理
     * @param  string|array  $_name session名称，如果为数组表示进行session设置
     * @param  mixed         $_value session值
     * @return mixed
     */
    function session($_name, $_value = '')
    {
        if (is_null($_name)) {
            // 清除
            Session::clear();
        } elseif (is_null($_value)) {
            // 删除
            Session::delete($_name);
        } elseif ('' === $_value) {
            // 判断或获取
            return 0 === strpos($_name, '?') ? Session::has(substr($_name, 1)) : Base64::decrypt(Session::get($_name));
        } else {
            // 设置
            Session::set($_name, Base64::encrypt($_value));
        }
    }
}
