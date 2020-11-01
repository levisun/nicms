<?php

/**
 *
 * 获取HTML文档内容
 *
 * @package   NICMS
 * @category  app\common\library
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2020
 */

declare(strict_types=1);

namespace app\common\library\tools;

class Html
{
    private $xml = '';

    public function __construct(string $_html = '')
    {
        $this->xml = $_html;
    }

    /**
     * 获得内容
     * @access public
     * @return string
     */
    public function content(int $_length = 0): string
    {
        $content = '';
        preg_replace_callback('/<body[^<>]*>(.*?)<\/body>/si', function ($body) use (&$content, &$_length) {
            $body = trim($body[1]);

            // 过滤Emoji
            $content = (string) preg_replace_callback('/./u', function (array $matches) {
                return strlen($matches[0]) >= 4 ? '' : $matches[0];
            }, $content);
            // 空格
            $content = (string) str_ireplace(['\u00a0', '\u0020', '\u3000', '\ufeff'], ' ', json_encode($content));
            $content = (string) json_decode($content);

            // 过滤脚本,样式,a标签和ul标签
            // 过滤空格回车等
            $pattern = [
                '/<script.*?\/script>/si' => '',
                '/<style.*?\/style>/si'   => '',
                '/<a.*?\/a>/si'           => '',
                '/<ul.*?\/ul>/si'         => '',
                '/<ol.*?\/ol>/si'         => '',
                '/>\s+</'                 => '><',
                '/>\s+/'                  => '>',
                '/\s+</'                  => '<',
                '/　/si'                  => ' ',
                '/ {2,}/si'               => ' ',


                // 清除日期和时间
                '/[\d]{2,4}[\-\/\.]+[\d]{1,2}[\-\/\.]+[\d]{1,2}/si' => '',
                '/[\d]{1,2}:[\d]{1,2}:[\d]{1,2}/si' => '',
                '/[\d]{1,2}:[\d]{1,2}/si' => '',

                '/[\d]{4}[\x{4e00}-\x{9fa5}]{1}[\d]{2}[\x{4e00}-\x{9fa5}]{1}[\d]{2}[\x{4e00}-\x{9fa5}]{1}/u' => '',

                // 清除电话
                '/[\d]{11}+/si' => '',
                '/[\d]{3}-[\d]{3,4}-[\d]{3,4}+/si' => '',
                '/[\d]{3,4}-[\d]{8}/si' => '',


                '/[a-zA-Z0-9]{20,}/si' => '',
                '/[|]+/si' => '',
            ];
            $body = (string) preg_replace(array_keys($pattern), array_values($pattern), $body);

            // 替换article标签为div
            $body = str_replace('article', 'div', $body);

            // 清除多余标签
            $body = strip_tags($body, '<div><p><br><span><img><table><tr><td><th>');

            // 替换图片
            $body = preg_replace_callback('/<img[^<>]+src=([^<>\s]+)[^<>]+>/si', function ($img) {
                return '[TAG:img_src=' . trim($img[1], '"\'') . ']';
            }, $body);

            // 过滤属性
            $body = preg_replace_callback('/(<[\w\d!]+)([\w\d\- ]+=[^>]*)/si', function ($attr) {
                return $attr[1];
            }, $body);

            // 替换表格
            $body = preg_replace_callback('/<table[^<>]*>(.*?)<\/table[^<>]*>/si', function ($table) {
                $table[1] = strip_tags($table[1], '<tr><td><th><br><p>');
                $table[1] = preg_replace_callback('/<(\/)?(tr|td|th)>/si', function ($tr) {
                    return '[TAG:' . $tr[1] . $tr[2] . ']';
                }, $table[1]);
                return '[TAG:table]' . $table[1] . '[TAG:/table]';
            }, $body);

            // 清除无用标签
            $body = preg_replace([
                '/<span>[\d]{4,}<\/span>/si',
                '/<\/?span>/si',
            ], '', $body);
            while (preg_match('/<div[^<>]*><div/si', $body)) {
                $body = preg_replace('/<div[^<>]*><div/si', '<div', $body);
            }
            while (preg_match('/<div[^<>]*><\/div>/si', $body)) {
                $body = preg_replace('/<div[^<>]*><\/div>/si', '', $body);
            }
            while (preg_match('/<\/div><\/div>/si', $body)) {
                $body = preg_replace('/<\/div><\/div>/si', '</div>', $body);
            }
            // 清除空格
            // while (preg_match('/<[\w\d!]+>\s?<\/[\w\d!]+>/si', $body)) {
            //     $body = preg_replace('/<[\w\d!]+>\s?<\/[\w\d!]+>/si', '', $body);
            // }

            // 标签转回车
            $body = str_ireplace(['<p>', '</p>', '<br>', '<br />', '<br/>'], PHP_EOL, $body);

            // 匹配内容
            $pattern = '/>[^<>]{160,}</si';
            if (false !== preg_match_all($pattern, $body, $matches)) {
                $content = $matches[0];
                foreach ($content as $key => $value) {
                    $content[$key] = trim($value, '><') . PHP_EOL;
                }
                $content = implode('', $content);

                // 截取
                if ($_length && $_length < mb_strlen($content, 'utf-8')) {
                    if ($position = mb_strpos($content, '[TAG:/table]', $_length, 'utf-8')) {
                        $content = mb_substr($content, 0, $position + 12, 'utf-8');
                    } elseif ($position = mb_strpos($content, '。', $_length, 'utf-8')) {
                        $content = mb_substr($content, 0, $position + 1, 'utf-8');
                    } elseif ($position = mb_strpos($content, '.', $_length, 'utf-8')) {
                        $content = mb_substr($content, 0, $position + 1, 'utf-8');
                    } elseif ($position = mb_strpos($content, ' ', $_length, 'utf-8')) {
                        $content = mb_substr($content, 0, $position + 1, 'utf-8');
                    } else {
                        $content = mb_substr($content, 0, $_length, 'utf-8');
                    }
                }

                // 恢复格式
                $content = explode('<br />', nl2br((string) $content));
                $content = array_map('trim', $content);
                $content = array_filter($content);

                // 跳过字符
                $jump = [
                    '版权', '@', 'copyright', 'ICP', '办理工商登记', '举报原因',
                    '可选中1个或多个下面的关键词', '大脑最佳状态搜索资料', '发布者',
                    '扫码支付', '微信支付', '举报电话', '订单号', '商户单号', '支付宝', '悬赏分',
                ];
                foreach ($content as $key => $value) {
                    foreach ($jump as $needle) {
                        if (mb_stripos($value, $needle, 0, 'utf-8')) {
                            $content[$key] = '';
                        }
                    }
                }
                $content = array_map('trim', $content);
                $content = array_filter($content);
            }

            foreach ($content as $key => $value) {
                // 恢复表格
                $value = preg_replace_callback('/\[TAG:([\w\/]+)\]/si', function ($table) {
                    return '<' . $table[1] . '>';
                }, $value);
                // 恢复图片
                $value = preg_replace_callback('/\[TAG:img_src=([^<>\s]+)\]/si', function ($img) {
                    return '<img src="' . trim($img[1], '"\'') . '" />';
                }, $value);


                if (false === mb_strpos($value, '<', 0, 'utf-8') && false === mb_strpos($value, '>', mb_strlen($value, 'utf-8') - 1, 'utf-8')) {
                    $value = '<p>' . $value . '</p>';
                }
                $content[$key] = $value;
            }

            $content = implode('', $content);
        }, $this->xml);

        return $content;
    }

    public function select(string $_element): string
    {
        $id = '';
        $_element = (string) preg_replace_callback('/#[\w\d\- ]+/si', function ($matches) use (&$id) {
            $matches[0] = trim($matches[0]);
            $id = 'id=["\']?[^<>]*' . ltrim($matches[0], '#') . '[^<>]*[\s"\']?';
            return;
        }, $_element);

        $class = '';
        $_element = (string) preg_replace_callback('/\.[\w\d\- ]+/si', function ($matches) use (&$class) {
            $matches[0] = trim($matches[0]);
            $class = 'class=["\']?[^<>]*' . ltrim($matches[0], '.') . '[^<>]*[\s"\']?';
            return;
        }, $_element);

        $sub_ele = '(.*?)';
        $_element = (string) preg_replace_callback('/>[\w\d\- ]+/si', function ($matches) use (&$sub_ele) {
            $matches[0] = trim($matches[0]);
            $matches[0] = ltrim($matches[0], '>');
            $sub_ele = '\s?<' . $matches[0] . '[^<>]*>(.*?)<\/' . $matches[0] . '>.*?';
            return;
        }, $_element);

        $content = '';
        $_element = $_element ?: '[^<>]+';
        $pattern = '/<(' . $_element . ')[^<>]*' . $id . $class . '[^<>]*>/si';
        preg_replace_callback($pattern, function ($matches) use ($sub_ele, &$content) {
            // 匹配地址
            $pattern = '/' . $matches[0] . $sub_ele . '<\/' . trim($matches[1]) . '>/si';
            preg_match_all($pattern, $this->xml, $matches);

            $content = !empty($matches[1][0]) ? trim($matches[1][0]) : '';
        }, $this->xml);

        return $content;
    }

    /**
     * 获得图片
     * 无法获得懒加载图片
     * @access public
     * @return array
     */
    public function imgs(): array
    {
        $imgs = [];
        preg_replace_callback('/<body[^<>]*>(.*?)<\/body>/si', function ($body) use (&$imgs) {
            $body = trim($body[1]);

            // 匹配地址
            $pattern = '/<img[^<>]*src=([^<>\s]+)[^<>]*>/si';
            if (false !== preg_match_all($pattern, $body, $matches)) {
                foreach ($matches[0] as $key => $value) {
                    // 宽
                    if (false !== preg_match('/width=([^<>%\s;]+)/si', $value, $width)) {
                        $width = (int) trim($width[1], '"\'');
                    }

                    // 高
                    if (false !== preg_match('/height=([^<>%\s;]+)/si', $value, $height)) {
                        $height = (int) trim($height[1], '"\'');
                    }

                    $imgs[] = [
                        'src'    => trim($matches[1][$key], '"\''),
                        'width'  => isset($width) ? $width : 0,
                        'height' => !empty($height) ? $height : 0,
                    ];
                }
            }
        }, $this->xml);

        return $imgs;
    }

    /**
     * 获得links
     * @access public
     * @return array
     */
    public function links(): array
    {
        $links = [];
        preg_replace_callback('/<body[^<>]*>.*?<\/body>/si', function ($body) use (&$links) {
            $body = trim($body[0]);

            // 匹配地址
            $pattern = '/<a[^<>]*href=([^<>\s]+)/si';
            if (false !== preg_match_all($pattern, $body, $matches)) {
                $matches = array_map('array_unique', $matches);

                foreach ($matches[1] as $value) {
                    $links[] = trim($value, '"\'');
                }
            }
        }, $this->xml);

        return $links;
    }

    /**
     * 获得description
     * @access public
     * @return string
     */
    public function description(): string
    {
        $description = '';
        preg_replace_callback('/<head[^<>]*>.*?<\/head>/si', function ($head) use (&$description) {
            $head = trim($head[0]);

            // 匹配地址
            $pattern = '/<meta[^<>]*name=["\']?description["\']?[^<>]*content=([^<>\s]+)/si';
            if (false !== preg_match($pattern, $head, $matches) && !empty($matches)) {
                $description = !empty($matches[1]) ? trim($matches[1], '"\'') : '';
            }
        }, $this->xml);

        $description = trim($description, '\/.');
        return $description;
    }

    /**
     * 获得keywords
     * @access public
     * @return string
     */
    public function keywords(): string
    {
        $keywords = '';
        preg_replace_callback('/<head[^<>]*>.*?<\/head>/si', function ($head) use (&$keywords) {
            $head = trim($head[0]);

            // 匹配地址
            $pattern = '/<meta[^<>]*name=["\']?keywords["\']?[^<>]*content=([^<>\s]+)/si';
            if (false !== preg_match($pattern, $head, $matches) && !empty($matches)) {
                $keywords = !empty($matches[1]) ? trim($matches[1], '"\'') : '';
            }
        }, $this->xml);

        $keywords = trim($keywords, '\/.');
        return $keywords;
    }

    /**
     * 获得title
     * @access public
     * @return string
     */
    public function title(): string
    {
        $title = '';
        preg_replace_callback('/<head[^<>]*>.*?<\/head>/si', function ($head) use (&$title) {
            $head = trim($head[0]);

            // 匹配地址
            $pattern = '/<title>[^<>]+<\/title>/si';
            if (false !== preg_match($pattern, $head, $matches) && !empty($matches)) {
                $title = strip_tags($matches[0]);
                $title = str_replace(['_', '|'], '-', $title);
                list($title) = explode('-', $title, 2);
            }
        }, $this->xml);

        $title = trim($title, '\/.');
        return $title;
    }
}
