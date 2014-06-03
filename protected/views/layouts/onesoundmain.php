<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="">
        <meta name="author" content="">

        <link href="/images/gur-project/gur-project-03.png" rel="shortcut icon">

        <title>OS网上</title>
       
        <?php $this->oneSoundCss()?>
        <!-- Just for debugging purposes. Don't actually copy this line! -->
        <!--[if lt IE 9]><script src="../../docs-assets/js/ie8-responsive-file-warning.js"></script><![endif]-->

        <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
        <!--[if lt IE 9]>
          <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
          <script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
        <![endif]-->
    </head>

    <body>

        <div class="navbar navbar-inverse navbar-fixed-top bs-docs-nav" role="navigation">
            <div class="container">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                        <span class="sr-only">Toggle navigation</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                    <a class="navbar-brand" href="/">首页</a>
                </div>
                <div class="collapse navbar-collapse">
                    <ul class="nav navbar-nav">
                        <!--<li class="active"><a href="http://www.onesound.com/">Home</a></li>
                        <li><a href="http://www.onesound.com/">About</a></li>
                        <li><a href="">Contact</a></li>-->
                    </ul>
                    <ul class="nav navbar-nav navbar-right">
                        <?php $userId = Yii::app()->user->id;
                        $user = Admin::model()->findByPk($userId);
                        if(!empty($userId)){
                            echo '<li><a href="/user/">我的网上</a></li>';
                        }else{?>
                            <li><a href="/login/">登录</a></li>
                        <?php }?>
                    </ul>
                </div><!--/.nav-collapse -->
            </div>
        </div>

        <?php echo $content;?>


        <!-- Bootstrap core JavaScript
        ================================================== -->
        <!-- Placed at the end of the document so the pages load faster -->
        <script src="https://code.jquery.com/jquery-1.10.2.min.js"></script>
        <script src="/js/bootstrap.min.js"></script>
    </body>
</html>
