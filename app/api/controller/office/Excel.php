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
use app\common\controller\BaseApi;
use app\common\library\tools\File;
use app\common\library\tools\OfficeExcel;

class Excel extends BaseApi
{

    /**
     * 读取Excel数据
     * @access public
     * @param
     * @return Response
     */
    public function read()
    {
        $this->ApiInit();

        if (!$file = $this->request->param('file')) {
            return miss(404);
        }

        if ($file = File::pathDecode($file, true)) {
            $sheet = $this->request->param('sheet/d', 0, 'abs');

            if (!$this->cache->has($file . $sheet) || !$result = $this->cache->get($file . $sheet)) {
                $result = (new OfficeExcel)->read($file, $sheet);
                $this->cache->set($file . $sheet, $result);
            }

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
        $this->ApiInit();

        if (!$data = $this->request->param('data/a')) {
            return miss(404);
        }
        $sheet = $this->request->param('sheet/d', 0, 'abs');

        $file = (new OfficeExcel)->write($data, $sheet);

        return $file
            ? Response::create($file, 'file')->name(pathinfo($file, PATHINFO_FILENAME))->isContent(false)->expire(28800)
            : $this->error('Excel write error');
    }
}
