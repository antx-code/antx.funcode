/**
 * Created by WebStorm.
 * User: 835173372@qq.com
 * NickName: GreenArrow
 * Date: 2019/6/6 0206 8:56
 */
const appkey = 'b132212558baf7db66671d8cc490f629';
var base_url;
var Debug = false;
if (Debug) {
  base_url = 'http://127.0.0.1:8005/';
} else {
  base_url = 'https://www.enongmall.com/';
}

function config() {
  let config = {
    'domain': base_url,
    'alioss_upload': base_url + "admin/uploader/alioss_upload",
    'oss_upload': base_url + "api/apicloud/ossSign",
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
    'resetpaypasswd': base_url + "api/apicloud/resetpaypasswd",
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
    'rechargelog': base_url + "api/apicloud/rechargelog",
    'accountlog': base_url + "api/apicloud/accountlog",
    'xiaxiang': base_url + "api/apicloud/xiaxiang",
    'jincheng': base_url + "api/apicloud/jincheng",
    'addjoinus': base_url + "api/apicloud/addjoinus",
    'orderupdate': base_url + "api/apicloud/orderupdate",
    'updatepwd': base_url + "api/apicloud/updatepwd",
    'orderCancel': base_url + "api/apicloud/orderCancel",
    'autoLogin': base_url + "api/apicloud/autoLogin",
    'buycardpay': base_url + "api/apicloud/buycardpay",
    'giftcardBindUser': base_url + "api/apicloud/giftcardBindUser",
    'rechargejspay': base_url + "wap/index/rechargejspay",
    'venue': base_url + "api/apicloud/venue",
    'bindMobile': base_url + "api/apicloud/bindMobile",
    'prom': base_url + "api/apicloud/prom",
    'makePromOrder': base_url + "api/apicloud/makePromOrder",
    'promOrder': base_url + "api/apicloud/promOrder",
    'promOrderList': base_url + "api/apicloud/promOrderList",
    'promOrderDetail': base_url + "api/apicloud/promOrderDetail",
    'queryPromWriteOff': base_url + "api/apicloud/queryPromWriteOff",

    'selfPickupList': base_url + "api/apicloud/selfPickupList",
    'selfPickup': base_url + "api/apicloud/selfPickup",
    'selfPickupDel': base_url + "api/apicloud/selfPickupDel",
    'queryOrderWriteOff': base_url + "api/apicloud/queryOrderWriteOff",
    //2020新增
    'addComment': base_url + "api/apicloud/addComment",
    'goodsComment': base_url + "api/apicloud/goodsComment",
    'getInvoice': base_url + "api/apicloud/getInvoice",
    'groupList': base_url + "api/apicloud/groupList",
    'groupDetail': base_url + "api/apicloud/groupDetail",
    'shippingStatus': base_url + "api/apicloud/shippingStatus",
    'checkUserName': base_url + "api/apicloud/checkUserName",
    'getQrcodeWithGoods': base_url + "api/apicloud/getQrcodeWithGoods",
    'sharelist': base_url + "api/apicloud/sharelist",

    'refund': base_url + "api/apicloud/refund",
    'refundDetail': base_url + "api/apicloud/refundDetail",
    'cancelRefund': base_url + "api/apicloud/cancelRefund",
    'appeal': base_url + "api/apicloud/appeal",
    'refundHistory': base_url + "api/apicloud/refundHistory",
    'refundList': base_url + "api/apicloud/refundList",

    //积分商城webview模块开始
    'exchangeUrl': base_url + "wap/index/exchange",
    'changephUrl': base_url + "wap/index/changeph",
    'exdetailUrl': base_url + "wap/index/exdetail",
    'mypointUrl': base_url + "wap/index/mypoint",
    'mycouponUrl': base_url + "wap/index/mycoupon",
    'favoriteUrl': base_url + "wap/index/favorite",
    'goodsclickUrl': base_url + "wap/index/goodsclick",
    'joinusUrl': base_url + "wap/index/joinus",
    'jinchengUrl': base_url + "wap/index/jincheng",
    'storelistUrl': base_url + "wap/index/storelist",
    'storeindexUrl': base_url + "wap/index/storeindex",
    'moneyinfoUrl': base_url + "wap/index/moneyinfo",
    'giftlistUrl': base_url + "wap/index/giftlist",
    //积分商城webview模块结束

  };
  return config;
}

function getNowTime() {
  var now = new Date();
  var year = now.getFullYear();
  var month = now.getMonth() + 1;
  var day = now.getDate();
  if (month < 10) {
    month = '0' + month;
  }
  if (day < 10) {
    day = '0' + day;
  }
  var formatDate = year + '-' + month + '-' + day;
  return formatDate;
}
module.exports = {
  config: config,
  getNowTime: getNowTime
};