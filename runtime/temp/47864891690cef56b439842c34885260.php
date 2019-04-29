<?php if (!defined('THINK_PATH')) exit(); /*a:3:{s:84:"D:\phpstudy\PHPTutorial\WWW\kedi\public/../application/admin\view\.\menu_create.html";i:1538213684;s:74:"D:\phpstudy\PHPTutorial\WWW\kedi\application\admin\view\public_header.html";i:1538213684;s:74:"D:\phpstudy\PHPTutorial\WWW\kedi\application\admin\view\public_footer.html";i:1538213684;}*/ ?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="renderer" content="webkit">

    <title>科迪管理系统</title>

    <meta name="keywords" content="科迪管理系统">
    <meta name="description" content="科迪管理系统">

    <!--[if lt IE 9]>
    <meta http-equiv="refresh" content="0;ie.html" />
    <![endif]-->
    <link rel="shortcut icon" href="favicon.ico"> 
    <link href="/static/admin/org/css/bootstrap.min.css?v=3.3.6" rel="stylesheet">
    <link href="/static/admin/org/css/font-awesome.min.css?v=4.4.0" rel="stylesheet">
    <link href="/static/admin/org/css/animate.css" rel="stylesheet">
    <link href="/static/admin/org/css/style.css?v=4.1.0" rel="stylesheet">
    <link href="/static/admin/org/css/plugins/iCheck/custom.css" rel="stylesheet">
    <link href="/static/css/plugins/sweetalert/sweetalert.css" rel="stylesheet">

<script src="/static/js/plugins/sweetalert/sweetalert.min.js"></script>
<!-- 全局js -->
<script src="/static/admin/org/js/jquery.min.js?v=2.1.4"></script>
<script src="/static/admin/org/js/bootstrap.min.js?v=3.3.6"></script>
<script src="/static/admin/org/js/plugins/metisMenu/jquery.metisMenu.js"></script>

<script src="/static/admin/org/js/plugins/slimscroll/jquery.slimscroll.min.js"></script>
<script src="/static/admin/org/js/plugins/layer/layer.min.js"></script>
<!-- 自定义js -->
<script src="/static/admin/org/js/hplus.js?v=4.1.0"></script>
<script type="text/javascript" src="/static/admin/org/js/contabs.js"></script>

<!-- 自定义js -->
<script src="/static/admin/org/js/content.js?v=1.0.0"></script>


<!-- Bootstrap table -->
<script src="/static/admin/org/js/plugins/bootstrap-table/bootstrap-table.min.js"></script>
<script src="/static/admin/org/js/plugins/bootstrap-table/bootstrap-table-mobile.min.js"></script>
<script src="/static/admin/org/js/plugins/bootstrap-table/locale/bootstrap-table-zh-CN.min.js"></script>

<!-- Data Tables -->
<script src="/static/admin/org/js/plugins/dataTables/jquery.dataTables.js"></script>
<script src="/static/admin/org/js/plugins/dataTables/dataTables.bootstrap.js"></script>
<script>
    function malert(msg){
        
       swal ( "提示" ,  msg ,  "success" );
    }
    function malerte(msg){
        
       swal ( "提示" ,  msg ,  "error" );
    }
</script>
</head>
<body class="gray-bg">
    <div class="wrapper wrapper-content animated fadeInRight">
        <div class="row">
            <div class="col-sm-8">
                <div class="ibox float-e-margins">
                    <div class="ibox-title">
                        <h5>菜单添加/修改</h5>
                    </div>
                    <div class="ibox-content">
                        <form class="form-horizontal m-t" id="menuForm">
                            <input type="hidden" name='act' value='<?php echo $act; ?>'>
                            <input type="hidden" name='usertoken' value='<?php echo $usertoken; ?>'>
                            <div class="form-group">
                                <label class="col-sm-3 control-label">上级菜单：</label>
                              <input id="cname" name="Menu_id"  type="hidden" class="form-control" value="<?php echo (isset($item['Menu_id']) && ($item['Menu_id'] !== '')?$item['Menu_id']:''); ?>">
                                <div class="col-sm-8">
                                    <select class="form-control m-b" name="Menu_pid">
                                        <option value='0'>顶级菜单</option>
                                        <?php if(is_array($parents) || $parents instanceof \think\Collection || $parents instanceof \think\Paginator): $k = 0; $__LIST__ = $parents;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($k % 2 );++$k;?>
                                        <option value='<?php echo $vo->Menu_id; ?>' <?php if($vo->Menu_id == $item["Menu_pid"]): ?>selected="selected"<?php endif; ?>><?php echo $vo->Menu_name; ?></option>
                                        <?php endforeach; endif; else: echo "" ;endif; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label">菜单名称：</label>
                                <div class="col-sm-8">
                                    <input id="cname" name="Menu_name"  type="text" class="form-control" value="<?php echo (isset($item['Menu_name']) && ($item['Menu_name'] !== '')?$item['Menu_name']:''); ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label">菜单标示：</label>
                                <div class="col-sm-8">
                                    <input id="cemail" type="text" class="form-control" name="Menu_value" value="<?php echo (isset($item['Menu_value']) && ($item['Menu_value'] !== '')?$item['Menu_value']:''); ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label">是否可用：</label>
                                <div class="col-sm-8">

                                    <input type="radio" value="1" name="Menu_status" <?php if($item['Menu_status'] == 1): ?>checked<?php endif; ?>> <i></i> 启用
                                    <input type="radio" value="0" name="Menu_status" <?php if($item['Menu_status'] != 1): ?>checked<?php endif; ?>> <i></i> 禁用

                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label">排序：</label>
                                <div class="col-sm-8">
                                    <input id="cemail" type="number" class="form-control" name="Menu_order" value="<?php echo (isset($item['Menu_order']) && ($item['Menu_order'] !== '')?$item['Menu_order']:''); ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-sm-6 col-sm-offset-3">
                                    <button type="button" id='submit' class="btn btn-w-m btn-white">确认</button>
                                    <button type="button" onclick="javascript:history.go(-1);" class="btn btn-w-m btn-white">返回</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
  var url="<?php echo url('menu_create_ajax'); ?>";
  var url_index="<?php echo url('menu_list',['usertoken'=>$usertoken]); ?>";
  var url_edit="<?php echo url('edit'); ?>";
  if($("[name='Menu_id']").val()>0){
      url=url_edit;
  }
        $("#submit").click(function () {
            $.ajax({
                url:url,
                type: 'post',
                dataType: 'json',
                data: $('#menuForm').serialize(),
                success: function (res) {
                    if (res.stat == '0') {
                        swal({
                            title: "Error!",
                            text: res.errmsg,
                            type: "error",
                            confirmButtonText: "Cool"
                        });
                    } else {
                        location.href=url_index;
                    }

                },
                error: function () {
                    swal("网络错误", "", "error");
                    //alert('网络错误');
                }

            });

        });

    </script>
</body>
</html>

