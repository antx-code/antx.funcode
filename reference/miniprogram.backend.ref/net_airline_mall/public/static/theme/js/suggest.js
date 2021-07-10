/**
 * 焦点查询关键词
 * @param {type} keyword
 * @returns {undefined}
 */
function lookup(keyword) {
    var suggestions = document.getElementById('suggestions');
    var category = json_languages.all;
    if (keyword.length == 0) {
        //隐藏建议框
        suggestions.style.display = 'none';
    } else {
        $.ajax({
            url: "/home/index/suggestions",
            type: "post",
            data: 'keyword=' + keyword,
            success: function (data, textStatus) {
                if (data) {
                    //防止操作过快 显示上一步的筛选页面
                    var real_keword = document.getElementById('keyword').value;
                    if (real_keword.length > 0) {
                        $("#suggestions").css('display', 'block');
                        $("#auto_suggestions_list").html(data);
                    }
                } else {
                    $("#suggestions").css('display', 'none');
                }

            },
            error: function (o) {

            }
        });
    }
}

/**
 * 按键查询关键字
 * @type Number|Number|Number|@exp;li@pro;length
 */
index = 0; //初始化索引
function keyup(e) {

    var keyword = document.getElementById('keyword').value;
    var suggestions = document.getElementById('suggestions');
    var category_list = document.getElementById('category');

    e = window.event || e;
    if (40 == e.keyCode) //按键盘向下键
    {
        var li = document.getElementById('suggestions_list_id').getElementsByTagName('li');
        if (index == 0 && document.getElementById('keyOne').value == 1) {
            index = -1;
            document.getElementById('keyOne').value = 0;
        }
        if (++index == li.length) {
            index = 0;
        }
        setView(li, index, li[index].title);
    } else if (38 == e.keyCode) //按键盘向上键
    {
        var li = document.getElementById('suggestions_list_id').getElementsByTagName('li');
        if (--index == -1) {
            index = li.length - 1;
        }
        setView(li, index, li[index].title);
    } else {
        if (keyword.length == 0) {
            //隐藏建议框
            suggestions.style.display = 'none';
        } else {
            $.ajax({
                url: "suggestions.php",
                type: "post",
                data: 'keyword=' + keyword,
                success: function (data, textStatus) {

                    if (data) {
                        //防止操作过快 显示上一步的筛选页面
                        var real_keword = document.getElementById('keyword').value;
                        if (real_keword.length > 0) {
                            $("#suggestions").css('display', 'block');
                            $("#auto_suggestions_list").html(data);
                        }
                    }
                },
                error: function (o) {
                }
            });
        }
    }
}

function change_suggestions_response(res) {
    var suggestions = document.getElementById('suggestions');
    var auto_suggestions_list = document.getElementById('auto_suggestions_list');
    if (res.option) {
        suggestions.style.display = 'block';
        auto_suggestions_list.innerHTML = res.option;
    } else {
        auto_suggestions_list.innerHTML = 'error';
    }
}

/**
 * 设置背景颜色
 * @param {string} elems
 * @param {string} index
 * @returns {undefined}
 */
function setView(elems, index, str) {
    var input_obj = document.getElementById('keyword');
    for (var j = 0; j < elems.length; j++) {
        elems[j].style.background = '';
    }
    elems[index].style.background = '#ffdfc6';
    input_obj.value = str;
}

/**
 * 隐藏提示框，并提交搜索
 * @param {type} this_value
 * @returns {undefined}
 */
function fill(goods_name,goods_id) {
    var keyword = document.getElementById('keyword');

    if(goods_id > 0){
        window.open('/home/goods/detail?id='+goods_id);
    }else{
        keyword.value = goods_name;
        window.open('/home/category/catgoodslist?keywords='+goods_name);
    }
}

function hide_suggest() {
    var suggestions = document.getElementById('suggestions');
    setTimeout("suggestions.style.display='none'", 100);
}

function _over(li) {
    var li_list = document.getElementById('suggestions_list_id').getElementsByTagName('li');
    for (var i = 0, len = li_list.length; i < len; i++) {
        li_list[i].style.background = '';
        li.style.cursor = '';
    }
    li.style.background = '#f7f7f7';
    li.style.cursor = 'pointer';
}

function _out(li) {
    li.style.background = '';
    li.style.cursor = '';
}

$(function () {
    $("#ECS_CARTINFO").mouseenter(function () {
        var eveval = $(this).data("carteveval");
        if (eveval == 0) {
            $.ajax({
                type: "POST",
                url: "/home/index/ajaxTopCart",
                success: function (data) {
                    $("#ECS_CARTINFO").html(data.content).data("carteveval", 1);
                    // $("#ECS_CARTINFO");
                },
                beforeSend: function () {
                    //加载效果
                    try {
                        if (typeof load_cart_info != undefined) {
                            $("*[shoptype='dorpdownLayer']").html(load_cart_info);
                        }
                    } catch (e) {
                    }
                }
            });
        }
    });
    $(document).click(function () {
        $(".suggestions_box").hide();
    });
    $(".suggestions_box").click(function (e) { //自己要阻止
        e.stopPropagation(); //阻止冒泡到body
    });
});