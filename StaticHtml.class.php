<?php
namespace Common;
/* *
 * 功能：页面静态化的创建和删除
 *      创建：当且仅当，一个页面需要被静态化并且还未静态化时。
 *      删除：当且仅当，一个页面存在静态化页面并且需要被重新静态化时。
 *
 * 作者：郭军周
 *
 * 注 ：本类基于ThinkPHP3.2，或者其他具有“单一入口且MVC模式”的其他php框架。
 *
 * 使用方式：在Controller的构造方法中获取其对象；在Controller的销毁方法里，用其对象的_static方法。
 *      例：XXXController extends BaseController.
 *         BaseController:
 *         function __construct(){
 *             $this->__sh = StaticHtml::getInstance();
 *         }
 *         function __destruct(){
 *             $this->__sh->_static();
 *         }
 *
 * */
class StaticHtml{
    private static $_instance = null;       /* 单例模式，自身的引用 */
    private $_needStatic = false;           /* 是否需要将其静态化 */
    private $_needDeleteStatic = false;     /* 是否需要删除其静态化的页面 */
    private $_hasStaticed = true;           /* 是否存在其的静态化页面 */
    private $_group = null;                 /* 当前被访问的group */
    private $_controller = null;            /* 当前被访问的controller */
    private $_action = null;                /* 当前被访问的action */
//    private $_staticAgain = false;        /* 删除静态文件后，是否马上重新更新【【注意】再次请求】 */
    private $_save_path = null;             /* 将要创建或者删除的静态文件的路径 */

    private $_conf = array(                 /* 此文件定义一些静态文件的存放方式 */
        'files_per_directory' => 100,       /* 此值不允许被修改，除非是要删除所有已经存在的静态文件，重新缓存 */
        'static_base_dir' => '/StaticHtml'
    );

//        'Base' => array(      /* Base为controller name */
//            'aaa' => array(       /* aaa为action name */
//                'save_path' => '/StaticHtml/Base/aaa/',   /* save_path为生成的静态文件存放的根路径 */
//                'static_base' => 'id',        /* static_base为生成静态文件的“依据”。建议为对应数据库的primary_key */
//                'alias' => 'aaa'  /* 静态文件的名字，否则为1.html */
//            )
//        )

//        'Base' => array(      /* Base为controller name */
//            'bbb' => array(       /* bbb为action name */
//                'save_path' => '/StaticHtml/Base/aaa/',   /* save_path为要删除的静态文件存放的根路径 */
//                'static_base' => 'id',        /* static_base为确定静态文件路径的“依据”。建议为对应数据库的primary_key */
//                'alias' => 'aaa'  /* 静态文件的名字，否则为1.html */
//            )
//        )

    private function __construct(){
        $this->needStatic(); /* 确定本次请求是否需要静态化 */
        $this->hasStaticed(); /* 确定本次请求是否已经存在静态化页面 */
        $this->needDeleteStatic(); /* 确定本次请求是否需要删除某些静态页面 */
    }

    /* 确定需要删除的静态文件的存放路径 */
    private function needDeleteStatic(){
        if($this->_needDeleteStatic){
            if($this->_group == 'Home' && $this->_controller == 'Index' && $this->_action == 'index'){
                $save_path = $this->_conf['static_base_dir'].'/Home/Index/index';
                if(isset($_SESSION['logined_user'])){
                    $save_path .= '-'.$_SESSION['logined_user']['id']+0;
                }
                $save_path .= '.html';
            }else{
                $save_path = $this->getSavePath($this->_staticList[$this->_group][$this->_controller][$this->_action]);
            }
            $this->_hasStaticed = false;
            if(file_exists(ROOT_PATH.$save_path)){
                $this->_hasStaticed = true;
            }
//            $this->_staticAgain = $this->_deleteList[$this->_controller][$this->_action]['visitAgain'];
            $this->_save_path = ROOT_PATH.$save_path;
        }
    }

    /* 获取本类的，唯一的，实例化 */
    public static function getInstance(){
        if(!(self::$_instance instanceof self)){
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /* 判断是否存在其静态化的文件 */
    private function hasStaticed(){
        if($this->_needStatic){
            if($this->_group == 'Home' && $this->_controller == 'Index' && $this->_action == 'index'){
                $save_path = $this->_conf['static_base_dir'].'/Home/Index/index';
                if(isset($_SESSION['logined_user'])){
                    $save_path .= '-'.$_SESSION['logined_user']['id']+0;
                }
                $save_path .= '.html';
            }else{
                $save_path = $this->getSavePath($this->_staticList[$this->_group][$this->_controller][$this->_action]);
            }
            if(!file_exists(ROOT_PATH.$save_path)){
                $this->_hasStaticed = false;
                ob_start();
            }else{
                header("location: http://".$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']).$save_path);
            }
            $this->_save_path = ROOT_PATH.$save_path;
        }
    }

    /* 获取本次请求要生成或者删除的，静态化文件的路径 */
    private function getSavePath($conf){
        $save_path = $this->_conf['static_base_dir'].'/'.$this->_group.'/'.$this->_controller.'/';
        if(!isset($conf['static_base'])){
            $save_path .= $conf['alias'].'.html';
        }else{
            if(isset($conf['static_base'])){
                if($conf['static_base'] == 'user_id'){
                    $id = (int)$_SESSION['logined_user']['id']+0;
                }else{
                    if(IS_GET){
                        $id = isset($_GET[$conf['static_base']])?$_GET[$conf['static_base']]+0:false;
                    }else{
                        $id = isset($_POST[$conf['static_base']])?$_POST[$conf['static_base']]+0:false;
                    }
                }
            }
            if(isset($conf['static_base_2'])){
                if($conf['static_base_2'] == 'user_id'){
                    $id2 = (int)$_SESSION['logined_user']['id']+0;
                }else{
                    if(IS_GET){
                        $id2 = isset($_GET[$conf['static_base_2']])?$_GET[$conf['static_base_2']]+0:false;
                    }else{
                        $id2 = isset($_POST[$conf['static_base_2']])?$_POST[$conf['static_base_2']]+0:false;
                    }
                }
            }
            if(isset($id) && $id !== false){
                $directory_id = ceil($id/$this->_conf['files_per_directory']);
            }else if(isset($id) && $id !== false && isset($id2) && $id2 !== false){
                $directory_id = ceil($id*$id2/$this->_conf['files_per_directory']);
            }else{
                $directory_id = '';
            }
            $save_path .= $directory_id.'/';
            if($conf['alias']){
                $fileName = $conf['alias'];
            }
            if(isset($id)){
                $fileName .= '-'.$id;
            }
            if(isset($id2)){
                $fileName .= '-'.$id2;
            }
            $fileName .= '.html';
            $save_path .= $fileName;
        }
        return $save_path;
    }

    /* 确定本次请求，是否需要生成静态化文件 */
    private function needStatic(){
        $url = explode('/',__ACTION__);
        $this->_group = $url[3];
        $this->_controller = $url[4];
        $this->_action = $url[5];
        if(isset($this->_staticList[$this->_group]) && 
           isset($this->_staticList[$this->_group][$this->_controller]) && 
           isset($this->_staticList[$this->_group][$this->_controller][$this->_action])){
            $this->_needStatic = true;
        }
        if(isset($this->_deleteList[$this->_group]) &&
           isset($this->_deleteList[$this->_group][$this->_controller]) &&
           isset($this->_deleteList[$this->_group][$this->_controller][$this->_action])){
            $this->_needDeleteStatic = true;
        }
    }

    /* 生成，或者删除，静态化文件 */
    public function _static(){
        if($this->_needStatic && !$this->_hasStaticed){
            $html = ob_get_contents();
            $this->_mkdir(dirname($this->_save_path));
            file_put_contents($this->_save_path,$html);
        }
        if($this->_needDeleteStatic && $this->_hasStaticed){
            unlink($this->_save_path);
            /*if($this->_staticAgain){
                header("location: http://www.baidu.com");
//                header("location: http://".$_SERVER['HTTP_HOST'].'/'.$_SERVER['REQUEST_URI']);
            }*/
        }
    }

    /* 创建目录 */
    private function _mkdir($path){
        if (!file_exists($path)){
            $this->_mkdir(dirname($path));
            mkdir($path, 0777);
        }
    }
}
?>