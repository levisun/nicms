<?php

/**
 *
 * 上传文件
 *
 * @package   NICMS
 * @category  app\common\library
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\common\library;

use think\Image;
use think\facade\Config;
use think\facade\Request;
use think\facade\Filesystem;
use think\Validate;
use app\common\library\Base64;
use app\common\library\UploadLog;

class UploadFile
{

    /**
     * 允许上传文件后缀,避免恶意修改配置文件导致的有害文件上传
     * @var array
     */
    private $fileExtension = [
        'jpg', 'gif', 'png', 'webp',
        'mp3', 'mp4',
        'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'pdf',
        'zip'
    ];
    private $fileMime = [
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'gif'  => 'image/gif',
        'gz'   => 'application/x-gzip',
        'jpeg' => 'image/jpeg',
        'mp3'  => 'audio/mpeg',
        'mp4'  => 'video/mp4',
        'pdf'  => 'application/pdf',
        'png'  => 'image/png',
        'ppt'  => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'rar'  => 'application/octet-stream',
        'xls'  => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'zip'  => 'application/zip',
        '7z'   => 'application/x-7z-compressed'
    ];

    private $element = null;

    /**
     * 图片宽
     * @var int
     */
    private $imgWidth = 0;

    /**
     * 图片高
     * @var int
     */
    private $imgHeight = 0;

    /**
     * 图片缩略图(裁减)类型
     * @var int
     */
    private $imgThumbType = 0;

    /**
     * 图片水印
     * @var bool
     */
    private $imgWater = true;

    public function __construct(array $_size, bool $_water, string $_element)
    {
        @set_time_limit(600);
        @ini_set('max_execution_time', '600');
        @ini_set('memory_limit', '128M');

        $this->imgWidth = !empty($_size['width']) ? abs($_size['width']) : 0;
        $this->imgHeight = !empty($_size['height']) ? abs($_size['height']) : 0;
        // THUMB_SCALING:等比例缩放 | THUMB_FIXED:固定尺寸缩放
        $this->imgThumbType = !empty($_size['type']) ? Image::THUMB_SCALING : Image::THUMB_FIXED;

        $this->imgWater = $_water;

        $this->element = $_element;
    }

    /**
     * 获得上传文件信息
     * @access public
     * @param  array $_user 用户
     * @return array
     */
    public function getFileInfo(array $_user): array
    {
        $files = Request::file($this->element);

        // 校验上传文件
        if (!$result = $this->validate($this->element, $files)) {
            // 单文件
            if (is_string($_FILES[$this->element]['name'])) {
                $result = $this->save($_user, $files);
            }

            // 多文件
            elseif (is_array($_FILES[$this->element]['name'])) {
                $result = [];
                foreach ($files as $file) {
                    $result[] = $this->save($_user, $file);
                }
            }
        }

        return $result;
    }

    /**
     * 校验上传文件
     * @access private
     * @param  string $_element HTML元素
     * @param  \think\File $_files 文件
     * @return bool|string
     */
    private function validate(string &$_element, \think\File &$_files)
    {
        $size = (int) Config::get('app.upload_size', 1) * 1048576;

        // 允许上传文件后缀,避免恶意修改配置文件导致的有害文件上传
        $ext = Config::get('app.upload_type');
        $ext = explode(',', $ext);
        foreach ($ext as $key => $value) {
            if (!in_array($value, $this->fileExtension)) {
                unset($ext[$key]);
            }
        }
        $ext = implode(',', $ext);

        $validate = new Validate;
        $error = $validate->rule([
            $_element => [
                'fileExt'  => $ext,
                'fileSize' => $size
            ]
        ])->batch(false)->failException(false)->check([$_element => $_files]);

        return $error ? false : $validate->getError();
    }

    /**
     * 保存上传文件
     * @access private
     * @param  array $_user 用户
     * @param  \think\File $_files 文件
     * @return array
     */
    private function save(array &$_user, \think\File &$_files): array
    {
        $path = $this->savePath($_user, $_files);

        $save_path = Config::get('filesystem.disks.public.url') . DIRECTORY_SEPARATOR;
        $save_path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $save_path);
        $save_file = $save_path . Filesystem::disk('public')->putFile($path, $_files, 'uniqid');

        // 记录上传文件日志
        UploadLog::write($save_file);

        if (false !== stripos($_files->getMime(), 'image/')) {
            $save_file = $this->tailoring($save_file);
            $save_file = $this->water($save_file);
            $save_file = $this->toExt($save_file);
            $host = Config::get('app.img_host');
        } else {
            $host = Config::get('app.static_host');
        }

        $save_file = str_replace(DIRECTORY_SEPARATOR, '/', ltrim($save_file, '/'));

        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        return [
            'extension' => pathinfo($save_file, PATHINFO_EXTENSION),
            'name'      => pathinfo($save_file, PATHINFO_BASENAME),
            'save_path' => $save_file,
            'size'      => filesize(public_path() . $save_file),
            'type'      => finfo_file($finfo, public_path() . $save_file),
            'url'       => $host . ltrim($save_file, '\/'),
        ];
    }

    /**
     * 保存路径
     * @access private
     * @param  array $_user 用户
     * @return string
     */
    private function savePath(array &$_user, \think\File &$_files): string
    {
        $save_dir  = 'uploads' . DIRECTORY_SEPARATOR;

        if (in_array($_files->extension(), ['jpg', 'gif', 'png', 'webp'])) {
            $save_dir .= 'image' . DIRECTORY_SEPARATOR;
        } elseif (in_array($_files->extension(), ['mp3', 'mp4'])) {
            $save_dir .= 'media' . DIRECTORY_SEPARATOR;
        } elseif (in_array($_files->extension(), ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'pdf'])) {
            $save_dir .= 'office' . DIRECTORY_SEPARATOR;
        } else {
            $save_dir .= 'file' . DIRECTORY_SEPARATOR;
        }

        $save_dir .= Base64::url62encode((int) date('Y')) . DIRECTORY_SEPARATOR;
        $save_dir .= Base64::url62encode((int) date('m')) . DIRECTORY_SEPARATOR;

        // 用户类型ID目录
        if (!empty($_user['userType']) && !empty($_user['userId'])) {
            $save_dir .= Base64::flag($_user['userType']) . DIRECTORY_SEPARATOR;
            $save_dir .= Base64::url62encode((int) $_user['userId']) . DIRECTORY_SEPARATOR;
        } else {
            $save_dir .= 'guest' . DIRECTORY_SEPARATOR;
        }

        return $save_dir;
    }

    /**
     * 转换图片格式
     * @access private
     * @param  string $_save_file 文件名
     * @return void
     */
    private function toExt(string &$_save_file): string
    {
        $extension = pathinfo($_save_file, PATHINFO_EXTENSION);
        $new_file = $_save_file;

        $new_ext = false;
        if (function_exists('imagewebp')) {
            $new_ext = 'webp';
        } elseif ('gif' !== $extension) {
            $new_ext = 'jpg';
        }

        if ($new_ext) {
            $new_file = str_replace('.' . $extension, '.' . $new_ext, $_save_file);
            $image = Image::open(public_path() . $_save_file);
            $image->save(public_path() . $new_file, $new_ext);
            unlink(public_path() . $_save_file);
        }

        // 记录上传文件日志
        UploadLog::write($new_file);

        return $new_file;
    }

    /**
     * 添加图片水印
     * @access private
     * @param  string $_save_file 文件名
     * @return void
     */
    private function water(string &$_save_file): string
    {
        // 添加水印
        if (true === $this->imgWater) {
            $ttf = root_path('extend/font') . 'simhei.ttf';
            $image = Image::open(public_path() . $_save_file);
            $image->text(Request::rootDomain(), $ttf, 16, '#00000000', mt_rand(1, 9), mt_rand(1, 3));
            $image->save(public_path() . $_save_file);
        }

        return $_save_file;
    }

    /**
     * 裁减图片
     * @access private
     * @param  string $_save_file 文件名
     * @return void
     */
    private function tailoring(string &$_save_file): string
    {
        $image = Image::open(public_path() . $_save_file);

        // 裁减图片到指定尺寸
        if ($this->imgWidth && $this->imgHeight) {
            $image->thumb($this->imgWidth, $this->imgHeight, $this->imgThumbType);
        }
        // 裁减图片到最大尺寸
        elseif ($image->width() >= 800) {
            $image->thumb(800, 800, Image::THUMB_SCALING);
        }
        // 过滤图片中的木马信息
        else {
            $image->thumb($image->width(), $image->height(), Image::THUMB_SCALING);
        }

        $image->save(public_path() . $_save_file);

        return $_save_file;
    }
}
