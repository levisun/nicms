let nav = new Vue({
    el: "#layout-header",
    data: {
        nav: {
            "network": {
                "name": "网络",
                "child": {
                    "USER_AGENT": "user_agent.html",
                    "IPV4": "ipv4.html"
                }
            },
            "enc": {
                "name": "编码\/加密",
                "child": {
                    "base64": "base64.html",
                    "unicode": "unicode.html"
                }
            },
            "other": {
                "name": "其他",
                "child": {
                    "regex": "regex.html"
                }
            }
        }
    },
});
