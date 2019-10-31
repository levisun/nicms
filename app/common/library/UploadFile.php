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
     * 校验上传文件
     * @access public
     * @param  string      $_element input元素名
     * @param  \think\File $_files   文件
     * @return bool|string
     */
    public function validate(string $_element, \think\File &$_files)
    {
        $size = (int) Config::get('app.upload_size', 1) * 1048576;
        $ext = Config::get('app.upload_type', 'doc,docx,gif,gz,jpeg,mp4,pdf,png,ppt,pptx,rar,xls,xlsx,zip');
        $mime = [
            'doc'  => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'gif'  => 'image/gif',
            'gz'   => 'application/x-gzip',
            'jpeg' => 'image/jpeg',
            'mp4'  => 'video/mp4',
            'pdf'  => 'application/pdf',
            'png'  => 'image/png',
            'ppt'  => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'rar'  => 'application/octet-stream',
            'xls'  => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'zip'  => 'application/zip'
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
     * @access public
     * @param  int         $_uid   用户ID
     * @param  \think\File $_files 文件
     * @return array
     */
    public function save(int $_uid, \think\File &$_files): array
    {
        $_dir = $_uid
            ? '/u' . dechex($_uid) . '/' . dechex(date('Ym'))
            : '/t' . dechex(date('Ym'));

        $save_path = Config::get('filesystem.disks.public.url') . '/';
        $save_file = $save_path . Filesystem::disk('public')->putFile('uploads' . $_dir, $_files, 'uniqid');

        if (false !== strpos($_files->getMime(), 'image/')) {
            $image = Image::open(app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . $save_file);
            // 图片最大尺寸
            if ($image->width() >= 800) {
                $image->thumb(800, 800, Image::THUMB_SCALING);
            }
            // 转换图片格式
            $webp_file = str_replace('.' . $_files->extension(), '.webp', $save_file);
            $image->save(app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . $webp_file, 'webp');
            unset($image);
            unlink(app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . $save_file);
            $save_file = $webp_file;
            unset($webp_file);
        }

        $this->write($_uid, $save_file);

        return [
            'extension'    => $_files->extension(),
            'name'         => pathinfo($save_file, PATHINFO_BASENAME),
            'old_name'     => $_files->getOriginalName(),
            'original_url' => $save_file,
            'size'         => $_files->getSize(),
            'type'         => $_files->getMime(),
            'url'          => Config::get('app.cdn_host') . $save_file,
        ];
    }

    public function remove(int $_uid, string $_sql): void
    {
        trace($_uid . $_sql, 'alert');
        $path = app()->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR .
            'temp' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;


        $temp_file_name = $path . md5('upload_file_log' . $_uid) . '.php';
        $data = is_file($temp_file_name) ? include $temp_file_name : '';

        if ($fp = @fopen($temp_file_name, 'w+')) {
            if (flock($fp, LOCK_EX | LOCK_NB)) {
                $data = !empty($data) ? (array) $data : [];
                foreach ($data as $key => $value) {
                    if (false !== strpos($value, $_sql)) {
                        unset($data[$key]);
                    }
                }

                $upload_path = app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR;
                foreach ($data as $key => $value) {
                    $value = str_replace('/', DIRECTORY_SEPARATOR, trim($value, '/'));
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
        $path = app()->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR .
            'temp' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . '*';

        $upload_path = app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR;

        $day = strtotime('-1 days');
        $dir = (array) glob($path);
        foreach ($dir as $files) {
            if (is_file($files) && filemtime($files) <= $day) {
                $data = include $files;
                $data = !empty($data) ? (array) $data : [];
                if ($fp = @fopen($files, 'w+')) {
                    if (flock($fp, LOCK_EX | LOCK_NB)) {
                        foreach ($data as $key => $value) {
                            $value = str_replace('/', DIRECTORY_SEPARATOR, trim($value, '/'));
                            if (is_file($upload_path . $value)) {
                                Log::record('[删除上传垃圾] ' . $value, 'alert');
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
                        }

                        $data = '<?php /* remove */ return ' . var_export($data, true) . ';';

                        fwrite($fp, $data);
                        flock($fp, LOCK_UN);
                    }
                    fclose($fp);
                }
            }
        }
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
        $path = app()->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR .
            'temp' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
        is_dir($path) or mkdir($path, 0755, true);

        $temp_file_name = $path . md5('upload_file_log' . date('Ymd') . $_uid) . '.php';
        $data = is_file($temp_file_name) ? include $temp_file_name : '';

        if ($fp = @fopen($temp_file_name, 'w+')) {
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
