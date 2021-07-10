function sendSms() {
    if (document.getElementById('zphone').disabled==true){
        return;
    }
    var mobile = document.getElementById('mobile_phone').value;
    $.post('/home/login/is_registered', {
        mobile: mobile,
        act: 'sendsms'
    }, function (result) {
        let phone_notice=$("#phone_notice");
        if (result.status == 1) {
            RemainTime();
            // 安全中心 start qin
            $('#mobile_code').removeAttr("disabled");
            // 安全中心 end
            $("#seccode").val(result.data.smscode);
            phone_notice.removeClass("error").addClass("succeed").html("<i></i>");
        } else {
            phone_notice.show();
            if (result.msg) {
                phone_notice.removeClass("error").addClass("error").html("<i></i>" + result.msg);
            } else {
                phone_notice.removeClass("error").addClass("error").html("<i></i>" + "手机验证码发送失败");
            }
        }
    }, 'json');
}

//短信登录发送短信
function sendsmsLogin() {
    var mobile = document.getElementById('username').value;
    $.post('/home/login/is_registered', {
        mobile: mobile,
        act: 'sendsmsLogin'
    }, function (ret) {
        if (ret.status == 0) {
            var frm = $("form[name='formLogin']");
            var username = frm.find("input[name='username']");
            var error = frm.find(".msg-error");
            var msg = "<i class='iconfont icon-minus-sign'></i>";
            msg += ret.msg;
            error.show();
            username.parents(".item").addClass("item-error");
            console.log(msg);
            showMesInfo(msg);
            return false;
        }else{
            RemainTime();
            // 安全中心 start qin
            $('#mobile_code').removeAttr("disabled");
        }
    }, 'json');
}
function register2() {
    var mobile = document.getElementById('mobile_phone').value;
    if (mobile_phone != '') {
        var mobile_code = document.getElementById("mobile_code").value;
        var seccode = document.getElementById('seccode').value;
        if (mobile_code.length == '') {
            alert('请填写手机验证码');
            return false;
        }
        var result = Ajax.call('sms/sms.php?act=check', 'mobile=' + mobile + '&mobile_code=' + mobile_code + '&seccode=' + seccode, null, 'POST', 'JSON', false);
        if (result.code == 2) {
            return register();
        } else {
            alert(result.msg);
            return false;
        }
    }
    return register();
}


var iTime = 59;
var Account;

/**
 * 倒计时css调用
 * @param
 */
function RemainTime() {
    document.getElementById('zphone').disabled = true;
    var iSecond, sSecond = "", sTime = "";
    if (iTime >= 0) {
        iSecond = parseInt(iTime % 60);
        if (iSecond >= 0) {
            sSecond = iSecond + "秒";
        }
        sTime = sSecond;
        if (iTime == 0) {
            clearTimeout(Account);
            sTime = '手机验证码';
            iTime = 59;
            document.getElementById('zphone').disabled = false;
        } else {
            Account = setTimeout("RemainTime()", 1000);
            iTime = iTime - 1;
        }
    } else {
        sTime = '没有倒计时';
    }
    document.getElementById('zphone').innerHTML = sTime;
}
