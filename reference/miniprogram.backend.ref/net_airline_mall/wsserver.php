<?php
/**
 * Created by PhpStorm.
 * User: 835173372@qq.com
 * NickName: 老孟编程
 * Date: 2019/8/28 0028 14:55
 */

error_reporting(0);
Swoole\Runtime::enableCoroutine(true);

use think\cache\Redis;

if (!preg_match("/cli/i", php_sapi_name())) {
    //echo '此脚本运行在命令号环境下', PHP_EOL;
    //exit;
}


class DB
{
    public    $pdo;
    protected $res;
    protected $config;

    function __construct($config)
    {
        $this->Config = $config;
        $this->connect();
    }

    function __destruct()
    {
        $this->pdo = null;
    }

    public function connect()
    {
        try {
            $this->pdo = new PDO($this->Config['dsn'], $this->Config['name'], $this->Config['password'], [
                PDO::ATTR_PERSISTENT => true // PDO长连接
            ]);
        } catch (PDOException $e) {
            echo 'Connection failed: ' . $e->getMessage();
        }
        $this->pdo->query('set names utf8;');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function query($sql)
    {
        $this->ping();
        $res = $this->pdo->query($sql);
        if ($res) {
            $this->res = $res;
        }
    }

    public function exec($sql)
    {
        $this->ping();
        $res = $this->pdo->exec($sql);
        if ($res) {
            $this->res = $res;
        }
    }

    public function getAll($sql)
    {
        $this->query($sql);
        return $this->res->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRow($sql)
    {
        $this->query($sql);
        return $this->res->fetch(PDO::FETCH_ASSOC);
    }


    public function getOne($sql)
    {
        $this->query($sql);
        return $this->res->fetchColumn(PDO::FETCH_ASSOC);
    }

    public function insertId()
    {
        return $this->pdo->lastInsertId();
    }

    public function add($table, $dataset, $debug = 0)
    {
        if (empty($table)) {
            throw new Exception("表名不能为空");
        }
        if (!is_array($dataset) || count($dataset) <= 0) {
            throw new Exception('没有要插入的数据');
        }
        $value = '';
        while (list($key, $val) = each($dataset)) {
            $value .= "`{$key}`='{$val}',";
        }
        $value = substr($value, 0, -1);
        if ($debug === 0) {
            $this->query("insert into `{$table}` set {$value}");
            if (!$this->res) {
                return FALSE;
            } else {
                return $this->insertId();
            }
        } else {
            echo "insert into `{$table}` set {$value}";
            if ($debug === 2) {
                exit;
            }
        }
    }

    public function update($table, $dataset, $conditions = "", $debug = 0)
    {
        if (empty($table)) {
            throw new Exception("表名不能为空");
        }
        if (!is_array($dataset) || count($dataset) <= 0) {
            throw new Exception('没有要更新的数据');
            return false;
        }
        if (empty($conditions)) {
            throw new Exception("删除条件为空");
        }
        $conditions = " where " . $conditions;
        $value      = '';
        while (list($key, $val) = each($dataset)) {
            $value .= "`$key`='$val',";
        }
        $value = substr($value, 0, -1);
        if ($debug === 0) {
            $this->exec("update `{$table}` set {$value} {$conditions}");
            return $this->res;
        } else {
            echo "update `{$table}` set {$value} {$conditions}";
            if ($debug === 2) {
                exit;
            }
        }
    }

    public function delete($table, $conditions = "", $debug = 0)
    {
        if (empty($table)) {
            throw new Exception("表名不能为空");
        }
        if (empty($conditions)) {
            throw new Exception("删除条件为空");
        }
        $conditions = " where " . $conditions;
        if ($debug === 0) {
            $this->exec("delete from {$table} {$conditions}");
            return $this->res;
        } else {
            echo "delete from {$table} {$conditions}";
            if ($debug === 2) {
                exit;
            }
        }
    }

    public function getValue($sql)
    {
        $this->query($sql);
        return $this->res->fetchColumn(PDO::FETCH_ASSOC);
    }

    // PDO重连MYSQL
    public function ping()
    {
        try {
            $this->pdo->getAttribute(PDO::ATTR_SERVER_INFO);
        } catch (PDOException $e) {
            if (stripos($e->getMessage(), 'MySQL server has gone away') !== FALSE) {
                $this->connect();
            }
        }
    }

    public function close()
    {
        $this->pdo = null;
    }

}

class Cache
{
    protected static $serialize = ['serialize', 'unserialize', 'think_serialize:', 16];
    protected        $options
                                = [
            'type'       => 'redis',
            'host'       => '127.0.0.1',
            'port'       => 6379,
            'password '  => '',
            'select'     => 0,
            'timeout'    => 0,
            'expire'     => 0,
            'persistent' => true,#开启redis长连接
            'prefix'     => '',
            'serialize'  => true,
        ];

    protected $handler = null;//redis句柄

    protected $tag = null;//tag

    public function __construct($options = [])
    {
        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }
        if (extension_loaded('redis')) {
            $this->handler = new \Redis;

            // 命令行环境Redis长连接
            if ($this->options['persistent']) {
                $this->handler->pconnect($this->options['host'], $this->options['port'], $this->options['timeout'], 'persistent_id_' . $this->options['select']);
            } else {
                $this->handler->connect($this->options['host'], $this->options['port'], $this->options['timeout']);
            }
            $this->handler->setOption(\Redis::OPT_READ_TIMEOUT, -1);
            if ('' != $this->options['password']) {
                $this->handler->auth($this->options['password']);
            }
            if (0 != $this->options['select']) {
                $this->handler->select($this->options['select']);
            }
        } else {
            throw new \Exception('extension :redis is not install');
        }
    }

    public function has($name)
    {
        return $this->handler->exists($this->getCacheKey($name));
    }

    public function get($name, $default = false)
    {

        $value = $this->handler->get($this->getCacheKey($name));
        if (is_null($value) || false === $value) {
            return $default;
        }
        return $this->unserialize($value);
    }

    public function rm($name)
    {
        return $this->handler->delete($this->getCacheKey($name));
    }

    public function set($name, $value, $expire = null)
    {
        if (is_null($expire)) {
            $expire = $this->options['expire'];
        }

        $key = $this->getCacheKey($name);

        $value = $this->serialize($value);

        if ($this->tag && !$this->has($name)) {
            $this->setTagItem($key);
        }

        if ($expire) {
            $result = $this->handler->setex($key, $expire, $value);
        } else {
            $result = $this->handler->set($key, $value);
        }
        return $result;
    }

    public function clear($tag = null)
    {
        if ($tag) {
            $keys = $this->getTagItem($tag);
            $this->handler->del($keys);

            $tagName = $this->getTagKey($tag);
            $this->handler->del($tagName);
            return true;
        }
        return $this->handler->flushDB();
    }

    public function tag($name, $keys = null, $overlay = false)
    {
        if (is_null($keys)) {
            $this->tag = $name;
        } else {
            $tagName = $this->getTagKey($name);
            if ($overlay) {
                $this->handler->del($tagName);
            }

            foreach ($keys as $key) {
                $this->handler->sAdd($tagName, $key);
            }
        }

        return $this;
    }

    protected function setTagItem($name)
    {
        if ($this->tag) {
            $tagName = $this->getTagKey($this->tag);
            $this->handler->sAdd($tagName, $name);
        }
    }

    protected function getTagItem($tag)
    {
        $tagName = $this->getTagKey($tag);
        return $this->handler->sMembers($tagName);
    }

    public function serialize($data)
    {
        if (is_scalar($data) || !$this->options['serialize']) {
            return $data;
        }
        $serialize = self::$serialize[0];
        return self::$serialize[2] . $serialize($data);
    }

    protected function unserialize($data)
    {
        if ($this->options['serialize'] && 0 === strpos($data, self::$serialize[2])) {
            $unserialize = self::$serialize[1];

            return $unserialize(substr($data, self::$serialize[3]));
        } else {
            return $data;
        }
    }

    protected function getCacheKey($name)
    {
        return $this->options['prefix'] . $name;
    }

    protected function getTagKey($tag)
    {
        return 'tag_' . md5($tag);
    }

    public static function getInstance($option = [])
    {
        $redis = new self($option);
        return $redis->handler;
    }
}

class wsserver
{

    public $db    = '';
    public $ws    = '';
    public $redis = '';

    public function __construct($config)
    {
        $this->db    = new DB($config);
        $this->redis = new Cache();
        if (preg_match("/cli/i", php_sapi_name())) {
            $this->ws = new Swoole\WebSocket\Server("0.0.0.0", 9501, SWOOLE_PROCESS, SWOOLE_SOCK_TCP | SWOOLE_SSL);
            $this->ws->set([
                'daemonize'                => 1,
                'heartbeat_idle_time'      => 600,
                'heartbeat_check_interval' => 60,
                'log_file'                 => 'swoole.log',
                'ssl_cert_file'            => '/home/laomeng/djbix/cert/full_chain.pem',
                'ssl_key_file'             => '/home/laomeng/djbix/cert/private.key',
            ]);
            $this->open();
            $this->message();
            $this->close();
            $this->ws->start();
        }
    }

    public function open()
    {
        $this->ws->on('open', function (Swoole\WebSocket\Server $server, $request) {
            echo "server: handshake success with fd{$request->fd}\n";
        });
    }

    public function message()
    {
        $this->ws->on('message', function (Swoole\WebSocket\Server $server, $frame) {

            echo '当前用户FD=' . $frame->fd, PHP_EOL;
            if ($frame->data == 'ping') {
                return false;//心跳处理，避免自动掉线
            }
            if (isset($frame->data)) {
                $pageparm = json_decode($frame->data, true);
                print_r($pageparm);


                #出价记录
                $house_jlist = $this->redis->get('House' . $pageparm['id']);
                if ($house_jlist && isset($house_jlist['list'])) {
                    $jlist = $house_jlist['list'];
                } else {
                    $jlist = [];
                }
                print_r($jlist);

                ###房间信息
                $paimai_house = $this->db->getRow("select * from oc_paimai_house where id='{$pageparm['id']}'");
                if ($paimai_house['status'] == 1) {
                    $paimai_house['end_time'] = $paimai_house['create_time'] + ($paimai_house['time_down'] + $paimai_house['time_total']) * 60 - time();
                }
                if ($paimai_house['status'] == 3) {
                    $paimai_house['end_time'] = $paimai_house['create_time'] + ($paimai_house['time_down']) * 60 - time();
                }
                print_r($paimai_house);
                ###房间信息
                switch ( $pageparm['act'] ) {

                    #离开房间
                    case 'leave':
                        $plist = $this->db->getAll("select a.*,b.* from oc_paihouse_people as a left join  oc_user b on a.user_id=b.user_id where a.house_id = '{$pageparm['id']}' order by a.id desc limit 4");
                        var_dump($plist);
                        foreach ($plist as $k => $v) {
                            $fd = $v['fd'];
                            go(function () use ($fd, $plist, $paimai_house, $jlist, $server) {
                                $server->push($fd, json_encode([
                                    'fd'           => $fd,
                                    'peopleList'   => $plist,
                                    'paimai_house' => $paimai_house,
                                    'jlist'        => $jlist,
                                ], 320));
                            });
                        }
                        break;
                    #进入房间
                    case 'online':
                        $user = $this->db->getRow("select * from oc_user where token = '{$pageparm['token']}'");
                        print_r($user);
                        $paihouse_people = $this->db->getRow("select * from oc_paihouse_people where user_id = '{$user['user_id']}'");
                        print_r($paimai_house);
                        if ($paihouse_people) {
                            $this->db->update('oc_paihouse_people', [
                                'house_id'    => $pageparm['id'],
                                'fd'          => $frame->fd,
                                'create_time' => time(),
                            ], "user_id={$user['user_id']}");
                        } else {
                            $this->db->add('oc_paihouse_people', [
                                'user_id'     => $user['user_id'],
                                'house_id'    => $pageparm['id'],
                                'fd'          => $frame->fd,
                                'create_time' => time(),
                            ]);
                        }
                        #客户端推送信息
                        $plist = $this->db->getAll("select a.*,b.* from oc_paihouse_people as a left join  oc_user b on a.user_id=b.user_id where a.house_id = '{$pageparm['id']}' order by a.id desc limit 4");
                        print_r($plist);
                        if ($plist) {
                            foreach ($plist as $k => $v) {
                                $fd = $v['fd'];
                                if ($server->isEstablished($fd)) {
                                    go(function () use ($fd, $plist, $paimai_house, $jlist, $server) {
                                        $server->push($fd, json_encode([
                                            'fd'           => $fd,
                                            'peopleList'   => $plist,
                                            'paimai_house' => $paimai_house,
                                            'jlist'        => $jlist,
                                        ], 320));
                                    });
                                }
                            }
                        } else {
                            echo "此房间没人为空\n";
                        }

                        break;
                    #竞拍出价（其他操作）
                    default:
                        $plist = $this->db->getAll("select a.*,b.* from oc_paihouse_people as a left join  oc_user b on a.user_id=b.user_id where a.house_id = '{$pageparm['id']}' order by a.id desc limit 4");
                        foreach ($plist as $k => $v) {
                            $fd = $v['fd'];
                            go(function () use ($fd, $plist, $paimai_house, $jlist, $server) {
                                $server->push($fd, json_encode([
                                    'fd'           => $fd,
                                    'peopleList'   => $plist,
                                    'paimai_house' => $paimai_house,
                                    'jlist'        => $jlist,
                                ], 320));
                            });
                        }
                }
            }
        });
    }

    #异常退出处理
    public function close()
    {
        $this->ws->on('close', function (Swoole\WebSocket\Server $server, $fd) {

            echo "client {$fd} closed\n";
            $paihouse_people = $this->db->getRow("select * from oc_paihouse_people where fd='{$fd}'");

            var_dump($paihouse_people);

            $house_jlist = $this->redis->get('House' . $paihouse_people['house_id']);
            if ($house_jlist && isset($house_jlist['list'])) {
                $jlist = $house_jlist['list'];
            } else {
                $jlist = [];
            }
            $this->db->delete('oc_paihouse_people', "fd='{$fd}'");

            echo "clientTwo {$fd} closed\n";

            #客户端推送信息
            $plist = $this->db->getAll("select a.*,b.* from oc_paihouse_people as a left join  oc_user b on a.user_id=b.user_id where a.house_id = '{$paihouse_people['house_id']}' order by a.id desc limit 4");
            var_dump($plist);
            $paimai_house = $this->db->getRow("select * from oc_paimai_house where id='{$paihouse_people['house_id']}'");
            var_dump($paimai_house);

            if ($plist) {
                if ($paimai_house['status'] == 1) {
                    $paimai_house['end_time'] = $paimai_house['create_time'] + ($paimai_house['time_down'] + $paimai_house['time_total']) * 60 - time();
                }
                if ($paimai_house['status'] == 3) {
                    $paimai_house['end_time'] = $paimai_house['create_time'] + ($paimai_house['time_down']) * 60 - time();
                };
                foreach ($plist as $k => $v) {
                    $fdTemp = $v['fd'];
                    if ($server->isEstablished($fdTemp)) {
                        go(function () use ($fdTemp, $plist, $paimai_house, $jlist, $server) {
                            $server->push($fdTemp, json_encode([
                                'fd'           => $fdTemp,
                                'peopleList'   => $plist,
                                'paimai_house' => $paimai_house,
                                'jlist'        => $jlist,
                            ], 320));
                        });
                    }
                }
                echo "一切正常\n";
            } else {
                echo "此房间没人为空\n";
            }

        });
    }

    public function testlist()
    {
        $sql  = "select * from oc_paimai_goods";
        $list = $this->db->getAll($sql);
        return $list;
    }
}

$config = [
    'dsn'      => 'mysql:host=127.0.0.1;dbname=djbix',
    'name'     => 'djbix',
    'password' => 'x2vfCcaxcXr9XUbP',
];
$server = new wsserver($config);








