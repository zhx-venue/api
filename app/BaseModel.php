<?php
declare (strict_types = 1);

namespace app;

use think\Model;

/**
 * 模型基础类
 */
class BaseModel extends Model
{
    // 状态
    const STATUS_BAN = 0;
    const STATUS_NORMAL = 1;
    const STATUS_DELETE = -1;

    // 一页读取记录数
    const SIZE_PER_PAGE = 10;

    protected $autoWriteTimestamp = true;

    /**
     * 创建时间字段 false表示关闭
     * @var false|string
     */
    protected $createTime = 'created_at';

    /**
     * 更新时间字段 false表示关闭
     * @var false|string
     */
    protected $updateTime = 'updated_at';

    protected $createBy = 'created_by';
    protected $updateBy = 'updated_by';

    /**
     * 根据条件读取记录
     * @param object $query think\db\Query对象
     * @param $expand string or array 需要读取的扩展属性
     */
    public function getItem($query, $expand=null)
    {
        $item = $query->find();
        if ($item) {
            $expand = $expand ?? input('get.expand', '');
            $expand = is_array($expand) ? $expand : array_keys(array_flip(array_filter(explode(',', strval($expand)))));
            foreach ($expand as $_exField) {
                $method = 'get'.ucfirst($_exField);
                method_exists($item, $method) && $item->$_exField = $item->$method();
            }
        }

        return $item;
    }

    /**
     * 根据条件列举记录
     * @param object $query think\db\Query对象
     * @param $expand string or array 需要读取的扩展属性
     */
    public function listItem($query, $expand=null)
    {
        $pageCur = $query->getOptions('page');
        $pageSize = $query->getOptions('limit');
        if ($pageSize) {
            $clone = clone($query);
            $clone->removeOption('page')->removeOption('limit');
            $totalCount = intval($clone->count());
            $pageCount = intval($totalCount % $pageSize == 0 ? $totalCount / $pageSize : ceil($totalCount / $pageSize));
        } else {
            $totalCount = intval($query->count());
        }

        if (1 || $totalCount > 0) {
            $lists = $query->select();
            if (!($lists->isEmpty())) {
                $expand = $expand ?? input('get.expand', '');
                $expand = is_array($expand) ? $expand : array_keys(array_flip(array_filter(explode(',', strval($expand)))));
                foreach ($lists as $item) {
                    foreach ($expand as $_exField) {
                        $method = 'get'.ucfirst($_exField);
                        method_exists($item, $method) && $item->$_exField = $item->$method();
                    }
                }
            }
        }
        
        return [
            'page' => $pageCur ? intval($pageCur) : 1, 
            'psize' => $pageSize ? intval($pageSize) : 0, 
            'ptotal' => $pageCount ?? 1,
            'counts' => $totalCount ?? 0, 
            'data' => $lists ?? []
        ];
    }

    /**
     * 解析查询字段
     * @return array(field1 => [operate, queryString], field2 => [operate, queryString], ...)
     */
    public function parseFilter($filter=null)
    {
        $query = null;
        $filter = $filter ?? input('get.');
        foreach ($filter as $key => $value) {
            switch ($key) {
                case 'order': {
                    $orders = [];
                    $fields = array_flip(array_filter(explode(',', $value)));
                    foreach ($fields as $_filed => $_val) {
                        if (strpos($_filed, '-') === 0) {
                            $_filed = substr($_filed, 1);
                            empty($_filed) || $orders[$_filed] = 'desc';
                        } else {
                            $orders[$_filed] = 'asc';
                        }
                    }
                    empty($orders) || $query = $query ? $query->order($orders) : static::order($orders);
                    break;
                }
                case 'page': {
                    $page = intval($value);
                    $page > 0 || $page = 1;
                    $query = $query ? $query->page($page) : static::page($page);
                    break;
                }
                case 'psize': {
                    $psize = intval($value);
                    $psize > 0 || $psize = self::SIZE_PER_PAGE;
                    $query = $query ? $query->limit($psize) : static::limit($psize);
                    break;
                }
                default: {
                    if (stripos($key, 'filter_') === 0) {
                        $key = substr($key, 7);
                        $where = $this->formatFilter($key, $value);
                        $where && $query = $query ? $query->where($key, $where[0], $where[1]) : static::where($key, $where[0], $where[1]);
                    }
                }
            }
        }

        return $query;
    }

    /**
     * 格式化字段的查询条件
     */
    protected function formatFilter($key, $value) { }

    /**
     * 保存当前数据对象
     * @access public
     * @param array  $data     数据
     * @param string $sequence 自增序列名
     * @return bool
     */
    public function save(array $data = [], string $sequence = null): bool
    {
        if (!(app()->user->isGuest())) {
            if (!($this->exists) && $this->createBy && !isset($data[$this->createBy])) {
                $data[$this->createBy] = app()->user->id;
            }
            if ($this->updateBy && !isset($data[$this->updateBy])) {
                $data[$this->updateBy] = app()->user->id;
            }
        }

        return parent::save($data, $sequence);
    }
}
