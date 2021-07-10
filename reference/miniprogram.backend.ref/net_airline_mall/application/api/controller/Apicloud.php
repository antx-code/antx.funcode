<?php
/**
 * Created by PhpStorm.
 * User: 835173372@qq.com
 * NickName: 老孟编程
 * Date: 2019/8/8 0008 13:50
 * APP: 全部接口
 */


namespace app\api\controller;

use Alidayu\SignatureHelper;
use app\common\model\OrderReturn;
use app\common\model\ReturnLog;
use app\home\model\AccountLog;
use app\home\model\Cart as CartModel;
use app\home\model\Giftcard;
use app\home\model\GroupGoods;
use app\home\model\Order as OrderModel;
use app\home\model\Order;
use app\home\model\OrderComment;
use app\home\model\Comment;
use think\captcha\Captcha;
use think\Controller;
use think\Exception;
use think\Db;
use think\facade\Log;
use think\facade\Request;
use think\facade\Validate;
use app\home\model\Goods as GoodsModel;
use Payment\Pay;
use Payment\Notify;
use think\cache\driver\Redis;
use EasyWeChat\Factory;

class Apicloud extends Controller
{
    #API约束秘钥
    public $appkey = 'b132212558baf7db66671d8cc490f629';

    protected $pay_code;
    protected $pay_method;
    protected $pay_name;
    protected $pay;
    protected $conf;
    protected $user_id;

    public $cacheconfig = [
        'type'   => 'redis',
        'host'   => '127.0.0.1',
        'port'   => '6379',
        //'password' => 'Redis@2016',
        'select' => 0,
    ];
    static $redis = '';

    public function msectime()
    {
        list($msec, $sec) = explode(' ', microtime());
        $msectime = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);

        return $msectime;
    }

    public function send_tcp_message($host, $port, $message)
    {
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_connect($socket, $host, $port);
        $num    = 0;
        $length = strlen($message);
        do {
            $buffer = substr($message, $num);
            $ret    = @socket_write($socket, $buffer);
            $num    += $ret;
        } while ($num < $length);

        $ret = '';
        do {
            $buffer = @socket_read($socket, 1024, PHP_BINARY_READ);
            $ret    .= $buffer;
        } while (strlen($buffer) == 1024);

        socket_close($socket);
        return $ret;
    }

    public function receive_tcp_message($host, $port, $callback)
    {
        set_time_limit(0);
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_bind($socket, $host, $port);
        socket_listen($socket, 5);
        while ($child = @socket_accept($socket)) {
            if (false === socket_getpeername($child, $remote_host, $remote_port)) {
                @socket_close($child);
                continue;
            }
            // 读取请求数据
            // 例如是 http 报文, 则解析 http 报文
            $request = '';
            do {
                $buffer = @socket_read($child, 1024, PHP_BINARY_READ);
                if (false === $buffer) {
                    @socket_close($child);
                    continue 2;
                }
                $request .= $buffer;
            } while (strlen($buffer) == 1024);

            // 此处省略如何调用 $callback
            $response = $callback($remote_host, $remote_port, $request);

            if (!strlen($response)) {
                $response = ' ';
            }

            // 因为是 TCP 链接, 需要返回给客户端处理数据
            $num    = 0;
            $length = strlen($response);
            do {
                $buffer = substr($response, $num);
                $ret    = @socket_write($child, $buffer);
                $num    += $ret;
            } while ($num < $length);

            // 关闭 socket 资源, 继续循环
            @socket_close($child);
        }
    }

    public function tcptry()
    {
        set_time_limit(0);
        $host   = '127.0.0.1';
        $port   = 9504;
        $buffer = "HELLO TCP";

        if (($socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
            echo "socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "\n";
        } else {
            //socket_set_nonblock($socket);
        }

        if (socket_connect($socket, $host, $port) === false) { // 创建连接
            echo 'socket connect error，error code is ' . socket_last_error();
            exit;
        }

        if (socket_write($socket, $buffer) === false) { // 发包
            $message = sprintf("write socket error:%s", socket_strerror(socket_last_error()));
            echo $message . PHP_EOL;
            exit;
        }
        $buf = '';
        while ($flag = socket_recv($socket, $buf, 2048, MSG_WAITALL)) {
            echo $flag . PHP_EOL;
            sleep(1);
        }
        //echo $flag.PHP_EOL;

        echo $buf;
        socket_close($socket);
        exit;
    }

    public function tcpswoole()
    {
        set_time_limit(0);

        $host   = '127.0.0.1';
        $port   = 9504;
        $buffer = "HELLO TCP";
        $client = new \Swoole\Client(SWOOLE_SOCK_TCP);
        $client->set([
            'open_eof_check' => true,
            'package_eof'    => "\r\n\r\n",
        ]);
        if (!$client->connect($host, $port, -1)) {
            exit("connect failed. Error: {$client->errCode}\n");
        }
        $client->send($buffer);
        $result = $client->recv();
        echo $result . PHP_EOL . PHP_EOL;
        $client->close();
    }

    public function initialize()
    {
        header('content-type:text/html;charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
        header('Access-Control-Allow-Methods: GET, POST, PUT');
        self::$redis = new Redis($this->cacheconfig);
    }

    public function venue()
    {
        $id   = input('venue_id/d');
        $time = time();
        $map  = [
            ['start_time', '<', $time],
            ['end_time', '>', $time],
            ['status', '=', 1],
            ['venue_id', '=', $id]
        ];
        $data = Db::name('venue')->where($map)->find();
        if (!$data) {
            ajaxmsg("限时会场已关闭", 0);
        }
        $data['goods'] = Db::name('goods a')
            ->join('venue_goods b', 'a.goods_id=b.goods_id')
            ->where('b.venue_id', $id)
            ->field('b.*,a.shop_price,a.goods_number')
            ->order('b.sort ASC')
            ->select();

        ajaxmsg("true", 1, $data);
    }

    public function prom()
    {
        $prom_id = input('prom_id/d');
        $time    = time();
        $map     = [
            ['start_time', '<', $time],
            ['end_time', '>', $time],
            ['status', '=', 1],
            ['prom_id', '=', $prom_id]
        ];
        $data    = Db::name('pop_prom')->where($map)->find();
        if (!$data) {
            ajaxmsg("活动已结束", 0);
        }
        $token   = input('token');
        $user_id = Db::name("user")->where('token', $token)->value("user_id");
        if ($user_id) {
            $data['prom_record'] = Db::name('prom_record')->where(['prom_id' => $prom_id, 'user_id' => $user_id])->find();
        }

        ajaxmsg("true", 1, $data);
    }

    public function makePromOrder()
    {
        $user_id = $this->islogin();
        $prom_id = input('prom_id/d');

        $time = time();
        $map  = [
            ['start_time', '<', $time],
            ['end_time', '>', $time],
            ['status', '=', 1],
            ['prom_id', '=', $prom_id]
        ];
        $prom = Db::name('pop_prom')->where($map)->find();
        if (!$prom) {
            ajaxmsg("活动已过期", 0);
        }

        $record = Db::name('prom_record')->where(['user_id' => $user_id, 'prom_id' => $prom_id])->find();
        if ($record) {
            if ($record['write_off'] > 0 && $record['status'] == 3) ajaxmsg("您已参与过本次活动啦", 0);
            else if ($record['pay_time'] > 0) ajaxmsg("您已支付成功，请领取奖励", -2);
            else ajaxmsg("true", 1, $record);
        }
        $order_sn = get_order_sn('prom_record');

        $row           = [
            'order_sn' => 'P' . substr($order_sn, 0, 17),
            'user_id'  => $user_id,
            'prom_id'  => $prom_id,
            'add_time' => $time,
            'status'   => 1,
            'amount'   => $prom['price'],
        ];
        $row['rec_id'] = Db::name('prom_record')->insertGetId($row);
        ajaxmsg("true", 1, $row);
    }

    public function promOrder()
    {
        $user_id  = $this->islogin();
        $order_sn = input("order_sn");
        $map      = [
            'user_id'  => $user_id,
            'order_sn' => $order_sn
        ];

        $prom_record                 = Db::name("prom_record")->where($map)->find();
        $prom_record['order_amount'] = $prom_record['amount'];
        $prom_record['total_amount'] = $prom_record['amount'];
        $prom_record['user']         = Db::name("user")->where('user_id', $user_id)->field('passwrod', true)->find();
        ajaxmsg('true', 1, $prom_record);
    }

    public function promOrderList()
    {
        $user_id = $this->islogin();
        $list    = Db::name("prom_record a")
            ->join('pop_prom b', 'a.prom_id=b.prom_id')
            ->where('a.user_id', $user_id)
            ->field('a.*,b.title,b.type,b.price,b.start_time,b.end_time')
            ->select();

        ajaxmsg('true', 1, $list);
    }

    public function promOrderDetail()
    {
        $user_id     = $this->islogin();
        $rec_id      = input('rec_id/d');
        $map         = [
            'a.user_id' => $user_id,
            'a.rec_id'  => $rec_id
        ];
        $prom_record = Db::name("prom_record a")
            ->join('pop_prom b', 'a.prom_id=b.prom_id')
            ->where($map)
            ->field('a.*,b.title,b.type,b.desc,b.btn_style,b.btn_text')
            ->find();
        ajaxmsg('true', 1, $prom_record);
    }

    public function queryPromWriteOff()
    {
        $rec_id = input('rec_id/d');
        $record = Db::name("prom_record")->where('rec_id', $rec_id)->find();
        if ($record && $record['status'] == 3) {
            ajaxmsg('true', 2);
        }
        ajaxmsg('true', 1);
    }

    public function writeOffProm()
    {
        $token    = input('token');
        $order_sn = input('order_sn');
        $user     = Db::name("user")->where('token', $token)->find();
        if (!$user || $user['is_special'] != 1) {
            ajaxmsg('您没有核销优惠活动的权限,请联系工作人员', -1);
        }
        $prom_record = Db::name('prom_record')->where('order_sn', $order_sn)->find();
        if (!$prom_record) {
            ajaxmsg('无效二维码', -1);
        }
        if ($prom_record['status'] != 2) {
            ajaxmsg('活动未完成', -1);
        }
        $res = Db::name('prom_record')->where('order_sn', $order_sn)->update(['write_off' => time(), 'operater' => $user['user_id'], 'status' => 3]);
        if ($res) {
            ajaxmsg('成功，请兑现活动优惠', 1);
        } else {
            ajaxmsg('核销失败,请重试', -1);
        }
    }

    public function indexapp()
    {
        $data              = [];
        $ad_code           = input("ad_code", 'index_ad');
        $data['adlist']    = Db::name("ads")
            ->where("ad_code", "=", $ad_code)
            ->where("enabled", "=", 1)
            ->select();
        $data['floor_adv'] = Db::name("ads")
            ->where("ad_code", 'wap_floor_adv')
            ->where("enabled", 1)
            ->column('*', 'ad_code');
        //会场专区
        $time             = time();
        $map              = [
            ['start_time', '<', $time],
            ['end_time', '>', $time],
            ['status', '=', 1],
        ];
        $data['venue']    = Db::name('venue')->where($map)->find();
        $data['pop_prom'] = Db::name('pop_prom')->where($map)->find();

        $sklist              = Db::name("seckill")
            ->field("*,FROM_UNIXTIME(start_time,'%H:%i') as start_time_temp,
                    FROM_UNIXTIME(start_time,'%Y-%m-%d %H:%i:%S') as start_time_cn,
                    FROM_UNIXTIME(end_time,'%Y-%m-%d %H:%i:%S') as end_time_cn")
            ->where("start_time", "<", time())
            ->where("end_time", ">", time())
            ->order("sec_id asc")
            ->select();
        $gmap                = [];
        $gmap[]              = ['a.sec_id', 'IN', array_column($sklist, 'sec_id')];
        $data['sklist']      = $sklist;
        $data['seckillList'] = Db::name('seckill_goods')->alias("a")
            ->join("goods b", "a.goods_id=b.goods_id", "LEFT")
            ->field("a.*,convert(a.sold_num/a.sec_num,decimal(15,2))*100 as saleper, 
                     b.goods_name,b.shop_price,b.cat_id,b.store_id,b.goods_sn,b.brand_id,b.keywords,b.original_img")
            ->where($gmap)
            ->order('a.sec_id asc')
            ->select();


        $field2 = 'a.goods_id,a.goods_name,a.cat_id,a.store_id,a.brand_id,a.shop_price,a.market_price,
                   a.original_img,a.total_sale_num,a.virtual_sale_num,a.comment_num,a.store_id,a.is_new,a.is_hot,a.is_best,
                   b.product_id';
        $gmap   = [];

        $gmap[] = ['a.is_on_sale', '=', 1];
        $gmap[] = ['a.is_show', '=', 1];
        $gmap[] = ['a.review_status', '=', 2];
        $gmap[] = ['a.is_sui', '=', 1];
        $gmap[] = ['a.is_delete', '=', 0];

        $data['goodsList'] = Db::name("goods")->alias("a")
            ->leftJoin("products b", "a.goods_id=b.goods_id")
            ->field($field2)
            ->where($gmap)
            ->order("a.goods_id asc")
            ->group("a.goods_id")
            ->limit(20)
            ->select();

        $storemap          = [
            ['is_delete', '=', 0],
            ['status', '=', 1],
            ['store_close', '=', 1]
        ];
        $data['storelist'] = Db::name("store")->where($storemap)->limit(6)->select();


        $map                 = [];
        $map[]               = ['b.is_delete', 'eq', 0];
        $map[]               = ['b.is_on_sale', 'eq', 1];
        $map[]               = ['a.status', 'eq', 1];
        $data['paimaigoods'] = Db::name("paimai_goods")->alias("a")
            ->leftJoin("goods b", "b.goods_id=a.goods_id")
            ->field("b.goods_name,b.shop_price,b.cat_id,b.store_id,b.goods_sn,b.brand_id,b.keywords,b.original_img,a.*")
            ->where($map)
            ->order("a.id desc")
            ->limit(20)
            ->select();

        $catelist = Db::name("category")
            ->where("parent_id", "=", 0)
            ->where("is_delete", "=", 0)
            ->where("is_show", "=", 1)
            ->order("sort_order asc")
            ->select();
        $field    = 'a.goods_id,a.goods_name,a.cat_id,a.store_id,a.brand_id,a.shop_price,a.market_price,
                   a.original_img,a.total_sale_num,a.comment_num,a.store_id,a.is_new,a.is_hot,a.is_best';
        foreach ($catelist as $k => $v) {
            $catelist[$k]['goodslist'] = Db::name("goods")->alias("a")
                ->where($gmap)
                ->where("a.is_ad", "=", 1)
                ->where("a.cat_id", "in", getChildrenData($v['cat_id'], 1))
                ->field($field)
                ->limit(6)
                ->select();
        }
        $data['catelist'] = $catelist;
        $data['areaList'] = config('areaList');

        ajaxmsg("true", 1, $data);
    }

    public function xiaxiang()
    {
        $data                = [];
        $ad_code             = input("ad_code", 'wapIndex');
        $data['adlist']      = Db::name("ads")
            ->where("ad_code", "=", $ad_code)
            ->where("enabled", "=", 1)
            ->select();
        $sklist              = Db::name("seckill")
            ->field("*,FROM_UNIXTIME(start_time,'%H:%i') as start_time_temp,
                    FROM_UNIXTIME(start_time,'%Y-%m-%d %H:%i:%S') as start_time_cn,
                    FROM_UNIXTIME(end_time,'%Y-%m-%d %H:%i:%S') as end_time_cn")
            ->where("start_time", "<", time())
            ->where("end_time", ">", time())
            ->order("sec_id asc")
            ->select();
        $gmap                = [];
        $gmap[]              = ['a.sec_id', 'IN', array_column($sklist, 'sec_id')];
        $data['sklist']      = $sklist;
        $data['seckillList'] = Db::name('seckill_goods')->alias("a")
            ->join("goods b", "a.goods_id=b.goods_id", "LEFT")
            ->field("a.*,convert(a.sold_num/a.sec_num,decimal(15,2))*100 as saleper, 
                     b.goods_name,b.shop_price,b.cat_id,b.store_id,b.goods_sn,b.brand_id,b.keywords,b.original_img")
            ->where($gmap)
            ->order('a.sec_id asc')
            ->select();


        $field2 = 'a.goods_id,a.goods_name,a.cat_id,a.store_id,a.brand_id,a.shop_price,a.market_price,
                   a.original_img,a.total_sale_num,a.comment_num,a.store_id,a.is_new,a.is_hot,a.is_best,
                   b.product_id';
        $gmap   = [];

        $gmap[] = ['a.is_on_sale', '=', 1];
        $gmap[] = ['a.is_show', '=', 1];
        $gmap[] = ['a.review_status', '=', 2];
        $gmap[] = ['a.is_sui', '=', 1];
        $gmap[] = ['a.is_delete', '=', 0];
        #$str                 = getChildrenData(47045, 1);
        $str                 = getChildrenData(46547, 1);
        $str                 .= ',' . getChildrenData(46366, 1);
        $str                 .= ',' . getChildrenData(46612, 1);
        $str                 .= ',' . getChildrenData(46385, 1);
        $str                 .= ',' . getChildrenData(46254, 1);
        $data['goodsList']   = Db::name("goods")->alias("a")
            ->leftJoin("products b", "a.goods_id=b.goods_id")
            ->field($field2)
            ->where($gmap)
            ->where("a.original_img != ''")
            ->where("a.cat_id in ({$str})")
            ->order("a.goods_id desc")
            ->group("a.goods_id")
            ->limit(50)
            ->select();
        $data['storelist']   = Db::name("store")->limit(6)->select();
        $map                 = [];
        $map[]               = ['b.is_delete', 'eq', 0];
        $map[]               = ['b.is_on_sale', 'eq', 1];
        $map[]               = ['a.status', 'eq', 1];
        $data['paimaigoods'] = Db::name("paimai_goods")->alias("a")
            ->leftJoin("goods b", "b.goods_id=a.goods_id")
            ->field("b.goods_name,b.shop_price,b.cat_id,b.store_id,b.goods_sn,b.brand_id,b.keywords,b.original_img,a.*")
            ->where($map)
            ->order("a.id desc")
            ->limit(20)
            ->select();

        $data['catelist'] = Db::name("category")
            ->where("cat_id in (46547,46366,46612,46385,46254)")
            ->order("sort_order desc")
            ->group("cat_id")
            ->limit(5)
            ->select();
        ajaxmsg("true", 1, $data);
    }

    public function jincheng()
    {
        $data                = [];
        $ad_code             = input("ad_code", 'wapIndex');
        $data['adlist']      = Db::name("ads")
            ->where("ad_code", "=", $ad_code)
            ->where("enabled", "=", 1)
            ->select();
        $sklist              = Db::name("seckill")
            ->field("*,FROM_UNIXTIME(start_time,'%H:%i') as start_time_temp,
                    FROM_UNIXTIME(start_time,'%Y-%m-%d %H:%i:%S') as start_time_cn,
                    FROM_UNIXTIME(end_time,'%Y-%m-%d %H:%i:%S') as end_time_cn")
            ->where("start_time", "<", time())
            ->where("end_time", ">", time())
            ->order("sec_id asc")
            ->select();
        $gmap                = [];
        $gmap[]              = ['a.sec_id', 'IN', array_column($sklist, 'sec_id')];
        $data['sklist']      = $sklist;
        $data['seckillList'] = Db::name('seckill_goods')->alias("a")
            ->join("goods b", "a.goods_id=b.goods_id", "LEFT")
            ->field("a.*,convert(a.sold_num/a.sec_num,decimal(15,2))*100 as saleper, 
                     b.goods_name,b.shop_price,b.cat_id,b.store_id,b.goods_sn,b.brand_id,b.keywords,b.original_img")
            ->where($gmap)
            ->order('a.sec_id asc')
            ->select();


        $field2 = 'a.goods_id,a.goods_name,a.cat_id,a.store_id,a.brand_id,a.shop_price,a.market_price,
                   a.original_img,a.total_sale_num,a.comment_num,a.store_id,a.is_new,a.is_hot,a.is_best,
                   b.product_id';
        $gmap   = [];

        $gmap[]            = ['a.is_on_sale', '=', 1];
        $gmap[]            = ['a.is_show', '=', 1];
        $gmap[]            = ['a.review_status', '=', 2];
        $str               = getChildrenData(46624, 1);
        $data['goodsList'] = Db::name("goods")->alias("a")
            ->leftJoin("products b", "a.goods_id=b.goods_id")
            ->field($field2)
            ->where($gmap)
            ->where("a.original_img != ''")
            ->where("a.cat_id in ({$str})")
            ->order("a.goods_id asc")
            ->group("a.goods_id")
            ->limit(50)
            ->select();

        $data['storelist'] = Db::name("store")->limit(6)->select();


        $map                 = [];
        $map[]               = ['b.is_delete', 'eq', 0];
        $map[]               = ['b.is_on_sale', 'eq', 1];
        $map[]               = ['a.status', 'eq', 1];
        $data['paimaigoods'] = Db::name("paimai_goods")->alias("a")
            ->leftJoin("goods b", "b.goods_id=a.goods_id")
            ->field("b.goods_name,b.shop_price,b.cat_id,b.store_id,b.goods_sn,b.brand_id,b.keywords,b.original_img,a.*")
            ->where($map)
            ->order("a.id desc")
            ->limit(20)
            ->select();

        $catelist = Db::name("category")
            ->where("parent_id", "=", 0)
            ->where("is_delete", "=", 0)
            ->where("is_show", "=", 1)
            ->order("sort_order desc")
            ->select();
        $field    = 'a.goods_id,a.goods_name,a.cat_id,a.store_id,a.brand_id,a.shop_price,a.market_price,
                   a.original_img,a.total_sale_num,a.comment_num,a.store_id,a.is_new,a.is_hot,a.is_best';
        foreach ($catelist as $k => $v) {
            $catelist[$k]['goodslist'] = Db::name("goods")->alias("a")
                ->where($gmap)
                ->where("a.is_ad", "=", 1)
                ->where("a.cat_id", "=", $v['cat_id'])
                ->field($field)
                ->limit(10)
                ->select();
        }
        $data['catelist'] = $catelist;
        ajaxmsg("true", 1, $data);
    }

    public function uinfo()
    {
        $token = input('token');
        if (!$token) {
            ajaxmsg('无权限', -1);
        }
        $map   = [];
        $map[] = ['token', '=', $token];
        $user  = Db::name("user")->where($map)->find();
        return $user;
    }

    public function islogin($all = false)
    {
        $token = input('token');
        if (!$token) {
            ajaxmsg('无权限', 401);
        }

        $user = Db::name("user")->where('token', $token)->find();
        if ($user) {
            if ($user['is_validated'] > 0) {
                ajaxmsg('未登录', 401);
            }
            if ($all) {
                return $user;
            }
            return $user['user_id'];
        } else {
            ajaxmsg('未登录', 401);
        }
    }

    public function getPageparm()
    {
        $pageparm = json_decode($_REQUEST['pageparm'], true);
        if (!is_array($pageparm)) return [];
        foreach ($pageparm as $k => $v) {
            if (is_array($v)) {
                $pageparm[$k] = json_encode($v);
            }
        }
        return $pageparm;
    }

    public function getconfig()
    {
        $list = Db::name("shop_config")->column('value', "code");
        ajaxmsg("true", 1, $list);
    }

    public function dayuSms()
    {
        $mobile = input("mobile");
        $sign   = input("sign");
        if (!Validate::isMobile($mobile)) {
            ajaxmsg('手机号格式不对', 0);
        }
        if (md5($mobile . $this->appkey) != $sign) {
            ajaxmsg("非法操作", 0);
        }
        $smstime = Db::name('smscode')->where("mobile", "=", $mobile)->value("smstime");

        if (time() - $smstime < 60) {
            ajaxmsg("一分钟后在申请发送短信", 0);
        }

        $code = mt_rand(1111, 9999);
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

    public function register()
    {

        $shop_reg_closed = Db::name("shop_config")->where("code='shop_reg_closed'")->value('value');
        if ($shop_reg_closed == 1) {
            ajaxmsg("数据维护中，暂停注册！！！", 0);
        }
        $userData = [];
        $pageparm = input("request.");

        #图片验证码验证
        $captcha     = new Captcha();
        $reg_captcha = Db::name("shop_config")->where("code='reg_captcha'")->value('value');
        if ($reg_captcha == 1) {
            if (!$captcha->check(input('captcha'))) {
                ajaxmsg("验证码不正确", 0);
            }
        }
        $user = Db::name("user")->where("user_name='{$pageparm['username']}'")->select();
        if ($user) {
            ajaxmsg("用户名已经存在,请重新输入", 0);
        }
        $user = Db::name("user")->where("mobile='{$pageparm['mobile_phone']}'")->select();
        if ($user) {
            ajaxmsg("手机已经存在,请重新输入", 0);
        }

        #短信验证码验证
        $sms_signin = Db::name("shop_config")->where("code='sms_signin'")->value('value');
        if ($sms_signin == 1) {
            if ($pageparm['mobile_code']) {
                $sid = Db::name("smscode")
                    ->where('mobile', '=', $pageparm['mobile_phone'])
                    ->where('smscode', '=', $pageparm['mobile_code'])
                    ->value("id");
                if ($sid == 0) {
                    ajaxmsg("短信验证码不正确,请重新输入", 0);
                } else {
                    Db::name("smscode")->where("id={$sid}")->delete();
                }
            } else {
                ajaxmsg("短信验证码不正确,请重新输入", 0);
            }
        }
        $email = input('email');
        if ($email) {
            $user = Db::name("user")
                ->where("email='{$pageparm['email']}'")
                ->select();
            if ($user) {
                ajaxmsg("邮箱已存在,请重新输入", 0);
            }
            $userData['email'] = $pageparm['email'];
        }
        $userData['user_name']  = $pageparm['username'];
        $userData['mobile']     = $pageparm['mobile_phone'];
        $userData['password']   = password_hash($pageparm['password'], 1);
        $userData['reg_time']   = time();
        $userData['last_login'] = time();
        $userData['last_ip']    = Request::ip();


        $token             = create_token();
        $userData['token'] = $token;
        $user_id           = Db::name("user")->insertGetId($userData);
        if ($user_id > 0) {
            $user = Db::name("user")->find($user_id);
            ajaxmsg("注册成功", 1, $user);

        } else {
            ajaxmsg("数据异常", 0);
        }
    }

    public function login()
    {

        $captcha  = new Captcha();
        $pageparm = input("request.");


        #开启验证码
        $login_captcha = Db::name("shop_config")->where("code='login_captcha'")->value('value');
        if ($login_captcha == 1) {
            if (!$captcha->check($pageparm['captcha'])) {
                ajaxmsg('验证码不正确', 0);
            }
        }
        #邮箱登录
        if (filter_var($pageparm['username'], FILTER_VALIDATE_EMAIL)) {
            $user = Db::name("user")
                ->where("email='{$pageparm['username']}'")
                ->find();
            if (!$user) {
                ajaxmsg('邮箱错误', 0);
            }
        }
        #手机号登录
        if (Validate::isMobile($pageparm['username'])) {
            $user = Db::name("user")->where("mobile='{$pageparm['username']}'")->find();
            if (!$user) {
                ajaxmsg('手机号错误', 0);
            }
        } else {
            $user = Db::name("user")->where("user_name='{$pageparm['username']}'")->find();
            if (!$user) {
                ajaxmsg('用户名错误', 0);
            }
        }

        if (password_verify($pageparm['password'], $user['password'])) {
            if (!$user['token']) {
                Db::name("user")->where('user_id', $user['user_id'])->update(['token' => create_token()]);
            }
            $user = Db::name("user")->field('password', true)->find($user['user_id']);
            ajaxmsg('登录成功', 1, $user);
        } else {
            ajaxmsg('密码错误', 0);
        }
    }

    public function smslogin()
    {

        $shop_reg_closed = Db::name("shop_config")->where("code='shop_reg_closed'")->value('value');
        if ($shop_reg_closed == 1) {
            ajaxmsg("数据维护中，暂停注册！！！", 0);
        }
        $userData = [];
        $pageparm = input("request.");

        #图片验证码验证
        $captcha     = new Captcha();
        $reg_captcha = Db::name("shop_config")->where("code='reg_captcha'")->value('value');
        if ($reg_captcha == 1) {
            if (!$captcha->check(input('captcha'))) {
                ajaxmsg("验证码不正确", 0);
            }
        }

        #短信验证码验证
        $sms_signin = Db::name("shop_config")->where("code='sms_signin'")->value('value');
        if ($sms_signin == 1) {
            if ($pageparm['mobile_code']) {
                $sid = Db::name("smscode")
                    ->where('mobile', '=', $pageparm['mobile_phone'])
                    ->where('smscode', '=', $pageparm['mobile_code'])
                    ->value("id");
                if ($sid == 0) {
                    ajaxmsg("短信验证码不正确,请重新输入", 0);
                } else {
                    Db::name("smscode")->where("id={$sid}")->delete();
                }
            } else {
                ajaxmsg("短信验证码不正确,请重新输入", 0);
            }
        }

        $userData['mobile']     = $pageparm['mobile_phone'];
        $userData['last_login'] = time();
        $userData['last_ip']    = Request::ip();
        $userData['token']      = create_token();
        $user                   = Db::name("user")->where("mobile", "=", $pageparm['mobile_phone'])->find();
        if ($user) {
            Db::name("user")->where("mobile", "=", $pageparm['mobile_phone'])->update($userData);
            $user_id = $user['user_id'];
        } else {
            $userData['reg_time'] = time();
            $user_id              = Db::name("user")->insertGetId($userData);
        }
        if ($user_id > 0) {
            $user = Db::name("user")->find($user_id);
            ajaxmsg("登录成功", 1, $user);
        } else {
            ajaxmsg("数据异常", 0);
        }
    }

    public function resetpaypasswd()
    {
        $user_id  = $this->islogin();
        $userData = [];
        $pageparm = input("request.");

        #短信验证码验证
        if ($pageparm['smscode']) {
            $sid = Db::name("smscode")
                ->where('mobile', '=', $pageparm['mobile'])
                ->where('smscode', '=', $pageparm['smscode'])
                ->value("id");
            if ($sid == 0) {
                ajaxmsg("短信验证码不正确,请重新输入", 0);
            } else {
                Db::name("smscode")->where("id={$sid}")->delete();
            }
        } else {
            ajaxmsg("短信验证码不正确,请重新输入", 0);
        }
        $userData['pay_password'] = $pageparm['password'];
        $user                     = Db::name("user")->where("user_id", "=", $user_id)->find();
        if ($user) {
            Db::name("user")->where("user_id", "=", $user_id)->update($userData);
            ajaxmsg("支付密码修改成功", 1);
        } else {
            ajaxmsg("数据异常", 0);
        }
    }

    #验证码
    public function verify($fontSize = 18, $length = 2, $imageW = 112, $imageH = 37)
    {
        $config  = [
            'fontSize' => $fontSize,
            'length'   => $length,
            'imageW'   => $imageW,
            'imageH'   => $imageH,
            'useCurve' => false,
            'useNoise' => false,
        ];
        $captcha = new Captcha($config);
        return $captcha->entry();
    }

    public function getuserinfo()
    {
        $user_id          = $this->islogin();
        $data             = [];
        $data['helpList'] = Db::name("article")->where("cat_id=1000 and is_open=1")->field("article_id,title")->select();
        //$sql                      = "SELECT order_status,count(order_id) as total FROM oc_order where user_id={$user_id} GROUP BY order_status;";
        //$data['order']            = array_column(Db::query($sql), 'total', 'order_status');
        //$sql                      = "SELECT shipping_status,count(order_id) as total FROM oc_order where user_id={$user_id} GROUP BY shipping_status;";
        //$data['shipping']         = array_column(Db::query($sql), 'total', 'shipping_status');
        $data['order_count'] = orderCount($user_id);

        $data['couponsum']        = Db::name("coupon_list")->where("user_id={$user_id}")->sum("list_id");
        $data['giftcardQuantity'] = Db::name("giftcard")
            ->where("user_id={$user_id}")
            ->where("cash", ">", 0)
            ->count("id");
        $data['user']             = Db::name("user")->find($user_id);
        ajaxmsg('true', 1, $data);
    }

    public function profile()
    {
        $user_id             = $this->islogin();
        $pageparm            = $this->getPageparm();
        $pageparm['user_id'] = $user_id;
        if (isset($pageparm['user_name'])) {
            $count = Db::name('user')->where('user_name', $pageparm['user_name'])->where('user_id', '<>', $user_id)->count();
            if ($count) {
                ajaxmsg('用户名重复', 0);
            }
        }

        $res = Db::name("user")->strict(false)->update($pageparm);
        if ($res > 0) {
            ajaxmsg('操作成功', 1);
        } else {
            ajaxmsg('操作失败', 0);
        }
    }

    public function checkUserName()
    {
        $user_id   = $this->islogin();
        $user_name = input('user_name');
        if (!$user_name) ajaxmsg('请输入用户名', 0);
        $count = Db::name('user')->where('user_name', $user_name)->where('user_id', '<>', $user_id)->count();
        if ($count) {
            ajaxmsg('用户名重复', 0);
        } else {
            ajaxmsg('ok', 1);
        }
    }

    public function updatepwd()
    {
        $user_id  = $this->islogin();
        $user     = $this->uinfo();
        $pageparm = $this->getPageparm();
        $count    = Db::name("smscode")->where("mobile='{$user['mobile']}' and smscode='{$pageparm['smscode']}'")->count();
        if (!$count) {
            ajaxmsg('短信验证码不正确', 0);
        } else {
            Db::name("smscode")->where("mobile='{$user['mobile']}' and smscode='{$pageparm['smscode']}'")->delete();#删除验证码
        }
        $pageparm['password'] = password_hash($pageparm['newpwd'], 1);
        $pageparm['user_id']  = $user['user_id'];
        Db::name("user")->strict(false)->update($pageparm);
        ajaxmsg('更新成功', 1);
    }

    public function updatepaypwd()
    {
        $user_id  = $this->islogin();
        $user     = $this->uinfo();
        $pageparm = $this->getPageparm();
        $count    = Db::name("smscode")->where("mobile='{$user['mobile']}' and smscode='{$pageparm['smscode']}'")->count();
        if (!$count) {
            ajaxmsg('短信验证码不正确', 0);
        } else {
            Db::name("smscode")->where("mobile='{$user['mobile']}' and smscode='{$pageparm['smscode']}'")->delete();#删除验证码
        }
        $res = Db::name("user")->where("user_id", "=", $user_id)->update([
            'pay_password' => trim($pageparm['pay_password']),
        ]);

        if ($res == 0) {
            ajaxmsg('更新失败', 0);
        } else {
            ajaxmsg('更新成功', 1);
        }

    }

    public function updatecard()
    {
        $user_id  = $this->islogin();
        $pageparm = $this->getPageparm();
        $res      = Db::name("user")->where("user_id", "=", $user_id)->update($pageparm);
        if ($res == 0) {
            ajaxmsg('更新失败', 0);
        } else {
            ajaxmsg('更新成功', 1);
        }
    }

    public function updatemob()
    {
        $user_id  = $this->islogin();
        $pageparm = $this->getPageparm();
        $count    = Db::name("smscode")
            ->where("mobile", "=", $pageparm['newmobile'])
            ->where("smscode", "=", $pageparm['smscode'])
            ->count();
        if (!$count) {
            ajaxmsg('短信验证码不正确', 0);
        } else {
            Db::name("smscode")
                ->where("mobile", "=", $pageparm['newmobile'])
                ->where("smscode", "=", $pageparm['smscode'])
                ->delete();
        }
        $pageparm['user_id'] = $user_id;
        $pageparm['mobile']  = $pageparm['newmobile'];
        Db::name("user")->strict(false)->update($pageparm);
        ajaxmsg('更新成功', 1);
    }

    public function address()
    {
        $user_id = $this->islogin();
        $prefix  = config('database.prefix');
        $list    = Db::name("user_address")->where("user_id", "=", $user_id)->select();
        foreach ($list as $k => $v) {
            $sql                     = "SELECT region_name from {$prefix}region where region_id={$v['province']} OR region_id={$v['city']} OR region_id={$v['district']}";
            $tempArr                 = Db::query($sql);
            $list[$k]['region_name'] = implode(' ', array_column($tempArr, 'region_name'));
            $list[$k]['city_name']   = Db::name("region")->where("region_id={$v['city']}")->value("region_name");
        }
        ajaxmsg('true', 1, $list);
    }

    public function addressrupdate()
    {
        $pageparm            = $this->getPageparm();
        $pageparm['user_id'] = $this->islogin();
        if ($pageparm['address_id'] > 0) {
            $res = Db::name('user_address')->update($pageparm);
        } else {
            $data['is_default'] = setDefaultAddress($pageparm['user_id']);
            $res                = Db::name('user_address')->insertGetId($pageparm);
        }
        if ($res > 0) {
            ajaxmsg('操作成功', 1);
        } else {
            ajaxmsg('操作失败', 0);
        }
    }

    public function addressdetail()
    {
        $user_id    = $this->islogin();
        $address_id = input('address_id', 0);
        $info       = Db::name("user_address")
            ->where("address_id", "=", $address_id)
            ->where("user_id", "=", $user_id)
            ->find();
        ajaxmsg('true', 1, $info);
    }

    public function deladdress()
    {
        $user_id    = $this->islogin();
        $address_id = input('address_id', 0);
        $res        = Db::name("user_address")
            ->where("address_id", "=", $address_id)
            ->where("user_id", "=", $user_id)
            ->delete();
        if ($res > 0) {
            ajaxmsg('操作成功', 1);
        } else {
            ajaxmsg('操作失败', 0);
        }
    }

    public function addrdefault()
    {
        $pageparm            = input("request.");
        $pageparm['user_id'] = $this->islogin();

        Db::name('user_address')
            ->where("user_id", "=", $pageparm['user_id'])
            ->update([
                'is_default' => 0,
            ]);
        $res = Db::name('user_address')
            ->where("address_id", "=", $pageparm['address_id'])
            ->update([
                'is_default' => 1,
            ]);;
        if ($res > 0) {
            ajaxmsg('操作成功', 1);
        } else {
            ajaxmsg('操作失败', 0);
        }
    }


    public function getChildrenData()
    {
        $list = categoryListAjax();
        ajaxmsg('true', 1, $list);
    }

    #聚合查询商品列表-启用
    public function catgoodslist()
    {
        $sort  = input("sort", 'goods_id');
        $order = input('order');
        switch ($sort) {
            case 'goods_id':
                $orderby = "a.goods_id {$order}";
                break;
            case 'total_sale_num':
                $orderby = "a.total_sale_num {$order}";
                break;
            case 'last_update':
                $orderby = "a.goods_id {$order}";
                break;
            case 'comment_num':
                $orderby = "a.comment_num {$order}";
                break;
            case 'shop_price':
                $orderby = "a.shop_price {$order}";
                break;
            case 'is_new':
                $orderby = "a.is_new {$order}";
                break;
            case 'is_hot':
                $orderby = "a.is_hot {$order}";
                break;
        }
        $cat_id = input('cat_id', 0);
        if ($cat_id > 0) {
            $getChildrenDataStr = getChildrenData($cat_id, 1);
        }
        $keywords = input('keywords');
        $brand_id = input('brand_id');
        $page     = input('page', 1);
        $rows     = input('rows', 20);
        if ($cat_id > 0) {
            $categoryInfo = Db::name("category")
                ->where("cat_id={$cat_id}")
                ->find();
            $pargoryInfo  = Db::name("category")
                ->where("cat_id={$categoryInfo['parent_id']}")
                ->find();
            $pcatList     = Db::name("category")
                ->where("parent_id={$categoryInfo['parent_id']}")
                ->field("cat_id,cat_name,parent_id")
                ->order("sort_order desc")
                ->select();
            $scatList     = Db::name("category")
                ->where("parent_id={$categoryInfo['cat_id']}")
                ->field("cat_id,cat_name,parent_id")
                ->order("sort_order desc")
                ->select();
        }
        $field2 = 'a.goods_id,a.goods_name,a.cat_id,a.store_id,a.brand_id,a.shop_price,a.market_price,
        a.original_img,a.total_sale_num,virtual_sale_num,a.comment_num,a.store_id,a.is_new,a.is_hot,a.is_best,
        b.product_id';
        $gmap   = [];
        if ($cat_id > 0) {
            $gmap[] = ['a.cat_id', 'in', $getChildrenDataStr];
        }
        $gmap[] = ['a.is_on_sale', '=', 1];
        $gmap[] = ['a.is_show', '=', 1];
        $gmap[] = ['a.review_status', '=', 2];
        if ($keywords) {
            $gmap[] = ['a.goods_name|a.keywords|a.goods_brief|a.goods_tag|a.goods_product_tag', 'like', "%{$keywords}%"];
        }
        if ($brand_id > 0) {
            $gmap[] = ['a.brand_id', '=', $brand_id];
        }

        $price_min = input('price_min', 0);
        $price_max = input('price_max', 0);
        if ($price_min > 0 && $price_max > 0) {
            $gmap[] = ['a.shop_price', 'between', [$price_min, $price_max]];
        }
        $this->assign('price_min', $price_min);
        $this->assign('price_max', $price_max);

        $ship = input('ship', 0);
        if (isset($ship)) {
            if ($ship == 1) {
                $gmap[] = ['a.is_shipping', '=', $ship];
            }
        }
        $self = input('self', 1);
        if (isset($self)) {
            if ($self == 0) {
                $gmap[] = ['a.store_id', '=', $self];
            }
        }
        $have = input('have', 0);
        if (isset($have)) {
            if ($have > 0) {
                $gmap[] = ['a.goods_number', '>', 0];
            }
        }

        $area = input('area', '');
        if ($area) {
            $gmap[] = ['a.area', '=', $area];
        }

        $goodsList     = Db::name("goods")->alias("a")
            ->join("products b", "a.goods_id=b.goods_id", "LEFT")
            ->field($field2)
            ->where($gmap)
            ->order($orderby)
            ->group("a.goods_id")
            ->page($page, $rows)
            ->select();
        $total         = Db::name("goods")->alias("a")
            ->join("products b", "a.goods_id=b.goods_id", "LEFT")
            ->field($field2)
            ->where($gmap)
            ->group("a.goods_id")
            ->count();
        $goods_gallery = Db::name('goods_gallery')
            ->where('goods_id', 'IN', array_column($goodsList, 'goods_id'))
            ->order('goods_id desc')
            ->select();
        foreach ($goodsList as $k => $v) {
            $ids      = array_column($goods_gallery, 'goods_id');
            $valCount = array_count_values($ids);
            if (isset($valCount[$v['goods_id']])) {
                $goodsList[$k]['goods_gallery'] = array_slice($goods_gallery, array_search($v['goods_id'], $ids), $valCount[$v['goods_id']]);
            } else {
                $goodsList[$k]['goods_gallery'] = [];
            }
        }
        ajaxmsg('true', 1, $goodsList);
    }

    #省市区三级联动数据源
    public function regionList()
    {
        $list      = Db::name('region')
            ->where("parent_id", "=", 1)
            ->field("region_id as id,region_name as value")
            ->order("region_id asc")
            ->select();
        $listTwo   = Db::name("region")
            ->where('parent_id', 'IN', array_column($list, 'id'))
            ->field("region_id as id,region_name as value,parent_id as pid")
            ->order("parent_id asc")
            ->select();
        $listThree = Db::name("region")
            ->where('parent_id', 'IN', array_column($listTwo, 'id'))
            ->field("region_id as id,region_name as value,parent_id as pid")
            ->order("parent_id asc")
            ->select();
        foreach ($listTwo as $k => $v) {
            $ids      = array_column($listThree, 'pid');
            $valCount = array_count_values($ids);
            if (array_key_exists($v['id'], $valCount)) {
                $listTwo[$k]['childs'] = array_slice(
                    $listThree,
                    array_search($v['id'], $ids),
                    $valCount[$v['id']]
                );
            }
        }
        foreach ($list as $k => $v) {
            $ids      = array_column($listTwo, 'pid');
            $valCount = array_count_values($ids);
            if (array_key_exists($v['id'], $valCount)) {
                $list[$k]['childs'] = array_slice(
                    $listTwo,
                    array_search($v['id'], $ids),
                    $valCount[$v['id']]
                );
            }
        }
        ajaxmsg('true', 1, $list);
    }

    public function goodsdetail()
    {
        $goodsModel       = new GoodsModel();
        $goods_id         = input('goods_id/d');
        $goods            = Db::name("goods")->alias("a")
            ->leftJoin("exchange_goods b", "a.goods_id=b.goods_id")
            ->where('a.goods_id', $goods_id)
            ->field("a.*,b.exchange_integral")
            ->find();
        $goods['product'] = $goodsModel->getProduct($goods_id);
        $goods['attr']    = $goodsModel->getAttriBute($goods);
        $goods['brand']   = $goodsModel->getBrand($goods['brand_id']);

        $data            = [];
        $data['goods']   = $goods;
        $data['comment'] = $goodsModel->getGoodsComment($goods_id);

        $spec = Db::name("products")->where("goods_id", "=", $goods['goods_id'])->select();
        if (count($spec) > 0) {
            foreach ($spec as $k => $v) {
                if ($k == 0) {
                    $spec[$k]['active'] = 1;
                } else {
                    $spec[$k]['active'] = 0;
                }
            }
        } else {
            $spec = [];
        }
        $data['spec']    = $spec;
        $token           = input("token");
        $cartGoodsNumber = 0;
        if ($token) {
            $user                    = Db::name("user")->where("token", "=", $token)->find();
            $data['user']            = $user;
            $data['cartGoodsNumber'] = Db::name("cart")->where("user_id", "=", $user['user_id'])->sum("goods_num");

            $data['iscollect']                   = Db::name("goods_collect")
                ->where("goods_id", "=", $goods_id)
                ->where("user_id", "=", $user['user_id'])
                ->count();
            $data['user_address']                = Db::name("user_address")
                ->where("user_id", "=", $user['user_id'])
                ->order("is_default desc")
                ->find();
            $data['user_address']['region_name'] = getprovincecitydistrict($data['user_address']);
            $data['address']                     = Db::name("user_address")
                ->where("user_id", "=", $user['user_id'])
                ->order("is_default desc")
                ->find();

            $goodsclickData            = [
                'goods_id' => $goods_id,
                'store_id' => $goods['store_id'],
                'date'     => date('Y-m-d'),
            ];
            $goodsclickData['user_id'] = $user['user_id'];
            $goodsclick                = Db::name("goods_click")
                ->where('goods_id', '=', $goods_id)
                ->where('user_id', '=', $user['user_id'])
                ->find();
            if (!$goodsclick) {
                Db::name('goods_click')->insert($goodsclickData);
            }

        } else {
            $data['user']            = [];
            $data['cartGoodsNumber'] = $cartGoodsNumber;
            $data['iscollect']       = 0;
            $data['user_address']    = [];
        }
        $data['guesslike'] = goods_random_data(20);

        $data['store'] = Db::name("store")
            ->where("store_id", "=", $goods['store_id'])
            ->find();

        $data['storeaddress']  = getAddressWithOder($data['store']);
        $data['goods_gallery'] = Db::name("goods_gallery")->where("goods_id", "=", $goods_id)->order("img_sort asc")->select();
        ajaxmsg("true", 1, $data);
    }

    public function goodsComment()
    {
        $goods_id = input('goods_id/d');
        $page     = input('page/d');
        $pageSize = input('pageSize/d');
        $model    = new GoodsModel;
        $res      = $model->getCommentList($goods_id, $page, $pageSize);
        ajaxmsg('', 1, $res);
    }

    public function getcartGoodsNumber()
    {
        $user_id                 = $this->islogin();
        $data                    = [];
        $data['cartGoodsNumber'] = Db::name("cart")->where("user_id", "=", $user_id)->sum("goods_num");
        ajaxmsg('true', 1, $data);
    }

    public function goodscollectList()
    {
        $user_id = $this->islogin();
        $list    = Db::name("goods_collect")->alias("a")
            ->leftJoin("goods b", "a.goods_id=b.goods_id")
            ->field("a.*,b.*")
            ->select();
        ajaxmsg('true', 1, $list);
    }

    public function collectupdate()
    {
        $act      = input('act');
        $goods_id = input('goods_id', 0);
        $user_id  = $this->islogin();
        switch ($act) {
            case 'add':
                $map   = [];
                $map[] = ['goods_id', '=', $goods_id];
                $map[] = ['user_id', '=', $user_id];
                $id    = Db::name("goods_collect")->where($map)->value("rec_id");
                if ($id > 0) {
                    $res = Db::name('goods_collect')->where($map)->delete();
                } else {
                    $collectData             = [];
                    $collectData['user_id']  = $user_id;
                    $collectData['goods_id'] = $goods_id;
                    $collectData['add_time'] = time();
                    $res                     = Db::name('goods_collect')->insertGetId($collectData);
                }
                if ($res > 0) {
                    ajaxmsg('收藏成功', 2);
                } else {
                    ajaxmsg('收藏失败', 0);
                }
                break;
            case 'delete':
                $map   = [];
                $map[] = ['goods_id', '=', $goods_id];
                $map[] = ['user_id', '=', $user_id];
                $res   = Db::name('goods_collect')->where($map)->delete();
                if ($res > 0) {
                    ajaxmsg('取消收藏成功', 1);
                } else {
                    ajaxmsg('取消收藏失败', 0);
                }
                break;
        }
    }


    public function cartList()
    {
        $user_id = $this->islogin();

        $map        = [];
        $map[]      = ['a.user_id', '=', $user_id];
        $select_arr = input('request.select/a');
        $num_arr    = input('request.num/a');
        if ($num_arr) {
            $cartList = Db::name('cart a')->where($map)
                ->join('goods b', 'a.goods_id=b.goods_id')
                ->join('products c', 'a.product_id=c.product_id', 'LEFT')
                ->field('a.id,a.goods_id,a.goods_num,a.product_id,a.selected,b.goods_number,c.product_number')
                ->select();
            if ($cartList) {
                $cartList = array_combine(array_column($cartList, 'id'), $cartList);
            }

            foreach ($num_arr as $id => $num) {
                $data = [
                    'goods_num' => $num < 1 ? 1 : $num,
                    'selected'  => ($select_arr[$id] == 'false' || $select_arr[$id] == '0') ? 0 : 1,
                ];
                if ($cartList[$id]['goods_num'] == $data['goods_num'] && $cartList[$id]['selected'] == $data['selected']) {
                    continue;
                }
                if (is_numeric($cartList[$id]['product_number'])) {
                    if ($data['goods_num'] > $cartList[$id]['product_number']) {
                        return json(['msg' => '库存仅剩余：' . $cartList[$id]['product_number'], 'num' => $cartList[$id]['product_number'], 'id' => $id], 403);
                    }
                } elseif ($data['goods_num'] > $cartList[$id]['goods_number']) {
                    return json(['msg' => '库存仅剩余：' . $cartList[$id]['goods_number'], 'num' => $cartList[$id]['goods_number'], 'id' => $id], 403);
                }

                Db::name('cart')->where("id", $id)->update($data);
                unset($data);
            }
        }

        $storeList = Db::name("cart")->alias("a")
            ->join("store b", "a.store_id=b.store_id", "LEFT")
            ->field("a.*,b.store_name,b.store_id,max(a.selected) as selected")
            ->where($map)
            ->group("a.store_id")
            ->select();
        $goodsList = Db::name("cart")->alias("a")
            ->join("goods b", "a.goods_id=b.goods_id", "LEFT")
            ->join("products c", "a.product_id=c.product_id", "LEFT")
            ->field("a.*,b.goods_name,b.cat_id,b.goods_sn,b.brand_id,b.shop_price,b.market_price,original_img,
                c.tempvalue,c.product_sn,c.product_number,c.product_price,(a.goods_price*a.goods_num) as goodsxiaoji")
            ->where($map)
            ->select();

        $select_num = $sum_price = $sum_save_price = 0;
        foreach ($goodsList as $k => $v) {
            if ($v['selected'] == 1) {
                $sum_price      += $v['goods_num'] * $v['goods_price'];
                $sum_save_price += $v['goods_num'] * $v['market_price'];
            }
        }
        foreach ($storeList as $k => $v) {
            foreach ($goodsList as $a => $b) {
                if (intval($v['store_id']) == $b['store_id']) {
                    $storeList[$k]['goodsList'][] = $b;
                }
            }
        }
        $jiesuanNum = 0;
        if ($goodsList) {
            $select_num = array_sum(array_column($goodsList, 'selected'));
            foreach ($goodsList as $k => $v) {
                if ($v['selected'] == 1) {
                    $jiesuanNum += $v['goods_num'];
                }
            }
        }


        $data['storeList']        = $storeList;
        $data['selectGoodsNum']   = $select_num;
        $data['jiesuanNum']       = $jiesuanNum;
        $data['selectGoodsTotal'] = round($sum_price, 2);
        $data['marketGoodsTotal'] = round($sum_save_price - $sum_price, 2);

        $gmap[] = ['a.is_on_sale', '=', 1];
        $gmap[] = ['a.is_show', '=', 1];
        $gmap[] = ['a.review_status', '=', 2];
        $gmap[] = ['a.is_sui', '=', 1];
        $gmap[] = ['a.is_delete', '=', 0];
        $field  = 'a.goods_id,a.goods_name,a.cat_id,a.store_id,a.brand_id,a.shop_price,a.market_price,
                   a.original_img,a.total_sale_num,a.comment_num,a.store_id,a.is_new,a.is_hot,a.is_best';


        $data['guesslike'] = Db::name("goods")->alias("a")
            ->where($gmap)
            ->field($field)
            ->limit(20)
            ->select();;
        ajaxmsg('true', 1, $data);
    }

    public function addCart()
    {
        $goods_id  = input('request.goods_id');
        $shareMan  = input('share_man/d');
        $num       = input('request.num');
        $attr_keys = input('request.attr_keys');
        $user_id   = $this->islogin();
        $result    = CartModel::addCart($goods_id, $num, $user_id, session_id(), $attr_keys, false, $shareMan);
        return json($result);
    }

    public function cartUpdate()
    {
        $user_id = $this->islogin();
        $id      = input("id", 0);
        $map     = [];
        $map[]   = ['user_id', '=', $user_id];
        $map[]   = ['id', '=', $id];

        $act = input("request.act");
        switch ($act) {
            case 'add':
                $cart = Db::name("cart")->where($map)->find();
                if ($cart['prom_type'] == 2) {
                    $checkPrice = (new GroupGoods)->calcPrice($cart['prom_id'], $cart['goods_num'] + 1);
                    if (!is_numeric($checkPrice)) {
                        ajaxmsg($checkPrice, 0);
                    }
                    $update = [
                        'goods_num'   => $cart['goods_num'] + 1,
                        'goods_price' => $checkPrice
                    ];
                    $res    = Db::name("cart")->where('id', $id)->update($update);
                } else {
                    $res = Db::name("cart")->where($map)->setInc("goods_num");
                }
                break;
            case 'reduce':
                $cart = Db::name("cart")->where($map)->find();
                if ($cart['goods_num'] <= 1) ajaxmsg('需最少购买1件', 0);

                if ($cart['prom_type'] == 2) {
                    $checkPrice = (new GroupGoods)->calcPrice($cart['prom_id'], $cart['goods_num'] - 1);
                    if (!is_numeric($checkPrice)) {
                        ajaxmsg($checkPrice, 0);
                    }
                    $update = [
                        'goods_num'   => $cart['goods_num'] - 1,
                        'goods_price' => $checkPrice
                    ];
                    $res    = Db::name("cart")->where('id', $id)->update($update);
                } else {
                    $res = Db::name("cart")->where($map)->setDec("goods_num");
                }
                break;
            case 'delete':
                $res = Db::name("cart")->where($map)->delete();
                break;

            case 'cartDeleteAll':
                $cartDeleteAllmap   = [];
                $cartDeleteAllmap[] = ['selected', '=', 1];
                $cartDeleteAllmap[] = ['user_id', '=', $user_id];
                $res                = Db::name("cart")->where($map)->delete();
                break;
            case 'cartSelectAll':
                $cartSelectAllmap   = [];
                $cartSelectAllmap[] = ['user_id', '=', $user_id];
                $res                = Db::name("cart")->where($map)->update([
                    'selected' => 1,
                ]);
                break;
            case 'storeInit':
                $selected = input("selected");
                $cmap     = [];
                $cmap[]   = ['store_id', '=', input("store_id", 0)];
                $cmap[]   = ['user_id', '=', $user_id];

                $res = Db::name("cart")->where($cmap)->update([
                    'selected' => $selected,
                ]);
                break;
        }
        if ($res > 0) {
            ajaxmsg('操作成功', 1);
        } else {
            ajaxmsg('操作失败', 0);
        }
    }

    public function confirmOrder()
    {
        $this->user_id = $this->islogin();
        $coupon        = input('coupon/a', 0);
        $shareMan      = input('share_man/d', 0);
        $buyNow        = input('post.buyNow');
        $address_id    = input('post.address_id/d');
        $buyNowGoods   = [];

        #立即购买（单独购买，团购，秒杀，拼团，拍卖）
        if ($buyNow) {
            $post  = input('post.');
            $check = $this->validate($post, [
                'goods_id' => 'require|integer|gt:0',
                'number'   => 'require|integer|gt:0',
            ], [
                'goods_id' => '商品ID错误',
                'number'   => '购买数量错误',
            ]);
            if (!$check) {
                ajaxmsg($check, 0);
            }
            $virtualCart = CartModel::addCart($post['goods_id'], $post['number'], $this->user_id, '', $post['attr_key'] ?? null, 'buyNow', $shareMan);
            if ($virtualCart['status'] < 0) {
                ajaxmsg($virtualCart['msg'], 0);
            }
            $buyNowGoods[] = $virtualCart['result'];
        }
        $result = calculatePrice($this->user_id, $coupon, $buyNowGoods);
        if ($result['status'] < 0) {
            ajaxmsg($result['msg'], 0);
        }
        $address_map = ['user_id' => $this->user_id, 'is_default' => 1];
        if ($address_id) {
            $address_map = ['user_id' => $this->user_id, 'address_id' => $address_id];
        }
        $address                = Db::name('user_address')
            ->where($address_map)
            ->order('is_default DESC')
            ->find();
        $address['region_name'] = getprovincecitydistrict($address);
        $region                 = [];
        if ($address) {
            $region_ids = array_merge(array_column($address, 'province'), array_column($address, 'city'), array_column($address, 'district'));
            $region     = Db::name('region')->whereIn('region_id', $region_ids)->column('region_name', 'region_id');
        }
        $time       = time();
        $coupon     = [];
        $couponList = Db::name('coupon_list a')
            ->join('coupon b', 'a.coupon_id=b.coupon_id')
            ->where(['a.user_id' => $this->user_id, 'a.is_delete' => 0, 'a.order_id' => 0])
            ->whereIn('b.store_id', $result['result']['store_ids'])
            ->where('b.use_start_time < ' . $time)
            ->where('b.use_end_time > ' . $time)
            ->field('a.*,b.name,b.store_id,b.money,b.min_goods_amount,b.store_id,FROM_UNIXTIME(b.use_end_time,"%Y-%m-%d") AS use_end_time')
            ->select();
        foreach ($couponList as $index => $item) {
            if ($item['min_goods_amount'] <= $result['result']['data'][$item['store_id']]['goods_price']) {
                $coupon['valid'][$item['store_id']][] = $item;
            } else {
                $coupon['invalid'][] = $item;
            }
        }
        $data                        = [];
        $data['region']              = $region;
        $data['address']             = $address;
        $data['coupon']              = $coupon;
        $data['orderInfo']           = $result['result'];
        $data['self_pickup_info']    = Db::name('self_pickup')->where(['user_id' => $this->user_id, 'is_delete' => 0])->order('last_time DESC')->find();
        $data['self_pickup_address'] = Db::name('shop_config')->where('code', 'self_pickup_address')->value('value');

        $time             = time();
        $map              = [];
        $map[]            = ['user_id', '=', $this->user_id];
        $map[]            = ['cash', '>', 0];
        $map[]            = ['start_time', '<=', $time];
        $map[]            = ['over_time', '>=', $time];
        $map[]            = ['status', '=', 1];
        $data['giftcard'] = Db::name("giftcard")->where($map)->field('*,FROM_UNIXTIME(over_time,"%Y-%m-%d") AS over_time_cn')->select();
        $mixture          = 0;
        $store_ids        = $result['result']['store_ids'];
        foreach ($store_ids as $k => $v) {
            if ($v > 0) {
                $mixture = 1;
            }
        }
        $data['mixture'] = $mixture;

        $data['inv_content_list'] = Db::name('shop_config')->where('code', 'fapiaocon')->value('value');
        if ($data['inv_content_list']) {
            $data['inv_content_list'] = explode(',', $data['inv_content_list']);
        }
        $data['invoice']     = Db::name('user_invoice')->where('user_id', $this->user_id)->order('invoice_id DESC')->find();
        $data['vat_invoice'] = Db::name('user_vat_invoice')->where('user_id', $this->user_id)->find();

        ajaxmsg('true', 1, $data);
    }

    public function createOrder()
    {
        $coupon        = input('coupon/a', 0);
        $buyNow        = input('post.buyNow');
        $shareMan      = input('share_man/d');
        $buyNowGoods   = [];
        $this->user_id = $this->islogin();
        if ($buyNow) {
            $post  = input('post.');
            $check = $this->validate($post, [
                'goods_id' => 'require|integer|gt:0',
                'number'   => 'require|integer|gt:0',
            ], [
                'goods_id' => '商品ID错误',
                'number'   => '购买数量错误',
            ]);
            if (true !== $check) {
                ajaxmsg($check, 0);
            }
            $virtualCart = CartModel::addCart($post['goods_id'], $post['number'], $this->user_id, '', $post['attr_key'] ?? null, 'buyNow', $shareMan);
            if ($virtualCart['status'] < 0) {
                ajaxmsg($virtualCart['msg'], 0);
            }
            $buyNowGoods[] = $virtualCart['result'];
        }
        $pageparm = input('request.');
        //移动端礼品卡在前端计算，args[2]固定传0
        $result = calculatePrice($this->user_id, $coupon, $buyNowGoods, 0, $pageparm['mode'] ?? false);
        if (isset($pageparm['mode']) && (int)$pageparm['mode'] == 1) {
            if (!isset($pageparm['pickup'])) {
                ajaxmsg('请填写提货信息', 0);
            }
            //小程序端，pickup为json字符串
            if (!is_array($pageparm['pickup'])) {
                $pageparm['pickup'] = json_decode($pageparm['pickup'], true);
            }
            if (!isset($pageparm['pickup']['consignee']) || !isset($pageparm['pickup']['mobile'])) {
                ajaxmsg('请填写提货人及手机号', 0);
            }
            if ((!isset($pageparm['pickup']['pickup_time']))) {
                ajaxmsg('请填写提货时间', 0);
            }
        } else if (!isset($pageparm['address_id'])) {
            ajaxmsg('请选择收货地址', 0);
        }

        $res = (new OrderModel())->addOrder($this->user_id, $result['result'], $pageparm, $buyNow);
        if ($res['status'] < 0) {
            ajaxmsg($res['msg'], 0);
        } else {
            //$res = $this->giftcardBindOrder($res);//礼品卡单处理
            ajaxmsg("生成订单", 1, $res);
        }
    }

    /**
     * order_status: 订单状态  0:待确认 1:已确认 2:待评价 3:已取消 4:已完成 5:退款 6:无效
     * pay_status: 0：待支付，1：已付款
     * shipping_status: 发货状态  0：未发货 1：发货中 2：已发货 3：部分发货
     **/
    public function orderDetail()
    {
        $user_id  = $this->islogin();
        $order_sn = input("order_sn");
        $act      = input('act', 'payact');
        $map      = [];
        $map[]    = ['user_id', '=', $user_id];
        if ($act == 'payact') {
            $map[] = ['order_status', '=', 0];
            $map[] = ['pay_status', '=', 0];
        }
        $map[] = ['order_sn', '=', $order_sn];

        $order = Db::name("order")->where($map)->find();
        if (!$order) {

            $omap   = [];
            $omap[] = ['user_id', '=', $user_id];
            if ($act == 'payact') {
                $omap[] = ['order_status', '=', 0];
                $omap[] = ['pay_status', '=', 0];
            }
            $omap[]                = ['master_order_sn', '=', $order_sn];
            $list                  = Db::name("order")->where($omap)->select();
            $order                 = $list[0];
            $order['order_amount'] = round(array_sum(array_column($list, 'order_amount')), 2);//支持批量订单支付
            $order['total_amount'] = round(array_sum(array_column($list, 'total_amount')), 2);

        }
        $order['user'] = Db::name("user")->where('user_id', '=', $user_id)->find();
        ajaxmsg('true', 1, $order);
    }

    public function orderlist()
    {
        $this->user_id = $this->islogin();
        $map           = [];
        $map[]         = ['a.user_id', 'eq', $this->user_id];
        $bttime        = trim(input('bttime', 0));
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
        $page = input('page', 1);
        $rows = input('rows', 20);

        $is_delete = input('is_delete', 0);
        if ($is_delete > 0) {
            $map[] = ['a.is_delete', '>', 0];
        } else {
            $map[] = ['a.is_delete', '=', 0];
        }

        $order_status = input('order_status');
        $orderModel   = new Order;
        $order_map    = $orderModel->orderStatusWhere($order_status);

        $list  = Db::name("order")->alias("a")
            ->join('store b', 'a.store_id=b.store_id', 'LEFT')
            ->where($map)
            ->where($order_map)
            ->field('a.*,b.store_name')
            ->order("a.order_id desc")
            ->page($page, $rows)
            ->select();
        $total = 0;
        if ($list) {
            $total = Db::name("order")->alias("a")
                ->where($map)
                ->where($order_map)
                ->count();

            $goodslist       = Db::name('order_goods')->alias("a")
                ->join("goods b", "a.goods_id=b.goods_id")
                ->field("a.*,b.goods_name,b.goods_id,b.shop_price,b.original_img")
                ->where('a.order_id', 'IN', array_column($list, 'order_id'))
                ->order("a.order_id desc")
                ->select();
            $self_shop_title = Db::name('shop_config')->where('code', 'shop_name')->cache(true)->value('value');
            foreach ($list as $k => $v) {
                $ids           = array_column($goodslist, 'order_id');
                $valCount      = array_count_values($ids);
                $goodslistTemp = [];
                if (isset($valCount[$v['order_id']])) {
                    $goodslistTemp         = array_slice($goodslist, array_search($v['order_id'], $ids), $valCount[$v['order_id']]);
                    $list[$k]['goodslist'] = $goodslistTemp;
                }

                $jiesuanNum = 0;
                foreach ($goodslistTemp as $a => $b) {
                    $jiesuanNum += $b['goods_num'];
                }
                $list[$k]['jiesuanNum'] = $jiesuanNum;
                if ($v['store_id'] == 0) {
                    $list[$k]['store_name'] = $self_shop_title;
                }
            }
        }
        $data = [
            'tinfo' => $orderModel->statistics($this->user_id),
            'list'  => $list,
            'total' => $total
        ];
        ajaxmsg('true', 1, $data);
    }

    public function orderpailist()
    {
        $this->user_id = $this->islogin();
        $map           = [];
        $map[]         = ['a.user_id', 'eq', $this->user_id];
        $keywords      = trim(input('keywords', ''));
        if ($keywords) {
            $map[] = ['a.order_sn', 'like', "%$keywords%"];
        }
        $page        = input('page', 1);
        $rows        = input('rows', 20);
        $oder_status = input('order_status', 10000);
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
            ->leftJoin("store b", "a.store_id=b.store_id")
            ->leftJoin("paimai_house c", "a.order_prom_id=c.id")
            ->leftJoin("user d", "a.user_id=d.user_id")
            ->field("a.*,b.store_id,b.store_name,d.avatar,d.user_name")
            ->where($map)
            ->where("c.id > 0")
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
            $houstData = self::$redis->get('House' . $v['order_prom_id']);
            if ($houstData) {
                $list[$k]['join_num'] = count($houstData['list']);
            } else {
                $list[$k]['join_num'] = 0;
            }
            $ids                   = array_column($goodslist, 'order_id');
            $valCount              = array_count_values($ids);
            $list[$k]['goodslist'] = array_slice($goodslist, array_search($v['order_id'], $ids), $valCount[$v['order_id']]);
        }
        $data['list']  = $list;
        $data['total'] = $total;
        ajaxmsg('true', 1, $data);
    }

    public function orderinfo()
    {
        $this->user_id = $this->islogin();
        $order_id      = input('order_id', 0);
        $order         = Db::name("order")->alias("a")
            ->join("store b", "a.store_id=b.store_id", "LEFT")
            ->field("a.*,b.store_name")
            ->where("a.order_id={$order_id}")
            ->find();
        if (!$order) {
            ajaxmsg('异常操作', 0);
        }
        if ($order['invoice_type'] > 0) {
            $invoice                  = Db::name('user_vat_invoice')->where('id', $order['invoice_no'])->field('company_name,tax_id')->find();
            $order['invoice_title']   = $invoice['company_name'];
            $order['invoice_no']      = $invoice['tax_id'];
            $order['invoice_content'] = '商品明细';
        }
        $data = ['order' => $order];

        $data['goodslist']  = Db::name('order_goods')->alias("a")
            ->join("goods b", "a.goods_id=b.goods_id", "LEFT")
            ->join("order c", "a.order_id=c.order_id")
            ->field("a.*,b.goods_name,b.goods_id,b.shop_price,b.original_img")
            ->where("a.order_id={$order_id}")
            ->order("a.order_id desc")
            ->select();
        $data['user']       = Db::name("user")
            ->where("user_id={$order['user_id']}")
            ->find();
        $data['order_log']  = Db::name("order_log")
            ->where("order_id={$order_id}")
            ->order("action_id desc")
            ->select();
        $data['goodsCount'] = Db::name('order_goods')
            ->where("order_id={$order_id}")
            ->sum("goods_num");

        $withdraw_day = Db::name('shop_config')->where('code', 'withdraw_day')->cache(true)->value('value');
        //未发货 或 发货时间在结算期内，允许退货
        $data['allow_refund'] = $order['pay_status'] == 1 && ($order['shipping_time'] < 1 || (((int)$withdraw_day * 86400) + (int)$order['shipping_time']) > time());
        //方便前端检查整单退货
        $data['refund_count'] = array_sum(array_column($data['goodslist'], 'is_refund'));
        //物流信息
        if ($order['shipping_status'] == 2) {
            $delivery_order = Db::name('delivery_order')->where('order_id', $order_id)->find();
            if ($delivery_order) {
                $no   = $delivery_order['shipping_no'];
                $code = Db::name('shipping')->where('shipping_id', $delivery_order['shipping_id'])->value('shipping_code');
                if ($code == 'shunfeng') {
                    $no = $no . ':' . substr($delivery_order['mobile'], -4);
                }
                $data['shipping'] = expressQuery($no, $code);
            }
        }
        ajaxmsg('true', 1, $data);
    }

    public function orderupdate()
    {
        $user_id  = $this->islogin();
        $order_id = input("order_id");

        $map = [
            ['user_id', '=', $user_id],
            ['order_id', '=', $order_id],
            ['shipping_status', '=', 2]
        ];

        $res = Db::name("order")->where($map)->update([
            'confirm_time' => time(),
            'order_status' => 2
        ]);
        if ($res > 0) {
            Db::name("delivery_order")->where('order_id', $order_id)->update(['status' => 3]);

            $user  = $this->uinfo();
            $order = Db::name('order')->where($map)->find();
            orderLog($order, '买家确认收货', '', $user['user_id'], $user['user_name'], 2);
            ajaxmsg("订单完成", 1);
        } else {
            ajaxmsg("操作失败", 0);
        }
    }

    public function orderCancel()
    {
        $user_id  = $this->islogin();
        $order_id = input("order_id");

        $orderModel = new Order;
        $res        = $orderModel->cancel($order_id, $user_id);
        if ($res['status'] > 0) {
            ajaxmsg('订单已取消', 1);
        } else {
            ajaxmsg($res['msg'], 0);
        }
    }

    #我的收藏
    public function favorite()
    {
        $this->user_id = $this->islogin();
        $map           = [];
        $map[]         = ['a.user_id', 'eq', $this->user_id];
        $page          = input('page', 1);
        $rows          = input('rows', 100);
        $list          = Db::name("goods_collect")->alias("a")
            ->join("goods c", "c.goods_id=a.goods_id", "LEFT")
            ->field("c.*,a.rec_id")
            ->where($map)
            ->order("a.rec_id desc")
            ->page($page, $rows)
            ->select();
        $total         = Db::name("goods_collect")
            ->alias("a")
            ->where($map)
            ->count();
        ajaxmsg('true', 1, $list);
    }

    public function goodscollectDelete()
    {
        $user_id = $this->islogin();
        $rec_id  = input('rec_id', 0);
        $map     = [];
        $map[]   = ['user_id', '=', $user_id];
        $map[]   = ['rec_id', '=', $rec_id];
        $res     = Db::name("goods_collect")->where($map)->delete();
        if ($res > 0) {
            ajaxmsg('删除成功', 1);
        } else {
            ajaxmsg('删除失败', 0);
        }
    }

    public function favstore()
    {
        $this->user_id = $this->islogin();
        $map           = [];
        $map[]         = ['a.user_id', 'eq', $this->user_id];
        $page          = input('page', 1);
        $rows          = input('rows', 10);
        $list          = Db::name("store_collect")->alias("a")
            ->join("store c", "c.store_id=a.store_id", "LEFT")
            ->field("c.*,a.rec_id")
            ->where($map)
            ->order("a.rec_id desc")
            ->page($page, $rows)
            ->select();
        $total         = Db::name("store_collect")
            ->alias("a")
            ->where($map)
            ->count();
        ajaxmsg('true', 1, $list);
    }

    public function storecollectDelete()
    {
        $user_id = $this->islogin();
        $rec_id  = input('rec_id', 0);
        $map     = [];
        $map[]   = ['user_id', '=', $user_id];
        $map[]   = ['rec_id', '=', $rec_id];
        $res     = Db::name("store_collect")->where($map)->delete();
        if ($res > 0) {
            ajaxmsg('删除成功', 1);
        } else {
            ajaxmsg('删除失败', 0);
        }
    }

    public function goodsclick()
    {
        $this->user_id = $this->islogin();
        $map           = [];
        $map[]         = ['a.user_id', 'eq', $this->user_id];
        $page          = input('page', 1);
        $rows          = input('rows', 100);
        $list          = Db::name("goods_click")->alias("a")
            ->join("goods b", "a.goods_id=b.goods_id", "LEFT")
            ->field("a.click_id as rec_id,b.*")
            ->where($map)
            ->order("a.click_id desc")
            ->group("a.goods_id")
            ->page($page, $rows)
            ->select();
        ajaxmsg('true', 1, $list);
    }

    public function goodsclickDelete()
    {
        $user_id  = $this->islogin();
        $click_id = input('click_id', 0);
        $map      = [];
        $map[]    = ['user_id', '=', $user_id];
        //$map[]    = ['click_id', '=', $click_id];
        $res = Db::name("goods_click")->where($map)->delete();
        if ($res > 0) {
            ajaxmsg('删除成功', 1);
        } else {
            ajaxmsg('删除失败', 0);
        }
    }

    public function pay()
    {
        error_reporting(0);
        $user_id   = $this->islogin();
        $user      = Db::name("user")->find($user_id);
        $jsapi     = input('jsapi', 0);//0:APP,1:公众号,2:小程序
        $data      = input('post.');
        $pay_code  = input('pay_code');
        $use_money = input('use_money/f', 0);
        if (isset($data['source']) && $data['source'] == 'prom') {
            //专题活动付款
            $subject = '订单支付';
            $order   = Db::name('prom_record')->where('order_sn', $data['order_sn'])->find();
            if (!$order) {
                ajaxmsg('订单不存在', 0);
            }
            $data['amount'] = $order['amount'];
        } else {
            //正常订单付款
            $subject = '商品订单';
            $order   = Db::name('order')->where('order_sn', $data['order_sn'])->select();
            if (!$order) {
                $order = Db::name('order')->where('master_order_sn', $data['order_sn'])->select();
                if (!$order) {
                    ajaxmsg('订单不存在', 0);
                }
            }
            if ($order[0]['pay_status'] == 1) {
                ajaxmsg('订单已支付', 0);
            }
            $data['amount'] = array_sum(array_column($order, 'order_amount'));//多个订单批量付款
        }

        if ($pay_code == 'usermoney') {
            //余额支付
            if ($user['pay_password'] != $data['pay_pwd']) {
                ajaxmsg('支付密码不正确', 0);
            }
            if (isset($data['source']) && $data['source'] == 'prom') {
                //专题活动付款
                $this->usermoneypayProm($order, $user_id);
            } else {
                //正常订单付款
                $this->usermoneypay($order, $data['amount'], $data['order_sn']);
            }
        } else {
            if (isset($data['source']) && $data['source'] == 'prom') {
                Db::name('prom_record')->where('order_sn', $data['order_sn'])->update(['user_money' => $use_money]);
            } else {
                //每个商家平摊使用多少余额
                if ($use_money > 0) {
                    foreach ($order as $index => $item) {
                        $proportion = $item['order_amount'] / $data['amount'];
                        $balance    = round($proportion * $use_money, 2);
                        Db::name('order')->where('order_id', $item['order_id'])->update(['user_money' => $balance]);
                    }

                    $data['amount'] = round($data['amount'] - $use_money, 2);
                } else {
                    //重置使用余额0元
                    Db::name('order')->whereIn('order_id', array_column($order, 'order_id'))->update(['user_money' => 0]);
                }
            }

            $payData    = [
                'subject'      => $subject,
                'out_trade_no' => $data['order_sn'] . mt_rand(1000, 9999),
                'amount'       => $data['amount'],
            ];
            $code       = ['weixin' => 'WxApp', 'alipay' => 'AliApp'];
            $pay_method = $code[$pay_code];
            $config     = json_decode(Db::name('payment')->where('pay_code', $pay_code)->value('pay_config'), true);
            if (!$config) {
                ajaxmsg('支付参数错误', 0);
            }
            if (strpos($pay_code, 'ali') === false) {
                if ($jsapi == 1) {
                    $config['appid']   = config('wapwxpayConfig')['appid'];
                    $config['secret']  = config('wapwxpayConfig')['secret'];
                    $config['mchid']   = config('wapwxpayConfig')['mch_id'];
                    $config['key']     = config('wapwxpayConfig')['key'];
                    $payData['openid'] = $user['openid'];
                    $res               = Pay::run('WxJspay', $config, $payData);
                }
                if ($jsapi == 0) {
                    $appwxpayConfig   = config('appwxpayConfig');
                    $config['appid']  = $appwxpayConfig['AppID'];
                    $config['secret'] = $appwxpayConfig['AppSecret'];
                    $res              = Pay::run($pay_method, $config, $payData);
                }
                if ($jsapi == 2) {
                    $config['appid']   = config('miniwxpayConfig')['appid'];
                    $config['secret']  = config('miniwxpayConfig')['secret'];
                    $config['mch_id']  = config('miniwxpayConfig')['mch_id'];
                    $payData['openid'] = $user['miniid'];
                    $res               = Pay::run('WxMinipro', $config, $payData);
                }
                if (isset($res['ret']) && isset($res['msg'])) {
                    ajaxmsg($res['msg'], 0);
                } else {
                    ajaxmsg('true', 1, $res);
                }
            } else {
                $res = Pay::run($pay_method, $config, $payData);
                ajaxmsg('true', 1, $res);
            }
        }
    }

    public function usermoneypay($orderlist, $amount, $order_sn)
    {
        $order = $orderlist[0];
        $user  = Db::name("user")->where('user_id', $order['user_id'])->find();
        if ($user['user_money'] < $amount) {
            ajaxmsg('数据异常', 0);
        }
        $res = 1;
        Db::startTrans();
        try {
            $result = (new AccountLog)->accountLog($user['user_id'], (-1) * min($user['user_money'], $amount), '用户移动订单支付', 0, 0, $order['order_id'], $order_sn);
            if ($result !== true) {
                throw new \Exception($result);
            }
            $orderData = [
                'pay_status'   => 1,
                'pay_time'     => time(),
                'pay_code'     => 'usemoney',
                'pay_name'     => '余额支付',
                'user_money'   => Db::raw('order_amount'),
                'order_amount' => 0
            ];
            Db::name("order")->where('master_order_sn', $order['master_order_sn'])->update($orderData);

            foreach ($orderlist as $item) {
                if ($item['store_id'] == 0) {
                    $stock_type = Db::name('shop_config')->where('code', 'stock_dec_time')->value('value');
                } else {
                    $stock_type = Db::name('store')->where('store_id', $order['store_id'])->value('stock_type');
                }
                if ($stock_type == 1) {
                    minus_stock($item);
                }

                $item['pay_status'] = 1;
                orderLog($item, '订单付款成功', '付款成功', $item['user_id'], $item['consignee'], 2);
                addStoreMsg($item['store_id'], $item['order_sn'] . '您有新订单已付款,请及时处理');
            }

            Db::commit();
        } catch (\Exception $e) {
            trace('余额支付异常：' . $e->getMessage());
            $res = 0;
            Db::rollback();
        }
        if ($res > 0) {
            ajaxmsg("支付成功", 1);
        } else {
            ajaxmsg("支付失败", 0);
        }
    }

    public function usermoneypayProm($order, $user_id)
    {
        $user = Db::name('user')->where('user_id', $user_id)->find();
        if ($user['user_money'] < $order['amount']) {
            ajaxmsg('余额不足', 0);
        }

        $result = (new AccountLog)->accountLog($user_id, (-1) * $order['amount'], '用户活动订单支付', 0, 0, $order['rec_id'], $order['order_sn']);
        if ($result !== true) {
            ajaxmsg($result, 1);
        }
        Db::name('prom_record')->where('rec_id', $order['rec_id'])->update(['pay_time' => time(), 'status' => 2, 'user_money' => $order['amount']]);
        ajaxmsg("支付成功", 1);
    }

    public function rechargepay()
    {
        $user_id    = $this->islogin();
        $order_sn   = get_order_sn();
        $order_type = input("order_type", 1);
        $money      = input("money", 0);

        if ($money < 10) {
            ajaxmsg('最小金额10元人民币', 0);
        }
        $rechargeData                = [];
        $rechargeData['order_sn']    = $order_sn;
        $rechargeData['money']       = $money;
        $rechargeData['user_id']     = $user_id;
        $rechargeData['order_type']  = $order_type;
        $rechargeData['note']        = input('note', '');
        $rechargeData['create_time'] = time();
        $pay_code                    = $order_type == 1 ? 'alipay' : 'weixin';
        $config                      = json_decode(Db::name('payment')->where('pay_code', $pay_code)->value('pay_config'), true);
        if (!$config) {
            ajaxmsg('支付参数错误', 0);
        }
        $id = Db::name("user_recharge")->insertGetId($rechargeData);
        if (!$id) {
            ajaxmsg('支付订单生成失败', 0);
        }
        $payData               = [
            'subject'      => '充值订单',
            'out_trade_no' => $rechargeData['order_sn'],
            'amount'       => $rechargeData['money'],
        ];
        $payData['notify_url'] = Request::domain() . '/api/apicloud/rechargecheck';
        if ($order_type == 2) {
            #微信返回
            $appwxpayConfig   = config('appwxpayConfig');
            $config['appid']  = $appwxpayConfig['AppID'];
            $config['secret'] = $appwxpayConfig['AppSecret'];
            $res              = Pay::run('WxApp', $config, $payData);
            if (isset($res['ret']) && isset($res['msg'])) {
                ajaxmsg($res['msg'], 0);
            } else {
                ajaxmsg('true', 1, $res);
            }
        } else {
            $res = Pay::run('AliApp', $config, $payData);
            ajaxmsg('true', 1, $res);
        }
    }

    public function rechargecheck()
    {
        error_reporting(0);
        if (isset($_POST['trade_no'])) {
            $pay_code = 'alipay';
        } else {
            $pay_code = 'weixin';
        }
        $config = json_decode(Db::name('payment')->where('pay_code', $pay_code)->value('pay_config'), true);
        if ($pay_code == 'weixin') {
            trace(date('Y-m-d H:i:s') . '==微信支付回调==' . var_export($_POST, true), 'pay');
            Notify::run('NotifyWx', $config, [$this, 'rechargenotify']);
        } else {
            trace(date('Y-m-d H:i:s') . '==阿里支付回调==' . var_export($this->request->put(), true), 'pay');
            Notify::run('NotifyAli', $config, [$this, 'rechargenotify']);
        }
    }

    public function rechargenotify($type, $pageparm)
    {
        trace(date('Y-m-d H:i:s') . '==逻辑参数==' . var_export($pageparm, true), 'pay');
        $map                              = [];
        $map['order_sn']                  = $pageparm['out_trade_no'];
        $map['status']                    = 0;
        $user_recharge                    = Db::name("user_recharge")->where($map)->find();
        $user_rechargeData                = [];
        $user_rechargeData['status']      = 1;
        $user_rechargeData['update_time'] = time();
        if ($user_recharge['order_type'] == 1) {
            $user_rechargeData['trade_no'] = $pageparm['trade_no'];
        }
        if ($user_recharge['order_type'] == 2) {
            $user_rechargeData['trade_no'] = $pageparm['transaction_id'];
        }
        if ($user_recharge) {
            try {
                $res = Db::name("user_recharge")->where($map)->update($user_rechargeData);
                (new AccountLog)->accountLog($user_recharge['user_id'], $user_recharge['money'], '用户充值成功', 0, 0, $user_recharge['id'], $pageparm['out_trade_no']);
            } catch (\Exception $e) {
                return $e->getMessage();
            }
            if ($res > 0) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function rechargelog()
    {
        $map            = [];
        $map['user_id'] = $this->islogin();
        $list           = Db::name("user_recharge")->where($map)->order("id desc")->select();
        ajaxmsg('true', 1, $list);
    }

    public function accountlog()
    {
        $map            = [];
        $map['user_id'] = $this->islogin();
        $list           = Db::name("account_log")->where($map)->order("log_id desc")->select();
        ajaxmsg('true', 1, $list);
    }


    public function buycardpay()
    {
        error_reporting(0);
        $user_id    = $this->islogin();
        $user       = Db::name("user")->find($user_id);
        $order_sn   = get_order_sn();
        $order_type = input("order_type", 1);
        $money      = input("money", 0);
        if ($money < 50) {
            ajaxmsg('最小金额10元人民币', 50);
        }
        $rechargeData                 = [];
        $rechargeData['order_sn']     = $order_sn;
        $rechargeData['money']        = $money;
        $rechargeData['num']          = 1;
        $rechargeData['order_amount'] = $money;
        $rechargeData['user_id']      = $user_id;
        $rechargeData['order_type']   = $order_type;
        $rechargeData['note']         = input('note', '');
        $rechargeData['create_time']  = time();
        $pay_code                     = $order_type == 1 ? 'alipay' : 'weixin';
        $config                       = json_decode(Db::name('payment')->where('pay_code', $pay_code)->value('pay_config'), true);
        if (!$config) {
            ajaxmsg('支付参数错误', 0);
        }
        $id = Db::name("user_buycard")->insertGetId($rechargeData);
        if (!$id) {
            ajaxmsg('支付订单生成失败', 0);
        }
        $payData               = [
            'subject'      => '购买卡片支付订单',
            'out_trade_no' => $rechargeData['order_sn'],
            'amount'       => $rechargeData['money'],
        ];
        $payData['notify_url'] = Request::domain() . '/api/apicloud/buycardcheck';
        $jsapi                 = input('jsapi', 0);//0：app支付，1：公众号支付，2：小程序支付

        if ($order_type == 2) {

            if ($jsapi == 0) {
                $appwxpayConfig   = config('appwxpayConfig');
                $config['appid']  = $appwxpayConfig['AppID'];
                $config['secret'] = $appwxpayConfig['AppSecret'];
                $res              = Pay::run('WxApp', $config, $payData);
            }
            if ($jsapi == 1) {
                $config['appid']        = config('wapwxpayConfig')['appid'];
                $config['secret']       = config('wapwxpayConfig')['secret'];
                $config['mchid']        = config('wapwxpayConfig')['mch_id'];
                $config['key']          = config('wapwxpayConfig')['key'];
                $config['report_level'] = 0;
                $payData['openid']      = $user['openid'];
                $res                    = Pay::run('WxJspay', $config, $payData);
            }
            if ($jsapi == 2) {
                $config['appid']        = config('miniwxpayConfig')['appid'];
                $config['secret']       = config('miniwxpayConfig')['secret'];
                $config['mchid']        = config('miniwxpayConfig')['mch_id'];
                $config['key']          = config('miniwxpayConfig')['key'];
                $config['report_level'] = 0;
                $payData['openid']      = $user['miniid'];
                $res                    = Pay::run('WxJspay', $config, $payData);
            }
            if (isset($res['ret']) && isset($res['msg'])) {
                ajaxmsg($res['msg'], 0);
            } else {
                ajaxmsg('true', 1, $res);
            }
        } else {
            $res = Pay::run('AliApp', $config, $payData);
            ajaxmsg('true', 1, $res);
        }
    }

    public function buycardcheck()
    {
        error_reporting(0);
        if (isset($_POST['trade_no'])) {
            $pay_code = 'alipay';
        } else {
            $pay_code = 'weixin';
        }
        $config = json_decode(Db::name('payment')->where('pay_code', $pay_code)->value('pay_config'), true);
        if ($pay_code == 'weixin') {
            trace(date('Y-m-d H:i:s') . '==微信支付回调==' . var_export($_POST, true), 'pay');
            Notify::run('NotifyWx', $config, [$this, 'buycardnotify']);
        } else {
            trace(date('Y-m-d H:i:s') . '==阿里支付回调==' . var_export($this->request->put(), true), 'pay');
            Notify::run('NotifyAli', $config, [$this, 'buycardnotify']);
        }
    }

    public function buycardnotify($type, $pageparm)
    {
        trace(date('Y-m-d H:i:s') . '==逻辑参数==' . var_export($pageparm, true), 'pay');
        $map                              = [];
        $map['order_sn']                  = $pageparm['out_trade_no'];
        $map['status']                    = 0;
        $user_recharge                    = Db::name("user_buycard")->where($map)->find();
        $user_rechargeData                = [];
        $user_rechargeData['status']      = 1;
        $user_rechargeData['update_time'] = time();
        if ($user_recharge['order_type'] == 1) {
            $user_rechargeData['trade_no'] = $pageparm['trade_no'];
        }
        if ($user_recharge['order_type'] == 2) {
            $user_rechargeData['trade_no'] = $pageparm['transaction_id'];
        }

        $res = 0;
        if ($user_recharge) {
            Db::startTrans();
            try {
                Db::name("user_buycard")->where($map)->update($user_rechargeData);
                #创建对应礼品卡
                $time         = time();
                $over_time    = $time + 86400 * 365;
                $giftcardData = [];
                for ($i = 0; $i < $user_recharge['num']; $i++) {
                    $giftcardData[$i] = [
                        'price'       => $user_recharge['money'],
                        'cash'        => $user_recharge['money'],
                        'card_no'     => 'ENEK' . mt_rand(100000000000, 999999999999),
                        'password'    => createpwd(16),
                        'start_time'  => $time,
                        'over_time'   => $over_time,
                        'create_time' => $time,
                        'buyid'       => $user_recharge['user_id'],
                        'type'        => 2,//1：自动发放，2：自助购买，3：赠送
                    ];
                }

                Db::name('giftcard')->insertAll($giftcardData);
                $res = 1;
                Db::commit();
            } catch (\Exception $e) {
                $res = 0;
                Db::rollback();
            }
            if ($res > 0) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function exchange()
    {
        $ad_code        = input("ad_code", 'wapIndex');
        $data['adlist'] = Db::name("ads")
            ->where("ad_code", "=", $ad_code)
            ->where("enabled", "=", 1)
            ->select();

        $sort  = input("sort", 'default');
        $order = input('order', 'desc');


        $map    = [];
        $page   = input('page', 1);
        $rows   = input('rows', 100);
        $cat_id = input('cat_id', 0);
        if ($cat_id > 0) {
            $map[] = ['b.cat_id', '=', $cat_id];
        }
        $map[] = ['b.is_on_sale', '=', 1];
        $map[] = ['b.is_show', '=', 1];
        $map[] = ['b.review_status', '=', 2];
        switch ($sort) {
            case 'exchange_integral':
                $orderby           = "a.exchange_integral {$order}";
                $data['goodsList'] = Db::name("exchange_goods")->alias("a")
                    ->join("goods b", "a.goods_id=b.goods_id")
                    ->join("category c", "b.cat_id=c.cat_id")
                    ->field("a.*,b.goods_name,b.goods_sn,b.store_id,b.user_cat,b.cat_id,b.original_img,b.shop_price")
                    ->where("a.is_exchange=1 and a.review_status=2")
                    ->where($map)
                    ->order($orderby)
                    ->page($page, $rows)
                    ->select();
                break;
            case 'sale_num':
                $orderby           = "a.sale_num {$order}";
                $data['goodsList'] = Db::name("exchange_goods")->alias("a")
                    ->join("goods b", "a.goods_id=b.goods_id")
                    ->join("category c", "b.cat_id=c.cat_id")
                    ->field("a.*,b.goods_name,b.goods_sn,b.store_id,b.user_cat,b.cat_id,b.original_img,b.shop_price")
                    ->where("a.is_exchange=1 and a.review_status=2")
                    ->where($map)
                    ->order($orderby)
                    ->page($page, $rows)
                    ->select();
                break;
            default:
                $data['goodsList'] = Db::name("exchange_goods")->alias("a")
                    ->join("goods b", "a.goods_id=b.goods_id")
                    ->join("category c", "b.cat_id=c.cat_id")
                    ->field("a.*,b.goods_name,b.goods_sn,b.store_id,b.user_cat,b.cat_id,b.original_img,b.shop_price")
                    ->where("a.is_exchange=1 and a.review_status=2")
                    ->where($map)
                    ->page($page, $rows)
                    ->select();
                break;
        }

        $data['tuijianList'] = Db::name("exchange_goods")->alias("a")
            ->join("goods b", "a.goods_id=b.goods_id")
            ->join("category c", "b.cat_id=c.cat_id")
            ->field("a.*,b.goods_name,b.goods_sn,b.store_id,b.user_cat,b.cat_id,b.original_img,b.shop_price")
            ->where("a.is_exchange=1 and a.review_status=2 and b.is_on_sale=1 and b.is_show=1 and b.review_status=2")
            ->order("a.sale_num desc")
            ->limit(10)
            ->select();
        $data['topList']     = Db::name("exchange_goods")->alias("a")
            ->join("goods b", "a.goods_id=b.goods_id")
            ->join("category c", "b.cat_id=c.cat_id")
            ->field("a.*,b.goods_name,b.goods_sn,b.store_id,b.user_cat,b.cat_id,b.original_img,b.shop_price")
            ->where("a.is_exchange=1 and a.review_status=2 and b.is_on_sale=1 and b.is_show=1 and b.review_status=2")
            ->order("a.sale_num desc")
            ->limit(100)
            ->select();

        $data['total'] = Db::name("exchange_goods")->alias("a")
            ->join("goods b", "a.goods_id=b.goods_id")
            ->where("a.is_exchange=1 and a.review_status=2 and b.is_on_sale=1 and b.is_show=1 and b.review_status=2")
            ->where($map)
            ->count();

        $data['categoryList'] = Db::name("exchange_goods")->alias("a")
            ->join("goods b", "a.goods_id=b.goods_id")
            ->join("category c", "b.cat_id=c.cat_id")
            ->field("c.*")
            ->group("b.cat_id")
            ->select();

        ajaxmsg('true', 1, $data);
    }

    public function exorder()
    {
        $orderModel = new Order();
        $user_id    = $this->islogin();
        $goods_id   = input("goods_id", 0);
        $goods_num  = input("goods_num", 0);
        $address    = json_decode(input('address'), true);
        if (!$address['address_id']) {
            ajaxmsg('收货地址不存在', 0);
        }
        $exchange_goods = Db::name("exchange_goods")->where("goods_id={$goods_id}")->find();
        $user           = Db::name("user")->where("user_id={$user_id}")->find();
        if ($user['points'] < $exchange_goods['exchange_integral'] * $goods_num) {
            ajaxmsg('当前积分不够', 0);
        }
        $res = $orderModel->exorder($user_id, $goods_id, $goods_num, $address);
        if ($res > 0) {
            ajaxmsg('兑换成功', 1);
        } else {
            ajaxmsg('兑换失败', 0);
        }
    }

    public function storeindex()
    {
        $this->store_id = input('store_id', 29);
        $store          = Db::name('store')->where('store_id', $this->store_id)->find();

        $store['collectCount'] = Db::name("store_collect")->where('store_id', '=', $this->store_id)->count();
        $order_count           = \app\seller\model\Order::whereIn('order_status', '0,1,2,4')->group('order_status')->column('count(order_id) as count', 'order_status');

        $store['store_bind_category'] = Db::name("store_bind_category")->alias("a")
            ->leftJoin("category b", "a.cat_id=b.cat_id")
            ->field("b.*")
            ->where("a.store_id", "=", $store['store_id'])
            ->select();

        $msg_count = Db::name('store_msg')->where(['store_id' => $this->store_id, 'open' => 0])->count();
        $help      = Db::name('article_cat a')->join('article b', 'a.cat_id=b.cat_id')->where('a.cat_name LIKE "%帮助%"')->limit(6)->column('title', 'article_id');

        $map   = [];
        $map[] = ['is_on_sale', '=', 1];
        $map[] = ['is_show', '=', 1];
        $map[] = ['review_status', '=', 2];
        $map[] = ['store_id', '=', $this->store_id];
        $goods = Db::name('goods')->where($map)->column('is_on_sale,review_status,is_delete', 'goods_id');
        if ($goods) {
            $goods_count['is_delete'] = $goods_count['review_status'] = 0;
            foreach ($goods as $index => $good) {
                if ($good['is_delete']) {
                    $goods_count['is_delete']++;//商品回收站 数量
                    unset($goods[$index]);
                } else if ($good['review_status'] == 1) {
                    $goods_count['review_status']++;
                    unset($goods[$index]);
                }
            }
        }
        $goods_count['is_on_sale'] = Db::name("goods")->where($map)->where('is_on_sale', '=', 1)->count("goods_id");
        $goods_count['is_new']     = Db::name("goods")->where($map)->where('is_new', '=', 1)->count("goods_id");
        $goods_count['is_best']    = Db::name("goods")->where($map)->where('is_best', '=', 1)->count("goods_id");
        $bestlist                  = Db::name('goods')
            ->where($map)
            ->where('store_best', '=', 1)
            ->field("goods_id,goods_name,original_img,total_sale_num,shop_price,total_sale_num,goods_number")
            ->order('total_sale_num DESC')
            ->limit(6)
            ->select();
        $hotslist                  = Db::name('goods')
            ->where($map)
            ->where('store_hot', '=', 1)
            ->field("goods_id,goods_name,original_img,total_sale_num,shop_price,total_sale_num,goods_number")
            ->order('total_sale_num DESC')
            ->limit(6)
            ->select();
        $newslist                  = Db::name('goods')
            ->where($map)
            ->where('store_new', '=', 1)
            ->field("goods_id,goods_name,original_img,total_sale_num,shop_price,total_sale_num,goods_number")
            ->order('total_sale_num DESC')
            ->limit(6)
            ->select();
        $yesterday                 = strtotime('yesterday');
        $store_data                = Order::where([['pay_status', '=', 1], ['pay_time', '>', $yesterday]])
            ->field('count(order_id) as order_num,(sum(order_amount)+sum(user_money)) as amount,FROM_UNIXTIME(pay_time,"%Y-%m-%d") as days,IF(_source="PC","pc","mobile") as `sourceb`')
            ->group('days,`sourceb`')->order('days DESC')
            ->select()->toArray();

        $report = [
            'mobile' => [
                'today'     => ['order_num' => null, 'amount' => 0],
                'yesterday' => ['order_num' => null, 'amount' => 0],
            ],
            'pc'     => [
                'today'     => ['order_num' => null, 'amount' => 0],
                'yesterday' => ['order_num' => null, 'amount' => 0],
            ],
        ];
        if ($store_data) {
            $days = [
                date('Y-m-d', $yesterday) => 'yesterday',
                date('Y-m-d')             => 'today',
            ];
            foreach ($store_data as $index => $s_data) {
                $report[$s_data['sourceb']][$days[$s_data['days']]]['order_num'] = $s_data['order_num'];
                $report[$s_data['sourceb']][$days[$s_data['days']]]['amount']    = $s_data['amount'];
            }
        }
        $today            = array_column($report, 'today');
        $today_amount_sum = array_sum(array_column($today, 'amount'));
        $today_order_sum  = array_sum(array_column($today, 'order_num'));
        $userview         = Db::name('goods_click')->where(['store_id' => $this->store_id, 'date' => date('Y-m-d')])->count();//统计pv时,count('click_count')
        $userview         = $userview > 0 ? $userview : 1;
        $conversion_rate  = round($today_order_sum / $userview * 100, 3);
        $data             = [
            'store'            => $store,
            'order_count'      => $order_count,
            'status_text'      => config('order_status'),
            'msg_count'        => $msg_count,
            'goods_count'      => $goods_count ?? [],
            'help'             => $help,
            'bestlist'         => $bestlist,
            'hotslist'         => $hotslist,
            'newslist'         => $newslist,
            'report'           => $report,
            'today_amount_sum' => $today_amount_sum,
            'conversion_rate'  => $conversion_rate,

        ];
        ajaxmsg('true', 1, $data);
    }

    public function storelists()
    {
        $page     = input('page', 1);
        $rows     = input('rows', 100);
        $province = input('province', 0);
        $cat_id   = input('cat_id', 0);


        $map   = [];
        $map[] = ['is_delete', '=', 0];
        $map[] = ['status', '=', 1];
        $map[] = ['store_close', '=', 1];
        if ($province > 0) {
            $map[] = ['province', '=', $province];
        }
        $prefix = config('database.prefix');
        if ($cat_id > 0) {
            $sql = "SELECT a.*,storeCount FROM {$prefix}store a 
                LEFT JOIN 
                (SELECT store_id,COUNT(store_id) as storeCount FROM {$prefix}store_collect GROUP BY store_id ORDER BY store_id desc) as b ON a.store_id=b.store_id
                LEFT JOIN {$prefix}store_bind_category c ON c.store_id=a.store_id
                WHERE c.cat_id={$cat_id} AND a.store_close=1
                GROUP BY a.store_id 
                ORDER BY a.store_id DESC";
        } else {
            $sql = "SELECT a.*,storeCount FROM {$prefix}store a 
                LEFT JOIN 
                (SELECT store_id,COUNT(store_id) as storeCount FROM {$prefix}store_collect GROUP BY store_id ORDER BY store_id desc) as b ON a.store_id=b.store_id
                LEFT JOIN {$prefix}store_bind_category c ON c.store_id=a.store_id
                WHERE a.store_close=1 
                GROUP BY a.store_id    
                ORDER BY a.store_id DESC";
        }
        $list       = Db::query($sql);
        $goodsModel = new GoodsModel;
        $listTwo    = $goodsModel
            ->field("goods_id,store_id,goods_name,original_img,total_sale_num,shop_price,total_sale_num,goods_number")
            ->whereIn('store_id', array_column($list, 'store_id'))
            ->order("store_id desc")
            ->select()->toArray();
        foreach ($list as $k => $v) {
            $ids      = array_column($listTwo, 'store_id');
            $cat_ids  = [$v['store_id']];
            $valCount = array_count_values($ids);
            if (array_key_exists($v['store_id'], $valCount)) {
                $list[$k]['goodslist'] = array_slice(
                    $listTwo,
                    array_search($v['store_id'], $ids),
                    $valCount[$v['store_id']]
                );
            }
        }
        $catelist         = Db::name("category")->alias("a")
            ->join("store_bind_category b", "a.cat_id=b.cat_id")
            ->join('store c', 'b.store_id=c.store_id')
            ->field("a.*")
            ->where("a.parent_id", 0)
            ->where('c.store_close', 1)
            ->group("a.cat_id")
            ->select();
        $data             = [];
        $data['list']     = $list;
        $data['catelist'] = $catelist;

        ajaxmsg('ok', 1, $data);
    }

    public function storegoods()
    {
        $store_id = input('store_id', 0);
        $cat_id   = input('cat_id', 0);
        $map      = [];
        $map[]    = ['store_id', "=", $store_id];
        if ($cat_id > 0) {
            $map[] = ['user_cat', "=", $cat_id];
        }

        $map[] = ['is_on_sale', '=', 1];
        $map[] = ['is_show', '=', 1];
        $map[] = ['review_status', '=', 2];


        $list = Db::name('goods')
            ->where($map)
            ->field("goods_id,goods_name,original_img,total_sale_num,shop_price,total_sale_num,goods_number")
            ->order('total_sale_num DESC')
            ->select();
        ajaxmsg('true', 1, $list);
    }

    public function storecatelist()
    {
        $this->store_id = input('store_id', 33);

        $list = Db::name("store_category")
            ->where("store_id", "=", $this->store_id)
            ->where("parent_id", "=", 0)
            ->order("cat_id desc")
            ->select();

        $listTwo = Db::name("store_category")
            ->where('parent_id', 'IN', array_column($list, 'cat_id'))
            ->order("parent_id desc")
            ->select();
        foreach ($list as $k => $v) {
            $ids      = array_column($listTwo, 'parent_id');
            $valCount = array_count_values($ids);
            if (array_key_exists($v['cat_id'], $valCount)) {
                $list[$k]['children'] = array_slice(
                    $listTwo,
                    array_search($v['cat_id'], $ids),
                    $valCount[$v['cat_id']]
                );
            }
        }

        ajaxmsg('true', 1, $list);
    }

    public function skgoods()
    {
        $sklist = Db::name("seckill")
            ->field("*,FROM_UNIXTIME(start_time,'%H:%i') as start_time_temp,
            FROM_UNIXTIME(start_time,'%Y-%m-%d %H:%i:%S') as start_time_cn,
            FROM_UNIXTIME(end_time,'%Y-%m-%d %H:%i:%S') as end_time_cn")
            ->order("sec_id asc")
            ->select();

        $cat_id    = input('cat_id', 0);
        $gmap      = [];
        $gmap[]    = ['a.sec_id', 'IN', array_column($sklist, 'sec_id')];
        $goodsList = Db::name('seckill_goods')->alias("a")
            ->join("goods b", "a.goods_id=b.goods_id", "LEFT")
            ->field("a.*,convert(a.sold_num/a.sec_num,decimal(15,2))*100 as saleper, 
            b.goods_name,b.shop_price,b.cat_id,b.store_id,b.goods_sn,b.brand_id,b.keywords,b.original_img")
            ->where($gmap)
            ->order('a.sec_id asc')
            ->select();


        $gids   = implode(',', array_column($goodsList, 'goods_id'));
        $cmap   = [];
        $cmap[] = ['a.goods_id', 'in', $gids];
        $clist  = Db::name("goods")->alias("a")
            ->join("category b", "a.cat_id=b.cat_id", 'LEFT')
            ->field("a.cat_id,b.cat_name")
            ->where($cmap)
            ->group("a.cat_id")
            ->order("b.sort_order desc")
            ->select();
        foreach ($sklist as $k => $v) {
            if ($v['start_time'] <= time() && time() <= $v['end_time']) {
                $sklist[$k]['curr'] = 1;
            } else {
                $sklist[$k]['curr'] = 0;
            }
            $ids      = array_column($goodsList, 'sec_id');
            $valCount = array_count_values($ids);
            if (isset($valCount[$v['sec_id']])) {
                $sklist[$k]['goods_list'] = array_slice(
                    $goodsList,
                    array_search($v['sec_id'], $ids),
                    $valCount[$v['sec_id']]
                );
            }
        }
        $data          = [];
        $data['list']  = $sklist;
        $data['clist'] = $clist;
        ajaxmsg('true', 1, $data);
    }

    public function skdetail()
    {
        $id         = input('id', 50467);
        $goodsModel = new GoodsModel();
        $goods      = $goodsModel->get($id);
        if (!$goods) {
            ajaxmsg('您要查看的商品可能下架了,逛逛其它吧', 0);
        }
        $market_goods = Db::name("seckill_goods")->alias("a")
            ->join("seckill b", "a.sec_id=b.sec_id", "LEFT")
            ->field("a.*,FROM_UNIXTIME(b.end_time,'%Y-%m-%d %H:%i:%S') as end_time_cn,
            b.start_time,b.end_time")
            ->where("goods_id={$id}")
            ->find();
        if (!$market_goods) {
            ajaxmsg('您要查看的商品可能下架了,逛逛其它吧', 0);
        }
        //$goods['is_collect'] = $goodsModel->countFavorite($user_id);
        $goods['gallery']      = $goodsModel->getGallery($goods->goods_id);
        $goods['product']      = $goodsModel->getProduct($goods->goods_id);
        $goods['spec']         = $goodsModel->getSpec($goods);
        $goods['market_goods'] = $market_goods;

        $token = input("token");
        if ($token) {
            $user                     = Db::name("user")->where("token", "=", $token)->find();
            $goods['user']            = $user;
            $goods['cartGoodsNumber'] = Db::name("cart")->where("user_id", "=", $user['user_id'])->sum("goods_num");

            $goods['iscollect']    = Db::name("goods_collect")
                ->where("goods_id", "=", $id)
                ->where("user_id", "=", $user['user_id'])
                ->count();
            $goods['user_address'] = Db::name("user_address")
                ->where("user_id", "=", $user['user_id'])
                ->order("is_default desc")
                ->find();
            $goods['address']      = Db::name("user_address")
                ->where("user_id", "=", $user['user_id'])
                ->order("is_default desc")
                ->find();
        } else {
            $goods['user']            = [];
            $goods['cartGoodsNumber'] = 0;
            $goods['iscollect']       = 0;
            $goods['user_address']    = [];
        }
        ajaxmsg('true', 1, $goods);
    }

    public function paimaigoods()
    {
        $map    = [];
        $map[]  = ['b.is_delete', 'eq', 0];
        $map[]  = ['b.is_on_sale', 'eq', 1];
        $map[]  = ['a.status', 'eq', 1];
        $cat_id = input('cat_id', 0);

        if ($cat_id > 0) {
            $map[] = ['b.cat_id', 'eq', $cat_id];
        }
        $page = input('page', 1);
        $rows = input('rows', 100);


        $data          = [];
        $data['list']  = Db::name("paimai_goods")->alias("a")
            ->leftJoin("goods b", "b.goods_id=a.goods_id")
            ->field("b.goods_name,b.shop_price,b.cat_id,b.store_id,b.goods_sn,b.brand_id,b.keywords,b.original_img,a.*")
            ->where($map)
            ->order("a.id desc")
            ->page($page, $rows)
            ->select();
        $data['total'] = Db::name("paimai_goods")->alias("a")
            ->leftJoin("goods b", "b.goods_id=a.goods_id")
            ->where($map)
            ->count();

        $data['categoryList'] = Db::name("paimai_goods")->alias("a")
            ->join("goods b", "a.goods_id=b.goods_id")
            ->join("category c", "b.cat_id=c.cat_id")
            ->field("c.*")
            //->where("c.parent_id","=",0)
            ->group("b.cat_id")
            ->select();
        ajaxmsg('true', 1, $data);
    }

    public function pmdetail()
    {
        $id         = input('goods_id', 25729);
        $goodsModel = new GoodsModel();
        $goods      = $goodsModel->get($id);
        if (!$goods) {
            ajaxmsg('您要查看的商品可能下架了,逛逛其它吧', 0);
        }
        $market_goods = Db::name("paimai_goods")->where("goods_id", "=", $id)->where("status", "=", 1)->find();
        if (!$market_goods) {
            ajaxmsg('您要查看的商品可能下架了,逛逛其它吧', 0);
        }
        $goods['gallery']      = Db::name("goods_gallery")->where("goods_id", "=", $id)->select();
        $goods['product']      = $goodsModel->getProduct($goods->goods_id);
        $goods['spec']         = $goodsModel->getSpec($goods);
        $goods['market_goods'] = $market_goods;

        $token = input("token");
        if ($token) {
            $user                     = Db::name("user")->where("token", "=", $token)->find();
            $goods['user']            = $user;
            $goods['cartGoodsNumber'] = Db::name("cart")->where("user_id", "=", $user['user_id'])->sum("goods_num");

            $goods['iscollect']    = Db::name("goods_collect")
                ->where("goods_id", "=", $id)
                ->where("user_id", "=", $user['user_id'])
                ->count();
            $goods['user_address'] = Db::name("user_address")
                ->where("user_id", "=", $user['user_id'])
                ->order("is_default desc")
                ->find();
            $goods['address']      = Db::name("user_address")
                ->where("user_id", "=", $user['user_id'])
                ->order("is_default desc")
                ->find();

            $myhouse = Db::name("paimai_house")
                ->where("user_id", "=", $user['user_id'])
                ->where("goods_id", "=", $id)
                ->where("status", "=", 1)
                ->find();
            if ($myhouse) {
                $goods['myhouse'] = $myhouse;
            } else {
                $goods['myhouse'] = ['id' => 0];
            }
        } else {
            $goods['user']            = [];
            $goods['cartGoodsNumber'] = 0;
            $goods['iscollect']       = 0;
            $goods['user_address']    = [];
            $goods['myhouse']         = ['id' => 0];
        }
        $goods['paimai_house'] = Db::name("paimai_house")->alias("a")
            ->leftJoin("goods b", "a.goods_id=b.goods_id")
            ->leftJoin("user c", "a.user_id=c.user_id")
            ->where("a.goods_id", "=", $id)
            ->field("a.*,c.avatar,(a.create_time+a.time_down*60) as nexttime,unix_timestamp(now()) as uptime")
            ->select();
        ajaxmsg('true', 1, $goods);
    }

    #任务调度
    public function paimaihouseTask()
    {
        $rows = 0;
        Db::startTrans();
        try {

            #要设置竞拍开始的房间
            $list = Db::name("paimai_house")
                ->where("status=3")
                ->where("create_time+time_down*60)<unix_timestamp(now()")
                ->where("unix_timestamp(now())<(create_time+(time_down+time_total)*60)")
                ->select();
            $rows = Db::name("paimai_house")->where("id", "IN", array_column($list, 'id'))->update(['status' => 1]);
            Log::record(date('Y-m-d H:i:s') . ' 更新了: ' . $rows . '间房间，设置为竞拍开始！！');

            #要设置即将失效的房间
            $list = Db::name("paimai_house")->where("status=1")->where("unix_timestamp(now())>(create_time+(time_down+time_total)*60)")->select();
            $rows = Db::name("paimai_house")->where("id", "IN", array_column($list, 'id'))->update(['status' => 2]);

            #设置失效时房角有出价，价高者得
            if (count($list) > 0) {
                foreach ($list as $k => $v) {
                    $rlist = self::$redis->get('House' . $v['id'])['list'];
                    if ($rlist) {
                        $info     = $rlist[count($rlist) - 1];
                        $order_sn = (new OrderModel())->addOrderForAuction($info['user_id'], $info['goods_id'], $info['min_price'], 1, $info['house_id']);
                        if ($order_sn) {
                            Db::name("paimai_house")->where("id", "=", $v['id'])->update(['status' => 2]);
                        } else {
                            Db::name("paimai_house")->where("id", "=", $v['id'])->update(['status' => 0]);
                        }
                    }
                }
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
        }
        if ($rows > 0) {
            ajaxmsg('更新了' . $rows . '间房', 1);
        } else {
            ajaxmsg('暂无更新', 0);
        }
    }

    public function jlistInit()
    {
        $id          = input("id");
        $house_jlist = self::$redis->get('House' . $id);
        if ($house_jlist && isset($house_jlist['list'])) {
            $jlist = $house_jlist['list'];
        } else {
            $jlist = [];
        }

        $data                 = [];
        $data['jlist']        = $jlist;
        $data['paimai_house'] = Db::name("paimai_house")->find($id);

        ajaxmsg("true", 1, $data);
    }

    public function orderupaddress()
    {

        $user_id    = $this->islogin();
        $order_id   = input("order_id");
        $address_id = input("address_id", 0);
        $address    = Db::name("user_address")->find($address_id);
        $orderData  = [
            'consignee' => $address['consignee'], // 收货人
            'country'   => $address['country'],//城市id,
            'province'  => $address['province'],//省份id,
            'city'      => $address['city'],//城市id,
            'district'  => $address['district'],//县,
            'address'   => $address['address'],//详细地址,
            'mobile'    => $address['mobile'],//手机,
            'zipcode'   => $address['zipcode'],//邮编
            'email'     => $address['email'],//邮箱,
        ];
        $res        = Db::name("order")
            ->where("order_id", "=", $order_id)
            ->where("user_id", "=", $user_id)
            ->update($orderData);
        if ($res > 0) {
            ajaxmsg('true', 1);
        } else {
            ajaxmsg('false', 0);
        }
    }

    public function createHouse()
    {
        $user_id  = $this->islogin();
        $pageparm = input("request.");

        $map   = [];
        $map[] = ['user_id', '=', $user_id];
        $map[] = ['goods_id', '=', $pageparm['goods_id']];
        $con   = Db::name("paimai_house")
            ->where($map)
            ->where("status=1 or status=3")
            ->count();
        if ($con) {
            ajaxmsg('正在竞拍中，完成后重新创建！', 0);
        }
        $paimai_house_Data                = [];
        $paimai_house_Data['user_id']     = $user_id;
        $paimai_house_Data['goods_id']    = $pageparm['goods_id'];
        $paimai_house_Data['autop_num']   = $pageparm['autop_num'];
        $paimai_house_Data['house_num']   = $pageparm['house_num'];
        $paimai_house_Data['time_down']   = $pageparm['time_down'];
        $paimai_house_Data['time_total']  = $pageparm['time_total'];
        $paimai_house_Data['status']      = 3;//默认暂未开始
        $paimai_house_Data['create_time'] = time();
        $id                               = Db::name("paimai_house")->insertGetId($paimai_house_Data);

        $paimai_house_Data['id'] = $id;
        if ($id > 0) {
            ajaxmsg('创建成功', 1, $paimai_house_Data);
        } else {
            ajaxmsg('创建失败', 0);
        }
    }

    #进入房间-更新房间数据信息
    public function houseinfo()
    {
        $user_id = $this->islogin();
        $id      = input("id", 122);//房间ID

        $map = [];
        //$map[]        = ['status', '=', 1];
        $paimai_house = Db::name("paimai_house")->where($map)->find($id);
        if (!$paimai_house) {
            ajaxmsg('拍卖已结束', 0);
        }
        if ($paimai_house['status'] == 1) {
            $paimai_house['end_time'] = $paimai_house['create_time'] + ($paimai_house['time_down'] + $paimai_house['time_total']) * 60 - time();
        }
        if ($paimai_house['status'] == 3) {
            $paimai_house['end_time'] = $paimai_house['create_time'] + $paimai_house['time_down'] * 60 - time();
        }
        $master               = Db::name("user")->find($paimai_house['user_id']);
        $peopleCount          = Db::name("paihouse_people")->where("house_id", '=', $id)->count();
        $data                 = [];
        $data['paimai_house'] = $paimai_house;
        $data['user']         = Db::name("user")->find($user_id);
        $data['master']       = $master;
        $data['peopleCount']  = $peopleCount;
        $map                  = [];
        $map[]                = ['a.is_delete', 'eq', 0];
        $map[]                = ['a.is_on_sale', 'eq', 1];
        //$map[]                = ['b.status', 'eq', 1];
        $map[]            = ['b.id', 'eq', $id];
        $data['goods']    = Db::name("goods")->alias("a")
            ->leftJoin("paimai_house b", "a.goods_id=b.goods_id")
            ->leftJoin("paimai_goods c", "a.goods_id=c.goods_id")
            ->field("a.goods_name,a.shop_price,a.market_price,a.goods_sn,a.brand_id,a.keywords,a.original_img,b.*,c.max_price,c.min_price")
            ->where($map)
            ->find();
        $data['order_id'] = Db::name("order")->where("order_prom_id", "=", $id)->value("order_id");
        ajaxmsg('true', 1, $data);
    }

    #离开房间-更新房间信息数据
    public function houseleave()
    {
        $user_id = $this->islogin();
        $id      = input("id", 0);//房间ID
        $con     = Db::name("paihouse_people")->where("user_id", '=', $user_id)->count();
        if ($con > 0) {
            Db::name("paihouse_people")->where("user_id", '=', $user_id)->delete();
        }
        ajaxmsg('离开房间', 1);
    }

    #出价操作-更新房间信息数据
    public function rushlogadd()
    {
        $user_id = $this->islogin();
        $id      = input("id", 102);//房间ID

        $map          = [];
        $map[]        = ['status', '=', 1];
        $paimai_house = Db::name("paimai_house")->where($map)->find($id);
        if (!$paimai_house) {
            ajaxmsg('拍卖已结束或则已流拍', 0);
        }
        $user         = Db::name("user")->find($user_id);
        $paimai_goods = Db::name("paimai_house")->alias("a")
            ->leftJoin("paimai_goods b", "a.goods_id=b.goods_id")
            ->where("a.id", "=", $id)
            ->field("b.*")
            ->find();

        $houseData = [];
        $list      = self::$redis->get('House' . $id)['list'];
        if (isset($list) && is_array($list) > 0) {
            $lastarr = $list[count($list) - 1];
            if ($lastarr['min_price'] == $paimai_goods['max_price']) {
                ajaxmsg('出价失败', 0);
            }
            $min_price = $lastarr['min_price'] + 1;
            $itemarr   = [
                'user_id'     => $user_id,
                'user_name'   => $user['user_name'],
                'mobile'      => $user['mobile'],
                'avatar'      => $user['avatar'],
                'house_id'    => $id,
                'goods_id'    => $lastarr['goods_id'],
                'min_price'   => $min_price,
                'create_time' => date("Y-m-d H:m:s"),
            ];
            array_push($list, $itemarr);
        } else {
            $list      = [];
            $min_price = $paimai_goods['min_price'] + 1;
            $list[]    = [
                'user_id'     => $user_id,
                'user_name'   => $user['user_name'],
                'mobile'      => $user['mobile'],
                'avatar'      => $user['avatar'],
                'house_id'    => $id,
                'goods_id'    => $paimai_goods['goods_id'],
                'min_price'   => $min_price,
                'create_time' => date("Y-m-d H:m:s"),
            ];
        }
        $houseData['list'] = $list;
        self::$redis->set('House' . $id, $houseData);


        if ($paimai_goods['max_price'] - $min_price < 1) {
            Db::startTrans();
            try {
                Db::name("paimai_house")->where("id", "=", $id)->update(['status' => 2,]);
                $order_sn = (new OrderModel())->addOrderForAuction($user_id, $paimai_house['goods_id'], $min_price, 1, $paimai_house['id']);
                if ($order_sn) {
                    $houseData['order_id'] = $order_id;
                } else {
                    $houseData['order_id'] = '';
                    throw new Exception('订单生成失败');
                }
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
            }
        }
        ajaxmsg('出价成功', 1, $houseData);
    }

    #出价记录轮训操作
    public function rushloglist()
    {
        $id   = input("id", 102);//房间ID
        $list = self::$redis->get('House' . $id);
        ajaxmsg('true', 1, $list);
    }

    #房间人数统计数据
    public function housepeople()
    {
        $id   = input("id", 102);//房间ID
        $list = Db::name("paihouse_people")->alias("a")
            ->leftJoin("user b", "a.user_id=b.user_id")
            ->field("a.*,b.*")
            ->where("a.house_id", '=', $id)
            ->select();
        ajaxmsg('true', 1, $list);
    }


    public function brand()
    {
        $map      = [];
        $map[]    = ["is_delete", "=", 0];
        $map[]    = ["is_show", "=", 1];
        $map[]    = ["audit_status", "=", 1];
        $keywords = input("keywords", '');
        if ($keywords) {
            $map[] = ['brand_name|brand_first_char', "like", "%{$keywords}%"];
        }
        $page = input('page', 1);
        $rows = input('rows', 20);
        $list = Db::name("brand")
            ->where($map)
            ->order("brand_first_char asc")
            ->page($page, $rows)
            ->select();
        ajaxmsg('true', 1, $list);
    }

    public function hotkeywordadd()
    {
        $search_keywords = Db::name("shop_config")->where("code", "search_keywords")->value('value');
        $data            = explode(',', $search_keywords);;
        ajaxmsg('true', 1, $data);
    }

    public function hotkeyworddel()
    {
        $user_id = $this->islogin();
        self::$redis->rm('userid' . $user_id);
        $data            = [];
        $data['list']    = [];
        $search_keywords = Db::name("shop_config")->where("code", "=", "search_keywords")->value('value');
        $data['hot']     = explode(',', $search_keywords);;
        ajaxmsg('true', 1, $data);
    }


    #优惠券
    public function mycoupon()
    {
        $this->user_id = $this->islogin();
        $map           = [];
        $map[]         = ['a.user_id', '=', $this->user_id];
        $list          = Db::name("coupon_list")->alias("a")
            ->join("coupon b", "a.coupon_id=b.coupon_id", "LEFT")
            ->field("a.*,b.*")
            ->where($map)
            ->group("a.coupon_id")
            ->select();
        $alist         = [];
        $blist         = [];
        $clist         = [];
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
        $data          = [];
        $data['alist'] = $alist;
        $data['blist'] = $blist;
        $data['clist'] = $clist;
        ajaxmsg('true', 1, $data);
    }

    public function article()
    {
        $article_id = input("article_id");
        $info       = Db::name("article")->find($article_id);
        ajaxmsg('true', 1, $info);
    }

    public function weixinnotify()
    {
        trace(date('Y-m-d H:i:s') . '==支付回调==' . var_export($_REQUEST, true), 'wxauth');
        $appwxpayConfig = config('appwxpayConfig');
        $this->config   = [
            'app_id'        => $appwxpayConfig['AppID'],
            'secret'        => $appwxpayConfig['AppSecret'],//开放平台
            'response_type' => 'array',//指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
            'oauth'         => [
                'scopes' => ['snsapi_login'],//开放平台
                //'scopes'   => ['snsapi_userinfo'],
            ],
        ];
        $app            = Factory::officialAccount($this->config);

        $oauth = $app->oauth;
        // 获取 OAuth 授权结果用户信息
        $user                   = $oauth->user();
        $original               = $user->toArray()['original'];
        $userData['user_name']  = $original['nickname'];
        $userData['nick_name']  = $original['nickname'];
        $userData['avatar']     = $original['headimgurl'];
        $userData['appcid']     = $original['openid'];
        $userData['unionid']    = $original['unionid'];
        $userData['reg_time']   = time();
        $userData['last_login'] = time();
        $userData['last_ip']    = Request::ip();
        $token                  = create_token();
        $userData['token']      = $token;
        $info                   = Db::name("user")->where("unionid", "=", $original['unionid'])->find();
        if ($info) {
            Db::name("user")->where("unionid", "=", $original['unionid'])->update($userData);
            $info['token'] = $token;
            ajaxmsg('登录成功', 1, $info);
        } else {
            $user_id = Db::name("user")->insertGetId($userData);
            $info    = Db::name("user")->find($user_id);
            ajaxmsg('登录成功', 1, $info);
        }
    }

    public function addjoinus()
    {
        $pageparm = input("post.");

        unset($pageparm['token']);
        $pageparm['create_time'] = time();
        $res                     = Db::name("joinus")->insertGetId($pageparm);
        if ($res > 0) {
            ajaxmsg('提交成功', 1);
        } else {
            ajaxmsg('提交失败', 0);
        }
    }

    #小程序自动登录
    public function autoLogin()
    {
        $config        = [
            'app_id'        => config('miniwxpayConfig')['appid'],
            'secret'        => config('miniwxpayConfig')['secret'],
            'response_type' => 'array',
        ];
        $encryptedData = input('encryptedData');
        $iv            = input('iv');
        $code          = input('js_code');
        try {
        try {
            $app         = Factory::miniProgram($config);
            $oauth       = $app->auth->session($code);
            $miniid      = $oauth['openid'] ?? '';
            $session_key = $oauth['session_key'];
 
            if (isset($oauth['unionid'])) {
                $unionid = $oauth['unionid'];
                $user    = Db::name("user")->where(['unionid' => $unionid, 'is_validated' => 0])->field('password', true)->find();
                if ($user) {
                    Db::name("user")->where("user_id", $user['user_id'])->update(['miniid' => $miniid, 'wx_session_key' => $session_key]);
                    ajaxmsg("自动登录成功", 1, $user);
                } else {
                    $decryptedData              = $app->encryptor->decryptData($session_key, $iv, $encryptedData);
                    $userData['token']          = create_token();
                    $userData['miniid']         = $decryptedData['openId'];
                    $userData['unionid']        = $decryptedData['unionId'];
                    $userData['wx_session_key'] = $session_key;
                    $userData['sex']            = $decryptedData['gender'];
                    $userData['user_name']      = $decryptedData['nickName'];
                    $userData['nick_name']      = $decryptedData['nickName'];
                    $userData['avatar']         = $decryptedData['avatarUrl'];
                    $userData['reg_time']       = time();
                    $userData['last_login']     = time();
                    $userData['last_ip']        = Request::ip();

                    $user_id = Db::name("user")->insertGetId($userData);
                    $info    = Db::name("user")->field('password', true)->find($user_id);
                    ajaxmsg("自动注册成功", 1, $info);
                }
            } else if ($miniid) {
                $user = Db::name("user")->where(['miniid' => $miniid, 'is_validated' => 0])->field('password', true)->find();
                if ($user) {
                    Db::name("user")->where("user_id", $user['user_id'])->update(['wx_session_key' => $session_key]);
                    ajaxmsg("自动登录成功", 1, $user);
                } else {
                    $decryptedData = $app->encryptor->decryptData($session_key, $iv, $encryptedData);
                    trace(var_export($decryptedData, true));
                    $userData['token']          = create_token();
                    $userData['miniid']         = $decryptedData['openId'];
                    $userData['unionid']        = $decryptedData['unionId'];
                    $userData['wx_session_key'] = $session_key;
                    $userData['sex']            = $decryptedData['gender'];
                    $userData['user_name']      = $decryptedData['nickName'];
                    $userData['nick_name']      = $decryptedData['nickName'];
                    $userData['avatar']         = $decryptedData['avatarUrl'];
                    $userData['reg_time']       = time();
                    $userData['last_login']     = time();
                    $userData['last_ip']        = Request::ip();

                    $user_id = Db::name("user")->insertGetId($userData);
                    $info    = Db::name("user")->field('password', true)->find($user_id);
                    ajaxmsg("自动注册成功", 1, $info);
                }
            } else {
                ajaxmsg("自动注册失败", 0);
            }

        } catch (\Exception $e) {
            trace('小程序登录失败:' . $e->getMessage());
            ajaxmsg('登录失败,请稍后再试', 0);
        }
    }

    public function bindMobile()
    {
        $token       = input('token');
        $user_id     = $this->islogin();
        $data        = input('encryptedData');
        $iv          = input('iv');
        $code        = input('code');
        $config      = [
            'app_id'        => config('miniwxpayConfig')['appid'],
            'secret'        => config('miniwxpayConfig')['secret'],
            'response_type' => 'array',
        ];
        $session_key = Db::name('user')->where('user_id', $user_id)->value('wx_session_key');
        $app         = Factory::miniProgram($config);
        if ($code != 'none') {
            $session_key = $app->auth->session($code);
        } else if (!$session_key) {
            ajaxmsg('请重新登录', 401);
        }
        $decryptedData = $app->encryptor->decryptData($session_key, $iv, $data);
        trace('绑定：' . json_encode($decryptedData));

        $olduser = Db::name('user')
            ->where('mobile', $decryptedData['phoneNumber'])
            ->where('user_id', '<>', $user_id)->find();
        if ($olduser) {
            //合并账号
            $newuser = Db::name('user')->where('user_id', $user_id)->find();
            trace('自动合并账号old_user：' . json_encode($olduser, 320) . 'new_user:' . json_encode($newuser, 320));
            $newuser['mobile']         = $decryptedData['phoneNumber'];
            $newuser['user_money']     = Db::raw('user_money+' . $olduser['user_money']);
            $newuser['frozen_money']   = Db::raw('frozen_money+' . $olduser['frozen_money']);
            $newuser['points']         = Db::raw('points+' . $olduser['points']);
            $newuser['password']       = $olduser['password'];
            $newuser['user_name']      = $olduser['user_name'];
            $newuser['wx_session_key'] = $session_key;

            Db::name('user')->where('user_id', $user_id)->update($newuser);
            Db::name('user')->where('user_id', $olduser['user_id'])->update([
                'mobile'       => '',
                'password'     => '',
                'user_name'    => $newuser['miniid'],//关联新账号
                'is_validated' => 1,
                'user_money'   => 0,
                'frozen_money' => 0,
                'points'       => 0
            ]);
            Db::name('cart')->where('user_id', $olduser['user_id'])->update(['user_id' => $user_id]);
            Db::name('order')->where('user_id', $olduser['user_id'])->update(['user_id' => $user_id]);
            Db::name('account_log')->where('user_id', $olduser['user_id'])->update(['user_id' => $user_id]);
            Db::name('user_address')->where('user_id', $olduser['user_id'])->update(['user_id' => $user_id, 'is_default' => 0]);
        } else {
            Db::name('user')->where('user_id', $user_id)->update(['mobile' => $decryptedData['phoneNumber'], 'wx_session_key' => $session_key]);
        }
        ajaxmsg('绑定手机号成功', 1, ['mobile' => $decryptedData['phoneNumber'], 'token' => $token]);
    }

    #仿京东礼品卡模块
    public function giftcardBindUser()
    {
        $user_id  = $this->islogin();
        $password = trim(input('password'));
        if (!$password) {
            ajaxmsg("密码不能为空", 0);
        }
        $time     = time();
        $map      = [];
        $map[]    = ['password', '=', $password];
        $map[]    = ['status', '=', 1];
        $giftcard = Db::name("giftcard")->where($map)->find();
        if (!$giftcard) {
            ajaxmsg("卡片不存在或", 0);
        }
        if ($giftcard['user_id'] > 0) {
            ajaxmsg("本卡已经消费绑定账户", 0);
        }
        if ($giftcard['start_time'] > $time || $giftcard['over_time'] < $time) {
            ajaxmsg("不在有效期内", 0);
        }

        $giftcardData              = [];
        $giftcardData['user_id']   = $user_id;
        $giftcardData['bind_time'] = $time;
        $res                       = Db::name("giftcard")->where("password", "=", $password)->update($giftcardData);
        if ($res > 0) {
            $giftcard['user_id']   = $user_id;
            $giftcard['bind_time'] = $time;
            ajaxmsg("绑定成功", 1, $giftcard);
        } else {
            ajaxmsg("绑定失败", 0);
        }

    }

    public function giftcardBond()
    {
        $id        = input('id/d');
        $user_id   = $this->islogin();
        $cardModel = new Giftcard;
        $card      = $cardModel->where('buyid', $user_id)->where('id', $id)->find();
        $res       = $cardModel->bonding($card, $user_id);
        if ($res === false) {
            ajaxmsg($cardModel->getError(), 0);
        }
        ajaxmsg("绑定成功", 1, $res);
    }

    public function giftcardBindList()
    {
        $user_id = $this->islogin();
        $time    = time();
        $map     = [];
        $map[]   = ['user_id', '=', $user_id];
        $map[]   = ['cash', '>', 0];
        $map[]   = ['start_time', '<=', $time];
        $map[]   = ['over_time', '>=', $time];
        $map[]   = ['status', '=', 1];

        $data         = [];
        $data['list'] = Db::name("giftcard")->where($map)->order("id desc")->select();
        $data['user'] = Db::name("user")->find($user_id);
        ajaxmsg("true", 1, $data);
    }

    public function giftcardBuyList()
    {
        $user_id = $this->islogin();
        $list    = (new Giftcard())->where('buyid', $user_id)->order('id DESC')->select();
        ajaxmsg("true", 1, $list);
    }

    #$res = ['status' => 1, 'msg' => '提交订单成功', 'sn' => $sn, 'id' => $order_id];
    public function giftcardBindOrder($res)
    {
        $order_id     = $res['id'];
        $giftcardList = input("giftcardList");
        $order        = Db::name("order")->find($res['id']);
        $orderList    = Db::name("order")->where("master_order_sn", "=", $order['master_order_sn'])->select();

        #礼品卡不支持第三方商户
        foreach ($orderList as $k => $v) {
            if ($v['store_id'] > 0) {
                return $res;
            }
        }
        $giftcardList = json_decode($giftcardList, true);

        if (count($giftcardList) == 0) {
            return $res;
        }
        $order_amount = Db::name("order")->where("master_order_sn", "=", $order['master_order_sn'])->sum("order_amount");
        $card_ids     = [];
        foreach ($giftcardList as $k => $v) {
            array_push($card_ids, $v['id']);
            if ($order_amount > $v['cash']) {
                $order_amount        -= $v['cash'];
                $logData             = [];
                $logData['gid']      = $v['id'];
                $logData['order_id'] = $order_id;
                $logData['user_id']  = $order['user_id'];
                $logData['use_time'] = time();
                $logData['status']   = 0;
                $logData['cash']     = $v['cash'];
                Db::startTrans();
                try {
                    Db::name("giftcard_log")->insert($logData);
                    Db::name("giftcard")->where("id", "=", $v['id'])->update(['cash' => 0]);
                    Db::commit();
                } catch (\Exception $e) {
                    $order_amount += $v['cash'];
                    Db::rollback();
                }
            } else {
                $logData             = [];
                $logData['gid']      = $v['id'];
                $logData['order_id'] = $order_id;
                $logData['user_id']  = $order['user_id'];
                $logData['use_time'] = time();
                $logData['status']   = 0;
                $logData['cash']     = $order_amount;


                Db::startTrans();
                try {
                    Db::name("giftcard_log")->insert($logData);
                    Db::name("giftcard")->where("id", "=", $v['id'])->setDec('cash', $order_amount);
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                }
                $order_amount = 0;
                break;
            }
        }
        if ($order_amount == 0) {
            Db::name("order")->where("master_order_sn", "=", $order['master_order_sn'])->update([
                'pay_status'   => 1,
                'order_amount' => $order_amount,
                'use_card'     => implode(',', $card_ids),
                'pay_code'     => 'gift_card',
                'pay_name'     => '礼品卡抵扣'
            ]);
        } else {
            Db::name("order")->where("order_id", "=", $order_id)->update([
                'order_amount' => $order_amount,
                'use_card'     => implode(',', $card_ids)
            ]);
        }
        $res['order_amout'] = $order_amount;
        return $res;
    }

    public function giftcardlogList()
    {
        $user_id = $this->islogin();
        $id      = input("id");

        $map   = [];
        $map[] = ['user_id', '=', $user_id];
        $map[] = ['id', '=', $id];

        $data                 = [];
        $data['giftcard']     = Db::name("giftcard")->where($map)->find();
        $data['giftcard_log'] = Db::name("giftcard_log")->alias("a")
            ->leftJoin("order b", "a.order_id=b.order_id")
            ->field("b.*,a.*")
            ->where("a.gid", "=", $id)
            ->select();
        ajaxmsg("true", 1, $data);
    }

public function selfPickupList()
    {
        $user_id = $this->islogin();
        $list    = Db::name('self_pickup')->where(['user_id' => $user_id, 'is_delete' => 0])->order('last_time DESC')->select();
        ajaxmsg("true", 1, $list);
    }

    public function selfPickup()
    {
        $data   = input('post.');
        $result = $this->validate($data, [
            'mobile'    => 'require|mobile',
            'consignee' => 'require',
        ], [
            'mobile'    => '手机号码格式错误',
            'consignee' => '请填写提货人姓名',
        ]);
        if (true !== $result) {
            ajaxmsg($result, 0);
        }
        $user_id           = $this->islogin();
        $data['user_id']   = $user_id;
        $data['last_time'] = time();
        if (isset($data['id']) && $data['id'] > 0) {
            $id = $data['id'];
            unset($data['id']);
            Db::name('self_pickup')->where('id', $id)->field('consignee,mobile,last_time')->update($data);
        } else {
            $data['id'] = Db::name('self_pickup')->field('user_id,consignee,mobile,last_time')->insertGetId($data);
        }
        ajaxmsg("true", 1, $data);
    }

    public function selfPickupDel()
    {
        $user_id = $this->islogin();
        $id      = input('id/d');
        Db::name('self_pickup')->where(['id' => $id, 'user_id' => $user_id])->update(['is_delete' => time()]);
        ajaxmsg("true", 1);
    }

    public function queryOrderWriteOff()
    {
        $order_sn = input('order_sn');
        $order    = Db::name("order")->where('order_sn', $order_sn)->find();
        if ($order && $order['pay_status'] == 1 && $order['self_pickup_time'] && $order['shipping_status'] == 2) {
            ajaxmsg('true', 2, ['write_off_time' => $order['shipping_time']]);
        }
        ajaxmsg('true', 1);
    }

    public function selfPickupWriteOff()
    {
        $token    = input('token');
        $order_sn = input('order_sn');
        $user     = Db::name("user")->where('token', $token)->find();
        if (!$user || $user['is_special'] != 1) {
            ajaxmsg('您没有核销权限,请联系工作人员', -1);
        }
        $orderModel = new Order;
        $order      = $orderModel->where('order_sn', $order_sn)->find();
        if (!$order) {
            ajaxmsg('无效二维码', -1);
        }
        if (is_null($order['self_pickup_time'])) {
            ajaxmsg('非自助提货订单', -1);
        }

        if ($order['pay_status'] != 1 || $order['shipping_status'] != 0) {
            ajaxmsg('订单状态错误', -1);
        }
        $time = time();
        $data = ['shipping_status' => 2, 'order_status' => 2, 'shipping_time' => $time, 'confirm_time' => $time, 'write_off' => $user['user_id']];
        $res  = $orderModel->where('order_sn', $order_sn)->update($data);
        if ($res) {
            ajaxmsg('核销成功，请发货', 1, $data);
        } else {
            ajaxmsg('核销失败,请重试', -1);
        }
    }

    public function scanOrderRecord()
    {
        $token    = input('token');
        $page     = input('page/d', 1);
        $pageSize = input('pageSize/d', 10);
        $user     = Db::name("user")->where('token', $token)->find();
        if (!$user || $user['is_special'] != 1) {
            ajaxmsg('您没有核销权限,请联系工作人员', -1);
        }

        $orderModel = new Order;
        $order_list = $orderModel->alias('a')
            ->join('store b', 'a.store_id=b.store_id', 'LEFT')
            ->where('a.write_off', $user['user_id'])
            ->field("a.*,b.store_name")
            ->page($page, $pageSize)
            ->select()->toArray();
        $order      = $orderModel->fixOrderGoods($order_list);

        ajaxmsg('true', 1, $order);
    }

    public function scanPromRecord()
    {
        $token    = input('token');
        $page     = input('page/d', 1);
        $pageSize = input('pageSize/d', 10);
        $user     = Db::name("user")->where('token', $token)->find();
        if (!$user || $user['is_special'] != 1) {
            ajaxmsg('您没有核销权限,请联系工作人员', -1);
        }

        $prom = Db::name('prom_record a')
            ->join('user b', 'b.user_id=a.user_id')
            ->join('pop_prom c', 'c.prom_id=a.prom_id')
            ->where('a.operater', $user['user_id'])
            ->field('a.*,b.user_name,b.nick_name,b.mobile,c.title')
            ->page($page, $pageSize)
            ->select();
        ajaxmsg('true', 1, $prom);
    }

    public function ossSign()
    {
        return json(ossUploadToken());
    }

    public function addComment()
    {
        $data   = input('post.');
        $result = $this->validate($data, [
            'order_id'      => 'require|gt:0',
            'store_id'      => 'require',
            'is_anonymous'  => 'require',
            'desc_rank'     => 'require|gt:0',
            'service_rank'  => 'require|gt:0',
            'delivery_rank' => 'require|gt:0',
            'goods'         => 'require'
        ], [
            'order_id'      => '参数错误1',
            'store_id'      => '参数错误2',
            'is_anonymous'  => '参数错误3',
            'desc_rank'     => '请对商品描述相符打分',
            'service_rank'  => '请对商家服务打分',
            'delivery_rank' => '请对物流配送打分',
            'goods'         => '请对商品评价打分'
        ]);

        if (true !== $result) {
            ajaxmsg($result, 0);
        }
        $user_id   = $this->islogin();
        $user_name = Db::name("user")->where('user_id', $user_id)->value('user_name');

        try {
            Db::startTrans();
            $orderComment     = new OrderComment;
            $orderCommentData = [
                'order_id'      => $data['order_id'],
                'store_id'      => $data['store_id'],
                'desc_rank'     => $data['desc_rank'],
                'service_rank'  => $data['service_rank'],
                'delivery_rank' => $data['delivery_rank'],
                'user_id'       => $user_id,
                'ip_address'    => Request::ip(),
                'create_time'   => time(),
            ];
            $res              = $orderComment->addComment($orderCommentData);
            if ($res['status'] == 0) {
                ajaxmsg($res['msg'], 0);
            }

            $comment = new Comment;

            foreach ($data['goods'] as $rec_id => $goods) {
                $row = [
                    'comment_rank' => $goods['rank'],
                    'content'      => $goods['content'] ?: '默认好评',
                    'rec_id'       => $rec_id,
                    'user_name'    => $user_name,
                    'is_anonymous' => $data['is_anonymous'] == 'true' ? 1 : 0,
                    'order_id'     => $data['order_id'],
                    'store_id'     => $data['store_id'],
                    'goods_id'     => $goods['goods_id'],
                    'spec'         => $goods['spec'] ?? '',
                    'user_id'      => $user_id,
                    'ip_address'   => Request::ip()
                ];
                if (is_array($goods['img'])) {
                    $row['imgs'] = '';
                    foreach ($goods['img'] as $index => $img) {
                        if ($img['active'] == 'true') {
                            $row['imgs'] = $row['imgs'] . ',' . $img['src'];
                        }
                    }
                    $row['imgs'] = trim($row['imgs'], ',');
                } else {
                    $row['imgs'] = $goods['img'];
                }

                $res = $comment->addComment($row);
                if ($res['status'] == 0) {
                    throw new \Exception($res['msg']);
                }
                unset($row);
            }
            Db::commit();
            ajaxmsg($res['msg'], $res['status']);
        } catch (\Exception $e) {
            trace($e->getMessage());
            Db::rollback();
            ajaxmsg($e->getMessage(), 0);
        }
    }

    public function getInvoice()
    {
        $user_id = $this->islogin();
        if (input('save')) {
            $data = input('post.');
            unset($data['save'], $data['token']);
            if (isset($data['id']) && $data['id'] > 0) {
                $res = Db::name('user_vat_invoice')
                    ->where('id', $data['id'])
                    ->where('user_id', $user_id)
                    ->update($data);
            } else {
                $data['user_id']      = $user_id;
                $data['audit_status'] = 1;//默认增票信息通过审核
                $res                  = Db::name('user_vat_invoice')->insertGetId($data);
                if ($res) {
                    $data['id'] = $res;
                }
            }
            if ($res) ajaxmsg('ok', 1, $data['id']);
            ajaxmsg('操作失败，信息未保存', 0);
        }

        $vat_invoice = Db::name('user_vat_invoice')->where('user_id', $user_id)->find();
        if (!$vat_invoice) {
            $table       = Db::query('SHOW COLUMNS FROM ' . config('database.prefix') . 'user_vat_invoice');
            $vat_invoice = array_combine(array_column($table, 'Field'), array_column($table, 'Default'));
        }
        ajaxmsg('ok', 1, $vat_invoice);
    }

    public function groupList()
    {
        $page       = input('page', 1);
        $rows       = input('rows', 10);
        $cat_id     = input('cat_id');
        $keywords   = input('keywords');
        $sort       = input('sort', 'gid');
        $order      = input('order', 'DESC');
        $groupGoods = new GroupGoods();
        $list       = $groupGoods->getList($page, $rows, $keywords, $cat_id, $sort, $order);
        ajaxmsg('ok', 1, $list);
    }

    public function groupDetail()
    {
        $id    = input('id/d');
        $time  = time();
        $token = input("token");
        $group = (new GroupGoods)->alias('a')->where('start_time', '<', $time)->where('end_time', '>', $time)->get($id)->toArray();
        if (!$group) {
            ajaxmsg('商品团购时间已结束', 0);
        }

        $goodsModel       = new GoodsModel();
        $goods            = $goodsModel->get($group['goods_id'])->toArray();
        $goods['product'] = $goodsModel->getProduct($group['goods_id']);
        $goods['attr']    = $goodsModel->getAttriBute($goods);
        $goods['brand']   = $goodsModel->getBrand($goods['brand_id']);

        //$interval_price     = array_column($group['ext_info'], 'price');
        //$group['min_price'] = min($interval_price);
        //$group['max_price'] = max($interval_price);
        $data            = [
            'goods' => $goods,
            'group' => $group
        ];
        $data['comment'] = $goodsModel->getGoodsComment($group['goods_id']);
        $cartGoodsNumber = 0;
        if ($token) {
            $user                    = Db::name("user")->where("token", "=", $token)->find();
            $data['user']            = $user;
            $data['cartGoodsNumber'] = Db::name("cart")->where("user_id", $user['user_id'])->sum("goods_num");

            $data['iscollect']                   = Db::name("goods_collect")
                ->where("goods_id", $group['goods_id'])
                ->where("user_id", $user['user_id'])
                ->count();
            $data['user_address']                = Db::name("user_address")
                ->where("user_id", $user['user_id'])
                ->order("is_default desc")
                ->find();
            $data['user_address']['region_name'] = getprovincecitydistrict($data['user_address']);
            $data['address']                     = Db::name("user_address")
                ->where("user_id", $user['user_id'])
                ->order("is_default desc")
                ->find();

            $goodsclickData            = [
                'goods_id' => $group['goods_id'],
                'store_id' => $goods['store_id'],
                'date'     => date('Y-m-d'),
            ];
            $goodsclickData['user_id'] = $user['user_id'];
            $goodsclick                = Db::name("goods_click")
                ->where('goods_id', $group['goods_id'])
                ->where('user_id', $user['user_id'])
                ->find();
            if (!$goodsclick) {
                Db::name('goods_click')->insert($goodsclickData);
            }

        } else {
            $data['user']            = [];
            $data['cartGoodsNumber'] = $cartGoodsNumber;
            $data['iscollect']       = 0;
            $data['user_address']    = [];
        }
        $data['store'] = Db::name("store")
            ->where("store_id", "=", $goods['store_id'])
            ->find();

        $data['storeaddress']  = getAddressWithOder($data['store']);
        $data['goods_gallery'] = Db::name("goods_gallery")->where("goods_id", $group['goods_id'])->order("img_sort asc")->select();

        ajaxmsg('ok', 1, $data);
    }

    public function shippingStatus()
    {
        $order_id = input('order_id/d');
        $order    = Db::name('order')->where('order_id', $order_id)->find();
        if ($order['shipping_status'] == 2) {
            $delivery_order = Db::name('delivery_order')->where('order_id', $order_id)->find();

            if ($delivery_order) {
                $no          = $delivery_order['shipping_no'];
                $shipcompany = Db::name('shipping')->where('shipping_id', $delivery_order['shipping_id'])->find();

                $shipping = [
                    'cname' => $shipcompany['shipping_name'],
                    'state' => 2,
                    'no'    => $delivery_order['shipping_no'],
                ];

                if ($shipcompany['shipping_code'] == 'shunfeng') {
                    $no = $no . ':' . substr($delivery_order['mobile'], -4);
                }
                $Queryshipping = expressQuery($no, $shipcompany['shipping_code']);
                if ($shipcompany['shipping_code'] == 'shunfeng') {
                    $Queryshipping['no'] = substr($Queryshipping['no'], 0, strlen($shipping['no']) - 4);
                }
                if (is_array($Queryshipping)) {
                    $shipping = $Queryshipping;
                }
                ajaxmsg('ok', 1, ['shipping' => $shipping, 'order_sn' => $order['order_sn']]);
            } else {
                ajaxmsg('物流信息不存在', 0);
            }
        } else {
            ajaxmsg('物流信息不存在', 0);
        }
    }

    public function getQrcodeWithGoods()
    {
        $uid      = $this->islogin();
        $goods_id = input('goods_id/d');
        try {
            if (!GoodsModel::get($goods_id)) {
                ajaxmsg('商品不存在或已下架', 0);
            }

            $data = (new \app\home\logic\Miniprogram)->makeQrcodeWithGoods($goods_id, $uid);
            ajaxmsg('ok', 1, ['base64' => $data]);
        } catch (\Exception $e) {
            trace($e->getMessage());
            ajaxmsg('获取小程序码错误', 0);
        }
    }

    public function sharelist()
    {
        $page       = input('page/d', 1);
        $pageSize   = input('pageSize/d', 10);
        $start_time = input('start_time');
        $end_time   = input('end_time');
        $goods      = input('goods');
        $uid        = $this->islogin();

        $map = [['a.share_man', '=', $uid]];

        if ($end_time) {
            $end_time = strtotime($end_time) + 86400;
            if ($end_time === false) ajaxmsg('查询结束时间错误', 0);
            $map[] = ['a.add_time', '<=', $end_time];
        }
        if ($start_time) {
            $start_time = strtotime($start_time);
            if ($start_time === false) ajaxmsg('查询开始时间错误', 0);
            $map[] = ['a.add_time', '>', $start_time];
        }

        $list = Db::name("order")->alias("a")
            ->join('store b', 'a.store_id=b.store_id', 'LEFT')
            ->where($map)
            ->field('a.*,b.store_name')
            ->order("a.order_id desc")
            ->page($page, $pageSize)
            ->select();

        if ($list) {
            $goodslist       = Db::name('order_goods')
                ->where('order_id', 'IN', array_column($list, 'order_id'))
                ->order("order_id desc")
                ->select();
            $self_shop_title = Db::name('shop_config')->where('code', 'shop_name')->cache(true)->value('value');
            $ids             = array_column($goodslist, 'order_id');
            $valCount        = array_count_values($ids);
            foreach ($list as $k => $v) {
                if (isset($valCount[$v['order_id']])) {
                    $list[$k]['goodslist'] = array_slice($goodslist, array_search($v['order_id'], $ids), $valCount[$v['order_id']]);
                }

                if ($v['store_id'] == 0) {
                    $list[$k]['store_name'] = $self_shop_title;
                }
            }
        }

        ajaxmsg('ok', 1, $list);
    }

    public function refund()
    {
        $data   = json_decode(input('pageparm'), true);
        $result = $this->validate($data, [
            'goods'         => 'require',
            'refund_amount' => 'require|gt:0',
            'reason'        => 'require',
            'goods_status'  => 'require'
        ], [
            'goods'         => '参数错误',
            'refund_amount' => '退款金额错误',
            'reason'        => '请选择退款原因',
            'goods_status'  => '请选择货物状态'
        ]);
        if (true !== $result) {
            ajaxmsg($result, 0);
        }
        $user          = $this->islogin(true);
        $amount        = 0;
        $goods_is_send = 0;
        $is_entire     = 0;
        $return_goods  = [];
        $rec_ids       = [];
        foreach ($data['goods'] as $index => $goods) {
            if ($goods['is_refund'] > 0) ajaxmsg('退货状态错误', 0);
            $amount         += $goods['goods_price'] * $goods['goods_num'];
            $return_goods[] = [
                'rec_id'      => $goods['rec_id'],
                'goods_price' => $goods['goods_price'],
                'return_num'  => $goods['goods_num']
            ];
            $goods_is_send  = $goods['is_send'];
            array_push($rec_ids, $goods['rec_id']);
        }

        $time  = time();
        $order = OrderModel::get(['user_id' => $user['user_id'], 'order_id' => $data['goods'][0]['order_id']]);
        //未发货 或 发货时间在结算期内，允许退货
        $withdraw_day = Db::name('shop_config')->where('code', 'withdraw_day')->cache(true)->value('value');
        $allow_refund = $order->pay_status == 1 && ($order->shipping_time < 1 || (((int)$withdraw_day * 86400) + (int)$order->shipping_time) > $time);

        if (!$allow_refund) {
            ajaxmsg('已超出退货期限:' . $withdraw_day . '天', 0);
        }

        $count = Db::name('order_goods')->where('order_id', $order->order_id)->count();
        if ($count == count($return_goods)) {
            $is_entire = 1;
        }
        //ajaxmsg($is_entire . '===' . $order->shipping_price . '&' . $amount, 0);
        if ($is_entire) {
            if ($data['refund_amount'] > $amount + $order->shipping_price) {
                ajaxmsg('退款金额不得大于:' . ($amount + $order->shipping_price), 0);
            }
        } else if ($data['refund_amount'] > $amount) {
            ajaxmsg('退款金额不得大于' . $amount, 0);
        }
        $returnModel = new OrderReturn;
        $row         = [
            'order_id'      => $order->order_id,
            'refund_no'     => $returnModel->makeRefundNo(),
            'order_sn'      => $order->order_sn,
            'user_id'       => $order->user_id,
            'store_id'      => $order->store_id,
            'status'        => 0,
            'type'          => $data['type'],
            'reason'        => $data['reason'],
            'user_note'     => $data['user_note'],
            'goods_status'  => $data['goods_status'],
            'imgs'          => implode(',', $data['imgs']),
            'consignee'     => $user['user_name'],
            'mobile'        => $order->mobile,
            'refund'        => $data['refund_amount'],
            'goods_is_send' => $goods_is_send,
            'is_entire'     => $is_entire
        ];

        $returnModel->save($row);
        $returnModel->return_id;
        foreach ($return_goods as $i => $item) {
            $return_goods[$i]['return_id'] = $returnModel->return_id;
        }
        Db::name('return_goods')->insertAll($return_goods);
        Db::name('order_goods')->whereIn('rec_id', $rec_ids)->update(['is_refund' => 1]);
        $content = [
            '退款类型' => $row['type'] == 1 ? '退货退款' : '仅退款',
            '退款金额' => '￥' . $row['refund'],
            '退款原因' => $row['reason'],
            '退款描述' => $row['user_note']
        ];
        ReturnLog::create([
            'rid'       => $returnModel->return_id,
            'username'  => $user['user_name'],
            'user_type' => 0,
            'user_id'   => $user['user_id'],
            'title'     => '买家(' . $user['user_name'] . ')创建了退款申请',
            'content'   => json_encode($content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        ]);
        ajaxmsg('提交成功', 1);
    }

    public function refundDetail()
    {
        $user_id   = $this->islogin();
        $rec_id    = input('rec_id/d');
        $return_id = input('rid/d');
        if ($rec_id) {
            $return_ids = Db::name('return_goods')->where('rec_id', $rec_id)->column('return_id');
            $return     = OrderReturn::where([['user_id', '=', $user_id], ['status', '>', '-2'], ['return_id', 'IN', $return_ids]])->find();
            $return_id  = $return['return_id'];
        } else if ($return_id) {
            $return = OrderReturn::get($return_id);
        }

        if (!$return_id || !$return) {
            ajaxmsg('退款信息不存在', 0);
        }
        $return_goods = Db::name('return_goods')->where('return_id', $return_id)->column('return_num', 'rec_id');
        ajaxmsg('', 1, ['rtn' => $return, 'goods' => $return_goods]);
    }

    public function cancelRefund()
    {
        $rid    = input('return_id/d');
        $return = OrderReturn::get($rid);
        if (!$return) {
            ajaxmsg('退货信息不存在', 0);
        }
        $user = $this->islogin(true);
        OrderReturn::where('return_id', $rid)->update(['status' => -2, 'canceltime' => time()]);
        Db::name('return_goods')->where('return_id', $rid)->update(['status' => 1]);
        $rec_ids = Db::name('return_goods')->where('return_id', $rid)->column('rec_id');
        Db::name('order_goods')->whereIn('rec_id', $rec_ids)->update(['is_refund' => 0]);
        ReturnLog::create([
            'rid'       => $rid,
            'username'  => '',
            'user_type' => 0,
            'user_id'   => $user['user_id'],
            'title'     => '买家(' . $user['user_name'] . ')取消退款',
        ]);
        ajaxmsg('已取消退款', 1);
    }

    public function appeal()
    {
        $rid     = input('return_id/d');
        $content = input('content');
        $user    = $this->islogin(true);
        $content = ['申诉信息' => $content];
        ReturnLog::create([
            'rid'       => $rid,
            'username'  => $user['user_name'],
            'user_type' => 0,
            'user_id'   => $user['user_id'],
            'title'     => '买家(' . $user['user_name'] . ')投诉',
            'content'   => json_encode($content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'is_appeal' => 1
        ]);
        OrderReturn::where('return_id', $rid)->update(['status' => 6]);
        ajaxmsg('申请信息已提交', 1);
    }

    public function refundHistory()
    {
        $rid  = input('return_id/d');
        $list = ReturnLog::where('rid', $rid)->order('id DESC')->select();
        ajaxmsg('', 1, $list);
    }

    public function refundList()
    {
        $uid      = $this->islogin();
        $page     = input('page/d', 1);
        $pageSize = input('pageSize/d', 10);

        $model             = new OrderReturn;
        $res               = $model->getListByUser($uid, $page, $pageSize);
        $self_shop_title   = Db::name('shop_config')->where('code', 'shop_name')->cache(true)->value('value');
        $res['shop_title'] = $self_shop_title;
        ajaxmsg('', 1, $res);
    }
}
