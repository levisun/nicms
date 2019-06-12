<?php
/**
 *
 * API接口层
 * 文章内容
 *
 * @package   NICMS
 * @category  app\service\cms\article
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
declare (strict_types = 1);

namespace app\service\cms\article;

use app\service\cms\ArticleBase;

class Details extends ArticleBase
{

    /**
     * 查询内容
     * @access public
     * @param
     * @return array
     */
    public function query(): array
    {
        if ($result = $this->details()) {

            $result['content'] = preg_replace('/(style=["|\'])(.*?)(["|\'])/si', '', $result['content']);


            // $result['content']



            return [
                'debug' => false,
                'cache' => true,
                'msg'   => $this->lang->get('success'),
                'data'  => $result
            ];
        } else {
            return [
                'debug' => false,
                'cache' => true,
                'msg'   => $this->lang->get('article not')
            ];
        }
    }

    /**
     * 更新浏览量
     * @access public
     * @param
     * @return array
     */
    public function hits(): array
    {
        return [
            'debug' => false,
            'cache' => true,
            'expire' => 30,
            'msg'   => $this->lang->get('success'),
            'data'  => parent::hits()
        ];
    }
}
