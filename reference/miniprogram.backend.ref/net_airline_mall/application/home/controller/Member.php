<?php

/**
 * =========================================================
 * Copy right 2015-2025 老孟编程, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: http://heimishop.com
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用。
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * =========================================================
 * @author : 孟老师
 * @date : 2018.1.17
 * @version : v1.0.0.0
 * @email: 835173372@qq.com
 * @controller: 用户中心核心控制器
 */

namespace app\home\controller;

use app\home\model\Giftcard;
use think\Db;

use think\facade\Request;
use think\captcha\Captcha;

use Alidayu\SignatureHelper;
use app\home\model\Order;
use app\home\model\Comment;
use app\home\model\OrderComment;


class Member extends Common
{
    protected $user_id;
    protected $user;
    protected $beforeActionList = [
        'isLogin',
    ];

    #公共短信接口-阿里大鱼-每天5条限制
    public function dayuSms($mobile = '', $sign = '')
    {
        $code = mt_rand(1111, 9999);
        /*        if (md5($mobile . $this->appkey) != $sign) {
                    ajaxmsg("非法操作", 0);
                }*/
        $info = Db::name("smsconfig")->where("is_use=1")->find();

        $params                  = [];
        $accessKeyId             = $info['appkey'];
        $accessKeySecret         = $info['secretkey'];
        $params["PhoneNumbers"]  = $mobile;
        $params["SignName"]      = $info['signature'];
        $params["TemplateCode"]  = $info['template'];
        $params['TemplateParam'] = [
            "code" => $code,
        ];

        // *** 需用户填写部分结束, 以下代码若无必要无需更改 ***
        if (!empty($params["TemplateParam"]) && is_array($params["TemplateParam"])) {
            $params["TemplateParam"] = json_encode($params["TemplateParam"], JSON_UNESCAPED_UNICODE);
        }
        $helper  = new SignatureHelper();
        $content = $helper->request(
            $accessKeyId,
            $accessKeySecret,
            "dysmsapi.aliyuncs.com",
            array_merge($params, [
                "RegionId" => "cn-hangzhou",
                "Action"   => "SendSms",
                "Version"  => "2017-05-25",
            ])
        );
        $res     = (array)$content;
        if ($res['Code'] == 'OK') {
            $count = Db::name('smscode')->where("mobile={$mobile}")->count();
            if ($count > 0) {
                Db::name('smscode')->where("mobile={$mobile}")->update([
                    'smscode' => $code,
                    'smstime' => time(),
                ]);
            } else {
                Db::name('smscode')->insert([
                    'smscode' => $code,
                    'smstime' => time(),
                    'mobile'  => $mobile,
                ]);
            }
            ajaxmsg('发送成功', 1);
        } else {
            ajaxmsg('发送失败', 0);
        }
    }

    public function checkSms($mobile = '', $smscode = '')
    {
        $count = Db::name("smscode")->where("mobile={$mobile} and smscode={$smscode}")->count();
        if (!$count) {
            return false;
        } else {
            Db::name("sys_smscode")->where("mobile={$mobile} and smscode={$smscode}")->delete();#删除验证码
            return true;
        }
    }


    public function isLogin()
    {
        if (!session('user')) {
            $this->redirect('/home/login/index');
        } else {
            $user = getUserInfo();
        }
        $this->user    = $user;
        $this->user_id = $user['user_id'];
        $act           = Request::action();
        $this->assign("act", $act);
    }


    public function logout()
    {
        session('user', null);
        session('seller', null);
        $this->redirect('/home/login/index');
    }

    #用户中心
    public function index()
    {
        if (Request::isAjax()) {

            $data             = [];
            $data['helpList'] = Db::name("article")
                ->where("cat_id=1000 and is_open=1")
                ->field("article_id,title")
                ->select();
            $sql              = "SELECT order_status,count(order_id) as total FROM oc_order where user_id={$this->user_id} GROUP BY order_status;";
            $data['order']    = array_column(Db::query($sql), 'total', 'order_status');

            $sql              = "SELECT shipping_status,count(order_id) as total FROM oc_order where user_id={$this->user_id} GROUP BY shipping_status;";
            $data['shipping'] = array_column(Db::query($sql), 'total', 'shipping_status');

            $data['couponsum'] = Db::name("coupon_list")->where("user_id={$this->user_id}")->sum("list_id");
            ajaxmsg('true', 1, $data);
        } else {
            return view();
        }
    }

    public function profile()
    {

        $user = $this->user;
        if (Request::isAjax()) {
            $pageparm            = $this->getPageparm();
            $pageparm['user_id'] = $user['user_id'];
            $res                 = Db::name("user")->strict(false)->update($pageparm);
            if ($res > 0) {
                ajaxmsg('操作成功', 1);
            } else {
                ajaxmsg('操作失败', 0);
            }
        } else {
            $this->assign("info", json_encode($this->user, 320));
            return view();
        }
    }

    public function account()
    {
        $this->assign("info", json_encode($this->user, 320));
        return view();
    }

    #短信调用
    public function sendsms()
    {
        if (input("mobile")) {
            $this->dayuSms(input("mobile"));
        } else {
            $user = $this->user;
            $this->dayuSms($user['mobile']);
        }
    }

    public function checkoperation()
    {
        $pageparm = $this->getPageparm();
        $captcha  = new Captcha();
        if (!$captcha->check($pageparm['captcha'])) {
            ajaxmsg('图形验证码不正确', 0);
        }
        $user  = $this->user;
        $count = Db::name("smscode")->where("mobile='{$user['mobile']}' and smscode='{$pageparm['smscode']}'")->count();
        if (!$count) {
            ajaxmsg('短信验证码不正确', 0);
        } else {
            ajaxmsg('操作成功', 1);
        }
    }

    public function updatepwd()
    {
        $this->assign("info", json_encode($this->user, 320));
        $user = $this->user;
        if (Request::isAjax()) {
            $pageparm = $this->getPageparm();
            $count    = Db::name("smscode")->where("mobile='{$user['mobile']}' and smscode='{$pageparm['smscode']}'")->count();
            if (!$count) {
                ajaxmsg('短信验证码不正确', 0);
            } else {
                Db::name("smscode")->where("mobile='{$user['mobile']}' and smscode='{$pageparm['smscode']}'")->delete();#删除验证码
            }
            if (password_verify($pageparm['oldpwd'], $user['password'])) {
                $pageparm['password'] = password_hash($pageparm['newpwd'], 1);
                $pageparm['user_id']  = $user['user_id'];
                Db::name("user")->strict(false)->update($pageparm);
                ajaxmsg('更新成功', 1);
            } else {
                ajaxmsg('老密码不正确', 0);
            }
        } else {
            return view();
        }
    }

    public function updatemob()
    {
        $this->assign("info", json_encode($this->user, 320));
        $user = $this->user;
        if (Request::isAjax()) {
            $pageparm = $this->getPageparm();
            $count    = Db::name("smscode")->where("mobile='{$pageparm['newmobile']}' and smscode='{$pageparm['smscode']}'")->count();
            if (!$count) {
                ajaxmsg('短信验证码不正确', 0);
            } else {
                Db::name("smscode")->where("mobile='{$pageparm['newmobile']}' and smscode='{$pageparm['smscode']}'")->delete();#删除验证码
            }
            $pageparm['user_id'] = $user['user_id'];
            $pageparm['mobile']  = $pageparm['newmobile'];
            Db::name("user")->strict(false)->update($pageparm);
            ajaxmsg('更新成功', 1);
        } else {
            return view();
        }
    }

    public function updatepay()
    {
        $this->assign("info", json_encode($this->user, 320));
        $user = $this->user;
        if (Request::isAjax()) {
            $pageparm = $this->getPageparm();
            $count    = Db::name("smscode")->where("mobile='{$user['mobile']}' and smscode='{$pageparm['smscode']}'")->count();
            if (!$count) {
                ajaxmsg('短信验证码不正确', 0);
            } else {
                Db::name("smscode")->where("mobile='{$user['mobile']}' and smscode='{$pageparm['smscode']}'")->delete();#删除验证码
            }
            $pageparm['user_id']    = $user['user_id'];
            $pageparm['pay_status'] = 1;
            Db::name("user")->strict(false)->update($pageparm);
            ajaxmsg('更新成功', 1);
        } else {
            return view();
        }
    }

    public function updateicard()
    {
        $this->assign("info", json_encode($this->user, 320));
        $user = $this->user;
        if (Request::isAjax()) {
            $pageparm            = $this->getPageparm();
            $pageparm['user_id'] = $user['user_id'];
            Db::name("user")->strict(false)->update($pageparm);
            ajaxmsg('更新成功', 1);
        } else {
            return view();
        }
    }

    public function address()
    {
        $prefix = config('database.prefix');
        if (Request::isAjax()) {
            $list = Db::name("user_address")->where("user_id={$this->user_id}")->select();
            foreach ($list as $k => $v) {
                $sql                     = "SELECT region_name from {$prefix}region where region_id={$v['province']} OR region_id={$v['city']} OR region_id={$v['district']}";
                $tempArr                 = Db::query($sql);
                $list[$k]['region_name'] = implode(' ', array_column($tempArr, 'region_name'));
                $list[$k]['city_name']   = Db::name("region")->where("region_id={$v['city']}")->value("region_name");
            }
            ajaxmsg('true', 1, $list);
        } else {
            $this->assign("info", json_encode($this->user, 320));
            return view();
        }
    }

    /**
     * 确认订单页面，ajax新增/编辑收货地址
     * @return \think\response\View
     */
    public function ajaxEditAddress()
    {
        $address_id = input("address_id/d", 0);

        if ($this->request->isPost()) {
            $data   = input('post.');
            $result = $this->validate($data, [
                'consignee' => 'require',
                'country'   => 'require',
                'province'  => 'require',
                'city'      => 'require',
                'district'  => 'require',
                'mobile'    => 'require|mobile',
            ], [
                'consignee' => '请您填写收货人姓名',
                'country'   => '请选择所在国家',
                'province'  => '请选择所在省',
                'city'      => '请选择所在市',
                'district'  => '请选择所在区域',
                'mobile'    => '手机号码错误',
            ]);
            if (true !== $result) {
                return json($result, 403);
            }
            $data['user_id'] = $this->user_id;
            if ($address_id > 0) {
                $res = Db::name('user_address')->update($data);
            } else {
                $data['is_default'] = setDefaultAddress($this->user_id);
                $res                = Db::name('user_address')->insertGetId($data);
            }

            $address = Db::name('user_address')->where(['user_id' => $this->user_id])->order('is_default DESC')->select();
            $region  = [];
            if ($address) {
                $region_ids = array_merge(array_column($address, 'province'), array_column($address, 'city'), array_column($address, 'district'));
                $region     = Db::name('region')->whereIn('region_id', $region_ids)->column('region_name', 'region_id');
            }
            return json(['region' => $region, 'address' => $address]);
        }

        $info = Db::name("user_address")->where("user_id={$this->user_id} and address_id={$address_id}")->find();
        if ($info) {
            $info = getRegionText($info);
            if ($info['best_time']) $info['best_time'] = explode(' ', $info['best_time']);
        }
        $this->assign('info', $info);
        return view('ajax/address');
    }

    public function addrupdate()
    {
        $address_id = input("address_id", 0);
        $infoColumn = gettableColumn('user_address');
        $this->assign('infoColumn', json_encode($infoColumn, 320));
        if (Request::isGet()) {
            if ($address_id > 0) {
                $info = Db::name("user_address")->where("user_id={$this->user_id} and address_id={$address_id}")->find();
            } else {
                $info = gettableColumn('user_address');
            }
            $this->assign('info', json_encode($info, 320));
            return view();
        } else {
            $pageparm            = $this->getPageparm();
            $pageparm['user_id'] = $this->user_id;
            if ($pageparm['address_id'] > 0) {
                $res = Db::name('user_address')->update($pageparm);
            } else {
                $data['is_default'] = setDefaultAddress($this->user_id);
                $res                = Db::name('user_address')->insertGetId($pageparm);
            }
            if ($res > 0) {
                ajaxmsg('操作成功', 1);
            } else {
                ajaxmsg('操作失败', 0);
            }
        }
    }

    public function deladdress()
    {
        $pageparm = $this->getPageparm();
        $res      = Db::name("user_address")
            ->where("address_id={$pageparm['address_id']} and user_id={$this->user_id}")
            ->delete();
        if ($res > 0) {
            ajaxmsg('操作成功', 1);
        } else {
            ajaxmsg('操作失败', 0);
        }
    }

    public function addrdefault()
    {
        $pageparm            = $this->getPageparm();
        $pageparm['user_id'] = $this->user_id;
        Db::name('user_address')->where("user_id={$this->user_id}")->update(['is_default' => 0]);
        $res = Db::name('user_address')->strict(false)->update($pageparm);
        if ($res > 0) {
            ajaxmsg('操作成功', 1);
        } else {
            ajaxmsg('操作失败', 0);
        }
    }

    public function regionList()
    {
        $type         = input('type/d', 1);
        $parent_id    = input('parent/d', 0);
        $data         = [];
        $data['type'] = $type + 1;
        if ($type > 1 && $parent_id == 0) {
            $regions = [];
            $content = "<div class='option' data-value='0' data-type='{$data['type']}' data-text='请选择' shoptype='ragionItem'>请选择</div>";
        } else {
            $regions = Db::name("region")
                ->field("region_id,region_name")
                ->where("parent_id={$parent_id}")
                ->select();

            $content = '';
            foreach ($regions as $k => $v) {
                $content .= "<div class='option' data-value='{$v['region_id']}' data-type='{$data['type']}' data-text='{$v['region_name']}' shoptype='ragionItem'>{$v['region_name']}</div>";
            }
        }


        $data['regions'] = $regions;
        $data['content'] = $content;

        echo json_encode($data, 320);
    }

    #我的收藏
    public function favorite()
    {
        if (Request::isAjax()) {
            $map   = [];
            $map[] = ['a.user_id', 'eq', $this->user_id];
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $list  = Db::name("goods_collect")->alias("a")
                ->join("goods c", "c.goods_id=a.goods_id", "LEFT")
                ->field("c.*,a.rec_id")
                ->where($map)
                ->order("a.rec_id desc")
                ->page($page, $rows)
                ->select();
            $total = Db::name("goods_collect")
                ->alias("a")
                ->where($map)
                ->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            return view();
        }
    }

    public function favstore()
    {
        if (Request::isAjax()) {
            $map   = [];
            $map[] = ['a.user_id', 'eq', $this->user_id];
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $list  = Db::name("store_collect")->alias("a")
                ->join("store c", "c.store_id=a.store_id", "LEFT")
                ->field("c.*,a.rec_id")
                ->where($map)
                ->order("a.rec_id desc")
                ->page($page, $rows)
                ->select();
            $total = Db::name("store_collect")
                ->alias("a")
                ->where($map)
                ->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            return view();
        }
    }

    public function feedbkadd()
    {
        $id         = input("id", 0);
        $infoColumn = gettableColumn('feedback');
        $flist      = Db::name("feedback")->alias("a")
            ->join("user b", 'a.user_id=b.user_id', 'LEFT')
            ->field("a.*,b.avatar")
            ->where('a.user_id', '=', $this->user_id)
            ->select();
        $this->assign('flist', json_encode($flist, 320));
        if (Request::isGet()) {
            if ($id > 0) {
                $info = Db::name("feedback")->where("user_id={$this->user_id} and id={$id}")->find();
            } else {
                $info = gettableColumn('feedback');
            }

            $this->assign('info', json_encode($info, 320));
            return view();
        } else {
            $pageparm            = $this->getPageparm();
            $pageparm['user_id'] = $this->user_id;
            $pageparm['addtime'] = time();
            $pageparm['ip']      = Request::ip();
            if ($pageparm['id'] > 0) {
                $res = Db::name('feedback')->update($pageparm);
            } else {
                $res = Db::name('feedback')->insertGetId($pageparm);
            }
            if ($res > 0) {
                ajaxmsg('操作成功', 1);
            } else {
                ajaxmsg('操作失败', 0);
            }
        }
    }

    public function feeddelete()
    {
        $id    = input('id', 0);
        $map[] = ['id', '=', $id];
        $map[] = ['user_id', '=', $this->user_id];
        $res   = Db::name('feedback')->where($map)->delete();
        if ($res > 0) {
            ajaxmsg('操作成功', 1);
        } else {
            ajaxmsg('操作失败', 0);
        }
    }

    public function compare()
    {

        if (Request::isAjax()) {
            $map      = [];
            $pageparm = $this->getPageparm();
            $map[]    = ['a.review_status', 'eq', 2];
            $map[]    = ['a.is_show', 'eq', 1];
            $map[]    = ['a.goods_id', 'in', implode(',', $pageparm)];

            $data     = [];
            $list     = Db::name("goods")->alias("a")
                ->join("brand b", "b.brand_id=a.brand_id", "LEFT")
                ->field("a.*,b.brand_name")
                ->group("a.goods_id")
                ->where($map)
                ->select();
            $pro_type = $list[0]['pro_type'];


            $product_attr = Db::name("product_attr")
                ->field("id,name,type_id")
                ->where('type_id', '=', $pro_type)
                ->order("id desc")
                ->select();
            $goods_map    = Db::name("goods_map")
                ->where('goods_id', 'in', $pageparm)
                ->where('product_arr_id', 'IN', array_column($product_attr, 'id'))
                ->order("product_arr_id desc")
                ->select();
            $ids          = array_column($goods_map, 'product_arr_id');
            $valCount     = array_count_values($ids);

            foreach ($product_attr as $index => $item) {
                if (isset($valCount[$item['id']])) {
                    $product_attr[$index]['goods_map'] = array_slice($goods_map, array_search($item['id'], $ids), $valCount[$item['id']]);
                } else {
                    $product_attr[$index]['goods_map'] = [];
                }
            }
            $data['list']         = $list;
            $data['product_attr'] = $product_attr;
            ajaxmsg('true', 1, $data);
        } else {
            $pageparm = json_decode(input('pageparm'), true);
            $this->assign('goods_ids', json_encode($pageparm, 320));
            return view();
        }

    }

    /**
     * table order
     * order_status：订单状态  0：待确认 1：已确认 2：待评价 3：已取消 4：已完成 5：退款 6：作废
     * pay_status：0：待支付，1：已付款
     * pay_code：  到付：cod，微信支付：weixin，支付宝：alipay
     * shipping_status: 发货状态   0：未发货 1：发货中 2：已发货 3：部分发货，4：已签收
     **/
    public function order()
    {
        $orderModel = new Order;
        if (Request::isAjax()) {
            $map    = [
                ['a.user_id', 'eq', $this->user_id],
                ['a.order_status', 'exp', Db::raw('not in (3,5,6)')]
            ];
            $bttime = trim(input('bttime', 0));
            if ($bttime > 0) {
                $endToday = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
                switch ($bttime) {
                    case 1:
                        $map[] = ['a.add_time', 'between time', [mktime(0, 0, 0, date('m'), date('d'), date('Y')), $endToday]];
                        break;
                    case 3:
                        $map[] = ['a.add_time', 'between time', [mktime(0, 0, 0, date('m'), date('d') - 3, date('Y')), $endToday]];
                        break;
                    case 7:
                        $map[] = ['a.add_time', 'between time', [mktime(0, 0, 0, date('m'), date('d') - 7, date('Y')), $endToday]];
                        break;
                    case 30:
                        $map[] = ['a.add_time', 'between time', [mktime(0, 0, 0, date('m'), date('d') - 30, date('Y')), $endToday]];
                        break;
                }
            }
            $keywords = trim(input('keywords', ''));
            if ($keywords) {
                $map[] = ['a.order_sn', 'like', "%$keywords%"];
            }
            $page         = input('page', 1);
            $rows         = input('rows', 10);
            $order_status = input('order_status', 10000);
            $order_map    = $orderModel->orderStatusWhere($order_status);

            $is_delete = input('is_delete', 0);
            if ($is_delete > 0) {
                $map[] = ['a.is_delete', '>', 0];
            } else {
                $map[] = ['a.is_delete', '=', 0];
            }
            $list      = Db::name("order")->alias("a")
                ->join("store b", "a.store_id=b.store_id", "LEFT")
                ->field("a.*,b.store_id,b.store_name")
                ->where($map)
                ->where($order_map)
                ->order("a.order_id desc")
                ->page($page, $rows)
                ->select();
            $total     = Db::name("order")->alias("a")->where($map)->where($order_map)->count();
            $goodslist = Db::name('order_goods')->alias("a")
                ->join("goods b", "a.goods_id=b.goods_id")
                ->field("a.*,b.goods_name,b.goods_id,b.shop_price,b.original_img")
                ->where('a.order_id', 'IN', array_column($list, 'order_id'))
                ->order("a.order_id desc")
                ->select();
            foreach ($list as $k => $v) {
                $ids                   = array_column($goodslist, 'order_id');
                $valCount              = array_count_values($ids);
                $list[$k]['goodslist'] = array_slice($goodslist, array_search($v['order_id'], $ids), $valCount[$v['order_id']]);
            }

            $data['list']    = $list;
            $data['total']   = $total;
            $data['counter'] = $orderModel->statistics($this->user_id);;
            ajaxmsg('true', 1, $data);
        } else {
            $this->assign('order_status', 'all');
            return view();
        }
    }

    public function orderdetail()
    {
        $order_id = input('order_id', 0);
        $order    = Db::name("order")->alias("a")
            ->join("store b", "a.store_id=b.store_id", "LEFT")
            ->field("a.*,b.store_name")
            ->where("a.order_id={$order_id} and a.user_id={$this->user_id}")
            ->find();
        if (!$order) {
            $this->error('异常操作');
        }

        $data               = [];
        $data['order']      = $order;
        $data['goodslist']  = Db::name('order_goods')->alias("a")
            ->join("goods b", "a.goods_id=b.goods_id", "LEFT")
            ->join("order c", "a.order_id=c.order_id")
            ->field("a.*,b.goods_name,b.goods_id,b.shop_price,b.original_img")
            ->where("a.order_id={$order_id} and c.user_id={$this->user_id}")
            ->order("a.order_id desc")
            ->select();
        $data['user']       = $this->user;
        $data['order_log']  = Db::name("order_log")
            ->where('order_id', $order_id)
            ->order("action_id desc")
            ->select();
        $data['goodsCount'] = Db::name('order_goods')
            ->where('order_id', $order_id)
            ->sum("goods_num");

        $this->assign('data', $data);
        return view();
    }

    #交易纠纷
    public function complaint()
    {
        if (Request::isAjax()) {
            $map    = [];
            $map[]  = ['a.user_id', 'eq', $this->user_id];
            $bttime = trim(input('bttime', 0));
            if ($bttime > 0) {
                $endToday = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
                switch ($bttime) {
                    case 1:
                        $map[] = ['a.add_time', 'between time', [mktime(0, 0, 0, date('m'), date('d'), date('Y')), $endToday]];
                        break;
                    case 3:
                        $map[] = ['a.add_time', 'between time', [mktime(0, 0, 0, date('m'), date('d') - 3, date('Y')), $endToday]];
                        break;
                    case 7:
                        $map[] = ['a.add_time', 'between time', [mktime(0, 0, 0, date('m'), date('d') - 7, date('Y')), $endToday]];
                        break;
                    case 30:
                        $map[] = ['a.add_time', 'between time', [mktime(0, 0, 0, date('m'), date('d') - 30, date('Y')), $endToday]];
                        break;
                }
            }
            $keywords = trim(input('keywords', ''));
            if ($keywords) {
                $map[] = ['a.order_sn', 'like', "%$keywords%"];
            }
            $is_complaint = input('is_complaint', 0);
            $map[]        = ['a.is_complaint', '=', $is_complaint];
            $page         = input('page', 1);
            $rows         = input('rows', 10);
            $oder_status  = input('order_status', 4);
            if ($oder_status < 10000) {
                $map[] = ['a.order_status', '=', $oder_status];
            }
            $is_delete = input('is_delete', 0);
            if ($is_delete > 0) {
                $map[] = ['a.is_delete', '>', 0];
            } else {
                $map[] = ['a.is_delete', '=', 0];
            }
            $list      = Db::name("order")->alias("a")
                ->join("store b", "a.store_id=b.store_id", "LEFT")
                ->field("a.*,b.store_id,b.store_name")
                ->where($map)
                ->order("a.order_id desc")
                ->page($page, $rows)
                ->select();
            $total     = Db::name("order")->alias("a")
                ->join("store b", "a.store_id=b.store_id", "LEFT")
                ->field("a.*,b.store_id,b.store_name")
                ->where($map)
                ->order("a.order_id desc")
                ->count("a.order_id");
            $goodslist = Db::name('order_goods')->alias("a")
                ->join("goods b", "a.goods_id=b.goods_id")
                ->field("a.*,b.goods_name,b.goods_id,b.shop_price,b.original_img")
                ->where('a.order_id', 'IN', array_column($list, 'order_id'))
                ->order("a.order_id desc")
                ->select();
            foreach ($list as $k => $v) {
                $ids                   = array_column($goodslist, 'order_id');
                $valCount              = array_count_values($ids);
                $list[$k]['goodslist'] = array_slice($goodslist, array_search($v['order_id'], $ids), $valCount[$v['order_id']]);
            }
            $data['list']  = $list;
            $data['total'] = $total;
            ajaxmsg('true', 1, $data);
        } else {
            return view();
        }
    }

    public function complaint_form()
    {
        $order_id = input('order_id', 0);
        if (Request::isAjax()) {

            $order = Db::name("order")->alias("a")
                ->join("store b", 'a.store_id=b.store_id', "LEFT")
                ->join("user c", 'a.user_id=c.user_id', "LEFT")
                ->where([
                    'a.order_id'     => $order_id,
                    'a.user_id'      => $this->user_id,
                    'a.order_status' => 4,
                ])
                ->field("a.*,b.store_name,c.user_name")
                ->find();
            if (!$order) {
                ajaxmsg('订单不存在', 0);
            }
            $complaint = Db::name("complaint")
                ->where([
                    'order_id' => $order_id,
                    'user_id'  => $this->user_id,
                ])
                ->find();
            if (!$complaint) {
                $complaintData                      = [];
                $complaintData['order_id']          = $order_id;
                $complaintData['order_sn']          = $order['order_sn'];
                $complaintData['user_name']         = $order['user_name'];
                $complaintData['store_name']        = $order['store_name'];
                $complaintData['user_id']           = $this->user_id;
                $complaintData['complaint_content'] = input('content');
                $complaintData['imgs']              = input('imgs');
                $complaintData['create_time']       = time();
                $complaint_id                       = Db::name("complaint")->strict(false)->insertGetId($complaintData);
                Db::name("order")->where(['order_id' => $order_id, 'user_id' => $this->user_id])->update(['is_complaint' => 1]);
                if ($complaint_id > 0) {
                    ajaxmsg('申请成功', 1);
                } else {
                    ajaxmsg('申请失败', 0);
                }

            } else {
                ajaxmsg('重复提交', 0);
            }


        } else {
            $goods_id  = input('goods_id', 0);
            $order     = Db::name("order")->alias("a")
                ->join("store b", "a.store_id=b.store_id", "LEFT")
                ->field("a.*,b.store_name")
                ->where("a.order_id={$order_id} and a.user_id={$this->user_id} and order_status=4")
                ->find();
            $complaint = Db::name("complaint")
                ->where([
                    'order_id' => $order_id,
                    'user_id'  => $this->user_id,
                ])
                ->find();
            $this->assign('order', $order);
            $this->assign('order_id', $order_id);
            $this->assign('goods_id', $goods_id);
            $this->assign('complaint', $complaint);
            return view('ajax/complaint_form');
        }
    }


    public function commented()
    {
        if (Request::isPost()) {
            $comment                = json_decode(input('post.rank'), true);
            $comment['user_id']     = $this->user_id;
            $comment['ip_address']  = Request::ip();
            $comment['create_time'] = time();
            $model                  = new OrderComment;
            $res                    = $model->addComment($comment);
            ajaxmsg($res['msg'], $res['status']);
        } else {
            $order_id = input('order_id', 0);
            $order    = Db::name("order")->alias("a")
                ->join("store b", "a.store_id=b.store_id", "LEFT")
                ->join("order_comment c", "a.order_id=c.order_id", "LEFT")
                ->field("a.*,b.store_name,c.order_commemt_id")
                ->where("a.order_id={$order_id} and a.user_id={$this->user_id}")
                ->find();
            if (!$order) {
                $this->error('异常操作');
            }

            $data              = [];
            $data['order']     = $order;
            $data['goodslist'] = Db::name('order_goods')->alias("a")
                ->join("goods b", "a.goods_id=b.goods_id", "LEFT")
                ->join("comment c", "a.rec_id=c.rec_id", 'LEFT')
                ->field("a.*,b.shop_price,b.original_img,c.comment_id")
                ->where('a.order_id', $order_id)
                ->order('a.rec_id desc')
                ->select();
            if ($order['store_id']) {
                $store = Db::name("store")->where('store_id', $order['store_id'])->find();
            } else {
                //自营店铺
                $siteInfo = cache('siteInfo');
                $store    = [
                    'logo'                 => $siteInfo['shop_logo'],
                    'store_name'           => $siteInfo['shop_name'],
                    'store_average_score'  => '5.00',
                    'store_desc_score'     => '5.00',
                    'store_service_score'  => '5.00',
                    'store_delivery_score' => '5.00',
                ];
            }

            $this->assign('store', $store);
            $this->assign('info', $data);
            return view();
        }
    }

    public function comments_form()
    {
        if (Request::isAjax()) {
            $comment = input('post.');
            //TODO 验证数据
            $order_goods = Db::name('order_goods')->where('rec_id', $comment['rec_id'])->find();

            $comment['order_id']   = $order_goods['order_id'];
            $comment['store_id']   = $order_goods['store_id'];
            $comment['goods_id']   = $order_goods['goods_id'];
            $comment['spec']       = $order_goods['tempvalue'];
            $comment['user_id']    = $this->user_id;
            $comment['ip_address'] = Request::ip();

            $res = (new Comment)->addComment($comment);
            ajaxmsg($res['msg'], $res['status']);
        } else {
            return view('ajax/comments_form');
        }
    }

    public function comment_list()
    {
        if ($this->request->isPost()) {
            $page     = input('page/d', 1);
            $pageSize = input('rows/d', 10);
            $status   = input('status/d', 0);

            $commentModel = new Comment;
            if ($status == 0) {
                return json($commentModel->waitCommentByUser($this->user_id, $page, $pageSize));
            } else {
                return json($commentModel->getListByUser($this->user_id, $page, $pageSize));
            }

        }
        return view();
    }

    public function showComment()
    {
        $rec_id  = input('rec_id/d');
        $comment = (new Comment)->where('rec_id', $rec_id)->findOrEmpty();
        if (!$comment) return json('评论内容不存在.');//兼容错误
        return view('ajax/show_comment', ['comment' => $comment]);
    }

    public function orderpay()
    {
        $map   = [];
        $map[] = ['user_id', '=', $this->user_id];
        $map[] = ['order_id', '=', input("order_id")];
        $map[] = ['pay_status', '=', 0];
        $map[] = ['order_status', '<', 2];
        $order = Db::name("order")->where($map)->find();
        if ($order) {
            $master_order_sn = get_order_sn();
            Db::name("order")->where($map)->update([
                'master_order_sn' => $master_order_sn,
            ]);
            ajaxmsg('true', 1);
        } else {
            ajaxmsg('数据异常', 0);
        }
    }

    public function orderupdate()
    {
        $map = [
            'user_id'         => $this->user_id,
            'order_id'        => input("order_id/d"),
            'shipping_status' => 2
        ];

        $order = Db::name("order")->where($map)->find();
        if ($order) {
            $time = time();
            Db::name("order")->where($map)->update([
                'confirm_time' => $time,
                'order_status' => 2
            ]);
            Db::name("delivery_order")->where('order_id', $order['order_id'])->update(['status' => 3]);
            orderLog($order, '买家确认收货', '', $this->user_id, $this->user['user_name'], 2);
            #更新所有订单对应的发货单为已完成状态
            ajaxmsg('true', 1);
        } else {
            ajaxmsg('数据异常', 0);
        }
    }

    #优惠券
    public function coupon()
    {
        $map   = [];
        $map[] = ['a.user_id', '=', $this->user_id];
        $list  = Db::name("coupon_list")->alias("a")
            ->join("coupon b", "a.coupon_id=b.coupon_id", "LEFT")
            ->field("a.*,b.*")
            ->where($map)
            ->group("a.coupon_id")
            ->select();
        $alist = [];
        $blist = [];
        $clist = [];
        $store = [];
        if ($list) {
            $store = Db::name('store')->whereIn('store_id', array_column($list, 'store_id'))->column('store_name', 'store_id');
            foreach ($list as $k => $v) {
                if ($v['used_time'] == 0 && $v['use_end_time'] > time()) {
                    $alist[] = $v;
                };
                if ($v['used_time'] > 0) {
                    $blist[] = $v;
                };
                if ($v['use_end_time'] < time()) {
                    $clist[] = $v;
                };
            }
        }

        $this->assign('store', $store);
        $this->assign('alist', $alist);
        $this->assign('blist', $blist);
        $this->assign('clist', $clist);
        return view();
    }

    public function package()
    {
        return view();
    }

    public function invoice()
    {
        if (Request::isPost()) {
            $pageparm = input("post.");
            if ($pageparm['act'] == 'update') {
                $res = Db::name("user_vat_invoice")->where("user_id={$this->user_id}")->strict(false)->update($pageparm);
            } else {
                $pageparm['user_id'] = $this->user_id;
                $res                 = Db::name("user_vat_invoice")->strict(false)->insertGetId($pageparm);
            }
            if ($res > 0) {
                $this->redirect('/home/member/invoice');
            } else {
                $this->error('数据异常');
            }
        } else {
            $info = Db::name("user_vat_invoice")->where("user_id={$this->user_id}")->find();
            if (!$info) {
                $info       = gettableColumn('user_vat_invoice');
                $info['id'] = 0;
            }
            $info['detailaddress'] = getAddressWithOder($info);
            $this->assign('info', $info);
            return view();
        }

    }

    public function uploadToken()
    {
        //return json(uploadtoken());//七牛上传
        $path = input('path', 'temp');
        return json(ossUploadToken($path));//阿里上传
    }

    #礼品卡
    public function giftcard()
    {
        if (Request::isAjax()) {
            $bound = input('bound');
            $page  = input('page', 1);
            $rows  = input('rows', 10);
            $map   = ['user_id' => $this->user_id];
            if ($bound == 'false') {
                $map = ['buyid' => $this->user_id];
            }
            $list  = Db::name("giftcard")
                ->where($map)
                ->order("id desc")
                ->page($page, $rows)
                ->select();
            $total = Db::name("giftcard")
                ->where($map)
                ->count();
            ajaxmsg('true', 1, ['list' => $list, 'total' => $total]);
        } else {
            return view();
        }
    }

    public function bondCard()
    {
        $id        = input('id/d');
        $password  = input('password');
        $cardModel = new Giftcard();
        if ($id) {
            $card = $cardModel->where('buyid', $this->user_id)->where('id', $id)->find();
        } else if ($password) {
            $card = $cardModel->where('password', $password)->find();
        } else {
            return json('参数错误', 403);
        }
        $res = $cardModel->bonding($card, $this->user_id);
        if ($res === false) {
            return json($cardModel->getError(), 403);
        }
        return json($res);
    }
}
