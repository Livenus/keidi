<?php if (!defined('THINK_PATH')) exit(); /*a:3:{s:87:"D:\phpstudy\PHPTutorial\WWW\kedi\public/../application/admin\view\.\nconfig\create.html";i:1538213685;s:74:"D:\phpstudy\PHPTutorial\WWW\kedi\application\admin\view\public_header.html";i:1538213684;s:74:"D:\phpstudy\PHPTutorial\WWW\kedi\application\admin\view\public_footer.html";i:1538213684;}*/ ?>
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
                        <h5>系统设置</h5>
                    </div>
                    <div class="ibox-content">
                        <form class="form-horizontal m-t" id="authForm">
                             <input  name="id"  type="hidden" class="form-control" value="<?php echo (isset($item['id']) && ($item['id'] !== '')?$item['id']:'0'); ?>">
                            <div class="form-group">
                                <label class="col-sm-3 control-label">Unit_Price：</label>
                                <div class="col-sm-8">
                                    <input  name="Unit_Price"  type="text" class="form-control" value="<?php echo (isset($item['Unit_Price']) && ($item['Unit_Price'] !== '')?$item['Unit_Price']:'4000'); ?>">
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
<script src="/static/js/plugins/layer/laydate/laydate.js"></script>
    <script>
        var create_url = "<?php echo url('Nconfig/create',array('usertoken'=>$usertoken)); ?>";
        var edit_url = "<?php echo url('Nconfig/edit',array('usertoken'=>$usertoken)); ?>";
        $("#submit").click(function () {
            if(0){
                var url=edit_url;
            }else{
                 var url=create_url;
            }
            $.ajax({
                url: url,
                type: 'post',
                dataType: 'json',
                data: $('#authForm').serialize(),
                success: function (res) {
                    if (res.stat == '0') {
                        swal({
                            title: "Error!",
                            text: res.errmsg,
                            type: "error",
                            confirmButtonText: "Cool"
                        });
                    } else {
                        location.reload();
                    }

                },
                error: function () {
                    swal("网络错误", "", "error");
                }

            });

        });

    </script>
</body>
</html>

