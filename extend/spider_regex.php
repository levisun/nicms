<?php
$siteRegex = [
    'zhidao.baidu.com' => 'div.line.content>div',
    'iask.sina.com.cn' => 'div.list-text-con>pre',
    'www.zhongkao.com' => 'div.content>p',
    'www.sohu.com' => 'article.article',
];
$filterRegex = [
    // 版权
    '/[^<>]*(©|copyright|&copy;)+[^<>]+/i',
    '/[^<>]*公网安备[^<>]+/u',
    '/[^<>]*版权所有[^<>]*/u',
    '/[^<>]*微信支付[^<>]*/u',
    '/[^<>]*扫码支付[^<>]*/u',

    '/[^<>]*备案号[^<>]+/u',
    '/[^<>]*许可证[^<>]+/u',
    '/[^<>]*支付宝[^<>]*/u',
    '/[^<>]*ICP备[^<>]*/ui',

    '/[^<>]*版权[^<>]*/u',
    '/[^<>]*支付[^<>]*/u',
    '/[^<>]*商户[^<>]*/u',
    '/[^<>]*订单[^<>]*/u',


    '/[^<>]*你对这个回答的评价是[^<>]*/u',



    '/[^<>]*内容来自用户[^<>]*/u',

    '/[^<>]*高考关键词[^<>]*/u',
    '/[^<>]*相关好文章[^<>]*/u',


    '/[^<>]*点击查看[^<>]*/u',
    '/[^<>]*微信支付[^<>]*/u',
    '/[^<>]*扫码支付[^<>]*/u',
    '/[^<>]*推荐阅读[^<>]*/u',
    '/[^<>]*展开全部[^<>]*/u',
    '/[^<>]*猜你喜欢[^<>]*/u',
    '/[^<>]*内容来源[^<>]*/u',
    '/[^<>]*责任编写[^<>]*/u',
    '/[^<>]*责任编辑[^<>]*/u',
    '/[^<>]*精华文章[^<>]*/u',
    '/[^<>]*相关文章[^<>]*/u',
    '/[^<>]*雅方教育[^<>]*/u',
    '/[^<>]*互动评论[^<>]*/u',
    '/[^<>]*互动交流[^<>]*/u',
    '/[^<>]*热点专题[^<>]*/u',
    '/[^<>]*精品推荐[^<>]*/u',
    '/[^<>]*网站版权[^<>]*/u',
    '/[^<>]*免责声明[^<>]*/u',
    '/[^<>]*最新文章[^<>]*/u',
    '/[^<>]*今日精品[^<>]*/u',
    '/[^<>]*使用说明[^<>]*/u',
    '/[^<>]*新浪微博[^<>]*/u',
    '/[^<>]*小编推荐[^<>]*/u',
    '/[^<>]*抢手推荐[^<>]*/u',
    '/[^<>]*网站地图[^<>]*/u',
    '/[^<>]*联系我们[^<>]*/u',
    '/[^<>]*关于我们[^<>]*/u',
    '/[^<>]*招聘信息[^<>]*/u',
    '/[^<>]*违法内容[^<>]*/u',
    '/[^<>]*目前位置[^<>]*/u',
    '/[^<>]*详细情况[^<>]*/u',
    '/[^<>]*在线客服[^<>]*/u',
    '/[^<>]*加盟热线[^<>]*/u',



    '/[^<>]*公众号[^<>]*/u',
    '/[^<>]*中考网[^<>]*/u',
    '/[^<>]*支付宝[^<>]*/u',
    '/[^<>]*已赞过[^<>]*/u',
    '/[^<>]*已踩过[^<>]*/u',
    '/[^<>]*初三网[^<>]*/u',
    '/[^<>]*学习啦[^<>]*/u',
    '/[^<>]*三好网[^<>]*/u',
    '/[^<>]*优惠券[^<>]*/u',
    '/[^<>]*管理员[^<>]*/u',
    '/[^<>]*原标题[^<>]*/u',
    '/[^<>]*上一篇[^<>]*/u',
    '/[^<>]*下一篇[^<>]*/u',
    '/[^<>]*身份证[^<>]*/u',
    '/[^<>]*结婚证[^<>]*/u',



    '/[^<>]*微信[^<>]*/u',
    '/[^<>]*顶部[^<>]*/u',
    '/[^<>]*首页[^<>]*/u',
    '/[^<>]*笔者[^<>]*/u',
    '/[^<>]*百度[^<>]*/u',
    '/[^<>]*网编[^<>]*/u',
    '/[^<>]*关注[^<>]*/u',
    '/[^<>]*转载[^<>]*/u',
    '/[^<>]*择要[^<>]*/u',
    '/[^<>]*支付[^<>]*/u',
    '/[^<>]*商户[^<>]*/u',
    '/[^<>]*订单[^<>]*/u',
    '/[^<>]*钉钉[^<>]*/u',
    '/[^<>]*评论[^<>]*/u',
    '/[^<>]*收起[^<>]*/u',
    '/[^<>]*来源[^<>]*/u',
    '/[^<>]*追问[^<>]*/u',
    '/[^<>]*追答[^<>]*/u',
    '/[^<>]*收藏[^<>]*/u',
    '/[^<>]*分享[^<>]*/u',
    '/[^<>]*点赞[^<>]*/u',
    '/[^<>]*电话[^<>]*/u',
    '/[^<>]*回复[^<>]*/u',
    '/[^<>]*点击[^<>]*/u',
    '/[^<>]*浏览[^<>]*/u',
    '/[^<>]*论坛[^<>]*/u',
    '/[^<>]*标签[^<>]*/u',
    '/[^<>]*资讯[^<>]*/u',
    '/[^<>]*下载[^<>]*/u',
    '/[^<>]*声明[^<>]*/u',
    '/[^<>]*侵权[^<>]*/u',
    '/[^<>]*源于[^<>]*/u',
    '/[^<>]*来自[^<>]*/u',
    '/[^<>]*推荐[^<>]*/u',
    '/[^<>]*发布[^<>]*/u',
    '/[^<>]*反馈[^<>]*/u',
    '/[^<>]*热线[^<>]*/u',
    '/[^<>]*地址[^<>]*/u',
    '/[^<>]*律师[^<>]*/u',
    '/[^<>]*文\/[^<>]*/u',
    '/[^<>]*VIP[^<>]*/u',

    '/(&nbsp;){2,}/i',
    '/[\- ]{2,}/i',
    '/<img>/i',


    // 百度知道
    '/<span[^<>]*>[\d\w]{10,}<\/span>/si',
    '/<span[^<>]*class="[\d\w]{10,}">[\d\w]{2,}<\/span>/si',
    '/<span[^<>]*class="[\d\w]{10,}">bai<\/span>/si',
    '/<span[^<>]*class="[\d\w]{10,}">du<\/span>/si',
    '/<span[^<>]*class="[\d\w]{10,}">zhi<\/span>/si',
    '/<span[^<>]*class="[\d\w]{10,}">dao<\/span>/si',

    '/<[\w]+[^<>]*hidden[^<>]*>.+<\/[\w]+>/ui',
    '/<[\w]+[^<>]*none[^<>]*>.+<\/[\w]+>/ui',

    '/<p[^<>]*>[\d\.]+<\/p>/si', // 错误信息
    '/<p[^<>]*>.?<\/p>/u',       // 错误信息
    '/<p[^<>]*>(\(\d\).{1})+<\/p>/u',       // 错误信息

    '/[^<>]*QQ[^<>]*/u',
    // '/\d{8,11}/i',
    '/\d{3,4}-\d{7,8}/i',

    '/[^<>]*\d{4}-\d{2}-\d{2}[^<>]*/i',


    '/<[\w]+>(<br *\/*>)*<\/[\w]+>/si', // 空行
    '/<[\w]+>(&nbsp;)+<\/[\w]+>/si',    // 空行
];
