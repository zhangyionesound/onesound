<div class="container">
    <div class="starter-template">
        <h1 class="text-primary">网上</h1>
        <p class="lead text-success">onesound</p>
    </div>
    <?php
    $form = $this->beginwidget('CActiveForm', array(
        'id' => 'admin-login-form',
        'enableClientValidation' => TRUE,
    ));
    ?>
    <div class="col-md-offset-4 col-md-4">
        <div class="panel panel-default">
            <div class="panel-body">
                <div class="input-group input-group-lg center-block">
                    <input type="text" class="form-control" placeholder="Username" id="AdminLoginForm_name" name="AdminLoginForm[name]">
                </div>
            </div>
        </div>
        <div><?php echo $form->error($model, 'name', array()); ?></div>
        <div class="panel panel-default">
            <div class="panel-body">
                <div class="input-group input-group-lg center-block">
                    <input type="text" class="form-control" placeholder="Password" id="AdminLoginForm_password" name="AdminLoginForm[password]">
                </div>
            </div>
        </div>
        <div><?php echo $form->error($model, 'password', array()); ?></div>
        <button type="submit" class="btn btn-primary navbar-btn btn-lg">Sign in</button>
        <button type="button" class="btn btn-success btn-lg">Create a free Account</button>
    </div>
    <?php $this->endWidget(); ?>

</div><!-- /.container -->


