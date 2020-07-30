<?php

/**
 *
 * 上传文件
 *
 * @package   NICMS
 * @category  app\common\library
 * @author    失眠小枕头 [levisun.mail@gmail.com]
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

    /**
     * 图片规定尺寸
     * @var array
     */
    private $thumbSize = [
        'width'  => 0,
        'height' => 0,
        'type'   => false,
    ];

    /**
     * 图片水印
     * @var bool
     */
    private $imgWater = true;

    public function __construct()
    {
        @set_time_limit(600);
        @ini_set('max_execution_time', '600');
        @ini_set('memory_limit', '128M');
    }

    /**
     * 获得上传文件信息
     * @access public
     * @param  int    $_uid 用户ID
     * @param  string $_element 表单名
     * @param  array  $_thumb 缩略图宽高,缩放比例
     * @param  bool   $_water 添加水印
     * @return array
     */
    public function getFileInfo(array $_user, string $_element, array $_thumb, bool $_water): array
    {
        $files = Request::file($_element);
        $this->thumbSize = [
            'width'  => !empty($_thumb['width']) ? (int) abs($_thumb['width']) : 0,
            'height' => !empty($_thumb['height']) ? (int) abs($_thumb['height']) : 0,
            // THUMB_SCALING:等比例缩放 | THUMB_FIXED:固定尺寸缩放
            'type'   => !empty($_thumb['type']) ? Image::THUMB_SCALING : Image::THUMB_FIXED,
        ];
        $this->imgWater = $_water;

        // 校验上传文件
        if (!$result = $this->validate($_element, $files)) {
            // 单文件
            if (is_string($_FILES[$_element]['name'])) {
                $result = $this->save($_user, $files);
            }

            // 多文件
            elseif (is_array($_FILES[$_element]['name'])) {
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
     * @param  \think\File $_files 文件
     * @return array
     */
    private function save(array &$_user, \think\File &$_files): array
    {
        $_dir = 'uploads' . DIRECTORY_SEPARATOR;

        // 用户类型目录[后台,前台,访客]
        $_dir .= !empty($_user['user_type'])
            ? Base64::flag($_user['user_type'], 7) . DIRECTORY_SEPARATOR
            : 'guest' . DIRECTORY_SEPARATOR;

        // 时间目录
        $_dir .= Base64::dechex((int) date('Ym')) . DIRECTORY_SEPARATOR;

        // 用户目录[删除用户时可删除目录]
        $_dir .= !empty($_user['user_id'])
            ? Base64::dechex($_user['user_id'])
            : '';

        $save_path = Config::get('filesystem.disks.public.url') . '/';
        $save_file = $save_path . Filesystem::disk('public')->putFile($_dir, $_files, 'uniqid');

        UploadLog::write($save_file);   // 记录上传文件日志

        if (false !== strpos($_files->getMime(), 'image/')) {
            $save_file = $this->thumb($save_file);
            $save_file = $this->water($save_file);
            $save_file = $this->toExt($_files->extension(), $save_file);
        }

        $save_file = str_replace(DIRECTORY_SEPARATOR, '/', $save_file);

        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        return [
            'extension' => pathinfo($save_file, PATHINFO_EXTENSION),
            'name'      => pathinfo($save_file, PATHINFO_BASENAME),
            'save_path' => $save_file,
            'size'      => filesize(public_path() . $save_file),
            'type'      => finfo_file($finfo, public_path() . $save_file),
            'url'       => Config::get('app.img_host') . $save_file,
        ];
    }

    /**
     * 转换图片格式
     * @access private
     * @param  string $_file 文件
     * @param  string $_save_file 文件名
     * @return void
     */
    private function toExt(string $_extension, string $_save_file): string
    {
        $image = Image::open(public_path() . $_save_file);

        // 转换webp格式
        if (function_exists('imagewebp')) {
            $webp_file = str_replace('.' . $_extension, '.webp', $_save_file);
            $image->save(public_path() . $webp_file, 'webp');
            UploadLog::write($webp_file);   // 记录上传文件日志
            // 删除非webp格式图片
            if ('webp' !== $_extension) {
                unlink(public_path() . $_save_file);
            }
            $_save_file = $webp_file;
        }

        // 转换jpg格式
        elseif ('gif' !== $_extension) {
            $jpg_file = str_replace('.' . $_extension, '.jpg', $_save_file);
            $image->save(public_path() . $jpg_file, 'jpg');
            UploadLog::write($jpg_file);   // 记录上传文件日志
            // 删除非jpg格式图片
            if ('jpg' !== $_extension) {
                unlink(public_path() . $_save_file);
            }
            $_save_file = $jpg_file;
        }

        return $_save_file;
    }

    /**
     * 添加水印
     * @access private
     * @param  string $_file 文件
     * @param  string $_save_file 文件名
     * @return void
     */
    private function water(string $_save_file): string
    {
        $image = Image::open(public_path() . $_save_file);

        // 添加水印
        if (true === $this->imgWater) {
            $ttf = root_path('extend/font') . 'simhei.ttf';
            $image->text(Request::rootDomain(), $ttf, 16, '#00000000', mt_rand(1, 9));
        }

        $image->save(public_path() . $_save_file);

        return $_save_file;
    }

    /**
     * 生成缩略图
     * @access private
     * @param  string $_file 文件
     * @param  string $_save_file 文件名
     * @return void
     */
    private function thumb(string $_save_file): string
    {
        $image = Image::open(public_path() . $_save_file);

        // 缩放图片到指定尺寸
        if ($this->thumbSize['width'] && $this->thumbSize['height']) {
            $image->thumb($this->thumbSize['width'], $this->thumbSize['height'], $this->thumbSize['type']);
        }
        // 规定图片最大尺寸
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
