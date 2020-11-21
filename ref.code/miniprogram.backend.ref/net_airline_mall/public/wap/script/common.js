//window注入wx对象
const Debug = false;
window.alert = function (name) {
    var iframe = document.createElement("IFRAME");
    iframe.style.display = "none";
    iframe.setAttribute("src", 'data:text/plain,');
    document.documentElement.appendChild(iframe);
    window.frames[0].window.alert(name);
    iframe.parentNode.removeChild(iframe);
};
window.wxapp = {
    ajax: function (url, pageparm = {}, mark = 0, method = "POST") {
        return new Promise(function (resolve, reject) {
            pageparm.token = $api.getStorage('token') ? $api.getStorage('token') : '';
            if (mark && !Debug) {
                layer.open({type: 2});
            }
            $.ajax({
                type: 'POST',
                url: url,
                data: pageparm,
                dataType: 'json',
                timeout: 30000,
                // async: true,
                // cache: false,
                // context: $('body'),
                success: function (ret) {
                    if (mark && !Debug) {
                        layer.closeAll();
                    }
                    resolve(ret);
                },
                error: function (xhr, type) {
                    alert(xhr.responseJSON);
                }
            });
        });
    },
    alert: function (type, title) {
        dialog(title);
    },
    fillzero: function (num, length = 2) {
        return (num / Math.pow(10, length)).toFixed(length).substr(2);
    },
    addege: function (a) {
        return a < 10 ? a = "0" + a : a = a;
    },
    timedown: function (totalsecond) {
        var totalsecond = totalsecond;
        var day = parseInt(totalsecond / 86400);
        var time = parseInt((totalsecond - (day * 86400)) / 3600);
        var min = parseInt((totalsecond - (time * 3600 + day * 86400)) / 60);
        var sinTime = time * 3600 + min * 60 + day * 86400;
        var sin1 = parseInt((totalsecond - sinTime));
        var timeArr = wxapp.addege(time) + ':' + wxapp.addege(min) + ':' + wxapp.addege(sin1);
        if (totalsecond <= 0) {
            timeArr = '00:00:00';
        }
        return timeArr;
    }
};

function getUrlPageparm() {
    var query = window.location.search.substring(1);
    if (query && query !== 'undefined') {
        var startindex = query.lastIndexOf("=") + 1;
        var pageparm = JSON.parse(decodeURI(query.substring(startindex)));
        return pageparm;
    } else {
        return {};
    }
}

function is_miniprogram() {
    return window.WeixinJSBridge && WeixinJSBridge.invoke;
}

window.api = {
    openWin: function (arg = {}) {
        var startindex = arg.url.lastIndexOf("/") + 1;
        var endindex = arg.url.indexOf("_");
        var act = arg.url.substring(startindex, endindex);

        if (arg.pageParam) {
            var jumpurl = '/wap/index/' + act + '?pageParam=' + encodeURI(JSON.stringify(arg.pageParam));
        } else {
            var jumpurl = '/wap/index/' + act;
        }
        window.location.href = jumpurl;
    },
    pageParam: getUrlPageparm(),
};

function dialog(title, duration) {
    let msgObj = document.createElement("div");
    let posx = window.innerWidth / 2;
    let posy = window.innerHeight / 2;
    let divId = 'msgDiv' + new Date().getTime();
    let exist = document.querySelector('div[id^="msgDiv1"]');
    if (exist) {
        return false;
    }
    msgObj.setAttribute("id", divId);
    msgObj.style.position = "absolute";
    msgObj.style.left = (posx - 75) + "px";
    msgObj.style.top = (posy + 40) + "px";
    msgObj.style.font = "12px/1.6em Verdana, Geneva, Arial, Helvetica, sans-serif";
    msgObj.style.color = '#fff';
    msgObj.style.textAlign = "center";
    msgObj.style.lineHeight = "25px";
    msgObj.style.zIndex = "9999";
    msgObj.style.padding = "12px 25px";
    msgObj.style.minWidth = "100px";
    msgObj.style.background = "rgba(0,0,0,.6)";
    msgObj.innerText = title;
    document.body.appendChild(msgObj);
    setTimeout(function () {
        document.body.removeChild(msgObj);
    }, duration ? parseInt(duration) : 2000);
}

function open_meiQia() {
    (function (m, ei, q, i, a, j, s) {
        m[i] = m[i] || function () {
            (m[i].a = m[i].a || []).push(arguments);
        };
        j = ei.createElement(q),
            s = ei.getElementsByTagName(q)[0];
        j.async = true;
        j.charset = 'UTF-8';
        j.src = 'https://static.meiqia.com/dist/meiqia.js?_=t';
        s.parentNode.insertBefore(j, s);
    })(window, document, 'script', '_MEIQIA');
    _MEIQIA('entId', '95397');
    _MEIQIA('withoutBtn');
    // 初始化成功后调用美洽 showPanel
    _MEIQIA('allSet', function () {
        _MEIQIA('showPanel');
    });
    return false;
    var mq = api.require('meiQia');//配置初始化美洽需要的appkey
    var param = {
        appkey: "25088206e220311a2860f0f916a7d4ec"//初始化美洽
    };
    mq.initMeiQia(param, function (ret, err) {
        if (ret) {
            //初始化成功
            mq.setTitleColor({
                color: "#4A4A4A"
            });
            mq.setTitleBarColor({
                color: "#ffffff"//设置标题栏背景颜色
            });
            mq.show();
        } else {
            console.log(JSON.stringify(err));
        }
    });
}

function longinwin() {
    api.openWin({
        name: 'login_win',
        url: 'widget://html/login_win.html',
    });
}

function checkLogin() {
    if (!$api.getStorage('token')) {
        api.openWin({
            name: 'login_win',
            url: 'widget://html/login_win.html',
        });
    }
}


function closeWin() {
    window.history.back();
}


function openwind(windir, winname, auth) {
    if (auth == 1) {
        if (!$api.getStorage('token')) {
            api.openWin({
                name: 'login_win',
                url: 'widget://html/login_win.html',
            });
            return false;
        }
    }
    if (windir) {
        api.openWin({
            name: winname,
            url: 'widget://html/' + windir + '/' + winname + '.html',
        });
    } else {
        api.openWin({
            name: winname,
            url: 'widget://html/' + winname + '.html',
        });
    }
}


if ('addEventListener' in document) {
    document.addEventListener('DOMContentLoaded', function () {
        FastClick.attach(document.body);
    }, false);
}

// 拦截Android的返回键，在首页点击返回键，提示退出应用
function initEventListenner() {
    api.addEventListener({
        name: 'keyback'
    }, function (ret, err) {
        api.confirm({
            title: '',
            msg: '是否退出应用?',
            buttons: ['确定', '取消']
        }, function (ret, err) {
            if (ret.buttonIndex == 1) {
                api.closeWidget({
                    silent: true //直接退出，无需提示
                });
            }
        });
    });
}

// 兼容vue初始化click事件处理
function compatibility() {
    FastClick.prototype.focus = function (targetElement) {
        targetElement.focus();
    };
}

if ('addEventListener' in document) {
    document.addEventListener('DOMContentLoaded', function () {
        FastClick.attach(document.body);
    }, false);
}

function inputFocus() {
    setTimeout(function () {
        var ios = document.getElementsByTagName("input");
        for (var i = 0; i < ios.length; i++) {
            ios[i].onclick = function () {
                this.focus();
            };
        }
    }, 50);
}

function wapAdLink(link) {
    if (0 < link.indexOf('=')) {
        let data = link.split('=');
        switch (data[0]) {
            case 'venue':
                if (vm.miniapp == 1) {
                    wx.miniProgram.navigateTo({
                        url: '/pages/venue/index?venue_id=' + data[1]
                    });
                } else {
                    api.openWin({
                        name: "venue_win",
                        url: "widget://html/venue_win.html",
                        pageParam: {venue_id: data[1]}
                    });
                }
                break;
            case 'goods':
                if ((vm.miniapp == 1)) {
                    wx.miniProgram.navigateTo({
                        url: '/pages/goods/index?goods_id=' + data[1]
                    });
                } else {
                    vm.openGoodsdetail(data[1]);
                }
                break;
            case 'category':
                if ((vm.miniapp == 1)) {
                    wx.miniProgram.navigateTo({
                        url: '/pages/category/list?cat_id=' + data[1]
                    });
                } else {
                    api.openWin({
                        name: "category",
                        url: "widget://html/goodslist.html",
                        pageParam: {cat_id: data[1]}
                    });
                }
                break;
            case 'store':
                if ((vm.miniapp == 1)) {
                    //TODO 小程序专用页面
                    api.openWin({
                        name: 'storeindex_win.html',
                        url: 'widget://html/storeindex_win.html',
                        pageParam: {
                            store_id: data[1],
                            miniapp: vm.miniapp,
                        }
                    });
                } else {
                    api.openWin({
                        name: 'storeindex_win.html',
                        url: 'widget://html/storeindex_win.html',
                        pageParam: {
                            store_id: data[1],
                            miniapp: vm.miniapp,
                        }
                    });
                }
                break;
        }
    }
}

const appkey = 'b132212558baf7db66671d8cc490f629';
var base_url = '';
if (Debug) {
    base_url = 'http://192.168.3.45:8005/';
} else {
    base_url = 'https://www.enongmall.com/';
}
const config = {
    'alioss_upload': base_url + "admin/uploader/alioss_upload",
    'rechargejspay': base_url + "wap/index/rechargejspay",
    'Login': base_url + "api/apicloud/Login",
    'verify': base_url + "api/apicloud/verify",
    'getconfig': base_url + "api/apicloud/getconfig",
    'dayuSms': base_url + "api/apicloud/dayuSms",
    'register': base_url + "api/apicloud/register",
    'getChildrenData': base_url + "api/apicloud/getChildrenData",
    'catgoodslist': base_url + "api/apicloud/catgoodslist",
    'getuserinfo': base_url + "api/apicloud/getuserinfo",
    'address': base_url + "api/apicloud/address",
    'addrdefault': base_url + "api/apicloud/addrdefault",
    'deladdress': base_url + "api/apicloud/deladdress",
    'addressdetail': base_url + "api/apicloud/addressdetail",
    'regionList': base_url + "api/apicloud/regionList",
    'addressrupdate': base_url + "api/apicloud/addressrupdate",
    'goodsdetail': base_url + "api/apicloud/goodsdetail",
    'cartList': base_url + "api/apicloud/cartList",
    'addCart': base_url + "api/apicloud/addCart",
    'cartUpdate': base_url + "api/apicloud/cartUpdate",
    'collectupdate': base_url + "api/apicloud/collectupdate",
    'getcartGoodsNumber': base_url + "api/apicloud/getcartGoodsNumber",
    'confirmOrder': base_url + "api/apicloud/confirmOrder",
    'createOrder': base_url + "api/apicloud/createOrder",
    'orderDetail': base_url + "api/apicloud/orderDetail",
    'orderlist': base_url + "api/apicloud/orderlist",
    'orderinfo': base_url + "api/apicloud/orderinfo",
    'favorite': base_url + "api/apicloud/favorite",
    'goodscollectDelete': base_url + "api/apicloud/goodscollectDelete",
    'favstore': base_url + "api/apicloud/favstore",
    'storecollectDelete': base_url + "api/apicloud/storecollectDelete",
    'goodsclick': base_url + "api/apicloud/goodsclick",
    'goodsclickDelete': base_url + "api/apicloud/goodsclickDelete",
    'profile': base_url + "api/apicloud/profile",
    'pay': base_url + "api/apicloud/pay",
    'exchange': base_url + "api/apicloud/exchange",
    'exorder': base_url + "api/apicloud/exorder",
    'storeindex': base_url + "api/apicloud/storeindex",
    'storelists': base_url + "api/apicloud/storelists",
    'storegoods': base_url + "api/apicloud/storegoods",
    'storecatelist': base_url + "api/apicloud/storecatelist",
    'skgoods': base_url + "api/apicloud/skgoods",
    'skdetail': base_url + "api/apicloud/skdetail",
    'paimaigoods': base_url + "api/apicloud/paimaigoods",
    'pmdetail': base_url + "api/apicloud/pmdetail",
    'createHouse': base_url + "api/apicloud/createHouse",
    'housepeople': base_url + "api/apicloud/housepeople",
    'houseinfo': base_url + "api/apicloud/houseinfo",
    'houseleave': base_url + "api/apicloud/houseleave",
    'rushlogadd': base_url + "api/apicloud/rushlogadd",
    'indexapp': base_url + "api/apicloud/indexapp",
    'brand': base_url + "api/apicloud/brand",
    'hotkeywordadd': base_url + "api/apicloud/hotkeywordadd",
    'hotkeyworddel': base_url + "api/apicloud/hotkeyworddel",
    'orderpailist': base_url + "api/apicloud/orderpailist",
    'mycoupon': base_url + "api/apicloud/mycoupon",
    'smslogin': base_url + "api/apicloud/smslogin",
    'jlistInit': base_url + "api/apicloud/jlistInit",
    'orderupaddress': base_url + "api/apicloud/orderupaddress",
    'article': base_url + "api/apicloud/article",
    'updatepaypwd': base_url + "api/apicloud/updatepaypwd",
    'updatecard': base_url + "api/apicloud/updatecard",
    'rechargepay': base_url + "api/apicloud/rechargepay",
    'rechargejsapi': base_url + "wap/index/rechargejsapi",
    'rechargelog': base_url + "api/apicloud/rechargelog",
    'xiaxiang': base_url + "api/apicloud/xiaxiang",
    'jincheng': base_url + "api/apicloud/jincheng",
    'addjoinus': base_url + "api/apicloud/addjoinus",
    'updatepwd': base_url + "api/apicloud/updatepwd",
    'orderCancel': base_url + "api/apicloud/orderCancel",
    'giftcardBindList': base_url + "api/apicloud/giftcardBindList",
    'giftcardBindUser': base_url + "api/apicloud/giftcardBindUser",
    'giftcardlogList': base_url + "api/apicloud/giftcardlogList",
    'giftcardBuyList': base_url + "api/apicloud/giftcardBuyList",
    'giftcardBond': base_url + "api/apicloud/giftcardBond",
    'buycardpay': base_url + "api/apicloud/buycardpay",
    'venue': base_url + "api/apicloud/venue",

};

window.onload = function () {
    if (!$api.getStorage('token')) {
        //window.location.href = base_url + "wap/index/weixinlogin";
    }
};
