<?php

/**
 *
 * 获取HTML文档内容
 *
 * @package   NICMS
 * @category  app\common\library\tools
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

    public function article(int $_length = 0)
    {
        $content = '';

        $regex = '/<body[^<>]*>(.*?)<\/body>/si';
        preg_replace_callback($regex, function ($body) use (&$content, &$_length) {
            $body = trim($body[1]);

            // 过滤有害标签及内容
            $pattern = [
                '/<script[^<>]*>.*?<\/script>/si',
                '/<style[^<>]*>.*?<\/style>/si',
                '/<ul[^<>]*>.*?<\/ul>/si',
                '/<ol[^<>]*>.*?<\/ol>/si',
                '/<a[^<>]*>/si',
                '/<\/a>/si',
            ];
            $body = preg_replace($pattern, '', $body);

            $pattern = [
                '/>\s+/' => '>',
                '/\s+</' => '<',
                '/　/si' => ' ',
                '/ {2,}/si' => ' ',
                '/<article/si' => '<div',
                '/(\x{00a0}|\x{0020}|\x{3000}|\x{feff})/u' => ' ',
            ];
            $body = preg_replace(array_keys($pattern), array_values($pattern), $body);

            // 清除多余标签
            $body = (string) preg_replace_callback('/<\/?([\w]+)([^<>]*)>/si', function ($ele) {
                if (in_array($ele[1], ['article', 'div', 'p', 'br', 'span', 'table', 'tr', 'td', 'th'])) {
                    return '<' . $ele[1] . '>';
                } elseif ('img' == $ele[1]) {
                    return $ele[0];
                }
            }, $body);
            while (preg_match('/<div[^<>]*><div/si', $body)) {
                $body = preg_replace('/<div[^<>]*><div/si', '<div', $body);
            }
            while (preg_match('/<div[^<>]*><\/div>/si', $body)) {
                $body = preg_replace('/<div[^<>]*><\/div>/si', '', $body);
            }
            while (preg_match('/<\/div><\/div>/si', $body)) {
                $body = preg_replace('/<\/div><\/div>/si', '</div>', $body);
            }

            // 替换图片
            $body = preg_replace_callback('/<img[^<>]+src=([^<>\s]+)[^<>]+>/si', function ($img) {
                return '{TAG:img_src=' . trim($img[1], '"\'') . '}';
            }, $body);

            // 替换表格
            $body = preg_replace_callback('/<table[^<>]*>(.*?)<\/table[^<>]*>/si', function ($table) {
                $table[1] = strip_tags($table[1], '<tr><td><th><br><p>');
                $table[1] = preg_replace_callback('/<(\/)?(tr|td|th)>/si', function ($tr) {
                    return '{TAG:' . $tr[1] . $tr[2] . '}';
                }, $table[1]);
                return '{TAG:table}' . $table[1] . '{TAG:/table}';
            }, $body);

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
                    if ($position = mb_strpos($content, '{TAG:/table}', $_length, 'utf-8')) {
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
                $content = '<p>' . implode('</p><p>', $content) . '</p>';

                // 清除版权等信息
                $pattern = [
                    // 版权
                    '/[^<>]*(©|copyright|&copy;)+[^<>]+/i',
                    // 备案号
                    '/[^<>]*\x{5907}\x{6848}\x{53f7}[^<>]+/u',
                    // 许可证
                    '/[^<>]*\x{8bb8}\x{53ef}\x{8bc1}[^<>]+/u',
                    // 空标签
                    '/<[\w]+><\/[\w]+>/si',
                ];
                $content = (string) preg_replace($pattern, '', $content);

                // 恢复表格
                $content = preg_replace_callback('/\{TAG:([\w\/]+)\}/si', function ($table) {
                    return '<' . $table[1] . '>';
                }, $content);
                // 恢复图片
                $content = preg_replace_callback('/\{TAG:img_src=([^<>\s]+)\}/si', function ($img) {
                    return '<img src="' . trim($img[1], '"\'') . '" />';
                }, $content);
            }
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
