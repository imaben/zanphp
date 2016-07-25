<?php
/**
 * Created by IntelliJ IDEA.
 * User: winglechen
 * Date: 16/4/4
 * Time: 01:29
 */

namespace Zan\Framework\Network\Connection\Driver;


use Zan\Framework\Contract\Network\Connection;
use Zan\Framework\Foundation\Coroutine\Task;
use Zan\Framework\Network\Server\Timer\Timer;
use Zan\Framework\Store\Database\Mysql\Exception\MysqliConnectionLostException;
use Zan\Framework\Store\Database\Mysql\Mysqli as Engine;
use Zan\Framework\Utilities\Types\Time;

class Mysqli extends Base implements Connection
{
    private $classHash = null;

    public function closeSocket()
    {
        try {
            $this->getSocket()->close();
        } catch (\Exception $e) {
            //todo log
        }
    }
    
    public function heartbeat()
    {
        //绑定心跳检测事件
        $this->classHash = spl_object_hash($this);
        $this->heartbeatLater();
    }

    public function heartbeatLater()
    {
        Timer::after($this->config['pool']['heartbeat-time'], [$this,'heartbeating']);
    }
    
    public function heartbeating()
    {
        $time = Time::current(true) - $this->lastUsedTime;
        $hearBeatTime = $this->config['pool']['heartbeat-time']/1000;//s
        if ($this->lastUsedTime != 0 && $time <  $hearBeatTime) {
            Timer::after(($hearBeatTime-$time)*1000, [$this,'heartbeating']);
            return;
        }

        $coroutine = $this->ping();
        Task::execute($coroutine);
    }

    public function ping()
    {
        $connection = (yield $this->pool->get($this));
        if (null == $connection) {
            $this->heartbeatLater();
            return;
        }
        $this->setUnReleased();
        $engine = new Engine($this);
        try{
            $result = (yield $engine->query('select 1'));
        } catch (\Exception $e){
            return; 
        }

        $this->release();
        $this->heartbeatLater();
    }
}