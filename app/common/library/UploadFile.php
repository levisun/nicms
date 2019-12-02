<?php

/**
 *
 * 模板驱动
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
use think\facade\Filesystem;
use think\facade\Log;
use think\Validate;

class UploadFile
{
    /**
     * 用户ID
     * @var int
     */
    private $uid = 0;

    /**
     * 表单name
     * @var string
     */
    private $element = '';

    /**
     * 上传文件实例
     * @var object
     */
    private $files = null;

    /**
     * 图片规定尺寸
     * @var array
     */
    private $thumbSize = [
        'width' => 0,
        'height' => 0,
        'type' => false,
    ];

    /**
     * 上传文件日志
     * @var string
     */
    private $uploadLogFile = '';

    public function remove(int $_uid, string $_sql): void
    {
        trace($_uid . $_sql, 'alert');
        $data = is_file($this->uploadLogFile) ? include $this->uploadLogFile : '';

        if ($fp = @fopen($this->uploadLogFile, 'w+')) {
            if (flock($fp, LOCK_EX | LOCK_NB)) {
                $data = !empty($data) ? (array) $data : [];
                foreach ($data as $key => $value) {
                    if (false !== strpos($value, $_sql)) {
                        unset($data[$key]);
                    }
                }

                $upload_path = app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR;
                foreach ($data as $key => $value) {
                    $value = str_replace('/', DIRECTORY_SEPARATOR, trim($value, " \/,._-\t\n\r\0\x0B"));
                    if (is_file($upload_path . $value)) {
                        unlink($upload_path . $value);
                    }

                    // 删除缩略图
                    $ext = '.' . pathinfo($value, PATHINFO_EXTENSION);
                    for ($i = 1; $i < 9; $i++) {
                        $size = $i * 100;
                        $thumb = str_replace($ext, '_' . $size . $ext, $value);
                        if (is_file($upload_path . $thumb)) {
                            Log::record('[删除上传垃圾] ' . $thumb, 'alert');
                            unlink($upload_path . $thumb);
                        }
                    }

                    unset($data[$key]);
                }

                $data = '<?php /*' . $_uid . '*/ return ' . var_export($data, true) . ';';
                fwrite($fp, $data);
                flock($fp, LOCK_UN);
            }
            fclose($fp);
        }
    }

    /**
     * 删除上传垃圾文件
     * @access public
     * @return void
     */
    public function ReGarbage()
    {
        $path = app()->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . 'lock' . DIRECTORY_SEPARATOR;
        is_dir($path) or mkdir($path, 0755, true);
        $lock = $path . 'remove_upload_garbage.lock';

        if (!is_file($lock) || filemtime($lock) <= strtotime('-12 hour')) {
            if ($fp = @fopen($lock, 'w+')) {
                if (flock($fp, LOCK_EX | LOCK_NB)) {
                    $upload_path = app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR;
                    $path = app()->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR .
                        'temp' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . '*';
                    $dir = (array) glob($path);
                    foreach ($dir as $files) {
                        if (!is_file($files)) {
                            continue;
                        }
                        $data = include $files;
                        $data = !empty($data) ? (array) $data : [];
                        foreach ($data as $value) {
                            $extension = pathinfo($value, PATHINFO_EXTENSION);
                            $value = $upload_path . str_replace('/', DIRECTORY_SEPARATOR, trim($value, " \/,._-\t\n\r\0\x0B"));
                            if (!is_file($value)) {
                                continue;
                            }

                            // 图片
                            if (in_array($extension, ['png', 'webp'])) {
                                for ($i = 1; $i < 9; $i++) {
                                    $size = $i * 100;
                                    $thumb = str_replace('.' . $extension, '_' . $size . '.png', $value);
                                    if (!is_file($thumb)) {
                                        @unlink($thumb);
                                    }
                                    $thumb = str_replace('.' . $extension, '_' . $size . '.webp', $value);
                                    if (!is_file($thumb)) {
                                        @unlink($thumb);
                                    }
                                }
                            }

                            @unlink($value);
                        }
                    }

                    fwrite($fp, '删除上传垃圾文件' . date('Y-m-d H:i:s'));
                    flock($fp, LOCK_UN);
                }
                fclose($fp);
            }
        }
    }

    /**
     * 获得上传文件信息
     * @access public
     * @param  int    $_uid 用户ID
     * @param  string $_element 表单名
     * @param  int    $_width 缩略图宽
     * @param  int    $_height 缩略图宽
     * @param  bool   $_type 缩略图是否等比例缩放 默认false
     * @retuen array
     */
    public function getFileInfo(int $_uid = 0, string $_element = '', int $_width = 0, int $_height = 0, bool $_type = false): array
    {
        $files = app('request')->file($_element);
        $this->thumbSize = [
            'width'  => $_width,
            'height' => $_height,
            'type'   => $_type ? Image::THUMB_SCALING : Image::THUMB_FIXED,
        ];

        $path = app()->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR .
            'temp' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
        is_dir($path) or mkdir($path, 0755, true);

        $this->uploadLogFile = $path . md5('upload_file_log' . date('Ymd') . $_uid) . '.php';

        // 校验上传文件
        if (!$result = $this->validate($_element, $files)) {
            // 单文件
            if (is_string($_FILES[$_element]['name'])) {
                $result = $this->save($_uid, $files);
            }

            // 多文件
            elseif (is_array($_FILES[$_element]['name'])) {
                $result = [];
                foreach ($files as $file) {
                    $result[] = $this->save($_uid, $file);
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
        $ext = Config::get('app.upload_type', 'doc,docx,gif,gz,jpeg,mp3,mp4,pdf,png,ppt,pptx,rar,xls,xlsx,zip');
        $mime = [
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

        $validate = new Validate;
        $error = $validate->rule([
            $_element => [
                'fileExt'  => $ext,
                // 'fileMime' => $mime,
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
    private function save(int &$_uid, \think\File &$_files): array
    {
        $_dir = $_uid
            ? '/u' . dechex(date('ym')) . dechex($_uid)
            : '/t' . dechex(date('ym'));

        $save_path = Config::get('filesystem.disks.public.url') . '/';
        $save_file = $save_path . Filesystem::disk('public')->putFile('uploads' . $_dir, $_files, 'uniqid');
        $this->write($_uid, $save_file);   // 记录上传文件日志

        if (false !== strpos($_files->getMime(), 'image/')) {
            $image = Image::open(app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . $save_file);

            // 缩放图片到指定尺寸
            if ($this->thumbSize['width'] && $this->thumbSize['height']) {
                $image->thumb($this->thumbSize['width'], $this->thumbSize['height'], $this->thumbSize['type']);
            }
            // 规定图片最大尺寸
            elseif ($image->width() > 800) {
                $image->thumb(800, 800, Image::THUMB_SCALING);
            }

            // 转换webp格式
            $webp_file = str_replace('.' . $_files->extension(), '.webp', $save_file);
            $image->save(app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . $webp_file, 'webp');
            $this->write($_uid, $webp_file);   // 记录上传文件日志

            // 转换png格式
            $png_file = str_replace('.' . $_files->extension(), '.png', $save_file);
            $image->save(app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . $png_file, 'png');
            $this->write($_uid, $png_file);    // 记录上传文件日志
            unset($image);

            // 删除非png格式图片
            if ('png' !== $_files->extension()) {
                unlink(app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . $save_file);
            }
            $save_file = $png_file;
        }

        return [
            // 'extension'    => $_files->extension(),
            'extension'    => pathinfo($save_file, PATHINFO_EXTENSION),
            'name'         => pathinfo($save_file, PATHINFO_BASENAME),
            // 'old_name'     => $_files->getOriginalName(),
            'original_url' => $save_file,
            // 'size'         => $_files->getSize(),
            // 'type'         => $_files->getMime(),
            'url'          => Config::get('app.cdn_host') . $save_file,
        ];
    }

    /**
     * 记录上传文件
     * @access private
     * @param  int    $_uid
     * @param  string $_file 文件
     * @return void
     */
    private function write(int $_uid, string $_file): void
    {
        $data = is_file($this->uploadLogFile) ? include $this->uploadLogFile : '';

        if ($fp = @fopen($this->uploadLogFile, 'w+')) {
            if (flock($fp, LOCK_EX | LOCK_NB)) {
                $data = !empty($data) ? (array) $data : [];
                $data[] = $_file;
                $data = '<?php /*uid:' . $_uid . '*/ return ' . var_export($data, true) . ';';
                fwrite($fp, $data);
                flock($fp, LOCK_UN);
            }
            fclose($fp);
        }
    }
}
