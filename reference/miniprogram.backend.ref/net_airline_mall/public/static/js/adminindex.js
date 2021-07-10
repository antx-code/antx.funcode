

//iframe内页 a标签链接跳转方法
function intheHref(obj) {
    var url = obj.data("url")
        , param = obj.data("param");

    openItem(param);
    loadUrl(url);
}

function openItem(param, home) {
    //若cookie值不存在，则跳出iframe框架
    if (!$.cookie('dscActionParam')) {
        top.location.href = location.href;
        //top.location = self.location
        //window.location.reload();
    }

    var $this = $('div[id^="adminNavTabs_"]').find('a[data-param="' + param + '"]');
    var url = $this.data('url');

    data_str = param.split('|');
    if ($this.parents(".admin-main").hasClass("fold")) {
        $this.parents('.sub-menu').hide();
    } else {
        $this.parents('.sub-menu').show();
    }

    $this.parents('.item').addClass('current').siblings().removeClass('current');
    $this.parents('.item').siblings().find(".sub-menu").hide();
    $this.parents('li').addClass('curr').siblings().removeClass('curr');
    $this.parents('div[id^="adminNavTabs_"]').show().siblings().hide();

    $('li[data-param="' + data_str[0] + '"]').addClass('active').siblings().removeClass("active");

    $.cookie('dscActionParam', data_str[0] + '|' + data_str[1], {
        expires: 1,
        path: '/'
    });

    if (param == 'home') {
        $('#adminNavTabs_home').show().siblings().hide();
        $('#adminNavTabs_home').find(".sub-menu").show();
        $('#adminNavTabs_home .sub-menu').find("li a:first").click();
    }
    var text = $this.parents('li').children("a").html();
    var url = $this.parents('li').children("a").attr('data-url');
    var params = {text:text,url:url};
    if(params.text && params.url){
        addTab(params);
    }
}

function loadUrl(url) {
    $.cookie('shopUrl', url, {
        expires: 1,
        path: '/'
    });
}

//消息通知
function message(){
    var hoverTimer, outTimer;
    $("*[shoptype='oper_msg']").mouseenter(function(){
        clearTimeout(outTimer);
        hoverTimer = setTimeout(function(){
            $('#msg_Container').show();
        },200);
    });

    $("*[shoptype='oper_msg']").mouseleave(function(){
        clearTimeout(hoverTimer);
        outTimer = setTimeout(function(){
            $('#msg_Container').hide();
        },100);
    });
}

//关闭tab动态高亮显示菜单
function autoShowMenuList() {
    var param = null;
    $(".admincj_nav li").find("a").each(function (i) {
        var currentTab = $('#index_tabs').iTabs('getSelected');
        var opts = $.data(currentTab[0], 'panel').options;
        if(opts.url == $(this).data('url')){
            param = $(this).data("param");
        }
    });
    var $this = $('div[id^="adminNavTabs_"]').find('a[data-param="' + param + '"]');
    data_str = param.split('|');
    if ($this.parents(".admin-main").hasClass("fold")) {
        $this.parents('.sub-menu').hide();
    } else {
        $this.parents('.sub-menu').show();
    }

    $this.parents('.item').addClass('current').siblings().removeClass('current');
    $this.parents('.item').siblings().find(".sub-menu").hide();
    $this.parents('li').addClass('curr').siblings().removeClass('curr');
    $this.parents('div[id^="adminNavTabs_"]').show().siblings().hide();
    $('li[data-param="' + data_str[0] + '"]').addClass('active').siblings().removeClass("active");
}
$(function() {
    //logo点击跳转到首页
    $(".admin-logo a").on("click", function() {
        var param = $(this).data('param');
        $(".admincj_nav").find(".item").eq(0).show();
        $(".admincj_nav").find(".sub-menu").hide();
        $(".module-menu").find("li").removeClass("active");
        openItem(param);
    });

    //顶部管理员信息展开
    function adminSetup() {
        var hoverTimer, outTimer;
        $('#admin-manager-btn,.manager-menu,.admincp-map').mouseenter(function() {
            clearTimeout(outTimer);
            hoverTimer = setTimeout(function() {
                $('.manager-menu').show();
                $('#admin-manager-btn i').removeClass().addClass("arrow-close");
            }, 200);
        });

        $('#admin-manager-btn,.manager-menu,.admincp-map').mouseleave(function() {
            clearTimeout(hoverTimer);
            outTimer = setTimeout(function() {
                $('.manager-menu').hide();
                $('#admin-manager-btn i').removeClass().addClass("arrow");
            }, 100);
        });
    }
    adminSetup();

    function loadEach() {
        $('.admincj_nav').find('div[id^="adminNavTabs_"]').each(function() {
            var $this = $(this);

            var name = $this.attr("id").replace("adminNavTabs_", "");

            $this.find('.item > .tit > a').each(function(i) {
                $(this).parent().next().css('top', (-68) * i + 'px');
                $(this).click(function() {
                    var type = $(this).parents(".item").data("type");
                    if (type == "home") {
                        var url = $(this).data('url');
                        var param = $(this).data('param');

                        //$(".admin-main").addClass("start_home");
                        $(".admincj_nav").find(".item").eq(0).addClass("current").siblings().removeClass("current");
                        $(".admincj_nav").find(".item").eq(0).show();
                        $(".module-menu").find("li").removeClass("active");
                        $this.find('.sub-menu').hide();
                        openItem(param, 1);
                    } else {
                        var url = '';
                        $this.find('.sub-menu').hide();
                        $this.find('.item').removeClass('current');
                        if (name == "menushopping") {
                            //商品 默认三级分类链接到第二个 商品列表
                            var param = $(this).parent().next().find('a:first').data('param');
                            var data_str = param.split('|');
                            if ($(this).parents('.item').index() == 0 && data_str[1] == "001_goods_setting") {
                                $(this).parents('.item').eq(1).addClass('current');
                                $(this).parent().next().find('a').eq(1).click();
                                url = $(this).parent().next().find('a').eq(1).data('url');
                            } else {
                                $(this).parents('.item:first').addClass('current');
                                $(this).parent().next().find('a:first').click();
                                url = $(this).parent().next().find('a:first').data('url');
                            }
                        } else {
                            $(this).parents('.item:first').addClass('current');
                            $(this).parent().next().find('a:first').click();
                            url = $(this).parent().next().find('a:first').data('url');
                        }
                        //$(".admin-main").removeClass("start_home");
                        //loadUrl(url);
                    }
                });
            });
        });
    }
    loadEach();

    //右侧二级导航选择切换
    $(".sub-menu li a").on("click", function() {
        var param = $(this).data("param");
        var url = $(this).data("url");
        if (param != null) {
            loadUrl(url);
            openItem(param);
        }
    });



    //顶部导航栏菜单切换
    $(".module-menu li").on("click", function() {
        var modules = $(this).data("param");
        var items = $("#adminNavTabs_" + modules).find(".item");
        var first_item = items.first();
        var default_a = "";

        items.find('.sub-menu').hide();
        $(this).addClass("active").siblings().removeClass("active");
        $("#adminNavTabs_" + modules).show().siblings().hide();
        items.removeClass("current");
        first_item.addClass('current');

        if (modules == "menushopping") {
            var param = first_item.find('li').find("a").data("param");
            var data_str = param.split('|');

            if (data_str[1] == "001_goods_setting") {
                default_a = first_item.find('li').eq(1).find("a");
            } else {
                default_a = first_item.find('li').eq(0).find("a");
            }
        } else {
            default_a = first_item.find('li').eq(0).find('a:first');
        }

        default_a.click();

    });

    //后台提示
    $(document).on("click", "#msg_Container .msg_content a", function() {
        var param = $(this).data("param");
        var url = $(this).data("url");

        loadUrl(url);
        openItem(param);
    });

    $(".foldsider").click(function() {
        var leftdiv = $(".admin-main");
        if (leftdiv.hasClass("fold")) {
            leftdiv.removeClass("fold");
            $(this).find("i.icon").removeClass("icon-indent-right").addClass("icon-indent-left");
            leftdiv.find(".current").children(".sub-menu").show();

            loadEach();
        } else {
            leftdiv.addClass("fold");
            $(this).find("i.icon").removeClass("icon-indent-left").addClass("icon-indent-right");
            leftdiv.find(".sub-menu").hide();
            leftdiv.find(".sub-menu").css("top", "0px");
        }
        setTimeout(function () {
            $('#index_tabs').tabs({
                width: $("#index_tabs").parent().width(),
                height: "auto"
            });
            $('#index_tabs .panel-htop').css('width',$("#index_tabs").parent().width()+'px');
            $('#index_tabs .panel-body').css('width',$("#index_tabs").parent().width()+'px');
        },300);

    });

    function ready() {
        var bwidth = $(window).width();

        if (bwidth < 1380) {
            $(".foldsider").click();
        }

        $(window).resize(function() {
            bwidth = $(window).width();

            if (bwidth < 1380 && !$(".admin-main").hasClass("fold")) {
                $(".foldsider").click();
            }
        });
    }

    ready();

    var foldHoverTimer, foldOutTimer, foldHoverTimer2;
    $(document).on("mouseenter", ".fold .tit", function() {
        var $this = $(this);
        var items = $this.parents(".item");

        var length = items.find(".sub-menu").find("li").length;
        items.parent().find(".item:gt(5)").find(".sub-menu").css("top", -((40 * length) - 68));
        $this.next().show();
        items.addClass("current");
        items.siblings(".item").removeClass("current");
    });

    $(document).on("mouseleave", ".fold .tit", function() {
        var $this = $(this);
        clearTimeout(foldHoverTimer);
        foldOutTimer = setTimeout(function() {
            $this.next().hide();
        });
    });

    $(document).on("mouseenter", ".fold .sub-menu", function() {
        clearTimeout(foldOutTimer);
        var $this = $(this);
        foldHoverTimer2 = setTimeout(function() {
            $this.show();
        });
    });

    $(document).on("mouseleave", ".fold .sub-menu", function() {
        var $this = $(this);
        $this.hide();
    });

    //没有cookie默认选择起始页
    if ($.cookie('dscActionParam') == null) {
        $('.admin-logo').find('a').click();
    } else {
        //openItem($.cookie('dscActionParam'));
    }


    if ($.cookie('bgColorSelectorPosition') != null) {
        $('.admin-header').css('background-color', $.cookie('bgColorSelectorPosition'));
    } else {
        $('.admin-header').css('background-color', 'rgb(60, 141, 188)');
    }

    /* 判断浏览器是ie6 - ie8 后台不可以进入*/
    if (!$.support.leadingWhitespace) {
        notIe();
    }


    /* 后台消息提示 展开伸缩*/
    $("[shoptype='msg_tit']").on("click",function(){
        var t = $(this),
            con = t.siblings(".msg_content"),
            Item = t.parents(".item");

        if(con.is(":hidden")){
            con.slideDown();
            Item.siblings().find(".msg_content").slideUp();
            t.find(".iconfont").addClass("icon-up").removeClass("icon-down");
            Item.siblings().find(".iconfont").removeClass("icon-up").addClass("icon-down");
        }else{
            con.slideUp();
            t.find(".iconfont").removeClass("icon-up").addClass("icon-down");
        }
    });
});








