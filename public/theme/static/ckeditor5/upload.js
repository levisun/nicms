function MyCustomUploadAdapterPlugin(editor) {
    editor.plugins.get('FileRepository').createUploadAdapter = (loader) => {
        // Configure the URL to the upload script in your back-end here!
        return new MyUploadAdapter(loader);
    };
}

class MyUploadAdapter {
    constructor(loader) {
        // Save Loader instance to update upload progress.
        this.loader = loader;
    }

    async upload() {
        var data = new FormData();
        data.append('typeOption', 'upload_image');
        data.append('upload', await this.loader.file);

        data.append('method', 'content.article.upload');
        data.append('timestamp', jQuery.timestamp());
        data.append('appid', jQuery('meta[name="csrf-appid"]').attr('content'));
        data.append('__token__', jQuery('meta[name="csrf-token"]').attr('content'));
        var newData = [];
        var items = data.entries();
        while (item = items.next()) {
            if (item.done) {
                break;
            }
            newData.push({ name: item.value[0], value: item.value[1] });
        }
        data.append('sign', jQuery.sign(newData));

        return new Promise((resolve, reject) => {
            axios({
                url: NICMS.api.url + '/upload.do',
                method: 'post',
                data,
                headers: {
                    'Accept': 'application/vnd.' + jQuery('meta[name="csrf-root"]').attr('content') + '.v' + jQuery('meta[name="csrf-version"]').attr('content') + '+json',
                    'Authorization': jQuery('meta[name="csrf-authorization"]').attr('content')
                },
                withCredentials: true // 此处可删掉，没发现有什么用
            }).then(res => {
                var resData = res.data;
                resData.default = resData.url;
                resolve(resData);
            }).catch(error => {
                reject(error)
            });
        });
    }
}
