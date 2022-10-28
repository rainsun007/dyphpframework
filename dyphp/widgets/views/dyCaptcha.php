<table style="border: 0;padding: 0;">
    <tr>
        <td style="border: 0;padding: 0;">
            <img style="border:0;cursor:pointer;" onclick="<?php echo $dyCWRefreshCaptcha; ?>;" id="<?php echo $dyCWElementId; ?>" name="<?php echo $dyCWElementId; ?>" src="<?php echo $dyCWRequest; ?>" />
        </td>
        <td style="border: 0;padding: 0;" valign="bottom">
            <span onclick="<?php echo $dyCWRefreshCaptcha; ?>;" style="font-size: 12px;color: #757575;border: 0;padding: 0 0 0 5px;cursor:pointer;">
                <?php echo $dyCWButtonLabel; ?>
            </span>
        </td>
    </tr>
</table>

<script language="javascript" type="text/javascript">
    function <?php echo $dyCWRefreshCaptcha; ?> {
        var url = '<?php echo $dyCWRequest . (DyPhpConfig::getRestCa() ? "?" : "&"); ?>r=' + Math.floor(Math.random() * 1000 + 1);
        document.getElementById('<?php echo $dyCWElementId; ?>').setAttribute("src", url);
    }
</script>