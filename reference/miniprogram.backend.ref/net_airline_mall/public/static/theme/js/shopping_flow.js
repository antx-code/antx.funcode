/* $Id : shopping_flow.js 4865 2007-01-31 14:04:10Z paulgao $ */

var selectedShipping = null;
var selectedPayment  = null;
var selectedPack     = null;
var selectedCard     = null;
var selectedSurplus  = '';
var selectedBonus    = 0;
var selectedVcard    = 0;
var selectedIntegral = 0;
var selectedOOS      = null;
var alertedSurplus   = false;

var groupBuyShipping = null;
var groupBuyPayment  = null;

/* *
 * 改变配送方式
 */
function selectShipping(obj)
{
  if (selectedShipping == obj)
  {
    return;
  }
  else
  {
    selectedShipping = obj;
  }

  var supportCod = obj.attributes['supportCod'].value + 0;
  var theForm = obj.form;

  for (i = 0; i < theForm.elements.length; i ++ )
  {
    if (theForm.elements[i].name == 'payment' && theForm.elements[i].attributes['isCod'].value == '1')
    {
      if (supportCod == 0)
      {
        theForm.elements[i].checked = false;
        theForm.elements[i].disabled = true;
      }
      else
      {
        theForm.elements[i].disabled = false;
      }
    }
  }

  if (obj.attributes['insure'].value + 0 == 0)
  {
    document.getElementById('ECS_NEEDINSURE').checked = false;
    document.getElementById('ECS_NEEDINSURE').disabled = true;
  }
  else
  {
    document.getElementById('ECS_NEEDINSURE').checked = false;
    document.getElementById('ECS_NEEDINSURE').disabled = false;
  }
  
  var warehouse_id = $("#theForm").find("input[name='warehouse_id']").val();
  var area_id = $("#theForm").find("input[name='area_id']").val();

  var now = new Date();
  Ajax.call('flow.php?step=select_shipping', 'shipping=' + obj.value + '&warehouse_id=' + warehouse_id + '&area_id=' + area_id, orderShippingSelectedResponse, 'GET', 'JSON');
}

/**
 *
 */
function orderShippingSelectedResponse(result)
{
  if (result.need_insure)
  {
    try
    {
      document.getElementById('ECS_NEEDINSURE').checked = true;
    }
    catch (ex)
    {
      alert(ex.message);
    }
  }

  try
  {
    if (document.getElementById('ECS_CODFEE') != undefined)
    {
      document.getElementById('ECS_CODFEE').innerHTML = result.cod_fee;
    }
  }
  catch (ex)
  {
    alert(ex.message);
  }

  orderSelectedResponse(result);
}

/* *
 * 改变支付方式
 */
function selectPayment(value)
{
  if (selectedPayment == value)
  {
    return;
  }
  else
  {
    selectedPayment = value;
  }
  
  var warehouse_id = $("#theForm").find("input[name='warehouse_id']").val();
  var area_id = $("#theForm").find("input[name='area_id']").val();
  var shipping_id = get_cart_shipping_id();
  
    /*by kong 门店id*/
  var store_id = document.getElementById('store_id').value;
  (store_id > 0) ? store_id : 0;
   var store_seller = document.getElementById('store_seller').value;
  Ajax.call('flow.php?step=select_payment', 'payment=' + value + '&warehouse_id=' + warehouse_id + '&area_id=' + area_id + '&store_id=' +store_id + '&store_seller='+store_seller + '&shipping_id=' + $.toJSON(shipping_id), orderSelectedResponse, 'GET', 'JSON');
}
/* *
 * 团购购物流程 --> 改变配送方式
 */
function handleGroupBuyShipping(obj)
{
  if (groupBuyShipping == obj)
  {
    return;
  }
  else
  {
    groupBuyShipping = obj;
  }

  var supportCod = obj.attributes['supportCod'].value + 0;
  var theForm = obj.form;

  for (i = 0; i < theForm.elements.length; i ++ )
  {
    if (theForm.elements[i].name == 'payment' && theForm.elements[i].attributes['isCod'].value == '1')
    {
      if (supportCod == 0)
      {
        theForm.elements[i].checked = false;
        theForm.elements[i].disabled = false;
      }
      else
      {
        theForm.elements[i].disabled = false;
      }
    }
  }

  if (obj.attributes['insure'].value + 0 == 0)
  {
    document.getElementById('ECS_NEEDINSURE').checked = false;
    document.getElementById('ECS_NEEDINSURE').disabled = true;
  }
  else
  {
    document.getElementById('ECS_NEEDINSURE').checked = false;
    document.getElementById('ECS_NEEDINSURE').disabled = false;
  }

  Ajax.call('group_buy.php?act=select_shipping', 'shipping=' + obj.value, orderSelectedResponse, 'GET');
}

/* *
 * 团购购物流程 --> 改变支付方式
 */
function handleGroupBuyPayment(obj)
{
  if (groupBuyPayment == obj)
  {
    return;
  }
  else
  {
    groupBuyPayment = obj;
  }

  Ajax.call('group_buy.php?act=select_payment', 'payment=' + obj.value, orderSelectedResponse, 'GET');
}

/* *
 * 改变商品包装
 */
function selectPack(obj)
{
  if (selectedPack == obj)
  {
    return;
  }
  else
  {
    selectedPack = obj;
  }
  
  var warehouse_id = $("#theForm").find("input[name='warehouse_id']").val();
  var area_id = $("#theForm").find("input[name='area_id']").val();

  Ajax.call('flow.php?step=select_pack', 'pack=' + obj.value + '&warehouse_id=' + warehouse_id + '&area_id=' + area_id, orderSelectedResponse, 'GET', 'JSON');
}

/* *
 * 改变祝福贺卡
 */
function selectCard(obj)
{
  if (selectedCard == obj)
  {
    return;
  }
  else
  {
    selectedCard = obj;
  }
  
  var warehouse_id = $("#theForm").find("input[name='warehouse_id']").val();
  var area_id = $("#theForm").find("input[name='area_id']").val();

  Ajax.call('flow.php?step=select_card', 'card=' + obj.value + '&warehouse_id=' + warehouse_id + '&area_id=' + area_id, orderSelectedResponse, 'GET', 'JSON');
}

/* *
 * 选定了配送保价
 */
function selectInsure(needInsure)
{
  needInsure = needInsure ? 1 : 0;
  
  var warehouse_id = $("#theForm").find("input[name='warehouse_id']").val();
  var area_id = $("#theForm").find("input[name='area_id']").val();

  Ajax.call('flow.php?step=select_insure', 'insure=' + needInsure + '&warehouse_id=' + warehouse_id + '&area_id=' + area_id, orderSelectedResponse, 'GET', 'JSON');
}

/* *
 * 团购购物流程 --> 选定了配送保价
 */
function handleGroupBuyInsure(needInsure)
{
  needInsure = needInsure ? 1 : 0;

  Ajax.call('group_buy.php?act=select_insure', 'insure=' + needInsure, orderSelectedResponse, 'GET', 'JSON');
}

/* *
 * 回调函数
 */
function orderSelectedResponse(result){
	
	if(result.error){
		if(result.error == 1){
			pbDialog(json_languages.cart_empty_goods,"",0);
		}else if(result.error == 2){
			pbDialog(json_languages.please_checked_address,"",0);
		}
		return false;
	}

	try{
		var layer = document.getElementById("ECS_ORDERTOTAL");
		var goods_inventory = document.getElementById("goods_inventory");
		layer.innerHTML = (typeof result == "object") ? result.content : result;
	  tfootScroll();
		if(result.goods_list){
			goods_inventory.innerHTML = (typeof result == "object") ? result.goods_list : result;
		}   
	
		if (result.payment != undefined){
			var surplusObj = document.getElementById('ECS_SURPLUS');
			if (surplusObj != undefined){
				//surplusObj.disabled = result.pay_code == 'balance';
			}
		}
	}catch(ex){
	
	}
}

/* *
 * 改变余额
 */
function changeSurplus(val){
		
  var warehouse_id = $("#theForm").find("input[name='warehouse_id']").val();
  var area_id = $("#theForm").find("input[name='area_id']").val();
  var payPw = $("#qt_onlinepay");	  //支付密码
  var shipping_id = get_cart_shipping_id();

  /*by kong 门店id*/
  var store_id = document.getElementById('store_id').value;
  (store_id > 0) ? store_id : 0;
	
	/*获取 价格 by yanxin*/
	var sur = $(".sur").val();
	var shipping = $(".shipping").val();
	sur = sur.replace(/<[^<>]+>/g,'');
	sur = sur.replace('¥','');
	
	shipping = shipping.replace(/<[^<>]+>/g,'');
	shipping = shipping.replace('¥','');
	total_price = parseFloat(sur) + parseFloat(shipping);
	/*获取 价格 by yanxin*/
	
	if(selectedSurplus === val && val != 0){
		return;
	}else{
		if(val > total_price){
			 $("#ECS_SURPLUS").val(total_price)
		}else{
			selectedSurplus = val;
		}
	}
  
	//验证支付密码
	if(payPw.length > 0){
		//非在线支付状态，使用余额抵扣，余额输入框大于0，支付密码填写框展示
		if(val > 0){
			//支付密码显示
			payPw.show();
			
			//初始化支付密码
			payPw.find("input[name='pay_pwd']").val("");
			
			//支付密码隐藏域值赋值为1
			payPw.find("input[name='pay_pwd_error']").val(1);
		}else{
			//支付密码隐藏
			payPw.hide();
			
			//支付密码隐藏域值赋值为0
			payPw.find("input[name='pay_pwd_error']").val(0);
		}
	}

	Ajax.call('flow.php?step=change_surplus', 'surplus=' + val + '&warehouse_id=' + warehouse_id + '&area_id=' + area_id + '&store_id=' + store_id + '&shipping_id=' + $.toJSON(shipping_id), changeSurplusResponse, 'GET', 'JSON');
}

/* *
 * 改变余额回调函数
 */
function changeSurplusResponse(obj)
{
	if(obj.error){
		try
		{
			document.getElementById("ECS_SURPLUS_NOTICE").innerHTML = obj.error;
			document.getElementById('ECS_SURPLUS').value = '0';
		}
		catch (ex) { }
	}else{
		try
		{
			document.getElementById("ECS_SURPLUS_NOTICE").innerHTML = '';
		}
		catch (ex) { }
		orderSelectedResponse(obj.content);
	}
}

/* *
 * 改变积分
 */
function changeIntegral(val)
{
	var warehouse_id = $("#theForm").find("input[name='warehouse_id']").val();
	var area_id = $("#theForm").find("input[name='area_id']").val();
	var payPw = $("#qt_onlinepay");	  //支付密码
	var shipping_id = get_cart_shipping_id();

  /*by kong 门店id*/
  var store_id = document.getElementById('store_id').value;
  (store_id > 0) ? store_id : 0;

	if(selectedIntegral === val && val != 0){
		return;
	}else{
		selectedIntegral = val;
	}

  if(payPw.length > 0){
    payPw.show();
  }
  
	Ajax.call('flow.php?step=change_integral', 'points=' + val + '&warehouse_id=' + warehouse_id + '&area_id=' + area_id + '&store_id=' + store_id + '&shipping_id=' + $.toJSON(shipping_id), changeIntegralResponse, 'GET', 'JSON');
}

/* *
 * 改变积分回调函数
 */
function changeIntegralResponse(obj)
{
  if (obj.error)
  {
    try
    {
      document.getElementById('ECS_INTEGRAL_NOTICE').innerHTML = obj.error;
      document.getElementById('ECS_INTEGRAL').value = '0';
    }
    catch (ex) { }
  }
  else
  {
    try
    {
      document.getElementById('ECS_INTEGRAL_NOTICE').innerHTML = '';
    }
    catch (ex) { }
    orderSelectedResponse(obj.content);
  }
}

/* *
 * 改变红包
 */
function changeBonus(val)
{
	var warehouse_id = $("#theForm").find("input[name='warehouse_id']").val();
   	var area_id = $("#theForm").find("input[name='area_id']").val();
	var shipping_id = get_cart_shipping_id();
	
  if (selectedBonus == val)
  {
    return;
  }
  else
  {
    selectedBonus = val;
  }

  Ajax.call('flow.php?step=change_bonus', 'bonus=' + val + '&warehouse_id=' + warehouse_id + '&area_id=' + area_id + '&shipping_id=' + $.toJSON(shipping_id), changeBonusResponse, 'GET', 'JSON');
}

/* *
 * 改变红包的回调函数
 */
function changeBonusResponse(obj)
{
  orderSelectedResponse(obj);
}

/* *
 * 改变储值卡
 */
function changeVcard(val)
{
	var warehouse_id = $("#theForm").find("input[name='warehouse_id']").val();
   	var area_id = $("#theForm").find("input[name='area_id']").val();
	var store_id = $("#theForm").find("input[name='store_id']").val();
	var shipping_id = get_cart_shipping_id();
    
	if (selectedVcard == val){
		return;
	}else{
		selectedVcard = val;
	}
	
	Ajax.call('flow.php?step=change_value_card', 'value_card=' + val + '&warehouse_id=' + warehouse_id + '&area_id=' + area_id + '&store_id=' + store_id + '&shipping_id=' + $.toJSON(shipping_id), changeVcardResponse, 'GET', 'JSON');
}

/* *
 * 改变储值卡的回调函数
 */
function changeVcardResponse(obj)
{
	
	var payPw = $("#qt_onlinepay");	  //支付密码
	var is_pay = 0;
	
	if(document.getElementById('ECS_VALUE_CARD').value > 0){
		if(document.getElementById('value_card_psd')){
			document.getElementById('value_card_psd').disabled = true;	
			document.getElementById('value_card_psd').value = '';
			
			is_pay = 1;
		}		
	}else{
		if(document.getElementById('value_card_psd')){
			document.getElementById('value_card_psd').disabled = false;
			
			is_pay = 0;
		}
	}
	
	//验证支付密码
	if(payPw.length > 0){
		//非在线支付状态，使用余额抵扣，余额输入框大于0，支付密码填写框展示
		if(is_pay > 0){
			//支付密码显示
			payPw.show();
			
			//初始化支付密码
			payPw.find("input[name='pay_pwd']").val("");
			
			//支付密码隐藏域值赋值为1
			payPw.find("input[name='pay_pwd_error']").val(1);
		}else{
			//支付密码隐藏
			payPw.hide();
			
			//支付密码隐藏域值赋值为0
			payPw.find("input[name='pay_pwd_error']").val(0);
		}
	}
	
	orderSelectedResponse(obj);
}

/**
 * 验证红包序列号
 * @param string bonusSn 红包序列号
 */
function validateBonus(bonusPsd)
{
	
	var warehouse_id = $("#theForm").find("input[name='warehouse_id']").val();
   	var area_id = $("#theForm").find("input[name='area_id']").val();
	var shipping_id = get_cart_shipping_id();
	
	Ajax.call('flow.php?step=validate_bonus', 'bonus_psd=' + bonusPsd + '&warehouse_id=' + warehouse_id + '&area_id=' + area_id + '&shipping_id=' + $.toJSON(shipping_id), validateBonusResponse, 'GET', 'JSON');
}

function validateBonusResponse(obj)
{

if (obj.error)
  {
    alert(obj.error);
    orderSelectedResponse(obj.content);
    try
    {
      document.getElementById('ECS_BONUSN').value = '0';
    }
    catch (ex) { }
  }
  else
  {
    orderSelectedResponse(obj.content);
  }
}

/**
 * 验证并绑定储值卡
 * @param string vc_psd 储值卡密码
 */
function validateVcard(vc_psd)
{
	var warehouse_id = $("#theForm").find("input[name='warehouse_id']").val();
   	var area_id = $("#theForm").find("input[name='area_id']").val();
	var shipping_id = get_cart_shipping_id();
	
	Ajax.call('flow.php?step=validate_value_card', 'vc_psd=' + vc_psd + '&warehouse_id=' + warehouse_id + '&area_id=' + area_id + '&shipping_id=' + $.toJSON(shipping_id), validateVcardResponse, 'GET', 'JSON');
}

function validateVcardResponse(obj)
{

if (obj.error)
  {
    alert(obj.error);
    orderSelectedResponse(obj.content);
    try
    {
      document.getElementById('ECS_BONUSN').value = '0';
    }
    catch (ex) { }
  }
  else
  {
	document.getElementById('ECS_VALUE_CARD').value = '0';
    orderSelectedResponse(obj.content);
  }
}

/* *
 * 改变发票的方式
 */
function changeNeedInv()
{
  var obj        = document.getElementById('ECS_NEEDINV');
  var objType    = document.getElementById('ECS_INVTYPE');
  var objPayee   = document.getElementById('ECS_INVPAYEE');
  var objContent = document.getElementById('ECS_INVCONTENT');
  var needInv    = obj.checked ? 1 : 0;
  var invType    = obj.checked ? (objType != undefined ? objType.value : '') : '';
  var invPayee   = obj.checked ? objPayee.value : '';
  var invContent = obj.checked ? objContent.value : '';
  objType.disabled = objPayee.disabled = objContent.disabled = ! obj.checked;
  if(objType != null)
  {
    objType.disabled = ! obj.checked;
  }
  
  var warehouse_id = $("#theForm").find("input[name='warehouse_id']").val();
  var area_id = $("#theForm").find("input[name='area_id']").val();

  Ajax.call('flow.php?step=change_needinv', 'need_inv=' + needInv + '&inv_type=' + encodeURIComponent(invType) + '&inv_payee=' + encodeURIComponent(invPayee) + '&inv_content=' + encodeURIComponent(invContent) + '&warehouse_id=' + warehouse_id + '&area_id=' + area_id, orderSelectedResponse, 'GET');
}

/* *
 * 改变发票的方式
 */
function groupBuyChangeNeedInv()
{
  var obj        = document.getElementById('ECS_NEEDINV');
  var objPayee   = document.getElementById('ECS_INVPAYEE');
  var objContent = document.getElementById('ECS_INVCONTENT');
  var needInv    = obj.checked ? 1 : 0;
  var invPayee   = obj.checked ? objPayee.value : '';
  var invContent = obj.checked ? objContent.value : '';
  objPayee.disabled = objContent.disabled = ! obj.checked;

  Ajax.call('group_buy.php?act=change_needinv', 'need_idv=' + needInv + '&amp;payee=' + invPayee + '&amp;content=' + invContent, null, 'GET');
}

/* *
 * 改变缺货处理时的处理方式
 */
function changeOOS(obj)
{
  if (selectedOOS == obj)
  {
    return;
  }
  else
  {
    selectedOOS = obj;
  }

  Ajax.call('flow.php?step=change_oos', 'oos=' + obj.value, null, 'GET');
}

/* //ecmoban模板堂 --zhuo 仓库 start
 * 根据元素clsssName得到元素集合
 * @param fatherId 父元素的ID，默认为document
 * @tagName 子元素的标签名
 * @className 用空格分开的className字符串
 */
function getElementsByClassName_zhuo(fatherId,tagName,className){
	node = fatherId&&document.getElementById(fatherId) || document;
	tagName = tagName || "*";
	className = className.split(" ");
	var classNameLength = className.length;
	for(var i=0,j=classNameLength;i<j;i++){
		//创建匹配类名的正则
		className[i]= new RegExp("(^|\\s)" + className[i].replace(/\-/g, "\\-") + "(\\s|$)");
	}
	var elements = node.getElementsByTagName(tagName);
	var result = [];
	for(var i=0,j=elements.length,k=0;i<j;i++){//缓存length属性
		var element = elements[i];
		while(className[k++].test(element.className)){//优化循环
			if(k === classNameLength){
				result[result.length] = element;
				break;
			}  
		}
		k = 0;
	}
	return result;
}

/* *
 * 检查提交的订单表单
 */
function checkOrderForm(frm){
	var frm = $(frm);
	var fale = true;
	var paymentSelected = false; //支付方式标识
	var store_id = $("input[name='store_id']").val(); //店铺id	
	var user_id = $("input[name='user_id']").val(); //会员id
	
	(store_id > 0) ? store_id : 0;
	
	/* 收获地址 start */
	var consignee_radio = $("#consignee-addr input[name='consignee_radio']");
	var input_length = consignee_radio.length;
	var numChecked = 0;
	
	consignee_radio.each(function(index, element) {
		if($(this).is(':checked')){
			numChecked += 1;
		}else{
			numChecked += 0;
		} 
	});

	if(user_id > 0 && store_id == 0){
		//判断是否具有收货地址
		if(input_length == 0 || numChecked == 0){			
			pbDialog(json_languages.please_checked_address,"",0,'','','',false,function(){
				$(".add-new-address,.dialog_checkout").click();
			},json_languages.add_shipping_address);
			
			fale = false;
		}
	}
  	/* 收获地址 end */

	/* 检查是否选择了配送方式或者配送方式是否支持配送 satrt*/
	var is_address = frm.find("input[name='is_address']").val();
	var shipping = frm.find("input[name='shipping[]']"); //配送方式
	var ru_name =  frm.find("input[name='ru_name[]']");   //店铺名称
	var store_seller =  frm.find("input[name='store_seller']").val();		

	if(is_address == 0){
		for(var i=0; i<shipping.length; i++){
			if(shipping[i].value == 0 && store_id==0){
				pbDialog(ru_name[i].value,json_languages.no_delivery,0);	
				fale = false;
			}
		}
	}
  	/* 检查是否选择了配送方式或者配送方式是否支持配送 end*/
	
	/* 判断是否选择支付方式 start*/
	if(frm.find("input[name='payment']").is(":checked") == true){
		paymentSelected = true;
	}
	
	if(!paymentSelected){
		pbDialog(json_languages.flow_no_payment,"",0);
		fale = false;
	}
	/* 判断是否选择支付方式 end*/
	
	
	/* 门店订单 验证是否选择门店 start*/
	if(is_address == 1){
		if(store_id == 0){
			pbDialog(json_languages.select_store,"",0);
			fale = false;
		}
		
		//提交订单验证门店是否填写手机号码
		if(checked_store_info() == false){
			fale = false;	
		}	
	}
	/* 门店订单 验证是否选择门店 end*/
  
	/* 验证支付密码 start*/
	var payPw = $("#qt_onlinepay");
	if(payPw.length > 0 && payPw.is(":hidden") == false){
		var pay_pwd = payPw.find("input[name='pay_pwd']").val();
		var pay_type = 0;
		
		$.ajax({
			url:'flow.php?step=pay_pwd',
			data:'pay_pwd='+ pay_pwd + "&type=" + pay_type,
			type:'POST',
			dataType:'json',
			async : false, //设置为同步操作就可以给全局变量赋值成功
			success:function(result){
				if(result.error == 1){
					$("#ECS_PAY_PAYPWD").html(json_languages.pay_password_packup_null);
					pbDialog(json_languages.pay_password_packup_null,"",0);
					fale = false;
				}else if(result.error == 2){
					$("#ECS_PAY_PAYPWD").html(json_languages.pay_password_packup_error);
					pbDialog(json_languages.pay_password_packup_error,"",0);
					fale = false;
				}else{
					fale = true;
				}
			}
		});
	}
	
  	/* 验证支付密码 end*/
  
	/* 检查用户输入的余额 start*/
	var surplus_input = frm.find("input[name='surplus']"); //余额input
	if(surplus_input.length > 0){
		var surplus = surplus_input.val();
		var error   = Utils.trim(Ajax.call('flow.php?step=check_surplus', 'surplus=' + surplus, null, 'GET', 'TEXT', false));
		
		if(error){
			try{
				$("#ECS_SURPLUS_NOTICE").html(error); //超出会员余额
			}catch (ex){
			}
			fale = false;
		}
	}
	/* 检查用户输入的余额 end*/
	
	/* 检查用户输入的积分 start */
	var integral_input = frm.find("input[name='integral']"); //积分input
	if(integral_input.length > 0){
		var integral = integral_input.val();
		var error = Utils.trim(Ajax.call('flow.php?step=check_integral', 'integral=' + integral, null, 'GET', 'TEXT', false));
		
		if(error){
			try{
				$("#ECS_INTEGRAL_NOTICE").html(error);
			}catch(ex){
			}
			fale = false;
		}
	}
	/* 检查用户输入的积分 end */
	
	frm.attr("action",'flow.php?step=done');
	
	return fale;
}

/* *
 * 检查收货地址信息表单中填写的内容
 */
function checkConsignee(frm)
{
  var err = false;

  if (frm.elements['province'] && frm.elements['province'].value == 0)
  {
    err = true;
    $(".area_error").removeClass("hide").addClass("show").html(json_languages.Province);
  }

  if (frm.elements['city'] && frm.elements['city'].value == 0)
  {
    err = true;
    $(".area_error").removeClass("hide").addClass("show").html(json_languages.City);
  }
  
  var district = frm.elements['district'].style.display;
  if (frm.elements['district'] && frm.elements['district'].value == 0 && district != 'none')
  {
    if (frm.elements['district'].value == 0)
    {
      err = true;
      $(".area_error").removeClass("hide").addClass("show").html(json_languages.District);
    }
  }
  
  var street = frm.elements['street'].style.display;
  if (frm.elements['street'] && frm.elements['street'].value == 0 && street != 'none')
  {
    if (frm.elements['street'].value == 0)
    {
      err = true;
      $(".area_error").removeClass("hide").addClass("show").html(json_languages.Street);
    }
  }

  if (Utils.isEmpty(frm.elements['consignee'].value))
  {
    err = true;
    $(".consignee_error").removeClass("hide").addClass("show");
  }
  
  if(frm.elements['email']){
  
	if ( frm.elements['email'].value != '' && !Utils.isEmail(frm.elements['email'].value))
	{
	  err = true;
	  $(".email_error").removeClass("hide").addClass("show").html(json_languages.email_error);
	}
  }	

  if (frm.elements['address'] && Utils.isEmpty(frm.elements['address'].value))
  {
    err = true;
    $(".address_error").removeClass("hide").addClass("show");
  }

  if(frm.elements['mobile'] && frm.elements['tel']){
	if(Utils.isEmpty(frm.elements['mobile'].value) && Utils.isEmpty(frm.elements['tel'].value)){
		$(".phone_error").removeClass("hide").addClass("show");
		err = true;
	}else{
		if (!Utils.isPhone(frm.elements['mobile'].value) && frm.elements['mobile'].value)
		{
			  err = true;
			  $(".phone_error").removeClass("hide").addClass("show").html(json_languages.Mobile_error);
		}
		
		if(frm.elements['tel'].value){
			if (!Utils.isTel(frm.elements['tel'].value) && frm.elements['tel'].value)
			{
				  err = true;
				  $(".phone_error").removeClass("hide").addClass("show").html(json_languages.phone_error);
			}
		}
	}
  }else if(frm.elements['mobile']){
	if(Utils.isEmpty(frm.elements['mobile'].value)){
		$(".phone_error").removeClass("hide").addClass("show");
		err = true;
	}else{
		if (!Utils.isPhone(frm.elements['mobile'].value) && frm.elements['mobile'].value)
		{
		  err = true;
		  $(".phone_error").removeClass("hide").addClass("show").html(json_languages.Mobile_error);
		}
	}
  }
    
  return !err;
}

/**
* 获取购物车配送方式
*/
function get_cart_shipping_id(){
	/*获取配送方式 by kong */
    var arr =[];
    $("*[shoptype='shoppingList']").each(function(k,v){
        var arr2 = [];
        var ru_id = $(this).find("input[name='ru_id[]']").val();
        var shipping = $(this).find("input[name='shipping[]']").val(); 
        arr2.push(ru_id);
        arr2.push(shipping);
        arr[k] = arr2;
    });
	
	return arr;
}