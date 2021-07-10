/**
 * 重写jQuery的ajax，追加loading效果
 */
// (function ($) {
//     var _ajax = $.ajax;
//     $.ajax = function (opt) {
//         //扩展增强处理
//         var _opt = $.extend(opt, {
//             error: function (XHR, TS) {
//                 ajaxLoadClose();
//                 if(XHR.status!==403) {
//                     layer.msg('数据异常');
//                 }else{
//                     pbDialog(XHR.responseJSON, '', 0);
//                 }
//             },
//             beforeSend: function (XHR) {
//                 //showMask();
//             },
//             complete: function (XHR, TS) {
//                 layer.closeAll();
//             }
//         });
//         return _ajax(_opt);
//     };
// })(jQuery);
/**
 *全局定义公共数据变量
 */
var imgtips = "缺少必填项";
var process_request = "<i class='icon-spinner icon-spin'></i>";
var attribute = "属性";
var brandfirstcharList = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
var kuaidiConfig = [{"com": "顺丰", "no": "sf"}, {"com": "申通", "no": "sto"}, {"com": "圆通", "no": "yt"}, {"com": "韵达", "no": "yd"}, {"com": "天天", "no": "tt"}, {"com": "EMS", "no": "ems"}, {"com": "中通", "no": "zto"}, {"com": "汇通", "no": "ht"}, {"com": "全峰", "no": "qf"}, {"com": "德邦", "no": "db"}, {"com": "国通", "no": "gt"}, {"com": "如风达", "no": "rfd"}, {"com": "京东快递", "no": "jd"}, {"com": "宅急送", "no": "zjs"}, {"com": "EMS国际", "no": "emsg"}, {"com": "Fedex国际", "no": "fedex"}, {"com": "邮政国内（挂号信）", "no": "yzgn"}, {"com": "UPS国际快递", "no": "ups"}, {"com": "中铁快运", "no": "ztky"}, {"com": "佳吉快运", "no": "jiaji"}, {"com": "速尔快递", "no": "suer"}, {"com": "信丰物流", "no": "xfwl"}, {"com": "优速快递", "no": "yousu"}, {"com": "中邮物流", "no": "zhongyou"}, {"com": "天地华宇", "no": "tdhy"}, {"com": "安信达快递", "no": "axd"}, {"com": "快捷速递", "no": "kuaijie"}, {"com": "AAE全球专递", "no": "aae"}, {"com": "DHL", "no": "dhl"}, {"com": "DPEX国际快递", "no": "dpex"}, {
    "com": "D速物流",
    "no": "ds"
}, {"com": "FEDEX国内快递", "no": "fedexcn"}, {"com": "OCS", "no": "ocs"}, {"com": "TNT", "no": "tnt"}, {"com": "东方快递", "no": "coe"}, {"com": "传喜物流", "no": "cxwl"}, {"com": "城市100", "no": "cs"}, {"com": "城市之星物流", "no": "cszx"}, {"com": "安捷快递", "no": "aj"}, {"com": "百福东方", "no": "bfdf"}, {"com": "程光快递", "no": "chengguang"}, {"com": "递四方快递", "no": "dsf"}, {"com": "长通物流", "no": "ctwl"}, {"com": "飞豹快递", "no": "feibao"}, {"com": "马来西亚（大包EMS）", "no": "malaysiaems"}, {"com": "安能快递", "no": "ane66"}, {"com": "中通快运", "no": "ztoky"}, {"com": "远成物流", "no": "ycgky"}, {"com": "远成快运", "no": "ycky"}, {"com": "邮政快递", "no": "youzheng"}, {"com": "百世快运", "no": "bsky"}, {"com": "苏宁快递", "no": "suning"}, {"com": "安能物流", "no": "anneng"}];

/**
 * 返回随机数字符串
 */
function rand_str(prefix) {
    var dd = new Date();
    var tt = dd.getTime();
    tt = prefix + tt;
    var rand = Math.random();
    rand = Math.floor(rand * 100);
    return (tt + rand);
}

/**
 * 选择文章分类
 */
$('input[name="cat_name"]').click(function () {
    $(".brand-select-container").hide();
    $(this).parents(".selection").next(".brand-select-container").show();
    $(".brand-list").perfectScrollbar("destroy");
    $(".brand-list").perfectScrollbar();
});


/**
 * Created by PhpStorm.
 * User: 835173372@qq.com
 * NickName: 半城村落
 * Date: 2018/10/31 0031 18:55
 */
function ueditorInit(content, name = 'ue') {
    UE.Editor.prototype._bkGetActionUrl = UE.Editor.prototype.getActionUrl;
    UE.Editor.prototype.getActionUrl = function (action) {
        if (action == 'uploadimage' || action == 'uploadscrawl' || action == 'uploadimage' || action === 'uploadvideo') {
            return '/admin/uploader/alioss_upload';
        } else {
            return this._bkGetActionUrl.call(this, action);
        }
    };
    return UE.getEditor(content, {
        toolbars: [
            [
                'source', //源代码
                'bold', //加粗
                'indent', //首行缩进
                'pasteplain', //纯文本粘贴模式
                'horizontal', //分隔线
                'removeformat', //清除格式
                'insertcode', //代码语言
                'time', //时间
                'date', //日期
                'fontfamily', //字体
                'fontsize', //字号
                'paragraph', //段落格式
                'map', //Baidu地图
                'gmap', //Google地图
                'autotypeset', //自动排版
                'justifyleft', //居左对齐
                'justifyright', //居右对齐
                'justifycenter', //居中对齐
                'justifyjustify', //两端对齐
                'forecolor', //字体颜色
                'lineheight', //行间距
                'simpleupload', //单图上传
                'insertimage', //多图上传
                'insertvideo', //视频
                'attachment', //附件
            ]
        ],
        initialFrameWidth: '100%',  //初始化编辑器宽度,默认1000
        autoHeightEnabled: false,
        autoFloatEnabled: true,
    });
}

/**
 * ueditor初始化开始
 */
function get_editor_content(content) {
    var arr = [];
    arr.push(UE.getEditor(content).getContent());
    return arr.join("\n");
}

/**
 * 打开新的tab
 */
function fatherAddTab(pagepram) {
    self.parent.addTab(
        {
            url: pagepram.url,
            text: pagepram.text
        }
    );
}

/**
 * JS数组拆分并换行展示-textarea文本框展示
 */
function showTextarea(arr) {
    var str = '';
    for (var i = 0; i < arr.length; i++) {
        str += (arr[i] + "\n");
    }
    return str;
}

function majax(url, pageparm, table = '', win = '') {
    $.post(url, {
        pageparm: JSON.stringify(pageparm),
        table: table
    }, function (ret) {
        console.log(JSON.stringify(ret));

        if (ret.status == 1) {
            $('#' + win).window('close');
            $.messager.show({title: '提示', msg: ret.msg, timeout: 3000, showType: 'slide'});
            if (typeof (window.parent.vm.init) == 'function') {
                window.parent.vm.init();
            }
        } else {
            $.messager.alert('提示', ret.msg, 'warning');
        }
    }, 'json');
}

//用户端操作统一提示样式
function fajax(url, pageparm, reload) {
    $.post(url, {
        pageparm: JSON.stringify(pageparm),
    }, function (ret) {
        if (ret.status == 1) {
            pbDialog(ret.msg, '', 1, '', '', '', false);
            if (reload) {
                setTimeout(function () {
                    location.reload();
                }, 1500);
            }
        } else {
            pbDialog(ret.msg, '', 0, '', '', '', false);
        }
    }, 'json');
}


/**
 * layer打开windows操作
 * @param title 显示标题
 * @param url 页面地址
 */
function layerOpenwin(title, url) {
    layer.open({
        type: 2,
        title: title,
        area: ['100%', '100%'],
        content: url,
        cancel: function (index, layero) {
            vm.init();
            layer.closeAll();
        }
    });
}

function layerOpenwinMid(title, url) {
    layer.open({
        type: 2,
        title: title,
        area: ['90%', '90%'],
        content: url,
        cancel: function (index, layero) {
            vm.init();
            layer.closeAll();
        }
    });
}

/**
 *JS去除左右空格
 */
function trim(text) {
    if (typeof (text) == "string") {
        return text.replace(/^\s*|\s*$/g, "");
    } else {
        return text;
    }
}

/**
 * JS获取二维数组组合
 var attrlist = [["白红色", "海军蓝", "黄黑色"], ["皮", "革", "草"], ["穷人版", "富人版", "极客版", '办公']];
 var OBJ = getArrayByArrays(attrlist);
 console.log(OBJ);
 */

function getArrayByArrays(arrays) {
    function getValuesByArray(arr1, arr2) {
        var arr = [];
        for (var i = 0; i < arr1.length; i++) {
            var v1 = arr1[i];
            for (var j = 0; j < arr2.length; j++) {
                var v2 = arr2[j];
                var value = trim(v1 + " " + v2);
                arr.push(value);
            }
        }
        return arr;
    }

    var arr = [''];
    for (var i = 0; i < arrays.length; i++) {
        arr = getValuesByArray(arr, arrays[i]);
    }
    return arr;
}


/**
 *获取当前时间，格式：2019-03-29 09:27
 */
function getNowFormatDate() {
    var date = new Date();
    var seperator1 = "-";
    var seperator2 = ":";
    var month = date.getMonth() + 1;
    var strDate = date.getDate();
    if (month >= 1 && month <= 9) {
        month = "0" + month;
    }
    if (strDate >= 0 && strDate <= 9) {
        strDate = "0" + strDate;
    }
    var currentdate = date.getFullYear() + seperator1 + month + seperator1 + strDate
        + " " + date.getHours() + seperator2 + date.getMinutes()
        + seperator2 + date.getSeconds();
    return currentdate;
}

/**
 * 七牛前端上传 弹窗提醒依赖qiniu.min.js,layer.js
 * @param token_url
 * @param file
 * @param complete  上传完成回调
 * @param error     错误回调
 * @param next      分片上传回调
 * @param token     七牛上传token
 */
function qiniu_upload(token_url, file, complete, error, next, token) {
    var finishedAttr = [];
    var compareChunks = [];
    var observable;
    if (file) {
        var index = layer.load(1);
        var key = (new Date()).valueOf().toString() + Math.random().toString().slice(2, 8);
        var config = {
            useCdnDomain: true,
            region: qiniu.region.z0,//华东
        };
        var putExtra = {
            fname: "",
            params: {},
            mimeType: ["image/png", "image/jpg", "image/jpeg", "image/gif", "video/mp4"]
        };

        if (!token) {
            $.ajax({
                type: 'POST',
                url: token_url,
                async: false,
                success: function (data) {
                    token = data;
                },
                error: function (e) {
                    layer.msg('获取上传token失败');
                }
            });
        }
        if (typeof (error) != 'function') {
            error = function (err) {
                layer.close(index);
                layer.msg(err.error);
            };
        }

        if (typeof (complete) != 'function') {
            complete = function (res) {
                return res.key;
            };
        }

        if (typeof (next) != 'function') {
            next = function (response) {
                var chunks = response.chunks || [];
                var total = response.total;
                // 这里对每个chunk更新进度，并记录已经更新好的避免重复更新，同时对未开始更新的跳过
                for (var i = 0; i < chunks.length; i++) {
                    if (chunks[i].percent === 0 || finishedAttr[i]) {
                        continue;
                    }
                    if (compareChunks[i].percent === chunks[i].percent) {
                        continue;
                    }
                    if (chunks[i].percent === 100) {
                        finishedAttr[i] = true;
                    }
                }
                //console.log(total.percent);
                compareChunks = chunks;
            };
        }

        observable = qiniu.upload(file, key, token, putExtra, config);
        var subscription = observable.subscribe(next, error, complete);//控制句柄
    }
}

/**
 * 阿里OSS后端签名,前端表单上传
 * @param token_url
 * @param file
 * @param complete
 * @param error
 * @param next
 * @param token
 */
function single_upload(file, complete, error, token) {
    if (file) {
        var key = (new Date()).valueOf().toString() + Math.random().toString().slice(2, 8);
        var ear = file.name.substr(file.name.lastIndexOf('.'));//后缀名
        if (typeof token == 'string') {
            token = aliOssToken(token);
        }
        var ossData = new FormData();
        ossData.append('OSSAccessKeyId', token.accessid);
        ossData.append('policy', token.policy);
        ossData.append('Signature', token.signature);
        ossData.append('key', token.dir + key + ear);
        ossData.append('success_action_status', 201);
        ossData.append('file', file);//必须是表单中的最后一个域

        $.ajax({
            url: token.url,
            data: ossData,
            dataType: 'xml',
            processData: false,
            contentType: false,
            type: 'POST',
            success: function (data) {
                complete($(data).find('Location').text());
            },
            error: function (xhr) {
                if (typeof error == 'function') {
                    error(xhr);
                } else {
                    console.log(xhr);
                    if (layer) {
                        layer.close(index);
                        layer.msg('上传失败');
                    } else {
                        alert('上传失败');
                    }
                }
            }
        });
    } else {
        if (typeof error == 'function') {
            error(xhr);
        } else {
            console.log(xhr);
            if (layer) {
                layer.close(index);
                layer.msg('上传失败');
            } else {
                alert('上传失败');
            }
        }
    }
}

function aliOssToken(url) {
    var token;
    $.ajax({
        type: 'POST',
        url: url,
        async: false,
        success: function (data) {
            token = data;
        },
        error: function (xhr, type) {
            console.log(xhr);
            easyshow('获取图片上传token失败');
        }
    });
    return token;
}

/**
 *后台-admin模块通用分页组件
 */
function pageinit(elem, theme) {
    if (typeof elem == 'undefined') {
        elem = "turn-page";
    }
    if (typeof theme == 'undefined') {
        theme = "#5cadff";
    }
    layui.use('laypage', function () {
        var laypage = layui.laypage;
        laypage.render({
            elem: elem, //注意，这里的 test1 是 ID，不用加 # 号
            count: vm.count, //数据总数，从服务端得到
            limit: vm.rows,
            limits: [10, 15, 20, 30, 40, 50, 100],
            groups: 5,
            //theme: '#EF4F4F',
            theme: theme,
            //layout: ['count', 'prev', 'page', 'next', 'limit', 'skip'],
            layout: ['prev', 'page', 'next', 'limit'],
            jump: function (obj, first) {
                vm.page = obj.curr;
                vm.rows = obj.limit;
                if (!first) {
                    vm.init();
                }
            }
        });
    });


}

/**
 *tabs_info点击切换DIV
 */
function tabsinfoinit() {
    $(document).on("click", ".tabs_info li", function () {
        var index = $(this).index();
        $(this).addClass("curr").siblings().removeClass("curr");
        $(".switch_info").eq(index).show().siblings(".switch_info").hide();
        if ($(".switch_info").siblings(".info_btn").length > 0) {
            document.getElementById("info_btn_bf100").className = "info_btn info_btn_bf100";
            $(".switch_info").siblings(".info_btn").addClass("button-info-item" + index);
        }
    });
}

/**
 * 一个页面设置多个图片上传组件，有规则的操作
 * @param id 页面div绑定的ID
 */
function moreUploadFile(id, showMsg) {
    var upload = layui.upload;
    upload.render({
        elem: '#' + id,
        url: '/admin/uploader/alioss_upload', //上传接口
        data: {
            upload: 1
        },
        accept: 'file', //允许上传的文件类型
        done: function (ret) {
            vm.info[id] = ret.url;
            if (showMsg) {
                if (ret.state == 'SUCCESS') {
                    $.messager.show({title: '提示', msg: '操作成功', timeout: 1000, showType: 'slide'});
                } else {
                    $.messager.show({title: '提示', msg: '操作失败', timeout: 1000, showType: 'slide'});
                }
            }
        },
    });
}

/**
 * 前台页面上传组件
 * 一个页面设置多个图片上传组件，有规则的操作
 * @param id 页面div绑定的ID
 */
function fmoreUploadFile(id = '', showMsg = true) {
    var upload = layui.upload;
    upload.render({
        elem: '#' + id,
        url: '/admin/uploader/alioss_upload', //上传接口
        data: {
            upload: 1
        },
        accept: 'file', //允许上传的文件类型
        done: function (ret) {
            vm.info[id] = ret.url;
            if (showMsg) {
                if (ret.state == 'SUCCESS') {
                    layer.msg('操作成功', {icon: 1});
                } else {
                    layer.msg('操作失败', {icon: 2});
                }
            }
        },
    });
}

/**
 *无权限则关闭当前选项卡
 */
function parentTabClose() {
    let curTab = window.parent.$("#index_tabs").tabs("getSelected");
    let curTabIndex = window.parent.$("#index_tabs").tabs("getTabIndex", curTab);
    if (curTabIndex == 0) {
        $.messager.alert('提示', '首页不允许关闭！', 'warning');
        return false;
    } else {
        //$.messager.show({title: '提示', msg: '自动关闭', timeout: 300, showType: 'slide'});
    }
    setTimeout(function () {

        if (curTabIndex > 0) {
            window.parent.$("#index_tabs").tabs("close", curTabIndex);
        }
    }, 1000);
}

/**
 * ECharts初始化操作
 * @param id div设置ID
 * @param walden 主体配置文件
 * @param option
 * {
        title: {
            text: 'ECharts 入门示例'
        },
        tooltip: {},
        legend: {
            data:['销量']
        },
        xAxis: {
            data: ["衬衫","羊毛衫","雪纺衫","裤子","高跟鞋","袜子"]
        },
        yAxis: {},
        series: [{
            name: '销量',
            type: 'bar',
            data: [5, 20, 36, 10, 10, 20]
        }]
   };
 */

function echartsInit(id = "main", option = {}) {
    let echartsOBJ = echarts.init(document.getElementById(id), 'walden');
    echartsOBJ.setOption(option);
}

/* 必填验证错误提示 */
function error_div(obj, error) {
    var error_div = $(obj).parents('dd').find('div.form_prompt');

    error_div.find("label").remove();
    if (!error) {
        $(obj).removeClass("error");
        return;
    } else {
        $(obj).addClass("error");
    }

    $(obj).focus();
    error_div.find("label").remove();
    error_div.append("<label class='error'><i class='icon icon-exclamation-sign'></i>" + error + "</label>");
}

function changeAvatar(obj) {
    let input = document.createElement('input');
    input.type = 'file';
    input.style.opacity = 0;
    input.onchange = function () {
        let param = new FormData();
        param.append('upload', 1);
        param.append('file', input.files[0]);
        $.ajax({
            type: 'POST',
            url: '/admin/uploader/alioss_upload',
            data: param,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (data) {
                $(input).remove();
                if (data.error == 0) {
                    $(obj).find('img').attr('src', data.url);
                    let userinfo = {
                        user_id: user_id,
                        avatar: data.url
                    };
                    fajax('/home/member/profile', userinfo);
                } else {
                    layer.msg(data.message);
                }
            },
            error: function (xhr, type) {
                layer.msg(xhr.responseJSON);
                $(input).remove();
            }
        });
    };
    document.body.append(input);
    $(input).trigger('click');
}
