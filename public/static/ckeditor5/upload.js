function MyCustomUploadAdapterPlugin(editor) {
    editor.plugins.get('FileRepository').createUploadAdapter = (loader) => {
        // Configure the URL to the upload script in your back-end here!
        return new MyUploadAdapter(loader);

    };
}

class MyUploadAdapter {
    constructor(loader) {
        this.loader = loader;
    }

    upload() {
        return this.loader.file
            .then(file => new Promise((resolve, reject) => {
                this._initRequest();
                this._initListeners(resolve, reject, file);
                this._sendRequest(file);
            }));
    }

    // Aborts the upload process.
    abort() {
        if (this.xhr) {
            this.xhr.abort();
        }
    }

    // Initializes the XMLHttpRequest object using the URL passed to the constructor.
    _initRequest() {
        const xhr = this.xhr = new XMLHttpRequest();

        // Note that your request may look different. It is up to you and your editor
        // integration to choose the right communication channel. This example uses
        // a POST request with JSON as a data structure but your configuration
        // could be different.
        // xhr.open('POST', 'http://example.com/image/upload/path', true);
        xhr.open('POST', NICMS.api_uri + '/upload.do', true);
        let root = window.location.host.substr(window.location.host.indexOf(".")+1);
            root = root.substr(0, root.indexOf("."));
        xhr.setRequestHeader('Accept', 'application/vnd.' + root + '.v' + jQuery('meta[name="csrf-version"]').attr('content') + '+json');
        xhr.setRequestHeader('Authorization', 'Bearer ' + window.atob(jQuery.get_cookie('XSRF_AUTHORIZATION')));
        xhr.responseType = 'json';
    }

    // Initializes XMLHttpRequest listeners.
    _initListeners(resolve, reject, file) {
        const xhr = this.xhr;
        const loader = this.loader;
        const genericErrorText = `Couldn't upload file: ${file.name}.`;

        xhr.addEventListener('error', () => reject(genericErrorText));
        xhr.addEventListener('abort', () => reject());
        xhr.addEventListener('load', () => {
            const response = xhr.response;

            // This example assumes the XHR server's "response" object will come with
            // an "error" which has its own "message" that can be passed to reject()
            // in the upload promise.
            //
            // Your integration may handle upload errors in a different way so make sure
            // it is done properly. The reject() function must be called when the upload fails.
            if (!response || response.error) {
                return reject(response && response.error ? response.error.message : genericErrorText);
            }

            if (!response && response.token) {
                jQuery.set_cookie('CSRF_TOKEN', response.token);
            }

            // If the upload is successful, resolve the upload promise with an object containing
            // at least the "default" URL, pointing to the image on the server.
            // This URL will be used to display the image in the content. Learn more in the
            // UploadAdapter#upload documentation.
            resolve({
                default: response.data.url
            });
        });

        // Upload progress when it is supported. The file loader has the #uploadTotal and #uploaded
        // properties which are used e.g. to display the upload progress bar in the editor
        // user interface.
        if (xhr.upload) {
            xhr.upload.addEventListener('progress', evt => {
                if (evt.lengthComputable) {
                    loader.uploadTotal = evt.total;
                    loader.uploaded = evt.loaded;
                }
            });
        }
    }

    // Prepares the data and sends the request.
    _sendRequest(file) {
        // Prepare the form data.
        let common = [];
        common.push({ name: 'appid', value: jQuery('meta[name="csrf-appid"]').attr('content') });
        common.push({ name: '__token__', value: jQuery.get_cookie('CSRF_TOKEN') });
        common.push({ name: 'method', value: 'content.article.upload' });
        // common.push({ name: 'width', value: 800 });
        // common.push({ name: 'height', value: 800 });
        common.push({ name: 'water', value: 0 });
        common.push({ name: 'typeOption', value: 'upload_image' });

        let from_data = new FormData();
        for (let index in common) {
            const element = common[index];
            from_data.append(element.name, element.value);
        }

        from_data.append('sign', jQuery.sign(common));
        from_data.append('upload', file);

        // Important note: This is the right place to implement security mechanisms
        // like authentication and CSRF protection. For instance, you can use
        // XMLHttpRequest.setRequestHeader() to set the request headers containing
        // the CSRF token generated earlier by your application.

        // Send the request.
        this.xhr.send(from_data);
    }
}
