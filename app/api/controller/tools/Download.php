<?php

/**
 *
 * 控制层
 * 下载API
 * 支持大文件下载
 *
 * @package   NICMS
 * @category  app\api\controller\tools
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\api\controller\tools;

use think\Response;
use app\common\controller\BaseApi;
use app\common\library\File;

class Download extends BaseApi
{

    public function index()
    {
        if (!$this->ValidateReferer()) {
            return miss(404);
        }

        if (!$file = $this->request->param('file')) {
            return miss(404);
        }

        if ($file = File::pathDecode($file, true)) {
            @set_time_limit(0);
            @ini_set('max_execution_time', '0');
            @ini_set('memory_limit', '16M');

            $name = sha1(pathinfo($file, PATHINFO_FILENAME) . date('Ymd')) . '.' . pathinfo($file, PATHINFO_EXTENSION);
            header('Pragma: public');
            header('Content-Type: ' . finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file));
            header('Content-Disposition: attachment; filename=' . $name);
            header('Content-Length: ' . filesize($file));
            header('Content-Transfer-Encoding: binary');

            $download_rate = 64 * 1024;

            ob_end_clean();
            $resource = fopen($file, 'r');
            while (!feof($resource)) {
                print fread($resource, (int) round($download_rate));
                flush();
                sleep(1);
            }
            fclose($resource);

            // return Response::create($file, 'file')
            //     ->name(sha1(pathinfo($file, PATHINFO_FILENAME) . date('Ymd')))
            //     ->isContent(false)
            //     ->expire(28800);
        }
    }
}
