<!DOCTYPE html>
<html lang="zh-cmn-Hans">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
        <meta content=""  name="Description" />
        <meta content="" name="Keywords" />
        <title><?php echo $this->pageTitle(); ?></title>

        <?php VHelper::regCss('layui/css/layui.css'); ?>

        <?php VHelper::regJs('jquery-2.2.3.js', 'head'); ?>
        <?php VHelper::regJs('layui/layui.js', 'head'); ?>
    </head>

    <body>
        <?php $this->renderPartial('/Layout/header'); ?>
        <?php  echo $content; ?>
        <?php $this->renderPartial('/Layout/footer'); ?>
        <?php DyDebug::show();?>
    </body>
</html>
