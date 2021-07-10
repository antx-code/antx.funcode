$(function () {
    //上传管理员头像
    $("[runat='server'] input[name='img']").unbind("change").bind('change', function () {
        single_upload(this.files[0], function (res) {
            layer.closeAll();
            $.ajax({
                type: 'POST',
                url: "/seller/api/seller_set",
                data: {field: 'avatar', value: res},
                success: function (data) {
                    $(".avatar img").attr('src', res);
                },
                error: function (xhr, type) {
                    easyshow(xhr.responseJSON);
                }
            });

        }, null, '/seller/api/uploadtoken');
    });

    if (/tab/.test(location.search)) {
        var tab = getUrlParam('tab');
        setTimeout(function () {
            $('.tabmenu .tab li').eq(parseInt(tab)).find('a').trigger('click');
        }, 100);
    }
    $('.ex_tit').on('click', function () {
        var explanation = $('.explanation');
        if (explanation.hasClass('exp')) {
            var wid = explanation.parent().width() - 28;
            explanation.animate({
                width: wid,
            }, 300, function () {
                $(this).css('height', '100%');
            });
        } else {
            explanation.animate({
                width: 100,
                height: 20
            }, 300);
        }

        explanation.toggleClass('exp');
    });

    $('.add-quickmenu > a').on("click", function (event) {
        event.stopPropagation();
        var offset = $(event.target).offset();
        //$(".sitemap-menu-arrow").css({left:offset.left + $(event.target).width() + 15 + "px"});
        $(".sitemap-menu").css({left: offset.left + $(event.target).width() + 20 + "px"});

        //$(".sitemap-menu-arrow").slideDown("slow");
        $(".sitemap-menu").slideDown("slow");

        $("#quicklink_list").perfectScrollbar("destroy");
        $("#quicklink_list").perfectScrollbar();
    });

    $('#closeSitemap').on("click", function () {
        //$(".sitemap-menu-arrow").slideUp("fast");
        $(".sitemap-menu").slideUp("fast");
        var count = $('#quicklink_list').find('dd.selected').length;
        if (count >= 7) {
            //$(".sitemap-menu-arrow").prev().prev().hide();
            //$(".sitemap-menu-arrow").hide();
            $(".sitemap-menu").hide();
        }
    });

    $('*[shoptype="dialog"]').click(function () {
        var content = $('.eject_con').prop("outerHTML");
        var title = $(this).attr('dialog_title');
        ds.dialog({
            title: title,
            content: content,
            lockColor: "transparent",
            lockOpacity: 0,
            width: 480,
            padding: 0,
            top: 220,
            left: '60%'
        });
    });

    // 绑定点击事件
    $('div[shoptype^="file"]').each(function () {
        if ($(this).prev().find('input[type="hidden"]').val() != '') {
            selectDefaultImage($(this));
        }
    });

    // 商品主图使用
    $('a[shoptype="show_image"]').unbind().click(function () {
        $(this).hide();
        $('a[shoptype="del_goods_demo"]').show();
        $('#demo').show();
    });
    $('a[shoptype="del_goods_demo"]').unbind().click(function () {
        $(this).hide();
        $('a[shoptype="show_image"]').show();
        $('#demo').hide();
    });

    /* 插入商品图片 */
    function insert_img(name, src) {
        $('input[shoptype="goods_image"]').val(name);
        $('img[shoptype="goods_image"]').attr('src', src);
    }

    // 选择品牌
    $('input[name="b_name"]').click(function () {
        $('.ecsc-brand-select > .ecsc-brand-select-container').show();
    });
    $(document).click(function (e) {
        if (e.target.id != 'b_name' && !$(e.target).parents("div").is(".ecsc-brand-select-container")) {
            $('.ecsc-brand-select > .ecsc-brand-select-container').hide();
        }

        //品牌
        if (e.target.id != 'brand_name' && !$(e.target).parents("div").is(".brand-select-container")) {
            $('.brand-select-container').hide();
            $('.brandSelect .brand-select-container').hide();
        }

        //仿select
        if (e.target.className != 'cite' && !$(e.target).parents("div").is(".imitate_select")) {
            $('.imitate_select ul').hide();
        }
    });

    /* AJAX选择品牌 */
    // 根据首字母查询
    $('.letter[shoptype="letter"]').find('a[data-letter]').click(function () {
        var _url = $(this).parents('.brand-index:first').attr('data-url');
        var _tid = $(this).parents('.brand-index:first').attr('data-tid');
        var _letter = $(this).attr('data-letter');
        var _search = $(this).html();
        $.getJSON(_url, {type: 'letter', tid: _tid, letter: _letter}, function (data) {
            insertBrand(data, _search);
        });
    });
    // 根据关键字查询
    $('.search[shoptype="search"]').find('a').click(function () {
        var _url = $(this).parents('.brand-index:first').attr('data-url');
        var _tid = $(this).parents('.brand-index:first').attr('data-tid');
        var _keyword = $('#search_brand_keyword').val();
        $.getJSON(_url, {type: 'keyword', tid: _tid, keyword: _keyword}, function (data) {
            insertBrand(data, _keyword);
        });
    });
    // 选择品牌
    $('ul[shoptype="brand_list"]').on('click', 'li', function () {
        $(this).parents('.ecsc-brand-select-container').prev().children('input[name=b_id]').val($(this).attr('data-id'));
        $(this).parents('.ecsc-brand-select-container').prev().children('input[name=b_name]').val($(this).attr('data-name'));
        $('.ecsc-brand-select > .ecsc-brand-select-container').hide();
    });

    // 选择品牌
    $(document).on('click', 'input[name="brand_name"]', function () {
        $(".brand-select-container").hide();
        $(this).parents(".selection").next(".brand-select-container").show();
        $(".brand-list").perfectScrollbar("destroy");
        $(".brand-list").perfectScrollbar();
    });

    // 选择品牌（关联品牌）
    $(document).on('click', 'input[data-filter="brand_name"]', function () {
        $(".brand-select-container").hide();
        $(this).parents(".selection").next(".brand-select-container").show();
        $(".brand-list").perfectScrollbar("destroy");
        $(".brand-list").perfectScrollbar();
    });

    /* AJAX选择品牌 */
    // 根据首字母查询
    // $(document).on('click', '.letter a[data-letter]', function () {
    //     var goods_id = $("input[name=goods_id]").val();
    //     var ru_id = $("input[name=ru_id]").val();
    //     var letter = $(this).attr('data-letter');
    //     $(".brand-not strong").html(letter);
    //     $.jqueryAjax('get_ajax_content.php', 'act=search_brand_list&goods_id=' + goods_id + '&ru_id=' + ru_id + '&letter=' + letter, function (data) {
    //         if (data.content) {
    //             $(".brand-list").html(data.content);
    //             $(".brand-not").hide();
    //         } else {
    //             $(".brand-list").html("");
    //             $(".brand-not").show();
    //         }
    //         $(".brand-list").perfectScrollbar("destroy");
    //         $(".brand-list").perfectScrollbar();
    //     });
    // });

    // 根据关键字查询
    // $(document).on('click', '.b_search a', function () {
    //     var goods_id = $("input[name=goods_id]").val();
    //     var ru_id = $("input[name=ru_id]").val();
    //     var keyword = $(this).prev().val();
    //     $(".brand-not strong").html(keyword);
    //     $.jqueryAjax('get_ajax_content.php', 'act=search_brand_list&goods_id=' + goods_id + '&ru_id=' + ru_id + '&keyword=' + keyword, function (data) {
    //         if (data.content) {
    //             $(".brand-list").html(data.content);
    //             $(".brand-not").hide();
    //         } else {
    //             $(".brand-list").html("");
    //             $(".brand-not").show();
    //         }
    //         $(".brand-list").perfectScrollbar("destroy");
    //         $(".brand-list").perfectScrollbar();
    //     });
    // });

    // 选择品牌
    $(document).on('click', '.brand-list li', function () {
        // $(this).parents('.brand-select-container').prev().find('input[data-filter=brand_id]').val($(this).data('id'));
        // $(this).parents('.brand-select-container').prev().find('input[data-filter=brand_name]').val($(this).data('name'));
        $('.brand-select-container').hide();
    });

    // 添加店铺分类
    $("#add_sgcategory").unbind().click(function () {
        $(".sgcategory:last").after($(".sgcategory:last").clone(true).val(0));
    });
    // 选择店铺分类
    $('.sgcategory').unbind().change(function () {
        var _val = $(this).val();       // 记录选择的值
        $(this).val('0');               // 已选择值清零
        // 验证是否已经选择
        if (!checkSGC(_val)) {
            alert('该分类已经选择,请选择其他分类');
            return false;
        }
        $(this).val(_val);              // 重新赋值
    });

    // 验证店铺分类是否重复
    function checkSGC($val) {
        var _return = true;
        $('.sgcategory').each(function () {
            if ($val != 0 && $val == $(this).val()) {
                _return = false;
            }
        });
        return _return;
    }

    // 选择商品
    $('div[shoptype="combo_goods_list"]').on('click', 'a[shoptype="a_choose_goods"]', function () {
        _owner_gid = $(this).parents('.ecsc-form-goods-combo:first').attr('data-gid');
        eval('var data_str = ' + $(this).attr('data-param'));
        _li = $('<li></li>')
            .append('<input type="hidden" value="' + data_str.gid + '">')
            .append('<div class="pic-thumb"> <a target="_blank" href="' + data_str.gurl + '"> <img src="' + data_str.gimage240 + '"> </a> </div>')
            .append('<dl><dt><a target="_blank" href="' + data_str.gurl + '">' + data_str.gname + '</a></dt><dd>￥' + data_str.gprice + '</dd></dl>')
            .append('<a class="ecsc-btn-mini ecsc-btn-red" shoptype="del_choosed" href="javascript:void(0);"><i class="icon-ban-circle"></i>取消推荐</a>');
        $(this).parents('.ecsc-form-goods-combo:first').find('[shoptype="choose_goods_list"] > ul').append(_li);
        $(this).removeClass('ecsc-btn-green').addClass('ecsc-btn-grey');
        $(this).removeAttr('shoptype');
        func();
    });

    // 删除
    $('div[shoptype="choose_goods_list"]').on('click', 'a[shoptype="del_choosed"]', function () {
        $(this).parents('li:first').remove();
        func();
    });

    // 定时发布时间
    $('input[name="g_state"]').click(function () {
        if ($(this).attr('shoptype') == 'auto') {
            $('#starttime').removeAttr('disabled').css('background', '');
            $('#starttime_H').removeAttr('disabled').css('background', '');
            $('#starttime_i').removeAttr('disabled').css('background', '');
        } else {
            $('#starttime').attr('disabled', 'disabled').css('background', '#E7E7E7 none');
            $('#starttime_H').attr('disabled', 'disabled').css('background', '#E7E7E7 none');
            $('#starttime_i').attr('disabled', 'disabled').css('background', '#E7E7E7 none');
        }
    });

    //图片空间读取图片
    $('a[shoptype="select-0"]').click(function () {
        $(this).hide();
        $(this).next().show();
        $('div[shoptype="album-0"]').show();
    });
    $('a[shoptype="close_album"]').click(function () {
        $(this).hide();
        $(this).prev().show();
        $('div[shoptype="album-0"]').hide();
    });

    //右侧导航跟随滚动
    /*$(document).ready(function(){
        $("#mainContent").waypoint(function(event, direction) {
            $(this).parent().toggleClass('sticky', direction === "down");
            event.stopPropagation();
        });
    });*/

    //fixme 右侧tab li点击激活状态
    $('.tab li').click(function () {
        var index = $(this).index();
        var main = $(this).parents(".main-content");
        $(this).addClass("active").siblings().removeClass("active");
        main.find('.items-info .wrapper-list').eq(index).show().siblings('.wrapper-list').hide();
    });

    //div仿select下拉选框 start
    $(document).on("click", ".imitate_select .cite", function () {
        $(".imitate_select ul").hide();
        $(this).parents(".imitate_select").find("ul").show();
        $(this).siblings("ul").perfectScrollbar("destroy");
        $(this).siblings("ul").perfectScrollbar();
    });
    //select下拉默认值赋值
    $('.imitate_select').each(function () {
        var sel_this = $(this);
        var val = sel_this.children('input[type=hidden]').val();
        sel_this.find('a').each(function () {
            if ($(this).attr('data-value') == val) {
                sel_this.children('.cite').html($(this).html());
            }
        });
    });
    $(document).on("click", ".imitate_select li  a", function () {
        var _this = $(this);
        var val = _this.data('value');
        var text = _this.html();
        _this.parents(".imitate_select").find(".cite").html(text);
        _this.parents(".imitate_select").find("input[type=hidden]").val(val);
        _this.parents(".imitate_select").find("ul").hide();
    });
    //div仿select下拉选框 end

    //分类选择
    $.category();

    //添加商品 分类选择
    $(document).on("click", ".sort_list li", function () {
        $(this).addClass("current").siblings().removeClass("current");
    });

    $(".select-list,.select-list2").hover(function () {
        $(".select-list,.select-list2").perfectScrollbar();
    });

    function windowHeight() {
        var height = $(window).height();
        var header = $("header").height();
        var footer = $(".footer").outerHeight();
        var ecsc_path = $(".ecsc-path").outerHeight();
        $(".ecsc-layout-right").css("min-height", height - header - footer - ecsc_path);
        $(window).resize(function () {
            height = $(window).height();
            header = $("header").height();
            footer = $(".footer").outerHeight();
            ecsc_path = $(".ecsc-path").outerHeight();
            $(".ecsc-layout-right").css("min-height", height - header - footer - ecsc_path);
        });
    }

    //windowHeight();

    //关闭打开切换
    $(document).on("click", ".switch", function () {
        // if ($(this).hasClass("active")) {
        //     $(this).removeClass("active");
        //     $(this).next("input[type='hidden']").val(0);
        //     $(this).attr("title", "否");
        // } else {
        //     $(this).addClass("active");
        //     $(this).next("input[type='hidden']").val(1);
        //     $(this).attr("title", "是");
        // }
    });

    //jquery仿file
    $(document).on("change", ".type-file-box input[type='file']", function () {
        var val = $(this).val();
        $(this).parents(".type-file-box").find(".type-file-text").val(val);
    });
    //jquery仿file
});

//清除seller缓存
function clearCache() {
    $.ajax({
        type: 'POST',
        url: "/seller/api/clear_cache",
        success: function (data) {
            easyshow('页面缓存已清除成功');
        },
        error: function (xhr, type) {
            easyshow('操作失败');
        }
    });
}

function loadWarpper() {
    var dom = document.getElementById('loadWarpper');
    var status = dom.style.display;
    status = status == 'block' ? 'none' : 'block';
    dom.style.display = status;
}

function prototype(msg, title) {
    pb({
        id: 'idialog',
        title: title,
        content: msg,
        width: 400,
        fixed: false,
        foot: false,
        timeCozuo: true,
        time: 3
    });
    for (var i = 3; i >= 0; i--) {
        window.setTimeout('doUpdate(' + i + ')', (3 - i) * 1000);
    }
}

function doUpdate(num) {
    $("#time").html(num);
}

function get_confirm(a, b) {
    ds.dialog({
        title: '提示信息',
        content: a,
        yesText: '确定',
        onyes: function () {
            alert("你点击了确定！");
            return false;
        },
        noText: '取消',
        onno: function () {
            this.close();
        },
        icon: "question.gif",
        width: 360,
        padding: '30px 20px',
    });
}

// 选择默认主图&&删除
function selectDefaultImage($this) {
    // 默认主题
    $this.click(function () {
        $(this).parents('ul:first').find('.show-default').removeClass('selected').find('input').val('0');
        $(this).addClass('selected').find('input').val('1');
    });
    // 删除
    $this.parents('li:first').find('a[shoptype="del"]').click(function () {
        $this.unbind('click').removeClass('selected').find('input').val('0');
        $this.prev().find('input').val('').end().find('img').attr('src', DEFAULT_GOODS_IMAGE);
    });
}

//相关产品添加删除方法
function func() {
    var li = $('*[shoptype="choose_goods_list"]').find("li");
    var a = $(".goods-list").find("a.ecsc-btn-mini");
    li.children('a[shoptype="del_choosed"]').click(function () {
        var val = $(this).prevAll("input").attr("value");
        $(".goods-list").find("li").each(function (i) {
            eval("var val2=" + $(this).find("a.ecsc-btn-mini").attr("data-param"));
            if (val == val2.gid) {
                a.eq(i).removeClass('ecsc-btn-grey').addClass('ecsc-btn-green');
                a.eq(i).attr('shoptype', 'a_choose_goods');
            }
        });
    });
}

//导航栏鼠标移上左右弹性滑动
function huadong() {
    var $navcur = $(".nav-current");
    var $nav = $(".ecsc-nav");
    var itemW = $nav.find(".current").innerWidth();	//默认当前位置宽度
    var defLeftW = $nav.find(".current").position().left;	//默认当前位置Left值
    //添加默认下划线
    $navcur.css({width: itemW, left: defLeftW});
    //hover事件
    $nav.find("li[shoptype='item']").hover(function () {
        var index = $(this).index();	//获取滑过元素索引值
        var leftW = $(this).position().left;	//获取滑过元素Left值
        var currentW = $nav.find("li[shoptype='item']").eq(index).innerWidth();	//获取滑过元素Width值
        $(this).children("ul").show();
        $navcur.stop().animate({
            left: leftW,
            width: currentW
        }, 300);
    }, function () {
        $(this).children("ul").hide();
        $navcur.stop().animate({
            left: defLeftW,
            width: itemW
        }, 300);
    });
}

//移动到订单号悬浮展示订单详情方法
function orderLevitate(over, layer, top, left) {
    var hoverTimer, outTimer, hoverTimer2;
    //var left = $('.'+over).position().left + $('.'+over).outerWidth() + 30;
    $(document).on('mouseenter', '.' + over, function () {
        var content = $("#order_goods_layer").html();
        var order_goods_layer = $(document.createElement('div')).addClass(layer);
        var $this = $(this);
        clearTimeout(outTimer);
        hoverTimer = setTimeout(function () {
            $(".order_goods_layer").remove();
            $this.parent().css("position", "relative");
            order_goods_layer.html(content);
            order_goods_layer.css({"left": left, "top": -top});
            $this.after(order_goods_layer);
        }, 200);
    });

    $(document).on('mouseleave', '.' + over, function () {
        clearTimeout(hoverTimer);
        outTimer = setTimeout(function () {
            $('.' + layer).remove();
        }, 100);
    });

    $(document).on('mouseenter', '.' + layer, function () {
        clearTimeout(outTimer);
    });

    $(document).on('mouseleave', '.' + layer, function () {
        $(this).remove();
    });
}

/*分类搜索的下拉列表*/
jQuery.category = function () {
    // $(document).on("click", '.selection input[id="cat_name"]', function () {
    //     $(this).parents(".selection").next('.select-container').show();
    // });
    //
    // $(document).on('click', '.select-list li', function () {
    //     var obj = $(this);
    //     var cat_name = obj.data('catname');
    //
    //     obj.parents(".categorySelect").find("input[id=cat_name]").val(cat_name);
    //
    //     var cat_level = obj.parents(".categorySelect").find(".select-top a").length; //获取分类级别
    //     if (cat_level >= 3) {
    //         $('.categorySelect .select-container').hide();
    //     }
    // });
    //点击a标签返回所选分类 by wu
    // $(document).on('click', '.select-top a', function () {
    //
    //     var obj = $(this);
    //     var cat_id = obj.data('cid');
    //     var cat_name = obj.data('cname');
    //     var cat_type_show = obj.data('show');
    //     var user_id = obj.data('seller');
    //     var url = obj.data('url');
    //     var table = obj.data('table');
    //
    //     /* 自定义导航 start */
    //     if (document.getElementById('item_name')) {
    //         $("#item_name").val(cat_name);
    //     }
    //
    //     if (document.getElementById('item_url')) {
    //         $("#item_url").val(url);
    //     }
    //
    //     if (document.getElementById('item_catId')) {
    //         $("#item_catId").val(cat_id);
    //     }
    //     /* 自定义导航 end */
    //
    //     // $.jqueryAjax('get_ajax_content.php', 'act=filter_category&cat_id=' + cat_id + "&cat_type_show=" + cat_type_show + "&user_id=" + user_id + "&table=" + table, function (data) {
    //     //     if (data.content) {
    //     //         obj.parents(".categorySelect").find("input[data-filter=cat_name]").val(data.cat_nav); //修改cat_name
    //     //         obj.parents(".select-container").html(data.content);
    //     //         $(".select-list").perfectScrollbar("destroy");
    //     //         $(".select-list").perfectScrollbar();
    //     //     }
    //     // });
    //     obj.parents(".categorySelect").find("input[data-filter=cat_id]").val(cat_id); //修改cat_id
    // });
    /*分类搜索的下拉列表end*/
};

// 高级搜索边栏动画
jQuery.gjSearch = function (right) {
    $('#searchBarOpen').click(function () {
        $('.search-gao-list').animate({'right': '-40px'}, 200,
            function () {
                $('.search-gao-bar').animate({'right': '0'}, 300);
            });
    });
    $('#searchBarClose').click(function () {
        $('.search-gao-bar').animate({'right': right}, 300,
            function () {
                $('.search-gao-list').animate({'right': '0'}, 200);
            });
    });
};
// 高级搜索边栏动画end

//jq仿select -- 带返回函数的
/*jQuery.divselect = function(divselectid,inputselectid,fn) {
	var inputselect = $(inputselectid);
	$(document).on('click',divselectid+" .cite",function(event){
		$(".imitate_select").find("ul").hide();
		event.stopImmediatePropagation();
		var ul = $(divselectid+" ul");
		if(ul.css("display")=="none"){
			ul.css("display","block");
		}else{
			ul.css("display","none");
		}
		$(this).siblings("ul").perfectScrollbar("destroy");
		$(this).siblings("ul").perfectScrollbar();
	});
	$(document).on("click",divselectid+" ul li a",function(event){
		event.stopImmediatePropagation();
		var txt = $(this).text();
		$(divselectid+" .cite").html(txt);
		var value = $(this).data("value");
		inputselect.val(value);
		$(divselectid+" ul").hide();
		if(fn){
			fn($(this));
		}
	});
	$(document).on("click",function(){
		$(divselectid+" ul").hide();
	});
};*/

//新版商家后台js
$(function () {
    //是否商品活动切换
    // $(".goods_activity .checkbox_item input[type='radio'],.goods_special .checkbox_item input[type='radio']").on("click", function () {
    //     var special_div = $(this).parents(".checkbox_items").next(".special_div");
    //     var hidden_div = $(this).parents(".checkbox_items").find(".hidden_div");
    //     if ($(this).is(":checked")) {
    //         var val = $(this).val();
    //         if (val == 1) {
    //             special_div.show();
    //             hidden_div.show();
    //         } else {
    //             special_div.hide();
    //             hidden_div.hide();
    //         }
    //     }
    // });

    /*************************************商品添加/编辑页面start****************************************/
    /* 属性模式设置 by wu start */
    $(document).on("click", "#attribute_warehouse input", function () {
        var warehouse_id = $(this).val();
        var warehouse_obj = $("#attribute_region .value[data-wareid=" + warehouse_id + "]");
        warehouse_obj.show();
        warehouse_obj.siblings(".value").hide();
        warehouse_obj.find("input[type=radio]:first").prop("checked", true);

        var goods_id = $("input[name='goods_id']").val();
        set_attribute_table(goods_id);
    });

    if ($("#attribute_city_region").length > 0) {
        $(document).on("click", "#attribute_city_region input", function () {
            var goods_id = $("input[name='goods_id']").val();
            set_attribute_table(goods_id, 3);
        });
    } else {
        $(document).on("click", "#attribute_region input", function () {
            var goods_id = $("input[name='goods_id']").val();
            set_attribute_table(goods_id, 3);
        });
    }

    /* 属性模式设置 by wu end */

    //属性选中效果 by wu start
    // $(document).on("click", "#tbody-goodsAttr .checkbox_items label", function () {
    //     if ($(this).siblings("input[type=checkbox]:first").prop("checked") == true) {
    //         $(this).siblings("input[type=checkbox]:first").prop("checked", false);
    //     } else {
    //         $(this).siblings("input[type=checkbox]:first").prop("checked", true);
    //     }
    //     var goods_id = $("input[name='goods_id']").val();
    //     set_attribute_table(goods_id);
    // });
    //属性选中效果 by wu end
    /*************************************商品添加/编辑页面end****************************************/


    /*************************************商品搜索/关联start****************************************/
    //筛选搜索商品/文章/其他列表 by wu start
    $("a[shoptype='search']").on("click", function () {
        var obj = $(this);
        var step = $(this).parents(".step[shoptype=filter]:first");
        var search_type = step.data("filter");
        var search_data = "";
        var search_div = $(this).parents(".goods_search_div:first"); //搜索div
        if (search_type == "goods") {
            //商品筛选
            //var search_div = $(this).parents(".goods_search_div");
            var cat_id = search_div.find("input[data-filter=cat_id]").val();
            var brand_id = search_div.find("input[data-filter=brand_id]").val();
            var keyword = search_div.find("input[data-filter=keyword]").val();
            var limit_start = search_div.find("input[data-filter=limit_start]").val();
            var limit_number = search_div.find("input[data-filter=limit_number]").val();
            if (limit_start != null && typeof limit_start != "undefined") {
                search_data += "&limit_start=" + limit_start;
            }
            if (limit_number != null && typeof limit_number != "undefined") {
                search_data += "&limit_number=" + limit_number;
            }
            if (cat_id) {
                search_data += "&cat_id=" + cat_id;
            }
            if (brand_id) {
                search_data += "&brand_id=" + brand_id;
            }
            if (keyword) {
                search_data += "&keyword=" + keyword;
            }
        } else if (search_type == "article") {
            //文章筛选
            //var search_div = $(this).parents(".goods_search_div");
            var keyword = search_div.find("input[data-filter=keyword]").val();
            if (keyword) {
                search_data += "&keyword=" + keyword;
            }
        } else if (search_type == "area") {
            //地区筛选
            //var search_div = $(this).parents(".goods_search_div");
            var keyword = search_div.find("input[data-filter=keyword]").val();
            if (keyword) {
                search_data += "&keyword=" + keyword;
            }
        } else if (search_type == "goods_type") {
            //商品类型
            //var search_div = $(this).parents(".goods_search_div");
            var keyword = search_div.find("input[data-filter=keyword]").val();
            if (keyword) {
                search_data += "&keyword=" + keyword;
            }
        } else if (search_type == "get_content") {
            //商品筛选
            var cat_id = search_div.find("input[data-filter=cat_id]").val();
            var brand_id = search_div.find("input[data-filter=brand_id]").val();
            var keyword = search_div.find("input[data-filter=keyword]").val();
            if (cat_id) {
                search_data += "&cat_id=" + cat_id;
            }
            if (brand_id) {
                search_data += "&brand_id=" + brand_id;
            }
            if (keyword) {
                search_data += "&keyword=" + keyword;
            }
        }

        step.find(".move_left .move_list:first,.move_all .move_list:first").html('<i class="icon-spinner icon-spin"></i>');

        function ajax() {
            $.jqueryAjax('get_ajax_content.php', 'act=filter_list&search_type=' + search_type + search_data, function (data) {
                step.find(".move_left .move_list:first, .move_all .move_list:first").html(data.content);
                step.find(".move_left .move_list , .move_all .move_list").perfectScrollbar('destroy');
                step.find(".move_left .move_list, .move_all .move_list").perfectScrollbar();
            });
        }

        setTimeout(function () {
            ajax();
        }, 300);
    });
    //筛选搜索商品/文章/其他列表 by wu end

    /* 关联设置效果 by wu start */

    //选中状态切换
    $(document).on("click", ".move_info li", function () {
        if ($(this).hasClass('current')) {
            $(this).removeClass("current");
        } else {
            $(this).addClass("current");
        }
    });

    //全选状态切换
    $(document).on("click", "a[shoptype=moveAll]", function () {
        if ($(this).hasClass('checked')) {
            $(this).removeClass('checked');
            $(this).parent().prev().find("li").removeClass("current");
        } else {
            $(this).addClass('checked');
            $(this).parent().prev().find("li").addClass("current");
        }
    });

    //确定操作
    $(document).on("click", "a[shoptype=sub]", function () {
        var obj = $(this);
        var goods_id = $("input[name=goods_id]").val();
        var length = $(this).parent().prev().find("li.current").length; //选中项数量
        var step = $(this).parents(".step[shoptype=filter]:first");
        step.find(".move_list").perfectScrollbar();

        var dealType = 0; //处理类型，0为ajax处理（默认），1为js处理
        if (length == 0) {
            alert("请选择列表中的项目");
            return;
        }

        var operation = $(this).data("operation"); //操作类型
        //将所有值插入数组传递
        var value = new Array();
        length = $(this).parent().prev().find("li.current").each(function () {
            value.push($(this).data("value"));
        });
        var extension = ""; //扩展数据

        //添加关联商品
        if (operation == "add_link_goods") {
            var is_single = $(this).parents(".step[shoptype=filter]").find("input[name=is_single]:checked").val(); //单向关联还是双向关联
            extension += "&is_single=" + is_single;
        }

        //添加配件
        if (operation == "add_group_goods") {
            var group2 = $(this).parents(".step[shoptype=filter]").find("input[name=group2]:checked").val();
            var price2 = $(this).parents(".step[shoptype=filter]").find("input[name=price2]").val();
            extension += "&group2=" + group2 + "&price2=" + price2;
        }

        //添加关联文章
        if (operation == "add_goods_article") {
        }

        //添加关联地区
        if (operation == "add_area_goods") {
        }

        //添加橱窗商品
        if (operation == "add_win_goods") {
            var win_id = $("input[name=win_id]").val();
            extension += "&win_id=" + win_id;
        } else if (operation == "drop_win_goods") { //删除橱窗商品
            var win_id = $("input[name=win_id]").val();
            extension += "&win_id=" + win_id;
        }

        //添加统一详情
        if (operation == "add_link_desc") {
            var id = $("input[name=id]").val();
            extension += "&id=" + id;
        } else if (operation == "drop_link_desc") { //删除统一详情
            var id = $("input[name=id]").val();
            extension += "&id=" + id;
        }

        //设置楼层品牌
        if (operation == "add_floor_content") {
            var group = $(this).parents(".step[shoptype=filter]").find("input[name=group2]").val();
            extension += "&group=" + group;
        }
        //商品批量修改，商品批量导出，商品数据列
        if (operation == "add_edit_goods" || operation == "add_export_goods" || operation == "add_goods_fields") {
            dealType = 1;
            $(this).parent().prev().find("li.current").each(function () {
                //检查是否重复
                var thisCatId = $(this).data("value");
                var thisCatExist = obj.parents(".step[shoptype=filter]:first").find(".move_right .move_list ul:first li[data-value=" + thisCatId + "]").length;
                if (!thisCatExist) {
                    var thisCat = $(this).clone();
                    thisCat.find("i").removeClass("sc_icon_ok").addClass("sc_icon_no");
                    obj.parents(".step[shoptype=filter]:first").find(".move_right .move_list ul:first").append(thisCat);
                }
            });
        } else if (operation == "drop_edit_goods" || operation == "drop_export_goods" || operation == "drop_goods_fields") { //删除关联地区
            dealType = 1;
            $(this).parent().prev().find("li.current").remove();
        }

        if (dealType == 0) {
            $.jqueryAjax("get_ajax_content.php", "act=" + operation + "&goods_id=" + goods_id + "&value=" + value + extension, function (data) {
                obj.parents(".step[shoptype=filter]").find(".move_right .move_list").html(data.content);

                obj.parents(".step[shoptype=filter]").find(".move_right .move_list").perfectScrollbar('destroy');
                obj.parents(".step[shoptype=filter]").find(".move_right .move_list").perfectScrollbar();
            });
        }
    });

    $(".move_middle .move_point").on("click", function () {
        var obj = $(this);
        var goods_id = $("input[name=goods_id]").val();
        var dealType = 0;
        var operation = $(this).data("operation");
        var extension = "";
        var thisCatExist = 0;
        var i = 0;
        var thisCatId = "";
        var li = obj.parents(".move_middle").prev().find("li.current");
        var value = new Array();

        if (operation == "add_edit_goods" || operation == "add_export_goods" || operation == "add_goods_fields") {
            dealType = 1;
        }

        //添加橱窗商品
        if (operation == "add_win_goods") {
            var win_id = $("input[name=win_id]").val();
            extension += "&win_id=" + win_id;
        } else if (operation == "drop_win_goods") { //删除橱窗商品
            var win_id = $("input[name=win_id]").val();
            extension += "&win_id=" + win_id;
        }

        //添加统一详情
        if (operation == "add_link_desc") {
            var id = $("input[name=id]").val();
            extension += "&id=" + id;
        } else if (operation == "drop_link_desc") { //删除统一详情
            var id = $("input[name=id]").val();
            extension += "&id=" + id;
        }

        if (li.length > 0) {
            li.each(function () {
                value.push($(this).data("value"));

                thisCatId = $(this).data("value");
                thisCatExist = obj.parents(".step[shoptype=filter]:first").find(".move_right .move_list li[data-value=" + thisCatId + "]").length;
                if (thisCatExist > 0) {
                    i++;
                }
                if (dealType == 1 && !thisCatExist) {
                    var thisCat = $(this).clone();
                    thisCat.find("i").removeClass("sc_icon_ok").addClass("sc_icon_no");
                    obj.parents(".step[shoptype=filter]:first").find(".move_right .move_list ul:first").append(thisCat);
                }
            });
        } else {
            alert("请选择列表中的项目");
            return;
        }

        if (i > 0) {
            alert("您选择的类目有重复选项请查看清楚在选择");
            return;
        }

        if (dealType == 0) {
            $.jqueryAjax("get_ajax_content.php", "act=" + operation + "&goods_id=" + goods_id + "&value=" + value + extension, function (data) {
                obj.parents(".step[shoptype=filter]").find(".move_right .move_list").html(data.content);

                obj.parents(".step[shoptype=filter]").find(".move_right .move_list").perfectScrollbar('destroy');
                obj.parents(".step[shoptype=filter]").find(".move_right .move_list").perfectScrollbar();
            });
        }
    });

    /* 关联设置效果 by wu end */
    /*************************************商品搜索/关联end****************************************/

    /****************************************其他start******************************************/

    //上传图片类型
    $('input[class="type-file-file"]').change(function () {
        var state = $(this).data('state');
        var filepath = $(this).val();
        var extStart = filepath.lastIndexOf(".");
        var ext = filepath.substring(extStart, filepath.length).toUpperCase();

        if (state == 'txtfile') {
            if (ext != ".TXT") {
                alert("上传文件限于txt格式");
                $(this).attr('value', '');
                return false;
            }
        } else if (state == 'imgfile') {
            if (ext != ".PNG" && ext != ".GIF" && ext != ".JPG" && ext != ".JPEG") {
                alert("上传图片限于png,gif,jpeg,jpg格式");
                $(this).attr('value', '');
                return false;
            }
        } else if (state == 'csvfile') {
            if (ext != ".CSV") {
                alert("上传文件限于csv格式");
                $(this).attr('value', '');
                return false;
            }
        }
    });

    //file移动上去的js
    $(".type-file-box").hover(function () {
        $(this).addClass("hover");
    }, function () {
        $(this).removeClass("hover");
    });

    //listTable span eidt 点击修改
    $(document).on("click", "*[shoptype='editSpanInput'] .icon-edit", function () {
        $(this).siblings("span").click();
    });

    /****************************************其他end********************************************/

    /*点击充值后清空页面错误提示*/
    $("input[type='reset']").on("click", function () {
        $(this).parents("form").find(".text").removeClass('error');
        $(this).parents("form").find(".form_prompt").html('');
    });
});

//添加阶梯价格 有删除
function add_clonetd(obj, type) {
    var obj = $(obj);
    var table = obj.parents(".table_div");
    var number_td = obj.parent().prev();
    var price_tr = obj.parents("tr").next();
    var price_td = price_tr.find("td:last-child");
    var handle_tr = obj.parents("tr").siblings().last();
    var handle_td = handle_tr.find("td:last-child");

    var copy_number_td = number_td.clone();
    var copy_price_td = price_td.clone();
    var copy_handle_td = handle_td.clone();
    var td_num = table.find(".td_num").length;

    var number = 3;

    if (obj.parents("*[shoptype='volumeNumber']").length > 0) {
        number = obj.parents("*[shoptype='volumeNumber']").data("number");
    }

    if (td_num < number && td_num != 0) {
        copy_number_td.find(".text").val("");
        copy_price_td.find(".text").val("");
        number_td.after(copy_number_td);
        price_tr.append(copy_price_td);
        handle_tr.append(copy_handle_td);
    } else if (td_num == 0) {
        if (type == "mj") {
            number_td.after("<td><input type='text' name='cfull[]' value='' class='text w50 td_num'><input type='hidden' name='c_id[]' value='0' autocomplete='off'></td>");
            price_tr.append("<td><input type='text' name='creduce[]' value='' class='text w50'></td>");
        } else {
            number_td.after("<td><input type='text' name='volume_number[]' value='' class='text w50 td_num'><input type='hidden' name='id[]' value='0' autocomplete='off'></td>");
            price_tr.append("<td><input type='text' name='volume_price[]' value='' class='text w50'></td>");
        }
        handle_tr.append("<td><a href='javascript:;' class='sc-btn btn25 sc-blueBg-btn' data-id='0' shoptype='remove_volume'>删除</a></td>");
    } else {
        pbDialog("阶梯价格设置不能超过" + number + "个", "", 0);
    }
}

//添加阶梯价格 无删除
function add_clonetd_two(obj, type) {
    var obj = $(obj);
    var number_td = obj.parent().prev();
    var price_tr = obj.parents("tr").next();
    var price_td = price_tr.find("td:last-child");

    var copy_number_td = number_td.clone();
    var copy_price_td = price_td.clone();


    copy_number_td.find(".text").val("");
    copy_price_td.find(".text").val("");
    number_td.after(copy_number_td);
    price_tr.append(copy_price_td);
    if (type == null) {
        var handle_tr = obj.parents("tr").siblings().last();
        var handle_td = handle_tr.find("td:last-child");
        var copy_handle_td = handle_td.clone();
        handle_tr.append(copy_handle_td);
    }
}

/////////////////////////////
/////   FIXME 新增      /////
////////////////////////////
function clickEdit(obj, param, callback) {
    var tag = obj.firstChild.tagName;

    if (typeof (tag) != "undefined" && tag.toLowerCase() == "input") {
        return;
    }

    /* 保存原始的内容 */
    var org = obj.innerHTML;
    var val = window.ActiveXObject ? obj.innerText : obj.textContent;

    /* 创建一个输入框 */
    var txt = document.createElement("INPUT");
    txt.value = (val == 'N/A') ? '' : val;
    txt.className = 'text textpadd';
    if (obj.offsetWidth > 12) {
        txt.style.width = (obj.offsetWidth - 12) + "px";
    } else {
        txt.style.width = obj.offsetWidth + "px";
    }
    if ($(obj).siblings(".edit_icon")) {
        $(obj).siblings(".edit_icon").hide();
    }
    /* 隐藏对象中的内容，并将输入框加入到对象中 */
    obj.innerHTML = "";
    obj.appendChild(txt);
    txt.focus();

    /* 编辑区输入事件处理函数 */
    txt.onkeypress = function (e) {
        var evt = (typeof e == "undefined") ? window.event : e;
        var obj = document.all ? evt.srcElement : evt.target;

        if (evt.keyCode == 13) {
            obj.blur();
            return false;
        }

        if (evt.keyCode == 27) {
            obj.parentNode.innerHTML = org;
        }
    };

    txt.onblur = function (e) {
        if (trim(txt.value).length > 0 && txt.value != org) {
            if (typeof callback == 'function') {
                param.value = txt.value;
                callback(param, function (res) {
                    if (res.msg) {
                        layer.msg(res.msg, {time: 2000});
                    }
                    obj.innerHTML = (res.msg) ? org : res.content;
                });
            }
        } else {
            obj.innerHTML = org;
        }
    };
}

function getUrlParam(name) {
    var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)");
    var r = window.location.search.substr(1).match(reg);
    var strValue = "";
    if (r != null) {
        strValue = unescape(r[2]);
    }
    return strValue;
}

function easyshow(msg, title) {
    title = title ? title : '提示';
    msg = msg ? msg : '操作成功';
    var dom = document.getElementsByClassName('footer')[0];
    $.messager.show({
        title: title,
        msg: msg,
        timeout: 3000,
        showType: 'slide',
        // style: {
        //     bottom: -1 * (dom.offsetHeight + dom.offsetTop),
        // }
    });
}
