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

use think\Image as ThinkImage;
use app\common\controller\BaseApi;
use app\common\library\Filter;

class Storage extends BaseApi
{
    /**
     * 允许访问文件后缀,避免恶意有害文件访问
     * @var array
     */
    private $fileExtension = [
        'css', 'map', 'js',
        'jpg', 'gif', 'png', 'webp',
        'mp3', 'mp4',
        'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'pdf',
        'zip'
    ];
    private $storageDir = [];
    private $filePath = '';
    private $mimeType = '';
    private $extension = '';

    /**
     * 初始化
     * @access protected
     * @return void
     */
    protected function initialize()
    {
        $this->storageDir[] = public_path('static');
        $this->storageDir[] = public_path('storage/uploads/image');
        $this->storageDir[] = public_path('storage/uploads/media');
        $this->storageDir[] = public_path('storage/uploads/office');
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

        $this->getExtension();
        $this->getMimeType();
    }

    public function static(): void
    {
        $this->ValidateReferer();
        $this->thumb();
        $this->header();
        $this->resource();
    }

    public function media(): void
    {
        $this->ValidateReferer();
        $this->header();
        $this->resource();
    }

    public function file()
    {
        if ($this->request->param('view') && in_array($this->extension, ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'])) {
            $html = '<html lang="zh-cn"><head><meta charset="UTF-8"><meta name="robots" content="none" /><meta name="renderer" content="webkit" /><meta name="force-rendering" content="webkit" /><meta name="viewport"content="width=device-width,initial-scale=1,maximum-scale=1,minimum-scale=1,user-scalable=no" /><meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" /><title>office 预览</title></head><body style="padding:0;margin:0"><iframe id="office" name="office" frameborder="0" scrolling="no" width="100%" height ="100%" src="https://view.officeapps.live.com/op/view.aspx?src=' . urlencode($this->request->baseUrl(true)) . '"></iframe></body></html>';

            return \think\Response::create($html)
                ->allowCache(true)
                ->cacheControl('max-age=31536000,must-revalidate')
                ->lastModified(gmdate('D, d M Y H:i:s') . ' GMT')
                ->expires(gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
        } else {
            $this->ValidateReferer();
            $this->download();
        }
    }

    /**
     * 缩略图
     * @access public
     * @param
     * @return void
     */
    private function thumb(): void
    {
        $width = $height = 0;
        $size = $this->request->param('m');
        if ($size && false !== strpos($size, 'x')) {
            list($width, $height) = explode('x', $this->request->param('m'));
            $width = abs((int) $width);
            $height = abs((int) $height);
        }

        if (in_array($this->extension, ['gif', 'jpg', 'jpeg', 'png', 'bmp', 'webp']) && ($width || $height)) {
            $width = intval($width / 10) * 10;
            $width = 800 > $width ? $width : 800;
            $width = 10 < $width ? $width : 10;

            if ($height) {
                $height = intval($height / 10) * 10;
                $height = 800 > $height ? $height : 800;
                $height = 10 < $height ? $height : 10;
            } else {
                $height = $width;
            }

            $thumb_file = md5(hash_file('sha256', $this->filePath) . $width . $height) . '.' . $this->extension;
            $path = runtime_path('thumb/' . substr($thumb_file, 0, 2));
            if (!is_dir($path)) mkdir($path, 0755, true);

            if (!is_file($path . $thumb_file)) {
                @ini_set('memory_limit', '128M');
                $image = ThinkImage::open($this->filePath);
                $width = $image->width() > $width ? $width : $image->width();
                $height = $image->height() > $height ? $height : $image->height();
                $image->thumb($width, $height, ThinkImage::THUMB_SCALING);
                $image->save($path . $thumb_file);
                unset($image);
            }

            $this->filePath = $path . $thumb_file;
        }
    }

    /**
     * 文件下载
     * @access private
     * @param
     * @return void
     */
    private function download(): void
    {
        if (in_array($this->extension, ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'pdf', 'zip'])) {
            @set_time_limit(0);
            @ini_set('max_execution_time', '0');
            @ini_set('memory_limit', '16M');
            $name = md5(hash_file('sha256', $this->filePath) . date('Ymd')) . '.' . $this->extension;
            header('Pragma: public');
            header('Content-Type: ' . $this->mimeType . '; charset=utf-8');
            header('Content-Disposition: attachment; filename=' . $name);
            header('Content-Length: ' . filesize($this->filePath));
            header('Content-Transfer-Encoding: binary');

            $this->resource();
        } else {
            miss(404, false, true);
        }
    }

    /**
     * 输出
     * @access private
     * @param
     * @return void
     */
    private function resource(): void
    {
        ob_end_clean();
        $resource = fopen($this->filePath, 'r');
        while (!feof($resource)) {
            print fread($resource, (int) round(1024 * 64));
            flush();
            usleep(100000);
        }
        fclose($resource);
    }

    /**
     * 通用输出头部信息
     * @access private
     * @param
     * @return void
     */
    private function header(): void
    {
        header('Content-Type: ' . $this->mimeType . '; charset=utf-8');
        header('Content-Length: ' . filesize($this->filePath));
        header('Cache-control: max-age=31536000,must-revalidate');
        header('Last-Modified:' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Expires:' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
        header('ETag:' . hash_file('md5', $this->filePath));
    }

    /**
     * 获得文件mime type值
     * @access private
     * @param
     * @return void
     */
    private function getMimeType(): void
    {
        if ('css' === $this->extension) {
            $this->mimeType = 'text/css';
        } elseif ('js' === $this->extension) {
            $this->mimeType = 'application/javascript';
        }
    }

    /**
     * 获得文件后缀名
     * @access private
     * @param
     * @return void
     */
    private function getExtension(): void
    {
        $this->extension = pathinfo($this->filePath, PATHINFO_EXTENSION);
        $this->extension = strtolower($this->extension);
        if (!in_array($this->extension, $this->fileExtension)) {
            miss(404, false, true);
        }
    }

    protected function ValidateReferer(): bool
    {
        $referer = $this->request->server('HTTP_REFERER');
        if (!$referer || false === stripos($referer, $this->request->rootDomain())) {
            miss(404, false, true);
        }
        return true;
    }
}
