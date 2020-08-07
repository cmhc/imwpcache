<?php
echo "<table class='wp-list-table widefat fixed striped'>
     <tr><td>缓存文件个数</td><td>{$stats['count']}</td></tr>
     <tr><td>缓存文件大小</td><td>" . sprintf('%.2f',$stats['size']/1024/1024) . "MB</td></tr>
     <tr><td>命中量</td><td>{$stats['hits']}</td></tr>
     <tr><td>未命中量</td><td>{$stats['misses']}</td></tr>";
if ($stats['get'] == 0) {
    echo "<tr><td>命中率</td><td>0</td></tr>";
} else {
    echo "<tr><td>命中率</td><td>".intval($stats['hits']/$stats['get']*100) ."%</td></tr>";
}
echo "<tr><td>get总次数</td><td>{$stats['get']}</td></tr>
     <tr><td>set总次数</td><td>{$stats['set']}</td></tr>
</table>";
