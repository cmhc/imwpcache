<div class="wrap">

    <h1>缓存设置</h1>
    <form class="" action="" method="post">

        <table class="form-table">
            <tr>

                <td>
                    <label for="type">缓存类型</label>
                </td>

                <td>
                    <select name="type" id="type">
                        <option value="file" <?php if($config['type'] == 'file') echo 'selected="selected"'; ?> >文件缓存</option>
                        <option value="memcache" <?php if($config['type'] == 'memcache') echo 'selected="selected"'; ?> >Memcache缓存</option>
                        <option value="redis" <?php if($config['type'] == 'redis') echo 'selected="selected"'; ?> >Redis缓存</option>
                    </select>
                    当为文件缓存的时候，缓存服务器和端口选项无效
                </td>

            </tr>

            <tr>
                <td>
                    <label for="host">缓存服务器</label>
                </td>
                <td>
                    <input type="text" id="host" name="host" value="<?php echo $config['host'];?>">
                </td>
            </tr>

            <tr>
                <td>
                    <label for="post">端口</label>
                </td>
                <td>
                    <input type="text" name="port" id="port" value="<?php echo $config['port'];?>">
                </td>
            </tr>

            <tr>
                <td><label for="expires">缓存有效期</label></td>
                <td><input type="text" name="expires" id="expires" value="<?php echo $config['expires'];?>">秒(推荐3600)</td>
            </tr>
            <tr>
                <td><input type="submit" class="button button-primary" value="保存"></td>
                <td></td>
            </tr>
        </table>




    </form>

</div>
