<html>
<head>
   <style type="text/css">body {font-size:16px;} .error {background-color:red} .small {font-size:12px}</style>
   <script src="/js/jquery-1.10.2.js" type="text/javascript"></script>
   <script type="text/javascript">
    $(document).ready(function() {
        $('#login').click(function() {
            $('#login').attr("href", $('#login').attr("href") + "?r=" + window.btoa($(location).attr('pathname'))));
        });
    });
   </script>
</head>
<body>

<a href="/">Home</a> | <a href="/about">About</a> | <a href="/admin">Admin</a>

<?if (empty($user)):?>
    | <a href="/login" id="login">Login</a>
<?else:?>
    | <a href="/logout">Logout</a>
<?endif;?>

<hr/>