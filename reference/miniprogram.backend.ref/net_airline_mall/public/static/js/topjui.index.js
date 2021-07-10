var isFullScreen = false;

var App = function () {
    var setFullScreen = function () {
        var docEle = document.documentElement;
        if (docEle.requestFullscreen) {
            //W3C
            docEle.requestFullscreen();
        } else if (docEle.mozRequestFullScreen) {
            //FireFox
            docEle.mozRequestFullScreen();
        } else if (docEle.webkitRequestFullScreen) {
            //Chrome等
            docEle.webkitRequestFullScreen();
        } else if (docEle.msRequestFullscreen) {
            //IE11
            docEle.msRequestFullscreen();
        } else {
            $.iMessager.alert('温馨提示', '该浏览器不支持全屏', 'messager-warning');
        }
    };

    //退出全屏 判断浏览器种类
    var exitFullScreen = function () {
        // 判断各种浏览器，找到正确的方法
        var exitMethod = document.exitFullscreen || //W3C
            document.mozCancelFullScreen ||    //Chrome等
            document.webkitExitFullscreen || //FireFox
            document.msExitFullscreen; //IE11
        if (exitMethod) {
            exitMethod.call(document);
        } else if (typeof window.ActiveXObject !== "undefined") {//for Internet Explorer
            var wscript = new ActiveXObject("WScript.Shell");
            if (wscript !== null) {
                wscript.SendKeys("{F11}");
            }
        }
    };

    return {
        init: function () {

        },
        handleFullScreen: function () {
            if (isFullScreen) {
                exitFullScreen();
                isFullScreen = false;
            } else {
                setFullScreen();
                isFullScreen = true;
            }
        }
    };
};

// Tab菜单操作
function tabMenuOprate(menu, type) {
    var allTabs = $('#index_tabs').iTabs('tabs');
    var allTabtitle = [];
    $.each(allTabs, function (i, n) {
        var opt = $(n).iPanel('options');
        if (opt.closable)
            allTabtitle.push(opt.title);
    });
    var curTabTitle = $(menu).data("tabTitle");
    var curTabIndex = $('#index_tabs').iTabs("getTabIndex", $('#index_tabs').iTabs("getTab", curTabTitle));
    switch (type) {
        case "1"://关闭当前
            if (curTabIndex > 0) {
                $('#index_tabs').iTabs("close", curTabTitle);
                autoShowMenuList();
                return false;
                break;
            } else {
                $.iMessager.show({
                    title: '操作提示',
                    msg: '首页不允许关闭！'
                });
                break;
            }
        case "2"://全部关闭
            for (var i = 0; i < allTabtitle.length; i++) {
                $('#index_tabs').iTabs('close', allTabtitle[i]);
            }
            autoShowMenuList();
            break;
        case "3"://除此之外全部关闭
            for (var i = 0; i < allTabtitle.length; i++) {
                if (curTabTitle != allTabtitle[i])
                    $('#index_tabs').iTabs('close', allTabtitle[i]);
            }
            $('#index_tabs').iTabs('select', curTabTitle);
            autoShowMenuList();
            break;
        case "4"://当前侧面右边
            for (var i = curTabIndex; i < allTabtitle.length; i++) {
                $('#index_tabs').iTabs('close', allTabtitle[i]);
            }
            $('#index_tabs').iTabs('select', curTabTitle);
            autoShowMenuList();
            break;
        case "5": //当前侧面左边
            for (var i = 0; i < curTabIndex - 1; i++) {
                $('#index_tabs').iTabs('close', allTabtitle[i]);
            }
            $('#index_tabs').iTabs('select', curTabTitle);
            autoShowMenuList();
            break;
        case "6": //刷新
            var currentTab = $('#index_tabs').iTabs('getSelected');
            var opts = $.data(currentTab[0], 'panel').options;
            if (opts.iframe) {
                var currentIframe = currentTab.find('iframe')[0];
                currentIframe.contentWindow.location.href = currentIframe.src;
            } else {
                $(currentTab[0]).panel('refresh');
            }
            autoShowMenuList();
            break;
        case "7": //在新窗口打开
            var refresh_tab = $('#index_tabs').iTabs('getSelected');
            var refresh_iframe = refresh_tab.find('iframe')[0];
            window.open(refresh_iframe.src);
            break;
    }

}

//关闭当前Tab-全局调用
function closeCurTabIndex() {
    var currentTab = parent.$('#index_tabs').iTabs('getSelected');
    var opts = parent.$.data(currentTab[0], 'panel').options;
    setTimeout(function () {
        parent.$('#index_tabs').iTabs("close", opts.title);
    }, 500);

}

//选项卡右键菜单
function findTabElement(target) {
    var $ele = $(target);
    if (!$ele.is("a")) {
        $ele = $ele.parents("a.menu_tab");
    }
    return $ele;
}

//保存页面id的field
var pageIdField = "data-pageId";

function getPageId(element) {
    if (element instanceof jQuery) {
        return element.attr(pageIdField);
    } else {
        return $(element).attr(pageIdField);
    }
}

function findTabTitle(pageId) {
    var $ele = null;
    $(".page-tabs-content").find("a.menu_tab").each(function () {
        var $a = $(this);
        if ($a.attr(pageIdField) == pageId) {
            $ele = $a;
            return false;//退出循环
        }
    });
    return $ele;
}

function getTabUrlById(pageId) {
    var $iframe = findIframeById(pageId);
    return $iframe[0].contentWindow.location.href;
}

function getTabUrl(element) {
    var pageId = getPageId(element);
    getTabUrlById(pageId);
}

function findTabPanel(pageId) {
    var $ele = null;
    $("#index_tabs").find("div.tab-pane").each(function () {
        var $div = $(this);
        if ($div.attr(pageIdField) == pageId) {
            $ele = $div;
            return false;//退出循环
        }
    });
    return $ele;
}

function findIframeById(pageId) {
    return findTabPanel(pageId).children("iframe");
}

function getActivePageId() {
    var $a = $('.page-tabs-content').find('.active');
    return getPageId($a);
}

//激活Tab,通过id
function activeTabByPageId(pageId) {
    $(".menu_tab").removeClass("active");
    $("#index_tabs").find(".active").removeClass("active");
    //激活TAB
    var $title = findTabTitle(pageId).addClass('active');
    findTabPanel(pageId).addClass("active");
    // scrollToTab($('.menu_tab.active'));
    scrollToTab($title[0]);
}

/**
 * 更换页面风格
 * @param topjuiThemeName
 */
function changeTheme(themeName) {/* 更换主题 */
    var $dynamicTheme = $('#dynamicTheme');
    var themeHref = $dynamicTheme.attr('href');
    var themeHrefNew = themeHref.substring(0, themeHref.indexOf('themes')) + 'themes/default/topjui.' + themeName + '.css';
    // 更换主页面风格
    $dynamicTheme.attr('href', themeHrefNew);

    // 更换iframe页面风格
    var $iframe = $('iframe');
    if ($iframe.length > 0) {
        for (var i = 1; i < $iframe.length; i++) {
            var ifr = $iframe[i];
            var $iframeDynamicTheme = $(ifr).contents().find('#dynamicTheme');
            var iframeThemeHref = $iframeDynamicTheme.attr('href');
            if (iframeThemeHref != undefined) {
                var iframeThemeHrefNew = iframeThemeHref.substring(0, iframeThemeHref.indexOf('themes')) + 'themes/default/topjui.' + themeName + '.css';
                $iframeDynamicTheme.attr('href', iframeThemeHrefNew);
            }
        }
    }
    //设置皮肤颜色
    switch (themeName) {
        case 'blue':
            $('.admin-header').css('background-color', '#003e79');
            $.cookie('bgColorSelectorPosition', '#003e79');
            break;

        case 'black':
            $('.admin-header').css('background-color', 'rgb(44, 59, 65)');
            $.cookie('bgColorSelectorPosition', 'rgb(44, 59, 65)');
            break;
        case 'blacklight':
            $('.admin-header').css('background-color', 'rgb(44, 59, 65)');
            $.cookie('bgColorSelectorPosition', 'rgb(44, 59, 65)');
            break;

        case 'red':
            $('.admin-header').css('background-color', 'rgb(221, 75, 57)');
            $.cookie('bgColorSelectorPosition', 'rgb(221, 75, 57)');
            break;
        case 'redlight':
            $('.admin-header').css('background-color', 'rgb(221, 75, 57)');
            $.cookie('bgColorSelectorPosition', 'rgb(221, 75, 57)');
            break;

        case 'green':
            $('.admin-header').css('background-color', 'rgb(0, 166, 90)');
            $.cookie('bgColorSelectorPosition', 'rgb(0, 166, 90)');
            break;
        case 'greenlight':
            $('.admin-header').css('background-color', 'rgb(0, 166, 90)');
            $.cookie('bgColorSelectorPosition', 'rgb(0, 166, 90)');
            break;

        case 'purple':
            $('.admin-header').css('background-color', 'rgb(96, 92, 168)');
            $.cookie('bgColorSelectorPosition', 'rgb(96, 92, 168)');
            break;
        case 'purplelight':
            $('.admin-header').css('background-color', 'rgb(96, 92, 168)');
            $.cookie('bgColorSelectorPosition', 'rgb(96, 92, 168)');
            break;

        case 'blue':
            $('.admin-header').css('background-color', 'rgb(60, 141, 188)');
            $.cookie('bgColorSelectorPosition', 'rgb(60, 141, 188)');
            break;
        case 'bluelight':
            $('.admin-header').css('background-color', 'rgb(60, 141, 188)');
            $.cookie('bgColorSelectorPosition', 'rgb(60, 141, 188)');
            break;

        case 'yellow':
            $('.admin-header').css('background-color', 'rgb(243, 156, 18)');
            $.cookie('bgColorSelectorPosition', 'rgb(243, 156, 18)');
            break;
        case 'yellowlight':
            $('.admin-header').css('background-color', 'rgb(243, 156, 18)');
            $.cookie('bgColorSelectorPosition', 'rgb(243, 156, 18)');
            break;
    }
    $.cookie('topjuiThemeName', themeName, {
        expires: 7,
        path: '/'
    });
};


// 退出系统
function logout() {
    $.iMessager.confirm('提示', '确定要退出吗?', function (r) {
        if (r) {
            $.iMessager.progress({text: '正在退出中...'});
            $.post('/admin/login/loginout', {}, function (ret) {
                if (ret.status == 0) {
                    location.href = ret.url;
                    // window.location.href = '/admin/login/login';
                } else {
                    $.messager.show({title: '提示', msg: ret.msg, timeout: 3000, showType: 'slide'});
                }
            }, 'json');
        }
    });
}

// 生成左侧导航菜单
function generateMenu(menuId, systemName) {
    if (menuId < 8000) {
        $(".panel-header .panel-title:first").html(systemName);
        var allPanel = $("#RightAccordion").iAccordion('panels');
        var size = allPanel.length;
        if (size > 0) {
            for (i = 0; i < size; i++) {
                var index = $("#RightAccordion").iAccordion('getPanelIndex', allPanel[i]);
                $("#RightAccordion").iAccordion('remove', 0);
            }
        }
        //var url = "./json/menu/menu_" + menuId + ".json";
        var url = "/admin/index/getMenuList";
        $.get(
            url, {"levelId": "2"}, // 获取第一层目录
            function (data) {
                $.each(data.data, function (i, e) {// 循环创建手风琴的项
                    var pid = e.pid;
                    var isSelected = i == 0 ? true : false;
                    $('#RightAccordion').iAccordion('add', {
                        fit: false,
                        title: e.text,
                        content: "<ul id='tree" + e.id + "' ></ul>",
                        border: false,
                        selected: isSelected,
                        iconCls: e.iconCls //追加字体class图标
                    });
                    $("#tree" + e.id).tree({
                        data: e.children,
                        lines: false,
                        animate: true,
                        onBeforeExpand: function (node, param) {
                            $("#tree" + e.id).tree('options').url = "./json/menu/menu_" + node.id + ".json";
                        },
                        onClick: function (node) {
                            if (node.url) {
                                addTab(node);
                            } else {
                                if (node.state == "closed") {
                                    $("#tree" + e.id).tree('expand', node.target);
                                } else if (node.state == 'open') {
                                    $("#tree" + e.id).tree('collapse', node.target);
                                }
                            }
                        }
                    });
                });
            }, "json"
        );
    }
}

//打开Tab窗口
function addTab(params) {
    var t = $('#index_tabs');
    var $selectedTab = t.iTabs('getSelected');
    var selectedTabOpts = $selectedTab.iPanel('options');

    var iframe = '<iframe src="' + params.url + '" scrolling="auto" frameborder="0" class="workspace" style="width:100%;height:100%;"></iframe>';
    var defaults = {
        id: getRandomNumByDef(),
        refererTab: {},
        title: params.text,
        iframe: topJUI.config.iframe,
        onlyInitParse: true,
        iconCls: 'fa fa-file-text-o',
        border: true,
        fit: true,
        closable: true,
        //cls: 'leftBottomBorder'
    };
    var opts = $.extend(defaults, params);
    var ifOpts = opts.iframe ? {content: iframe} : {href: params.url};
    opts = $.extend(opts, ifOpts);
    if (t.iTabs('exists', opts.title)) {
        t.iTabs('select', opts.title);
    } else {
        var lastMenuClickTime = $.cookie("menuClickTime");
        var nowTime = new Date().getTime();
        if ((nowTime - lastMenuClickTime) >= 300) {
            $.cookie("menuClickTime", new Date().getTime());
            t.iTabs('add', opts);
        } else {
            $.iMessager.show({title: '温馨提示', msg: '操作过快，请稍后重试！'});
        }
    }
}

function addParentTab(options) {
    var src, title;
    src = options.href;
    title = options.title;
    var t = parent.$('#index_tabs');

    //调试模式开启
    if (!t.context.title) {
        window.open(options.href);
        return false;
    }

    if (t.iTabs('exists', options.title)) {
        // let tab = t.iTabs('getTab', options.title);
        // t.iTabs('update', {tab: tab, options: options});
        // t.iTabs('select', options.title);
        t.iTabs('close',options.title)
    }
    // else {
        var iframe = '<iframe src="' + src + '" frameborder="0" class="workspace" style="border:0;width:100%;height:100%;"></iframe>';
        parent.$('#index_tabs').iTabs("add", {
            title: title,
            content: iframe,
            closable: true,
            iconCls: 'fa fa-file-text-o',
            border: true
        });
    // }
}

function removeRuntime() {
    $.ajax({
        url: '/admin/index/removeRuntime',
        type: 'post',
        cache: false,
        success: function (data, response, status) {
            if (data.status == 0) {
                $.iMessager.show({
                    title: '提示',
                    msg: data.msg
                });
            } else {
                $.iMessager.alert('操作失败！', '未知错误或没有任何修改，请重试！', 'messager-error');
            }
        }
    });
}

// 首页加载完后，取消加载中状态
$(function () {
    $("#loading").fadeOut();
    $(".collapseMenu").on("click", function () {
        var p = $("#index_layout").iLayout("panel", "west")[0].clientWidth;
        if (p > 0) {
            $('#index_layout').iLayout('collapse', 'west');
            $(this).children('span').removeClass('fa-chevron-circle-left').addClass('fa-chevron-circle-right');
        } else {
            $('#index_layout').iLayout('expand', 'west');
            $(this).children('span').removeClass('fa-chevron-circle-right').addClass('fa-chevron-circle-left');
        }
    });

    // 首页tabs选项卡
    var index_tabs = $('#index_tabs').iTabs({
        fit: true,
        tools: [{
            iconCls: 'fa fa-home',
            handler: function () {
                $('#index_tabs').iTabs('select', 0);
            }
        }, {
            iconCls: 'fa fa-refresh',
            handler: function () {
                var refresh_tab = $('#index_tabs').iTabs('getSelected');
                var refresh_iframe = refresh_tab.find('iframe')[0];
                refresh_iframe.contentWindow.location.href = refresh_iframe.src;
                //$('#index_tabs').trigger(TOPJUI.eventType.initUI.base);

                //var index = $('#index_tabs').iTabs('getTabIndex', $('#index_tabs').iTabs('getSelected'));
                //console.log(index);
                //$('#index_tabs').iTabs('getTab', index).iPanel('refresh');
            }
        }, {
            iconCls: 'fa fa-close',
            handler: function () {
                var index = $('#index_tabs').iTabs('getTabIndex', $('#index_tabs').iTabs('getSelected'));
                var tab = $('#index_tabs').iTabs('getTab', index);
                if (tab.iPanel('options').closable) {
                    $('#index_tabs').iTabs('close', index);
                }
            }
        }, {
            iconCls: 'fa fa-arrows-alt',
            handler: function () {
                App().handleFullScreen();
            }
        }],
        //监听右键事件，创建右键菜单
        onContextMenu: function (e, title, index) {
            e.preventDefault();
            if (index >= 0) {
                $('#mm').iMenu('show', {
                    left: e.pageX,
                    top: e.pageY
                }).data("tabTitle", title);
            }
        }
    });

    //tab右键菜单
    $("#mm").iMenu({
        onClick: function (item) {
            tabMenuOprate(this, item.name);
        }
    });

    // 初始化accordion
    $("#RightAccordion").iAccordion({
        fit: true,
        border: false
    });

    // 绑定横向导航菜单点击事件
    $(".systemName").on("click", function (e) {
        //generateMenu(e.currentTarget.dataset.menuid, e.target.textContent); //IE9及以下不兼容data-menuid属性
        //generateMenu(e.target.getAttribute('data-menuid'), e.target.textContent);
        generateMenu($(this).attr("id"), $(this).attr("title"));
        $(".systemName").removeClass("selected");
        $(this).addClass("selected");
    });

    // 主页打开初始化时显示第一个系统的菜单
    $('.systemName').eq('0').trigger('click');

    if ($.cookie('topjuiThemeName')) {
        changeTheme($.cookie('topjuiThemeName'));
        //$('#explanation').css('background-color', $.cookie('bgColorSelectorPosition'));
    }

});

