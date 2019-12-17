<table style="border: 0;">
    <tr>
        <td style="border: 0;padding: 0;">
            <img style="border:0" onclick="dyRefreshCaptchaImg();"  id="captchaShowImg" name="captchaShowImg" src="<?php echo $dycwRequest; ?>" />
        </td>
        <td style="border: 0;font-size: 12px; padding-left:5px;" valign="bottom">
            <span onclick="dyRefreshCaptchaImg();" style="border-bottom: 1px solid #0000FF;color: #0000FF;cursor:pointer;">
                <?php echo $dycwButtonLabel; ?>
            </span>
        </td>
    </tr>
</table>

<script language="javascript" type="text/javascript">
   function dyRefreshCaptchaImg(){
        var url = '<?php echo $dycwRequest.(DyPhpConfig::getRestCa() ? "?" : "&");?>r='+Math.floor(Math.random()*1000+1);
        document.getElementById('captchaShowImg').setAttribute("src",url);
   }
</script>
