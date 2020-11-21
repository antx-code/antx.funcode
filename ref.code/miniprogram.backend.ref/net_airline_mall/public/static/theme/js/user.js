/* $Id : user.js 4865 2007-01-31 14:04:10Z paulgao $ */

/* *
 * 修改会员信息
 */
function userEdit() {
    var frm = document.forms['formEdit'];

    if (frm.elements['email']) {
        var email = frm.elements['email'].value;
    } else {
        var email = $("#profile_email").html();
    }

    var msg = '';
    var reg = null;
    var passwd_answer = frm.elements['passwd_answer'] ? Utils.trim(frm.elements['passwd_answer'].value) : '';
    var sel_question = frm.elements['sel_question'] ? Utils.trim(frm.elements['sel_question'].value) : '';

    if (email.length == 0) {
        msg += email_empty + '\n';
    }
    else {
        if (!(Utils.isEmail(email))) {
            msg += email_error + '\n';
        }
    }

    if (passwd_answer.length > 0 && sel_question == 0 || document.getElementById('passwd_quesetion') && passwd_answer.length == 0) {
        msg += no_select_question + '\n';
    }

    for (i = 7; i < frm.elements.length - 2; i++)	// 从第七项开始循环检查是否为必填项
    {
        needinput = document.getElementById(frm.elements[i].name + 'i') ? document.getElementById(frm.elements[i].name + 'i') : '';

        if (needinput != '' && frm.elements[i].value.length == 0) {
            msg += '- ' + needinput.innerHTML + "<i></i>" + msg_blank + '\n';
        }
    }

    if (msg.length > 0) {
        alert(msg);
        return false;
    }
    else {
        return true;
    }
}

/* 会员修改密码 */
function editPassword() {
    var frm = document.forms['formPassword'];
    var old_password = frm.elements['old_password'].value;
    var new_password = frm.elements['new_password'].value;
    var confirm_password = frm.elements['comfirm_password'].value;

    var msg = '';
    var reg = null;

    if (old_password.length == 0) {
        msg += old_password + '\n';
    }

    if (new_password.length == 0) {
        msg += new_password + '\n';
    }

    if (confirm_password.length == 0) {
        msg += confirm_password + '\n';
    }

    if (new_password.length > 0 && confirm_password.length > 0) {
        if (new_password != confirm_password) {
            msg += Dont_agree_password + '\n';
        }
    }

    if (msg.length > 0) {
        alert(msg);
        return false;
    }
    else {
        return true;
    }
}

/* *
 * 对会员的留言输入作处理
 */
function submitMsg() {
    var frm = document.forms['formMsg'];
    var msg_title = frm.elements['msg_title'].value;
    var msg_content = frm.elements['msg_content'].value;
    var msg = '';

    if (msg_title.length == 0) {
        msg += msg_title_empty + '\n';
    }
    if (msg_content.length == 0) {
        msg += msg_content_empty + '\n'
    }

    if (msg_title.length > 200) {
        msg += msg_title_limit + '\n';
    }

    if (msg.length > 0) {
        alert(msg);
        return false;
    }
    else {
        return true;
    }
}

/* *
 * 会员找回密码时，对输入作处理
 */
function submitPwdInfo(obj) {
    var obj = $(obj).parents("form[name='getPassword']");
    var obj_div = obj.parent();
    var user_name = obj.find("input[name='user_name']");
    var email = obj.find("input[name='email']");
    var phone = obj.find("input[name='mobile_phone']");
    var wenti = obj.find("input[name='wenti']");
    var captcha = obj.find("input[name='captcha']");
    var sel_question = obj.find("select[name='sel_question']");
    var passwd_answer = obj.find("input[name='passwd_answer']");
    var errorMsg = '';
    var msg = obj.find('.msg_ts');
    var email_enabled_captcha = obj.find("input[name='email_enabled_captcha']");
    var captcha_verification = obj.find("input[name='captcha_verification']");
    var seKey = $(obj).find("img[name='img_captcha']").data('key');

    var myreg = /^(((13[0-9]{1})|(15[0-9]{1})|(18[0-9]{1})|(17[0-9]{1}))+\d{8})$/;
    var fade = false;

    if (obj_div.hasClass('formEmail')) {
        if (user_name.val().length == 0) {
            msg.show();
            msg.find(".error").html(json_languages.null_username);
            return false;
        } else if (email.val().length == 0) {
            msg.show();
            msg.find('.error').html(json_languages.null_email);
            return false;
        } else if (document.getElementById('captcha') && captcha.val().length == 0) {
            msg.show();
            msg.find('.error').html(json_languages.null_captcha);
            return false;
        } else {
            fade = true;
        }
    }

    if (obj_div.hasClass('formPhone')) {
        if (user_name.val().length == 0) {
            msg.show();
            msg.find('.error').html(json_languages.null_username);
            return false;
        } else if (phone.val().length == 0) {
            msg.show();
            msg.find('.error').html(json_languages.null_phone);
            return false;
        } else if (phone.val().length > 0) {
            if (phone.val().length != 11) {
                msg.find('.error').html('<i></i>' + phone_address_empty_11);
                return false;
            } else if (!myreg.test(phone.val())) {
                msg.find('.error').html('<i></i>' + phone_address_empty_bzq);
                return false;
            }
        } else {
            fade = true;
        }
    }

    if (obj_div.hasClass('formWenti')) {
        if (user_name.val().length == 0) {
            msg.show();
            msg.find('.error').html(json_languages.null_username);
            return false;
        } else if (sel_question.val() == 0) {
            msg.show();
            msg.find('.error').html(json_languages.select_password_question);
            return false;
        } else if (passwd_answer.val().length == 0) {
            msg.show();
            msg.find('.error').html(json_languages.null_password_question);
            return false;
        } else if (captcha.val().length == 0) {
            msg.show();
            msg.find('.error').html(json_languages.null_captcha);
            return false;
        } else {
            fade = true;
        }
    }

    if (obj_div.hasClass('formEmail')) {
        if (document.getElementById('captcha')) {
            if (captcha.val().length == 4) {
                Ajax.call('user.php?act=captchas_pass', 'captcha=' + captcha.val() + '&seKey=' + seKey, check_captcha_callback, 'GET', 'TEXT', true, true);
            } else {
                msg.show();
                msg.find('.error').html(json_languages.error_email);
                return false;
            }
        }
    } else {
        if (document.getElementById('mobile_captcha')) {
            if (captcha.val().length == 4) {
                Ajax.call('user.php?act=captchas_pass', 'captcha=' + captcha.val() + '&seKey=' + seKey, check_captcha_callback, 'GET', 'TEXT', true, true);
            } else {
                msg.show();
                msg.find('.error').html(json_languages.error_email);
                return false;
            }
        }
    }

    function check_captcha_callback(result) {

        if (result.replace(/\r\n/g, '') == ' ok') {
            captcha_verification.val(0);
        }
        else {
            captcha_verification.val(1);
        }
    }

    if (document.getElementById('captcha') && captcha_verification.val() == 0) {
        fade = false;
        msg.show();
        msg.find('.msg_error').html(json_languages.error_email);
    } else {
        fade = true;
    }

    if (fade == true) {
        obj.submit();
    }
}


/* *
 * 会员找回密码时，对输入作处理
 */
function submitPwd() {
    var frm = document.forms['getPassword2'];
    var password = frm.elements['new_password'].value;
    var confirm_password = frm.elements['confirm_password'].value;

    var errorMsg = '';
    if (password.length == 0) {
        errorMsg += new_password_empty + '\n';
    }

    if (confirm_password.length == 0) {
        errorMsg += confirm_password_empty + '\n';
    }

    if (confirm_password != password) {
        errorMsg += both_password_error + '\n';
    }

    if (errorMsg.length > 0) {
        alert(errorMsg);
        return false;
    }
    else {
        return true;
    }
}

/* *
 * 处理会员提交的缺货登记
 */
function addBooking() {
    var frm = document.forms['formBooking'];
    var goods_id = frm.elements['id'].value;
    var rec_id = frm.elements['rec_id'].value;
    var number = frm.elements['number'].value;
    var desc = frm.elements['desc'].value;
    var linkman = frm.elements['linkman'].value;
    var email = frm.elements['email'].value;
    var tel = frm.elements['tel'].value;
    var msg = "";

    if (number.length == 0) {
        msg += booking_amount_empty + '\n';
    }
    else {
        var reg = /^[0-9]+/;
        if (!reg.test(number)) {
            msg += booking_amount_error + '\n';
        }
    }

    if (desc.length == 0) {
        msg += describe_empty + '\n';
    }

    if (linkman.length == 0) {
        msg += contact_username_empty + '\n';
    }

    if (email.length == 0) {
        msg += email_empty + '\n';
    }
    else {
        if (!(Utils.isEmail(email))) {
            msg += json_languages.email_error + '\n';
        }
    }

    if (tel.length == 0) {
        msg += contact_phone_empty + '\n';
    }

    if (msg.length > 0) {
        alert(msg);
        return false;
    }

    return true;
}

/* *
 * 会员登录
*/
function userLogin() {
    var frm = $("form[name='formLogin']");
    var username = frm.find("input[name='username']");
    var password = frm.find("input[name='password']");
    var captcha = frm.find("input[name='captcha']");
    var remember = frm.find("input[name='remember']");
    var error = frm.find(".msg-error");
    var msg = '';
    var remember_string = "";


    /*保存登录信息 by wu start*/
    if (remember.filter(":checked").length > 0) {
        remember_string = '&remember=' + remember.val();
    }
    /*保存登录信息 by wu end*/
    if (username.val() == "") {
        error.show();
        username.parents(".item").addClass("item-error");
        msg += username_empty;
        showMesInfo(msg);
        return false;
    }

    if (password.val() == "") {
        error.show();
        password.parents(".item").addClass("item-error");
        msg += password_empty;
        showMesInfo(msg);
        return false;
    }

    if (captcha.val() == "") {
        error.show();
        captcha.parents(".item").addClass("item-error");
        msg += captcha_empty;
        showMesInfo(msg);
        return false;
    }
    var beforeUrl = frm.find("input[name='beforeUrl']").val();
    $.post('/home/login/index', {
        username:  username.val(),
        password: password.val(),
        captcha: captcha.val(),
        remember: remember.val(),

    }, function (ret) {
    	if (ret.status == 1) {
    		window.location.href = beforeUrl;
    	} else {
            showMesInfo(ret.msg);
    	}
    }, 'json');
}

/**
 * 短息登录
 * @param
 */
function smsLogin() {
    var frm = $("form[name='formLogin']");
    var username = frm.find("input[name='username']");
    var mobile_code = frm.find("input[name='mobile_code']");
    var captcha = frm.find("input[name='captcha']");

    var error = frm.find(".msg-error");
    var msg = '';
    if (username.val() == "") {
        error.show();
        username.parents(".item").addClass("item-error");
        msg += mobile_empty;
        showMesInfo(msg);
        return false;
    }
    if (mobile_code.val() == "") {
        error.show();
        mobile_code.parents(".item").addClass("item-error");
        msg += mobile_code_empty;
        showMesInfo(msg);
        return false;
    }
    if (captcha.val() == "") {
        error.show();
        captcha.parents(".item").addClass("item-error");
        msg += captcha_empty;
        showMesInfo(msg);
        return false;
    }


    var beforeUrl = frm.find("input[name='beforeUrl']").val();
    $.post('/home/login/smslogin', {
        mobile:  username.val(),
        mobile_code: mobile_code.val(),
        captcha: captcha.val(),

    }, function (ret) {
        if (ret.status == 1) {
            window.location.href = '/home/member/index';
        } else {;
            var msg = "<i class='iconfont icon-minus-sign'></i>";
            msg += ret.msg;
            error.show();
            username.parents(".item").addClass("item-error");
            showMesInfo(msg);
        }
    }, 'json');
}


function showMesInfo(msg) {
    $('.login-wrap .msg-wrap').empty();
    var info = '<div class="msg-error"><b></b>' + msg + '</div>';
    $('.login-wrap .msg-wrap').append(info);
}

function chkstr(str) {
    for (var i = 0; i < str.length; i++) {
        if (str.charCodeAt(i) < 127 && !str.substr(i, 1).match(/^\w+$/ig)) {
            return false;
        }
    }
    return true;
}

function check_password(password) {
    if (password.length < 6) {
        document.getElementById('password_notice').innerHTML = password_shorter;
    }
    else {
        document.getElementById('password_notice').innerHTML = msg_can_rg;
    }
}

function check_conform_password(conform_password) {
    password = document.getElementById('password1').value;

    if (conform_password.length < 6) {
        document.getElementById('conform_password_notice').innerHTML = password_shorter;
        return false;
    }
    if (conform_password != password) {
        document.getElementById('conform_password_notice').innerHTML = confirm_password_invalid;
    }
    else {
        document.getElementById('conform_password_notice').innerHTML = msg_can_rg;
    }
}

function is_registered(username, register_mode) {
    if (register_mode == 1) {
        var frm = document.forms['formUser'];
    } else {
        var frm = document.forms['formUserE'];
    }

    var submit_disabled = false;
    var unlen = username.replace(/[^\x00-\xff]/g, "**").length;

    if (!Utils.isNumber(register_mode)) {
        var submit_disabled = true;
    }
    if (username == '') {
        $('#username_notice_' + register_mode).html(msg_un_blank);
        var submit_disabled = true;
    }

    if (!chkstr(username)) {
        $('#username_notice_' + register_mode).html(msg_un_format);
        var submit_disabled = true;
    }
    if (unlen < 4) {
        $('#username_notice_' + register_mode).html(username_shorter);
        var submit_disabled = true;
    }
    if (unlen > 15) {
        $('#username_notice_' + register_mode).html(msg_un_length);
        var submit_disabled = true;
    }
    if (submit_disabled) {
        if (register_mode == 1) {
            frm.elements['Submit'].disabled = 'disabled';
        } else {
            frm.elements['Submit'].disabled = 'disabled';
        }
        $('#username_notice_' + register_mode).removeClass().addClass("error");
        return false;
    }
    Ajax.call('/home/member/is_registered', 'user_name=' + username + "&mode=" + register_mode, registed_callback, 'GET', 'JSON', true, true);
}

function is_extend_field(val, id, form) {
    if (val != '') {
        $("form[name='" + form + "']").find(".extend_field" + id).html('');
    }
}

function registed_callback(data) {
    if (data.result == "ok") {
        $("#username_notice_" + data.mode).removeClass("error").addClass("succeed");
        $("#username_notice_" + data.mode).html("<i></i>");
        if (data.mode == 1) {
            document.forms['formUser'].elements['Submit'].disabled = '';
        } else {
            document.forms['formUserE'].elements['Submit'].disabled = '';
        }
    }
    else {
        document.getElementById('username_notice_' + data.mode).className = "error";
        document.getElementById('username_notice_' + data.mode).innerHTML = msg_un_registered;
        if (data.mode == 1) {
            document.forms['formUser'].elements['Submit'].disabled = 'disabled';
        } else {
            document.forms['formUserE'].elements['Submit'].disabled = 'disabled';
        }
    }
}

//ecmoban模板堂 --zhuo start
function is_mobile_phone(phone) {
    var submit_disabled = false;
    var unlen = phone.replace(/[^\x00-\xff]/g, "**").length;
    var myreg = /^(((13[0-9]{1})|(15[0-9]{1})|(18[0-9]{1})|(17[0-9]{1}))+\d{8})$/;

    if (!myreg.test(phone)) {
        document.getElementById('phone_notice').innerHTML = Mobile_error;
        var submit_disabled = true;
    }

    if (unlen != 11) {
        document.getElementById('phone_notice').innerHTML = Mobile_error;
        var submit_disabled = true;
    }

    if (submit_disabled) {
        document.forms['formUser'].elements['Submit'].disabled = 'disabled';
        return false;
    }

    Ajax.call('user.php?act=is_mobile_phone', 'phone=' + phone, phone_callback, 'GET', 'TEXT', true, true);
}

function phone_callback(result) {
    if (result == "true") {
        document.getElementById('phone_notice').innerHTML = msg_can_rg;
        document.forms['formUser'].elements['Submit'].disabled = '';
    }
    else {
        document.getElementById('phone_notice').innerHTML = exist_phone;
        document.forms['formUser'].elements['Submit'].disabled = 'disabled';
    }
}

//ecmoban模板堂 --zhuo end

function checkEmail(email) {
    var submit_disabled = false;

    if (email == '') {
        document.getElementById('email_notice').innerHTML = msg_email_blank;
        submit_disabled = true;
    }
    else if (!Utils.isEmail(email)) {
        document.getElementById('email_notice').innerHTML = msg_email_format;
        submit_disabled = true;
    }

    if (submit_disabled) {
        document.forms['formUserE'].elements['Submit'].disabled = 'disabled';
        return false;
    }
    Ajax.call('user.php?act=check_email', 'email=' + email, check_email_callback, 'GET', 'TEXT', true, true);
}

function check_email_callback(result) {
    if (result.replace(/\r\n/g, '') == ' ok') {
        document.getElementById('email_notice').innerHTML = msg_can_rg;
        document.forms['formUserE'].elements['Submit'].disabled = '';
    }
    else {
        document.getElementById('email_notice').innerHTML = msg_email_registered;
        document.forms['formUserE'].elements['Submit'].disabled = 'disabled';
    }
}


/* *
 * 用户中心订单保存地址信息
 */
function saveOrderAddress(id) {
    var frm = document.forms['formAddress'];
    var consignee = frm.elements['consignee'].value;
    var email = frm.elements['email'].value;
    var address = frm.elements['address'].value;
    var zipcode = frm.elements['zipcode'].value;
    var tel = frm.elements['tel'].value;
    var mobile = frm.elements['mobile'].value;
    var sign_building = frm.elements['sign_building'].value;
    var best_time = frm.elements['best_time'].value;

    if (id == 0) {
        alert(current_ss_not_unshipped);
        return false;
    }
    var msg = '';
    if (address.length == 0) {
        msg += address_name_not_null + "\n";
    }
    if (consignee.length == 0) {
        msg += consignee_not_null + "\n";
    }

    if (msg.length > 0) {
        alert(msg);
        return false;
    }
    else {
        return true;
    }
}

/* *
 * 会员余额申请
 */
function submitSurplus() {
    var frm = document.forms['formSurplus'];
    var surplus_type = frm.elements['surplus_type'].value;
    var surplus_amount = frm.elements['amount'].value;
    var process_notic = frm.elements['user_note'].value;
    var payment_id = 0;
    var msg = '';

    if (surplus_amount.length == 0) {
        msg += surplus_amount_empty + "\n";
    }
    else {
        var reg = /^[\.0-9]+/;
        if (!reg.test(surplus_amount)) {
            msg += surplus_amount_error + '\n';
        }
    }

    if (process_notic.length == 0) {
        msg += process_desc + "\n";
    }

    if (msg.length > 0) {
        alert(msg);
        return false;
    }

    if (surplus_type == 0) {
        for (i = 0; i < frm.elements.length; i++) {
            if (frm.elements[i].name == "payment_id" && frm.elements[i].checked) {
                payment_id = frm.elements[i].value;
                break;
            }
        }

        if (payment_id == 0) {
            alert(payment_empty);
            return false;
        }
    }

    return true;
}

/* *
 *  处理用户添加一个红包
 */
function addBonus() {
    var frm = document.forms['addBouns'];
    var bonus_sn = frm.elements['bonus_sn'].value;

    if (bonus_sn.length == 0) {
        alert(bonus_sn_empty);
        return false;
    }
    else {
        var reg = /^[0-9]{10}$/;
        if (!reg.test(bonus_sn)) {
            alert(bonus_sn_error);
            return false;
        }
    }

    return true;
}

/* *
 *  合并订单检查
 */
function mergeOrder() {
    if (!confirm(confirm_merge)) {
        return false;
    }

    var frm = document.forms['formOrder'];
    var from_order = frm.elements['from_order'].value;
    var to_order = frm.elements['to_order'].value;
    var msg = '';

    if (from_order == 0) {
        msg += from_order_empty + '\n';
    }
    if (to_order == 0) {
        msg += to_order_empty + '\n';
    }
    else if (to_order == from_order) {
        msg += order_same + '\n';
    }
    if (msg.length > 0) {
        alert(msg);
        return false;
    }
    else {
        return true;
    }
}

/* *
 * 订单中的商品返回购物车
 * @param       int     orderId     订单号
 */
function returnToCart(orderId) {
    var bra = "";
    var length = $(".product-item input[type='checkbox']:checked").length;
    $(".product-item input[type='checkbox']").each(function () {
        if ($(this).prop("checked") == true) {
            var val = $(this).val();
            bra += val + ",";
        }
    });
    bra = bra.substring(0, bra.length - 1);

    if (bra.length == 0) {
        alert(select_cart_goods);
        return;
    }
    Ajax.call('user.php?act=return_to_cart', 'order_id=' + orderId + '&rec_id=' + bra, returnToCartResponse, 'POST', 'JSON');
}

function returnToCartResponse(result) {
    alert(result.message);

    if (result.error == 0) {
        $("#ECS_CARTINFO").html(result.cart_info);
    }
}

/* *
 * 检测密码强度
 * @param       string     pwd     密码
 */
function checkIntensity(pwd) {
    var Mcolor = "#FFF", Lcolor = "#FFF", Hcolor = "#FFF";
    var m = 0;

    var Modes = 0;
    for (i = 0; i < pwd.length; i++) {
        var charType = 0;
        var t = pwd.charCodeAt(i);
        if (t >= 48 && t <= 57) {
            charType = 1;
        }
        else if (t >= 65 && t <= 90) {
            charType = 2;
        }
        else if (t >= 97 && t <= 122)
            charType = 4;
        else
            charType = 4;
        Modes |= charType;
    }

    for (i = 0; i < 4; i++) {
        if (Modes & 1) m++;
        Modes >>>= 1;
    }

    if (pwd.length <= 4) {
        m = 1;
    }

    switch (m) {
        case 1 :
            Lcolor = "2px solid red";
            Mcolor = Hcolor = "2px solid #DADADA";
            break;
        case 2 :
            Mcolor = "2px solid #f90";
            Lcolor = Hcolor = "2px solid #DADADA";
            break;
        case 3 :
            Hcolor = "2px solid #3c0";
            Lcolor = Mcolor = "2px solid #DADADA";
            break;
        case 4 :
            Hcolor = "2px solid #3c0";
            Lcolor = Mcolor = "2px solid #DADADA";
            break;
        default :
            Hcolor = Mcolor = Lcolor = "";
            break;
    }
    if (document.getElementById("pwd_lower")) {
        document.getElementById("pwd_lower").style.borderBottom = Lcolor;
        document.getElementById("pwd_middle").style.borderBottom = Mcolor;
        document.getElementById("pwd_high").style.borderBottom = Hcolor;
    }


}

function changeType(obj) {
    if (obj.getAttribute("min") && document.getElementById("ECS_AMOUNT")) {
        document.getElementById("ECS_AMOUNT").disabled = false;
        document.getElementById("ECS_AMOUNT").value = obj.getAttribute("min");
        if (document.getElementById("ECS_NOTICE") && obj.getAttribute("to") && obj.getAttribute('fee')) {
            var fee = parseInt(obj.getAttribute("fee"));
            var to = parseInt(obj.getAttribute("to"));
            if (fee < 0) {
                to = to + fee * 2;
            }
            document.getElementById("ECS_NOTICE").innerHTML = notice_result + to;
        }
    }
}

function calResult() {
    var amount = document.getElementById("ECS_AMOUNT").value;
    var notice = document.getElementById("ECS_NOTICE");

    reg = /^\d+$/;
    if (!reg.test(amount)) {
        notice.innerHTML = notice_not_int;
        return;
    }
    amount = parseInt(amount);
    var frm = document.forms['transform'];
    for (i = 0; i < frm.elements['type'].length; i++) {
        if (frm.elements['type'][i].checked) {
            var min = parseInt(frm.elements['type'][i].getAttribute("min"));
            var to = parseInt(frm.elements['type'][i].getAttribute("to"));
            var fee = parseInt(frm.elements['type'][i].getAttribute("fee"));
            var result = 0;
            if (amount < min) {
                notice.innerHTML = notice_overflow + min;
                return;
            }

            if (fee > 0) {
                result = (amount - fee) * to / (min - fee);
            }
            else {
                //result = (amount + fee* min /(to+fee)) * (to + fee) / min ;
                result = amount * (to + fee) / min + fee;
            }

            notice.innerHTML = notice_result + parseInt(result + 0.5);
        }
    }
}

//收货地址--验证收货人是否为空
function setCheckConsignee(user_name) {
    if (user_name != '') {
        $(".consignee_error").removeClass("show").addClass('hide');
    }
}

//address----省份选择
function setCheckProvinces(province) {
    if (province != '') {
        $(".area_error").removeClass("show").addClass('hide');
    }
}

//address----城市选择
function setCheckCities(city) {
    if (city != '') {
        $(".area_error").removeClass("show").addClass('hide');
    }
}

//address----县区选择
function setCheckDistricts(district) {
    if (district != '') {
        $(".area_error").removeClass("show").addClass('hide');
    }
}

//address----街道选择
function setCheckStreets(street) {
    if (street != '') {
        $(".area_error").removeClass("show").addClass('hide');
    }
}


//address----详细地址
function setCheckAddress(address) {
    if (address != '') {
        $(".address_error").removeClass("show").addClass('hide');
    }
}

//address----联系方式
function setCheckPhone(phone) {
    if (phone != '') {
        $(".phone_error").removeClass("show").addClass('hide');
    }
}

//address----Email
function setCheckEmail(email) {
    if (email != '') {
        $(".email_error").removeClass("show").addClass('hide');
    }
}
