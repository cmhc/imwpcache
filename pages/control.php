<div class="wrap">
<h1>缓存功能</h1>
<p></p>
<form action="" method="post">
    <input type="hidden" name="clearcache" value="1">
    <input type="submit" class="button button-primary" value="清除所有缓存">
</form>

<h4>查看页面是否被缓存</h4>
<form action="" method="post">
    输入页面地址：<input type="text" name="pageurl" value="<?php echo $pageurl;?>">
    <input type="submit" class="button button-primary" value="查看">
    <?php
        if (isset($content)) {
            if ($content != '') {
                echo '<p>'.$pageurl . '已经缓存</p>';
            } else {
                echo '<p>'.$pageurl . '没有被缓存</p>';
            }
        }
    ?>
</form>

<?php
if (isset($delstatus) && $delstatus) {
    echo '<p>缓存删除成功，当页面在次被访问时，将会自动创建缓存</p>';
}
if (isset($content) && $content != '') { ?>
    <form action="" method="post">
        <input type="hidden" name="delcache" value="<?php echo $pageurl;?>">
        <input type="submit" class="button button-primary" value="删除该缓存">
    </form>
<?php } ?>
</div>
