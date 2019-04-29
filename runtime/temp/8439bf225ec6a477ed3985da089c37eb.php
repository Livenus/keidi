<?php if (!defined('THINK_PATH')) exit(); /*a:3:{s:97:"D:\phpstudy\PHPTutorial\WWW\kedi\public/../application/admin\view\.\nversions\nversions_list.html";i:1538213685;s:74:"D:\phpstudy\PHPTutorial\WWW\kedi\application\admin\view\public_header.html";i:1538213684;s:74:"D:\phpstudy\PHPTutorial\WWW\kedi\application\admin\view\public_footer.html";i:1538213684;}*/ ?>
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
                        <h5>版本列表</h5>
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
                        <button class="btn btn-primary " type="button" onclick='create()'>添加</button>
                        <table class="table table-striped noticeList" id="table_data" cellspacing="0" width="100%" style="border-bottom: none !important;"></table>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        var usertoken = "<?php echo $usertoken; ?>";
        var url = "<?php echo url('Nversions/Nversions_list_ajax'); ?>";
        var create_url = "<?php echo url('Nversions/create',array('usertoken'=>$usertoken)); ?>";
        var edit_url = "<?php echo url('Nversions/edit',array('usertoken'=>$usertoken)); ?>";
            $("#table_data").DataTable({
                /*   "autoWidth":true,
                 "scrollX": true,*/
                "paging": true,
                "destroy": true,
                "pagingType": "full_numbers",
                "lengthMenu": [5, 10, 25, 50],
                "processing": true,
                "searching": false, //是否开启搜索
                "serverSide": true, //开启服务器获取数据
                "ajax": {// 获取数据
                    "url": url,
                    "dataType": "json", //返回来的数据形式
                    'type': 'post',
                    'data': {usertoken: usertoken}
                },
                "columns": [//定义列数据来源
                    {'title': "ID", 'data': "Versions_id", 'searchable': false, 'orderable': false, "width": "80px"},
                    {'title': "名称", 'data': "Versions_name", 'searchable': false, 'orderable': false, "width": "80px"},
                    {'title': "版本号", 'data': "Versions_code", 'searchable': false, 'orderable': false, "width": "80px"},
                    {'title': "状态", 'data': "Versions_stat", 'searchable': false, 'orderable': false, "width": "80px"},
                    {'title': "时间", 'data': "Versions_push_time", 'searchable': false, 'orderable': false, "width": "80px"},
                    {'title': "操作", 'data': "Versions_id", 'searchable': false, 'orderable': false, "width": "80px"},
                ],
                "columnDefs": [//自定义列
                    {

                        "targets": 5,
                        "data": "Versions_id",
                        "render": function (data, type, row) {
                            var html = '';
                            html += '<button type="button" onclick="edit(' + data + ')" class="btn btn-w-m btn-white">编辑</button>';
                            return html;
                        }

                    }
                ],
                language:
                        {
                            paginate: {//分页的样式内容。
                                previous: "上一页",
                                next: "下一页",
                                first: "首页",
                                last: "末页"
                            },

                            zeroRecords: "没有内容", //table tbody内容为空时，tbody的内容。
                            //下面三者构成了总体的左下角的内容。
                            info: "共 _TOTAL_ 条，初始_MAX_ 条，显示第_START_ 条 到第 _END_ 条", //左下角的信息显示，大写的词为关键字。
                            infoEmpty: "共0条记录", //筛选为空时左下角的显示。
                            infoFiltered: ""//筛选之后的左下角筛选提示，
                        },
            });
        
        function edit(id) {
            var url=edit_url+"&id="+id;
            window.location.href =edit_url+"?Versions_id="+id;
        }
        function create() {
            window.location.href = create_url;
        }


    </script>
</body>
</html>

