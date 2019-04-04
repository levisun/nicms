layui.config({
    dir: '/static/layui/' //layui.js 所在路径
}).extend({
    book: '{/}' + NICMS.cdn.static + 'layui.book', // {/}的意思即代表采用自有路径，即不跟随 base 路径
});
layui.define(['jquery', 'book'], function(exports){
    var jq = layui.jquery;
    var book = layui.book;


    // book.category();

    // book.book_list({
    //     gender: 'male',
    //     type: 'reputation',
    //     major: '同人',
    //     minor: '动漫同人',
    //     start: 0,
    // });

    // book.book_info({
    //     id: '562a1926abbb9df8402352e7'
    // });

    // book.book_index({
    //     id: '562a1926abbb9df8402352e7'
    // });

    // book.book_details({
    //     id: 'http://chuangshi.qq.com/bk/2cy/AG8EN11iVjcAO1Rt-r-1.html'
    // });

});
