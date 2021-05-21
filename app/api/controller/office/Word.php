<?php

/**
 *
 * API接口层
 * office Word读取导出
 *
 * @package   NICMS
 * @category  app\api\controller\office
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2020
 */

declare(strict_types=1);

namespace app\api\controller\office;

use think\Response;
use app\common\controller\BaseApi;
use app\common\library\tools\File;
use app\common\library\tools\OfficeWord;

class Word extends BaseApi
{

    /**
     * 读取Word数据
     * @access public
     * @param
     * @return Response
     */
    public function read()
    {
        $this->ApiInit();

        if (!$file = $this->request->param('file')) {
            return miss(404, false);
        }

        if ($file = File::pathDecode($file, true)) {
            $this->error('Word read error');
        }
    }

    /**
     * 导出Word数据
     * @access public
     * @param
     * @return Response
     */
    public function write()
    {
        $this->ApiInit();

        if (!$data = $this->request->param('data')) {
            return miss(404, false);
        }

        $file = (new OfficeWord)->write($data);

        return $file
            ? Response::create(['code' => 'success', 'path' => $file], 'json')
            ->allowCache(true)
            ->cacheControl('max-age=28800,must-revalidate')
            ->lastModified(gmdate('D, d M Y H:i:s') . ' GMT')
            ->expires(gmdate('D, d M Y H:i:s', time() + 28800) . ' GMT')
            : $this->error('Word write error');
    }
}
