<?php

namespace App\Library;

trait BaseDB{

    //操作的表名
    private $table;

    //添加数据
    public function add($param)
    {
        if (empty($param)) return false;
        return app('db')->table($this->table)->insert($param);
    }

    /**
     * 根据条件删除数据
     *
     * @param array $where
     * @return bool
     * @Author jy
     * @DateTime 2021-08-16 09:18:00
     */
    public function del(array $where)
    {
        if (empty($where)) return false;

        return app('db')->table($this->table)->where($where)->delete();
    }

    /**
     * 根据条件更新数据
     *
     * @param array $where
     * @param [type] $data
     * @return bool
     * @Author jy
     * @DateTime 2021-08-16 09:17:46
     */
    public function update(array $where, $data)
    {
        if (empty($where) || empty($data)) return false;

        return  app('db')->where($where)->update($data);
    }

    /**
     * 根据条件查询第一条数据 并返回需要的字段
     *
     * @param array $where
     * @param array $select
     * @return mixed
     * @Author jy
     * @DateTime 2021-08-16 09:17:29
     */
    public function getFirst(array $where, array $select = [])
    {
        if (empty($where)) return false;

        //获取所有字段
        if (empty($select)) return app('db')->table($this->table)->where($where)->first();

        //获取指定字段
        return app('db')->table($this->table)->where($where)->select($select)->first();
    }

    /**
     * 分页查询
     *
     * @param int $nowPage 当前页
     * @param int $limit 每页最大显示条数 默认为10页
     * @param array $where 
     * @param array $field 排序字段名
     * @param string $order 排序方向 默认降序  
     * @return mixed
     * @Author jy
     * @DateTime 2021-08-16 09:17:23
     */
    public function getPage($nowPage, $limit = 10, array $where=[], $field = '', $order='desc')
    {
        if (empty($nowPage) || empty($limit)) return false;

        $db = app('db')->table($this->table)->forPage($nowPage, $limit);
        if (!empty($where)) $db = $db->where($where);
        if (!empty($field)) $db = $db->orderBy($field, $order);
        return $db->get();
    }

    /**
     * 获取符合条件的总数
     *
     * @param array $where
     * @return int
     * @Author jy
     * @DateTime 2021-08-16 09:27:28
     */
    public function getCount(array $where = [])
    {
        if (empty($where)) return app('db')->table($this->table)->count();
        return app('db')->table($this->table)->where($where)->count();
    }
}