<?php
/**
 * =========================================================
 * Copy right 2015-2025 老孟编程, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: http://heimishop.com
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用。
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * =========================================================
 * @author : 孟老师
 * @date : 2018.1.17
 * @version : v1.0.0.0
 * @email: 835173372@qq.com
 */

namespace app\admin\controller;

use Carbon\Traits\Date;
use think\Controller;
use think\Db;
use think\Config;
use think\facade\Request;
use think\File;
use think\Log;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;

use OSS\OssClient;
use OSS\Core\OssException;

class Uploader extends Controller
{
    #本地图片上传
    public function local_upload()
    {
        $filePath = $_FILES['file']['tmp_name'];
        $file     = new File($filePath);
        $info     = $file->webuploader_move(ROOT_PATH . 'public' . DS . 'uploads');
        if ($info) {
            $data['name']        = $info->getFilename();
            $data['md5']         = $info->hash('sha1');
            $data['sha1']        = $info->hash('md5');
            $data['ext']         = 'jpg';
            $data['path']        = '/uploads/' . $info->getSaveName() . 'jpg';
            $data['location']    = 'location';
            $data['create_time'] = time();
            $data['status']      = 1;
            $id                  = Db::name("admin_upload")->insertGetId($data);#插入图片数据
            if ($id > 0) {
                $return['path'] = $data['path'];
                $return['name'] = $data['name'];
                $return['id']   = $id;
            } else {
                $return['error']   = 1;
                $return['success'] = 0;
                $return['status']  = 0;
                $return['message'] = '上传出错' . $file->getError();
            }
        } else {
            # 上传失败获取错误信息
            $return['error']   = 1;
            $return['success'] = 0;
            $return['status']  = 0;
            $return['message'] = '上传出错' . $file->getError();
        }
        exit(json_encode($return));
    }

    #七牛云图片上传，ajax返回，七牛云存储图片(不保留本地备份数据，@author  <835173372@qq.com>
    public function qiniu_upload()
    {
        $ossconfig = Db::name("ossconfig")->where("is_use=1")->find();
        $config    = [
            'accessKey' => $ossconfig['keyid'],
            'secretKey' => $ossconfig['keysecret'],
            'domain'    => $ossconfig['domain'],
            'bucket'    => $ossconfig['bucket'],
        ];
        $accessKey = $config['accessKey'];
        $secretKey = $config['secretKey'];
        $auth      = new Auth($accessKey, $secretKey);
        $bucket    = $config['bucket'];# 要上传的空间
        $token     = $auth->uploadToken($bucket);# 生成上传 Token
        $file      = '';
        #要上传文件的本地路径
        if (isset($_FILES['upfile'])) {
            $extension = pathinfo($_FILES['upfile']['name'])['extension'];
            $file      = request()->file('upfile');
        }
        if (isset($_FILES['file'])) {
            $extension = pathinfo($_FILES['file']['name'])['extension'];
            $file      = request()->file('file');
        }
        $fileInfo  = $file->getInfo();
        $key       = md5(time() . mt_rand(1000000, 9999999)) . '.' . $extension;
        $uploadMgr = new UploadManager();
        list($ret, $err) = $uploadMgr->putFile($token, $key, $fileInfo['tmp_name']);
        if ($err === null) {
            $url               = $config['domain'] . $ret['key'];
            $return['error']   = 0;
            $return['name']    = $key;
            $return['state']   = 'SUCCESS';
            $return['url']     = $url;
            $return['message'] = '上传成功';
        } else {
            #上传失败获取错误信息
            $return['error']   = 1;
            $return['name']    = '';
            $return['state']   = 0;
            $return['url']     = '';
            $return['message'] = '上传出错' . $file->getError();
        }
        exit(json_encode($return, 320));
    }

    #阿里云OSS图片上传，ajax返回，七牛云存储图片(不保留本地备份数据，@author  <835173372@qq.com>
    public function alioss_upload()
    {

        #要上传文件的本地路径
        if (isset($_FILES['upfile'])) {
            $extension = pathinfo($_FILES['upfile']['name'])['extension'];
            $file      = request()->file('upfile');
        }
        if (isset($_FILES['file'])) {
            $extension = pathinfo($_FILES['file']['name'])['extension'];
            $file      = request()->file('file');
        }
        $fileInfo = $file->getInfo();
        $key      = 'upload/image/' . date('Ymd', time()) . '/' . md5(time() . mt_rand(1000000, 9999999)) . '.' . $extension;
        //阿里云主账号AccessKey拥有所有API的访问权限，风险很高。强烈建议您创建并使用RAM账号进行API访问或日常运维，请登录 https://ram.console.aliyun.com 创建RAM账号。
        $accessKeyId     = "LTAIWVVWg5MloIYB";
        $accessKeySecret = "x76UY66HuudlwWfTp16gCqwHoS6DJN";
        //Endpoint以杭州为例，其它Region请按实际情况填写。
        $endpoint = "image.hngpmall.com";
        //存储空间名称
        $bucket = "hngpmall";
        //文件名称
        $object = $key;
        //<yourLocalFile>由本地文件路径加文件名包括后缀组成，例如/users/local/myfile.txt
        $filePath = $fileInfo['tmp_name'];
        try {
            /**
             * 构造函数
             *
             * 构造函数有几种情况：
             * 1. 一般的时候初始化使用 $ossClient = new OssClient($id, $key, $endpoint)
             * 2. 如果使用CNAME的，比如使用的是www.testoss.com，在控制台上做了CNAME的绑定，
             * 初始化使用 $ossClient = new OssClient($id, $key, $endpoint, true)
             * 3. 如果使用了阿里云SecurityTokenService(STS)，获得了AccessKeyID, AccessKeySecret, Token
             * 初始化使用  $ossClient = new OssClient($id, $key, $endpoint, false, $token)
             * 4. 如果用户使用的endpoint是ip
             * 初始化使用 $ossClient = new OssClient($id, $key, “1.2.3.4:8900”)
             *
             * @param string $accessKeyId 从OSS获得的AccessKeyId
             * @param string $accessKeySecret 从OSS获得的AccessKeySecret
             * @param string $endpoint 您选定的OSS数据中心访问域名，例如oss-cn-hangzhou.aliyuncs.com
             * @param boolean $isCName 是否对Bucket做了域名绑定，并且Endpoint参数填写的是自己的域名
             * @param string $securityToken
             * @param string $requestProxy 添加代理支持
             * @throws OssException
             */
            $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint, true);
            $res       = $ossClient->uploadFile($bucket, $object, $filePath);
            $return['error']   = 0;
            $return['name']    = $key;
            $return['state']   = 'SUCCESS';
            $return['url']     = $res['info']['url'];
            $return['message'] = '上传成功';
        } catch (OssException $e) {
            $return['error']   = 1;
            $return['name']    = '';
            $return['state']   = 0;
            $return['url']     = '';
            $return['message'] = $e->getMessage();
        }
        exit(json_encode($return, 320));
    }


    public function appuploadFile()
    {
        require_once EXTEND_PATH . 'Qiniu/autoload.php';
        $config    = Db::name("sys_qiniu")->where("id=1")->find();
        $accessKey = $config['accessKey'];
        $secretKey = $config['secretKey'];
        $auth      = new Auth($accessKey, $secretKey);
        $bucket    = $config['bucket'];# 要上传的空间
        $token     = $auth->uploadToken($bucket);# 生成上传 Token
        #要上传文件的本地路径
        if (isset($_FILES['upfile'])) {
            $filePath = $_FILES['upfile']['tmp_name'];#ueditor上传图片
        }
        if (isset($_FILES['file'])) {
            $filePath = $_FILES['file']['tmp_name'];#ueditor上传图片
        }
        $extension = pathinfo($_FILES['file']['name'])['extension'];
        $file      = new File($filePath);
        $info      = $file->webuploader_move(ROOT_PATH . 'public' . DS . 'uploads');#本地上传

        #上传到七牛后保存的文件名
        if ($info) {
            $key = $info->getFilename();
        } else {
            $key = md5(time()) . '.' . $extension;
        }

        #初始化 UploadManager 对象并进行文件的上传
        $uploadMgr = new UploadManager();
        #调用 UploadManager 的 putFile 方法进行文件的上传
        list($ret, $err) = $uploadMgr->putFile($token, $key, $info->getPathname());
        if ($err === null) {
            $data['url'] = $config['domain'] . $ret['key'];
        }
        if ($info) {
            $data['name']        = $info->getFilename();
            $data['md5']         = $info->hash('sha1');
            $data['sha1']        = $info->hash('md5');
            $data['ext']         = 'jpg';
            $data['path']        = '/uploads/' . $info->getSaveName() . $extension;
            $data['location']    = 'Qiniu';
            $data['create_time'] = time();
            $data['status']      = 1;
            $id                  = Db::name("admin_upload")->insertGetId($data);#插入图片数据
            if ($id > 0) {
                $return['path']  = $data['path'];
                $return['name']  = $data['name'];
                $return['id']    = $id;
                $return['state'] = 'SUCCESS';
                $return['url']   = $data['url'];
            } else {
                $return['error']   = 1;
                $return['success'] = 0;
                $return['status']  = 0;
                $return['message'] = '上传出错' . $file->getError();
            }
        } else {
            #上传失败获取错误信息
            $return['error']   = 1;
            $return['success'] = 0;
            $return['status']  = 0;
            $return['message'] = '上传出错' . $file->getError();
        }
        ajaxmsg('true', 1, $return);
    }
}