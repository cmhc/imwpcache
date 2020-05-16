<?php
echo "<table class='wp-list-table widefat fixed striped'>";

    echo "<tr><td>Memcache版本</td><td> ".$stats ["version"]."</td></tr>";
    echo "<tr><td>进程号</td><td>".$stats ["pid"]."</td></tr>";
    echo "<tr><td>运行时间</td><td>".$stats ["uptime"]."</td></tr>";
    echo "<tr><td>用户时间单位</td><td>".$stats ["rusage_user"]." seconds</td></tr>";
    echo "<tr><td>系统时间单位</td><td>".$stats ["rusage_system"]." seconds</td></tr>";
    echo "<tr><td>自运行以来存储的条数</td><td>".$stats ["total_items"]."</td></tr>";
    echo "<tr><td>当前开启的连接数</td><td>".$stats ["curr_connections"]."</td></tr>";
    echo "<tr><td>自运行以来开启的连接数</td><td>".$stats ["total_connections"]."</td></tr>";
    echo "<tr><td>服务器分配的连接数</td><td>".$stats ["connection_structures"]."</td></tr>";
    echo "<tr><td>get累计数 </td><td>".$stats ["cmd_get"]."</td></tr>";
    echo "<tr><td>set累计数</td><td>".$stats ["cmd_set"]."</td></tr>";

    $percCacheHit = ((real) $stats["get_hits"] / (real)$stats["cmd_get"] *100);
    $percCacheHit = round($percCacheHit,3);
    $percCacheMiss = 100-$percCacheHit;

    echo "<tr><td>命中量/命中率</td><td>".$stats["get_hits"]." ($percCacheHit%)</td></tr>";
    echo "<tr><td>miss/miss率</td><td>".$stats["get_misses"]."($percCacheMiss%)</td></tr>";

    $MBRead= (real) $stats["bytes_read"]/(1024*1024);

    echo "<tr><td>读取字节数</td><td>".$MBRead." Mega Bytes</td></tr>";
    $MBWrite=(real) $stats["bytes_written"]/(1024*1024) ;
    echo "<tr><td>发送字节数</td><td>".$MBWrite." Mega Bytes</td></tr>";
    $MBSize=(real) $stats["limit_maxbytes"]/(1024*1024) ;
    echo "<tr><td>总容量</td><td>".$MBSize." Mega Bytes</td></tr>";
    echo "<tr><td>已使用</td><td>".($stats['bytes']/1024/1024)."M</td></tr>";
    echo "<tr><td>能够从缓存中删除的条目数量</td><td>".$stats ["evictions"]."</td></tr>";

echo "</table>";
