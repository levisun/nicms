<?php

/**
 *
 * 网站地图
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

use think\facade\Request;
use app\common\library\Base64;
use app\common\model\Article as ModelArticle;
use app\common\model\Category as ModelCategory;

class Sitemap
{

    /**
     * 保存Sitemap文件
     * @access public
     * @static
     * @return void
     */
    public static function create(): void
    {
        $sitemap_xml = [];
        $domain = Request::scheme() . '://www.' . Request::rootDomain();
        $article = ModelArticle::view('article', ['id', 'category_id', 'title', 'keywords', 'description', 'access_id', 'update_time'])
            ->view('category', ['name' => 'cat_name'], 'category.id=article.category_id')
            ->view('model', ['name' => 'action_name'], 'model.id=category.model_id')
            ->where([
                ['article.is_pass', '=', '1'],
                ['article.delete_time', '=', 0],
                ['article.show_time', '<', time()],
            ])
            ->order('article.id DESC')
            ->limit(5000)
            ->select()
            ->toArray();
        foreach ($article as $value) {
            $sitemap_xml[]['url'] = [
                'loc'        => $domain . url('details/' . Base64::url62encode($value['category_id']) . '/' . Base64::url62encode($value['id'])),
                'lastmod'    => date('Y-m-d H:i:s', $value['update_time']),
                'changefreq' => 'weekly',
                'priority'   => '0.8',
            ];
        }

        self::saveXml($sitemap_xml, 'sitemap.xml');


        $article = ModelArticle::view('article', ['id', 'category_id', 'title', 'keywords', 'description', 'access_id', 'update_time'])
            ->view('category', ['name' => 'cat_name'], 'category.id=article.category_id')
            ->view('model', ['name' => 'action_name'], 'model.id=category.model_id')
            ->where([
                ['article.delete_time', '<>', 0],
            ])
            ->order('article.id DESC')
            ->limit(5000)
            ->select()
            ->toArray();
        $silian = '';
        foreach ($article as $value) {
            $silian .= $domain . url('details/' . Base64::url62encode($value['category_id']) . '/' . Base64::url62encode($value['id'])) . "\r\n";
        }
        file_put_contents(public_path() . 'silian.txt', $silian);
    }

    /**
     * 保存XML文件
     * @access private
     * @static
     * @param  array  $_data
     * @return void
     */
    private static function saveXml(array &$_data, string $_path): void
    {
        $xml  = '<?xml version="1.0" encoding="UTF-8" ?>' . PHP_EOL;
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
        $xml .= self::toXml($_data) . PHP_EOL;
        $xml .= '</urlset>';

        $filename = public_path() . $_path;
        if (!is_dir(dirname($filename))) {
            mkdir(dirname($filename), 0755, true);
        }
        file_put_contents($filename, $xml);
    }

    /**
     * 数组转XML
     * @access private
     * @static
     * @param  array  $_data
     * @return string
     */
    private static function toXml(array &$_data): string
    {
        $xml = '';
        foreach ($_data as $key => $value) {
            if (is_string($key)) {
                $xml .= '<' . $key . '>';
            }

            $xml .= is_array($value) ? PHP_EOL . self::toXml($value) : $value;

            if (is_string($key)) {
                $xml .= '</' . $key . '>' . PHP_EOL;
            }
        }

        return $xml;
    }
}
