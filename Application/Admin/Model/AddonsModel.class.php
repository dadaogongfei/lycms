<?php
namespace Admin\Model;
use Think\Model;

/**
 * 插件模型
 */

class AddonsModel extends Model {

    /**
     * 查找后置操作
     */
    protected function _after_find(&$result,$options) {

    }

    protected function _after_select(&$result,$options){

        foreach($result as &$record){
            $this->_after_find($record,$options);
        }
    }
    /**
     * 文件模型自动完成
     * @var array
     */
    protected $_auto = array(
        array('create_time', NOW_TIME, self::MODEL_INSERT),
    );

    /**
     * 获取插件列表
     * @param string $addon_dir
     */
    public function getList($addon_dir = ''){
        if(!$addon_dir)
            $addon_dir = ADDON_PATH;
        $dirs = array_map('basename',glob($addon_dir.'*', GLOB_ONLYDIR));
        if($dirs === FALSE || !file_exists($addon_dir)){
            $this->error = '插件目录不可读或者不存在';
            return FALSE;
        }
		$addons			=	array();
		$where['name']	=	array('in',$dirs);
		$list			=	$this->where($where)->field(true)->select();
		foreach($list as $addon){
			$addon['uninstall']		=	0;
			$addons[$addon['name']]	=	$addon;
		}
        foreach ($dirs as $value) {
            if(!isset($addons[$value])){
				$class = get_addon_class($value);
				if(!class_exists($class)){ // 实例化插件失败忽略执行
					\Think\Log::record('插件'.$value.'的入口文件不存在！');
					continue;
				}
                $obj    =   new $class;
				$addons[$value]	= $obj->info;
				if($addons[$value]){
					$addons[$value]['uninstall'] = 1;
                    unset($addons[$value]['status']);
				}
			}
        }
        int_to_string($addons, array('status'=>array(-1=>'损坏', 0=>'禁用', 1=>'启用', null=>'未安装')));
        $addons = list_sort_by($addons,'uninstall','desc');
        return $addons;
    }

    public function getMarketList($list){
        foreach ($list as $key => $value) {
            if($version=$this->where(array('title'=>$value['title']))->getField('version')){
                if(D('Update')->tonum($version) < D('Update')->tonum($value['version'])){
                    $list[$key]['state']=1;
                }else{
                    $list[$key]['state']=2;
                }
            }else{
                $list[$key]['state']=0;
            }
        }
        return $list;
    }
}