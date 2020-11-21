/**
 * 地区联动js
 * @return
 */
jQuery.levelLink = function () {
    var opt = '.options',
        liv = '.options > .liv',
        txt = '.txt',
        input = 'input[type="hidden"]',
        dropdown = $('.smartdropdown');

    //select下拉默认值赋值
    if ($('.ui-dropdown').parents(".level_linkage").length > 0) {
        $(".level_linkage").each(function () {
            var $this = $(this);
            $this.find('.ui-dropdown').each(function (v, k) {
                var sel_this = $(this);
                var val = sel_this.find('input[type=hidden]').val();
                sel_this.find('.liv').each(function () {
                    if ($(this).data('value') == val) {
                        sel_this.find('.txt').html($(this).data("text"));
                    }
                });
            });
        });
    } else {
        $('.ui-dropdown').each(function () {
            var sel_this = $(this);
            var val = sel_this.children('input[type=hidden]').val();
            sel_this.find('.liv').each(function () {
                if ($(this).attr('data-value') == val) {
                    sel_this.children('.txt').html($(this).html());
                }
            })
        });
    }
    $(document).find(txt).on('click', dropdown, function () {
        var t = $(this);
        $(dropdown).removeClass("visible");
        if (t.parent(dropdown).hasClass("visible")) {
            t.parents(dropdown).removeClass("visible");
            t.nextAll(opt).hide();
        } else {
            t.parents(dropdown).addClass("visible");
            t.nextAll(opt).show();
            t.parents(dropdown).siblings().removeClass("visible");
            t.parents(dropdown).siblings().find(opt).hide();
        }
    });

    $(document).on('click', liv, function () {
        var t = $(this);
        var text = t.data("text");
        var value = t.data("value");
        var type = t.data("type");
        var old_val = t.parents(opt).prevAll(input).val();
        if (old_val != value) {
            t.parents(".ui-dropdown").nextAll(".ui-dropdown").find("input").val(0);
            var length = t.parents(".ui-dropdown").nextAll(".ui-dropdown").length;

            t.parents(".ui-dropdown").nextAll(".ui-dropdown").each(function (k, v) {
                var name = $(this).find(input).attr("name");
                if (name == "province") {
                    $(this).find(txt).html("省/直辖市");
                } else if (name == "city") {
                    $(this).find(txt).html("市");
                } else if (name == "district") {
                    $(this).find(txt).html("区/县");
                    //$(this).hide();
                } else if (name == "street") {
                    $(this).find(txt).html("街道");
                    //$(this).hide();
                }
            });
        }
        t.parents(opt).prevAll(input).val(value);
        t.parents(opt).prevAll(txt).html(text);
        t.parents(opt).hide();
        t.parents(dropdown).removeClass("visible");

        if (value == -1) {
            var tid = $("#tab_tid").val();
            var shipping_id = $("#tab_shipping_id").val();
            t.parents(dropdown).nextAll(".ui-dropdown").hide();
        }
    });

    $(document).click(function (e) {
        if (e.target.className != 'txt' && !$(e.target).parents("div").is(opt)) {
            $(opt).hide();
            $(dropdown).removeClass("visible");
        }
    });
    var i = 100;
    $(".smartdropdown").each(function (index, element) {
        $(this).css({"z-index": i--});
    });
};
