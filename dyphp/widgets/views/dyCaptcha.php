<?php 
    DyStatic::regScript(viewHelper::getStaticPath('jquery.js'),'head');
    $captchaRequest = $options['request']; 
    $isRest = DyPhpConfig::getRestCa();    
?>

<script language="javascript" type="text/javascript">
   function dyRefreshCaptchaImg(){
        var url = '<?php echo $captchaRequest.($isRest?'?':'&');?>r='+Math.floor(Math.random()*1000+1);
        $('#captchaShowImg').attr("src",url);
   }
</script>

<table style="border: 0;">
    <tr>
        <td style="border: 0;padding: 0;">
            <img border="0" id="captchaShowImg" name="captchaShowImg" src="<?php echo $captchaRequest; ?>" />
        </td>
        <td style="border: 0;font-size: 12px; padding-left:5px;" valign="bottom">
            <span onclick="dyRefreshCaptchaImg();" style="border-bottom: 1px solid #0000FF;color: #0000FF;cursor:pointer;">
                <?php echo $options['buttonLabel']; ?>
            </span>
        </td>
    </tr>
</table>


