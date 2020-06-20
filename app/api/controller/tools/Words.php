<?php

/**
 *
 * API接口层
 * 分词
 *
 * @package   NICMS
 * @category  app\api\controller\tools
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2020
 */

declare(strict_types=1);

namespace app\api\controller\tools;

use app\common\library\api\Async;

class Words extends Async
{

    public function index()
    {
        if ($txt = $this->request->param('txt', false)) {
            if (mb_strlen($txt, 'UTF-8') <= 500) {
                $result = [];
                $result['segmentation'] = $this->segmentation($txt);
                foreach ($result['segmentation'] as $key => $value) {
                    $sentence = $this->sentence($value);
                    foreach ($sentence as $row => $val) {
                        $words = $this->words($row, $val);
                        $flag = '';
                        foreach ($words as $k => $v) {
                            $flag .= $k;
                        }
                        $sentence[$row] = [
                            'txt'   => $val,
                            'words' => $words,
                            'flag'  => $flag,
                        ];
                    }

                    $result['segmentation'][$key] = [
                        'txt'      => $value,
                        'sentence' => $sentence,
                    ];
                }

                return $this->cache(true)->success('spider success', $result);
            }
        }

        return miss(404);
    }

    /**
     * 分词
     * @access private
     * @param  int    $_row
     * @param  string $_txt
     * @return array
     */
    private function words(int $_row, string &$_txt): array
    {
        $words = words($_txt);
        $length = mb_strlen($_txt, 'UTF-8');
        foreach ($words as $key => $value) {
            // 词语在句中位置
            $numeric = mb_strpos($_txt, $value, 0, 'UTF-8');

            // 句首
            if ($numeric === 0) {
                $identity = '{R' . $_row . 'N' . $key . 'LF}';
                $words[$identity] = $value;
                unset($words[$key]);
            }
            // 句尾
            elseif ($numeric === ($length - mb_strlen($value, 'UTF-8'))) {
                $identity = '{R' . $_row . 'N' . $key . 'LE}';
                $words[$identity] = $value;
                unset($words[$key]);
            }
            // 句中
            else {
                $identity = '{R' . $_row . 'N' . $key . 'LM}';
                $words[$identity] = $value;
                unset($words[$key]);
            }
        }

        return $words;
    }

    /**
     * 分段
     * @access private
     * @param  string  $_txt
     * @return array
     */
    private function segmentation(string &$_txt): array
    {
        $segmentation = strip_tags($_txt);
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
            '。', '！', '!', '？', '?', '；', ';', '：', ':', '，', ',', '……', '、', '————'
        ], '<br />', $_txt);
        $sentence = explode('<br />', $sentence);
        $sentence = array_map('trim', $sentence);
        $sentence = array_unique($sentence);
        $sentence = array_filter($sentence);

        return $sentence;
    }
}
