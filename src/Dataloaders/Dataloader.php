<?php

declare(strict_types=1);

namespace QT\GraphQL\Dataloaders;

use GraphQL\Executor\Promise\Adapter\SyncPromise;

/**
 * 因为fpm模式不支持完全异步模式
 * 无法实现promise或者yield来异步调度
 * 只能使用SyncPromise来收集查询信息,然后加入待执行队列
 * 在graphql语法解析完成后执行队列,将任务全部完成
 *
 * 完整的dataloader实现参考
 *
 * @see https://github.com/graphql/dataloader
 *
 * Dataloader
 *
 * @package QT\GraphQL\Dataloaders
 */
class Dataloader
{
    /**
     * 数据加载回调函数
     *
     * @var callable
     */
    protected $loadFunc;

    /**
     * 待加载的keys
     *
     * @var array<mixed>
     */
    protected $keys = [];

    /**
     * 数据缓存
     *
     * @var array
     */
    protected $cacheMaps = [];

    /**
     * 根据key冗余的promises
     *
     * @var array
     */
    protected $promises = [];

    /**
     * Dataloader constructor
     *
     * @param callable $loadFunc
     */
    public function __construct(callable $loadFunc)
    {
        $this->loadFunc = $loadFunc;
    }

    /**
     * 获取结果
     *
     * @param mixed $key
     * @return SyncPromise
     */
    public function get(mixed $key): SyncPromise
    {
        if (isset($this->promises[$key])) {
            return $this->promises[$key];
        }

        $this->keys[] = $key;

        return $this->promises[$key] = new SyncPromise(function () use ($key) {
            // 在已有缓存数据的情况下,不去触发load逻辑
            if (isset($this->cacheMaps[$key])) {
                return $this->cacheMaps[$key];
            }

            // 获取已查询结果时,也支持注入新的条件
            // 在没用到新数据之前,不去主动请求新数据
            if (!empty($this->keys)) {
                $this->load();
            }

            return $this->cacheMaps[$key];
        });
    }

    /**
     * 加载数据
     *
     * @return void
     */
    protected function load()
    {
        $results = call_user_func($this->loadFunc, $this->keys);

        do {
            $key = array_shift($this->keys);

            $this->cacheMaps[$key] = $results[$key] ?? null;
        } while (!empty($this->keys));
    }

    /**
     * 清理数据
     *
     * @return void
     */
    public function clear()
    {
        $this->keys      = [];
        $this->cacheMaps = [];
    }
}
