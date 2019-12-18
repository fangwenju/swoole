<?php
class Websocket {
    public $server;
    public function __construct() {
        //实例化websocket服务
        $this->server = new Swoole\WebSocket\Server("0.0.0.0", 10006);

        //实例化内存表
        $table = new swoole_table(1024);

        //设置表字段 （字段名：string ， 字段类型：int、float、string ， 长度：int）
        $table->column('id',$table::TYPE_INT,4);
        $table->column('fd',$table::TYPE_INT,4);
        $table->column('uid',$table::TYPE_STRING,32);

        //创建表
        $table->create();

        $this->server->on('open', function (swoole_websocket_server $server, $request) use ($table) {
            $uid = $request->get['uid'];

            $table->incr($uid, 'id');
            $table->set($uid, ['fd'=>$request->fd, 'uid'=>$uid]);
            echo "open[{$request->fd}]\n";
        });
        $this->server->on('message', function (Swoole\WebSocket\Server $server, $frame) use ($table) {

            $data = json_decode($frame->data,true);
            if($table->exist($data['uid'])){
                $uid_data = $table->get($data['uid']);
                if ($this->server->isEstablished($uid_data['fd'])) {
                    var_dump($data['msg']);
                    $this->server->push($uid_data['fd'], $data['msg']);
                }
            }
        });
        $this->server->on('close', function ($server, $fd) {
            echo "close[{$fd}]\n";
        });
        $this->server->start();
    }
}
new Websocket();