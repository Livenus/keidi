<?php if (!defined('THINK_PATH')) exit(); /*a:3:{s:82:"D:\phpstudy\PHPTutorial\WWW\kedi\public/../application/admin\view\.\user_list.html";i:1538213684;s:74:"D:\phpstudy\PHPTutorial\WWW\kedi\application\admin\view\public_header.html";i:1538213684;s:74:"D:\phpstudy\PHPTutorial\WWW\kedi\application\admin\view\public_footer.html";i:1538213684;}*/ ?>
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
<style>
    .float-right{
        float:right;
    }
 </style>
<body class="gray-bg">
    <div class="wrapper wrapper-content animated fadeInRight">
        <div class="row">
            <div class="col-sm-12">
                <div class="ibox float-e-margins">
                    <div class="ibox-title">
                        <h5>管理员列表</h5>
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
                          <button class="btn btn-primary " type="button" onclick='user_add()'>添加</button>
                        <!---search start-->
                        <div class="float-right">
                            <form id="search">
                                <span style=" line-height: 30px; margin-right: 5px;">username:</span>
                                <input type='hidden' name='usertoken' value="<?php echo $usertoken; ?>">
                                <input type="text" name="username" value="" class="input_zdy input-sm" size=15 placeholder="用户名"/>
                                <span style=" line-height: 30px; margin-right: 5px;">realname:</span>
                                <input type="text" name="realname" value="" class="input_zdy input-sm" size=15 placeholder="真实姓名"/>
                                <span style=" line-height: 30px; margin-right: 5px;">Country:</span>
                                <input type="text" name="Country" value="" class="input_zdy input-sm" size=15 placeholder="国家"/>
                                <span style=" line-height: 30px; margin-right: 5px;">dept:</span>
                                <input type="text" name="Dept" value="" class="input_zdy input-sm" size=15 placeholder="部门"/>
                                <span style=" line-height: 30px; margin-right: 5px;">Group:</span>
                                <input type="text" name="GroupID" value="" class="input_zdy input-sm" size=15 placeholder="权限组"/>
                                <input type="hidden" name="level" value="1" class="input_zdy input-sm" />
                                <a href="javascript:;" id="btnsearch" class="btn btn-primary btn-sm">查询</a>
                            </form>
                        </div>
                        <!---search end---->
                        <table class="table table-striped noticeList" id="userlist" cellspacing="0" width="100%" style="border-bottom: none !important;"></table>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="/static/js/plugins/layer/laydate/laydate.js"></script>
    <script>
var usertoken = "<?php echo $usertoken; ?>";
var index = "<?php echo url('user_list_ajax',['usertoken'=>$usertoken]); ?>";
        var add_url = "<?php echo url('user/add',array('usertoken'=>$usertoken)); ?>";
var edit = "<?php echo url('edit',['usertoken'=>$usertoken]); ?>";
var table={};
function load_users() {

    table=$("#userlist").DataTable({
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
            "url": index,
            "dataType": "json", //返回来的数据形式
            'type': 'post',
            'data': function ( d ) {
               o3 = $.extend({}, d, getQueryParams())
return o3;
}
        },
        "columns": [//定义列数据来源
            {'title': "UserName", 'data': "username", 'searchable': false, 'orderable': false, "width": "80px"},
            {'title': "RealName", 'data': "realname", 'searchable': false, 'orderable': false, "width": "80px"},
            {'title': "Country", 'data': "Country", 'searchable': false, 'orderable': false, "width": "80px"},
            {'title': "权限组", 'data': "Group_name", 'searchable': false, 'orderable': false, "width": "80px"},
            {'title': "Dept", 'data': "Dept", 'searchable': false, 'orderable': false, "width": "80px"},
            {'title': "Tool", 'data': "UserID", 'searchable': false, 'orderable': false, "width": "80px"},
        ],
        "columnDefs": [//自定义列
            {

                "targets": 5,
                "data": "UserID",
                "render": function (data, type, row) {
                    var html = '';
                    html += '<button type="button" onclick="edit_user(' + data + ')" class="btn btn-w-m btn-white">信息编辑</button>';
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
        /*"createdRow":function(row,data,index){ //行回调函数
         if(data.m_id % 2 == 0){
         $('td',row).eq(0).css({"color":"#00A8FF","font-weight":"bold"});
         }
         }
         */
    });
}
function edit_user(userID) {
    location.href = edit + "?userID=" + userID;
}
$(document).ready(function () {
    load_users();
})

function getQueryParams() {
    var q = {};
    var params = $("#search").serializeArray();
    for (var item in params) {
        q[params[item].name] = params[item].value;
    }
    return q;
}
$("#btnsearch").click(function () {
    q = getQueryParams();
    console.log(q);
    var data = {};
    data.url = index;
    data.query = q;
   table.ajax.reload();
})
    function user_add(){
      window.location.href =add_url;
    }
    </script>
</body>
</html>

