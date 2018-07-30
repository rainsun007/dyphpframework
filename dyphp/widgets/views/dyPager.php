<?php DyStatic::regCss($dyPhpPagerStyle.'?v='.rand(10000,99999)); ?>

<div class="dypage">
    <a href="javascript:;" title="PageSize·Total  Current/PageCount">
        <?php echo $dyPhpPagerPageSize.'·'.$dyPhpPagerDataCount.'&nbsp;&nbsp;'.$dyPhpPagerCurrentPage.'/'.$dyPhpPagerPageCount;?>
    </a>
    <?php echo $dyPhpPagerShow;?>
</div>
