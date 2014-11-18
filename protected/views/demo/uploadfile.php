<!DOCTYPE html>

<html>
    <head>
        <meta charset="utf-8">
        <title>LOGIN DEMO</title>
        <link href="/js/uploadify-v3.1/uploadify.css" type="text/css" rel="stylesheet" />
        <script type="text/javascript" src="/js/uploadify-v3.1/jquery-1.7.2.min.js"></script>
        <script type="text/javascript" src="/js/uploadify-v3.1/jquery.uploadify-3.1.min.js"></script>
        <style>
            #photos li {
                float: left;
                width: 160px;
                height: 280px;
                text-align: left;
                padding-top: 10px;
            }
        </style>
    </head>

    <body>
        <div class="control-group">
            <form action="Addimage" method="get" name="images">
                <input id = "pic_file" name = "pic_file" required="required"/>
                <div id = "url" name = "imageurl">
                </div>

                <input id="tijiao" type="submit" value="保存"/>
            </form>
        </div>
    </body>
</html>

<script type="text/javascript">
//上传图片
    $(function() {
        $("#tijiao").click(function() {
            var tu = $("input[name='imgname[]']").val();
            if (tu == "" || tu == null) {
                alert("请上传图片了再提交!");
                return false;
            }
        });
    });
    $(function() {
        var upload_failed = new Array();
        var failed_index = 0;
        pic_flag = false;
        $("#pic_file").uploadify({
            'formData': {'width': '370', 'height': '255', 'session_id': '<?php echo Yii::app()->session->sessionID; ?>'}, //图片的大小
            'swf': '/js/uploadify-v3.1/uploadify.swf', //swf文件路径
            'uploader': '/demo/uploader/', //操作的action
            'cancelImg': '/js/uploadify-v3.1/uploadify-cancel.png', // 关闭按钮图片
            'auto': true, //自动上传
            'fileTypeDesc': '文件',
            'fileDesc': 'Image(*.jpg;*.gif;*.png;*.txt)', //对话框的文件类型描述
            'fileTypeExts': '*.txt;*.jpg;*.png;*.jpeg;*.gif',
            'buttonText': '上传文件',
            'multi': true,
            'onUploadSuccess': function(file, data, response) {//每次成功上传后执行的回调函数，从服务端返回数据到前端
                alert(data);
                console.log(data);
                return false;
                data = eval("(" + data + ")");
                if (!data['success']) {
                    upload_failed[failed_index] = file.name + " -- " + data['msg'];
                    failed_index++;
                } else {
                    pic_flag = true;
                    var url = data['data'].url;
                    var originalurl = data['data'].old_url;
                    var imgname = data['data'].name;
//        	    $('#pic_show').remove();
                    $('#url').after('<input type = "hidden" name="originalurl[]" value=' + originalurl + '>');
                    $('#url').after('<input type = "hidden" name="imgname[]" value="' + imgname + '">&nbsp;');
                    $('#url').after('<img id = "pic_show" src=' + url + ' />');
                }
            },
            'onQueueComplete': function(queueData) {//队列完成后回调的函数
                var failed_msg = '';
                if (upload_failed.length > 0) {
                    for (i = 0; i < upload_failed.length; i++)
                    {
                        var failed_msg = failed_msg + upload_failed[i] + "\n";
                    }
                    alert("失败文件: \n" + failed_msg);
                }
            }

        });
    });

</script>