<?php

/**
 *
 * 分词
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

use app\common\library\Filter;
use Lizhichao\Word\VicWord;

class Participle
{

    private $result = [];

    public function __construct(string $_txt = '')
    {
        if (!Filter::nonChsAlpha($_txt)) {
            return $_txt;
        }

        $_txt = Filter::htmlDecode($_txt);

        $this->result['words'] = $this->words($_txt, 0, 'DESC');
        $this->result['segmentation'] = $this->segmentation($_txt);

        foreach ($this->result['segmentation'] as $key => $value) {
            $sentence = $this->sentence($value);
            foreach ($sentence as $row => $val) {
                $words = $this->identity($row, $val);
                $flag = '';
                foreach ($words as $v) {
                    $flag .= $v['flag'];
                }
                $sentence[$row] = [
                    'txt'   => $val,
                    'words' => $words,
                    'flag'  => $flag,
                ];
            }

            $this->result['segmentation'][$key] = [
                'txt'      => $value,
                'sentence' => $sentence,
            ];
        }
    }

    public function __get(string $_name)
    {
        return $this->$_name;
    }

    /**
     * 词语文字指纹
     * @access private
     * @param  int     $_row 行号
     * @param  string  $_txt 行内容
     * @return array
     */
    private function identity(int &$_row, string &$_txt): array
    {
        $words = $this->words($_txt);
        $length = mb_strlen($_txt, 'UTF-8');
        $new_words = [];
        foreach ($words as $key => $value) {
            // 词语在句中位置
            $numeric = mb_strpos($_txt, $value, 0, 'UTF-8');

            // 句首
            if ($numeric === 0) {
                $identity = '{R' . $_row . 'N' . $key . 'LF}';
                $attr = 'first';
            }
            // 句尾
            elseif ($numeric === ($length - mb_strlen($value, 'UTF-8'))) {
                $identity = '{R' . $_row . 'N' . $key . 'LE}';
                $attr = 'end';
            }
            // 句中
            else {
                $identity = '{R' . $_row . 'N' . $key . 'LM}';
                $attr = 'middle';
            }

            $new_words[] = [
                'txt'   => $value,
                'flag'  => $identity,
                'attr'  => [
                    'location' => $attr,
                    'ago'      => !empty($words[$key - 1]) ? $words[$key - 1] : '',
                    'after'    => !empty($words[$key + 1]) ? $words[$key + 1] : '',
                ],
            ];
        }

        return $new_words;
    }

    /**
     * 分词
     * @access public
     * @param  string  $_txt
     * @param  int     $_length
     * @param  string  $_sort
     * @return array
     */
    public function words(string $_txt, int $_length = 0, string $_sort = ''): array
    {
        if (!Filter::nonChsAlpha($_txt)) {
            return [];
        }

        $date = $this->date($_txt);

        @ini_set('memory_limit', '128M');
        $fc = new VicWord();
        $words = $fc->getAutoWord($_txt);
        unset($fc);

        $length = [];
        foreach ($words as $key => $value) {
            $value[0] = trim($value[0]);
            $length[] = mb_strlen($value[0], 'utf-8');
            $words[$key] = $value[0];
        }
        $words = array_merge($words, $date);

        // 排序
        if ($_sort) {
            $_sort = strtoupper($_sort) === 'ASC' ? SORT_ASC : SORT_DESC;
            array_multisort($length, $_sort, $words);
        }
        unset($length);

        // 过滤重复数据或空数据
        $words = array_unique($words);
        $words = array_filter($words);

        // 如果设定长度,返回对应长度数组
        return $_length ? array_slice($words, 0, $_length) : $words;
    }

    /**
     * 提取日期
     * @access private
     * @param  string  $_txt
     * @return array
     */
    private function date(string &$_txt): array
    {
        $date = [];
        $_txt = (string) preg_replace_callback('/([\d]+[\x{4e00}-\x{9fa5}]{1})/u', function ($matches) use (&$date) {
            $matches = array_map('trim', $matches);
            if (false !== mb_strpos($matches[1], '年', 0, 'utf-8')) {
                $date[] = $matches[1];
            } elseif (false !== mb_strpos($matches[1], '月', 0, 'utf-8')) {
                $date[] = $matches[1];
            } elseif (false !== mb_strpos($matches[1], '日', 0, 'utf-8')) {
                $date[] = $matches[1];
            } elseif (false !== mb_strpos($matches[1], '时', 0, 'utf-8')) {
                $date[] = $matches[1];
            } elseif (false !== mb_strpos($matches[1], '分', 0, 'utf-8')) {
                $date[] = $matches[1];
            } elseif (false !== mb_strpos($matches[1], '秒', 0, 'utf-8')) {
                $date[] = $matches[1];
            } else {
                return mb_substr($matches[1], mb_strlen($matches['1'], 'utf-8') - 1);
            }
            return '';
        }, $_txt);

        return $date;
    }

    /**
     * 分段
     * @access private
     * @param  string  $_txt
     * @return array
     */
    private function segmentation(string &$_txt): array
    {
        $segmentation = strip_tags($_txt, '<br><p>');
        $segmentation = str_replace(['<br>', '<br />', '<p>', '</p>'], PHP_EOL, $segmentation);
        $segmentation = nl2br($segmentation);
        $segmentation = explode('<br />', $segmentation);
        $segmentation = array_map('trim', $segmentation);
        $segmentation = array_filter($segmentation);

        return $segmentation;
    }

    /**
     * 分句
     * @access private
     * @param  string  $_txt
     * @return array
     */
    private function sentence(string &$_txt): array
    {
        $sentence = strip_tags($_txt);
        $sentence = str_replace([
            '。', '！', '!', '？', '?', '；', ';', '：', ':', '，', ',', '......', '...', '.', '……', '————'
        ], '<br />', $_txt);
        $sentence = explode('<br />', $sentence);
        $sentence = array_map('trim', $sentence);
        $sentence = array_unique($sentence);
        $sentence = array_filter($sentence);

        return $sentence;
    }
}
