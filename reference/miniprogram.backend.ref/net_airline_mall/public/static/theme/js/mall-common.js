/* 全局变量 */
var user_id = $("input[name='user_id']").val(), //会员ID
    goods_id = 0,	//商品ID
    ru_id = 0,		//商家ID
    store_id = 0,	//门店ID
    hoverTimer = '',
    outTimer = '',
    doc = $(document);
var json_languages = {
    'pageparmALLneed': '请填写所有参数',
    "ok": "确定",
    "determine": "确定",
    "cancel": "取消",
    "drop": "删除",
    "edit": "编辑",
    "remove": "移除",
    "follow": "关注",
    "pb_title": "提示",
    "Prompt_information": "提示信息",
    "title": "提示",
    "not_login": "您尚未登录",
    "close": "关闭",
    "cart": "购物车",
    "js_cart": "购物车",
    "all": "全部",
    "go_login": "去登陆",
    "select_city": "请选择市",
    "comment_goods": "评论商品",
    "submit_order": "提交订单",
    "sys_msg": "系统提示",
    "no_keywords": "请输入搜索关键词！",
    "adv_packup_one": "请去后台广告位置",
    "adv_packup_two": "里面设置广告！",
    "more": "更多",
    "Please": "请去",
    "set_up": "设置！",
    "login_phone_packup_one": "请输入手机号码",
    "more_options": "更多选项",
    "Pack_up": "收起",
    "no_attr": "没有更多属性了",
    "search_Prompt": "可输入汉字,拼音查找品牌",
    "most_input": "最多只能选择5项",
    "multi_select": "多选",
    "checkbox_Packup": "请收起全部多选",
    "radio_Packup": "请收起全部单选",
    "contrast": "对比",
    "empty_contrast": "清空对比栏",
    "Prompt_add_one": "最多只能添加4个哦^_^",
    "Prompt_add_two": "您还可以继续添加",
    "button_compare": "比较选定商品",
    "exist": "您已经选择了%s",
    "count_limit": "最多只能选择4个商品进行对比",
    "goods_type_different": "%s和已选择商品类型不同无法进行对比",
    "compare_no_goods": "您没有选定任何需要比较的商品或者比较的商品数少于 2 个。",
    "btn_buy": "购买",
    "is_cancel": "取消",
    "select_spe": "请选择商品属性",
    "Country": "请选择所在国家",
    "Province": "请选择所在省份",
    "City": "请选择所在市",
    "District": "请选择所在区域",
    "Street": "请选择所在街道",
    "Detailed_address_null": "详细地址不能为空",
    "consignee": "请填写收货人信息",
    "Select_attr": "请选择属性",
    "Focus_prompt_one": "您已关注该店铺！",
    "Focus_prompt_login": "您尚未登录商城会员，不能关注！",
    "Focus_prompt_two": "登录商城会员。",
    "store_focus": "店铺关注。",
    "Focus_prompt_three": "您确实要关注所选店铺吗？",
    "Focus_prompt_four": "您确实要取消关注店铺吗？",
    "Focus_prompt_five": "您要关注该店铺吗？",
    "Purchase_quantity": "超过限购数量.",
    "My_collection": "我的收藏",
    "shiping_prompt": "暂不支持配送",
    "Have_goods": "有货",
    "No_goods": "无货",
    "No_shipping": "无法配送",
    "Deliver_back_order": "下单后立即发货",
    "Time_delivery": " 时发货",
    "goods_over": "此商品暂时售完",
    "Stock_goods_null": "商品库存不足",
    "purchasing_prompt_two": "对不起，该商品已经累计超过限购数量",
    "day_not_available": "当日无货",
    "day_yes_available": "当日有货",
    "Already_buy": "已购买",
    "Already_buy_two": "件商品达到限购条件,无法再购买",
    "Already_buy_three": "件该商品,只能再购买",
    "goods_buy_empty_p": "商品数量不能少于1件",
    "goods_number_p": "商品数量必须为数字",
    "search_one": "请填写筛选价格",
    "search_two": "请填写筛选左边价格",
    "search_three": "请填写筛选右边价格",
    "search_four": "左边价格不能大于或等于右边价格",
    "jian": "件",
    "letter": "件",
    "inventory": "存货",
    "move_collection": "移至我的收藏",
    "select_shop": "请选择套餐商品",
    "Parameter_error": "参数错误",
    "screen_price": "请填写筛选价格",
    "screen_price_left": "请填写筛选左边价格",
    "screen_price_right": "请填写筛选右边价格",
    "screen_price_dy": "左边价格不能大于或等于右边价格",
    "invoice_ok": "选择此发票模板",
    "invoice_desc_null": "输入内容不能为空！",
    "invoice_desc_number": "您最多可以添加3个公司发票！",
    "invoice_packup": "请选择或填写发票抬头部分！",
    "invoice_tax_null": "请填写纳税人识别码",
    "add_address_10": "最多只能添加10个收货地址",
    "msg_phone_not": "手机号码不正确",
    "msg_phone_blank": "手机号码不能为空",
    "captcha_not": "验证码不能为空",
    "captcha_xz": "请输入4位数的验证码",
    "captcha_cw": "验证码错误",
    "Detailed_map": "详细地图",
    "email_error": "邮箱格式不正确！",
    "bid_prompt_null": "价格不能为空!",
    "bid_prompt_error": "价格输入格式不正确！",
    "mobile_error_goods": "手机格式不正确！",
    "null_email_goods": "邮箱不能为空",
    "select_store": "请选择门店！",
    "Product_spec_prompt": "请选择商品规格类型",
    "reply_desc_one": "回复帖子内容不能为空",
    "go_shoping": "去购物",
    "loading": "正在拼命加载中...",
    "purchasing_minamount": "对不起，购买数量不能小于最小阶梯价",
    "no_history": "您已清空最近浏览过的商品",
    "emailInfo_incompleted": "您的邮箱信息未认证，进入用户中心<a href='user.php?act=profile' class='red' target='_blank'>完善邮箱信息</a>",
    "receive_coupons": "领取优惠券",
    "Immediate_use": "立即使用",
    "no_enabled": "关闭",
    "highest_price": "已是最高价！",
    "lowest_price": "已是最低价！",
    "Purchase_restrictions": "购买数量不能少于1件",
    "remove_checked_goods": "删除选中的商品",
    "go_up": "继续",
    "back_cart": "返回购物车",
    "save": "保存",
    "delivery_Prompt": "该区域没有提货点!",
    "delivery_Prompt_two": "请选择提货时间段!",
    "checked_address": "请选择收货地址!",
    "no_store": "该地区没有门店!",
    "buy_more": "最多领取",
    "a_goods": "个商品",
    "drop_goods": "删除商品？",
    "drop_desc": "您可以选择移到收藏，或删除商品。",
    "Move_collection": "移到收藏",
    "Move_desc": "移动后选中商品将不在购物车中显示。",
    "confirm_default_address": "您确定要设置为默认收货地址吗？",
    "confirm_drop_address": "您确定要删除该收货地址吗？",
    "please_checked_address": "您还没有选择收货地址！",
    "cart_empty_goods": "您的购物车中没有商品！",
    "confirm_Move_collection": "移动后选中商品将不在购物车中显示！",
    "Shipping_address": "收货地址",
    "add_shipping_address": "添加收货地址",
    "no_delivery": "暂不支持该地区配送。",
    "delivery_information": "配送信息",
    "pay_password_packup_null": "支付密码不能为空！",
    "pay_password_packup_error": "您的支付密码有误！",
    "flow_no_payment": "您必须选定一个支付方式",
    "flow_no_shipping": "您必须选定一个配送方式",
    "Mobile_error": "手机号格式不正确！",
    "phone_error": "电话号码格式不正确！",
    "order_detail": "订单详情",
    "down_detail": "收起详情",
    "payTitle": "正在支付",
    "select_consigne": "请选择所在国家",
    "consignee_legitimate_email": "您输入的邮件地址不是一个合法的邮件地址",
    "consignee_legitimate_phone": "手机号码不合法",
    "input_Consignee_name": "请您填写收货人姓名",
    "con_Preservation": "保存收货人信息",
    "Preservation": "保存",
    "add_invoice": "新增单位发票",
    "checked_goods_number": "请勾选中商品在修改商品数量",
    "not_seller_batch_goods_num": "不同店铺的不可以批量提交门店订单",
    "payment_is_online": "在线支付"
};
$(function () {
    /************************************** 通用内容start ****************************************/
    // 顶部快捷栏 地区切换 and 网站导航
    $("*[data-shoptype='dorpdown']").hover(function () {
        $(this).addClass("hover");
    }, function () {
        $(this).removeClass("hover");
    });

    //顶部快捷栏 地区选择
    $("*[data-shoptype='dorpdown'] *[shoptype='shop-choie']").on("mouseenter", function () {
        // $("*[shoptype='region-choie-content']").html(load_cart_info);
        $.jqueryAjax('get_ajax_content.php', 'act=insert_header_region', function (data) {
            if (data.content) {
                $("*[shoptype='region-choie-content']").html(data.content);
            }
        });
    });

    // 面包屑
    $(".crumbs-nav-item .menu-drop").hover(function () {
        $(this).addClass("menu-drop-open");
    }, function () {
        $(this).removeClass("menu-drop-open");
    });

    //返回顶部
    doc.on("click", "[shoptype='returnTop']", function () {
        $("body,html").animate({scrollTop: 0});
    });

    //top_banner关闭
    $("*[shoptype='close']").click(function () {
        $(this).parents(".top-banner").hide();
    });

    //底部二维码切换
    $(".help-scan .tabs li").hover(function () {
        var t = $(this);
        var index = t.index();
        t.addClass("curr").siblings().removeClass("curr");
        $(".code").find(".code_tp").eq(index).show().siblings().hide();
    });

    //价格筛选
    $(".fP-box input").click(function () {
        $('.fP-expand').show();
    });

    //价格筛选提交
    $('.ui-btn-submit').click(function () {
        var min_price = Number($(".price-min").val());
        var max_price = Number($(".price-max").val());

        if (min_price == '' && max_price == '') {
            pbDialog(json_languages.screen_price, "", 0);
            return false;
        } else if (min_price == '') {
            pbDialog(json_languages.screen_price_left, "", 0);
            return false;
        } else if (max_price == '') {
            pbDialog(json_languages.screen_price_right, "", 0);
            return false;
        } else if (min_price > max_price || min_price == max_price) {
            pbDialog(json_languages.screen_price_dy, "", 0, "", "", 70);
            return false;
        }
        $("form[name='listform']").submit();
    });

    $('.ui-btn-clear').click(function () {
        $("input[name='price_min']").val('');
        $("input[name='price_max']").val('');
    });

    //优惠活动价格筛选提交（团购、夺宝奇兵等）
    $('.ui-btn-submit').click(function () {
        $("form[name='listform']").submit();
    });

    //头部搜索
    $.inputPrompt("#keyword", true, $('#keyword').val());
    $.inputPrompt("#keyword2", true, $('#keyword2').val());

    //顶级分类展开（女装模板）
    $("*[shoptype='items'] *[shoptype='item']").on('mouseenter', function () {
        var T = $(this),
            cat_id = T.data('catid'),
            eveval = T.data('eveval'),
            layer = T.find("*[shoptype='cateLayer']"),
            defa = '';
        if (T.data('defa')) {
            defa = T.data('defa');
        }
        if (eveval != 1) {
            T.data('eveval', '1');
            /*加载中by wu*/
            layer.find("*[shoptype='subitems_" + cat_id + "']").html('<img src="/static/theme/images/loadGoods.gif" width="200" height="200" class="lazy">');
            $.ajax({
                type: "GET",
                url: "/home/category/getcatBrandList",
                data: "cat_id=" + cat_id,
                success: function (data) {
                    $("*[shoptype='subitems_" + cat_id + "']").html(data);
                }
            });

        }

        T.addClass("selected");
        layer.show();
    }).on("mouseleave", function () {
        var T = $(this), layer = T.parent().find("*[shoptype='cateLayer']");
        T.removeClass("selected");
        layer.hide();
    });

    //b2b二级导航展开
    $(".b2b-categorys-content li").hover(function () {
        var T = $(this), layer = T.find("*[shoptype='cateLayer']");
        layer.show();
    }, function () {
        var T = $(this), layer = T.find("*[shoptype='cateLayer']");
        layer.hide();
    });

    //点击空白处隐藏展开框
    $(document).click(function (e) {
        //购物车更多促销活动
        if (e.target.className != 'sales-promotion' && !$(e.target).parents("div").is("[shoptype='promInfo']")) {
            $("[shoptype='promInfo']").removeClass("prom-hover");
        }

        if (e.target.id != 'price-min' && e.target.id != 'price-max') {
            $('.fP-expand').hide();
        }

        //仿select
        if (e.target.className != 'cite' && !$(e.target).parents("div").is(".imitate_select")) {
            $('.imitate_select ul').hide();
        }

        if (e.target.id != 'btn-anchor' && !$(e.target).parents("div").is(".tb-popsku")) {
            $('.tb-popsku').hide();
        }

        //首页弹出广告
        if (e.target.className == 'ejectAdvbg' && !$(e.target).parents("div").is(".ejectAdvimg")) {
            $("*[shoptype='ejectAdv']").hide();
        }
    });

    $(".value-item").click(function () {
        $(this).addClass("selected").siblings().removeClass("selected");
    });

    //div仿select下拉选框 start
    $(document).on("click", ".imitate_select .cite", function () {
        $(".imitate_select ul").hide();
        $(this).parents(".imitate_select").find("ul").show();
        $(this).siblings("ul").perfectScrollbar("destroy");
        $(this).siblings("ul").perfectScrollbar();
    });

    $(document).on("click", ".imitate_select li  a", function () {
        var _this = $(this);
        var val = _this.data('value');
        var text = _this.html();
        _this.parents(".imitate_select").find(".cite span").html(text).css("color", "#707070");
        _this.parents(".imitate_select").find("input[type=hidden]").val(val);
        _this.parents(".imitate_select").find("ul").hide();
    });
    //div仿select下拉选框 end

    //input获得焦点加样式
    $("input.text").focus(function () {
        $(this).parents(".item").addClass("item-focus");
    });

    $("input.text").blur(function () {
        $(this).parents(".item").removeClass("item-focus");
    });

    /*****************************右侧黑色悬浮栏内容点击触发事件start***************************************/
    //移动图标出现文字提示
    $(".quick_links_panel li").hover(function () {
        $(this).find(".mp_tooltip").stop().animate({left: -92, queue: true});
        $(this).find(".mp_tooltip").css("visibility", "visible");
        $(this).find(".ibar_login_box").show();
    }, function () {
        $(this).find(".mp_tooltip").css("visibility", "hidden");
        $(this).find(".mp_tooltip").stop().animate({left: -121, queue: true});
        $(this).find(".ibar_login_box").hide();
    });

    //点击图标判断用户是否登录
    $(".quick_links li").find("a").click(function () {
        var $this = $(this),
            user_id = $this.parents(".quick_link_mian").data("userid");

        if (user_id < 1 && !$this.hasClass('cart_list') && !$this.hasClass('mpbtn_history') && !$this.hasClass('mpbtn_email')) {
            $.notLogin("get_ajax_content.php?act=get_login_dialog", '');
            return false;
        }
    });

    //点击展开邮箱订阅
    $(".mpbtn_email").click(function () {
        var obj = $(".email_sub");
        if (obj.hasClass("show")) {
            obj.removeClass("show");
        } else {
            obj.addClass("show");
        }
    });

    //判断浏览器下滚还是上滚，向上滚动邮箱验证隐藏
    $(document).ready(function () {
        var p = 0, t = 0;
        var obj = $(".email_sub");
        $(window).scroll(function (e) {
            p = $(this).scrollTop();
            if (t <= p) {
                if (obj.hasClass("show")) {
                    obj.addClass("show");
                }
            } else {
                obj.removeClass("show");
            }
            setTimeout(function () {
                t = p;
            }, 0);
        });
    });
    /*****************************右侧黑色悬浮栏内容点击触发事件end***************************************/

    //关注品牌
    $(document).on("click", "*[shoptype='coll_brand']", function () {
        var user_id = $("input[name=user_id]").val();
        if (user_id > 0) {
            var brand_id = $(this).data('bid');
            if ($(this).find("i").hasClass("icon-zan-alts")) {
                $(this).find("i").removeClass("icon-zan-alts").addClass("icon-zan-alt");
                $(this).find("*[shoptype='follow_span']").html("关注");
                Ajax.call('brandn.php', 'act=cancel&id=' + brand_id + '&user_id=' + user_id, collect_brandResponse, 'POST', 'JSON');
            } else {
                $(this).find("i").removeClass("icon-zan-alt").addClass("icon-zan-alts");
                $(this).find("*[shoptype='follow_span']").html("已关注");
                Ajax.call('brandn.php', 'act=collect&id=' + brand_id, collect_brandResponse, 'POST', 'JSON');
            }
        } else {
            var back_url = "brand.php";
            $.notLogin("get_ajax_content.php?act=get_login_dialog", back_url);
        }
    });

    //关注品牌回调函数
    function collect_brandResponse(result) {
        $("#collect_count").html(result.collect_count);
        $("#collect_count_" + result.brand_id).html(result.collect_count);
        ;
    }

    //秒杀商品设置提醒
    $(document).on("click", "*[shoptype='collSecGoods']", function () {
        var user_id = $("input[name=user_id]").val();
        if (user_id > 0) {
            var sid = $(this).data('id');
            if ($(this).hasClass("sc-greenBg-btn")) {
                $(this).removeClass("sc-greenBg-btn").addClass("sc-redBg-btn");
                $(this).html("取消提醒");
                Ajax.call('seckill.php', 'act=collect&sid=' + sid + '&user_id=' + user_id, colSecGoodsResponse, 'POST', 'JSON');
            } else {
                $(this).removeClass("sc-redBg-btn").addClass("sc-greenBg-btn");
                $(this).html("提醒我");
                Ajax.call('seckill.php', 'act=cancel&sid=' + sid + '&user_id=' + user_id, colSecGoodsResponse, 'POST', 'JSON');
            }
        } else {
            var back_url = "seckill.php";
            $.notLogin("get_ajax_content.php?act=get_login_dialog", back_url);
        }
    });

    //秒杀商品设置提醒回调函数
    function colSecGoodsResponse(result) {
        pbDialog(result.message, "", 1, 300, "80", 50);
    }

    /****会员领取优惠券 start***/
    $(document).on("click", ".get-coupon", function () {
        var cou_id = $(this).attr('cou_id');
        var coupon = '';
        if ($(this).data('coupon')) {
            coupon = $(this).data('coupon');
        }
        receiveCoupon(cou_id, coupon);
    });

    function receiveCoupon(coupon_id, coupon) {
        if (user_id > 0) {
            $.post('/home/market/coupons', {'coupon_id': coupon_id}, function (data) {
                if (data.status == '1') {
                    $(".item-fore h3").html(data.msg);
                    $(".success-icon").removeClass("i-icon").addClass("m-icon");
                    var content = $("#pd_coupons").html();
                    pb({
                        id: "coupons_dialog",
                        title: json_languages.receive_coupons,
                        width: 550,
                        height: 140,
                        ok_title: json_languages.Immediate_use, 	//按钮名称
                        cl_title: json_languages.close, 	//按钮名称
                        content: content, 	//调取内容
                        drag: false,
                        foot: true,
                        onOk: function () {
                            location.href = "/";
                        },
                        onCancel: function () {
                            window.location.reload();
                        },
                    });
                    $(".pb-ok").addClass("color_df3134");
                } else {
                    $(".success-icon").removeClass("m-icon").addClass("i-icon");
                    $(".item-fore h3").addClass("red");
                    $(".item-fore h3").html(data.msg);
                    var content = $("#pd_coupons").html();
                    pb({
                        id: "coupons_dialog",
                        title: json_languages.receive_coupons,
                        width: 550,
                        height: 140,
                        ok_title: json_languages.close, 	//按钮名称
                        content: content, 	//调取内容
                        cl_cBtn: false,
                        onOk: function () {
                        }
                    });
                }
            }, 'json');
        } else {
            $.notLogin();
            return false;
        }
    }

    /****会员领取优惠券 end***/

    /* 商品收藏 品牌关注 店铺关注 */
    $(document).on('click', "*[data-dialog='goods_collect_dialog']", function () {
        var url = $(this).data('url'),
            id = $(this).data('goodsid'),
            divId = $(this).data("divid"),
            width = 455,
            height = 58,
            content = "",
            type = $(this).data("type"),
            btn_coll = $(this),
            icon=$(this).find(".choose-btn-icon");

        if (user_id == 0 && type == "goods") {
            $.notLogin();
            return false;
        }

        if (id > 0) {
            $.ajax({
                type: 'POST',
                url: '/home/goods/favorite',
                data: {id: id},
                success: function (data) {
                    btn_coll.toggleClass('selected');
                    if (data.status) {
                        icon.removeClass('icon-collection').addClass('icon-collection-alt');
                    } else {
                        icon.removeClass('icon-collection-alt').addClass('icon-collection');
                    }
                    $("#collect_count").html(data.count);

                    pbDialog(data.msg, "", 1, width, height, 95, false, function () {
                        location.href = "/home/member/favorite.html";
                    }, '我的收藏');
                }
            });
        }
    });

    /* 对比框隐藏 */
    $("[shoptype='db_hide']").on("click", function () {
        $("#slideTxtBox").hide();
    });

    /* 对比 */
    var db_winWidth = $(window).width();
    var db_left = (db_winWidth - 1392) / 2 + (1392 - 996) / 2;
    $("#slideTxtBox").css({"left": db_left});

    $(window).resize(function () {
        db_winWidth = $(this).width();
        if (db_winWidth > 1200) {
            db_left = (db_winWidth - 1200) / 2;
            var db_left = (db_winWidth - 1392) / 2 + (1392 - 996) / 2;
        } else {
            $("#slideTxtBox").css({"left": 0});
        }
    });


    //商品名称title设置了颜色 前台处理title html代码
    $(".p-name a").each(function () {
        if ($(this).prop("title") != "") {
            var title = $(this).attr('title');
            var newTitle = title.replace(/<\/?[^>]*>/g, '');

            $(this).attr('title', newTitle);
        }
    });

    /*var brand_select = $(".brand_select_more");
	if(brand_select.length>0){
		brand_select.hover(function(e){
            $(".brand_select_more").perfectScrollbar("destroy");
			$(".brand_select_more").perfectScrollbar();
        });
	}*/


    /************************************** 通用内容end ******************************************/

    /************************************** 批发市场 start ******************************************/
    $(document).on(" click", "*[shoptype='lieMore']", function () {
        var t = $(this);
        var parent = t.parents("*[shoptype='lieItems']");
        if (t.hasClass("lie-down")) {
            t.removeClass("lie-down");
            t.find("i").addClass("icon-down").removeClass("icon-up");
            parent.find("*[shoptype='lieItem']").addClass("hide").eq(0).removeClass("hide");
        } else {
            t.addClass("lie-down");
            t.find("i").removeClass("icon-down").addClass("icon-up");
            parent.find("*[shoptype='lieItem']").removeClass("hide");

        }
    });

    $(document).on('click', "[shoptype='invPayee']", function () {
        var val = $(this).val();
        if (val == 0) {
            $('#inv_payee').hide();
            $('#tax_id').hide();
        } else {
            $('#inv_payee').show();
            $('#tax_id').show();
        }
    });
    /************************************** 批发市场 end ******************************************/

    /************************************** 首页 start ******************************************/

    //首页楼层鼠标移动分类触发事件
    $(document).on("mouseenter", "li[shoptype='floor_cat_content']", function () {
        get_homefloor_cat_content(this);
    });

    //首页品牌 换一批切换
    doc.on('click', "*[shoptype='changeBrand']", function () {
        var temp = '';
        if ($("input[name='temp']").length > 0) {
            temp = $("input[name='temp']").val();
        }

        $.get("/home/index/ajaxChangeBrands", '', changeBrandResponse);
    });

    function changeBrandResponse(result) {
        $("#recommend_brands").html(result);
    }

    //首页弹出全屏广告

    doc.on('click', "*[shoptype='ejectClose']", function () {
        $("*[shoptype='ejectAdv']").hide();
    });

    /************************************** 首页 end ******************************************/

    /************************************** 商品列表页start ***************************************/
    $("a[shoptype='gstop']").on("click", function () {
        var parent = $(this).parents(".goods-spread");
        var ico = $(this).find("i");
        var goodslist = parent.siblings(".goods-list");
        var right = 0;

        var winWidth = $(window).width();

        var minWidth = 1160;
        var maxWidth = 1392;

        if (winWidth < 1450) {
            minWidth = 978;
            maxWidth = 1200;
        }

        if (parent.hasClass("goods-spread-fix")) {
            goodslist.stop().animate({"width": minWidth}, startAnimate);
            goodslist.removeClass("goods-list-w1390");
        } else {
            goodslist.stop().animate({"width": maxWidth});

            right = ($(window).width() - maxWidth) / 2;
            parent.css("right", right - 60);

            goodslist.addClass("goods-list-w1390");

            parent.addClass("goods-spread-fix");
            ico.removeClass("icon-right").addClass("icon-left");
        }

        function startAnimate() {
            parent.removeClass("goods-spread-fix");
            ico.removeClass("icon-left").addClass("icon-right");
        }
    });

    $("*[shoptype='fsortTab'] .item").on("click", function () {
        var Item = $(this);
        var type = Item.data("type");
        var main = $("*[shoptype='gMain']");

        Item.addClass("current").siblings(".item").removeClass("current");
        if (type == "large") {
            main.find(".gl-warp-large").show();
            main.find(".gl-warp-samll").hide();
        } else {
            main.find(".gl-warp-large").hide();
            main.find(".gl-warp-samll").show();
        }
    });

    //列表页 相册切换
    $(".sider li").hover(function () {
        var src = $(this).find('img').attr("src");
        $(this).parents(".sider").prev().find("img").attr("src", src);
        $(this).addClass("curr").siblings().removeClass("curr");
    });

    //产品列表筛选
    $(".fcheckbox .checkbox_item label").click(function () {
        var check = $(this).prev();
        if (check.prop("checked") == true) {
            var input_url = ($(this).nextAll('#input-i2').attr('rev'));
            check.addClass("checkbox-checked");
        } else {
            var input_url = ($(this).nextAll("#input-i1").attr('rev'));
            check.addClass("checkbox-checked");
        }
        location.href = input_url;
    });
    /************************************** 商品列表页(goods_list)end ***************************************/


    /************************************** 商品详情页(goods_info)start ***************************************/
    //商品评论标签点击筛选
    $("*[shoptype='comment_tag']").on("click", function () {
        var type = $(this).data('type');//操作类型  1表示全部
        //点击高亮处理
        $(this).find('span').addClass('red');
        $(this).siblings().find('span').removeClass('red');
        var tag_obj = $("#ECS_COMMENT").find('.com-list-item');
        if (type == 1) {
            tag_obj.show();
        } else {
            var tag = $(this).find('span').html();
            var i = 0;//评论中出现次数
            if (tag_obj) {
                tag_obj.each(function () {
                    var _this = $(this);
                    var j = 0;//单条评论中出现次数
                    _this.find('.ciw-actor-info span e').each(function () {
                        var c_tag = $(this).html();
                        if (c_tag == tag) {
                            j++;
                            $(this).addClass('red');
                        } else {
                            $(this).removeClass('red');
                        }
                    });
                    if (j == 0) {
                        _this.hide();
                    } else {
                        _this.show();
                        i++;
                    }
                });
                //预留入口
                if (i == 0) {

                }
            }
        }
    });
    //多个促销活动展开
    $("*[shoptype='view-prom']").hover(function () {
        var $this = $(this);
        var s_wrap = $this.parents(".summary-price-wrap");
        var w_wrap = $this.parents(".s-p-w-wrap");
        var height = w_wrap.outerHeight();

        s_wrap.css("height", height);
        w_wrap.addClass("z-promotions-all-show");

    }, function () {
        var $this = $(this);
        var w_wrap = $this.parents(".s-p-w-wrap");
        w_wrap.removeClass("z-promotions-all-show");
    });

    //配送地区选择展开效果
    doc.on("mouseenter", "*[shoptype='areaSelect']", function () {
        var $this = $(this);
        $this.find("*[shoptype='areaWarp']").show();
        $this.addClass("hover");
        $this.find(".iconfont").removeClass("icon-down").addClass("icon-up");
    });

    doc.on("mouseleave", "*[shoptype='areaSelect']", function () {
        var $this = $(this);
        $this.find("*[shoptype='areaWarp']").hide();
        $this.removeClass("hover");
        $this.find(".iconfont").removeClass("icon-up").addClass("icon-down");
    });

    //商品降价通知
    $("*[shoptype='priceNotify']").on("click", function () {
        var $this = $(this),
            user_id = $this.data("userid"),
            goods_id = $this.data("goodsid"),
            content = $("#notify_box").html();

        //判断是否登录
        if (user_id == 0) {
            var back_url = "goods.php?id=" + goods_id;
            $.notLogin("get_ajax_content.php?act=get_login_dialog", back_url);
            return false;
        } else {
            pb({
                id: "notifyBox",
                title: json_languages.pb_title,
                width: 500,
                height: 210,
                content: content,
                ok_title: json_languages.determine,
                cl_title: json_languages.cancel,
                drag: false,
                foot: true,
                onOk: function () {
                    notifyBox(user_id, goods_id, "#notifyBox");
                }
            });
        }
    });

    //白条分期
    $("*[shoptype='is-ious'] .item").on("click", function () {
        var $this = $(this),
            val = $this.data("value");
        if ($this.hasClass("selected")) {
            $this.removeClass("selected");
            $this.siblings("input[name='stages_qishu']").val('');
        } else {
            $this.addClass("selected").siblings().removeClass("selected");
            $this.siblings("input[name='stages_qishu']").val(val);
        }
    });

    //分期提交表单
    $("*[shoptype='byStages']").on("click", function () {
        var val = $("input[name='stages_qishu']").val();
        var goods_id = $("input[name='good_id']").val();
        var user_id = $("input[name='user_id']").val();

        if (user_id > 0) {
            if (val > 0) {
                window.location.href = "javascript:bool=1;addToCartStages(goods_id);";
            } else {
                get_goods_prompt_message(json_languages.select_stages_number);
            }
        } else {
            var back_url = "goods.php?id=" + goods_id;
            $.notLogin("get_ajax_content.php?act=get_login_dialog", back_url);
        }
    });

    /*门店取货*/
    function goodsStorePick() {
        var goods_id = $("input[name='goods_id']").val(),
            user_id = $("input[name='user_id']").val(),
            back_url = "goods.php?id=" + goods_id,
            formBuy = document.forms['ECS_FORMBUY'],
            spec_arr = "",
            divId = "";
        //门店服务-门店取货弹窗口
        /*未登录 跳转登陆，登陆选择门店*/
        $("*[shoptype='seller_store']").on("click", function () {
            //商品属性
            if (formBuy) {
                spec_arr = getSelectedAttributes(formBuy);
            }

            divId = "storeDialogBody";
            if (user_id > 0) {
                Ajax.call("get_ajax_content.php?act=get_store_list&goods_id=" + goods_id + '&spec_arr=' + spec_arr, 'back_act=' + back_url, function (data) {
                    pb({
                        id: divId,
                        title: json_languages.see_store,
                        width: 670,
                        height: 320,
                        content: data.content,
                        drag: false,
                        foot: false
                    });

                    $.levelLink(1);

                }, 'POST', 'JSON');
            } else {
                $.notLogin("get_ajax_content.php?act=get_login_dialog", back_url);
                return false;
            }
        });

        //到店取货弹框
        $("*[shoptype='btn-store-pick']").on("click", function () {
            //商品属性
            if (formBuy) {
                spec_arr = getSelectedAttributes(formBuy);
            }

            divId = "storePick";
            ru_id = $("input[name='merchantId']").val();
            /*未登录 跳转登陆，登陆选择门店*/
            if (user_id == 0) {
                $.notLogin("get_ajax_content.php?act=get_login_dialog", back_url);
                return false;
            }
            Ajax.call("get_ajax_content.php?act=storePick", 'ru_id=' + ru_id + "&spec_arr=" + spec_arr + "&goods_id=" + goods_id, function (data) {
                pb({
                    id: divId,
                    title: json_languages.store_subscribe,
                    width: 450,
                    height: 240,
                    ok_title: json_languages.submit_subscribe,
                    cl_title: json_languages.cancel,
                    content: data.content, 	//调取内容
                    drag: false,
                    foot: true,
                    onOk: function () {
                        var store_id = $("input[name='store_id']").val(),
                            end_time = $("input[name='end_time']").val(),
                            store_mobile = $("input[name='store_mobile']").val();

                        if (store_id > 0) {
                            if (store_mobile == "") {
                                pbDialog(json_languages.login_phone_packup_one, "", 0);
                                $("input[name='store_mobile']").focus();
                                return false;
                            } else if (!Utils.isTel(store_mobile) || store_mobile.length != 11) {
                                pbDialog(json_languages.msg_phone_not, "", 0);
                                $("input[name='store_mobile']").focus();
                                return false;
                            } else {
                                bool = 2;
                                addToCart(goods_id, 0, 0, '', '', store_id, end_time, store_mobile);
                                return true;
                            }
                        } else {
                            pbDialog(json_languages.select_store, "", 0);
                            return false;
                        }
                    }
                });
            }, 'POST', 'JSON');
        });

        /*更换选择门店*/
        $(document).on("click", "*[shoptype='storeSelect']", function () {
            //商品属性
            if (formBuy) {
                spec_arr = getSelectedAttributes(formBuy);
            }

            divId = "latelStorePick";
            ru_id = $("input[name='merchantId']").val();

            Ajax.call("get_ajax_content.php?act=storeSelect", 'ru_id=' + ru_id + "&spec_arr=" + spec_arr + "&goods_id=" + goods_id, function (data) {
                pb({
                    id: divId,
                    title: json_languages.store_lately,
                    width: 900,
                    height: 410,
                    ok_title: json_languages.determine,
                    cl_title: json_languages.cancel,
                    content: data.content, 	//调取内容
                    drag: false,
                    foot: true,
                    onOk: function () {
                        store_id = $("#" + divId).find(".active input[name='store_id']").val();
                        if (store_id > 0) {
                            Ajax.call("get_ajax_content.php?act=replaceStore", 'store_id=' + store_id + "&spec_arr=" + spec_arr + "&goods_id=" + goods_id, function (result) {
                                $(".replaceStore").html(result.content);
                            }, 'POST', 'JSON');
                        }
                    }
                });
                //$(".select-shop").perfectScrollbar("destroy");
                //$(".select-shop").perfectScrollbar();
            }, 'POST', 'JSON');

            //筛选城市门店
            regionSelect(ru_id, goods_id);
        });
    }

    //门店取货方法调用
    goodsStorePick();

    //商品详情页价格阶梯 start
    $("*[shoptype='view_priceLadder']").hover(function () {
        /*clearTimeout(outTimer);
		var priceLadder = $(this).siblings("*[shoptype='priceLadder']");
		hoverTimer = setTimeout(function(){priceLadder.show()},200);*/

        $(this).siblings("*[shoptype='priceLadder']").show();
    }, function () {
        $(this).siblings("*[shoptype='priceLadder']").hide();
    });

    /*$("*[shoptype='priceLadder']").hover(function(){
		clearTimeout(outTimer);
		$(this).show();
	},function(){
		$(this).hide();
	});*/
    //商品详情页价格阶梯 end

    //配送地区 start
    function areaAddress() {
        var $this = $("#area_address");
        var width = 0;
        $this.hover(function () {
            width = $(this).outerWidth();
            $(this).find('.area-warp').show();
        }, function () {
            $(this).find('.area-warp').hide();
        });
    }

    areaAddress();
    //配送地区 end

    //后台购买流程设置点击确定立即购买，并且没有登录会员弹出登录框(后台设置购物流程为一步购物)
    $("*[shoptype='btn-buynow']").click(function () {
        var one_step_buy = $(this).data("type"),
            goods_id = $("input[name='good_id']").val();

        if (user_id <= 0 && one_step_buy == 1) {
            var back_url = "goods.php?id=" + goods_id;
            $.notLogin("get_ajax_content.php?act=get_login_dialog", back_url);
            return false;
        }
    });


    //预售详情 预售规则
    $(".sp-rule").hover(function () {
        $(this).addClass("hover");
    }, function () {
        $(this).removeClass("hover");
    });

    //商品详情页悬浮栏加入购物车 商品规格
    $("*[shoptype='tb-tab-anchor']").on("click", function () {
        var t = $(this);
        $(this).siblings(".tb-popsku").show();
    });

    $("*[shoptype='tb-cancel']").on("click", function () {
        var t = $(this);
        $(this).parents(".tb-popsku").hide();
    });

    //商品详情页店铺展开收起
    $(".arrow-show-more").click(function () {
        $(".seller-pop-box,.seller-address").stop(true, false).slideToggle();
    });

    //店内分类展开收起
    $("*[shoptype='cateOpen'] dt").click(function () {
        var $this = $(this);
        var dl = $this.parent("dl");
        if (dl.hasClass("hover")) {
            dl.removeClass("hover");
        } else {
            dl.addClass("hover");
        }
    });

    //商品详情页 详情左侧 店铺商品热销、新品、精品排行
    var rankmcli_length = $("*[shoptype='rankMcTab']").find("li").length;
    if (rankmcli_length == 1) {
        $("*[shoptype='rankMcTab']").addClass("mcOne");
    } else if (rankmcli_length == 2) {
        $("*[shoptype='rankMcTab']").addClass("mcTwo");
    } else if (rankmcli_length == 3) {
        $("*[shoptype='rankMcTab']").addClass("mcThree");
    }


    //评论筛选
    $("*[shoptype='gmf-tab'] li").click(function () {
        var rev = $(this).attr("rev");
        var comment = "";
        var goods_id = $(this).parent().data('id');
        $(this).addClass("curr").siblings().removeClass("curr");

        $.post('/home/goods/getComment.html', {rank: rev, id: goods_id}, get_commentResponse);
    });

    /**评论回复**/
    $("*[shoptype='reply']").click(function () {
        if ($(this).parents(".com-operate").next().hasClass("hide")) {
            $(this).parents(".com-operate").next().removeClass("hide");
        } else {
            $(this).parents(".com-operate").next().addClass("hide");
        }
    });

    function get_commentResponse(result) {
        $("#ECS_COMMENT").html(result);
    }

    /*评论图片展开 start */
    $(document).on("click", ".p-thumb-img li", function () {
        var $this = $(this);
        var imgUrl = $this.data("src");
        var viewImg = $this.parents(".p-imgs-warp").find(".p-view-img");
        var length = $this.siblings("li").length + 1;
        var fale = false;
        if ($this.hasClass("curr")) {
            $this.removeClass("curr");
            fale = false;
        } else {
            $this.addClass("curr").siblings().removeClass("curr");
            fale = true;
        }

        if (fale == true) {
            viewImg.show();
            viewImg.find("img").attr("src", imgUrl);
        } else {
            viewImg.hide();
        }
    });

    $(document).on("click", ".p-view-img img", function () {
        var $this = $(this);
        var viewImg = $this.parents(".p-view-img");
        viewImg.hide();
        viewImg.siblings(".p-thumb-img").find("li").removeClass("curr");
    });

    $(document).on("click", ".p-view-img a", function () {
        var $this = $(this);
        var imgs = $this.parents(".p-imgs-warp");
        var length = imgs.find("li").length;
        var count = imgs.find(".curr").data("count");

        if ($this.hasClass("p-prev")) {
            if (count > 1) {
                imgs.find("*[data-count=" + (count - 1) + "]").click();
            }
        } else {
            if (count != length) {
                imgs.find("*[data-count=" + (count + 1) + "]").click();
            }
        }
    });
    /*评论图片展开end*/

    /************************************** 商品详情页(goods_list)end ***************************************/

    /************************************** 品牌专区(brand)start *******************************************/
    //品牌专区首页分类筛选
    $(document).on("click", "*[shoptype='brandCate'] *[shoptype='cateItem']", function () {
        var cat_id = $(this).data('catid');

        $(this).addClass('curr').siblings("*[shoptype='cateItem']").removeClass('curr');

        $.jqueryAjax('brand.php', 'act=filter_category&cat_id=' + cat_id, function (data) {
            $("*[shoptype='brandList'] *[shoptype='items']").html(data.content);
        });
    });

    //品牌专区 品牌详情页 点击分类展示商品
    $(document).on("click", "*[shoptype='brandcat'] a", function () {
        var brand_id = $("input[name=brand_id]").val();
        var cat_id = $(this).data("catid");

        $(this).addClass("curr").siblings().removeClass("curr");
        $.jqueryAjax('brandn.php', 'act=get_brand_cat_goods&id=' + brand_id + '&cat=' + cat_id, function (data) {
            if (data.content) {
                $("*[shoptype='goodslist']").html(data.content);
            }
        });
    });
    /************************************** 品牌专区(brand)end *********************************************/

    /********************************************* 购物车(cart)start ***************************************/
    $("*[shoptype='c-promotion']").on("click", function () {
        var $this = $(this);
        var parent = $this.parent();
        var height = parent.find("*[shoptype='promTips'] ul").height();

        $(".promotion-info").removeClass("prom-hover");
        $(".promotion-info").find("*[shoptype='promTips']").css("height", 0);
        if (parent.hasClass("prom-hover")) {
            parent.removeClass("prom-hover");
            parent.find("*[shoptype='promTips']").css("height", 0);
        } else {
            parent.addClass("prom-hover");
            parent.find("*[shoptype='promTips']").css("height", height);
        }
    });

    //购物车删除和移到收藏弹框
    $(document).on("click", "*[shoptype='cartOperation']", function () {
        var user_id = $("#user_id").val();

        var ok_title, cl_title, content;
        var obj = $(this).data("value");

        if (obj.divId == 'cart_remove') {
            ok_title = json_languages.remove;
            cl_title = json_languages.move_collection;
            content = $("#dialog_remove").html();
        } else if (obj.divId == 'cart_collect') {
            ok_title = json_languages.follow;
            cl_title = json_languages.cancel;
            content = $("#dialog_collect").html();
        }

        if (user_id > 0 || obj.divId == 'cart_remove') {
            pb({
                id: obj.divId,
                title: obj.title,
                width: 455,
                height: 58,
                ok_title: ok_title, //按钮名称
                cl_title: cl_title, //按钮名称
                content: content, //调取内容
                drag: false,
                foot: true,
                onOk: function () {
                    location.href = obj.url;
                },
                onCancel: function () {
                    if (obj.divId == 'cart_remove') {
                        location.href = obj.cancelUrl;
                    }
                }
            });
        } else {
            var back_url = "flow.php";
            $.notLogin("get_ajax_content.php?act=get_login_dialog", back_url);
        }
    });

    //购物车批量删除
    $("*[data-dialog='remove_collect_dialog']").click(function () {
        var user_id = $("#user_id").val();
        if (user_id > 0) {
            var remove_url = $(this).data('removeurl');
            var collect_url = $(this).data('collecturl');
            var divId = $(this).data('divid');
            var cart_value = $('#cart_value').val();
            var goods_ru = $('#goods_ru').val();
            var url;

            if (divId == 'cart-remove-batch') {
                var content = json_languages.drop_goods;
                url = remove_url;
            } else if (divId == 'cart-collect-batch') {
                var content = json_languages.confirm_Move_collection;
                url = collect_url;
            }

            pbDialog(content, "", 0, 450, 50, "", true, function () {
                Ajax.call(url, 'cart_value=' + cart_value, function (data) {
                    location.href = "flow.php";
                }, 'POST', 'JSON');
            });
        } else {
            var back_url = "flow.php";
            $.notLogin("get_ajax_content.php?act=get_login_dialog", back_url);
        }
    });


    //购物车未登录结算弹出登录框
    $("#go_pay").click(function () {
        var back_url = $(this).data("url");
        $.notLogin(back_url);
        return false;
    });

    /********************************************* 购物车(cart)end ***************************************/

    /********************************************* 结算页(flow)start ***************************************/

    //收货人信息切换
    $(document).on("click", "*[shoptype='cs-w-item']", function () {
        $(this).addClass("cs-selected").siblings().removeClass("cs-selected");
    });

    /* 结算页面 用户收货地址 start */
    $(document).on("click", "*[shoptype='dialog_checkout']", function () {
        var type = $(this).data("type");
        var id = $(this).data("value");
        var parent = $(this).parents(".cs-w-item");
        var length = parent.siblings(".cs-w-item").length;
        if (type == 'new_address') {
            if ((length + 1) >= 11) {
                pbDialog(json_languages.add_address_10, "", 0);
                return false;
            }
        }

        if (type == 'new_address' || type == 'edit_address') {
            //添加收货地址信息
            $.get('/home/member/ajaxEditAddress', {address_id: id}, function (data) {
                var title = {new_address: '新增收货人地址', edit_address: '编辑收货人地址'};
                pb({
                    id: type,
                    title: title[type],
                    width: 900,
                    content: data, 	//调取内容
                    drag: false,
                    foot: true,
                    ok_title: json_languages.con_Preservation,
                    cl_title: json_languages.cancel,
                    onOk: function () {
                        var form = $("#" + type).find("form").attr("name");
                        if (addUpdate_Consignee(form) == false) {
                            return false;
                        } else {
                            return true;
                        }
                    }
                });
                if (type == 'new_address') {
                    //新增地区初始化
                    $.levelLink(1);
                } else {
                    //编辑地区初始化
                    $.levelLink(0);
                }
            });

        } else if (type == 'del_address') {
            //删除收货地址信息
            var content = $('#del_address').html();
            pbDialog(json_languages.confirm_drop_address, "", 0, '', '', '', true, function () {
                $.post('/home/member/deladdress', {pageparm: JSON.stringify({address_id: id})}, function (res) {
                    if (res.status > 0) {
                        del_ConsigneeResponse(id);
                    }
                }, 'json');
            });
        }
    });

    /* 结算页面 用户收货地址 end */

    /* 门店自提结算页面 修改门店选择 start*/
    $("*[shoptype='storeBtn']").on("click", function () {
        $("*[shoptype='seller_address']").addClass("hide");
        $("*[shoptype='get_seller_sotre']").addClass("show");
    });

    /* 门店自提结算页面 修改门店选择 end*/

    // function paymet() {
    //     var payment_method = $("*[shoptype='paymentType']"),			//结算页面支付方式
    //         payInput = $("input[name='pay_pwd_error']"),			//结算页面其他信息 支付密码隐藏域
    //         length = payInput.length,								//结算页面其他信息 支付密码隐藏域 大于0表示开启
    //         balance = $("#qt_balance"),                             //结算页面其他信息 使用余额
    //         payPw = $("#qt_onlinepay"),								//结束页面其他信息 支付密码
    //         integObj = $("#qt_integral"),							//结算页面其他信息 使用积分
    //         sueplus = balance.find("input[name='surplus']"),		//余额input
    //         user_sueplus = sueplus.data("yoursurplus"),				//用户可用余额
    //         integral = integObj.find("input[name='integral']"),		//积分input
    //         integral_max = integral.data("maxinteg");				//此订单可用积分
    //
    //     //余额和积分初始化方法
    //     initialize = function () {
    //         //积分input是否大于0
    //         if (integral.val() > 0) {
    //             //初始化积分
    //             integral.val(0);
    //             //初始化积分为0，总价去除积分抵扣价格
    //             changeIntegral(0);
    //         }
    //
    //         //余额input是否大于0
    //         if (sueplus.val() > 0) {
    //             //初始化余额
    //             sueplus.val(0);
    //             //初始化余额为0，总价去除余额抵扣价格
    //             changeSurplus(0);
    //         }
    //     };
    //
    //     payPassword = function () {
    //         var pay_length = payment_method.find(".item-selected").length;
    //         if (length > 0 && pay_length > 0) {
    //             var paymet_curr_val = payment_method.find(".item-selected").data("value"),  //结算页面支付方式 默认选中的支付方式value
    //                 paymet_curr_type = paymet_curr_val.type;								//结算页面支付方式 默认选中的支付方式类型
    //
    //             //初始化
    //             initialize();
    //
    //             if (paymet_curr_type == "balance") {
    //                 //余额支付状态，余额填写区域隐藏
    //                 balance.hide();
    //
    //                 //支付状态为在线支付，并且设置了支付密码
    //                 if (payPw.length > 0) {
    //                     payPw.show();    //支付密码显示
    //                     payInput.val(1); //支付密码隐藏域值赋值为1
    //                 }
    //             } else {
    //                 //非余额支付状态，余额填写区域显示
    //                 balance.show();
    //
    //                 //用户余额大于0，余额显示
    //                 if (user_sueplus > 0) {
    //                     balance.show();
    //                 } else {
    //                     balance.hide();
    //                 }
    //
    //                 //此订单可以使用积分，积分显示
    //                 if (integral_max > 0) {
    //                     //integObj.show();
    //                 } else {
    //                     //integObj.hide();
    //                 }
    //
    //                 payPw.hide();  //支付密码隐藏
    //                 payInput.val(0); //支付密码隐藏域值赋值为0
    //             }
    //         }
    //     };
    //
    //     //initialize(); //初始化方法默认调用
    //     //payPassword();
    //
    //     //支付方式切换
    //     payment_method.find(".p-radio-item").on("click", function () {
    //         var t = $(this),
    //             paymet_curr_type = t.data("value");			//选中支付方式的value
    //
    //         //支付方式选中
    //         t.addClass("item-selected").siblings().removeClass("item-selected");
    //         t.find('input').prop("checked", true);
    //
    //         if (paymet_curr_type == "balance") {
    //             //余额支付状态，余额填写区域隐藏
    //             balance.hide();
    //
    //             //支付状态为在线支付，并且设置了支付密码
    //             if (payPw.length > 0) {
    //                 payPw.show();    //支付密码显示
    //                 payInput.val(1); //支付密码隐藏域值赋值为1
    //             }
    //         } else {
    //
    //             //非余额支付状态，余额填写区域显示
    //             balance.show();
    //
    //             if (paymet_curr_allow == 1) {
    //                 //判断会员是否有余额
    //                 changeSurplus(0);
    //             }
    //
    //             /* 是否存在储值卡和是否选择储值卡 */
    //             if (document.getElementById('value_card_psd') && $("#value_card_psd").prop('disabled') == true) {
    //
    //                 //支付密码显示
    //                 payPw.show();
    //
    //                 //初始化支付密码
    //                 payPw.find("input[name='pay_pwd']").val("");
    //
    //                 payInput.val(1); //支付密码隐藏域值赋值为1
    //
    //             } else {
    //                 payPw.hide();  //支付密码隐藏
    //
    //                 //初始化支付密码
    //                 payPw.find("input[name='pay_pwd']").val("");
    //
    //                 payInput.val(0); //支付密码隐藏域值赋值为0
    //             }
    //         }
    //
    //         //改变支付方式
    //         selectPayment(paymet_curr_id);
    //     });
    // }
    //
    // paymet();

    //发票修改
    $(document).on("click", "*[shoptype='invEdit']", function () {
        var obj = $(this).data("value");
        var invoice_type = $("#inv_content").find("input[name='invoice_type']").val();
        $.get(obj.url, '', invoiceResponse);

        function invoiceResponse(data) {
            pb({
                id: obj.divid,
                title: obj.title,
                width: 675,
                height: 278,
                ok_title: json_languages.invoice_ok, 	//按钮名称
                cl_title: json_languages.cancel, 		//按钮名称
                content: data, 	//调取内容
                drag: false,
                foot: true,
                onOk: function () {
                    if ($('#edit_invoice .pb-btn.pb-ok').hasClass('pb-dis')) {
                        layer.msg('请先保存发票信息');
                        throw new Error('未保存发票信息');
                    }
                    var invoice_val = $("#edit_invoice .selected").find("input[name='invoice_id']").val();
                    var inv_content = $("#edit_invoice .radio-list .item-selected").find("input[name='inv_content']").val();
                    var invoice_type = $("#edit_invoice .tab-nav").find(".item-selected").data('value');
                    var tax_id = $("#tax_id").val();

                    if (typeof invoice_val == 'undefined' || invoice_val == "") {
                        pbDialog(json_languages.invoice_packup, "", 0);
                        return false;
                    }
                    if (!$("*[shoptype='tax']").is(":hidden") && tax_id == "") {
                        pbDialog(json_languages.invoice_tax_null, "", 0);
                        return false;
                    }

                    $.post('/home/cart/ajaxInvoince', {
                        invoice_type: invoice_type,
                        invoice_id: invoice_val,
                        tax_id: tax_id,
                        inv_content: inv_content
                    }, gotoInvoiceResponse, 'JSON');
                }
            });

            //选中效果 by wu start
            var inv_payee = $("#inv_content").find("input[name=inv_payee]").val();
            var inv_content = $("#inv_content").find("input[name=inv_content]").val();
            $("#edit_invoice .invoice-list").find("input[value='" + inv_payee + "']").parents(".invoice-item").addClass("selected").siblings().removeClass("selected");
            $("#edit_invoice .invoice-list").find("input[value='" + inv_payee + "']").siblings("input[name='invoice_id']").prop("checked", true);
            $("#edit_invoice .radio-list").find("input[value='" + inv_content + "']").parents("li").addClass("item-selected").siblings().removeClass("item-selected");
            $("#edit_invoice .radio-list").find("input[value='" + inv_content + "']").prop("checked", true);
            //选中效果 by wu end
            invoice();
        }

        //跳转手机端
        browserRedirect();
    });

//批发-发票修改
    $(document).on("click", "*[shoptype='wholesale_invEdit']", function () {
        var obj = $(this).data("value");
        var invoice_type = $("#inv_content").find("input[name='invoice_type']").val();
        Ajax.call(obj.url, 'invoice_type=' + invoice_type, invoiceResponse, 'POST', 'JSON');

        function invoiceResponse(data) {
            if (data.error == 0) {
                pb({
                    id: obj.divid,
                    title: obj.title,
                    width: 675,
                    height: 278,
                    ok_title: json_languages.invoice_ok, 	//按钮名称
                    cl_title: json_languages.cancel, 		//按钮名称
                    content: data.content, 	//调取内容
                    drag: false,
                    foot: true,
                    onOk: function () {
                        var invoice_val = $("#edit_invoice .selected").find("input[name='invoice_id']").val();
                        var inv_content = $("#edit_invoice .radio-list .item-selected").find("input[name='inv_content']").val();
                        var invoice_type = $("#edit_invoice .tab-nav").find(".item-selected").data('value');
                        var store_id = $("#store_id").val();
                        var tax_id = $("#tax_id").val();
                        var warehouse_id = $(".checkout-foot").find("input[name='warehouse_id']").val();
                        var area_id = $(".checkout-foot").find("input[name='area_id']").val();
                        var cfrom = $("#inv_content").find("input[name='from']").val();
                        var shipping_id = get_cart_shipping_id();

                        if (typeof invoice_val == 'undefined' || invoice_val == "") {
                            pbDialog(json_languages.invoice_packup, "", 0);
                            return false;
                        }
                        if (!$("*[shoptype='tax']").is(":hidden") && tax_id == "") {
                            pbDialog(json_languages.invoice_tax_null, "", 0);
                            return false;
                        }

                        Ajax.call('ajax_dialog.php?act=wholesale_gotoInvoice', 'inv_content=' + encodeURIComponent(inv_content) + '&invoice_id=' + invoice_val + '&from=' + cfrom + '&warehouse_id=' + warehouse_id + '&area_id=' + area_id + '&store_id=' + store_id + '&invoice_type=' + invoice_type + '&tax_id=' + tax_id + '&shipping_id=' + $.toJSON(shipping_id), gotoInvoiceResponse, 'POST', 'JSON');

                        function gotoInvoiceResponse(result) {
                            if (result.error != "") {
                                pbDialog(result.error, "", 0);
                                return false;
                            } else {
                                if (result.type) {
                                    $("#inv_content .inv_payee").html('');
                                    $("#inv_content .inv_content").html('');
                                    $("#inv_content .invoice_type").html(result.invoice_type);
                                    $("#inv_content").find("input[name=inv_payee]").val('');
                                    $("#inv_content").find("input[name=inv_content]").val('');
                                    $("#inv_content").find("input[name=invoice_type]").val(invoice_type);
                                } else {
                                    $("#inv_content .inv_payee").html(result.inv_payee);
                                    $("#inv_content .inv_content").html(result.inv_content);
                                    $("#inv_content .invoice_type").html(result.invoice_type);
                                    $("#inv_content").find("input[name=inv_payee]").val(result.inv_payee);
                                    $("#inv_content").find("input[name=inv_content]").val(result.inv_content);
                                    $("#inv_content").find("input[name=invoice_type]").val(invoice_type);
                                    $("#inv_content").find("input[name=tax_id]").val(result.tax_id);


                                    $("#common_button").find("input[name=inv_payee]").val(result.inv_payee);
                                    $("#common_button").find("input[name=inv_content]").val(result.inv_content);
                                    $("#common_button").find("input[name=invoice_type]").val(invoice_type);
                                    $("#common_button").find("input[name=tax_id]").val(result.tax_id);
                                    $("#ECS_ORDERTOTAL").html(result.content);
                                }
                            }
                        }
                    }
                });

                //选中效果 by wu start
                var inv_payee = $("#inv_content").find("input[name=inv_payee]").val();
                var inv_content = $("#inv_content").find("input[name=inv_content]").val();
                $("#edit_invoice .invoice-list").find("input[value='" + inv_payee + "']").parents(".invoice-item").addClass("selected").siblings().removeClass("selected");
                $("#edit_invoice .invoice-list").find("input[value='" + inv_payee + "']").siblings("input[name='invoice_id']").prop("checked", true);
                $("#edit_invoice .radio-list").find("input[value='" + inv_content + "']").parents("li").addClass("item-selected").siblings().removeClass("item-selected");
                $("#edit_invoice .radio-list").find("input[value='" + inv_content + "']").prop("checked", true);
                //选中效果 by wu end

                invoice();
            } else if (data.error == 1) {
                pbDialog(data.content, "", 0, "", "", 50);
            }
        }

        //跳转手机端
        browserRedirect();
    });

    /* 跳转手机端 start */
    function browserRedirect() {
        var sUserAgent = navigator.userAgent.toLowerCase();
        var bIsIpad = sUserAgent.match(/ipad/i) == "ipad";
        var bIsIphoneOs = sUserAgent.match(/iphone os/i) == "iphone os";
        var bIsMidp = sUserAgent.match(/midp/i) == "midp";
        var bIsUc7 = sUserAgent.match(/rv:1.2.3.4/i) == "rv:1.2.3.4";
        var bIsUc = sUserAgent.match(/ucweb/i) == "ucweb";
        var bIsAndroid = sUserAgent.match(/android/i) == "android";
        var bIsCE = sUserAgent.match(/windows ce/i) == "windows ce";
        var bIsWM = sUserAgent.match(/windows mobile/i) == "windows mobile";

        if ((bIsIpad || bIsIphoneOs || bIsMidp || bIsUc7 || bIsUc || bIsAndroid || bIsCE || bIsWM) == true) {
            window.location.href = "/mobile";
        }
    }

    /* 跳转手机端 end */

    //编辑发票弹窗内容
    function invoice() {
        var invoice = "#edit_invoice",
            invoiceItem = ".invoice-item",
            addBtn = ".add-invoice-btn",
            editBtn = ".edit-tit",
            updateBtn = ".update-tit",
            delBtn = ".del-tit",
            radioList = $(invoice).find(".radio-list");

        if ($(".invoice-item").hasClass("selected")) {

            var invoiceid = $(".invoice-thickbox .selected").data('invoiceid');

            var tax_id = $(":input[name='invoice_tax_" + invoiceid + "']").val();

            $("#tax_id").val(tax_id);
        }

        $(".invoice-list").on("click", invoiceItem, function () {
            $(this).addClass("selected").siblings().removeClass("selected");
            $(this).find("input[name='invoice_id']").prop("checked", true);

            var invoice_id = $(this).find(":input[name='invoice_id']").val();
            if (invoice_id > 0) {
                var tax_id = $(this).find('[shoptype=taxId]').val();
            } else {
                var tax_id = $("#tax_id").val();
            }

            $("#tax_id").val(tax_id);

            checked(invoice_id);
        });

        function checked(invoice_id) {
            if ($(invoiceItem).length <= 1 || invoice_id == 0) {
                $("*[shoptype='tax']").hide();
                $("#tax_id").val('');
            } else {
                $("*[shoptype='tax']").show();
            }
        }

        checked($(invoiceItem).find("input[name='invoice_id']:checked").val());

        //新增公司发票
        $(invoice).find(addBtn).on("click", function () {
            var $this = $(this),
                f_item = "";
            $this.addClass("hide");
            $(invoiceItem).removeClass("selected");

            f_item = $(invoiceItem).length;

            if (f_item < 4) {
                var div = "<div class='invoice-item selected'><span><input type='text' name='inv_payee' class='inv_payee'  placeholder='" + json_languages.add_invoice + "' value=''><input name='invoice_id' type='radio' class='hide' value='" + 0 + "'><b></b></span><div class='btns'><a href='javascript:void(0);' class='ftx-05 edit-tit hide'>" + json_languages.edit + "</a><a href='javascript:void(0);' class='ftx-05 update-tit'>" + json_languages.Preservation + "</a><a href='javascript:void(0);' class='ftx-05 ml10 del-tit hide'>" + json_languages.drop + "</a></div>";
                $this.parent().prev().append(div);
                $('#edit_invoice .pb-btn.pb-ok').addClass('pb-dis');
                $(invoiceItem).eq(f_item).find("input.inv_payee").focus().blur(function () {
                    $(this).parents('.invoice-item').find('.update-tit').addClass('remind');
                    $(this).unbind('blur');
                });

                $("div[shoptype='tax']").hide();
                $("div[shoptype='tax']").find("input[name='tax_id']").val('');
            } else {
                pbDialog(json_languages.invoice_desc_number, "", 0);

                $(invoiceItem).eq(0).addClass("selected");
                $this.removeClass("hide");
            }
        });

        //编辑公司名称
        $(".invoice-list").on('click', editBtn, function () {
            var $this = $(this),
                obj = $this.parent().prev(),
                val = 0;

            obj.find("input").removeAttr("readonly");
            obj.find("input").focus();
            $('#edit_invoice .pb-btn.pb-ok').addClass('pb-dis');
            $this.addClass("hide").next().removeClass("hide");

            val = obj.find("input[name='invoice_id']").val();
        });
        //保存公司名称
        $(".invoice-list").on('click', updateBtn, function () {
            var $this = $(this),
                obj = $this.parent().prev(),
                inv_payee = obj.find("input[name=inv_payee]").val(),
                invoice_id = obj.find("input[name=invoice_id]").val(),
                tax_id = $("#tax_id").val();

            if (inv_payee == "") {

                pbDialog(json_languages.invoice_desc_null, "", 0);
                return false;

            } else {
                $.ajax({
                    type: 'POST',
                    url: '/home/cart/ajaxUpdateInvoince.html?act=update_name',
                    data: {
                        'inv_payee': inv_payee,
                        'invoice_id': invoice_id,
                        'tax_id': tax_id
                    },
                    success: function (data) {
                        obj.find("input[name=invoice_id]").val(data.invoice_id);
                        $('#edit_invoice .pb-btn.pb-ok').removeClass('pb-dis');

                        $("#tax_id").val(data.tax_id);
                        $this.removeClass('remind');
                        checked(data.invoice_id);
                    },
                    error: function (xhr, type) {
                        pbDialog(xhr.responseJSON, "", 0);
                    }
                });

                obj.find("input").attr("readonly", true);

                $this.addClass("hide").siblings().removeClass("hide");

                $(addBtn).removeClass("hide");

                $this.find("input[name='invoice_id']").prop("checked", true);
            }
        });

        $(".invoice-list").on("click", delBtn, function () {
            var $this = $(this),
                obj = $this.parents(invoiceItem),
                invoice_id = obj.find("input[name=invoice_id]").val(),
                length = 0;
            if (invoice_id == 0) {
                obj.remove();
                length = $(invoice).find(invoiceItem).length;

                if (length == 1) {
                    $(invoice).find(invoiceItem).addClass("selected");
                    $(invoice).find(invoiceItem).find("input[name=invoice_id]").prop("checked", true);
                }

            } else {
                $('#edit_invoice .pb-btn.pb-ok').removeClass('pb-dis');
                $.ajax({
                    type: 'POST',
                    url: '/home/cart/ajaxUpdateInvoince.html?act=del',
                    data: {tax_id: invoice_id},
                    success: function (data) {
                        obj.remove();
                        $(invoice).find(invoiceItem).eq(0).addClass("selected");
                        $(invoice).find(invoiceItem).eq(0).find("input[name=invoice_id]").click();
                        $("#tax_id").val('');
                    },
                    error: function (xhr, type) {
                        pbDialog(xhr.responseJSON, "", 0);
                        return false;
                    }
                });
            }
        });

        radioList.find("li").click(function () {
            $(this).addClass("item-selected").siblings().removeClass("item-selected");
            var id = $("#vatCanEdit").val();
            if ($(this).data('value') == 1 && id < 1) {
                $('#edit_invoice .pb-btn.pb-ok').addClass('pb-dis');
            } else {
                $('#edit_invoice .pb-btn.pb-ok').removeClass('pb-dis');
            }
        });

        /*发票切换*/
        $(".invoice-dialog").slide({titCell: ".tab-nav li", mainCell: ".invoice-thickbox", titOnClassName: "item-selected", trigger: "click"});

        /*下一步*/
        $("*[shoptype='nextStep']").on("click", function () {
            var type = $(this).data("type"),
                steps = $(this).parents(".steps"),
                fald = true,
                frm = $(this).parents("form[name='inv_form']"),
                act = frm.find("input[name='action']").val(),
                msg = new Object;

            msg.company_name = frm.find("input[name='company_name']").val();
            msg.tax_id = frm.find("input[name='tax_id']").val();
            msg.company_address = frm.find("input[name='company_address']").val();
            msg.company_telephone = frm.find("input[name='company_telephone']").val();
            msg.bank_of_deposit = frm.find("input[name='bank_of_deposit']").val();
            msg.bank_account = frm.find("input[name='bank_account']").val();
            msg.consignee_name = frm.find("input[name='consignee_name']").val();
            msg.consignee_mobile_phone = frm.find("input[name='consignee_mobile_phone']").val();
            msg.country = frm.find("input[name='country']").val();
            msg.province = frm.find("input[name='province']").val();
            msg.city = frm.find("input[name='city']").val();
            msg.district = frm.find("input[name='district']").val();
            msg.consignee_address = frm.find("input[name='consignee_address']").val();

            if (type != 1) {
                var step = steps.find(".step").eq(type - 1);

                step.find("input[type='text']").each(function (v, k) {
                    if ($(this).val() == "") {
                        iValid($(this).attr("name"), $(this).val(), type);
                        fald = false;
                    } else {
                        fald = true;
                    }
                });
            } else {
                fald = true;
            }

            if (fald == true) {
                steps.find(".step").eq(type).show().siblings().hide();

                if (type == 3) {
                    $.ajax({
                        type: 'POST',
                        url: '/home/cart/ajaxUpdateInvoince?act=' + act,
                        data: msg,
                        success: function (data) {
                            $("*[shoptype='invReturn']").html(data);
                            $('#edit_invoice .pb-btn.pb-ok').removeClass('pb-dis');
                        },
                        error: function (xhr, type) {
                            pbDialog(xhr.responseJSON, '', 0);
                        }
                    });
                }
            }
        });

        /*返回*/
        $("*[shoptype='backStep']").on("click", function () {
            var type = $(this).data("type"),
                steps = $(this).parents(".steps");
            steps.find(".step").eq((type - 2)).show().siblings().hide();
        });

        function iValid(name, val, type) {
            if (val == "" && type == 2) {
                switch (name) {
                    case 'company_name':
                        $("input[name='" + name + "']").siblings(".form_prompt").html("请填写增值发票单位名称");
                        break;

                    case 'tax_id':
                        $("input[name='" + name + "']").siblings(".form_prompt").html("请填写纳税人识别码");
                        break;

                    case 'company_address':
                        $("input[name='" + name + "']").siblings(".form_prompt").html("请填写注册地址");
                        break;

                    case 'company_telephone':
                        $("input[name='" + name + "']").siblings(".form_prompt").html("请填写联系电话");
                        break;

                    case 'bank_of_deposit':
                        $("input[name='" + name + "']").siblings(".form_prompt").html("请填写开户行名称");
                        break;

                    case 'bank_account':
                        $("input[name='" + name + "']").siblings(".form_prompt").html("请填写银行卡卡号");
                        break;

                    default:
                        return true;
                }
            } else if (val == "" && type == 3) {
                switch (name) {
                    case 'consignee_name':
                        $("input[name='" + name + "']").siblings(".form_prompt").html("请填写收发票人名称");
                        break;

                    case 'consignee_mobile_phone':
                        $("input[name='" + name + "']").siblings(".form_prompt").html("请填写收票人手机号码");
                        break;

                    case 'consignee_province':
                        $("input[name='" + name + "']").siblings(".form_prompt").html("请填写收票人地址");
                        break;

                    case 'consignee_address':
                        $("input[name='" + name + "']").siblings(".form_prompt").html("请填写收票人详细地址");
                        break;

                    default:
                        return true;
                }
            }
        }
    }

    //优惠券/储值卡/红包
    $("*[shoptype='ck-toggle']").on("click", function () {
        var $this = $(this);
        $this.siblings(".ck-step-cont").slideToggle(300, function () {
            if ($this.hasClass("ck-toggle-off")) {
                $this.removeClass("ck-toggle-off")
                    .addClass("ck-toggle-on")
                    .find(".iconfont")
                    .removeClass(".icon-down")
                    .addClass("icon-up");
            } else {
                $this.removeClass("ck-toggle-on")
                    .addClass("ck-toggle-off")
                    .find(".iconfont")
                    .removeClass("icon-up")
                    .addClass("icon-down");
            }
        });
    });

    //优惠券/储值卡/红包 选择切换
    // $(document).on("click", "*[shoptype='panlItem']", function () {
    //     var $this = $(this);
    //     var shipping_id = get_cart_shipping_id();
    //     var warehouse_id = $(".checkout-foot").find("input[name='warehouse_id']").val();
    //     var area_id = $(".checkout-foot").find("input[name='area_id']").val();
    //
    //     //没填收货地址不允许选择
    //     var uc_id = $this.data('ucid');
    //     var type = $this.data("type");
    //
    //     if ($('#consignee-addr').length == 0) {
    //         pbDialog(json_languages.checked_address, "", 0);
    //         return false;
    //     }
    //     if ($this.hasClass("selected")) {
    //         $this.removeClass("selected").siblings().removeClass("selected");
    //         //优惠券
    //         if (type == 'coupons') {
    //             $.getJSON("flow.php?step=change_coupons&uc_id=0", '&warehouse_id=' + warehouse_id + '&area_id=' + area_id + '&shipping_id=' + $.toJSON(shipping_id), function (data) {
    //                 orderSelectedResponse(data);
    //                 $('#uc_id').val(0);
    //                 $('#not_freightfree').val(0);
    //             }, 'json');
    //         }
    //         //红包
    //         else if (type == 'bonus') {
    //             $('#bonus_id').val(0);
    //             changeBonus(0);
    //         }
    //         //储值卡
    //         else if (type == 'value_card') {
    //             $('#ECS_VALUE_CARD').val(0);
    //             changeVcard(0);
    //         }
    //     } else {
    //         $this.addClass("selected").siblings().removeClass("selected");
    //         //优惠券
    //         if (type == 'coupons') {
    //             $.getJSON("flow.php?step=change_coupons&uc_id=" + uc_id, '&warehouse_id=' + warehouse_id + '&area_id=' + area_id + '&shipping_id=' + $.toJSON(shipping_id), function (data) {
    //                 $('#uc_id').val(uc_id);
    //                 $('#not_freightfree').val(data.not_freightfree);
    //
    //                 orderSelectedResponse(data);
    //             }, 'json');
    //         }
    //         //红包
    //         else if (type == 'bonus') {
    //             $('#bonus_id').val(uc_id);
    //             changeBonus(uc_id);
    //         }
    //         //储值卡
    //         else if (type == 'value_card') {
    //             $('#ECS_VALUE_CARD').val(uc_id);
    //             changeVcard(uc_id);
    //         }
    //     }
    // });

    //配送方式选择
    function logistics() {
        var t = "",
            parents = "",
            _html = "",
            index = 0,
            ru_id = 0,
            type = 0,
            shipping = "",
            shipping_id = 0,
            shipping_code = "",
            text = "";

        //展开配送方式
        doc.on('mouseenter', '.mode-tab-item', function () {
            clearTimeout(outTimer);
            var width = 0;
            t = $(this);
            width = t.parents("ul").outerWidth();

            shipping_code = t.data('shippingcode');
            parents = t.parents("[shoptype='disInfo']");

            hoverTimer = setTimeout(function () {
                if (shipping_code == "cac") {
                    parents.find("*[shoptype='since']").show();
                    parents.find("*[shoptype='logistics']").hide();
                } else {
                    parents.find("*[shoptype='logistics']").css("right", width - t.outerWidth());
                    parents.find("*[shoptype='logistics']").show();
                    parents.find("*[shoptype='since']").hide();
                }
            }, 200);
        })
            .on('mouseleave', '.mode-tab-item', function () {
                clearTimeout(hoverTimer);
                t = $(this);

                shipping_code = t.data('shippingcode');
                parents = t.parents();

                outTimer = setTimeout(function () {
                    parents.find("*[shoptype='since']").hide();
                    parents.find("*[shoptype='logistics']").hide();
                }, 100);
            })
            .on('mouseenter', '.mwapper', function () {
                clearTimeout(outTimer);
            })
            .on('mouseleave', '.mwapper', function () {
                $(this).hide();
            });

        //展开配送方式end

        //切换配送方式 start
        $(document).on("click", ".logistics_li", function () {
            t = $(this);
            index = t.index();
            ru_id = t.data('ruid');
            type = t.data('type');
            shipping_id = t.data('shipping');
            shipping_code = t.data('shippingcode');
            parents = t.parents("*[shoptype='disInfo']");
            shipping = "";
            var warehouse_id = $(".checkout-foot").find("input[name='warehouse_id']").val();
            var area_id = $(".checkout-foot").find("input[name='area_id']").val();

            if (shipping_code != 'cac') {
                _html = t.data("text");
                parents.find("*[shoptype='tabLog']").addClass("item-selected").siblings().removeClass("item-selected");
            }

            //console.log(index,ru_id,type,shipping_id,shipping_code,_html);

            t.addClass("item-selected").siblings().removeClass("item-selected");

            $(".shipping_" + ru_id).val(shipping_id);
            $(".shipping_code_" + ru_id).val(shipping_code);

            if (_html != "") {
                parents.find("*[shoptype='tabLog'] span").html(_html);
                parents.find("*[shoptype='tabLog']").attr("data-shipping", shipping_id).attr("data-shippingcode", shipping_code).attr("data-ruid", ru_id).attr("data-type", type);
            }

            t.parents("*[shoptype='logistics']").hide();

            /* 选择配送方式 start */
            $("*[shoptype='shoppingList']").each(function (index, element) {
                var li_shinpping_id = $(element).find("*[shoptype='disInfo'] li.item-selected").attr("data-shipping");
                var seller_shipping = Number(li_shinpping_id);

                if (index > 0) {
                    shipping += ",";
                }

                shipping += li_shinpping_id;
            });
            /* 选择配送方式 end */

            Ajax.call('ajax_dialog.php?act=shipping_type', 'ru_id=' + ru_id + '&shipping_id=' + shipping_id + '&warehouse_id=' + warehouse_id + '&area_id=' + area_id + '&type=' + type + '&shipping=' + shipping, changeShippingResponse, 'POST', 'JSON');
        });
        //切换配送方式 end

        doc.on("click", ".mode-tab-item", function () {
            t = $(this);
            index = t.index();
            shippingcode = t.data("shippingcode");
            shipping = "";
            var warehouse_id = $(".checkout-foot").find("input[name='warehouse_id']").val();
            var area_id = $(".checkout-foot").find("input[name='area_id']").val();

            t.addClass('item-selected').siblings().removeClass('item-selected');

            /* 选择配送方式 start */
            $("*[shoptype='shoppingList']").each(function (index, element) {
                var li_shinpping_id = $(element).find("*[shoptype='disInfo'] li.item-selected").attr("data-shipping");
                var seller_shipping = Number(li_shinpping_id);

                if (index > 0) {
                    shipping += ",";
                }

                shipping += seller_shipping;
            });
            /* 选择配送方式 end */

            if (shippingcode == 'cac') {
                ru_id = t.data('ruid');
                type = t.data('type');
                shipping_id = t.data('shipping');

                Ajax.call('ajax_dialog.php?act=shipping_type', 'ru_id=' + ru_id + '&shipping_id=' + shipping_id + '&warehouse_id=' + warehouse_id + '&area_id=' + area_id + '&type=' + type + '&shipping=' + shipping, changeShippingResponse, 'POST', 'JSON');
            } else {
                parents = t.parents("[shoptype='disInfo']");
                parents.find(".logistics_li").each(function (index, element) {
                    var $this = $(this);
                    if ($this.hasClass("item-selected")) {
                        ru_id = $this.data("ruid");
                        type = $this.data("type");
                        shipping_id = $this.data("shipping");

                        Ajax.call('ajax_dialog.php?act=shipping_type', 'ru_id=' + ru_id + '&shipping_id=' + shipping_id + '&warehouse_id=' + warehouse_id + '&area_id=' + area_id + '&type=' + type + '&shipping=' + shipping, changeShippingResponse, 'POST', 'JSON');
                    }
                });
            }

        });

        //自提点
        doc.on("click", "*[shoptype='flow_dialog']", function () {
            var value, ok_title, cl_title, url, title, width, height, divId, mark, ajax_picksite;

            value = $(this).data("value");

            url = value.url; //删除连接地址
            title = value.title;
            width = value.width;
            height = value.height;
            divId = value.divid;
            mark = value.mark; //区分提货站与日期修改

            ok_title = json_languages.save;
            cl_title = json_languages.cancel;

            $("*[shoptype='tabCac']").click();

            Ajax.call(url, '', shopResponse, 'POST', 'JSON');

            function shopResponse(result) {
                pb({
                    id: divId,
                    title: title,
                    width: width,
                    height: height,
                    ok_title: ok_title, 	//按钮名称
                    cl_title: cl_title, 	//按钮名称
                    content: result.result, 	//调取内容
                    drag: false,
                    foot: true,
                    onOk: function () { //保存回调函数
                        if (mark == 0) {
                            var district = $("#pickRegion_select").val();
                            var picksite_id = $("input[name='picksite_radio']:checked").val();
                            ajax_picksite = 'district=' + district + '&picksite_id=' + picksite_id + 'mark=' + mark;

                            if (typeof (picksite_id) == "undefined") {
                                pbDialog(json_languages.delivery_Prompt, "", 0);
                                return false;
                            }
                        } else {
                            var shipping_date = $("input[name='shipping_date']:checked").attr('data-shippingDate');
                            var time_range = $("input[name='shipping_date']:checked").attr('data-range');

                            if (typeof (shipping_date) == "undefined") {
                                pbDialog(json_languages.delivery_Prompt_two, "", 0);
                                return false;
                            }
                            ajax_picksite = 'shipping_date=' + shipping_date + '&time_range=' + time_range + '&mark=' + mark;
                        }

                        Ajax.call('flow.php?step=select_picksite', ajax_picksite, selectPicksiteResponse, 'POST', 'JSON');
                    },
                    onCancel: function () { //取消回调函数
                    }
                });
            }
        });
    }

    //配送方式方法
    logistics();


    /* 支付订单页 */
    $("*[shoptype='opened']").on("click", function () {
        var $this = $(this);
        var div = $this.parents(".o-list-info").next();
        if (div.is(":hidden")) {
            $this.html(json_languages.down_detail + "</span><i class='iconfont icon-up'></i>");
        } else {
            $this.html(json_languages.order_detail + "</span><i class='iconfont icon-down'></i>");
        }
        div.slideToggle();
    });

    //银行卡切换
    $("*[shoptype='bankList'] li").on("click", function () {
        var $this = $(this);
        var parent = $(this).parents("*[shoptype='bankList']");
        $this.addClass("selected").siblings().removeClass("selected");

        if (parent.find(".selected").length > 0) {
            $("#alipay_bank").find(".noBtn").hide();
            $("#alipay_bank").find("input").show().css({"background-color": "#24be00"});
        }
    });

    //移除到order_total.lbi
    // $(document).on("click",".no_goods", function(){
    // 	var rec_number = $("input[name='rec_number_str']").val();
    // 	var url = $(this).data('url');
    // 	if(rec_number != ''){
    // 		url = url + "&rec_number=" + rec_number;
    // 	}

    // 	Ajax.call(url,'',noGoods, 'POST', 'JSON');
    // 	function noGoods(result){
    // 		if(result.error == 1){
    // 			pb({
    // 				id:'noGoods',
    // 				title:json_languages.No_goods,
    // 				width:670,
    // 				ok_title:json_languages.go_up, 	//按钮名称
    // 				cl_title:json_languages.back_cart, 	//按钮名称
    // 				content:result.content, 	//调取内容
    // 				drag:false,
    // 				foot:true,
    // 				onOk:function(){
    // 					$("form[name='stockFormCart']").submit();
    // 				},
    // 				onCancel:function(){
    // 					location.href = "flow.php";
    // 				}
    // 			});
    // 			$('.pb-ok').addClass('color_df3134');
    // 		}else{
    // 			$("form[name='doneTheForm']").submit();
    // 		}
    // 	}
    // });

    // $(document).on("click",".no_shipping", function(){
    // 	var shipping_prompt = $("input[name='shipping_prompt_str']").val();
    // 	var url = $(this).data('url');
    // 	if(shipping_prompt != ''){
    // 		url = url + "&shipping_prompt=" + shipping_prompt;
    // 	}

    // 	Ajax.call(url,'',noShipping, 'POST', 'JSON');
    // 	function noShipping(result){
    // 		if(result.error == 1){
    // 			pb({
    // 				id:'noGoods',
    // 				title:json_languages.No_shipping,
    // 				width:670,
    // 				ok_title:json_languages.go_up, 	//按钮名称
    // 				cl_title:json_languages.back_cart, 	//按钮名称
    // 				content:result.content, 	//调取内容
    // 				drag:false,
    // 				foot:true,
    // 				onOk:function(){
    // 					$("form[name='stockFormCart']").submit();
    // 				},
    // 				onCancel:function(){
    // 					location.href = "flow.php";
    // 				}
    // 			});
    // 			$('.pb-ok').addClass('color_df3134');
    // 		}else{
    // 			$("form[name='doneTheForm']").submit();
    // 		}}
    // });

    /********************************************* 结算页(flow)end ***************************************/


    /********************************************* 促销模块（团购，优惠，夺宝，礼包等）start************************/
    //优惠活动
    $(document).on('click', "*[shoptype='actFilter'] a", function () {
        var actType = $(this).data('acttype');
        var i = 0;

        $(this).addClass('curr').siblings().removeClass('curr');

        $("*[shoptype='actList'] li").each(function () {
            var li_acttype = $(this).data('acttype');
            if (li_acttype == actType || actType == -1) {
                i++;
                $(this).show();
            } else {
                $(this).hide();
            }
        });

        if (i == 0) {
            $(".no_records").show();
        } else {
            $(".no_records").hide();
        }
    });


    //团购详情页 立即团购
    $("*[shoptype='btn-group-buy']").on('click', function () {
        var quantity = Number($("*[shoptype='quantity']").val()),
            perNumber = Number($("*[shoptype='perNumber']").val()),
            restrictShop = Number($("*[shoptype='restrictShop']").val()),
            ogNumber = Number($("*[shoptype='orderGNumber']").data("value")),
            minamount = Number($(this).data('minamount'));

        if (user_id > 0) {

            if (perNumber == 0 || quantity > perNumber) {

                pbDialog(json_languages.Stock_goods_null, "", 0, 450, 80, 50);
                return false;
            } else if ((quantity + ogNumber) > restrictShop && restrictShop > 0) {
                pbDialog(json_languages.purchasing_prompt_two, "", 0, 500, 80, 50);
                return false;
            } else if (minamount > 0 && quantity < minamount) {
                pbDialog(json_languages.purchasing_minamount, "", 0, 500, 80, 50);
                return false;
            } else {
                Ajax.call('group_buy.php?act=checked_certification', 'user_id=' + user_id, function (data) {
                    if (data.error > 0) {
                        pbDialog(json_languages.emailInfo_incompleted, "", 0, 500, 80, 50);
                        return false;
                    } else {
                        $("form[name='ECS_FORMBUY']").submit();
                    }
                }, 'POST', 'JSON');
            }
        } else {
            var group_buy_id = $("input[name='group_buy_id']").val();
            var back_url = "group_buy.php?act=view&id=" + group_buy_id;
            $.notLogin("get_ajax_content.php?act=get_login_dialog", back_url);
            return false;
        }
    });

    /********************************************* 促销模块（团购，优惠，夺宝，礼包等）end************************/


    /********************************************* 文章页start ***************************************/
    $(".menu-item div.item-hd").on("click", function () {
        var t = $(this);
        $(this).siblings("ul").slideToggle(500, function () {
            if ($(this).is(":hidden")) {
                t.find(".iconfont").removeClass("icon-down").addClass("icon-up");
            } else {
                t.find(".iconfont").addClass("icon-down").removeClass("icon-up");
            }
        });
    });
    /********************************************* 文章页end ***************************************/

    /********************************************* 用户中心页(user) start *********************************/
    //用户中心右侧最小高度和左侧栏高度一样
    $(window).ready(function (e) {
        var height = $("*[shoptype='userSide']").height(),
            action = $("*[shoptype='userMain']").data("action");

        if (action == "default") {
            $("*[shoptype='userMain'] .user-mod").css({"min-height": height - 298});
        } else {
            $("*[shoptype='userMain'] .user-mod").css({"min-height": height - 70});
        }
    });

    //点击其他地方关闭选择列表模块
    $("body").on('click', function (e) {
        var target = $(e.target);
        var opened = $(".mod-select.mod-select-open");
        if (opened.length > 0) {
            if (target.parents(".mod-select").length == 0) {
                opened.removeClass("mod-select-open");
            }
        }
    });

    // 用户菜单展开效果
    $(".user-side .side-menu dt .square").click(function () {
        var $this = $(this);
        var dd = $this.parent("dt").siblings("dd");
        $this.toggleClass("square-plus");
        dd.slideToggle();
    });

    //订单多个产品展开
    $(document).on("click", "[shoptype='opm']", function () {
        $(this).prevAll("[shoptype='c-goods']").show();
        $(this).prev().hide();
        $(this).hide();
    });

    /* 跟踪包裹start */
    var hoverTimer, outTimer, hoverTimer2;
    $(document).on('mouseenter', "*[shoptype='track-packages-btn']", function () {
        clearTimeout(outTimer);
        var $this = $(this);
        hoverTimer = setTimeout(function () {
            $this.find("*[shoptype='track-packages-info']").show();
        }, 50);
    });

    $(document).on('mouseleave', "*[shoptype='track-packages-btn']", function () {
        clearTimeout(hoverTimer);
        var $this = $(this);
        outTimer = setTimeout(function () {
            $this.find("*[shoptype='track-packages-info']").hide();
        }, 50);
    });
    $(document).on('mouseenter', "*[shoptype='track-packages-info']", function () {
        clearTimeout(outTimer);
        hoverTimer2 = setTimeout(function () {
            $(this).show();
        });
    });
    $(document).on('mouseleave', "*[shoptype='track-packages-info']", function () {
        $(this).hide();
    });
    /* 虚拟商品卡密end*/

    /* 评论晒单 start */
    function userComment() {
        var t = "",
            parent = "";

        //评价星级
        doc.on("click", "*[shoptype='p_rate'] a", function () {
            t = $(this);
            parent = t.parents("*[shoptype='rates']");
            val = t.data("value");

            parent.find(".error").hide();
            t.addClass("selected").siblings().removeClass("selected");

            parent.find("input[type='hidden']").val(val);

            if (parent.find(".degree-text").length > 0) {
                parent.find(".degree-text").show();
                parent.find(".comt-error").hide();
                parent.find("*[shoptype='number']").html(val);
            }
        });

        //买家印象标签切换
        doc.on("click", "*[shoptype='itemTab']", function () {
            var val = "", recid = "";
            t = $(this);

            if (t.hasClass("selected")) {
                t.removeClass("selected");
            } else {
                t.addClass("selected");
            }

            t.parent().find(".selected").each(function () {
                var tag_val = $(this).data('val');
                var tag_recid = $(this).data('recid');

                val += tag_val + ",";
                recid += tag_recid + ",";
            });

            val = val.substring(0, val.length - 1);
            recid = recid.substring(0, recid.length - 1);

            $("input[name='impression']").val(val);
        });
    }

    userComment();

    //用户评论提交方法
    function commentForm(obj) {
        var obj = $("#" + obj),
            comment_id = "",
            comment_rank = "",
            content = "",
            impression = "",
            is_impression = "",
            captcha = "",
            cmt = new Object;

        comment_id = obj.find("input[name='comment_id']").val();
        comment_rank = obj.find("input[name='comment_rank']").val();
        content = obj.find("textarea[name='content']").val();
        impression = obj.find("input[name='impression']").val();
        is_impression = obj.find("input[name='is_impression']").val();
        captcha = obj.find("input[name='captcha']").val();

        cmt.comment_rank = (typeof (comment_rank) == "undefined") ? 0 : comment_rank;
        cmt.comment_id = (typeof (comment_id) == "undefined") ? 0 : comment_id;
        cmt.impression = (typeof (impression) == "undefined") ? '' : impression;
        cmt.content = (typeof (content) == "undefined") ? '' : content;
        cmt.captcha = (typeof (captcha) == "undefined") ? '' : captcha;

        cmt.order_id = obj.find("input[name='order_id']").val();
        cmt.goods_id = obj.find("input[name='goods_id']").val();
        cmt.rec_id = obj.find("input[name='rec_id']").val();
        cmt.sign = obj.find("input[name='sign']").val();

        if (cmt.comment_rank == 0 && cmt.sign == 0) {
            pbDialog(json_languages.select_pf, "", 0);
            return false;
        } else if (cmt.impression == '' && cmt.sign == 0 && is_impression == 1) {
            pbDialog(json_languages.Label_number_null, "", 0);
            return false;
        } else if ((cmt.content == '' || cmt.content.length > 500) && cmt.sign == 0) {
            if (cmt.content == '') {
                pbDialog(json_languages.content_not, "", 0);
            } else {
                pbDialog(json_languages.word_number, "", 0);
            }
            return false;
        } else if (cmt.captcha == '' && typeof (captcha) != "undefined") {
            pbDialog(json_languages.null_captcha_login, "", 0);
            return false;
        } else {
            Ajax.call('comment.php?act=comm_order_goods', 'cmt=' + $.toJSON(cmt), commentSignOneResponse, 'POST', 'JSON');
        }
    }

    //回调函数
    function commentSignOneResponse(result) {
        var sign = '';
        var left = 0;
        if (result.sign > 0) {
            sign = "&sign=" + result.sign;
        }

        if (result.sign > 0) {
            left = 100;
        } else {
            left = 60;
        }

        var hrefCont = "user.php?act=comment_list" + sign;

        if (result.error > 0) {
            pbDialog(result.message, "", 0);
        } else {
            pbDialog(result.message, json_languages.comments_Other, 1, "", "", left, false, function commentOk() {
                location.href = hrefCont;
            });
        }
    }

    //店铺满意度提交
    $("[shoptype='storeRateBtn']").on("click", function () {
        var rank = new Object;

        rank.order_id = $(this).data('orderid');
        rank.store_id = $("input[name='store_id']").val();
        rank.desc_rank = $(this).parents(".score").find("input[name=desc_rank]").val();
        rank.service_rank = $(this).parents(".score").find("input[name=service_rank]").val();
        rank.delivery_rank = $(this).parents(".score").find("input[name=delivery_rank]").val();

        if (rank.desc_rank == 0) {
            $("input[name=desc_rank]").nextAll(".comt-error").show();
            return false;
        } else if (rank.service_rank == 0) {
            $("input[name=service_rank]").nextAll(".comt-error").show();
            return false;
        } else if (rank.delivery_rank == 0) {
            $("input[name=delivery_rank]").nextAll(".comt-error").show();
            return false;
        } else {
            Ajax.call('/home/member/commented', 'rank=' + $.toJSON(rank), SatisfactionDegreeResponse, 'POST', 'JSON');
        }
    });

    function SatisfactionDegreeResponse(result) {
        if (result.status > 0) {
            var _html = '<h3><s class="icon-02"></s><span>感谢您的评价</span></h3>';
            $(".votelist-content").find(".service-rcol").html(_html);
        } else {
            pbDialog(result.msg, "", 0);
            var _html = '<h3><s class="icon-01"></s><span>重复评价</span></h3>';
            $(".votelist-content").find(".service-rcol").html(_html);
            return false;
        }
    }

    /* 评论晒单 end */


    /* 虚拟商品卡密start */
    var hoverTimer, outTimer, hoverTimer2;
    $(document).on('mouseenter', '.virtual_title', function () {
        clearTimeout(outTimer);
        var parents = $(this).parents('.virtual_div');
        hoverTimer = setTimeout(function () {
            parents.find(".virtual_info").show();
        }, 200);
    });

    $(document).on('mouseleave', '.virtual_title', function () {
        clearTimeout(hoverTimer);
        var parents = $(this).parents('.virtual_div');
        outTimer = setTimeout(function () {
            parents.find(".virtual_info").hide();
        }, 100);
    });
    $(document).on('mouseenter', '.virtual_info', function () {
        clearTimeout(outTimer);
        hoverTimer2 = setTimeout(function () {
            $(this).show();
        });
    });
    $(document).on('mouseleave', '.virtual_info', function () {
        $(this).hide();
    });
    /* 虚拟商品卡密end*/


    /* 银行卡号每隔4位空格 by yanxin start */
    /*var bank_card = $("*[shoptype='bank_card']");
	if(bank_card.length > 0){
		//默认加载银行卡号 4位数后空格隔开
		var card = bank_card.val();
		var ncard = "";

		card = card.replace(/\D/g,'');

		for(var i = 0; i < card.length; i = i+4){
			ncard += card.substring(i,i+4)+" ";
		}

		bank_card.val(ncard.replace(/(\s*$)/g,""));

		//银行卡输入后4位数后空格隔开
		bank_card.keyup(function(e){
			var obj = e , bankVal;
			if(obj.keyCode != 8){             				//判断是否为Backspace键，若不是执行函数；
				bankVal = $(this).val();   					//定义变量input  value值
				bankVal = bankVal.replace(/[^\d\s]/g,"");   //正则表达式：如果输入框中输入的不是数字或者空格，将不会显示；
				$(this).val(bankVal);       				//把新得到得value值赋值给输入框；
				for(n=1;n<=4;n++){
					if(bankVal.length <= 5*n-2 || bankVal.length>5*n-1){   //判断是否是该加空格的时候，若不会，还是原来的值；
						bankVal = bankVal;
					}else{
						bankVal += " ";                         //给value添加一个空格；
						$(this).val(bankVal);  					//赋值给输入框新的value
					}
				}
			}
		});

		bank_card.blur(function(e){
			var $this = $(this).parents("div.value");
            bankCard = bank_card.val();
			bankCard = bankCard.replace(/\s+/g, "");
			$.getJSON("./data/bankData.json", {}, function (data) {
				var bankBin = 0;
				var isFind = false;
				for (var key = 10; key >= 2; key--) {
					bankCard = bankCard.toString();
					bankBin = bankCard.substring(0, key);
					$.each(data, function (i, item) {
						if (item.bin == bankBin) {
							isFind = true;
							bName = item.bankName;
							$this.find(".notic").hide();
							$this.find("*[shoptype='bname']").html(bName).show();
						}
					});
					if (isFind) {
						break;
					}
				}
				if (!isFind) {
					$this.find(".notic").hide();
					$this.find("*[shoptype='bname']").html("请填写正确卡号").show();
				}
			});
        });
	}*/
    /* 银行卡号每隔4位空格 by yanxin end */

    /* 举报start */
    $(document).on("click", "*[shoptype='cancel_report']", function () {
        var _this = $(this);
        var id = _this.data("id");
        var type = _this.data("type");
        var state = _this.data("state");
        var back_href = '';
        if (type == 1 || state == 3) {
            back_href = 'user.php?act=illegal_report';
        } else {
            back_href = "user.php?act=goods_report&report_id=" + id;
        }
        if (confirm("确定执行此操作吗？执行后数据将不能找回！请谨慎操作！")) {
            Ajax.call('ajax_user.php?act=check_report_state', 'report_id=' + id + "&state=" + state, function (data) {
                if (data.error > 0) {
                    pbDialog(data.message, "", 0);
                } else {
                    location.href = back_href;
                }
            }, 'POST', 'JSON');
        }
    });
    /* 举报end */

    /* 缺货登记 取消 */
    $("*[shoptype='goods_del_booking']").on("click", function () {
        var url = $(this).data("url");

        pbDialog("您确定要取消订购信息？", "", 0, 455, 58, "", true, function () {
            location.href = url;
        });
    });

    /* 提现手续费 */
    $("*[shoptype='deposit_amout']").blur(function () {
        var val = $(this).val();
        var deposit_fee = $(this).parents('form').find("input[name='deposit_fee']").val();
        var deposit_money = 0;
        var input = '';
        //parseInt(val);
        if (deposit_fee > 0 && val > 0 && !isNaN(val)) {
            deposit_money = parseInt(val) * parseInt(deposit_fee) / 100;
            if (deposit_money > 0) {
                input = '<input type="hidden" value="' + deposit_money + '" name="deposit_money" />';
                $("*[shoptype='deposit_fee']").find("*[shoptype='deposit_fee_value']").html(deposit_money + input);
                $("*[shoptype='deposit_fee']").removeClass('hide');
            } else {
                $("*[shoptype='deposit_fee']").find("*[shoptype='deposit_fee_value']").html('');
                $("*[shoptype='deposit_fee']").addClass('hide');
            }
        } else {
            $("*[shoptype='deposit_fee']").find("*[shoptype='deposit_fee_value']").html('');
            $("*[shoptype='deposit_fee']").addClass('hide');
        }
    });

    $(".user-purchase .item").each(function () {
        var height_l = $(this).find(".itemc-left").height();
        var height_r = $(this).find(".itemc-right").height();


        if (height_l < height_r) {
            $(this).find(".itemc-right").addClass("borderLeft");
        } else if (height_l > height_r) {
            $(this).find(".itemc-left").addClass("borderRight");
        } else {
            $(this).find(".itemc-left").addClass("borderRight");
        }
    });

    //会员中心储值卡
    $("*[shoptype='value_see']").hover(function () {
        $("[shoptype='value_shop']").show();
    }, function () {
        $("[shoptype='value_shop']").hide();
    });
    //会员中心延迟收货
    $(document).on("click", "#sbumit_order_delay", function () {
        var rquest_url = "user.php?act=apply_delivery";
        var order_id = $(this).data('id');

        $.ajax({
            type: 'post',
            cache: false,
            async: false,
            dataType: 'json',
            data: {order_id: order_id},
            url: rquest_url,
            success: function (result) {
                alert(result.err_msg);
            },
            error: function () {
            }
        });
    });
    //全选
    $("input[name='all_list']").click(function () {
        if ($(this).prop("checked") == true) {
            $("input[name='checkboxes[]']").prop("checked", true);
        } else {
            $("input[name='checkboxes[]']").prop("checked", false);
        }
    });

    /********************************************* 用户中心页(user) end ***********************************/


    /********************************************* 入驻切换头部导航start ***********************************/
    $("*[ shoptype='merchants_article']").on("click", function () {
        var _this = $(this);
        var title = _this.html();
        $.post('/home/merchant/ajaxGetArticle', {title: title}, function (res) {
            _this.parents('li').addClass("curr").siblings().removeClass("curr");
            $(".container").html(res);
        });
    });
    /********************************************* 入驻切换头部导航end *************************************/


    /********************************************* 促销活动页面 start *************************************/
    $("*[shoptype='snatchType']").on("click", function () {
        $("#detail-slide").find(".hd li:eq(1)").click();
    });
    /********************************************* 促销活动页面 end***************************************/

    /********************************************* 众筹页面 start ****************************************/
    $("#parent_catagory li a").on("click", function () {
        var textTypeIndex = $(this).parent().index();
        var vsecondlist = $(".v-second-list");
        $(this).parent().addClass("current").siblings().removeClass("current");
        $(this).parents(".v-fold").next().show();
        var index = textTypeIndex - 1;
        if (index >= 0) {
            vsecondlist.show();
            vsecondlist.children(".s-list").eq(index).show().siblings().hide();
        } else {
            vsecondlist.hide();
            vsecondlist.children(".s-list").hide();
        }
    });

    $("#sort li").click(function () {
        $(this).addClass("current").siblings().removeClass("current");
    });

    $(".v-option").click(function () {
        if ($(this).hasClass('slidedown')) {
            $(this).removeClass('slidedown').addClass('v-close');
            $(this).html("<b></b><span>" + json_languages.Pack_up + "</span>");
            $(this).next().css("height", "auto");
        } else {
            $(this).removeClass('v-close').addClass('slidedown');
            $(this).html("<b></b><span>" + json_languages.more + "</span>");
            $(this).next().css("height", "26px");
        }
    });
    /********************************************* 众筹页面 end ****************************************/

    /***********************************************秒杀 start*****************************************/
    $(document).on("mouseenter", "*[shoptype='skmuMove']", function () {
        clearTimeout(outTimer);
        hoverTimer = setTimeout(function () {
            $("[shoptype='skmuMcate']").addClass("skmu-mcate-active");
        }, 200);
    });

    $(document).on("mouseleave", "*[shoptype='skmuMove']", function () {
        clearTimeout(hoverTimer);
        outTimer = setTimeout(function () {
            $("[shoptype='skmuMcate']").removeClass("skmu-mcate-active");
        }, 100);
    });
    $(document).on("mouseenter", "[shoptype='skmuMcate']", function () {
        clearTimeout(outTimer);
        hoverTimer2 = setTimeout(function () {
            $(this).addClass("skmu-mcate-active");
        });
    });
    $(document).on("mouseleave", "[shoptype='skmuMcate']", function () {
        $(this).removeClass("skmu-mcate-active");
    });
});

function gotoPage(page) {
    var tab = $('*[shoptype="gmf-tab"]');
    var id = tab.data('id');
    var rank = tab.find('li.curr').attr('rev');
    var pt = $('.pages .pages-it');
    if (isNaN(page)) {
        page = page == 'prev' ? pt.data('current') - 1 : pt.data('current') + 1;
        if (page < 1 || page > pt.data('total')) {
            return;
        }
    }

    $.post('/home/goods/getComment', {
        id: id,
        page: page,
        rank: rank

    }, function (data) {
        $("#ECS_COMMENT").html(data);
    });
}

/****************************************** js通用方法start *************************************************/

/* 商品详情信息 详情、评论、讨论圈滚动悬浮栏 start */

(function ($) {
    $.fn.jfloor = function (itemHeight, bHeight) {
        if (itemHeight == null) {
            var itemHeight = 0;
        }
        if (bHeight == null) {
            var bHeight = 0;
        }
        return this.each(function () {
            var winHeight = $(window).width();
            floors = $(this).find("*[shoptype='gm-floors']"),
                flooritem = floors.find("*[shoptype='gm-item']"),
                axis = $(this).find("*[shoptype='gm-tabs']"),
                layer = axis.find("*[shoptype='gm-tab-item']"),
                bor = axis.find("*[shoptype='qp-bort']"),
                floorsTop = parseInt(floors.offset().top - itemHeight);

            layer.click(function () {
                var index = layer.index(this);
                var top = parseInt(flooritem.eq(index).offset().top - itemHeight);
                $("body,html").stop().animate({scrollTop: top});
            });

            $(window).scroll(function () {
                var top = $(document).scrollTop();

                if (top >= floorsTop - itemHeight) {
                    axis.addClass("detail-hd-fixed");

                    if (bor.length > 0) {
                        bor.css({"width": winHeight, "left": -((winHeight - 1200) / 2 + floors.position().left)});
                    }
                } else {
                    axis.removeClass("detail-hd-fixed");
                }

                for (var i = 0; i < flooritem.length; i++) {
                    var flooritemTop = parseInt(flooritem.eq(i).offset().top - itemHeight);
                    if (top >= flooritemTop - bHeight) {
                        layer.eq(i).addClass("curr").siblings().removeClass("curr");
                    }
                }
            });
        });
    };
})(jQuery);

/* 商品详情描述 规格参数切换 */
function goods_desc_floor() {
    var winHeight = $(window).width(),
        floors = $("*[shoptype='gm-floors']"),
        flooritem = floors.find("*[shoptype='gm-item']"),
        axis = $("*[shoptype='gm-tabs']"),
        layer = axis.find("*[shoptype='gm-tab-item']"),
        bor = axis.find("*[shoptype='qp-bort']"),
        floorsTop = parseInt(floors.offset().top);

    $("*[shoptype='gm-tabs'] .gm-tab li").on("click", function () {
        var t = $(this),
            index = t.index();

        t.addClass("curr").siblings().removeClass("curr");

        for (var i = 0; i < flooritem.length; i++) {
            if (index == 0) {
                if (i == 1) {
                    flooritem.eq(i).hide();
                } else {
                    flooritem.eq(i).show();
                }
            } else if (index == 1) {
                if (i == 0) {
                    flooritem.eq(i).hide();
                } else {
                    flooritem.eq(i).show();
                }
            } else {
                if (i >= index) {
                    flooritem.eq(i).show();
                } else {
                    flooritem.eq(i).hide();
                }
            }
        }
        $("body,html").stop().animate({scrollTop: (floorsTop - 100)});
    });

    $("*[shoptype='product-detail']").on("click", function () {
        $("*[shoptype='gm-tabs'] li").eq(1).click();
    });

    $(window).scroll(function () {
        var top = $(document).scrollTop();

        if (top >= floorsTop) {
            $('.product-info').css('margin-top', '0px');
            axis.addClass("detail-hd-fixed");

            if (bor.length > 0) {
                bor.css({"width": winHeight, "left": -((winHeight - 1200) / 2 + floors.position().left)});
            }
        } else {
            $('.product-info').css('margin-top', '20px');
            axis.removeClass("detail-hd-fixed");
        }
    });
}

/* 商品详情信息 详情、评论、讨论圈滚动悬浮栏 end */


/* 商品详情页 清空浏览历史记录 */
function clear_history() {
    $.post('/home/index/clearHistory', '', function () {
        $("*[shoptype='history_mian']").html('<div class="history_tishi">' + json_languages.no_history + '<br /><a href="/" class="ftx-05">' + json_languages.go_shoping + '</a></div>');
    });
}

/* jq仿select带返回函数 start */

jQuery.divselect = function (divselectid, inputselectid, fn) {
    var inputselect = $(inputselectid);
    $(document).on('click', divselectid + " .cite", function (event) {
        $(".imitate_select").find("ul").hide();
        event.stopImmediatePropagation();
        var ul = $(divselectid + " ul");
        if (ul.css("display") == "none") {
            ul.css("display", "block");
        } else {
            ul.css("display", "none");
        }
        $(this).siblings("ul").perfectScrollbar("destroy");
        $(this).siblings("ul").perfectScrollbar();
    });
    $(document).on("click", divselectid + " ul li a", function (event) {
        event.stopImmediatePropagation();
        var txt = $(this).text();
        $(divselectid + " .cite span").html(txt);
        var value = $(this).data("value");
        inputselect.val(value);
        $(divselectid + " ul").hide();
        if (fn) {
            fn($(this));
        }
    });
    $(document).on("click", function () {
        $(divselectid + " ul").hide();
    });
};

/* jq仿select带返回函数 end */

/* 未登录弹出框 start */
jQuery.notLogin = function (backUrl) {
    if (!backUrl) backUrl = location.href;
    $.ajax({
        type: 'GET',
        url: '/home/login/ajaxLogin',
        data: {refer: backUrl},
        success: function (data) {
            pb({
                id: "loginDialogBody",
                title: '您还没有登录哦',
                width: 380,
                height: 430,
                content: data,
                drag: false,
                foot: false
            });
        }
    });
};

function searchInStore(obj) {
    var keywords = $(obj).parent().find("input[name='keywords']").val();
    if (keywords == '') {
        divId = "keywords_html";
        var content = '<div id="' + divId + '">' +
            '<div class="tip-box icon-box">' +
            '<span class="warn-icon m-icon"></span>' +
            '<div class="item-fore">' +
            '<h3 class="rem ftx-04">请输入搜索关键词！</h3>' +
            '</div>' +
            '</div>' +
            '</div>';

        pb({
            id: divId,
            title: '系统提示',
            width: 445,
            height: 58,
            content: content,
            drag: false,
            foot: false
        });

        return false;
    }
    $(obj).parent().attr('action', '/home/store/category/id/' + $('input[name="store_id"]').val() + '.html').submit();
}

function checkSearchForm(obj, search_store) {
    var keywords = $(obj).parent().find("input[name='keywords']").val();
    if (keywords == '') {
        divId = "keywords_html";
        var content = '<div id="' + divId + '">' +
            '<div class="tip-box icon-box">' +
            '<span class="warn-icon m-icon"></span>' +
            '<div class="item-fore">' +
            '<h3 class="rem ftx-04">请输入搜索关键词！</h3>' +
            '</div>' +
            '</div>' +
            '</div>';

        pb({
            id: divId,
            title: '系统提示',
            width: 445,
            height: 58,
            content: content,
            drag: false,
            foot: false
        });

        return false;
    }
    if (search_store) {
        $('#searchForm').attr('action', '/home/index/store_street');
    }
    $(obj).parent().submit();
}

//购物车点击去结算判断是否选择商品
function get_toCart() {
    var num = 0;
    var checkItem = $("input[name='checkItem']");
    var fale = true;
    var recid = "";

    checkItem.each(function (index, element) {
        if ($(element).is(":checked")) {
            var Item = $(this).parents("*[shoptype='item']");
            rec_id = Item.data("recid");

            recid += rec_id + ',';

            num++;
        }
    });

    recid = recid.substr(0, recid.length - 1);

    if (num == 0) {
        var content = $("#flow_add_cart").html();
        pb({
            id: "flow_add_cart",
            title: json_languages.pb_title,
            width: 455,
            height: 58,
            content: content,
            drag: false,
            foot: false
        });
        fale = false;
    } else {
        if (recid != "") {
            $.ajax({
                url: 'flow.php?act=check_cart_goods',
                type: 'post',
                dataType: 'json',
                data: {
                    'rec_id': recid
                },
                async: false,
                success: function (result) {
                    if (result.error > 0) {
                        pbDialog(result.message, "", 0, '', '', 10);

                        fale = false;
                    }
                }
            });
        }
    }

    return fale;
}

//购物车和结算页面 结算按钮悬浮显示start
function tfootScroll() {
    var winHeight = $(window).height();
    var toolbar = $("*[shoptype='tfoot-toolbar']");
    var toolbarTop = toolbar.offset().top;
    var scrollTop = $(document).scrollTop();

    if (toolbarTop > winHeight) {
        toolbar.addClass("fixed-bottom");
    }

    $(window).resize(function () {
        winHeight = $(window).height();

        if (toolbarTop > (winHeight + scrollTop)) {
            toolbar.addClass("fixed-bottom");
        } else {
            toolbar.removeClass("fixed-bottom");
        }
    });

    $(window).scroll(function () {
        scrollTop = $(document).scrollTop();

        if (scrollTop + winHeight > toolbarTop) {
            toolbar.removeClass("fixed-bottom");
        } else {
            toolbar.addClass("fixed-bottom");
        }
    });
}

//购物车和结算页面 结算按钮悬浮显示end

/*商品降价通知 提交*/
function notifyBox(user_id, goods_id, divid) {
    var hopeDiscount = $(divid).find("input[name='price-notice']").val();
    var cellphone = $(divid).find("input[name='cellphone']").val();
    var email = $(divid).find("input[name='email']").val();

    //var res = checkform(hopeDiscount,cellphone,email);

    /*if(!res){
		return false;
	}*/

    jQuery.ajax({
        url: 'ajax_dialog.php?act=price_notice',
        type: 'post',
        dataType: 'json',
        data: {
            'user_id': user_id,
            'goods_id': goods_id,
            'hopeDiscount': hopeDiscount,
            'cellphone': cellphone,
            'email': email
        },
        cache: false,
        success: function (result) {
            if (result.status == 0) {
                pbDialog(result.msg, "", 0);
            } else {
                pbDialog(result.msg, "", 0);
            }
        },
        error: function () {
        }
    });
}

/****************************结算页面收货地址修改新增 保存方法 start*****************************/

function addUpdate_Consignee(frm) {
    frm = $("form[name='" + frm + "']");
    var csg = {}, fale = false;

    csg.consignee = frm.find("input[name='consignee']").val();

    csg.country = frm.find("[name='country']").val();
    csg.province = frm.find("[name='province']").val();
    csg.city = frm.find("[name='city']").val();
    csg.district = frm.find("[name='district']").val();
    csg.address = frm.find("[name='address']").val();
    csg.zipcode = frm.find("[name='zipcode']").val();
    csg.sign_building = frm.find("[name='sign_building']").val();
    csg.best_time = frm.find("[name='best_time']").val();

    csg.mobile = frm.find("[name='mobile']").val();
    csg.tel = frm.find("[name='tel']").val();
    csg.email = frm.find("[name='email']").val();
    csg.address_id = frm.find("[name='address_id']").val();

    frm.validate({
        errorPlacement: function (error, element) {
            var error_div = element.parents('div.form-value').find('div.form_prompt');
            error_div.html("").append(error);
        },
        ignore: ".ignore",
        rules: {
            consignee: {
                required: true
            },
            mobile: {
                required: true,
                isMobile: true
            },
            country: {
                min: 1
            },
            province: {
                min: 1
            },
            city: {
                min: 1
            },
            district: {
                min: 1
            },
            address: {
                required: true
            }
        },
        messages: {
            consignee: {
                required: json_languages.input_Consignee_name
            },
            mobile: {
                required: json_languages.msg_phone_blank,
                isMobile: json_languages.phone_format_error
            },
            country: {
                min: json_languages.Country
            },
            province: {
                min: json_languages.Province
            },
            city: {
                min: json_languages.City
            },
            district: {
                min: json_languages.District
            },
            address: {
                required: json_languages.Detailed_address_null
            }
        }
    });
    if (frm.valid()) {
        //$("#consignee-addr").html("<div class='load'><img src='/public/static/images/load.gif' width='200' height='200' /></div>");
        $.ajax({
            type: 'POST',
            url: '/home/member/ajaxEditAddress',
            data: csg,
            dataType: 'json',
            success: addUpdate_ConsigneeResponse,
            error: function (xhr, type) {
                pbDialog(xhr.responseJSON, "", 0);
            }
        });
        // Ajax.call('flow.php', 'step=insert_Consignee&csg=' + $.toJSON(csg) + '&shipping_id=' + $.toJSON(shipping_id) + '&uc_id=' + uc_id, addUpdate_ConsigneeResponse, 'POST', 'JSON');

        fale = true;
    }

    return fale;
}


/****************************结算页面收货地址修改新增 保存方法 start*****************************/

/******************************门店选择 切换城市*******************************/
function regionSelect(ru_id, goods_id) {
    var hoverTimer, outTimer, _this, level = 0, id = 0, name = "";
    var changeCity = "#latelStorePick .change-shop-city",
        changeBoxinfo = ".city-box-info",
        tab = ".city-tab .city-item",
        cityItem = ".city-box .city-item",
        catyHst = ".city-hot .city-item",
        shopitem = ".select-shop-box .shop-info-item",
        doc = $(document);

    //鼠标移动到切换城市展开所有城市选择
    doc.on("mouseenter", changeCity, function () {
        clearTimeout(outTimer);
        _this = $(this);
        hoverTimer = setTimeout(function () {
            _this.parents(".city-box-tit").siblings(".city-box-info").show();
        }, 100);
    })
        .on("mouseleave", changeCity, function () {
            clearTimeout(hoverTimer);
            _this = $(this);
            outTimer = setTimeout(function () {
                _this.parents(".city-box-tit").siblings(".city-box-info").hide();
            }, 100);
        })
        .on('mouseenter', changeBoxinfo, function () {
            clearTimeout(outTimer);
            _this = $(this);
            _this.show();
        })
        .on('mouseleave', changeBoxinfo, function () {
            _this = $(this);
            _this.hide();
        })
        .off("click", catyHst).on('click', catyHst, function () {
        var spec_arr = '';
        var formBuy = document.forms['ECS_FORMBUY'];
        if (formBuy) {
            spec_arr = getSelectedAttributes(formBuy);
        }
        _this = $(this), level = _this.data("level"), id = _this.data("id"), name = _this.data("name");

        var province = 0, city = 0, district = 0;
        if (level == 1) {
            province = id;
        } else if (level == 2) {
            city = id;
        } else {
            district = id;
        }
        check_store(province, city, district, goods_id, spec_arr);
    })
        .off("click", tab).on("click", tab, function () {
        var spec_arr = '';
        var formBuy = document.forms['ECS_FORMBUY'];
        if (formBuy) {
            spec_arr = getSelectedAttributes(formBuy);
        }
        //地区三级联动切换
        _this = $(this), level = _this.data("level"), id = _this.data("id"), name = _this.data("name");
        _this.addClass("curr").siblings().removeClass("curr");
        Ajax.call("get_ajax_content.php?act=get_parent_regions", 'value=' + id + "&level=" + level + "&ru_id=" + ru_id + '&goods_id=' + goods_id + "&spec_arr=" + spec_arr, function (data) {
            $(".city-box").html(data.html);
        }, 'POST', 'JSON');
    })
        .off("click", cityItem).on("click", cityItem, function () {
        /*获取属性*/
        var spec_arr = '';
        var formBuy = document.forms['ECS_FORMBUY'];
        if (formBuy) {
            spec_arr = getSelectedAttributes(formBuy);
        }
        //地区选择
        _this = $(this), level = _this.data("level"), id = _this.data("id"), name = _this.data("name");

        var cityTab = _this.parents(".city-box").siblings(".city-tab");

        cityTab.find("[data-level=" + (level + 1) + "]").addClass("curr").siblings().removeClass("curr");

        cityTab.find("[data-level=" + level + "]").html(name).attr("data-id", id).attr("data-name", name);

        if (level < 3) {
            Ajax.call("get_ajax_content.php?act=getstoreRegion", 'value=' + id + "&level=" + level + "&ru_id=" + ru_id + '&goods_id=' + goods_id + "&spec_arr=" + spec_arr, function (data) {
                $(".city-box").html(data.html);
            }, 'POST', 'JSON');
        } else {
            var str = "";

            $(tab).each(function () {
                name = $(this).attr("data-name");
                str += name + "&nbsp;";
            });

            $(changeBoxinfo).hide();
            $(changeCity).find("em").html(str);

            var province = 0, city = 0, district = 0;

            if (level == 1) {
                province = id;
            } else if (level == 2) {
                city = id;
            } else {
                district = id;
            }

            check_store(province, city, district, goods_id, spec_arr);
        }
    })
        .off("click", shopitem).on("click", shopitem, function () {
        _this = $(this);
        _this.addClass("active").siblings().removeClass("active");
    });
}

function check_store(province, city, district, goods_id, spec_arr) {
    Ajax.call("ajax_dialog.php?act=get_store_list", 'province=' + province + '&city=' + city + '&district=' + district + '&goods_id=' + goods_id + "&spec_arr=" + spec_arr + "&type=store_select_shop", function (data) {
        $(".select-shop").html(data.content);
    }, 'POST', 'JSON');
}

/******************************门店选择 切换城市*******************************/

/******************************结算页面 门店选择 start*******************************/

//门店结算页面切换门店时间 start
function checked_store_info() {
    var end_time = $("input[name='take_time']").val();
    var store_mobile = $("input[name='store_mobile']").val();
    var cart_value = $("input[name='done_cart_value']").val();
    var store_mobile_data = $("input[name='store_mobile']").data('val');
    if (store_mobile == '') {
        $("input[name='store_mobile']").val(store_mobile_data);
        pbDialog(json_languages.login_phone_packup_one, "", 0);
        $("input[name='store_mobile']").focus();
        return false;
    } else if (!Utils.isTel(store_mobile) || store_mobile.length != 11) {
        pbDialog(json_languages.msg_phone_not, "", 0);
        $("input[name='store_mobile']").focus();
        return false;
    } else {
        Ajax.call("get_ajax_content.php?act=checked_store_info", 'end_time=' + end_time + '&store_mobile=' + store_mobile + "&cart_value=" + cart_value, function (data) {
            if (data.error == 1) {
                pbDialog(data.message, "", 0);
            }
        }, 'POST', 'JSON');
    }
}

//门店结算页面切换门店时间 end

//获取门店
function get_store_list(district, type, cart_value) {
    var province = $("#selProvinces").find("input[name='province']").val();
    var city = $("#selCities").find("input[name='city']").val();

    if (district > 0) {
        Ajax.call('ajax_dialog.php?act=get_store_list', 'province=' + province + '&city=' + city + '&district=' + district + '&cart_value=' + cart_value + '&type=' + type, get_store_listResponse, 'GET', 'JSON');
    }
}

function get_store_listResponse(result) {
    var div = $("*[shoptype='get_seller_sotre']");
    if (result.error > 0) {
        div.find("#seller_soter *[shoptype='layer']").html(result.content);
        div.find(".error").html('');
    } else {
        div.find("#seller_soter *[shoptype='layer']").html('');
        div.find("#seller_soter").hide();
        div.find(".error").html('<i class="s_icon"></i>该地区没有门店');
    }
}

//切换门店，处理点单页面刷新，检查商品库存
function edit_offline_store(obj) {
    var store_id = $(obj).data("value");
    var cart_value = $("input[name='done_cart_value']").val();
    var txt = $(obj).text();

    $("input[name='store_id']").val(store_id);
    $(obj).parents("*[shoptype='smartdropdown']").find("*[shoptype='txt']").html(txt);

    if (store_id > 0) {
        Ajax.call('flow.php?step=edit_offline_store', 'store_id=' + store_id + '&cart_value=' + cart_value, edit_offline_storeResponse, 'GET', 'JSON');
    }
}

function edit_offline_storeResponse(result) {
    if (result.error > 0) {
        if (result.error == 1) {
            var back_url = "flow.php";
            $.notLogin("get_ajax_content.php?act=get_login_dialog", back_url);
            return false;
        } else {
            pbDialog(result.msg, "", 0);
            return false;
        }
    } else {
        $('#goods_inventory').html(result.goods_list);//送货清单
        $('#ECS_ORDERTOTAL').html(result.order_total);//费用汇总
        $("input[name='store_id']").val();
    }
}

/******************************结算页面 门店选择 end*******************************/

//配送方式切换计算运费
function changeShippingResponse(result) {
    $(".shipping_" + result.ru_id).val(result.shipping_id);
    $(".shipping_code_" + result.ru_id).val(result.shipping_code);
    $(".shipping_type_" + result.ru_id).val(result.shipping_type);

    if (result.error) {
        pbDialog(result.massage, "", 0);
        location.href = './flow.php?step=checkout';
    }

    try {
        var layer = document.getElementById("ECS_ORDERTOTAL");
        layer.innerHTML = (typeof result == "object") ? result.content : result;
    } catch (ex) {
    }
}

//自提点回调函数
function selectPicksiteResponse(result) {
    if (result.error == 0) {
        $("#goods_inventory").html(result.content);
    } else {
        pbDialog(result.massage, "", 0);
        location.href = './';
    }
}

//众筹支持者列表 by wu
function get_backer_list(zcid, page) {
    $.ajax({
        type: 'get',
        url: 'crowdfunding.php',
        data: 'act=get_backer_list&zcid=' + zcid + "&page=" + page,
        dataType: 'json',
        success: function (data) {
            $("#backer_list").html(data.content);
        }
    });
}

//众筹话题列表 by wu
function get_topic_list(zcid, page) {
    $.ajax({
        type: 'get',
        url: 'crowdfunding.php',
        data: 'act=get_topic_list&zcid=' + zcid + "&page=" + page,
        dataType: 'json',
        success: function (data) {
            $("#topic_list").html(data.content);
        }
    });
}

//商品属性图片和商品相册关联切换 start
function getImgUrl(result) {
    if (result.t_img != '') {
        $('#Zoomer').attr({href: "" + result.t_img + ""});
        $('#J_prodImg').attr({src: "" + result.t_img + ""});
        $('.MagicBoxShadow').eq(0).find('img').eq(0).attr({src: "" + result.t_img + ""});
        $('.MagicThumb-expanded').find('img').attr({src: "" + result.t_img + ""});
    }
}

//商品属性图片和商品相册关联切换 end

//input文本框 提示文字
jQuery.inputPrompt = function (s, c, v) {
    var s = $(s);
    s.focus(function () {
        if ($(this).val() == v) {
            $(this).val("");
            if (c == true) {
                $(this).css("color", "#666");
            }
        }
    });
    s.blur(function () {
        if ($(this).val() == '') {
            $(this).val(v);
            if (c == true) {
                $(this).css("color", "#999");
            }
        } else {
            if (c == true) {
                $(this).css("color", "#666");
            }
        }
    });
};

//返回顶部（品牌专区使用到）
$.scrollTop = function (mode, obj) {
    var right = ($(window).width() - 1200) / 2 - 30;
    var top = $(window).height() - 100;
    var blTop = $(mode).offset().top;
    $(obj).css({"right": right, "top": top});

    $(window).scroll(function () {
        var sTop = $(window).scrollTop();
        if (sTop > blTop) {
            $(obj).removeClass("returnHide");
        } else {
            $(obj).addClass("returnHide");
        }
    });

    $(obj).click(function () {
        $("body,html").stop().animate({scrollTop: 0});
    });
};

//邮箱订阅
function add_email_list() {
    var email = $('#user_email').val();

    if (Utils.isEmail(email)) {
        Ajax.call('user.php?act=email_list&job=add&email=' + email, '', function (text) {

            pbDialog(text, "", 0);

        }, 'GET', 'TEXT');
    } else {
        pbDialog(json_languages.email_error, "", 0);
        return false;
    }
}

function cancel_email_list() {
    var email = $('#user_email').val();
    if (Utils.isEmail(email)) {
        Ajax.call('user.php?act=email_list&job=del&email=' + email, '', function (text) {

            pbDialog(text, "", 0);

        }, 'GET', 'TEXT');
    } else {
        pbDialog(json_languages.email_error, "", 0);
        return false;
    }
}

/****************************************** js通用方法 end *************************************************/


/**************************************店铺街(store_street)end ***************************************/
$(function () {
    $("#res_store_user").val('');
    $("#res_store_province").val('');
    $("#res_store_city").val('');
    $("#res_store_district").val('');

    var orderName = 'ASC';
    $("*[ shoptype='seller_sort']").on("click", function () {
        var T = $(this);
        var sortName = T.data('sort');
        if (orderName == 'ASC') {
            orderName = 'DESC';
            T.children('i').removeClass("icon-up1").addClass("icon-down1");
        } else {
            orderName = 'ASC';
            T.children('i').removeClass("icon-down1").addClass("icon-up1");
        }
        T.addClass('curr').siblings().removeClass('curr');
        var area_list = $("input[name='area_list']").val();
        var strText = area_list + "|" + "sort-" + sortName + "|" + "order-" + orderName;
    });

    $(document).on("click", "*[ shoptype='street_area']", function () {
        var _this = $(this),
            store_user = $("#res_store_user").val(),
            store_province = $("#res_store_province").val(),
            store_city = $("#res_store_city").val(),
            store_district = $("#res_store_district").val(),
            val = _this.data('val'),
            search_type = _this.data('type'),
            region_type = _this.data('region');

        var area = new Object();
        area.region_id = val;
        area.region_type = region_type;
        area.store_user = store_user;
        area.store_province = store_province;
        area.store_city = store_city;
        area.store_district = store_district;

        Ajax.call('store_street.php?act=' + search_type, 'area=' + $.toJSON(area), function (result) {
            var store_user = '',
                province = '',
                city = '',
                district = '';

            if (result.error == 0) {
                if (result.region_type == 2) {
                    $('#store_city').html(result.content);
                    $('#store_district').html('');
                } else if (result.region_type == 3) {
                    $('#store_district').html(result.content);
                }
            }

            if (result.store_province) {
                province = result.store_province;
            }

            if (result.store_city) {
                city = result.store_city;
            }

            if (result.store_district) {
                district = result.store_district;
            }

            if (result.store_user) {
                store_user = result.store_user;
            }

            $("#res_store_user").val(store_user);
            $("#res_store_province").val(province);
            $("#res_store_city").val(city);
            $("#res_store_district").val(district);
            _this.parents("li").addClass('curr').siblings().removeClass('curr');
            $("*[ shoptype='seller_sort']").first().addClass('curr').siblings().removeClass('curr');
            $("input[name='area_list']").val(result.id);
            store_shop_gotoPage_new(1, result.id, 0);
            slClick();
        }, 'POST', 'JSON'); //兼容jQuery by mike
    });
    slClick();

    $(document).on("click", "*[shoptype='collect_store']", function () {
        var _this = this;
        var store_id = $(this).data('store_id'),
            type = 0,
            user_id = $('input[name="user_id"]').val();

        if ($(this).hasClass("selected")) {
            type = 1;
        }

        if (user_id > 0) {
            if (type == 1) {
                pbDialog(json_languages.Focus_prompt_four, "", 0, 455, 78, "", true, function () {
                    ajax_collect_store(store_id, type, _this, user_id);
                });
            } else {
                ajax_collect_store(store_id, type, _this, user_id);
            }
        } else {
            $.notLogin();
        }
    });

    /* *
	 * 店铺街列表
	 */
    function store_shop_gotoPage_new(page, id, type, libType) {
        Ajax.call('ajax_dialog.php?act=store_shop_gotoPage', 'page=' + page + '&id=' + id + '&type=' + type + '&libType=' + libType, store_shop_gotoPageResponse, 'GET', 'JSON');
    }

    function store_shop_gotoPageResponse(result) {
        $("*[shoptype='store_shop_list']").html(result.content);
        $("*[shoptype='pages_ajax']").html(result.pages);
        street();
    }

});

function slClick() {
    $(".s-l-v-list li").find("a").click(function () {
        $(this).parent().addClass("curr").siblings().removeClass("curr");
    });
}

function ajax_collect_store(store_id, type, obj, user_id) {
    var origin = $(obj).data('type');
    $.ajax({
        type: 'POST',
        url: '/home/store/collect',
        data: {store_id: store_id, user_id: user_id, type: type},
        success: function (data) {
            if (type == 1) {
                if (origin == 'store') {
                    $(obj).removeClass("selected").html("<span>关注店铺</span><i class='iconfont icon-zan-alt'></i>");
                } else if (origin == 'goods') {
                    $(obj).removeClass("selected").html("<i class='iconfont icon-zan-alt'></i>关注");
                } else {
                    $(obj).removeClass("selected").html("<i class='iconfont icon-zan-alt'></i>关注店铺");
                }
            } else {
                if (origin == 'store') {
                    $(obj).addClass("selected").html("<span>已关注</span><i class='iconfont icon-zan-alts'></i>");
                } else if (origin == 'goods') {
                    $(obj).addClass("selected").html("<i class='iconfont icon-zan-alts'></i>已关注");
                } else {
                    $(obj).addClass("selected").html("<i class='iconfont icon-zan-alts'></i>已关注");
                }
            }
        },
        error: function (xhr, type) {
            layer.msg(xhr.responseJSON, {time: 1500});
        }
    });
}

/************************************** 店铺街(store_street)end ***************************************/

//jqueryAjax异步加载
$.jqueryAjax = function (url, data, ajaxFunc, type, dataType) {
    var baseData = "is_ajax=1&";
    var baseFunc = function () {
    };

    if (!url) {
        url = "index.php";
    }

    if (!data) {
        data = "";
    }

    if (!type) {
        type = "get";
    }

    if (!dataType) {
        dataType = "json";
    }

    if (!ajaxFunc) {
        ajaxFunc = baseFunc;
    }

    data = baseData + data;

    $.ajax({
        type: type,
        url: url,
        data: data,
        dataType: dataType,
        success: ajaxFunc.success ? ajaxFunc.success : ajaxFunc,
        error: ajaxFunc.error ? ajaxFunc.error : baseFunc,
        beforeSend: ajaxFunc.beforeSend ? ajaxFunc.beforeSend : baseFunc,
        complete: ajaxFunc.complete ? ajaxFunc.complete : baseFunc,
        //dataFilter:ajaxFunc.dataFilter? ajaxFunc.dataFilter:baseFunc
    });
};

//提示弹框
function pbDialog(msgTitle, msg, state, width, height, left, cBtn, onOk, ok_title, cl_title, pb_title) {
    //msgTitle 主提示信息
    //msg 次标题信息
    //state 状态 0表示感叹 1表示正确 2表示错误
    //width 弹出框宽度
    //height 弹出框高度
    //left 右边距
    //cBtn 弹出框取消按钮是否显示
    //onOk 点击确定返回函数
    var content = "",
        icon = "m-icon",
        msgTit = "",
        msgSpan = "";
    css = "",
        foot = true,
        color = "ftx-04";

    if (state == 0) {
        icon = "m-icon";
    } else if (state == 1) {
        icon = "m-icon warn-icon-ok";
        color = "ftx-16";
    } else if (state == 2) {
        icon = "m-icon warn-icon-error";
        color = "ftx-01";
    }

    if (msgTitle != "") {
        if (msg != "") {
            msgTit = "<h3 class='" + color + "'>" + msgTitle + "</h3>";
        } else {
            msgTit = "<h3 class='" + color + " rem'>" + msgTitle + "</h3>";
        }
    }

    if (msg != "") {
        msgSpan = "<span class='ftx-03'>" + msg + "</span>";
    }

    if (width == null || width == "") {
        width = 450;
    }

    if (height == null || height == "") {
        height = 80;
    }

    if (left == null || left == "") {
        left = 100;
        leftCss = "padding:0 0 0 100px";
    } else {
        leftCss = "padding:0 " + left + "px;";
    }

    if (onOk == null || onOk == "") {
        foot = false;
    }

    if (ok_title == null || ok_title == "") {
        ok_title = json_languages.determine;
    }

    if (cl_title == null || cl_title == "") {
        cl_title = json_languages.cancel;
    }

    if (pb_title == null || pb_title == "") {
        pb_title = json_languages.pb_title;
    }

    if (typeof (height) == "string") {
        content = '<div class="tip-box icon-box" style="min-height:' + height + 'px; ' + leftCss + '"><span class="warn-icon ' + icon + '"></span><div class="item-fore">' + msgTit + msgSpan + '</div></div>';
    } else {
        content = '<div class="tip-box icon-box" style="height:' + height + 'px; ' + leftCss + '"><span class="warn-icon ' + icon + '"></span><div class="item-fore">' + msgTit + msgSpan + '</div></div>';
    }

    pb({
        id: "pbDialog",
        title: pb_title,
        width: width,
        height: height,
        content: content,
        drag: false,
        foot: foot,
        ok_title: ok_title,
        cl_title: cl_title,
        cl_cBtn: cBtn,
        onOk: onOk
    });

    var tipbox = $('#pbDialog .tip-box'),
        item_height = tipbox.find(".item-fore").height();

    if (item_height > 48) {
        tipbox.find('h3').css({"line-height": "30px"});
    }

    if (typeof (height) == "string") {
        tipbox.parents(".pb-ct").css({"height": "auto", "min-height": height});
    }

    tipbox.css({"padding-left": left});
}

/* 首页楼层分类切换函数 */
function get_homefloor_cat_content(f_this) {
    var ajax = $(f_this).data("ajax");
    var visualItme = $(f_this).parents("*[shoptype='visualItme']");

    var cat_id = $(f_this).data('id');
    var num = $(f_this).data("floornum");


    if (ajax == 0) {
        $.ajax({
            type: "GET",
            url: "/home/index/ajaxFloorContent",
            data: {cat_id: cat_id, num: num},
            success: function (data) {
                $(f_this).data("ajax", 1);
                visualItme.find("*[shoptype='floor_cat_" + cat_id + "']").html(data);
            }
        });
    }
}

/* 首页可视化 第八套模板 楼层右侧前后轮播滚动 */
function aroundSilder(obj) {
    var obj = $(obj);
    var num = 0;

    var arr = [
        {width: 195, height: 330, top: 0, left: 0},
        {width: 170, height: 330, top: -9, left: 12},
        {width: 150, height: 330, top: -18, left: 22}
    ];

    var arr2 = [[100, 90, 80], [0, 0.5, 0.8]];

    obj.each(function (index, ele) {
        var t = $(this),
            bd = t.find(".bd"),
            hd = t.find(".hd"),
            li = bd.find("li"),
            length = bd.find("li").length,
            html = "";

        if (length > 1) {
            for (var i = 0; i < length; i++) {
                if (i == 0) {
                    html += "<li class='on'></li>";
                } else {
                    html += "<li></li>";
                }
            }
            hd.find("ul").append(html);
        }
        auto(t);
    });

    obj.find(".hd li").mouseenter(function () {
        var index = $(this).index();
        var parent = $(this).parents(".floor_silder");

        doMove(index, parent);
    });

    function doMove(num, t) {
        var bd_li = t.find(".bd li");
        var hd_li = t.find(".hd li");
        var k = 1;
        if (bd_li.length == 2) {
            k = 1;
        } else {
            k = 2;
        }

        for (var i = 0; i < bd_li.length; i++) {
            if (num == 0) {
                bd_li.eq(i).stop(true, false).animate(arr[i]);
                bd_li.eq(i).css("z-index", arr2[0][i]);
                bd_li.eq(i).find(".color_mask").stop(true, false).animate({'opacity': arr2[1][i]});
            } else if (num == 1) {
                bd_li.eq(i - k).stop(true, false).animate(arr[i]);
                bd_li.eq(i - k).css("z-index", arr2[0][i]);
                bd_li.eq(i - k).find(".color_mask").stop(true, false).animate({'opacity': arr2[1][i]});
            } else {
                bd_li.eq(i - 1).stop(true, false).animate(arr[i]);
                bd_li.eq(i - 1).css("z-index", arr2[0][i]);
                bd_li.eq(i - 1).find(".color_mask").stop(true, false).animate({'opacity': arr2[1][i]});
            }
            hd_li.eq(num).addClass("on").siblings().removeClass("on");
        }
    }

    function auto(t) {
        auto.timer = setInterval(function () {
            doMove(num, t);
            if (num < 2) {
                num++;
            } else {
                num = 0;
            }
        }, 3000);
    }
}

/* IM客服点击弹出框 */
function openIm(obj) {
    var user_id = document.querySelector('input[name="user_id"]').value;
    if (user_id > 0) {
        var url = obj.dataset.url;
        var iWidth = 701;
        var iHeight = 500;
        var iTop = (window.screen.availHeight - 30 - iHeight) / 2;
        var iLeft = (window.screen.availWidth - 10 - iWidth) / 2;
        strWindowFeatures = "menubar=no,location=yes,resizable=no,scrollbars=yes,status=yes,width=" + iWidth + ",height=" + iHeight + ",minimizable=on,left=" + iLeft + ",top=" + iTop + "";
        window.open(url, 'im', strWindowFeatures);
    } else {
        $.notLogin();
        return false;
    }
}

//获取url中的参数值
function getUrlQueryString(name) {
    var reg = new RegExp('(^|&)' + name + '=([^&]*)(&|$)', 'i');
    var r = window.location.search.substr(1).match(reg);
    if (r != null) {
        return unescape(r[2]);
    }
    return null;
}

/* 详情页 数量选择(商品详情页、团购详情页、秒杀详情页、积分商品详情页、预售详情页)*/
function quantity() {
    var quantity = $("*[shoptype='quantity']"),	//数量input
        btnReduce = $("[shoptype='btnReduce']"),	//数量 减
        btnAdd = $("[shoptype='btnAdd']"),		//数量 加
        message = '';							//提示文字

    var qNumber = Number(quantity.val()),									//购买选择的数量
        perNumber = Number($("*[shoptype='perNumber']").val()),	   			//库存数量
        perMinNumber = Number($("*[shoptype='perMinNumber']").val()),			//最小值 默认为1
        restrictShop = Number($("*[shoptype='restrictShop']").val()),			//是否开启限购 1为开启；0为未开启
        rNumber = Number($("*[shoptype='restrictNumber']").data("value")),	//限购数量
        ogNumber = Number($("*[shoptype='orderGNumber']").data("value"));		//限购已购数量

    //商品数量减少
    btnReduce.on("click", function () {
        if (qNumber > perMinNumber) {
            qNumber -= 1;

            quantity.val(qNumber);

            if (qNumber == 1) {
                $(this).addClass("btn-disabled");
            }
            btnAdd.removeClass("btn-disabled");
        } else {
            quantity.val(perMinNumber);
        }
    });

    //商品数量增加
    btnAdd.on("click", function () {
        if (perNumber > qNumber) {
            qNumber += 1;
            if (qNumber == perNumber) {
                btnAdd.addClass("btn-disabled");
            }
            restrictShopFunc();
        } else {
            if (perNumber == 0) {
                perNumber = 1;
            }
            quantity.val(perNumber);
            btnAdd.addClass("btn-disabled");
        }
    });

    //商品数量修改
    quantity.on('blur', function () {
        if ($(this).val() > 0) {
            if ($(this).val() > perNumber) {
                qNumber = perNumber;
                btnAdd.addClass("btn-disabled");
            } else {
                qNumber = Number($(this).val());
                btnAdd.removeClass("btn-disabled");
            }

            if ($(this).val() == 1) {
                btnReduce.addClass("btn-disabled");
            }
        } else {
            qNumber = 1;
        }
        restrictShopFunc();
        //changePrice();
    });

    restrictShopFunc = function () {
        //限购
        if (restrictShop > 0) {
            if (ogNumber >= rNumber) {
                message = json_languages.Already_buy + ogNumber + json_languages.Already_buy_two;

                pbDialog(message, "", 0, 550, "");
                qNumber = 1;
            } else if (qNumber > rNumber && rNumber > 0) {
                message = json_languages.Purchase_quantity;

                pbDialog(message, "", 0);
                qNumber = 1;
            }
        }
        quantity.val(qNumber);
        if (qNumber != 1) {
            btnReduce.removeClass("btn-disabled");
        }
    };
};

//积分兑换商品详情 立刻兑换
function get_exchange() {
    /* by kong start 改  */
    var quantity = Number($("*[shoptype='quantity']").val());  //购买数量
    var number = Number($("*[shoptype='perNumber']").val());	 //库存
    var payPoints = $("*[shoptype='payPoints']").val();//会员积分
    var ei = $("*[shoptype='exchange_integral']").val();//兑换商品需要积分值
    if (user_id > 0) {
        if (quantity > number) {
            var message = json_languages.most_exchange + number + json_languages.Piece_goods;
            pbDialog(message, "", 0);
            return false;
        }

        if (ei * quantity > payPoints) {
            pbDialog(json_languages.exchange_error_one, "", 0, 550, 80);
            return false;
        }
    } else {
        var goods_id = $("input[name='good_id']").val();
        var back_url = "exchange.php?act=view&id=" + goods_id;
        $.notLogin("get_ajax_content.php?act=get_login_dialog", back_url);
        return false;
    }
    /*by kong*/
}

//商城右侧悬浮黑导航栏展开高度只适应
function tbplHeigth() {
    var winHeight = $(window).height();
    var chaHeight = $("*[shoptype='tbpl-content']").data("height");

    $("*[shoptype='tbpl-main']").css({"height": winHeight - 38});
    $("*[shoptype='tbpl-content']").css({"height": winHeight - chaHeight});

    $(window).resize(function () {
        winHeight = $(this).height();
        $("*[shoptype='tbpl-main']").css({"height": winHeight - 38});
        $("*[shoptype='tbpl-content']").css({"height": winHeight - chaHeight});
    });
}

//加载中
function ajaxLoadFunc(obj) {
    var html = "<div class='shop-load-mask'></div><div class='shop-loadding'><img src='/public/static/images/ajaxLoader.gif'><p>正在拼命加载中...</p></div>";
    if (obj) {
        if (!obj.context) {
            obj = $(obj);
        }
        obj.append(html);
    } else {
        $("body").append(html);
    }
}

function ajaxLoadClose() {
    $('.shop-load-mask').remove();
    $('.shop-loadding').remove();
}

/* 店铺关注 */
function goods_collect_store(seller_id) {
    Ajax.call('ajax_dialog.php', 'act=goods_collect_store&seller_id=' + seller_id, goodsCollectStorenResponse, 'GET', 'JSON');
}

function goodsCollectStorenResponse(res) {

    if (res.error > 0) {

        if ($(".gz-store").length > 0) {
            $(".gz-store").html('<i class="iconfont icon-zan-alts"></i>已关注');
            $(".gz-store").addClass('selected');
        }

        if ($(".gz-store-top").length > 0) {
            $(".gz-store-top").html('<span>已关注</span><i class="iconfont icon-zan-alts"></i>');
            $(".gz-store-top").addClass('selected');
        }
    } else {

        if ($(".gz-store").length > 0) {
            $(".gz-store").html('<i class="iconfont icon-zan-alt"></i>关注');
            $(".gz-store").removeClass('selected');
        }

        if ($(".gz-store-top").length > 0) {
            $(".gz-store-top").html('<span>关注</span><i class="iconfont icon-zan-alt"></i>');
            $(".gz-store-top").removeClass('selected');
        }
    }
}

function increase() {
    //封顶价
    var maxPrice = 0;
    //当前价
    var currentPrice = 1;
    //起拍价
    var startPrice = 1;
    //最低加价幅度
    var priceLowerOffset = 1;
    //最高加价幅度
    var priceHigherOffset = 1000;

    /**
     * 正在拍卖：点+
     * */
    incre = function () {
        var userprice = $("#buyPrice").val();
        var price = Number($.trim(userprice));
        maxPrice = Number($("#maxPrice").val());
        currentPrice = Number($("#currentPrice").data("price"));
        priceLowerOffset = Number($("#priceLowerOffset").text());
        var limitPrice = !isNaN(maxPrice) && maxPrice >= 1;
        if (limitPrice) {
            if (price + priceLowerOffset > maxPrice) {
                $("#buyPrice").val(maxPrice);
                pbDialog(json_languages.highest_price, "", 0);
                return;
            }
        }

        if (price + priceLowerOffset < currentPrice) {
            $("#buyPrice").val(currentPrice + priceLowerOffset);
        } else if (price + priceLowerOffset >= currentPrice) {
            $("#buyPrice").val(price + priceLowerOffset);
        } else {
            $("#buyPrice").val(currentPrice + priceLowerOffset);
        }
    };


    /**
     * 正在拍卖：点-
     * */
    decre = function () {
        var userprice = $("#buyPrice").val();
        var price = Number(jQuery.trim(userprice));
        maxPrice = Number($("#maxPrice").val());
        currentPrice = Number($("#currentPrice").data("price"));
        priceLowerOffset = Number($("#priceLowerOffset").text());
        var limitPrice = !isNaN(maxPrice) && maxPrice >= 1;
        if (limitPrice) {
            if (price - priceLowerOffset > maxPrice) {
                $("#buyPrice").val(maxPrice);
                return;
            }
        }

        if (price - priceLowerOffset < currentPrice) {
            $("#buyPrice").val(currentPrice);
            pbDialog(json_languages.lowest_price, "", 0);
        } else if (price - priceLowerOffset >= currentPrice && price - priceLowerOffset <= currentPrice + maxPrice) {
            $("#buyPrice").val(price - priceLowerOffset);
        } else {
            $("#buyPrice").val(currentPrice);
            pbDialog(json_languages.lowest_price, "", 0);
        }
    };

    $("#buyPrice").blur(function () {
        var buyPrice = parseInt($(this).val());
        maxPrice = Number($("#maxPrice").val());
        currentPrice = Number($("#currentPrice").data("price"));
        if (buyPrice > maxPrice) {
            pbDialog("您的出价不能高于" + maxPrice, "", 0);
            $(this).val(maxPrice);
        } else if (buyPrice < currentPrice) {
            pbDialog("您的出价不能低于" + currentPrice, "", 0);
            $(this).val(currentPrice);
        } else {
            $(this).val(buyPrice);
        }
    });
}

//复制内容到剪贴板
function copyTextToClipboard(text) {
    var textArea = document.createElement("textarea");

    textArea.style.position = 'fixed';
    textArea.style.top = 0;
    textArea.style.left = 0;
    textArea.style.width = '2em';
    textArea.style.height = '2em';
    textArea.style.padding = 0;
    textArea.style.border = 'none';
    textArea.style.outline = 'none';
    textArea.style.boxShadow = 'none';
    textArea.style.background = 'transparent';
    textArea.value = text;

    document.body.appendChild(textArea);

    textArea.select();

    try {
        if (document.execCommand('copy')) {
            document.execCommand('copy');
            pbDialog("复制成功", "", 1, "", "", 120);
        } else {
            pbDialog("复制失败", "", 2, "", "", 120);
        }
    } catch (err) {
        pbDialog('不能使用这种方法复制内容', "", 0);
    }

    document.body.removeChild(textArea);
}

/***************批发js start***************/
$(function () {
    function wholesale_paymet() {
        var payment_method = $("*[shoptype='wholesalePaymentType']"),			//结算页面支付方式
            payInput = $("input[name='pay_pwd_error']"),			//结算页面其他信息 支付密码隐藏域
            length = payInput.length,								//结算页面其他信息 支付密码隐藏域 大于0表示开启
            balance = $("#qt_balance"),                             //结算页面其他信息 使用余额
            payPw = $("#qt_onlinepay"),								//结束页面其他信息 支付密码
            integObj = $("#qt_integral"),							//结算页面其他信息 使用积分
            sueplus = balance.find("input[name='surplus']"),		//余额input
            user_sueplus = sueplus.data("yoursurplus"),				//用户可用余额
            integral = integObj.find("input[name='integral']"),		//积分input
            integral_max = integral.data("maxinteg");				//此订单可用积分

        //余额和积分初始化方法
        initialize = function () {
            //积分input是否大于0
            if (integral.val() > 0) {
                //初始化积分
                integral.val(0);
                //初始化积分为0，总价去除积分抵扣价格
                changeIntegral(0);
            }

            //余额input是否大于0
            if (sueplus.val() > 0) {
                //初始化余额
                sueplus.val(0);
                //初始化余额为0，总价去除余额抵扣价格
                changeSurplus(0);
            }
        };

        payPassword = function () {
            var pay_length = payment_method.find(".item-selected").length;
            if (length > 0 && pay_length > 0) {
                var paymet_curr_val = payment_method.find(".item-selected").data("value"),  //结算页面支付方式 默认选中的支付方式value
                    paymet_curr_type = paymet_curr_val.type;								//结算页面支付方式 默认选中的支付方式类型

                //初始化
                initialize();

                if (paymet_curr_type == "balance") {
                    //余额支付状态，余额填写区域隐藏
                    balance.hide();

                    //支付状态为在线支付，并且设置了支付密码
                    if (payPw.length > 0) {
                        payPw.show();    //支付密码显示
                        payInput.val(1); //支付密码隐藏域值赋值为1
                    }
                } else {
                    //非余额支付状态，余额填写区域显示
                    //balance.show();

                    //用户余额大于0，余额显示
                    if (user_sueplus > 0) {
                        //balance.show();
                    } else {
                        balance.hide();
                    }

                    //此订单可以使用积分，积分显示
                    if (integral_max > 0) {
                        integObj.show();
                    } else {
                        integObj.hide();
                    }

                    payPw.hide();  //支付密码隐藏
                    payInput.val(0); //支付密码隐藏域值赋值为0
                }
            }
        };

        initialize(); //初始化方法默认调用
        payPassword();

        //支付方式切换
        payment_method.find(".p-radio-item").on("click", function () {
            var t = $(this),
                paymet_curr_val = t.data("value"),			//选中支付方式的value
                paymet_curr_type = paymet_curr_val.type,	//选中支付方式的type
                paymet_curr_id = paymet_curr_val.payid,     //选中支付方式的id
                paymet_curr_allow = paymet_curr_val.allow;  //选中支付方式的allow

            //初始化方法调用
            initialize();

            //支付方式选中
            t.addClass("item-selected").siblings().removeClass("item-selected");
            t.find('input').prop("checked", true);

            if (paymet_curr_type == "balance") {
                //余额支付状态，余额填写区域隐藏
                balance.hide();

                //支付状态为在线支付，并且设置了支付密码
                if (payPw.length > 0) {
                    payPw.show();    //支付密码显示
                    payInput.val(1); //支付密码隐藏域值赋值为1
                }
            } else {
                //非余额支付状态，余额填写区域显示
                //balance.show();

                if (paymet_curr_allow == 1) {
                    //判断会员是否有余额
                    changeSurplus(0);
                }

                payPw.hide();  //支付密码隐藏
                payInput.val(0); //支付密码隐藏域值赋值为0
            }

            //改变支付方式
            wholesale_selectPayment(paymet_curr_id);
        });
    }

    wholesale_paymet();

    /* *
     * 改变支付方式
     */
    function wholesale_selectPayment(value) {
        if (selectedPayment == value) {
            return;
        } else {
            selectedPayment = value;
        }

        var warehouse_id = $("#theForm").find("input[name='warehouse_id']").val();
        var area_id = $("#theForm").find("input[name='area_id']").val();
        var shipping_id = get_cart_shipping_id();

        /*by kong 门店id*/
        var store_id = document.getElementById('store_id').value;
        (store_id > 0) ? store_id : 0;
        var store_seller = document.getElementById('store_seller').value;
        Ajax.call('wholesale_flow.php?step=select_payment', 'payment=' + value + '&warehouse_id=' + warehouse_id + '&area_id=' + area_id + '&store_id=' + store_id + '&store_seller=' + store_seller + '&shipping_id=' + $.toJSON(shipping_id), orderSelectedResponse, 'GET', 'JSON');
    }

    //收货人信息切换
    $(document).on("click", "*[shoptype='w-cs-w-item']", function () {
        var $this = $(this);
        var address_id = $this.data('addressid');
        var store_id = 0;
        var shipping_id = get_cart_shipping_id();

        if ($(":input[name='uc_id']").length > 0) {
            var uc_id = $(":input[name='uc_id']").val();
        } else {
            var uc_id = 0;
        }

        $this.addClass("cs-selected").siblings().removeClass("cs-selected");

        if (document.getElementById('store_id')) {
            store_id = document.getElementById('store_id').value;
            (store_id > 0) ? store_id : 0;
        }

        Ajax.call('wholesale_flow.php?step=edit_consignee_checked', 'address_id=' + address_id + '&store_id=' + store_id + '&uc_id=' + uc_id + '&shipping_id=' + $.toJSON(shipping_id), function (result) {
            if (result.error > 0) {
                if (result.error == 1) {
                    var back_url = "wholesale_flow.php";
                    $.notLogin("get_ajax_content.php?act=get_login_dialog", back_url);
                    return false;
                } else {
                    alert(result.msg);
                    return false;
                }
            } else {
                $('#consignee-addr').html(result.content);
                $('#goods_inventory').html(result.goods_list);//送货清单
                $('#ECS_ORDERTOTAL').html(result.order_total);//费用汇总

                $('#not_freightfree').val(result.not_freightfree);
            }
        }, 'POST', 'JSON');
    });

    /* 结算页面 用户收货地址 start FIXME 无效代码*/
    $(document).on("click", "*[shoptype='wholesale_dialog_checkout']", function () {
        var obj = $(this).data("value");
        var parent = $(this).parents(".cs-w-item");
        var length = parent.siblings(".cs-w-item").length;
        if (obj.divId == 'new_address') {
            if ((length + 1) >= 11) {
                pbDialog(json_languages.add_address_10, "", 0);
                return false;
            }
        }

        if (obj.divId == 'new_address' || obj.divId == 'edit_address') {
            //添加收货地址信息
            Ajax.call(obj.url, 'address_id=' + obj.id, function (data) {
                pb({
                    id: obj.divId,
                    title: obj.title,
                    width: obj.width,
                    content: data.content, 	//调取内容
                    drag: false,
                    foot: true,
                    ok_title: json_languages.con_Preservation,
                    cl_title: json_languages.cancel,
                    onOk: function () {
                        //方法在consignee_new.lbi里
                        if (wholesale_addUpdate_Consignee("form[name='theForm']") == false) {
                            wholesale_addUpdate_Consignee("form[name='theForm']");
                            return false;
                        } else {
                            return true;
                        }
                    }
                });

                if (obj.divId == 'new_address') {
                    //新增地区初始化
                    $.levelLink(1);
                } else {
                    //编辑地区初始化
                    $.levelLink(0);
                }

            }, 'POST', 'JSON');

        } else if (obj.divId == 'del_address') {
            //删除收货地址信息
            var content = $('#del_address').html();

            pbDialog(json_languages.confirm_drop_address, "", 0, '', '', '', true, function () {
                Ajax.call('wholesale_flow.php?step=delete_Consignee', 'address_id=' + obj.id + "&temtype=1&type=1", function (data) {
                    if (data.error == 2) {
                        $('#consignee-addr').html(data.content);
                    } else {
                        $('#consignee-addr').html(data.content);
                    }

                    $('#goods_inventory').html(data.goods_list);//送货清单
                    $('#ECS_ORDERTOTAL').html(data.order_total);//费用汇总

                    $('#not_freightfree').val(0);
                }, 'POST', 'JSON');
            });
        }
    });

    /* 结算页面 用户收货地址 end */
});

/****************************结算页面收货地址修改新增 保存方法 start*****************************/
function wholesale_addUpdate_Consignee(frm) {
    var obj = $(frm);
    var info_return = 0;
    var csg = new Object;
    var fale = false;
    var shipping_id = get_cart_shipping_id();

    if ($(":input[name='uc_id']").length > 0) {
        var uc_id = $(":input[name='uc_id']").val();
    } else {
        var uc_id = 0;
    }

    csg.goods_flow_type = obj.find("input[name='goods_flow_type']").val(); //商品类型 虚拟100|实体101

    csg.consignee = obj.find("[name='consignee']").val();
    if (csg.goods_flow_type == 101) {
        csg.country = obj.find("[name='country']").val();
        csg.province = obj.find("[name='province']").val();
        csg.city = obj.find("[name='city']").val();
        csg.district = obj.find("[name='district']").val();
        csg.street = obj.find("[name='street']").val();
        csg.address = obj.find("[name='address']").val();
        csg.zipcode = obj.find("[name='zipcode']").val();
        csg.sign_building = obj.find("[name='sign_building']").val();
        csg.best_time = obj.find("[name='best_time']").val();
    }

    csg.mobile = obj.find("[name='mobile']").val();
    csg.tel = obj.find("[name='tel']").val();
    csg.email = obj.find("[name='email']").val();
    csg.address_id = obj.find("[name='address_id']").val();

    if (csg.consignee == '') {
        pbDialog(json_languages.input_Consignee_name, '', 0);
    } else if (!Utils.isTel(csg.mobile) || csg.mobile.length != 11) {
        pbDialog(json_languages.msg_phone_not, '', 0);
        return false;
    } else if (csg.country == 0 && csg.goods_flow_type == 101) {
        pbDialog(json_languages.select_consigne, '', 0);
        return false;
    } else if (csg.province == 0 && csg.goods_flow_type == 101) {
        pbDialog(json_languages.Province, '', 0);
        return false;
    } else if (csg.city == 0 && csg.goods_flow_type == 101) {
        pbDialog(json_languages.City, '', 0);
        return false;
    } else if (!$('#selDistricts_').is(":hidden") && csg.district == 0 && csg.goods_flow_type == 101) {
        pbDialog(json_languages.District, '', 0);
        return false;
    } else if (!$('#selStreets_').is(":hidden") && csg.street == 0 && csg.goods_flow_type == 101) {
        pbDialog(json_languages.Street, '', 0);
        return false;
    } else if (csg.address == '' && csg.goods_flow_type == 101) {
        pbDialog(json_languages.Detailed_address_null, '', 0);
        return false;
    }/*else if(csg.email != '' && !Utils.isEmail(csg.email)){
		pbDialog("邮箱不能为空",'',0);
		console.log(10);
	}*/ else {

        //修改新增地址 延迟加载效果
        $("#consignee-addr").html("<div class='load'>" + load_icon + "</div>");

        Ajax.call('wholesale_flow.php', 'step=insert_Consignee&csg=' + $.toJSON(csg) + '&shipping_id=' + $.toJSON(shipping_id) + '&uc_id=' + uc_id, wholesale_addUpdate_ConsigneeResponse, 'POST', 'JSON');

        fale = true;
    }

    return fale;
}

//回调
function wholesale_addUpdate_ConsigneeResponse(result) {
    if (result.error > 0) {
        if (result.error == 2) {
            pbDialog(result.message, "", 0);
            location.href = "user.php";
        }

        if (result.error == 4) {
            $('#consignee-addr').html(result.content);
        }
    } else {
        $('#consignee-addr').html(result.content);
        $('#goods_inventory').html(result.goods_list);//送货清单
        $('#ECS_ORDERTOTAL').html(result.order_total);//费用汇总
    }

    if (result.error == 4) {
        var ok_title, cl_title;
        var width = 455;
        var height = 58;
        var divId = "address_div_id";

        ok_title = json_languages.determine;
        cl_title = json_languages.cancel;

        var content = '<div id="' + divId + '">' +
            '<div class="tip-box icon-box">' +
            '<span class="warn-icon m-icon"></span>' +
            '<div class="item-fore">' +
            '<h3 class="rem ftx-04">' + result.message + '</h3>' +
            '</div>' +
            '</div>' +
            '</div>';

        pb({
            id: divId,
            title: json_languages.Prompt_info,
            width: width,
            height: height,
            ok_title: ok_title, 	//按钮名称
            cl_title: cl_title, 	//按钮名称
            content: content, 	//调取内容
            drag: false,
            foot: true,
            onOk: function () {
                $('#' + divId).remove();
            },
            onCancel: function () {
                $('#' + divId).remove();
            }
        });

        $('.pb-ok').addClass('color_df3134');
    }

    $('#shipping_list').html(result.shipping_content);
    $('#not_freightfree').val(result.not_freightfree);
}

/****************************结算页面收货地址修改新增 保存方法 start*****************************/
//详情页加入购物车
function addToCartShowDiv(goods_id) {
    $.post('/home/cart/addCart', {goods_id: goods_id, num: $('#goods_number').val(), attr_keys: attr_key}, addToCartShowDivResponse);
}

//详情页立即购买
function addToCart(goods_id) {
    $.post('/home/cart/addCart', {goods_id: goods_id, num: $('#goods_number').val(), attr_keys: attr_key}, addToCartResponse);
}

function addToCartResponse(result) {
    if (result.status > 0) {
        location.href = '/home/cart/index.html';
    } else if (result.status < -2) {
        //TODO 缺货登记
    } else {
        layer.msg(result.msg);
    }
}

function addToCartShowDivResponse(result) {
    if (result.status > 0) {
        var cartInfo = document.getElementById('ECS_CARTINFO');
        $(cartInfo).data('carteveval', 0);
        $(cartInfo).find('em.cart_num').text(result.cart_num);
        $('.mui-mbar-tabs #shopCart .cart_num').text(result.cart_num);
        var temp = $("#addtocartdialog");
        temp.find('.center_pop_txt .desc strong span').html(result.cart_num);
        temp.find('.center_pop_txt .desc em.saleP span').html(result.total_fee);
        temp.show();
        var $this = $("#addtocartdialog .loading");
        var top = ($(window).height() - $this.outerHeight()) / 2;
        var left = ($(window).width() - $this.outerWidth()) / 2;
        $this.css({"left": left, "top": top});
    } else if (result.status < -2) {
        //TODO 缺货登记
        layer.msg(result.msg);
    } else {
        layer.msg(result.msg);
    }
}

//团购倒计时扩展
(function ($) {
    $.fn.yomi = function () {
        var data = "";
        var _DOM = null;
        var TIMER;
        createdom = function (dom) {
            _DOM = dom;
            data = $(dom).attr("data-time");
            if (data) {
                data = data.replace(/-/g, "/");
                data = Math.round((new Date(data)).getTime() / 1000);
                reflash();
            }
        };
        reflash = function () {
            var range = data - Math.round((new Date()).getTime() / 1000),
                secday = 86400, sechour = 3600,
                days = parseInt(range / secday),
                hours = parseInt((range % secday) / sechour),
                min = parseInt(((range % secday) % sechour) / 60),
                sec = ((range % secday) % sechour) % 60;

            $(_DOM).find(".days").html(nol(days));
            $(_DOM).find(".hours").html(nol(hours));
            $(_DOM).find(".minutes").html(nol(min));
            $(_DOM).find(".seconds").html(nol(sec));
        };
        TIMER = setInterval(reflash, 1000);
        nol = function (h) {
            if (h < 0) {
                h = '0' + '0';
            } else if (h < 10) {
                h = '0' + h;
            }
            return h;
        };
        return this.each(function () {
            var $box = $(this);
            createdom($box);
        });
    };
})(jQuery);

/****右侧导航条领取优惠券，店铺首页领取优惠券***/
function receiveCoupon(cid) {
    if (user_id > 0) {
        $.post('/home/sale/collectCoupon', {'cid': cid}, function (data) {
            if (data.status) {
                $(".item-fore h3").html(data.msg);
                $(".success-icon").removeClass("i-icon").addClass("m-icon");
                var content = $("#pd_coupons").html();
                pb({
                    id: "coupons_dialog",
                    title: '领取优惠券',
                    width: 550,
                    height: 140,
                    ok_title: '立即使用', 	//按钮名称
                    cl_title: '关闭', 	//按钮名称
                    content: content, 	//调取内容
                    drag: false,
                    foot: true,
                    onOk: function () {
                        location.href = "/home/market/coupons.html?cid=" + cid;
                    },
                    onCancel: function () {
                        $(".cou-data").html(data.content);
                        $(".cou-seckill").html(data.content_kill);
                    },
                });

                $(".pb-ok").addClass("color_df3134");
            } else {
                $(".success-icon").removeClass("m-icon").addClass("i-icon");
                $(".item-fore h3").addClass("red");
                $(".item-fore h3").html(data.msg);
                var content = $("#pd_coupons").html();
                pb({
                    id: "coupons_dialog",
                    title: json_languages.receive_coupons,
                    width: 550,
                    height: 140,
                    ok_title: json_languages.close, 	//按钮名称
                    content: content, 	//调取内容
                    cl_cBtn: false,
                    onOk: function (han) {

                    }
                });
            }
        }, 'json');
    } else {
        $.notLogin();
        return false;
    }
}
