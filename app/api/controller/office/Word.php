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
use app\common\library\File;
use app\common\library\office\OfficeWord;

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
            return miss(404);
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
            return miss(404);
        }

        $file = (new OfficeWord)->write($data);

        if (is_file($file)) {
            @set_time_limit(0);
            @ini_set('max_execution_time', '0');
            @ini_set('memory_limit', '16M');

            $name = sha1(hash_file('sha256', $file) . date('Ymd')) . '. ' . pathinfo($file, PATHINFO_EXTENSION);
            header('Pragma: public');
            header('Content-Type: ' . finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file));
            header('Content-Disposition: attachment; filename=' . $name);
            header('Content-Length: ' . filesize($file));
            header('Content-Transfer-Encoding: binary');

            ob_end_clean();
            $resource = fopen($file, 'r');
            while (!feof($resource)) {
                print fread($resource, (int) round(1024 * 64));
                flush();
                sleep(1);
            }
            fclose($resource);
        } else {
            return miss(404);
        }

        // return $file
        //     ? Response::create(['code' => 'success', 'path' => $file], 'json')
        //     ->allowCache(true)
        //     ->cacheControl('max-age=28800,must-revalidate')
        //     ->lastModified(gmdate('D, d M Y H:i:s') . ' GMT')
        //     ->expires(gmdate('D, d M Y H:i:s', time() + 28800) . ' GMT')
        //     : $this->error('Word write error');
    }
}
