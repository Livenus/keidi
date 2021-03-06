<?php if (!defined('THINK_PATH')) exit(); /*a:3:{s:82:"D:\phpstudy\PHPTutorial\WWW\kedi\public/../application/admin\view\.\menu_list.html";i:1538213684;s:74:"D:\phpstudy\PHPTutorial\WWW\kedi\application\admin\view\public_header.html";i:1538213684;s:74:"D:\phpstudy\PHPTutorial\WWW\kedi\application\admin\view\public_footer.html";i:1538213684;}*/ ?>
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
            <div class="col-sm-12">
                <div class="ibox float-e-margins">
                    <div class="ibox-title">
                        <h5>菜单列表</h5>
                        <div class="ibox-tools">
                            <a class="collapse-link">
                                <i class="fa fa-chevron-up"></i>
                            </a>
                            <a class="dropdown-toggle" data-toggle="dropdown" href="table_data_tables.html#">
                                <i class="fa fa-wrench"></i>
                            </a>
                            <a class="close-link">
                                <i class="fa fa-times"></i>
                            </a>
                        </div>
                    </div>
                    <div class="ibox-content">
                       <button class="btn btn-primary " type="button" onclick='create_menu()'>添加</button>
                       <table class="table table-striped table-bordered table-hover dataTables-example">
                            <thead>
                                <tr>
                                    <th>菜单名称</th>
                                    <th>菜单值</th>
                                    <th>状态</th>
                                    <th>排序</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(is_array($data) || $data instanceof \think\Collection || $data instanceof \think\Paginator): $i = 0; $__LIST__ = $data;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?>
                                <tr class="parent" onclick="switch_sons(<?php echo $vo['Menu_id']; ?>)">
                                    <td><?php echo $vo['Menu_name_c']; ?></td>
                                    <td><?php echo $vo['Menu_value']; ?></td>
                                    <td> 
                                      <?php switch($vo['Menu_status']): case "0": ?> 禁用<?php break; case "1": ?> 启用<?php break; default: ?> 禁用
                                      <?php endswitch; ?>
                                    </td>
                                    <td class="center"><?php echo $vo['Menu_order']; ?></td>
                                    <td class="center"><a href="<?php echo url('edit',array('usertoken'=>$usertoken,'id'=>$vo['Menu_id'])); ?>">编辑</a></td>
                                </tr>
                                <?php if(is_array($vo['Menu_sons']) || $vo['Menu_sons'] instanceof \think\Collection || $vo['Menu_sons'] instanceof \think\Paginator): $i = 0; $__LIST__ = $vo['Menu_sons'];if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$son): $mod = ($i % 2 );++$i;?>
                                  <tr class="son_<?php echo $vo['Menu_id']; ?>">
                                    <td><?php echo $son['Menu_name_c']; ?></td>
                                    <td><?php echo $son['Menu_value']; ?></td>
                                    <td>
                                      <?php switch($son['Menu_status']): case "0": ?> 禁用<?php break; case "1": ?> 启用<?php break; default: ?> 禁用
                                      <?php endswitch; ?>
                                    </td>
                                    <td class="center"><?php echo $son['Menu_order']; ?></td>
                                    <td class="center"><a href="<?php echo url('edit',array('usertoken'=>$usertoken,'id'=>$son['Menu_id'])); ?>">编辑</a></td>
                                  </tr>
                                <?php endforeach; endif; else: echo "" ;endif; endforeach; endif; else: echo "" ;endif; ?>
                        </table>

                    </div>
                </div>
            </div>
        </div>
    </div>
<script>
var usertoken = "<?php echo $usertoken; ?>";
var add="<?php echo url('menu/menu_create',['usertoken'=>$usertoken]); ?>"
    function switch_sons(pid){
      var tr = $('.son_'+pid);
      if(tr.css('display') == 'none'){
        tr.show();
      }else{
        tr.hide();
      }
    }
    function edit_menu(auth_id){
        window.location.href = '../auth/auth_update.html?auth_id='+auth_id+'&usertoken='+usertoken;
    }
    function create_menu(){
      window.location.href = add;
    }


</script>
</body>
</html>

