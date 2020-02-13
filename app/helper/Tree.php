<?php

namespace app\helper;

/**
 * @name Tree
 * @author crazymus < QQ:291445576 >
 * @des PHP生成树形结构,无限多级分类
 * @version 1.2.0
 * @Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
 * @updated 2015-08-26

 */
class Tree{

    protected static $config = array(
        /* 主键 */
        'primary_key' 	=> 'id',
        /* 父键 */
        'parent_key'  	=> 'parentid',
        /* 展开属性 */
        'expanded_key'  => 'expanded',
        /* 叶子节点属性 */
        'leaf_key'      => 'leaf',
        /* 孩子节点属性 */
        'children_key'  => 'children',
        /* 是否展开子节点 */
        'expanded'    	=> true
    );

    /* 结果集 */
    protected static $result = array();

    /* 层次暂存 */
    protected static $level = array();
    /**
     * @name 生成树形结构
     * @param array 二维数组
     * @return mixed 多维数组
     */
    public static function makeTree($data,$options=array() ){
        $dataset = self::buildData($data,$options);
        $data = $dataset['data'];
        sort($data);
        
        $r = self::makeTreeCore($data[0],$dataset['r'],'normal');
        return $r;
    }

    /* 生成线性结构, 便于HTML输出, 参数同上 */
    public static function makeTreeForHtml($data,$options=array()){

        $dataset = self::buildData($data,$options);
        $r = self::makeTreeCore(0,$dataset,'linear');
        return $r;
    }

    /* 格式化数据, 私有方法 */
    private static function buildData($data,$options){
        $config = array_merge(self::$config,$options);
        self::$config = $config;
        extract($config);

        $r = $_data = array();
        foreach($data as $item){
            $item = (array)$item;
            $id = $item[$primary_key];
            $parent_id = $item[$parent_key];
            $r[$parent_id][$id] = $item;
            $_data[] = $parent_id;
        }
        $list['data'] = $_data;
        $list['r'] = $r;
        return $list;
    }

    /* 生成树核心, 私有方法  */
    private static function makeTreeCore($index,$data,$type='linear')
    {
        extract(self::$config);
        foreach($data[$index] as $id=>$item)
        {
            if($type=='normal'){
                if(isset($data[$id]))
                {
                    $item[$expanded_key]= self::$config['expanded'];
                    $item[$children_key]= self::makeTreeCore($id,$data,$type);
                }
                else
                {
                    $item[$leaf_key]= true;
                    $item['types'] = 1;   
                    !$item['is_member'] && $item['children'] = [['name' => '加载中...']];
                }
                $r[] = $item;
            }else if($type=='linear'){
                $parent_id = $item[$parent_key];
                self::$level[$id] = $index==0?0:self::$level[$parent_id]+1;
                $item['level'] = self::$level[$id];
                self::$result[] = $item;
                if(isset($data[$id])){
                    self::makeTreeCore($id,$data,$type);
                }

                $r = self::$result;
            }
        }
        return $r;
    }
}
