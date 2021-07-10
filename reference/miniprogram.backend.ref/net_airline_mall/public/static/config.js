/**
 * Created by PhpStorm.
 * User: 835173372@qq.com
 * NickName: 半城村落
 * Date: 2018/12/3 0003 14:07
 */

//var base_url = 'https://shangxing.17zhongke.com/app/';
var base_url = '/';

var dayuSmsUrl = base_url + 'home/login/dayuSms';
var registerUrl = base_url + 'home/login/register';
var indexUrl = base_url + 'home/login/index';
var loginoutUrl = base_url + 'member/index/loginout';
var certificationUrl = base_url + 'member/index/certification';


//记住密码
function rememberMe() {
    var storage = window.localStorage;
    if ($("#remb_me").is(':checked')) {
        //存储到loaclStage
        storage["loginphone"] = $("#loginphone").val();
        storage["loginpassword"] = $("#loginpassword").val();
        storage["isstorename"] = "yes";
    } else {
        storage["loginphone"] = "";
        storage["loginpassword"] = "";
        storage["isstorename"] = "no";
    }
}

function loginout() {
    layer.confirm('确认操作？', {
        btn: ['确定', '取消'] //按钮
    }, function () {
        $.ajax({
            type: 'POST',
            url: loginoutUrl,
            dataType: 'json',
            timeout: 30000,
            async: true,
            cache: false,
            context: $('body'),
            success: function (ret) {
                layer.msg(ret.msg, {icon: 1});
                setTimeout(function () {
                    window.location.href = indexUrl;
                }, 1000);
            }
        })
    });
}

function ueditorInit(content) {
    $(document).ready(function () {
        UE.Editor.prototype._bkGetActionUrl = UE.Editor.prototype.getActionUrl;
        UE.Editor.prototype.getActionUrl = function (action) {
            if (action == 'uploadimage' || action == 'uploadscrawl' || action == 'uploadimage' || action === 'uploadvideo') {
                return '/member/uploader/alioss_upload';
            } else {
                return this._bkGetActionUrl.call(this, action);
            }
        };
        var ue = UE.getEditor(content, {
            toolbars: [
                [
                    'source', //源代码
                    'bold', //加粗
                    'indent', //首行缩进
                    'pasteplain', //纯文本粘贴模式
                    'horizontal', //分隔线
                    'removeformat', //清除格式
                    'insertcode', //代码语言
                    'fontfamily', //字体
                    'fontsize', //字号
                    'paragraph', //段落格式
                    'map', //Baidu地图
                    'justifyleft', //居左对齐
                    'justifyright', //居右对齐
                    'justifycenter', //居中对齐
                    'justifyjustify', //两端对齐
                    'forecolor', //字体颜色
                    'lineheight', //行间距
                    'simpleupload', //单图上传
                ]
            ],
            autoHeightEnabled: true,
            autoFloatEnabled: true,
            initialFrameWidth: 650,
        });
    });
}

//签到
function signbookadd() {
    $.ajax({
        type: 'POST',
        url: '/member/index/signbookadd',
        dataType: 'json',
        timeout: 30000,
        async: true,
        cache: false,
        context: $('body'),
        success: function (ret) {
            if (ret.status == 1) {
                layer.msg(ret.msg, {icon: 6});
                setTimeout(function () {
                    window.location.reload();
                }, 500);
            } else {
                layer.msg(ret.msg, {icon: 5});
            }
        }
    })
}

//ueditor初始化开始
function get_editor_content(content) {
    var arr = [];
    arr.push(UE.getEditor(content).getContent());
    return arr.join("\n");
}