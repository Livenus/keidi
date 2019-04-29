<?php if (!defined('THINK_PATH')) exit(); /*a:1:{s:78:"D:\phpStudy\PHPTutorial\WWW\kedi\public/../application/admin\view\.\login.html";i:1538213684;}*/ ?>
<!DOCTYPE html>

<html>



    <head>



        <meta charset="utf-8">

        <meta name="viewport" content="width=device-width, initial-scale=1.0">





        <title>总后台登录</title>

        <meta name="keywords" content="H+后台主题,后台bootstrap框架,会员中心主题,后台HTML,响应式后台">

        <meta name="description" content="H+是一个完全响应式，基于Bootstrap3最新版本开发的扁平化主题，她采用了主流的左右两栏式布局，使用了Html5+CSS3等现代技术">



        <link rel="shortcut icon" href="favicon.ico"> <link href="/static/admin/css/bootstrap.min.css?v=3.3.6" rel="stylesheet">

        <link href="/static/admin/css/font-awesome.css?v=4.4.0" rel="stylesheet">



        <link href="/static/admin/css/animate.css" rel="stylesheet">

        <link href="/static/admin/css/style.css?v=4.1.0" rel="stylesheet">

        <!-- Sweet Alert -->

        <!-- <link rel="stylesheet" type="text/css" href="/static/admin/org/sweetalert/src/sweetalert.css"> -->
        <link href="https://cdn.bootcss.com/sweetalert/1.1.3/sweetalert.css" rel="stylesheet">

        <link href="/static/admin/css/login.css" rel="stylesheet">

        <link href="https://cdn.bootcss.com/Swiper/3.4.2/css/swiper.min.css" rel="stylesheet">

        <!--[if lt IE 9]>
    
        <meta http-equiv="refresh" content="0;ie.html" />
    
        <![endif]-->

        <!-- <script>if(window.top !== window.self){ window.top.location = window.location;}</script> -->

        <style>

            .form-group{

                position: relative;

                margin-bottom: 22px;

            }

            .has-error span.help-block.m-b-none {

                position: absolute;

                left: 30px;

                top: -26px;

                padding: 4px 6px;

                font-size: 12px;

                font-family: 'Glyphicons Halflings';

                font-style: normal;

                font-weight: 400;

                line-height: 1;

                -webkit-font-smoothing: antialiased;

            }



            .has-error span.help-block.m-b-none:before{

                content: "\e088";

                margin-right: 3px;

                vertical-align: text-bottom;

                font-size: 13px;

            }

            #example{

                position: fixed;

                top: 50%;

                left: 50%;

                right: inherit;

                bottom: inherit;

                z-index: 1050;

                max-height: 500px;

                overflow: auto;

                width: 560px;

                margin: -250px 0 0 -280px;

                background-color: #ffffff;

                border: 1px solid #999;

                border: 1px solid rgba(0, 0, 0, 0.3);

                -webkit-border-radius: 6px;

                -moz-border-radius: 6px;

                border-radius: 6px;

                -webkit-box-shadow: 0 3px 7px rgba(0, 0, 0, 0.3);

                -moz-box-shadow: 0 3px 7px rgba(0, 0, 0, 0.3);

                box-shadow: 0 3px 7px rgba(0, 0, 0, 0.3);

                -webkit-background-clip: padding-box;

                -moz-background-clip: padding-box;

                background-clip: padding-box;

            }

            #example .modal-footer{

                text-align: center;

                border-top: 0px;

            }

            #example .btn{

                background-color: #AEDEF4;

                color: white;

                border: none;

                box-shadow: none;

                font-size: 17px;

                font-weight: 500;

                -webkit-border-radius: 4px;

                border-radius: 5px;

                padding: 10px 32px;

                margin: 26px 5px 0 5px;

                cursor: pointer;

            }

            #example h4{

                color: #575757;

                font-size: 30px;

                text-align: center;

                font-weight: 600;

                text-transform: none;

                position: relative;

                margin: 25px 0;

                padding: 0;

                line-height: 40px;

                display: block;

            }

            #example .modal-body{

                padding: 0px;

            }

            #example p{

                color: #797979;

                font-size: 16px;

                text-align: center;

                font-weight: 300;

                position: relative;

                text-align: inherit;

                float: none;

                margin: 0;

                padding: 0;

                line-height: normal;

                text-align: center;

            }



            #example .sa-icon {

                width: 80px;

                height: 80px;

                border: 4px solid gray;

                -webkit-border-radius: 40px;

                border-radius: 40px;

                border-radius: 50%;

                margin: 20px auto;

                margin-bottom: 0px;

                padding: 0;

                position: relative;

                box-sizing: content-box;

            }

            #example .sa-icon.sa-error .sa-x-mark {

                position: relative;

                display: block;

                animation: animateXMark 0.5s;



            }#example .sa-icon.sa-error .sa-line {

                position: absolute;

                height: 5px;

                width: 47px;

                background-color: #F27474;

                display: block;

                top: 37px;

                border-radius: 2px;

            }

            #example .modal-header{

                border-bottom: 0px;

            }

            #example .sa-icon.sa-error {

                border-color: #F27474;

            }

            #example .sa-icon.sa-error .sa-line.sa-right{

                webkit-transform: rotate(-45deg);

                transform: rotate(-45deg);

                right: 16px;

            }

            #example .sa-icon.sa-error .sa-line.sa-left{

                webkit-transform: rotate(45deg);

                transform: rotate(45deg);

                left: 17px;

            }

            #example .sa-icon.sa-error .sa-line {

                position: absolute;

                height: 5px;

                width: 47px;

                background-color: #F27474;

                display: block;

                top: 37px;

                border-radius: 2px;

            }



            .full-width {

                width: 83% !important;

                margin: 0 auto;

            }



            .swiper-pagination-bullet {

                width: 46px;

                height: 8px;

                display: inline-block;

                border-radius: 4px;

                border:1px solid #fff;



            }

            .swiper-pagination-bullet-active {



                background: #fff;

            }

        </style>

    </head>





    <body class="gray-bg" style="background-image: url(/static/admin/images/bj1.jpg);" >



        <!--   <div class="middle-box text-center loginscreen  animated fadeInDown">
      
              <div>
      
                 
      
                              <div class="m-t">
      
                      <div class="form-group">
      
                          <input type="text" class="form-control" placeholder="用户名" required="" name="username">
      
                      </div>
      
                      <div class="form-group">
      
                          <input type="password" class="form-control" placeholder="密码" required="" name="password">
      
                      </div> 
      
                                      <div class="form-group">
      
                          <input type="text" class="form-control" placeholder="验证码" required="" name="code">
      
                                              <span><img
      
                                                      src="<{:captcha_src()}>" style="width: 100%; height: 45px;"
      
                                                      onclick="this.src='<{:captcha_src()}>?t'+new Date().getTime();" id="captcha"/>
      
                                              </span>
      
                      </div>
      
                      <button type="submit" class="btn btn-primary block full-width m-b" id="submit">登 录</button>
      
      
      
      
      
                      <p class="text-muted text-center"> <a href="login.html#"><small>忘记密码了？</small></a>
      
                      </p>
      
                              </div>
      
                 
      
              </div>
      
          </div> -->

        <div id="" class="container">

            <div id="" class="row">

                <div class="col-md-6">

                    <div class="swiper-container zdySlider1">

                        <div class="swiper-wrapper">

                            <div class="swiper-slide" style="background-image: url(/static/admin/images/swi1.png);"></div>

                            <div class="swiper-slide" style="background-image: url(/static/admin/images/sw2.png);"></div>

                            <div class="swiper-slide" style="background-image: url(/static/admin/images/sw3.png);"></div>

                        </div>

                        <div class="swiper-pagination swiper-pagination1"></div>

                    </div>



                </div>

                <div class="col-md-6" style="position: relative;z-index: 2;">

                    <div class="login row">

                        <div class="col-md-12">



                            <div class="m-t" style="margin-bottom: 40px;">



                                <div class="form-group">

                                    <img src="/static/admin/images/logo1.png" style="width: 231px;margin-top: 52px;" alt="" />

                                </div>

                                <div class="form-group">

                                    <input type="text" class="form-control" placeholder="用户名" required="" name="username">

                                </div>

                                <div class="form-group">

                                    <input type="password" class="form-control" placeholder="密码" required="" name="password">

                                </div> 

                                <div class="form-group">

                                    <input type="text" class="form-control" placeholder="验证码" required="" name="code">

                                    <span><img

                                            src="/index.php/admin/common/getcode" style="width: 100%; height: 45px;"

                                            onclick="this.src = '/index.php/admin/common/getcode?t' + new Date().getTime();" id="captcha"/>

                                    </span>

                                </div>

                                <button type="submit" class="btn btn-primary block full-width m-b" id="submit">登 录</button>

                                <p class="text-muted text-center" style="margin-top: 10px;"> <a href="login.html#"><small>忘记密码了？</small></a>

                                </p>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

            <div class="foot" style="margin-top: 100px; text-align: center;">

                <p  style="color: #fff;">© yysweb Inc. yycms<br />由<a href="http://www.yysweb.com" target="_blank" style="color: #fff;">yysweb.com</a>提供支持 软件著作权编号：2017SR38137</p>

            </div>

        </div>

        <div class="backgroundLogin">

        </div>

        <!-- 全局js -->

        <script src="/static/admin/js/jquery.min.js"></script>

        <script src="https://cdn.bootcss.com/sweetalert/1.1.3/sweetalert.min.js"></script>
        <script>
                                                $("#submit").click(function () {
                                                    var params = {};
                                                    params.username = $('input[name=username]').val();
                                                    params.password = $('input[name=password]').val();
                                                    params.code = $('input[name=code]').val();
                                                    $.ajax({
                                                        url: "/index.php/admin/login/login_ajax",
                                                        type: 'post',
                                                        dataType: 'json',
                                                        data: params,
                                                        success: function (res) {
                                                            if (res.code == '1') {
                                                                swal({
                                                                    title: "Error!",
                                                                    text: res.errmsg,
                                                                    type: "error",
                                                                    confirmButtonText: "Cool"
                                                                });
                                                            } else {
                                                                window.location.href = '/index.php/admin/index/index.html?usertoken=' + res.data.usertoken;
                                                            }

                                                        },
                                                        error: function () {
                                                            swal("网络错误", "", "error");
                                                            //alert('网络错误');
                                                        }

                                                    });

                                                });



                                                onkeydown = function (event) {

                                                    if (event.keyCode == 13) {

                                                        $("#submit").click();

                                                    }

                                                }

        </script>

    </body>



</html>

