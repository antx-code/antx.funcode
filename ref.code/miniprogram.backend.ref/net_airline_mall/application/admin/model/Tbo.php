<?php
/**
 * Created by PhpStorm.
 * User: 835173372@qq.com
 * NickName: 老孟编程
 * Date: 2019/2/22 13:57
 */

namespace app\admin\model;


use think\Model;
use think\facade\Config;

class Tbo extends Model
{
    protected $pk = 'id';
    protected $table = 'oc_store_process_steps';
    protected $prefix = '';

}