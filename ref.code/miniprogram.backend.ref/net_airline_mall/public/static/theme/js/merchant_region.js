var region = new Object();

region.dom = '';
region.loadRegions = function (parent, type, target) {
    $.post('/home/merchant/getRegionList', {parent_id: parent}, region.response);
};

/* *
 * 处理下拉列表改变的函数
 *
 * @obj     object  下拉列表
 * @type    integer 类型
 * @selName string  目标列表框的名称
 */
region.changed = function (obj, type, selName) {
    var parent = obj.options[obj.selectedIndex].value;
    region.dom = obj.nextElementSibling;
    region.loadRegions(parent, type, selName);
};

region.response = function (result, text_result) {
    var sel = region.dom;
    sel.length = 1;
    sel.selectedIndex = 0;
    sel.style.display = result.length == 0 ? "none" : '';

    if (result) {
        Object.keys(result).forEach(function (k, v) {
            var opt = document.createElement("OPTION");
            opt.value = k;
            opt.text = result[k].region_name;
            sel.options.add(opt);
        });
    }
};