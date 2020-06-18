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
use app\common\library\DataFilter;
use Lizhichao\Word\VicWord;

class Words extends Async
{

    public function index()
    {
        if ($this->request->isPost() && $this->validate->referer() && $this->validate->fromToken()) {
            if ($text = $this->request->param('text', false)) {
                // 过滤其他字符
                if (mb_strlen($text, 'UTF-8') <= 500 && $text = DataFilter::chs_alpha($text)) {
                    @ini_set('memory_limit', '128M');
                    // 词库
                    define('_VIC_WORD_DICT_PATH_', root_path('vendor/lizhichao/word/Data') . 'dict.json');
                    $fc = new VicWord('json');
                    $words = $fc->getAutoWord($text);
                    unset($fc);
                    foreach ($words as $key => $value) {
                        $value[0] = trim($value[0]);
                        if ($value[0]) {
                            $words[$key] = [
                                'length' => mb_strlen($value[0], 'UTF-8'),
                                'word'   => $value[0],
                            ];
                        } else {
                            unset($words[$key]);
                        }
                    }

                    // 排序
                    if ($sort = $this->request->param('sort', false)) {
                        // 过滤重复数据
                        $words = array_unique($words, SORT_REGULAR);

                        $sort = strtoupper($sort);
                        if ($sort == 'DESC') {
                            array_multisort(array_column($words, 'length'), SORT_DESC, $words);
                        } elseif ($sort == 'ASC') {
                            array_multisort(array_column($words, 'length'), SORT_DESC, $words);
                        }
                    }

                    // 如果设定长度,返回对应长度数组
                    if ($length = $this->request->param('length/d', false, 'abs')) {
                        $words = array_slice($words, 0, $length);
                    }

                    return $this->cache(true)->success('spider success', $words);
                }
            }
        }


        return miss(404);
    }
}
