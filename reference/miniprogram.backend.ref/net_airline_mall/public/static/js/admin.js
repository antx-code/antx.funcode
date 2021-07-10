$(function () {
    /* jquery.ui tooltip.js title美化 */
    $("[data-toggle='tooltip']").tooltip({
        position: {
            my: "left top+5",
            at: "left bottom"
        }
    });

    /* jquery.ui tooltip.js 图片放大 */
    jQuery.tooltipimg = function () {
        $("[shoptype='tooltip']").tooltip({
            content: function () {
                var element = $(this);
                var url = this.dataset.tooltipimg;

                console.log(url);
                if (element.is('[data-tooltipImg]')) {
                    element.addClass("ui-tooltipImg");
                    return "<img style='width: 300px;' src='" + url + "' />";
                }
            },
        });
    };
    $.tooltipimg();

    /*自动加载页面*/
    $(document).on("click", "a", function () {
        var url = $(this).attr("href");
        var cls = $(this).attr("class");
        var target = $(this).attr("target");
        if (url != null && url != "#" && !url.match("javascript") && target != "_blank" && cls != "nyroModal") {
            $.cookie('shopUrl', url, {expires: 1, path: '/'});
        }
    });

    function autoLoadPage() {
        if ($.cookie('shopUrl')) {
            $("iframe[name='workspace']").attr('src', $.cookie('shopUrl'));
        }
    }

    autoLoadPage();

    //iframe内页a标签链接跳转调用iframe外的方法
    $("*[shoptype='iframeHref']").on("click", function () {
        parent.intheHref($(this));
    });

    //操作提示展开收起
    $("#explanation").on("click", function () {
        var explanation = $(this);
        var width = $(".content").width();
        if ($(this).hasClass("shopUp")) {
            $(this).removeClass("shopUp");
            $(this).attr("title", "收起提示");
            explanation.find(".ex_tit").css("margin-bottom", 10);
            explanation.animate({
                width: width - 28
            }, 300, function () {
                $(".explanation").find("ul").show();
            });
        } else {
            $(this).addClass("shopUp");
            $(this).attr("title", "提示相关设置操作时应注意的要点");
            explanation.find(".ex_tit").css("margin-bottom", 0);
            explanation.animate({
                width: "100"
            }, 300);
            explanation.find("ul").hide();
        }
    });

    //设置选择
    $(".setup").hover(function () {
        $(this).find("s").show();
        $(this).find(".setup_select").show();
        $(".ps-container").perfectScrollbar();
    }, function () {
        $(this).find("s").hide();
        $(this).find(".setup_select").hide();
    });

    //关闭打开切换
    $(document).on("click", ".switch_2", function () {
        if ($(this).hasClass("active")) {
            $(this).removeClass("active");
            $(this).next("input[type='hidden']").val(0);
            $(this).attr("title", "否");
        } else {
            $(this).addClass("active");
            $(this).next("input[type='hidden']").val(1);
            $(this).attr("title", "是");
        }
    });

    /*    //内容页切换
        $(document).on("click", ".tabs_info li", function () {
            var index = $(this).index();
            $(this).addClass("curr").siblings().removeClass("curr");
            $(".switch_info").eq(index).show().siblings(".switch_info").hide();
            if ($(".switch_info").siblings(".info_btn").length > 0) {
                document.getElementById("info_btn_bf100").className = "info_btn info_btn_bf100";
                $(".switch_info").siblings(".info_btn").addClass("button-info-item" + index);
            }
        });*/

    //订单统计切换
    $(".stat_order_tabs li").on("click", function () {
        var index = $(this).index();
        $(this).addClass("current").siblings().removeClass("current");
        $(".stat_order_table_info").eq(index).show().siblings(".stat_order_table_info").hide();
    });

    //添加商品 分类选择
    $(document).on("click", ".sort_list li", function () {
        $(this).addClass("current").siblings().removeClass("current");
    });

    // 选择品牌
    $('input[name="brand_name"]').click(function () {
        $(".brand-select-container").hide();
        $(this).parents(".selection").next(".brand-select-container").show();
        $(".brand-list").perfectScrollbar("destroy");
        $(".brand-list").perfectScrollbar();
    });

    // 根据关键字查询
    $('.b_search').find('a').click(function () {
        var goods_id = $("input[name=goods_id]").val();
        var keyword = $(this).prev().val();
        $(".brand-not strong").html(keyword);
    });
    // 选择品牌
    $('.brand-list').on('click', 'li', function () {
        $(this).parents('.brand-select-container').prev().find('input[data-filter=brand_id]').val($(this).data('id'));
        $(this).parents('.brand-select-container').prev().find('input[data-filter=brand_name]').val($(this).data('name'));
        $('.brand-select-container').hide();
    });

    //是否商品活动切换
    $(".goods_activity .checkbox_item input[type='radio'],.goods_special .checkbox_item input[type='radio'],.switch_info .checkbox_item input[type='radio']").on("click", function () {
        var special_div = $(this).parents(".checkbox_items").next(".special_div");
        var hidden_div = $(this).parents(".checkbox_items").find(".hidden_div");
        if ($(this).is(":checked")) {
            var val = $(this).val();
            if (val == 1) {
                special_div.show();
                hidden_div.show();
            } else {
                special_div.hide();
                hidden_div.hide();
            }
        }
    });

    /*带搜索的下拉列表*/
    $(document).on("click", '.selection input[name="user_name"]', function () {
        $('.selection_select .select-container').show();
    });

    $('.select-list,.select-list2').on('click', 'li', function () {
        $(this).parents('.select-container').prev().children('input[name=user_id]').val($(this).data('id'));
        $(this).parents('.select-container').prev().children('input[name=user_name]').val($(this).data('name'));
        $('.selection_select .select-container').hide();
    });

    $(".select-list,.select-list2").hover(function () {
        $(".select-list,.select-list2").perfectScrollbar();
    });
    /*带搜索的下拉列表end*/

    /* 搜索卖场的下拉列表 */
    $(document).on("click", '.selection input[name="rs_name"]', function () {
        $('.rs_select .select-container').show();
    });

    $('.select-list2').on('click', 'li', function () {
        $(this).parents('.select-container').prev().children('input[name=rs_id]').val($(this).data('id'));
        $(this).parents('.select-container').prev().children('input[name=rs_name]').val($(this).data('name'));
        $('.rs_select .select-container').hide();
    });

    $(".select-list,.select-list2").hover(function () {
        $(".select-list,.select-list2").perfectScrollbar();
    });
    /*带搜索的下拉列表end*/


    /* 品牌搜索的下拉列表 by wu start */
    /*$('.selection input[name="brand_name"]').click(function(){
		$(this).parents(".selection").next(".brand-select-container").show();
    });	*/
    /* 品牌搜索的下拉列表 by wu end */

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
        var artic_id = $("input[name=id]").val();
        var length = $(this).parent().prev().find("li.current").length; //选中项数量
        var step = $(this).parents(".step[shoptype=filter]:first");
        step.find(".move_list").perfectScrollbar();

        var dealType = 0; //处理类型，0为ajax处理（默认），1为js处理
        var label = 0; //秒杀活动用标签， 0为默认 1为秒杀活动
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

        //添加、删除秒杀商品 liu
        if (operation == 'add_seckill_goods' || operation == 'drop_seckill_goods') {
            label = 1;
            var sec_num = $(this).parents(".step[shoptype=filter]").find("input[name=sec_num]").val();
            var sec_price = $(this).parents(".step[shoptype=filter]").find("input[name=sec_price]").val();
            var sec_limit = $(this).parents(".step[shoptype=filter]").find("input[name=sec_limit]").val();
            var sec_id = $("input[name=sec_id]").val();
            var tb_id = $("input[name=tb_id]").val();
            extension += "&sec_num=" + sec_num + "&sec_price=" + sec_price + "&sec_limit=" + sec_limit;
        }

        //添加关联文章
        if (operation == "add_goods_article") {
        }

        //删除文章的关联商品
        if (operation == "drop_link_artic_goods") {
            label = 2;

        }

        //添加文章的关联商品
        if (operation == "add_link_artic_goods") {
            label = 3;

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
            if (label == 0) {
                $.jqueryAjax("get_ajax_content.php", "act=" + operation + "&goods_id=" + goods_id + "&value=" + value + extension, function (data) {
                    obj.parents(".step[shoptype=filter]").find(".move_right .move_list").html(data.content);

                    obj.parents(".step[shoptype=filter]").find(".move_right .move_list").perfectScrollbar('destroy');
                    obj.parents(".step[shoptype=filter]").find(".move_right .move_list").perfectScrollbar();
                });
            } else if (label == 1) {
                $.jqueryAjax("get_ajax_content.php", "act=" + operation + "&sec_id=" + sec_id + "&tb_id=" + tb_id + "&value=" + value + extension, function (data) {
                    obj.parents(".step[shoptype=filter]").find(".move_right .move_list").html(data.content);

                    obj.parents(".step[shoptype=filter]").find(".move_right .move_list").perfectScrollbar('destroy');
                    obj.parents(".step[shoptype=filter]").find(".move_right .move_list").perfectScrollbar();
                });
            } else if (label == 2) {
                $.jqueryAjax("get_ajax_content.php", "act=" + operation + "&article_id=" + artic_id + "&value=" + value + extension, function (data) {
                    obj.parents(".step[shoptype=filter]").find(".move_right .move_list").html(data.content);

                    obj.parents(".step[shoptype=filter]").find(".move_right .move_list").perfectScrollbar('destroy');
                    obj.parents(".step[shoptype=filter]").find(".move_right .move_list").perfectScrollbar();
                });
            } else if (label == 3) {
                $.jqueryAjax("get_ajax_content.php", "act=" + operation + "&article_id=" + artic_id + "&value=" + value + extension, function (data) {
                    obj.parents(".step[shoptype=filter]").find(".move_right .move_list").html(data.content);

                    obj.parents(".step[shoptype=filter]").find(".move_right .move_list").perfectScrollbar('destroy');
                    obj.parents(".step[shoptype=filter]").find(".move_right .move_list").perfectScrollbar();
                });
            }
        }
    });

    $(".move_middle .move_point").on("click", function () {
        var obj = $(this);
        var goods_id = $("input[name=goods_id]").val();
        var dealType = 0;
        var label = 0; //秒杀活动用标签， 0为默认 1为秒杀活动
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

        //添加、删除秒杀商品 liu
        if (operation == 'add_seckill_goods' || operation == 'drop_seckill_goods') {
            label = 1;
            var sec_num = $(this).parents(".step[shoptype=filter]").find("input[name=sec_num]").val();
            var sec_price = $(this).parents(".step[shoptype=filter]").find("input[name=sec_price]").val();
            var sec_limit = $(this).parents(".step[shoptype=filter]").find("input[name=sec_limit]").val();
            var sec_id = $("input[name=sec_id]").val();
            var tb_id = $("input[name=tb_id]").val();
            extension += "&sec_num=" + sec_num + "&sec_price=" + sec_price + "&sec_limit=" + sec_limit;
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
            if (label == 0) {
                $.jqueryAjax("get_ajax_content.php", "act=" + operation + "&goods_id=" + goods_id + "&value=" + value + extension, function (data) {
                    obj.parents(".step[shoptype=filter]").find(".move_right .move_list").html(data.content);

                    obj.parents(".step[shoptype=filter]").find(".move_right .move_list").perfectScrollbar('destroy');
                    obj.parents(".step[shoptype=filter]").find(".move_right .move_list").perfectScrollbar();
                });
            } else if (label == 1) {
                $.jqueryAjax("get_ajax_content.php", "act=" + operation + "&sec_id=" + sec_id + "&tb_id=" + tb_id + "&value=" + value + extension, function (data) {
                    obj.parents(".step[shoptype=filter]").find(".move_right .move_list").html(data.content);

                    obj.parents(".step[shoptype=filter]").find(".move_right .move_list").perfectScrollbar('destroy');
                    obj.parents(".step[shoptype=filter]").find(".move_right .move_list").perfectScrollbar();
                });
            }
        }
    });

    /* 关联设置效果 by wu end */

    //上传图片类型
    /*$('input[class="type-file-file"]').change(function(){
		var state = $(this).data('state');
		var filepath=$(this).val();
		var extStart=filepath.lastIndexOf(".");
		var ext=filepath.substring(extStart,filepath.length).toUpperCase();

		if(state == 'txtfile'){
			if(ext!=".TXT"){
				alert("上传文件限于txt格式");
					$(this).attr('value','');
				return false;
			}
		}else if(state == 'imgfile'){
			if(ext!=".PNG"&&ext!=".GIF"&&ext!=".JPG"&&ext!=".JPEG"){
				alert("上传图片限于png,gif,jpeg,jpg格式");
					$(this).attr('value','');
				return false;
			}
		}else if(state == 'csvfile'){
			if(ext!=".CSV"){
				alert("上传文件限于csv格式");
					$(this).attr('value','');
				return false;
			}
		}else if(state == 'sqlfile'){
                    if(ext!=".SQL"){
				alert("上传文件限于sql格式");
					$(this).attr('value','');
				return false;
			}
    	}
	});*/

    //file移动上去的js
    /*$(".type-file-box").hover(function(){
    	$(this).addClass("hover");
    },function(){
    	$(this).removeClass("hover");
    });*/


    //全选切换效果
    $(document).on("click", "input[name='all_list']", function () {
        if ($(this).prop("checked") == true) {
            $(".list-div").find("input[type='checkbox']").prop("checked", true);
            $(".list-div").find("input[type='checkbox']").parents("tbody").addClass("tr_bg_org");
        } else {
            $(".list-div").find("input[type='checkbox']").prop("checked", false);
            $(".list-div").find("input[type='checkbox']").parents("tbody").removeClass("tr_bg_org");
        }
        btnSubmit();
    });

    //列表单选
    $(document).on("click", ".sign .checkbox", function () {
        if ($(this).is(":checked")) {
            $(this).parents("tbody").addClass("tr_bg_org");
        } else {
            $(this).parents("tbody").removeClass("tr_bg_org");
        }
        btnSubmit();
    });

    //图库相册图片单选
    $(".pic-items .ui-checkbox").click(function () {
        btnSubmit();
    });

    function btnSubmit() {
        var length = $(".list-div").find("input[name='checkboxes[]']:checked").length;

        //$(".list-div").find("input[name='checkboxes[]']:checked").parents("tr").addClass("tr_bg_org");

        if (length > 0) {
            if ($("#listDiv *[shoptype='btnSubmit']").length > 0) {
                $("#listDiv *[shoptype='btnSubmit']").removeClass("btn_disabled");
                $("#listDiv *[shoptype='btnSubmit']").attr("disabled", false);
            }
        } else {
            if ($("#listDiv *[shoptype='btnSubmit']").length > 0) {
                $("#listDiv *[shoptype='btnSubmit']").addClass("btn_disabled");
                $("#listDiv *[shoptype='btnSubmit']").attr("disabled", true);
            }
        }
    }

    //默认执行
    btnSubmit();

    $(document).on("mouseenter", ".list-div tbody td", function () {
        $(this).parents("tr").addClass("tr_bg_blue");
    });

    $(document).on("mouseleave", ".list-div tbody td", function () {
        $(this).parents("tr").removeClass("tr_bg_blue");
    });

    //属性选中效果 by wu start
    $(document).on("click", "#tbody-goodsAttr .checkbox_items label", function () {
        if ($(this).siblings("input[type=checkbox]:first").prop("checked") == true) {
            $(this).siblings("input[type=checkbox]:first").prop("checked", false);
        } else {
            $(this).siblings("input[type=checkbox]:first").prop("checked", true);
        }
        var goods_id = $("input[name='goods_id']").val();
    });
    //属性选中效果 by wu end

    //列表页默认搜索 by wu start
    /*$(document).on("click",".search .btn",function(){
		$(this).parents(".search:first").find("input").each(function(){
			var name = $(this).attr('name');
			if($(this).is(":checkbox")){
				if($(this).is(":checked")){
					listTable.filter[name] = Utils.trim($(this).val());
				}else{
					listTable.filter[name] = '';
				}
			}else{
				listTable.filter[name] = Utils.trim($(this).val());
			}
		});
		listTable.filter['page'] = 1;
        listTable.loadList();
	});*/
    //列表页默认搜索 by wu end

    //列表页刷新 by wu end

    //筛选搜索商品/文章/其他列表 by wu start
    $("a[shoptype='search']").on("click", function () {
        var obj = $(this);
        var step = $(this).parents(".step[shoptype=filter]:first");
        var search_type = step.data("filter");
        var search_data = "";
        var ru_data = "";
        var search_div = $(this).parents(".goods_search_div:first"); //搜索div
        if (search_type == "goods") {
            //商品筛选
            //var search_div = $(this).parents(".goods_search_div");
            var cat_id = search_div.find("input[data-filter=cat_id]").val();
            var brand_id = search_div.find("input[data-filter=brand_id]").val();
            var keyword = search_div.find("input[data-filter=keyword]").val();
            var ru_id = search_div.find("input[data-filter=ru_id]").val();
            var limit_start = search_div.find("input[data-filter=limit_start]").val();
            var limit_number = search_div.find("input[data-filter=limit_number]").val();
            if (ru_id == null || typeof ru_id == "undefined") {
                ru_id = 0;
            }
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
            if (ru_id) {
                ru_data += "&ru_id=" + ru_id;
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
            $.jqueryAjax('get_ajax_content.php', 'act=filter_list&search_type=' + search_type + search_data + ru_data, function (data) {
                step.find(".move_left .move_list:first, .move_all .move_list:first").html(data.content);
                step.find(".move_left .move_list , .move_all .move_list").perfectScrollbar('destroy');
                step.find(".move_left .move_list, .move_all .move_list").perfectScrollbar();
            });
        }

        setTimeout(function () {
            ajax()
        }, 300);
    });
    //筛选搜索商品/文章/其他列表 by wu end

    $(".text_time").click(function () {
        if (!$("#xv_Dates_box").is(":hidden")) {
            $(".iframe_body").addClass("relative");
        }
    });

    $(document).click(function (e) {
        /*
         **点击空白处隐藏展开框
         */

        //会员搜索
        if (e.target.id != 'user_name' && !$(e.target).parents("div").is(".select-container")) {
            $('.selection_select .select-container').hide();
        }
        //卖场搜索
        if (e.target.id != 'rs_name' && !$(e.target).parents("div").is(".select-container")) {
            $('.rs_select .select-container').hide();
        }
        //品牌
        if (e.target.id != 'brand_name' && !$(e.target).parents("div").is(".brand-select-container")) {
            $('.brand-select-container').hide();
            $('.brandSelect .brand-select-container').hide();
        }
        //分类
        if (e.target.id != 'category_name' && !$(e.target).parents("div").is(".select-container")) {
            $('.categorySelect .select-container').hide();
        }
        //仿select
        if (e.target.className != 'cite' && !$(e.target).parents("div").is(".imitate_select")) {
            $('.imitate_select ul').hide();
        }
        //日期选择插件
        if (!$(e.target).parent().hasClass("text_time")) {
            $(".iframe_body").removeClass("relative");
        }
        //查看案例
        if (e.target.className != 'view-case-tit' && !$(e.target).parents("div").is(".view-case")) {
            $('.view-case-info').hide();
        }
    });

    //select下拉默认值赋值
    $('.imitate_select').each(function () {
        var sel_this = $(this);
        var val = sel_this.children('input[type=hidden]').val();
        sel_this.find('a').each(function () {
            if ($(this).attr('data-value') == val) {
                sel_this.children('.cite').html($(this).html());
            }
        })
    });

    //分类选择
    $.category();

    $(document).on("click", ".xds_item .xds_up", function () {
        var parent = $(this).parents(".xds_item");
        var _div = parent.clone();
        _div.find("input[type='input']").val("");
        _div.find(".xds_up").removeClass("xds_up").addClass("xds_down");
        parent.parents(".xds_items").append(_div);
    });
    $(document).on("click", ".xds_item .xds_down", function () {
        var parent = $(this).parents(".xds_item");
        parent.remove();
    });

    //查看案例
    $(".view-case-tit").on("click", function () {
        var $this = $(this);
        $this.siblings(".view-case-info").slideToggle(300);
    });

    /*查看会员等级价格 批量修改*/
    $("[shoptype='seeRank']").hover(function () {
        $(this).find(".rankPrice_tip").show();
    }, function () {
        $(this).find(".rankPrice_tip").hide();
    });

    //返回上一页
    $(document).on("click", "a[shoptype=goback]", function () {
        history.go(-1);
    });

    //属性选中效果 by wu start
    $(document).off("click", "#tbody-wholesale-goodsAttr .checkbox_items label").on("click", "#tbody-wholesale-goodsAttr .checkbox_items label", function () {
        if ($(this).siblings("input[type=checkbox]:first").prop("checked") == true) {
            $(this).siblings("input[type=checkbox]:first").prop("checked", false);
        } else {
            $(this).siblings("input[type=checkbox]:first").prop("checked", true);
        }
        var goods_id = $("input[name='goodsId']").val();

        wholesale_set_attribute_table(goods_id);
    });

    //listTable span eidt 点击修改
    $(document).on("click", "*[shoptype='editSpanInput'] .icon-edit", function () {
        $(this).siblings("span").click();
    });

    //通用 详情页 审核状态切换
    $("*[shoptype='general_audit_status'] input[type='radio']").on("click", function () {
        var val = $(this).val();

        if (val == 2) {
            $("#review_content").show();
        } else {
            $("#review_content").hide();
        }
        if (val == 3) {
            $("*[shoptype='userInfo']").show();
        } else {
            $("*[shoptype='userInfo']").hide();
        }
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
        handle_tr.append("<td><a href='javascript:;' class='btn btn25 blue_btn' data-id='0' shoptype='remove_volume'>删除</a></td>");
    } else {
        pbDialog("最多设置不能超过" + number + "个", "", 0);
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

/* 列表搜索通用js */
function searchGoodsname(frm) {
    var frm = $(frm);
    frm.find("input").each(function () {
        var t = $(this),
            name = t.attr("name");

        if (t.is(":checkbox")) {
            if (t.is(":checked")) {
                listTable.filter[name] = Utils.trim(t.val());
            } else {
                listTable.filter[name] = '';
            }
        } else {
            listTable.filter[name] = Utils.trim(t.val());
        }
    });

    listTable.filter['page'] = 1;
    listTable.loadList();
}

//首页系统信息收起展开
jQuery.upDown = function (obj, title, section, w) {
    var obj = $(obj);
    var title = obj.parent(title);
    var section = obj.parents(section);
    var content = title.next();
    var width = $(".warpper").width();
    $(window).resize(function () {
        width = $(".warpper").width();
        if (!section.hasClass("w190")) {
            section.css("width", width - w);
        }
    });

    obj.click(function () {
        if (obj.hasClass("stop_jia")) {
            obj.removeClass("stop_jia");
            obj.attr("title", "收起详情")
            title.css("border-bottom", "1px solid #e4eaec");
            section.removeClass("w190");
            section.animate({
                width: width - w
            }, 300, function () {
                content.show();
            });
        } else {
            obj.addClass("stop_jia");
            obj.attr("title", "展开详情")
            title.css("border", "0");
            section.animate({
                width: "190"
            }, 300);
            section.addClass("w190");
            content.hide();
        }
    });
};
/*分类搜索的下拉列表*/
jQuery.category = function () {
    $(document).on("click", '.selection input[name="category_name"]', function () {
        $(this).parents(".selection").next('.select-container').show();
        $(".select-list").perfectScrollbar("destroy");
        $(".select-list").perfectScrollbar();
    });
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


/**
 * 重写jQuery的ajax，追加loading效果
 */
(function ($) {
    var _ajax = $.ajax;
    $.ajax = function (opt) {
        //扩展增强处理
        var _opt = $.extend(opt, {
            beforeSend: function (XHR) {
                //showMask();
            },
            complete: function (XHR, TS) {
                //hideMask();
                layer.closeAll();
            }
        });
        return _ajax(_opt);
    };
})(jQuery);
