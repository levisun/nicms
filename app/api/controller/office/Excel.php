<?php

/**
 *
 * API接口层
 * office Excel读取导出
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
use app\common\library\api\Async;
use app\common\library\Excel as LibExcel;

class Excel extends Async
{

    /**
     * 读取Excel数据
     * @access public
     * @param
     * @return Response
     */
    public function read()
    {
        if (!$this->validate->referer() || !$file = $this->request->param('file')) {
            return miss(404, false);
        }

        if ($file = filepath_decode($file, true)) {
            $sheet = $this->request->param('sheet/d', 0, 'abs');

            $result = (new LibExcel)->read($file, $sheet);

            return $result
                ? $this->cache(true)->success('Excel read success', $result)
                : $this->error('Excel read error');
        }
    }

    /**
     * 导出Excel数据
     * @access public
     * @param
     * @return Response
     */
    public function write()
    {
        if (!$this->validate->referer() || !$data = $this->request->param('data/a')) {
            return miss(404, false);
        }
        $sheet = $this->request->param('sheet/d', 0, 'abs');

        $file = (new LibExcel)->write($data, $sheet);

        return $file
            ? Response::create($file, 'file')
            ->name(pathinfo($file, PATHINFO_FILENAME))
            ->isContent(false)
            ->expire(28800)
            : $this->error('Excel write error');
    }
}
