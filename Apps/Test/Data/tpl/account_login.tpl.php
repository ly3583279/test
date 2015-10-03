<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>account_login.phtml</title>
    <link rel="stylesheet" type="text/css" href="/Apps/Public/Test/css/style.css" />
    <script src="/Apps/Public/Test/js/jquery.js"></script>
</head>
<body>
<div id="loginpanelwrap">

    <div class="loginheader">
        <div class="logintitle"><a href="#">登陆界面</a></div>
    </div>

    <form method="post" id="form">
        <div class="loginform">

            <div class="loginform_row">
                <label>用户账号:</label>
                <input type="text" class="loginform_input" name="username" id="username"/>
            </div>

            <div class="loginform_row">
                <label>用户密码:</label>
                <input type="text" class="loginform_input" name="password" id="password"/>
            </div>

            <div class="loginform_row">
                <input type="submit" class="loginform_submit" value="登录" />
            </div>
            <div class="clear"></div>
        </div>
    </form>

</div>
</body>
<script>
$(function(){
    $('#username').focus();

    $('#username').blur(function(){
        var username = $('#username').val();
        if(username === ''){
            alert('用户名不能为空');
        }
    })
    $('#password').blur(function(){
        var password = $('#password').val();
        if(password === ''){
            alert('密码不能为空');
        }
    })

    $('#form').submit(function(){
        var username = $('#username').val();
        var password = $('#password').val();

        if(username === ''){
            alert('用户名不能为空');
        }
        if(password === ''){
            alert('密码不能为空');
        }

        var str = username + ',' + password;
        $.ajax({
            type: "POST",
            url:'/Apps/index.php?c=Account&m=login',
            data:'str=' + str,
            success: function(res) {
                if(res == 'success'){
                    window.location.href = '/Apps/index.php?c=Main&m=index';
                }else{
                    alert('用户名或密码错误。');
                }
            }
        });
        return false;
    });

})
</script>
</html>