<?php
/**
 * =========================================================
 * Copy right 2015-2025 老孟编程, 保留所有权利。
 * ----------------------------------------------
 * 官方网址:heimicms.com
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用。
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * =========================================================
 * @author : 孟老师
 * @date : 2018.1.17
 * @version : v1.0.0.0
 * @email: 835173372@qq.com
 */

use Alidayu\SignatureHelper;
use think\Controller;
use think\Db;
use think\exception\HttpResponseException;
use think\facade\Request;
use think\facade\Config;
use think\Response;
use think\response\Redirect;
use think\facade\Session;
use think\Url;
use Qiniu\Auth;
use OSS\OssClient;
use OSS\Core\OssException;
use GuzzleHttp\Client;

#API数据返回接口，统一规定，-1：未登录，0：普通错误，1：返回成功
function ajaxmsg($msg = "", $status = 1, $data = '', $errcode = '')
{
    $json['msg']    = $msg;
    $json['status'] = $status;
    $json['data']   = $data;
    if ($errcode) {
        $json['errcode'] = $errcode;
    }
    echo json_encode($json, 320);
    exit;
}

#输出纯文本
function text($text, $parseBr = false, $nr = false)
{
    $text = htmlspecialchars_decode($text);
    $text = safe($text, 'text');
    if (!$parseBr && $nr) {
        $text = str_ireplace(["\r", "\n", "\t", "&nbsp;"], '', $text);
        $text = htmlspecialchars($text, ENT_QUOTES);
    } elseif (!$nr) {
        $text = htmlspecialchars($text, ENT_QUOTES);
    } else {
        $text = htmlspecialchars($text, ENT_QUOTES);
        $text = nl2br($text);
    }
    $text = trim($text);
    return $text;
}

#截取字符串函数
function cnsubstr($str, $length, $start = 0, $charset = "utf-8", $suffix = true)
{
    $str = strip_tags($str);
    if (function_exists("mb_substr")) {
        if (mb_strlen($str, $charset) <= $length) return $str;
        $slice = mb_substr($str, $start, $length, $charset);
    } else {
        $re['utf-8']  = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
        $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
        $re['gbk']    = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
        $re['big5']   = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
        preg_match_all($re[$charset], $str, $match);
        if (count($match[0]) <= $length) return $str;
        $slice = join("", array_slice($match[0], $start, $length));
    }
    if ($suffix) return $slice . "…";
    return $slice;
}

#获取不重复的随机数
function no_repe_number($start = 0, $end = 9, $len = 6)
{
    $co  = 0;
    $arr = $reArr = [];
    while ($co < $len) {
        $arr[] = mt_rand($start, $end);
        $reArr = array_unique($arr);
        $co    = count($reArr);
    }
    return $reArr;
}

#创建Token
function create_token()
{
    $Token = md5(time() . rand(111111111, 999999999));
    return $Token;
}

#生成邀请码
function create_invitecode()
{
    $invitecode = time() + 10001;
    return $invitecode;
}

function ajaxeasy($data)
{
    echo json_encode($data, 320);
    exit;
}

function curl_get($url)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0); #强制协议为1.0
    curl_setopt($curl, CURLOPT_HTTPHEADER, ['Expect:']);#头部要送出'Expect: '
    curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);#强制使用IPV4协议解析域名
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);#不验证HTTPS
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);#不验证HTTPS
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);#参数为1表示传输数据，为0表示直接输出显示
    curl_setopt($curl, CURLOPT_HEADER, 0);#参数为0表示不带头文件，为1表示带头文件
    $data = curl_exec($curl);
    curl_close($curl);
    return $data;
}

#模拟post提交
function curl_post($url, $post_data = [])
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0); #强制协议为1.0
    curl_setopt($curl, CURLOPT_HTTPHEADER, ['Expect:']);#头部要送出'Expect: '
    curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);#强制使用IPV4协议解析域名
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);#不验证HTTPS
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);#不验证HTTPS
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
    $data = curl_exec($curl);
    curl_close($curl);
    return $data;
}

#二维数组去重
function array_unset_tt($arr, $key)
{
    $res = [];
    foreach ($arr as $value) {
        if (isset($res[$value[$key]])) {
            unset($value[$key]);
        } else {
            $res[$value[$key]] = $value;
        }
    }
    return $res;
}

#密码加盐-32位随机数字和字母
function getPasssalt()
{
    return md5(uniqid(microtime(true), true));
}

#获取格式化JSON字符串
function getPageparm()
{
    $pageparm = json_decode($_REQUEST['pageparm'], true);
    return $pageparm;
}

#获取分类下的所有子分类
function getSonList($id)
{
    static $arr = [];
    $list = Db::name("news_cat")->where("up={$id}")->field("id")->select();
    if (count($list) > 0) {
        foreach ($list as $k => $v) {
            $arr[] = $v['id'];
            getSonList($v['id']);
        }
    }
    return $arr;
}

#获取单张表的所有字段
function gettableColumn($table)
{
    $prefix       = Config::get('database.prefix');
    $prefix_table = $prefix . $table;

    #$table需要填写默认表前缀
    //$sql  = "select COLUMN_NAME from information_schema.columns where table_name='{$prefix_table}'";
    $sql  = "show columns from {$prefix_table};";
    $list = Db::query($sql);
    $arr  = array_column($list, 'Field');
    $info = [];
    foreach ($arr as $k => $v) {
        $info[$v] = '';
    }
    return $info;
}

#获取首字母
function getInitials($str)
{
    if (empty($str)) {
        return '';
    }
    $fchar = ord($str{0});
    if ($fchar >= ord('A') && $fchar <= ord('z')) {
        return strtoupper($str{0});
    }
    $s1  = iconv('UTF-8', 'GBK', $str);
    $s2  = iconv('GBK', 'UTF-8', $s1);
    $s   = $s2 == $str ? $s1 : $str;
    $asc = ord($s{0}) * 256 + ord($s{1}) - 65536;
    if ($asc >= -20319 && $asc <= -20284) {
        return 'A';
    }
    if ($asc >= -20283 && $asc <= -19776) {
        return 'B';
    }
    if ($asc >= -19775 && $asc <= -19219) {
        return 'C';
    }
    if ($asc >= -19218 && $asc <= -18711) {
        return 'D';
    }
    if ($asc >= -18710 && $asc <= -18527) {
        return 'E';
    }
    if ($asc >= -18526 && $asc <= -18240) {
        return 'F';
    }
    if ($asc >= -18239 && $asc <= -17923) {
        return 'G';
    }
    if ($asc >= -17922 && $asc <= -17418) {
        return 'H';
    }
    if ($asc >= -17417 && $asc <= -16475) {
        return 'J';
    }
    if ($asc >= -16474 && $asc <= -16213) {
        return 'K';
    }
    if ($asc >= -16212 && $asc <= -15641) {
        return 'L';
    }
    if ($asc >= -15640 && $asc <= -15166) {
        return 'M';
    }
    if ($asc >= -15165 && $asc <= -14923) {
        return 'N';
    }
    if ($asc >= -14922 && $asc <= -14915) {
        return 'O';
    }
    if ($asc >= -14914 && $asc <= -14631) {
        return 'P';
    }
    if ($asc >= -14630 && $asc <= -14150) {
        return 'Q';
    }
    if ($asc >= -14149 && $asc <= -14091) {
        return 'R';
    }
    if ($asc >= -14090 && $asc <= -13319) {
        return 'S';
    }
    if ($asc >= -13318 && $asc <= -12839) {
        return 'T';
    }
    if ($asc >= -12838 && $asc <= -12557) {
        return 'W';
    }
    if ($asc >= -12556 && $asc <= -11848) {
        return 'X';
    }
    if ($asc >= -11847 && $asc <= -11056) {
        return 'Y';
    }
    if ($asc >= -11055 && $asc <= -10247) {
        return 'Z';
    }
    return null;
}

#管理后台-商户后台共用AJAX
function brandListAjax()
{
    $map              = [];
    $brand_first_char = input("brand_first_char");
    $brand_name       = trim(input("brand_name"));
    if ($brand_first_char) {
        $map[] = ['brand_first_char', 'eq', $brand_first_char];
    }
    if ($brand_name) {
        $map[] = ['brand_name', 'like', "%$brand_name%"];
    }
    $list = Db::name("brand")->where($map)->select();
    ajaxmsg('true', 1, $list);
}

function goodstypeListAjax()
{
    $map      = [];
    $cat_name = input("cat_name");
    if ($cat_name) {
        $map[] = ['cat_name', 'like', "%$cat_name%"];
    }
    $list = Db::name("goods_type")->where($map)->select();
    ajaxmsg('true', 1, $list);
}

function attributelistAjax()
{
    $map    = [];
    $cat_id = input("cat_id");
    if ($cat_id) {
        $map[] = ['cat_id', 'eq', $cat_id];
    }
    $list = Db::name("attribute")->where($map)->select();
    foreach ($list as $k => $v) {
        $arr         = [];
        $attr_values = json_decode($v['attr_values']);
        foreach ($attr_values as $a => $b) {
            $arr[] = [
                'checked' => 0,
                'val'     => $b,
            ];
        }
        $list[$k]['attr_values'] = $arr;
    }
    ajaxmsg('true', 1, $list);
}

#初始化$goodsattrList['attributeList']数组
function attributelistGet($goods_id = 0)
{
    $map = [];
    if ($goods_id > 0) {
        $map[] = ['b.goods_id', 'eq', $goods_id];
    }
    $list = Db::name("attribute")->alias("a")
        ->join("goods_attr b", "a.attr_id=b.attr_id")
        ->field("a.*,b.attr_checked")
        ->where($map)
        ->group("a.attr_name")
        ->select();
    foreach ($list as $k => $v) {
        $arr         = [];
        $attr_values = json_decode($v['attr_values']);
        foreach ($attr_values as $a => $b) {
            $arr[] = [
                'checked' => 0,
                'val'     => $b,
            ];
        }
        $list[$k]['attr_values'] = $arr;
    }
    return $list;
}

function gmt_iso8601($time)
{
    $dtStr      = date("c", $time);
    $mydatetime = new DateTime($dtStr);
    $expiration = $mydatetime->format(DateTime::ISO8601);
    $pos        = strpos($expiration, '+');
    $expiration = substr($expiration, 0, $pos);
    return $expiration . "Z";
}

/**
 * 七牛上传token
 * @return string
 */
function uploadtoken()
{
    $config = [
        'accessKey' => 'u1mpysU6FBNULGuJcVzGgQLJJ1zqBc3uhINvpazi',
        'secretKey' => 'LnEXgP-8F4nKn8HWzNWOF7kYtX0NqmlXXCyyYmIu',
        'domain'    => 'http://shangxing.heimicms.com/',
        'bucket'    => 'test2',
    ];

    $auth       = new Auth($config['accessKey'], $config['secretKey']);
    $returnBody = json_encode(['cdn' => $config['domain'], 'key' => '$(key)', 'hash' => '$(etag)']);

    $token = $auth->uploadToken($config['bucket'], null, 3600, ['returnBody' => $returnBody]);# 生成上传 Token
    return $token;
}

/**
 * 阿里oss签名
 * @return array
 */
function ossUploadToken($path = '')
{
    $id   = 'LTAIWVVWg5MloIYB';          // 请填写您的AccessKeyId。
    $key  = 'x76UY66HuudlwWfTp16gCqwHoS6DJN';     // 请填写您的AccessKeySecret。
    $url  = 'https://hngpmall.oss-cn-shanghai.aliyuncs.com';//$host的格式为 bucketname.endpoint : hngpmall
    $host = 'http://image.hngpmall.com';
    $dir  = 'upload/image/' . date('Ymd') . '/';          // 用户上传文件时指定的前缀。

    // $callbackUrl为上传回调服务器的URL，请将下面的IP和Port配置为您自己的真实URL信息。
    //$callbackUrl = 'http://88.88.88.88:8888/aliyun-oss-appserver-php/php/callback.php';
    //$callback_param  = ['callbackUrl'      => $callbackUrl,
    //                    'callbackBody'     => 'filename=${object}&size=${size}&mimeType=${mimeType}&height=${imageInfo.height}&width=${imageInfo.width}',
    //                    'callbackBodyType' => "application/x-www-form-urlencoded"];
    //$callback_string = json_encode($callback_param);
    //
    //$base64_callback_body = base64_encode($callback_string);

    $now        = time();
    $expire     = 300;  //设置该policy超时时间是10s. 即这个policy过了这个有效时间，将不能访问。
    $end        = $now + $expire;
    $expiration = gmt_iso8601($end);


    //最大文件大小.用户可以自己设置
    $condition    = [0 => 'content-length-range', 1 => 0, 2 => 10485760];
    $conditions[] = $condition;

    // 表示用户上传的数据，必须是以$dir开始，不然上传会失败，这一步不是必须项，只是为了安全起见，防止用户通过policy上传到别人的目录。
    //$start        = [0 => 'starts-with', 1 => '$key', 2 => $dir];
    //$conditions[] = $start;

    $arr           = ['expiration' => $expiration, 'conditions' => $conditions];
    $policy        = json_encode($arr);
    $base64_policy = base64_encode($policy);
    $signature     = base64_encode(hash_hmac('sha1', $base64_policy, $key, true));

    $response = [
        'accessid'  => $id,
        'policy'    => $base64_policy,
        'signature' => $signature,
        'expire'    => $end,
        'url'       => $url,//上传地址
        'dir'       => $dir//上传路径,需要前端在后面追加文件名及后缀
    ];
    return $response;
}


/**
 * 生成唯一 goods_sn
 * @return string
 */
function makeGoodsSn()
{
    while (true) {
        $sn    = strtoupper(config('database.prefix')) . mt_rand(1000000, 9999999);
        $count = Db::name('goods')->where('goods_sn', $sn)->count('goods_id');
        if ($count == 0) break;
    }
    return $sn;
}

function getSellerListAjax()
{
    $keywords = input("keywords");
    $map      = [];
    if ($keywords) {
        $map[] = ['a.user_name', "like", "%$keywords%"];
    }
    $list = Db::name("user")->alias("a")
        ->join("seller b", "a.user_id=b.user_id", "LEFT")
        ->field("a.user_id,a.user_name,b.id")
        ->where($map)
        ->select();
    return $list;
}

/**
 * 订单日志写入,新增订单/修改订单/发货/确认收货/退货 时使用
 * @param $order
 * @param $action
 * @param string $remark
 * @param int $action_user admin_id seller_id user_id
 * @param string $user_name
 * @param int $user_type 0管理员 1商家 2前台用户
 * @return int|string
 */
function orderLog($order, $action, $remark = '', $action_user = 0, $user_name = '', $user_type = 0)
{
    $data['order_id']         = $order['order_id'];
    $data['user_type']        = $user_type; // 0管理员 1商家 2前台用户
    $data['action_user_id']   = $action_user;
    $data['action_user_name'] = $user_name;
    $data['order_status']     = $order['order_status'];
    $data['shipping_status']  = $order['shipping_status'];
    $data['pay_status']       = $order['pay_status'];
    $data['action_remark']    = $remark;//操作备注，例：不买了，耽误时间
    $data['log_time']         = time();
    $data['status_desc']      = $action;//例：商家取消订单
    $data['store_id']         = $order['store_id'];
    return Db::name('order_log')->insert($data);//订单操作记录
}

#物流列表
function shippingListAjax()
{
    $map   = [];
    $map[] = ['enabled', 'eq', 1];
    $list  = Db::name("shipping")->where($map)->order("shipping_id desc")->select();
    return $list;
}

function transportListAjax()
{
    $map      = [];
    $store_id = input("store_id", 0);
    $map[]    = ['enabled', 'eq', 1];
    $map[]    = ['store_id', 'eq', $store_id];
    $list     = Db::name("transport")->where($map)->order("transport_id desc")->select();
    return $list;
}

/**
 * 根据一条数据库记录获取地区下拉列表
 * @param array $row
 * @return array
 */
function getRegionList($row = [])
{
    $field = 'region_id,parent_id,region_name,region_type,region_first_char';
    if (!isset($row['country']) || !is_numeric($row['country'])) {
        $row['country'] = 1;//默认中国
    }
    $region['country']  = Db::name('region')->where('parent_id', 0)->column($field, 'region_id');
    $region['province'] = Db::name('region')->where(['parent_id' => $row['country'], 'region_type' => 1])->column($field, 'region_id');
    $region['city']     = $region['district'] = [];
    if ($row['province'] > 0) {
        $region['city'] = Db::name('region')->where(['parent_id' => $row['province'], 'region_type' => 2])->column($field, 'region_id');
    }
    if ($row['city'] > 0) {
        $region['district'] = Db::name('region')->where(['parent_id' => $row['city'], 'region_type' => 3])->column($field, 'region_id');
    }
    return $region;
}

/**
 * 根据parent_id获取下级区域列表
 * @param null $pid
 * @return array
 */
function getRegionByParentId($pid = null)
{
    $parent_id = input('parent_id/d', 0);
    if (is_numeric($pid)) $parent_id = $pid;
    $list = Db::name('region')->where('parent_id', $parent_id)->column('region_id,parent_id,region_name,region_type,region_first_char', 'region_id');
    return $list;
}

#商品分类三级联动数据源
function categoryListAjax($cid = 0, $limit = 50)
{
    if ($cid) {
        $cat_id = $cid;
    } else {
        $cat_id = input("cat_id", 0);
    }
    $cmap   = [];
    $cmap[] = ['is_show', 'eq', 1];
    $cmap[] = ['is_delete', 'eq', 0];
    $map    = [];
    $field  = 'cat_id,cat_logo,cat_name,parent_id,sort_order,keywords,style_icon,cat_icon,pinyin_keyword,cat_alias_name,touch_icon';
    if ($cat_id > 0) {
        $map[]         = ['cat_id', 'eq', $cat_id];
        $cache_md5_str = md5('category_id_' . $cat_id);
    } else {
        $map[]         = ['parent_id', 'eq', 0];
        $cache_md5_str = md5('category_id_0');
    }
    if (Cache::get($cache_md5_str) && config('debug') === false) {
        return Cache::get($cache_md5_str);
    } else {
        $categoryList = Db::name("category")
            ->where($cmap)
            ->where($map)
            ->order("sort_order desc")
            ->field($field)
            ->limit($limit)
            ->select();
        $listTwo      = Db::name("category")
            ->where($cmap)
            ->where('parent_id', 'IN', array_column($categoryList, 'cat_id'))
            ->order("parent_id desc")
            ->field($field)
            ->select();
        foreach ($listTwo as $k => $v) {
            $listTwo[$k]['goods_list'] = [];
            $listTwo[$k]['checkitem']  = 0;
        }
        $listThree = Db::name("category")
            ->where($cmap)
            ->where('parent_id', 'IN', array_column($listTwo, 'cat_id'))
            ->order("parent_id desc")
            ->field($field)
            ->select();
        $catIds    = $cat_id . ',' . implode(',', array_column($listTwo, 'cat_id')) . ',' . implode(',', array_column($listThree, 'cat_id'));
        foreach ($listTwo as $k => $v) {
            $ids      = array_column($listThree, 'parent_id');
            $valCount = array_count_values($ids);
            if (array_key_exists($v['cat_id'], $valCount)) {
                $listTwo[$k]['children'] = array_slice(
                    $listThree,
                    array_search($v['cat_id'], $ids),
                    $valCount[$v['cat_id']]
                );
                array_multisort(array_column($listTwo[$k]['children'], 'sort_order'), $listTwo[$k]['children']);
            }
        }
        foreach ($categoryList as $k => $v) {
            $ids      = array_column($listTwo, 'parent_id');
            $cat_ids  = [$v['cat_id']];
            $valCount = array_count_values($ids);
            if (array_key_exists($v['cat_id'], $valCount)) {
                $categoryList[$k]['children'] = array_slice(
                    $listTwo,
                    array_search($v['cat_id'], $ids),
                    $valCount[$v['cat_id']]
                );
                array_multisort(array_column($categoryList[$k]['children'], 'sort_order'), $categoryList[$k]['children']);
                #找出分类下所属品牌
                $cat_ids = array_merge($cat_ids, array_column($categoryList[$k]['children'], 'cat_id'));//单个顶级分类以下,的所有二级分类ID
                try {
                    $cat_ids = array_merge($cat_ids, array_column(array_column($categoryList[$k]['children'], 'children'), 'cat_id'));
                } catch (\Exception $e) {
                    trace('顶级分类[' . $v['cat_name'] . $v['cat_id'] . ']不存在三级分类');
                };

                $categoryList[$k]['brands'] = Db::name('brand a')
                    ->join('goods b', 'a.brand_id=b.brand_id', 'LEFT')
                    ->where('a.is_show=1 AND a.is_delete=0 AND b.is_on_sale=1 AND b.is_delete=0 AND b.is_alone_sale=1')
                    ->where('b.cat_id', 'IN', $cat_ids)
                    ->field('a.brand_id,a.brand_name,a.brand_logo,COUNT(*) AS goods_num,IF(a.brand_logo > "","1","0") AS tag')
                    ->order(['a.sort_order', 'a.brand_id' => 'ASC'])
                    ->group('brand_id')
                    ->limit(20)
                    ->having('goods_num > 0')
                    ->select();
            }
            $categoryList[$k]['catIds'] = $catIds;
        }
        array_multisort(array_column($categoryList, 'sort_order'), $categoryList);
        Cache::set($cache_md5_str, $categoryList);
        return $categoryList;
    }
}

/**
 * 下载excel
 * @param $title
 * @param $data
 * @param string $name
 * @param string $remark
 * @param bool $down
 * @return bool|string
 */
function toExcel($title, $data, $name = '', $remark = '', $down = true)
{
    if (!is_array($title) && !is_array($data))
        return '标题或数据格式错误!';
    if (count($title) != count($data[0]))
        return '标题与数据列数不一致!';

    $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet       = $spreadsheet->getActiveSheet();
    $colcounter  = count($title);
    $keys        = array_keys($data[0]);
    for ($i = 0; $i < $colcounter; $i++) {
        if ($i >= 26) {
            $cell = chr(65 + $i / 26 - 1) . chr(65 + $i % 26);
        } else {
            $cell = chr(65 + $i);
        }
        $sheet->setCellValue($cell . '1', $title[$i]);
    }
    $sheet->getStyle('A1:' . $cell . '1')->getFont()->setBold(true);
    foreach ($data as $index => $row) {
        for ($j = 0; $j < $colcounter; $j++) {
            if ($j >= 26) {
                $cell = chr(65 + $j / 26 - 1) . chr(65 + $j % 26);
            } else {
                $cell = chr(65 + $j);
            }
            if (is_numeric($row[$keys[$j]]) && strlen($row[$keys[$j]]) > 10) {
                $sheet->getCell($cell . ($index + 2))->setValueExplicit($row[$keys[$j]], 's');
            } else {
                $sheet->setCellValue($cell . ($index + 2), $row[$keys[$j]]);
            }
            if ($keys[$j] == 'goods_detail') {
                $sheet->getStyle($cell . ($index + 2))->getAlignment()->setWrapText(true);
            }
        }
    }
    for ($k = 0; $k < $colcounter; $k++) {
        if ($k >= 26) {
            $cell = chr(65 + $k / 26 - 1) . chr(65 + $k % 26);
        } else {
            $cell = chr(65 + $k);
        }
        $sheet->getColumnDimension($cell)->setAutoSize(true);
    }
    if ($remark) {
        $sheet->setCellValue('A' . ($index + 3), $remark);
        $sheet->getStyle('A' . ($index + 3))->getAlignment()->setWrapText(true);
    }
    if (!$name) $name = date('Y-m-d');
    $name .= '.xlsx';
    if ($down) {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        //header('Content-Type:application/vnd.ms-excel');//输出03版本
        header("Content-Disposition: attachment;filename={$name}");
        header('Cache-Control: max-age=0');
        header('Cache-Control: cache, must-revalidate');
        $writer = PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
        exit;
    } else {
        $writer = PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($name);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
    }
    return true;
}

#获取用户基本信息
function getUserInfo()
{
    $user = session('user');
    if ($user) {
        $user = Db::name("user")->where('user_id', $user['user_id'])->field('password', true)->find();
    }
    return $user;
}

/**
 * 查询商品规格实际库存
 * @param $goods_id
 * @param $key
 * @return mixed
 */
function getGoodNum($goods_id, $product_id)
{
    if (!empty($product_id))
        return Db::name("products")->where(['goods_id' => $goods_id, 'product_id' => $product_id])->value('product_number');
    else
        return Db::name("goods")->where("goods_id", $goods_id)->value('goods_number');
}

/**
 * 商品表随机取出几条数据
 * @param $num 条数
 * @param $table 表名
 * @param $where 条件
 */
function goods_random_data($num)
{
    $slq  = "SELECT goods_id,goods_name,original_img,total_sale_num,shop_price,virtual_sale_num FROM `oc_goods` 
WHERE is_on_sale=1 AND is_show=1 AND review_status=2   
ORDER BY rand() LIMIT {$num};";
    $list = Db::query($slq);
    return $list;
}

#登录合并购物车
function mergeCart()
{
    $user       = getUserInfo();
    $session_id = session_id();
    $cartList   = Db::name("cart")
        ->field("goods_id,product_id")
        ->where("session_id='{$session_id}'")
        ->select();
    foreach ($cartList as $k => $v) {
        if ($v['product_id'] > 0) {
            Db::name("cart")->where("goods_id={$v['goods_id']} and product_id={$v['product_id']} and user_id={$user['user_id']}")->delete();
        } else {
            Db::name("cart")->where("goods_id={$v['goods_id']} and user_id={$user['user_id']}")->delete();
        }

    }
    Db::name("cart")
        ->where("session_id='{$session_id}'")
        ->update([
            'user_id'    => $user['user_id'],
            'session_id' => '',
        ]);
    return true;
}

/**
 * 获取goods_attr表主键ID集合
 * @param $tempvalue ='i3300 4GB 250SSD GTX1060显卡'
 * @param $goods_id
 */
function get_goods_attr_ids($tempvalue = '', $goods_id = 0)
{
    if (!$tempvalue) {
        return '';
    }
    $attr_order = '"' . str_replace(' ', '","', $tempvalue) . '"';
    $attr_ids   = Db::name('goods_attr')
        ->where(['goods_id' => $goods_id])
        ->whereIn('attr_value', explode(' ', $tempvalue))
        ->orderRaw('field(attr_value,' . $attr_order . ')')
        ->column('goods_attr_id');
    $attr_keys  = implode('_', $attr_ids);

    return trim($attr_keys);
}

#create filed goods_sn
function create_goods_sn()
{
    $goods_id = Db::name('goods')->max('goods_id');
    return "SP" . str_pad($goods_id, 9, "0", STR_PAD_LEFT);
}

/**
 * 根据一条user_address记录，增加省市区中文名称并完整返回
 * @param $data
 * @return mixed
 */
function getRegionText($data)
{
    $ids                   = [$data['province'], $data['city'], $data['district']];
    $region                = Db::name('region')->whereIn('region_id', $ids)->column('region_name', 'region_id');
    $data['province_text'] = $region[$data['province']] ?? '';
    $data['city_text']     = $region[$data['city']] ?? '';
    $data['district_text'] = $region[$data['district']] ?? '';
    return $data;
}

/**
 * 演算购物车选中商品的价格
 * @param $user_id
 * @param $coupon_id
 * @param array $buyNow
 * @param int $giftCardId
 * @param bool $self_pickup
 * @return array
 * @throws \think\db\exception\DataNotFoundException
 * @throws \think\db\exception\ModelNotFoundException
 * @throws \think\exception\DbException
 */
function calculatePrice($user_id, $coupon_id, $buyNow = [], $giftCardId = 0, $self_pickup = false)
{
    if ($buyNow) {
        $cart_goods = $buyNow;
    } else {
        $cart_goods = Db::name('cart')->where(["user_id" => $user_id, "selected" => 1, 'status' => 0])->order('store_id ASC,goods_id ASC')->select();
        if (!$cart_goods) {
            return ['status' => -1, 'msg' => '您的购物车没有选中的商品哦~'];
        }
    }

    //$user = Db::name('user')->where("user_id", $user_id)->find();//实时查询积分/余额
    if ($giftCardId) {
        $cardModel = new \app\home\model\Giftcard;
        $card      = $cardModel->getById($user_id, $giftCardId);
        if (!$card) {
            return ['status' => -2, 'msg' => '礼品卡不存在~'];
        }
        if ($card['cash'] <= 0) {
            return ['status' => -3, 'msg' => '礼品卡余额不足~'];
        }
    }

    $siteInfo = cache('siteInfo');

    $store_id_arr = array_column($cart_goods, 'store_id');
    //非自营店铺商品，不允许使用礼品卡
    if ($giftCardId && array_sum($store_id_arr) > 0) {
        return ['status' => -4, 'msg' => '礼品卡仅可购买' . $siteInfo['shop_title'] . '自营商品'];
    }

    $store_arr = Db::name('store')->whereIn('store_id', $store_id_arr)->column('store_name,mobile,support_cod', 'store_id');
    //自营店铺信息单独设置
    if (in_array(0, $store_id_arr)) {
        $store_arr[0]['store_id']    = 0;
        $store_arr[0]['store_name']  = $siteInfo['shop_name'];
        $store_arr[0]['mobile']      = $siteInfo['service_phone'];
        $store_arr[0]['support_cod'] = $siteInfo['support_cod'];
    }

    $goods_id_arr = array_column($cart_goods, 'goods_id');
    $goods_arr    = Db::name('goods')->whereIn('goods_id', $goods_id_arr)->column('goods_weight,market_price,freight,transport_id,shipping_fee,give_integral,original_img', 'goods_id');
    $store        = $store_ids = [];
    $support_cod  = 1;
    $goods_price  = $total_amount = $order_amount = $order_prom_amount = $shipping_price = $coupon_price = $give_integral = $goods_count = $card_price = 0;

    foreach ($cart_goods as $index => $goods) {
        $store[$goods['store_id']]['store'] = $store_arr[$goods['store_id']];
        // 计算每个商家的物流费
        if ($goods_arr[$goods['goods_id']]['freight'] == 2 && $goods_arr[$goods['goods_id']]['transport_id'] > 0) {
            $transport = Db::name('transport')->where('transport_id', $goods_arr[$goods['goods_id']]['transport_id'])->find();
            if (!$transport) {
                return ['status' => -5, 'msg' => '物流不支持'];
            }
            switch ($transport['type']) {
                case 0:
                    $goods['shipping_fee'] = $transport['transport_price'];
                    break;
                case 1:
                    $goods['shipping_fee'] = round($transport['unit_price'] * $goods['goods_num'], 2);//按件数计算运费
                    break;
                case 2:
                    $goods['shipping_fee'] = round($transport['kg_price'] * $goods['goods_num'] * $goods_arr[$goods['goods_id']]['goods_weight']);
                    break;
                default:
                    $goods['shipping_fee'] = 0;//FIXME 冗余
            }
            $store[$goods['store_id']]['shpping_id'] = $transport['shipping_id'];
        } elseif ($goods_arr[$goods['goods_id']]['shipping_fee'] > 0) {
            $goods['shipping_fee'] = $goods_arr[$goods['goods_id']]['shipping_fee'];
        } else {
            $goods['shipping_fee'] = 0;
        }

        $goods['sum_fee']       = round($goods['goods_price'] * $goods['goods_num'], 2);
        $goods['give_integral'] = (int)($goods_arr[$goods['goods_id']]['give_integral'] * $goods['goods_num']);
        $goods['original_img']  = $goods_arr[$goods['goods_id']]['original_img'];

        $store[$goods['store_id']]['goods'][] = $goods;
        $goods_count                          += $goods['goods_num'];
    }

    foreach ($store as $store_id => $item) {
        array_push($store_ids, $store_id);
        $support_cod *= $store_arr[$store_id]['support_cod'];
        //商品总价
        $store[$store_id]['goods_price'] = array_sum(array_column($item['goods'], 'sum_fee'));
        $goods_price                     += $store[$store_id]['goods_price'];

        //运费
        if ($self_pickup) {
            $store[$store_id]['shipping_price'] = 0;
        } else {
            //自提订单不再计算运费
            $store[$store_id]['shipping_price'] = array_sum(array_column($item['goods'], 'shipping_fee'));
            $shipping_price                     += $store[$store_id]['shipping_price'];
        }

        // 订单总价 = 商品总价 + 物流总价
        $total_amount += ($store[$store_id]['goods_price'] + $store[$store_id]['shipping_price']);

        //使用优惠券金额
        $store[$store_id]['coupon_id']    = $coupon_id[$store_id] ?? 0;
        $store[$store_id]['coupon_price'] = isset($coupon_id[$store_id]) ? getCouponPrice($coupon_id[$store_id], $user_id) : 0;
        $coupon_price                     += $store[$store_id]['coupon_price'];

        // 应付金额 = 商品价格 + 物流费 - 优惠券 - 订单优惠金额 - 礼品卡金额
        $payable    = $store[$store_id]['goods_price'] + $store[$store_id]['shipping_price'];
        $store_prom = getStoreProm($store_id, $store[$store_id]['goods_price'], $store[$store_id]['shipping_price'], $store[$store_id]['goods_price']);

        if ($store_prom['status'] > 0) {
            $store[$store_id]['order_prom_id']     = $store_prom['prom_id'];
            $store[$store_id]['order_prom_amount'] = $store_prom['prom_amount'];
            $order_prom_amount                     += $store[$store_id]['order_prom_amount'];
        } else {
            $store[$store_id]['prom_remind']       = $store_prom['msg'];
            $store[$store_id]['order_prom_amount'] = 0;
        }
        $order_amount += ($payable - $store[$store_id]['coupon_price'] - $store[$store_id]['order_prom_amount']);
        //礼品卡金额,允许优惠券叠加
        if ($giftCardId) {
            $card_price                     += ($order_amount > $card['cash'] ? $card['cash'] : $order_amount);
            $store[$store_id]['card_price'] = $card_price;
            $order_amount                   -= $card_price;
        }
        $store[$store_id]['card_id']    = $giftCardId;
        $store[$store_id]['card_price'] = $card_price;

        //赠送积分
        $give_integral += array_sum(array_column($item['goods'], 'give_integral'));
    }

    $result = [
        'goods_price'       => $goods_price,
        'total_amount'      => round($total_amount, 4),
        'order_amount'      => round($order_amount, 4),
        'order_prom_amount' => round($order_prom_amount, 4),
        'shipping_price'    => round($shipping_price, 4),
        'coupon_price'      => round($coupon_price, 4),
        'card_price'        => round($card_price, 4),
        'give_integral'     => $give_integral,
        'data'              => $store,
        'store_ids'         => $store_ids,
        'support_cod'       => $support_cod,
        'goods_count'       => $goods_count
    ];

    return ['status' => 1, 'result' => $result];
}

/**
 * 获取优惠券面额
 * @param $coupon_id
 * @param $user_id
 * @return int|mixed
 */
function getCouponPrice($coupon_id, $user_id)
{
    $time  = time();
    $map   = [
        ['a.list_id', '=', $coupon_id],
        ['a.user_id', '=', $user_id],
        ['a.order_id', '=', 0],
        ['a.is_delete', '=', 0],
        ['b.is_delete', '=', 0],
        ['b.use_start_time', '<', $time],
        ['b.use_end_time', '>', $time],
    ];
    $money = Db::name('coupon_list a')
        ->join('coupon b', 'a.coupon_id=b.coupon_id')
        ->where($map)
        ->value('b.money');
    return $money > 0 ? round($money) : 0;
}

/**
 * 获取订单优惠信息
 * @param $store_id
 * @param $goods_price
 * @param $shipping_price
 * @param $coupon_price
 * @return array
 */
function getStoreProm($store_id, $goods_price, $shipping_price, $coupon_price)
{
    //TODO 待增加订单活动
    $remind = [
        'status' => -1,
        'msg'    => '再购**元即可享受 【满500减50优惠】活动(不与优惠券叠加)~',
    ];

    $result = [
        'status'      => 1,
        'prom_id'     => 0,
        'prom_amount' => 0,
    ];
    return $result;
}

/**
 * 生成唯一订单号
 * @param string $table
 * @return string
 */
function get_order_sn($table = 'order')
{
    // 保证不会有重复订单号存在
    while (true) {
        $order_sn = date('YmdHis') . rand(1000, 9999); // 订单编号
        $where    = "order_sn = '$order_sn' or master_order_sn = '$order_sn'";
        if ($table == 'user_buycard' || $table == 'prom_record') {
            $where = "order_sn = '$order_sn'";
        }
        $order_sn_count = Db::name($table)->where($where)->count();
        if ($order_sn_count == 0)
            break;
    }
    return $order_sn;
}

/**
 * 给商家发送站内消息
 * @param $store_id
 * @param $message
 * @return int|string
 */
function addStoreMsg($store_id, $message)
{
    $message = [
        'store_id' => $store_id,
        'content'  => $message,
        'addtime'  => time(),
        'open'     => 0,
    ];
    return Db::name('store_msg')->insertGetId($message);
}

/**
 * 刷新规格库存
 * @param $goods_id
 * @return int
 */
function refresh_stock($goods_id)
{
    //TODO 优化SQL
    $total_stock = Db::name('products')->where('goods_id', $goods_id)->sum('product_number');
    Db::name('goods')->where('goods_id', $goods_id)->update(['goods_number' => $total_stock]);
    return $total_stock;
}

function goods_stock_log($goods_id, $num, $goods_name, $store_id, $muid = 0, $goods_spec = '', $order_sn = '', $username = '前台用户下单')
{
    $row = [
        'goods_id'   => $goods_id,
        'goods_name' => $goods_name,
        'goods_spec' => $goods_spec,
        'store_id'   => $store_id,
        'stock'      => $num,
        'muid'       => $muid,
        'username'   => $username,
        'order_sn'   => $order_sn,
        'ctime'      => time()
    ];
    return Db::name('goods_stock_log')->insertGetId($row);
}

#后台数据统计
function getSellDataByCommon()
{
    $info                    = [];
    $info['goodsCountSelf']  = Db::name("goods")
        ->where("store_id=0")
        ->count("goods_id");
    $info['goodsCountStore'] = Db::name("goods")->where("store_id!=0")->count("goods_id");
    $info['userTodayAdd']    = Db::name('user')
        ->where("DATEDIFF(reg_time,NOW())=0")
        ->count("user_id");
    $info['userYesdayAdd']   = Db::name('user')
        ->where("DATEDIFF(reg_time,NOW())=-1")
        ->count("user_id");
    $info['userMonthAdd']    = Db::name('user')
        ->where("DATE_FORMAT(reg_time, '%Y%m' ) = DATE_FORMAT( CURDATE( ) , '%Y%m' )")
        ->count("user_id");
    $info['userCount']       = Db::name('user')->count("user_id");

    $info['orderTodaySum'] = Db::name("order")
        ->where("DATEDIFF(add_time,NOW())=0")
        ->where("order_status=1 or order_status=2 or order_status=4")
        ->sum('order_amount');

    $info['orderStatus0Sum']    = Db::name("order")
        ->where("DATEDIFF(add_time,NOW())=0")
        ->where("order_status=0")
        ->count('order_id');
    $info['orderpayStatus0Sum'] = Db::name("order")
        ->where("DATEDIFF(add_time,NOW())=0")
        ->where("pay_status=0")
        ->count('order_id');
    $info['orderDaifahuo']      = Db::name("order")
        ->where("DATEDIFF(add_time,NOW())=0")
        ->where("order_status=1 and pay_status=1 and shipping_status=0")
        ->count('order_id');

    $info['orderComplete'] = Db::name("order")
        ->where("DATEDIFF(add_time,NOW())=0")
        ->where("order_status=1 and pay_status=1 and shipping_status=1")
        ->count('order_id');

    $info['orderTodayCount']      = Db::name("order")
        ->where("DATEDIFF(add_time,NOW())=0")
        ->count('order_id');
    $info['commentTodayCount']    = Db::name("comment")
        ->where("DATEDIFF(create_time,NOW())=0")
        ->count('comment_id');
    $info['storeCount']           = Db::name("store")->count("store_id");
    $info['merchant_apply_Count'] = Db::name("merchant_apply")->where('status', '=', 1)->count();;

    $prefix        = config('database.prefix');
    $sql           = "SELECT
                                COUNT(order_id) as cou,
                                SUM(IF(order_status = 0, 1, 0)) as con0,
                                SUM(IF(order_status = 1, 1, 0)) as con1,
                                SUM(IF(order_status = 2, 1, 0)) as con2,
                                SUM(IF(order_status = 3, 1, 0)) as con3,
                                SUM(IF(order_status = 4, 1, 0)) as con4,
                                SUM(IF(order_status = 5, 1, 0)) as con5,
                                SUM(IF(order_status = 6, 1, 0)) as con6,
                            
                                SUM(IF(pay_status = 0, 1, 0)) as pcon0,
                                SUM(IF(pay_status = 1, 1, 0)) as pcon1,
                                SUM(IF(shipping_status = 0, 1, 0)) as scon0,
                                SUM(IF(shipping_status = 1, 1, 0)) as scon1,
                                SUM(IF(shipping_status = 2, 1, 0)) as scon2,
                                SUM(IF(shipping_status = 3, 1, 0)) as scon3
                            FROM
                                {$prefix}order
                            WHERE
                                store_id = 0;";
    $ordertmep     = Db::query($sql);
    $info['order'] = $ordertmep[0];
    return $info;
}

function gmtime()
{
    return time() - date('Z');
}

function local_mktime($hour = NULL, $minute = NULL, $second = NULL, $month = NULL, $day = NULL, $year = NULL)
{

    $timezone = 8;#(GMT +08:00) Beijing, Hong Kong, Perth, Singapore, Taipei
    $time     = mktime($hour, $minute, $second, $month, $day, $year) - $timezone * 3600;
    return $time;
}

function local_date($format, $time = NULL)
{
    $timezone = 8;

    if ($time === NULL) {
        $time = gmtime();
    } else if ($time <= 0) {
        return '';
    }

    $time += $timezone * 3600;
    return date($format, $time);
}

function local_getdate($timestamp = NULL)
{
    $timezone = 8;
    if ($timestamp === NULL) {
        $timestamp = time();
    }
    $gmt        = $timestamp - date('Z');
    $local_time = $gmt + $timezone * 3600;
    return getdate($local_time);
}

function local_strtotime($str)
{
    $timezone = 8;
    $time     = strtotime($str) - ($timezone * 3600);
    return $time;
}

#后台数据统计

/**
 * 获取当前分类下的所有子分类的ID集合或字符串格式
 * @param $cat_id
 * @param $str
 * @return String
 */
function getChildrenData($cat_id = 0, $str = 0)
{

    if ($cat_id == 0) {
        return [];
    }
    $cmap = [];
    //$cmap[] = ['is_show', 'eq', 1];
    $cmap[]       = ['is_delete', 'eq', 0];
    $field        = 'cat_id,parent_id,cat_name';
    $categoryList = Db::name("category")
        ->where($cmap)
        ->where("cat_id={$cat_id}")
        ->order("sort_order desc")
        ->field($field)
        ->select();
    $childrenIds  = $cat_id;
    if ($categoryList) {
        $listTwo = Db::name("category")
            ->where($cmap)
            ->where('parent_id', 'IN', array_column($categoryList, 'cat_id'))
            ->order("sort_order desc")
            ->field($field)
            ->select();
        if (isset($listTwo) && $listTwo) {
            $childrenIds .= ',' . implode(',', array_column($listTwo, 'cat_id'));
            $listThree   = Db::name("category")
                ->where($cmap)
                ->where('parent_id', 'IN', array_column($listTwo, 'cat_id'))
                ->order("sort_order desc")
                ->field($field)
                ->select();

        }
        if (isset($listThree) && $listThree) {
            $childrenIds .= ',' . implode(',', array_column($listThree, 'cat_id'));
            $listFour    = Db::name("category")
                ->where($cmap)
                ->where('parent_id', 'IN', array_column($listThree, 'cat_id'))
                ->order("sort_order desc")
                ->field($field)
                ->select();
        }
        if (isset($listFour) && $listFour) {
            $childrenIds .= ',' . implode(',', array_column($listFour, 'cat_id'));
        }
    }
    #$childrenIds = $cat_id . ',' . implode(',', array_column($listTwo, 'cat_id')) . ',' . implode(',', array_column($listThree, 'cat_id')) . implode(',', array_column($listFour, 'cat_id'));
    #echo $childrenIds; exit;

    if (isset($listThree) && !empty($listThree)) {
        foreach ($listThree as $k => $v) {
            $ids      = array_column($listFour, 'parent_id');
            $valCount = array_count_values($ids);
            if (array_key_exists($v['cat_id'], $valCount)) {
                $listThree[$k]['children'] = array_slice(
                    $listFour,
                    array_search($v['cat_id'], $ids),
                    $valCount[$v['cat_id']]
                );
            }
        }
    }
    if (isset($listTwo) && !empty($listTwo)) {
        foreach ($listTwo as $k => $v) {
            $ids      = array_column($listThree, 'parent_id');
            $valCount = array_count_values($ids);
            if (array_key_exists($v['cat_id'], $valCount)) {
                $listTwo[$k]['children'] = array_slice(
                    $listThree,
                    array_search($v['cat_id'], $ids),
                    $valCount[$v['cat_id']]
                );
            }
        }
    }
    switch ($str) {
        case 0:
            return $listTwo;
            break;
        case 1:
            return $childrenIds;
            break;
    }
}


/**
 * 获取商品分类下的所有商品对应的品牌集合
 * @param $cat_id
 * @return []
 */
function getcatBrandList($cat_id = 0, $limit = 12)
{
    if ($cat_id == 0) {
        return [];
    }
    $cat_ids   = trim(getChildrenData($cat_id, $str = 1), ',');
    $map       = [];
    $map[]     = ['a.cat_id', 'in', $cat_ids];
    $brandList = Db::name("goods")->alias("a")
        ->join("brand b", "a.brand_id=b.brand_id", "LEFT")
        ->field("a.cat_id,b.brand_id,brand_logo")
        ->where($map)
        ->group("a.brand_id")
        ->limit($limit)
        ->select();
    return $brandList;
}

function updatePayStatus($code, $param)
{
    try {
        trace('支付updatePayStatus：' . $code . '==' . json_encode($param, 320));
        $param['out_trade_no'] = substr($param['out_trade_no'], 0, 18);
        if (strpos($param['out_trade_no'], 'P') === 0) {
            return updatePromOrderStatus($param);
        }
        $order_list = Db::name('order')->where(['master_order_sn' => $param['out_trade_no']])->select();
        if (!$order_list) {
            $order_list = Db::name('order')->where(['order_sn' => $param['out_trade_no']])->select();
        }

        $param['pay_name'] = $code == 'ALI' ? '支付宝' : '微信支付';
        foreach ($order_list as $index => $order) {
            if ($order['pay_status'] > 0) {
                continue;
                //return '已付款订单不再重复响应notify';
            }

            // 1.修改订单状态
            $data = [
                'pay_status'     => 1,
                'transaction_id' => $param['transaction_id'] ?? $param['trade_no'],
                'pay_time'       => time(),
                'pay_code'       => input('pay_code'),
                'pay_name'       => $param['pay_name'],
                'order_amount'   => isset($param['total_fee']) ? round($param['total_fee'] / 100, 2) : $param['total_amount']
            ];
            if ($order['user_money'] > 0) {
                //扣除余额
                $log_res = (new \app\home\model\AccountLog)->accountLog($order['user_id'], (-1) * $order['user_money'], '用户订单支付', 0, 0, $order['order_id'], $order['order_sn']);
                if ($log_res !== true) {
                    $data['abnormal'] = $param['pay_name'] . '支付【' . $data['order_amount'] . '】元成功，余额抵扣【' . $order['user_money'] . '元】错误：' . $log_res;
                }
            }
            Db::name('order')->where('order_id', $order['order_id'])->update($data);

            //2. 根据店铺设置，减少对应商品的库存
            if ($order['store_id'] == 0) {
                $stock_type = Db::name('shop_config')->where('code', 'stock_dec_time')->value('value');
            } else {
                $stock_type = Db::name('store')->where('store_id', $order['store_id'])->value('stock_type');
            }
            if ($stock_type == 1) {
                minus_stock($order);
            }

            //TODO 会员升级

            //3. 订单操作日志
            $order['pay_status'] = 1;
            orderLog($order, '订单付款成功', '付款成功', $order['user_id'], $order['consignee'], 2);

            //4. 订单结算
            //TODO 订单活动赠送优惠券 积分 赠品等

            //5. 通知卖家
            addStoreMsg($order['store_id'], $order['order_sn'] . '付款成功,请及时处理');
        }
        return true;
    } catch (Exception $e) {
        return $e->getMessage();
    }
}

/**
 * 活动支付订单 回调到此处
 * @param $param
 * @return bool
 */
function updatePromOrderStatus($param)
{
    $record = Db::name('prom_record')->where('order_sn', $param['out_trade_no'])->find();
    if (!$record) {
        return false;
    }
    if ($record['pay_time'] > 0) {
        //重复回调不再响应.
        return true;
    }
    $update = ['pay_time' => time(), 'status' => 2];
    if ($record['user_money'] > 0) {
        $res = (new \app\home\model\AccountLog)->accountLog($record['user_id'], (-1) * $record['user_money'], '用户活动订单支付', 0, 0, $record['rec_id'], $record['order_sn']);
        if (true !== $res) {
            $update['abnormal'] = $param['pay_name'] . ' 支付成功，余额扣除失败：' . $res;
        }
    }
    $res = Db::name('prom_record')->where('rec_id', $record['rec_id'])->update();
    if ($res) return true;
    else return false;
}

/**
 * 根据订单减少对应商品库存
 * @param $order
 */
function minus_stock($order)
{
    $orderGoodsArr = Db::name('order_goods')->where(['order_id' => $order['order_id']])->select();
    foreach ($orderGoodsArr as $key => $val) {
        // 有规格的商品
        if ($val['product_id'] > 0) {
            $spec      = Db::name('products')->where('product_id', $val['product_id'])->field('product_number,product_warn_number,tempvalue')->find();
            $remaining = $spec['product_number'] - $val['goods_num'];
            //超卖通知商家
            if ($remaining < 0) {
                addStoreMsg($order['store_id'], '商品[' . $val['goods_id'] . $val['goods_name'] . ']库存不足,订单[' . $order['order_sn'] . ']已经超卖!!');
            } elseif ($remaining <= $spec['product_warn_number']) {
                //库存预警
                addStoreMsg($order['store_id'], '商品[' . $val['goods_id'] . $val['goods_name'] . ']库存预警 : ' . $remaining);
            }
            Db::name('products')->where('product_id', $val['product_id'])->setDec('product_number', $val['goods_num']);
            goods_stock_log($val['goods_id'], -1 * $val['goods_num'], $val['goods_name'], $order['store_id'], $order['user_id'], $spec['tempvalue'], $order['order_sn']);//出库日志
            refresh_stock($val['goods_id']);
        } else {
            $goods     = Db::name('goods')->where('goods_id', $val['goods_id'])->field('goods_number,warn_number')->find();
            $remaining = $goods['goods_number'] - $val['goods_num'];

            if ($remaining < 0) {
                addStoreMsg($order['store_id'], '商品[' . $val['goods_id'] . $val['goods_name'] . ']库存不足,订单[' . $order['order_sn'] . ']已经超卖!!');
            } elseif ($remaining < $goods['warn_number']) {
                addStoreMsg($order['store_id'], '商品[' . $val['goods_id'] . $val['goods_name'] . ']库存预警 : ' . $remaining);
            }
            Db::name('goods')->where('goods_id', $val['goods_id'])->setDec('goods_number', $val['goods_num']);
            goods_stock_log($val['goods_id'], -1 * $val['goods_num'], $val['goods_name'], $order['store_id'], $order['user_id'], '', $order['order_sn']);//出库日志
        }

        //增加商品累计销售量
        Db::name('goods')->where('goods_id', $val['goods_id'])->setInc('total_sale_num', $val['goods_num']);

        //TODO 更新活动商品购买量
        if ($val['prom_type'] > 0) {

        }
    }
}

/**
 * 根据订单获取详细的收货地址
 * @param $order
 */
function getAddressWithOder($order)
{
    if (!$order) {
        return '';
    }
    if (isset($order['_source']) && $order['_source'] == '小程序' && $order['regionname']) {
        return $order['regionname'] . $order['address'];
    }
    $where = $order['province'] . ',' . $order['city'] . ',' . $order['district'];
    $map   = [];
    $map[] = ['region_id', 'in', $where];
    $list  = Db::name("region")
        ->field("region_name")
        ->where($map)
        ->select();
    if (isset($order['address'])) {
        $str = trim(implode('', array_column($list, 'region_name')) . $order['address']);
    } else {
        $str = trim(implode('', array_column($list, 'region_name')));
    }

    return $str;
}

function getprovincecitydistrict($order)
{
    if (!$order) {
        return '';
    }
    $where = $order['province'] . ',' . $order['city'] . ',' . $order['district'];
    $map   = [];
    $map[] = ['region_id', 'in', $where];
    $list  = Db::name("region")
        ->field("region_name")
        ->where($map)
        ->select();
    $str   = trim(implode('', array_column($list, 'region_name')));
    return $str;
}


/**
 * 判断用户是有有默认地址
 * @param $user_id
 * @return is_default= 0 OR 1
 */
function setDefaultAddress($user_id)
{
    $user_address = Db::name("user_address")->where("user_id={$user_id} and is_default=1")->find();
    if ($user_address) {
        return 0;
    } else {
        return 1;
    }
}

#团购秒杀路由中转
function promtypeInit($user_id)
{
    $pageparm = input('post.');
    if (!isset($pageparm["promtype"]) || !in_array($pageparm["promtype"], ['group_goods', 'seckill_goods'])) {
        return false;
    } else {
        $promtype = $pageparm["promtype"];
    }
    $res                = [
        'goods_price' => 0,
        'prom_id'     => 0,
        'prom_type'   => 0//// 0 普通订单,1 限时抢购, 2 团购 , 3 促销优惠
    ];
    $pageparm['number'] = $pageparm['number'] ?? $pageparm['num'];
    switch ($promtype) {
        case 'group_goods':
            $group_goods = Db::name("group_goods")
                ->where("goods_id={$pageparm['goods_id']}")
                ->find();
            $cart        = Db::name('cart')->where(['user_id' => $user_id, 'goods_id' => $pageparm['goods_id'], 'prom_type' => 2])->find();
            if ($cart) {
                $pageparm['number'] += $cart['goods_num'];
            }
            #$ext_info    = array_sort(json_decode($group_goods['ext_info'], true), 'num', SORT_DESC);
            $ext_info = json_decode($group_goods['ext_info'], true);
            array_multisort(array_column($ext_info, 'num'), SORT_DESC, $ext_info, SORT_NUMERIC);
            $res['prom_id']     = $group_goods['gid'];
            $res['prom_type']   = 2;
            $res['goods_price'] = $group_goods['shop_price'];
            foreach ($ext_info as $k => $v) {
                if ($v['num'] <= $pageparm['number']) {
                    $res['goods_price'] = $v['price'];
                    break;
                }
            }

            break;
        case 'seckill_goods':
            $seckill_goods      = Db::name("seckill_goods")
                ->where("goods_id={$pageparm['goods_id']}")
                ->find();
            $res['goods_price'] = $seckill_goods['sec_price'];
            $res['prom_type']   = 1;
            $res['prom_id']     = $seckill_goods['id'];

            break;
    }
    return $res;
}

#二维数组按某个键值排序
function array_sort($array, $on, $order = SORT_ASC)
{
    $new_array      = [];
    $sortable_array = [];
    if (count($array) > 0) {
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                foreach ($v as $k2 => $v2) {
                    if ($k2 == $on) {
                        $sortable_array[$k] = $v2;
                    }
                }
            } else {
                $sortable_array[$k] = $v;
            }
        }
        switch ($order) {
            case SORT_ASC:
                asort($sortable_array);
                break;
            case SORT_DESC:
                arsort($sortable_array);
                break;
        }
        foreach ($sortable_array as $k => $v) {
            $new_array[$k] = $array[$k];
        }
    }
    return $new_array;
}

/**
 * 短信接口触发器
 * @param $mobile 手机号，调用之前需要验证手机号的合法性
 */
function sendsms($mobile, $code)
{
    //$code = mt_rand(1111, 9999);
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
        ajaxmsg('发送成功', 1, ['smscode' => $code]);
    } else {
        ajaxmsg('发送失败', 0);
    }
}

/**
 * 领取优惠券
 * @param $coupon_id 优惠券ID
 * @param $user_id   会员ID
 * @return  boolean
 */
function getcoupon($coupon_id = 0, $user_id)
{
    $time   = time();
    $coupon = Db::name("coupon")->where(['coupon_id' => $coupon_id])->find();
    if (!$coupon) {
        return '优惠券信息不存在';
    }
    if ($coupon['send_start_time'] > $time || $coupon['send_end_time'] < $time) {
        return '本优惠券领取时间为 ' . date('Y-m-d H:i:s', $coupon['send_start_time']) . ' ~ ' . date('Y-m-d H:i:s', $coupon['send_end_time']);
    }
    if ($coupon['num'] <= $coupon['send_num']) {
        return '优惠券共' . $coupon['num'] . '张，' . '已发放完毕!';
    }
    $con = Db::name("coupon_list")
        ->where(['user_id' => $user_id, 'coupon_id' => $coupon_id])
        ->count();
    if ($con > 0) {
        return '您已经领取过该券了!每人限领取 1 张';
    }
    $list_id = Db::name("coupon_list")->insertGetId([
        'coupon_id' => $coupon_id,
        'user_id'   => $user_id,
        'bind_time' => $time,
    ]);
    if ($list_id > 0) {
        Db::name("coupon")->where(['coupon_id' => $coupon_id])->setInc('send_num', 1);
        return true;
    } else {
        return '领取失败';
    }
}

/**
 * 姓名|昵称|手机号搜索会员列表
 * @param $keywords
 * @param $limit
 * @return array
 */
function searchUser($keywords, $limit = 50)
{
    if (preg_match('/^1[3-9][0-9]\d{8}$/', $keywords)) {
        $list = Db::name('user')->where('mobile', $keywords)->limit($limit)->column('IF(nick_name,nick_name,user_name) as name', 'user_id');
    } else {
        $list = Db::name('user')->where('user_name|nick_name', 'LIKE', "%$keywords%")->limit($limit)->column('IF(nick_name,nick_name,user_name) as name', 'user_id');
    }
    return $list;
}

/**
 * 生成发货单
 * @return string
 */
function get_delivery_sn()
{
    mt_srand((double)microtime() * 1000000);
    return date('YmdHi') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
}

/**
 * 计算结算金额
 * @param $order_id
 * @return bool|string
 */
function pendding_money($order_id)
{
    $count = Db::name('order_settle')->where('order_id', $order_id)->count();
    //过滤已结算过的订单
    if ($count) {
        return '重复计算';
    }
    $order = Db::name('order')->where('order_id', $order_id)->find();
    if ($order['pay_code'] == 'cod') {
        return '货到付款订单';
    }
    $store       = Db::name('store')->where('store', $order['store_id'])->find();
    $order_goods = Db::name('order_goods')->where('order_id', $order_id)->select();
    //$point_rate    = Db::name('shop_config')->where('code', 'point_rate')->value('value');
    $point_rate    = 100;//FIXME 待后台设置积分兑换比例
    $give_integral = array_sum(array_column($order_goods, 'give_integral'));

    $row = [
        'store_id'          => $order['store_id'],
        'order_sn'          => $order['order_sn'],
        'order_totals'      => $order['total_amount'],
        'shipping_totals'   => $order['shipping_price'],
        'return_totals'     => 0,//TODO 退货流程
        'give_integral'     => round($give_integral / $point_rate, 2),
        'integral'          => $order['intgral_money'],
        'discount'          => $order['discount'],
        'order_prom_amount' => $order['order_prom_amount'],
        'coupon_price'      => $order['coupon_price'],
        'status'            => 0,
    ];

    //待结算金额 = 实付金额 + 支付余额 + 商家调价 + 积分抵扣 - 商家赠出积分金额
    $total_amount = $order['order_amount'] + $order['user_money'] + $order['discount'] + $order['ingral_money'] - $row['give_integral'];
    // 按店铺费率结算
    if ($store['commission_type'] == 0) {
        $row['commis_rate']   = $store['commission_rate'];//平台抽成比例
        $row['commis_totals'] = round($total_amount / $store['commission_rate'], 2);//平台抽成金额
        $row['result_totals'] = ($total_amount - $row['commis_totals']);//最终结算金额
    } else {
        //按商品分类计算费率
        $cat_rate             = Db::name('category')->whereIn('cat_id', array_column($order_goods, 'cat_id'))->column('commission_rate', 'cat_id');
        $row['commis_rate']   = '按商品分类';
        $row['commis_totals'] = 0;
        foreach ($order_goods as $index => $goods) {
            $goods_amount         = $goods['goods_price'] * $goods['goods_num'];
            $settlement_rate      = round($goods_amount / $total_amount, 4);//此商品占应结算金额的比例
            $row['commis_totals'] += round($goods_amount * ($settlement_rate / 100) * ($cat_rate[$goods['goods_id']] / 100), 2);
        }
        $row['result_totals'] = $total_amount - $row['commis_totals'];
    }

    //店铺待结算金额增加
    Db::name('store')->where('store_id', $order['store_id'])->update(['pendding_money' => Db::raw('pendding_money+' . $row['result_totals'])]);
    Db::name('order_settle')->insert($row);
    return true;
}

/**
 * 属性分组 数据格式组装
 * @param $attr_list
 * @return array
 */
function fixProductAttr($attr_list)
{
    $result = [
        'other' => [],
        'group' => []
    ];
    if ($attr_list) {
        array_walk($attr_list, function (&$v) {
            if ($v['attrvaluejson']) {
                $v['attrvaluejson'] = json_decode($v['attrvaluejson'], true);
            }
        });
        $ids       = array_column($attr_list, 'group_id');
        $temp      = array_unique($ids);
        $group_ids = [];
        array_walk($temp, function ($id) use (&$group_ids) {
            if ($id > 0) $group_ids[] = $id;
        });

        $val_count = array_count_values($ids);
        if (isset($val_count[0])) {
            $result['other'] = array_slice($attr_list, 0, $val_count[0]);
        }

        if ($group_ids) {
            $group = Db::name('product_group')->whereIn('group_id', $group_ids)->orderRaw('field(group_id,' . implode(',', $group_ids) . ')')->column('name', 'group_id');
            foreach ($group as $gid => $gname) {
                $result['group'][] = [
                    'group_name' => $gname,
                    'data'       => array_slice($attr_list, array_search($gid, $ids), $val_count[$gid])
                ];
            }
        }
    }
    return $result;
}

/**
 *
 * 发货单发货操作=>操作订单发货状态
 * @param $ids =1,2,3 发货单主键ID集合
 * @param $shipping_status 发货状态  0：未发货 1：发货中 2：已发货 3：部分发货，4：已签收
 *
 * UPDATE oc_order a,
 * (SELECT order_id,SUM(goods_num)as goodsnum,SUM(send_number) as sendnum
 * FROM oc_order_goods
 * WHERE order_id in (SELECT order_id FROM `oc_delivery_order` where delivery_id in (3,4,18,19,22) GROUP BY order_id)
 * GROUP BY order_id) b
 * SET a.shipping_status=1
 * WHERE a.order_id=b.order_id and b.goodsnum=b.sendnum;
 */
function spUpdate($ids = '')
{
    $prefix = Config::get('database.prefix');
    $ids    = trim($ids, ',');
    $sql    = "UPDATE {$prefix}order a,
            (SELECT order_id,SUM(goods_num)as goodsnum,SUM(send_number) as sendnum 
            FROM  {$prefix}order_goods 
            WHERE order_id in (SELECT order_id FROM  {$prefix}delivery_order where delivery_id in ({$ids}) GROUP BY order_id)
            GROUP BY order_id) b 
            SET a.shipping_status=2
            WHERE a.order_id=b.order_id and b.goodsnum=b.sendnum;";
    $rows   = Db::execute($sql);
    return $rows;
}

#更新店铺服务评分
function upstoreScore($store_id = 0)
{
    if ($store_id == 0) {
        return false;
    }
    $prefix = Config::get('database.prefix');
    $sql    = "UPDATE {$prefix}store AS store,
        (SELECT COUNT(comment_id) as countIDS,
        ROUND(SUM(desc_rank)/COUNT(comment_id),1) as store_desc_score,
        ROUND(SUM(service_rank)/COUNT(comment_id),1) as store_service_score,
        ROUND(SUM(delivery_rank)/COUNT(comment_id),1) as store_delivery_score,
        ROUND(SUM(sender_rank)/COUNT(comment_id),1) as store_average_score
        FROM {$prefix}comment WHERE store_id={$store_id} AND comment_type=1
        GROUP BY store_id) AS storeTemp 
        SET 
        store.store_desc_score=storeTemp.store_desc_score,
        store.store_service_score=storeTemp.store_service_score,
        store.store_delivery_score=storeTemp.store_delivery_score,
        store.store_average_score=storeTemp.store_average_score
        WHERE store_id={$store_id}";
    $rows   = Db::execute($sql);
    return $rows;
}

function createpwd($length = 32, $char = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ')
{
    if (!is_int($length) || $length < 0) {
        return false;
    }

    $string = '';
    for ($i = $length; $i > 0; $i--) {
        $string .= $char[mt_rand(0, strlen($char) - 1)];
    }

    return $string;
}

function selfEnCrypt($txtStream, $password = 'itolfnxkj')
{
    //密锁串，不能出现重复字符，内有A-Z,a-z,0-9,/,=,+,_,
    $lockstream = 'st=lDEFABCNOPyzghi_jQRST-UwxkVWXYZabcdef+IJK6/7nopqr89LMmGH012345uv';
    //随机找一个数字，并从密锁串中找到一个密锁值
    $lockLen    = strlen($lockstream);
    $lockCount  = rand(0, $lockLen - 1);
    $randomLock = $lockstream[$lockCount];
    //结合随机密锁值生成MD5后的密码
    $password = md5($password . $randomLock);
    //开始对字符串加密
    $txtStream = base64_encode($txtStream);
    $tmpStream = '';
    $k         = 0;
    for ($i = 0; $i < strlen($txtStream); $i++) {
        $k         = ($k == strlen($password)) ? 0 : $k;
        $j         = (strpos($lockstream, $txtStream[$i]) + $lockCount + ord($password[$k])) % ($lockLen);
        $tmpStream .= $lockstream[$j];
        $k++;
    }
    return $tmpStream . $randomLock;
}

function selfDeCrypt($txtStream, $password = 'itolfnxkj')
{
    //获得字符串长度
    $txtLen = strlen($txtStream);
    if (!$txtLen)
        return '';
    //密锁串，不能出现重复字符，内有A-Z,a-z,0-9,/,=,+,_,
    $lockstream = 'st=lDEFABCNOPyzghi_jQRST-UwxkVWXYZabcdef+IJK6/7nopqr89LMmGH012345uv';
    $lockLen    = strlen($lockstream);
    //截取随机密锁值
    $randomLock = $txtStream[$txtLen - 1];
    //获得随机密码值的位置
    $lockCount = strpos($lockstream, $randomLock);
    //结合随机密锁值生成MD5后的密码
    $password = md5($password . $randomLock);
    //开始对字符串解密
    $txtStream = substr($txtStream, 0, $txtLen - 1);
    $tmpStream = '';
    $k         = 0;
    for ($i = 0; $i < strlen($txtStream); $i++) {
        $k = ($k == strlen($password)) ? 0 : $k;
        $j = strpos($lockstream, $txtStream[$i]) - $lockCount - ord($password[$k]);
        while ($j < 0) {
            $j = $j + ($lockLen);
        }
        $tmpStream .= $lockstream[$j];
        $k++;
    }
    return base64_decode($tmpStream);
}

/**
 * 快递查询
 * @param $no
 * @param string $type
 * @return mixed|string
 */
function expressQuery($no, $type = 'auto')
{
    $host    = "https://kuaidi100.market.alicloudapi.com";
    $appcode = "feb3a279d436452fa411954bf8cdbd63";

    $client = new Client([
        'verify'   => false,
        'base_uri' => $host,
        'timeout'  => 10,
    ]);
    try {
        $response = $client->get('/getExpress', [
            'headers' => ['Authorization' => 'APPCODE ' . $appcode],
            'query'   => ['NO' => $no, 'TYPE' => $type],
        ]);

        if ($response->getStatusCode() != 200) {
            throw new \Exception('物流查询异常：' . $response->getBody()->getContents());
        }
        $res = json_decode($response->getBody()->getContents(), true);
        if (json_last_error() != JSON_ERROR_NONE) {
            throw new \Exception('物流响应错误：' . $response->getBody()->getContents());
        }
        if ($res['status'] != 200) {
            return $res['message'];
        }

        return $res;
    } catch (\Exception $e) {
        trace($e->getMessage());
        return '暂未查询到相关物流信息，请稍后再试';
    }
}

function orderCount($uid)
{
    $res  = [
        'total'        => 0,
        'wait_pay'     => 0,
        'wait_send'    => 0,
        'wait_recive'  => 0,
        'wait_comment' => 0,
        'completed'    => 0,
        'cancel'       => 0,
        'invalid'      => 0,
        'refund'       => 0,
    ];
    $list = Db::name('order')->where('user_id', $uid)->field('order_status,pay_status,shipping_status')->select();
    if ($list) {
        $res['total'] = count($list);
        foreach ($list as $index => $item) {
            if ($item['order_status'] == 6) {
                $res['refund']++;
                continue;
            }
            if ($item['order_status'] == 5) {
                $res['invalid']++;
                continue;
            }
            if ($item['order_status'] == 4) {
                $res['completed']++;
                continue;
            }
            if ($item['order_status'] == 3) {
                $res['cancel']++;
                continue;
            }
            if ($item['order_status'] == 2) {
                $res['wait_comment']++;
                continue;
            }
            if ($item['order_status'] < 2 && $item['shipping_status'] == 2) {
                $res['wait_recive']++;
                continue;
            }
            if (($item['order_status'] == 1 || $item['order_status'] == 0) && $item['pay_status'] == 1 && $item['shipping_status'] != 2) {
                $res['wait_send']++;
                continue;
            }
            if ($item['order_status'] == 0 && $item['pay_status'] == 0) {
                $res['wait_pay']++;
                continue;
            }
            //FIXME 货到付款情况
        }
    }
    return $res;
}

function fixTree(array $list, array $children, string $key, string $foregion)
{
    $ids      = array_column($children, $foregion);
    $valCount = array_count_values($ids);
    foreach ($list as $index => $item) {
        $list[$index][$key] = isset($valCount[$item[$foregion]]) ? array_slice($children, array_search($item[$foregion], $ids), $valCount[$item[$foregion]]) : [];
    }
    return $list;
}