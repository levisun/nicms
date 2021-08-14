<?php

/**
 *
 * 控制层
 * 静态资源
 *
 * @package   NICMS
 * @category  app\api\controller
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\api\controller;

use think\Response;
use app\common\controller\BaseApi;
use app\common\library\Filter;

class Storage extends BaseApi
{
    private $storageDir = [];
    private $filePath = '';
    private $mimeType = '';

    /**
     * 初始化
     * @access protected
     * @return void
     */
    protected function initialize()
    {
        if (!$this->ValidateReferer()) {
            miss(404, false, true);
        }

        $this->storageDir[] = public_path('static');
        $this->storageDir[] = public_path('storage/uploads/image');
        $this->storageDir[] = public_path('storage/uploads/media');
        $this->storageDir[] = public_path('theme');

        $path = $this->request->baseUrl();
        $path = Filter::strict($path);
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        foreach ($this->storageDir as $dir) {
            if (is_file($dir . $path)) {
                $this->filePath = $dir . $path;
                $this->mimeType = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $this->filePath);
                break;
            }
        }

        if (!$this->filePath) {
            miss(404, false, true);
        }
    }

    public function static()
    {
        // 修复CSS错误
        $this->mimeType = $this->mimeType == 'text/plain' && pathinfo($this->filePath, PATHINFO_EXTENSION) == 'css'
            ? 'text/css'
            : '';
        header('Content-Type: ' . $this->mimeType);
        header('Content-Length: ' . filesize($this->filePath));
        // 缺少缓存header信息

        $rate = 64 * 1024;

        ob_end_clean();
        $resource = fopen($this->filePath, 'r');
        while (!feof($resource)) {
            print fread($resource, (int) round($rate));
            flush();
            usleep(100000);
        }
        fclose($resource);
    }

    public function media()
    {
        header('Content-Type: ' . $this->mimeType);
        header('Content-Length: ' . filesize($this->filePath));
        // 缺少缓存header信息

        $rate = 64 * 1024;

        ob_end_clean();
        $resource = fopen($this->filePath, 'r');
        while (!feof($resource)) {
            print fread($resource, (int) round($rate));
            flush();
            usleep(100000);
        }
        fclose($resource);
    }
}
