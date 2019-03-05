<?php
use Yaf\Application;
use Yaf\Dispatcher;
class SetController extends Yaf\Controller_Abstract  {
    public $db;
    public $user;
    public function init(){
        $this->db = new dbModel();
        if(!empty($_SESSION['username'])){
            $this->user = $_SESSION['username'];
        }else{
            success("请先登陆!","/login/login");
            exit;
        }
    }

    public function indexAction(){
        include APP_PATH."/application/core/Page.php";
        $page = new \app\core\Page();
        $len = $this->db->zscount("filter");
        $page->init($len,13);
        $showstr = $page->show();
        $page = $this->db->action("SELECT * FROM zs_filter ORDER BY id DESC {$page->limit} ");
        $this->getView()->assign(["page"=>$page,"showstr"=>$showstr]);
    }

    public function addindexAction(){
        if($this->getRequest()->isPost()){
            Dispatcher::getInstance()->autoRender(false);
            $word = post("word");
            $filter['word'] = $word;
            $bool = $this->db->action($this->db->insertSql("filter",$filter));
            statusUrl($bool,"添加成功","/set/index","添加失败");
        }else{
            $this->getView()->assign(["xxx"=>"yyyy"]);
        }
    }

    public function delindexAction(){
        Dispatcher::getInstance()->autoRender(false);
        $id = get("id");
        $bool = $this->db->action($this->db->deleteSql("filter","id = {$id}"));
        if($bool){
            echo json_encode(['msg'=>"ok"]);
        }else{
            echo json_encode(['msg'=>"no"]);
        }
    }

    public function searchAction(){
        $search = get("search");
        $currentPage = empty($_GET["page"])?"1":$_GET["page"];
        $showPage = 10;
        $start =  ($currentPage-1)*$showPage;
        $result = $this->db->action("SELECT id,word FROM zs_filter WHERE word LIKE '%{$search}%' ORDER BY id DESC LIMIT {$start},{$showPage} ");
        $this->getView()->assign(["result"=>$result]);
    }

    //系统设置
    public function appuploadAction(){
        $arrData = $this->db->field("id,version_number,version_name,update_content,update_address,package_size,is_forced_update")->table("zs_setting")->order("id desc")->select();
        $this->getView()->assign(["arrData"=>$arrData]);
    }
    //添加更新APK包
    public function addappuploadAction(){
        if($this->getRequest()->isPost()){
            Dispatcher::getInstance()->autoRender(false);
            $data['version_number'] = post("version_number");
            $data['version_name'] = post("version_name");
            $data['update_content'] = post("update_content");
            if($_FILES['update_address']['name']){
                $file = files("update_address");
                if($file['type'] == "application/vnd.android.package-archive"){
                    $dir = APP_PATH."/package";
                    if(!file_exists($dir)){
                        mkdir($dir,0777,true);
                    }
                    $pathicon = $dir."/".$file['name'];
                    move_uploaded_file( $file['tmp_name'],$pathicon);
                    $data['update_address'] = "http://".server("SERVER_NAME")."/package/".$file['name'];
                    $data['package_size'] = $file['size'];
                    $bool = $this->db->action($this->db->insertSql("setting",$data));
                    statusUrl($bool,"添加成功","/set/appupload","添加失败");
                }else{
                    success("请上传APK后缀文件","/set/addappupload");
                }
            }else{
                success("缺少展示资源","/admin/banneradd");
            }
        }else{
            $this->getView()->assign(["xxx"=>"yyyy"]);
        }
    }

    public function appedituploadAction(){
        if($this->getRequest()->isPost()){
            Dispatcher::getInstance()->autoRender(false);
            $id = post("id");
            $data['version_number'] = post("version_number");
            $data['version_name'] = post("version_name");
            $data['update_content'] = post("update_content");
            $data['is_forced_update'] = post("is_forced_update");
            if($_FILES['update_address']['name']){
                $file = files("update_address");
                if($file['type'] == "application/vnd.android.package-archive"){
                    $dir = APP_PATH."/package";
                    if(!file_exists($dir)){
                        mkdir($dir,0777,true);
                    }
                    $pathicon = $dir."/".$file['name'];
                    move_uploaded_file( $file['tmp_name'],$pathicon);
                    $data['update_address'] = "/package/".$file['name'];
                    $data['package_size'] = $file['size'];
                    $this->db->action($this->db->updateSql("setting",$data,"id = {$id}"));
                    success("修改apk成功","/set/appupload");
                }else{
                    success("请上传APK后缀文件","/set/addappupload");
                }
            }else{
                $bool = $this->db->action($this->db->updateSql("setting",$data,"id = {$id}"));
                statusUrl($bool,"修改成功","/set/appupload","修改失败");
            }
        }else{
            $id = get("id");
            $arrData = $this->db->field("*")->table("zs_setting")->where("id = {$id}")->find();
            $this->getView()->assign(["arrData"=>$arrData]);
        }
    }

    public function emptyAction(){
        // TODO: Implement __call() method.
    }



}
