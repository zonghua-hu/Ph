<?php

namespace Swoole\Coroutine\Component;

use Swoole;
use Swoole\Coroutine\Context;

abstract class Base
{
    /**
     * @var \SplQueue
     */
    protected $pool;
    protected $config;
    protected $type;

    protected $current_entity = 0;
    static $threshold_percent = 1.3;
    static $threshold_num = 10;
    static $threshold_idle_sec = 120;

    function __construct($config)
    {
        if (empty($config['object_id']))
        {
            throw new Swoole\Exception\InvalidParam("require object_id");
        }
        $this->config = $config;
        $this->pool = new MinHeap();
        $this->type .= '_'.$config['object_id'];
    }

    function _createObject()
    {
        while (true)
        {
            if ($this->pool->count() > 0)
            {
                $heap_object = $this->pool->extract();
                $object = $heap_object['obj'];
                $time = $heap_object['priority'];
                //判断空闲时间是否大于配置时间
                if (time() - $time >= self::$threshold_idle_sec)
                {
                    unset($object);
                    continue;
                }
                //必须要 Swoole 2.1.1 以上版本
                if (property_exists($object, "connected") and $object->connected === false)
                {
                    unset($object);
                    continue;
                }
            }
            else
            {
                $object = $this->create();
            }
            break;
        }
        $this->current_entity ++;
        Context::put($this->type, $object);
        return $object;
    }

    function _freeObject()
    {
        $cid = Swoole\Coroutine::getuid();
        if ($cid < 0)
        {
            return;
        }
        $object = Context::get($this->type);
        if ($object)
        {
            if ($this->isReuse()) {
                $this->pool->insert(['priority' => time(), 'obj' => $object]);
            }
            Context::delete($this->type);
        }
        $this->current_entity ++;
    }

    protected function _getObject()
    {
        return Context::get($this->type);
    }

    private function isReuse()
    {
        $pool_size = $this->pool->count();
        if ($pool_size == 1) {
            return true;
        }
        if ($this->current_entity > 0 && $pool_size > self::$threshold_num) {
            if ($pool_size / $this->current_entity > self::$threshold_percent) {
                return false;
            }
        }
        return true;
    }

    abstract function create();
}


class MinHeap extends \SplHeap
{
    /*
     * key => obj
     *
     * */
    public function compare($array1, $array2)
    {
        $p1 = $array1['priority'];
        $p2 = $array2['priority'];
        if ($p1 === $p2) return 0;
        return $p1 > $p2 ? -1 : 1;
    }
}