<?php
echo "<table class='wp-list-table widefat fixed striped'>";

    echo "<tr><td>Redis版本</td><td> ".$stats ["redis_version"]."</td></tr>";
    echo "<tr><td>操作系统</td><td> ".$stats ["os"]."</td></tr>";

    echo "<tr><td>进程号</td><td>".$stats ["process_id"]."</td></tr>";
    echo "<tr><td>运行天数</td><td>".$stats ["uptime_in_days"]."天</td></tr>";
    echo "<tr><td>当前连接数</td><td>".$stats ["connected_clients"]."</td></tr>";

    $percCacheHit=((real)$stats ["keyspace_hits"]/ ((real)$stats ["keyspace_misses"] + (real)$stats ["keyspace_hits"])*100);
    $percCacheHit = round($percCacheHit,3);
    $percCacheMiss = 100-$percCacheHit;

    echo "<tr><td>命中量/命中率</td><td>".$stats ["keyspace_hits"]." ($percCacheHit%)</td></tr>";
    echo "<tr><td>miss/miss率</td><td>".$stats ["keyspace_misses"]."($percCacheMiss%)</td></tr>";

    echo "<tr><td>内存使用</td><td>".(sprintf('%.2f',$stats['used_memory_human']/1024))."M</td></tr>";
    echo "<tr><td>内存使用峰值</td><td>".(sprintf('%.2f',$stats['used_memory_peak_human']/1024))."M</td></tr>";

echo "</table>";
