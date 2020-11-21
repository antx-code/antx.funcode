/* $Id: listtable.js 14980 2008-10-22 05:01:19Z testyang $ */
if (typeof Ajax != 'object') {
    pbDialog('Ajax object doesn\'t exists.', '', 0);
}

if (typeof Utils != 'object') {
    pbDialog('Utils object doesn\'t exists.', '', 0);
}
var is_tipe;
var listTable = new Object;

listTable.query = "query";
listTable.filter = new Object;
listTable.url = location.href.lastIndexOf("?") == -1 ? location.href.substring((location.href.lastIndexOf("/")) + 1) : location.href.substring((location.href.lastIndexOf("/")) + 1, location.href.lastIndexOf("?"));
listTable.url += "?is_ajax=1";

/**
 * 创建一个可编辑区
 */
listTable.edit = function (obj, act, id) {
    var tag = obj.firstChild.tagName;

    if (typeof (tag) != "undefined" && tag.toLowerCase() == "input") {
        return;
    }

    /* 保存原始的内容 */
    var org = obj.innerHTML;
    var val = Browser.isIE ? obj.innerText : obj.textContent;

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
        var evt = Utils.fixEvent(e);
        var obj = Utils.srcElement(e);

        if (evt.keyCode == 13) {
            obj.blur();

            return false;
        }

        if (evt.keyCode == 27) {
            obj.parentNode.innerHTML = org;
        }
    };

    /* 编辑区失去焦点的处理函数 */
    txt.onblur = function (e) {
        if (Utils.trim(txt.value).length > 0) {
            res = Ajax.call(listTable.url, "act=" + act + "&val=" + encodeURIComponent(Utils.trim(txt.value)) + "&id=" + id, null, "POST", "JSON", false);

            if (res.message) {
                pbDialog(res.message, '', 0);
            }

            if (res.id && (res.act == 'goods_auto' || res.act == 'article_auto')) {
                document.getElementById('del' + res.id).innerHTML = "<a href=\"" + thisfile + "?goods_id=" + res.id + "&act=del\" onclick=\"return confirm('" + deleteck + "');\">" + deleteid + "</a>";
            }

            obj.innerHTML = (res.error == 0) ? res.content : org;

            if ($(obj).siblings(".edit_icon")) {
                $(obj).siblings(".edit_icon").css({"display": ""});
            }
        } else {
            obj.innerHTML = org;
        }
    }
};

/**
 * input编辑 by wu
 */
listTable.editInput = function (obj, act, id, val, str) {
    var type = '';
    var org = obj.innerHTML;
    if (val && str) {
        type += "&goods_model=" + val;
    }

    var value = obj.value

    //获取属性是否是临时表数据
    var changelog = $(obj).parents('tr').data('changelog');
    if (changelog == 1) {
        type += "&changelog=1";
    }

    if ($(":input[name='warehouse']").length > 0) {
        if ($("#attribute_model").attr("style") != 'display:none;') {
            var warehouse_id = $("#attribute_model :input[name='warehouse']:checked").val();
            type += "&warehouse_id=" + warehouse_id;

            var area_id = $("#attribute_region :input[name='region']:checked").val();
            type += "&area_id=" + area_id;
        }
    }

    if (Utils.trim(value).length > 0) {
        res = Ajax.call(listTable.url, "act=" + act + "&val=" + encodeURIComponent(Utils.trim(value)) + "&id=" + id + type, null, "POST", "JSON", false);
        if (res.message) {
            pbDialog(res.message, "", 0);
        }

    } else {
        obj.innerHTML = org;
    }
};

/**
 * 切换状态
 */
listTable.toggle = function (obj, act, id) {
    var val = (obj.src.match(/yes.gif/i)) ? 0 : 1;

    var res = Ajax.call(this.url, "act=" + act + "&val=" + val + "&id=" + id, null, "POST", "JSON", false);

    if (res.message) {
        pbDialog(res.message, "", 0);
    }

    if (res.error == 0) {
        obj.src = (res.content > 0) ? 'images/yes.gif' : 'images/no.gif';
    }
};

/* 按钮切换 by wu */
listTable.switchBt = function (obj, act, id) {
    var obj = $(obj);
    var val = (obj.attr('class').match(/active/i)) ? 0 : 1;
    var res = Ajax.call(this.url, "act=" + act + "&val=" + val + "&id=" + id, null, "POST", "JSON", false);

    /* 判断是否唯一 */
    var type = obj.data("type");

    if (res.message) {
        pbDialog(res.message, "", 0);
    }

    if (res.error == 0) {
        obj.src = (res.content > 0) ? 'images/yes.gif' : 'images/no.gif';
        if (!res.message) {
            if (obj.hasClass("active")) {
                obj.removeClass("active");
                obj.next("input[type='hidden']").val(0);
                obj.attr("title", "否");
            } else {
                obj.addClass("active");
                obj.next("input[type='hidden']").val(1);
                obj.attr("title", "是");
            }
        }
    }
    if (type == "only") {
        obj.parents("tr").siblings().find(".switch").removeClass("active").attr("title", "否");
        obj.parents("tr").siblings().find(".switch").next("input[type='hidden']").val(0);
    }
};

/**
 * 切换排序方式
 */
listTable.sort = function (sort_by, sort_order) {
    var args = "act=" + this.query + "&sort_by=" + sort_by + "&sort_order=";

    if (this.filter.sort_by == sort_by) {
        args += this.filter.sort_order == "DESC" ? "ASC" : "DESC";
    } else {
        args += "ASC";
    }

    for (var i in this.filter) {
        if (typeof (this.filter[i]) != "function" &&
            i != "sort_order" && i != "sort_by" && !Utils.isEmpty(this.filter[i])) {
            args += "&" + i + "=" + this.filter[i];
        }
    };

    this.filter['page_size'] = this.getPageSize();

};

/**
 * 翻页
 */
listTable.gotoPage = function (page) {
    if (page != null) this.filter['page'] = page;

    if (this.filter['page'] > this.pageCount) this.filter['page'] = 1;

    this.filter['page_size'] = this.getPageSize();

    this.loadList();
};

/**
 * 载入列表
 */
listTable.loadList = function () {
    if (this.query == "user_query_coupons") {
        var args = "act=" + this.query + "" + this.compileFilter() + "&cou_id=" + this.cou_id;
    } else {
        var args = "act=" + this.query + "" + this.compileFilter();
    }
    $(".refresh_tit").addClass("loading");

    Ajax.call(this.url, args, this.listCallback, "POST", "JSON");
};

/**
 * 删除列表中的一个记录
 */
listTable.remove = function (id, cfm, opt) {
    if (opt == null) {
        opt = "remove";
    }

    if (confirm(cfm)) {
        var args = "act=" + opt + "&id=" + id + this.compileFilter();
        Ajax.call(this.url, args, this.listCallback, "GET", "JSON");
    }
};

listTable.gotoPageFirst = function () {
    if (this.filter.page > 1) {
        listTable.gotoPage(1);
    }
};

listTable.gotoPagePrev = function () {
    if (this.filter.page > 1) {
        listTable.gotoPage(this.filter.page - 1);
    }
};

listTable.gotoPageNext = function () {
    if (this.filter.page < listTable.pageCount) {
        listTable.gotoPage(parseInt(this.filter.page) + 1);
    }
};

listTable.gotoPageLast = function () {
    if (this.filter.page < listTable.pageCount) {
        listTable.gotoPage(listTable.pageCount);
    }
};

listTable.changePageSize = function (e) {
    var evt = Utils.fixEvent(e);
    if (evt.keyCode == 13) {
        listTable.gotoPage();
        return false;
    }
    ;
};

listTable.listCallback = function (result, txt) {
    if (result.error > 0) {
        pbDialog(result.message, ' ', 0, '', '', 10);
    } else {
        try {

            var ById = "listDiv";
            if (result.class) {
                ById = result.class;
            }

            document.getElementById(ById).innerHTML = result.content;

            if (typeof result.filter == "object") {
                listTable.filter = result.filter;
            }
            if ($('.nyroModal').length > 0) {
                $('.nyroModal').nyroModal();
            }

            //提示插件方法调用
            if ($("*[data-toggle='tooltip']").length > 1) {
                $("*[data-toggle='tooltip']").tooltip();
            }

            if ($("*[shoptype='tooltip']").length > 1) {
                $.tooltipimg();
            }

            if (typeof (opts_time) == "function") {
                opts_time();
            }
            ;

            listTable.pageCount = result.page_count;
            if ($(".refresh_span").length > 0) {
                $(".refresh_span").html($(".refresh_span").html().replace(/\d+/g, result.filter['record_count'])); //刷新数量 by wu
                $(".refresh_tit").removeClass("loading");
            }

            if (typeof (commission_amount) == "function") {
                commission_amount();
            }
            ;
        } catch (e) {
            pbDialog(e.message, ' ', 0, '', '', 10);
        }
    }
};

listTable.selectAll = function (obj, chk) {
    if (chk == null) {
        chk = 'checkboxes';
    }

    var elems = obj.form.getElementsByTagName("INPUT");

    for (var i = 0; i < elems.length; i++) {
        if (elems[i].name == chk || elems[i].name == chk + "[]") {
            elems[i].checked = obj.checked;
        }
    }
};

listTable.compileFilter = function () {
    var args = '';
    for (var i in this.filter) {
        if (typeof (this.filter[i]) != "function" && typeof (this.filter[i]) != "undefined") {
            args += "&" + i + "=" + encodeURIComponent(this.filter[i]);
        }
    }

    return args;
};

listTable.getPageSize = function () {
    var ps = 15;

    pageSize = document.getElementById("pageSize");

    if (pageSize) {
        ps = Utils.isInt(pageSize.value) ? pageSize.value : 15;
        document.cookie = "ECSCP[page_size]=" + ps + ";";
    }
};

listTable.addRow = function (checkFunc) {
    cleanWhitespace(document.getElementById("listDiv"));
    var table = document.getElementById("listDiv").childNodes[0];
    var firstRow = table.rows[0];
    var newRow = table.insertRow(-1);
    newRow.align = "center";
    var items = new Object();
    for (var i = 0; i < firstRow.cells.length; i++) {
        var cel = firstRow.cells[i];
        var celName = cel.getAttribute("name");
        var newCel = newRow.insertCell(-1);
        if (!cel.getAttribute("ReadOnly") && cel.getAttribute("Type") == "TextBox") {
            items[celName] = document.createElement("input");
            items[celName].type = "text";
            items[celName].className = "text w50";
            items[celName].onkeypress = function (e) {
                var evt = Utils.fixEvent(e);
                var obj = Utils.srcElement(e);

                if (evt.keyCode == 13) {
                    listTable.saveFunc();
                }
            }
            newCel.appendChild(items[celName]);
        }
        if (cel.getAttribute("Type") == "Button") {
            var saveBtn = document.createElement("input");
            saveBtn.type = "image";
            saveBtn.src = "./images/icon_add.gif";
            saveBtn.value = save;
            newCel.appendChild(saveBtn);
            this.saveFunc = function () {
                if (checkFunc) {
                    if (!checkFunc(items)) {
                        return false;
                    }
                }
                var str = "act=add";
                for (var key in items) {
                    if (typeof (items[key]) != "function") {
                        str += "&" + key + "=" + items[key].value;
                    }
                }
                res = Ajax.call(listTable.url, str, null, "POST", "JSON", false);
                if (res.error) {
                    pbDialog(res.message, '', 0);
                    table.deleteRow(table.rows.length - 1);
                    items = null;
                } else {
                    document.getElementById("listDiv").innerHTML = res.content;
                    if (document.getElementById("listDiv").childNodes[0].rows.length < 6) {
                        listTable.addRow(checkFunc);
                    }
                    items = null;
                }
            }
            saveBtn.onclick = this.saveFunc;

            //var delBtn   = document.createElement("input");
            //delBtn.type  = "image";
            //delBtn.src = "./images/no.gif";
            //delBtn.value = cancel;
            //newCel.appendChild(delBtn);
        }
    }

};
