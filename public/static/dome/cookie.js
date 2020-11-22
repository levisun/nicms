class cookie {

    set(name, value, expire = 1, domain = '') {
        let d = new Date();
        d.setTime(d.getTime() + (expire * 24 * 60 * 60 * 1000));
        domain = domain ? domain : '.' + window.location.host.substr(window.location.host.indexOf('.') + 1);
        document.cookie = name + '=' + value + ';expires=' + d.toUTCString() + ';path=/;SameSite=lax;domain=' + domain;

    }

    get(name) {
        name += '=';
        let decodedCookie = decodeURIComponent(document.cookie);
        let ca = decodedCookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) == ' ') {
                c = c.substring(1);
            }
            if (c.indexOf(name) == 0) {
                return c.substring(name.length, c.length);
            }
        }
        return '';
    }

    remove(name) {
        domain = domain ? domain : "." + window.location.host.substr(window.location.host.indexOf(".") + 1);
        document.cookie = name + '=null;expires=-1440;path=/;SameSite=lax;domain=' + domain;
    }
}
