<?php

/**
 *
 * 网站地图
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

use app\common\model\Article as ModelArticle;
use app\common\model\Category as ModelCategory;

class Sitemap
{

    public function create()
    {
        clearstatcache();
        $path = app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . 'sitemap.xml';
        if (!is_file($path) || filemtime($path) < strtotime('-24 hour')) {
            app('log')->record('[SITEMAP] 网站地图', 'alert');

            $category = (new ModelCategory)
                ->view('category', ['id', 'name', 'aliases', 'image', 'is_channel', 'access_id'])
                ->view('model', ['name' => 'action_name'], 'model.id=category.model_id')
                ->view('level', ['name' => 'level_name'], 'level.id=category.access_id', 'LEFT')
                ->where([
                    ['category.is_show', '=', 1],
                    ['category.model_id', 'in', [1, 2, 3]]
                ])
                ->order('category.sort_order ASC, category.id DESC')
                ->select()
                ->toArray();

            $sitemap_xml = [];
            $sub_path = 'storage' . DIRECTORY_SEPARATOR . 'sitemaps' . DIRECTORY_SEPARATOR;
            foreach ($category as $vo_cate) {
                $article = (new ModelArticle)
                    ->view('article', ['id', 'category_id', 'title', 'keywords', 'description', 'access_id', 'update_time'])
                    ->view('category', ['name' => 'cat_name'], 'category.id=article.category_id')
                    ->view('model', ['name' => 'action_name'], 'model.id=category.model_id')
                    ->where([
                        ['article.category_id', '=', $vo_cate['id']],
                        ['article.is_pass', '=', '1'],
                        ['article.show_time', '<=', time()],
                    ])
                    ->order('article.id DESC')
                    ->limit(100)
                    ->select()
                    ->toArray();

                $article_xml = [];
                $category_xml = [];
                foreach ($article as $vo_art) {
                    $article_xml[]['url'] = [
                        'loc'        => app('request')->domain() . url('details/' . $vo_art['action_name'] . '/' . $vo_art['category_id'] . '/' . $vo_art['id']),
                        'lastmod'    => $vo_art['update_time'],
                        'changefreq' => 'weekly',
                        'priority'   => '0.8',
                    ];

                    $category_xml[]['url'] = [
                        'loc'        => app('request')->domain() . url('list/' . $vo_cate['action_name'] . '/' . $vo_cate['id']),
                        'lastmod'    => $vo_art['update_time'],
                        'changefreq' => 'daily',
                        'priority'   => '1.0',
                    ];
                }
                if ($article_xml) {
                    self::save($article_xml, $sub_path . 'details-' . $vo_cate['action_name'] . '-' . $vo_cate['id'] . '.xml');
                    self::save($category_xml, $sub_path . 'list-' . $vo_cate['action_name'] . '-' . $vo_cate['id'] . '.xml');

                    $sitemap_xml[]['sitemap'] = [
                        'loc'     => app('request')->domain() . '/storage/sitemaps/details-' . $vo_cate['action_name'] . '-' . $vo_cate['id'] . '.xml',
                        'lastmod' => date('Y-m-d H:i:s')
                    ];
                    $sitemap_xml[]['sitemap'] = [
                        'loc'     => app('request')->domain() . '/storage/sitemaps/list-' . $vo_cate['action_name'] . '-' . $vo_cate['id'] . '.xml',
                        'lastmod' => date('Y-m-d H:i:s')
                    ];
                }
            }
            self::save($sitemap_xml, 'sitemap.xml');
        }
        clearstatcache();
    }

    /**
     * 创建XML文件
     * @access private
     * @param  array  $_data
     * @return string
     */
    private function save(array $_data, string $_path): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>' . PHP_EOL .
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
        $xml .= self::toXml($_data) . PHP_EOL;
        $xml .= '</urlset>' . PHP_EOL;
        $xml .= '<!-- generated-on="' . date('Y-m-d H:i:s') . '" -->';

        $filename = app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . $_path;
        if (!is_dir(dirname($filename))) {
            mkdir(dirname($filename), 0755, true);
        }
        file_put_contents($filename, $xml);
    }

    /**
     * 数组转XML
     * @access private
     * @param  array  $_data
     * @return string
     */
    private function toXml(array $_data): string
    {
        $xml = '';
        foreach ($_data as $key => $value) {
            if (is_string($key)) {
                $xml .= '<' . $key . '>';
            }

            if (is_array($value)) {
                $xml .= PHP_EOL . self::toXml($value) . PHP_EOL;
            } else {
                $xml .= $value;
            }

            if (is_string($key)) {
                $xml .= '</' . $key . '>' . PHP_EOL;
            }
        }

        return trim($xml, PHP_EOL);
    }
}