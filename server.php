<?php
class Websocket {
    public $server;

    public $table;

    public $set =[
    ];

    public function __construct($ip='0.0.0.0', $prot=10006) {
        //实例化websocket服务
        $this->server = new Swoole\WebSocket\Server($ip, $prot);

        //实例化内存表
        $this->table = new swoole_table(1024);

        //设置表字段 （字段名：string ， 字段类型：int、float、string ， 长度：int）
        $this->table->column('id',$this->table::TYPE_INT,4);
        $this->table->column('fd',$this->table::TYPE_INT,4);
        $this->table->column('uid',$this->table::TYPE_STRING,32);
        //创建表
        $this->table->create();

        $this->server->on('open', [$this, 'open']);
        $this->server->on('message', [$this, 'message']);
        $this->server->on('close', [$this, 'close']);
    }

    public function open(swoole_websocket_server $server, swoole_http_request $request)
    {
        $uid = $request->get['uid'];
        $key = 'uid'.$uid;
        if($this->table->exist($key)){
            $uid_data = $this->table->get($key);
            if ($this->server->isEstablished($uid_data['fd'])) {
                $this->server->disconnect($uid_data['fd'], 1000, '账号在其他地方登录');
            }
        }

        $this->table->incr($key, 'id');
        $this->table->set($key, ['fd'=>$request->fd, 'uid'=>$uid]);

        $key = 'fd'.$request->fd;
        $this->table->incr($key, 'id');
        $this->table->set($key, ['fd'=>$request->fd, 'uid'=>$uid]);
    }

    public function message(swoole_websocket_server  $server, swoole_websocket_frame $frame)
    {
        $data = json_decode($frame->data,true);
        $key = 'uid'.$data['uid'];
        if($this->table->exist($key)){
            $uid_data = $this->table->get($key);
            if ($this->server->isEstablished($uid_data['fd'])) {
                $this->server->push($uid_data['fd'], $data['msg']);
            }
        }

    }

    public function start()
    {
        $this->server->start();
        echo "start\r\n";
    }

    public function close(swoole_server $server, int $fd, int $reactorId)
    {
        $key = 'fd'.$fd;
        if($this->table->exist($key)){
            $uid_data = $this->table->get($data['uid']);

            $this->table->del($key);
            $this->table->del('uid'.$uid_data['uid']);
        }
    }
}

$Websocket = new Websocket();
$Websocket->start();