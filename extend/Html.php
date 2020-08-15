<?php

declare(strict_types=1);

class Html
{

    /**
     * 获得内容
     * @access public
     * @static
     * @param  string $_html
     * @return string
     */
    public static function getContent(string &$_html): string
    {
        $content = '';
        preg_replace_callback('/<body.*?>(.*?)<\/body/si', function ($body) use (&$content) {
            $body = trim($body[1]);
            // 清除脚本与样式
            $body = preg_replace(['/<script.*?\/script>/si', '/<style.*?\/style>/si'], '', $body);
            // 替换article标签为div
            $body = str_replace('article', 'div', $body);
            // 替换空格
            $body = str_replace('&nbsp;', ' ', $body);
            // 清除a标签
            $body = preg_replace('/<a.*?\/a>/si', '', $body);
            // halt($body);
            // 清除多余标签
            $body = strip_tags($body, '<div><p><br><span>');
            $body = preg_replace([
                // 清除标签属性
                '/[\w\-]+=["\']+[^>]*["\']+/si',
                // 清除转义字符
                '/&[#\w]+;/si',

                // 清除日期和时间
                '/[\d]{2,4}[\-\/\.]+[\d]{1,2}[\-\/\.]+[\d]{1,2}/si',
                '/[\d]{1,2}:[\d]{1,2}:[\d]{1,2}/si',
                '/[\d]{1,2}:[\d]{1,2}/si',

                // 清除电话
                '/[\d]{11}+/si',
                '/[\d]{3}-[\d]{3,4}-[\d]{3,4}+/si',
                '/[\d]{3,4}-[\d]{8}/si',


                '/[a-zA-Z0-9]{20,}/si',
                '/[|]+/si',
            ], '', $body);
            // halt($body);
            // 修复标签中的空格
            $body = preg_replace('/[ ]+>/si', '>', $body);
            // 清除空格
            $body = preg_replace('/[ ]{2,}/si', '', $body);
            // 清除无用标签
            $body = preg_replace('/<span>[0-9]{4,}<\/span>/si', '', $body);
            $body = preg_replace('/<\/?span>/si', '', $body);
            while (preg_match('/<div[^<>]*><div/si', $body)) {
                $body = preg_replace('/<div[^<>]*><div/si', '<div', $body);
            }
            while (preg_match('/<div[^<>]*><\/div>/si', $body)) {
                $body = preg_replace('/<div[^<>]*><\/div>/si', '', $body);
            }
            while (preg_match('/<\/div><\/div>/si', $body)) {
                $body = preg_replace('/<\/div><\/div>/si', '</div>', $body);
            }
            // halt($body);

            // 标签转回车
            $body = str_ireplace(['<p>', '</p>', '<br>', '<br />', '<br/>'], PHP_EOL, $body);
            $body = str_replace('　', '', $body);
            // halt($body);

            // 匹配内容
            $pattern = '/>[^<>]{160,}</si';
            preg_match_all($pattern, $body, $matches);
            $content = $matches[0];
            foreach ($content as $key => $value) {
                $content[$key] = trim($value, '><') . PHP_EOL;
            }
            // halt($content);
            $content = implode('', $content);

            // 过滤Emoji
            $content = (string) preg_replace_callback('/./u', function (array $matches) {
                return strlen($matches[0]) >= 4 ? '' : $matches[0];
            }, $content);

            // 恢复格式
            $content = nl2br($content);
            $content = explode('<br />', nl2br($content));

            // 跳过字符
            $jump = [
                '版权', '@', 'copyright', 'ICP', '办理工商登记', '举报原因',
                '可选中1个或多个下面的关键词', '大脑最佳状态搜索资料', '发布者',
                '扫码支付', '微信支付', '举报电话', '订单号', '商户单号', '支付宝', '悬赏分',
            ];
            foreach ($content as $key => $value) {
                foreach ($jump as $needle) {
                    if (stripos($value, $needle)) {
                        $content[$key] = '';
                    }
                }
            }
            $content = array_map('trim', $content);
            $content = array_filter($content);

            $content = !empty($content)
                ? '<p>' . implode('</p><p>', $content) . '</p>'
                : '';
        }, $_html);

        return $content;
    }
}
