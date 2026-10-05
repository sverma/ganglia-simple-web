<?php
// Implements the upstream monitoring command language. Commands never reach a shell.
function cli_error(string $message): never { throw new InvalidArgumentException($message); }
function cli_match(array $items, string $pattern): array {
    $pattern = trim($pattern);
    if (strlen($pattern)>256 || preg_match('/[\x00-\x1f]/', $pattern)) cli_error('Filter is too long or contains control characters.');
    if ($pattern === '') return array_values($items);
    if (strlen($pattern)>1 && in_array($pattern[0], ['"', "'"], true) && substr($pattern,-1)===$pattern[0]) $pattern=substr($pattern,1,-1);
    $matched = @preg_grep('~(*LIMIT_MATCH=10000)(*LIMIT_DEPTH=128)' . str_replace('~','\\~',$pattern) . '~i', $items);
    if ($matched===false || preg_last_error()!==PREG_NO_ERROR) cli_error('Invalid or overly complex regular expression.');
    return array_values($matched);
}
function cli_options(string $text): array {
    $options=[];
    while (($text=trim($text))!=='') {
        if (!preg_match('/^([a-z_]+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|(\S+))(?:\s+|$)/', $text, $match, PREG_UNMATCHED_AS_NULL)) cli_error('Use options such as groups="cpu" or name="CPU overview".');
        if (isset($options[$match[1]])) cli_error('Duplicate option: ' . $match[1]);
        $options[$match[1]]=$match[2] ?? $match[3] ?? $match[4];
        $text=substr($text,strlen($match[0]));
    }
    return $options;
}
function cli_select(string $command): array {
    if (!preg_match('/^(list|find)\s+(servers|metrics|metric_grps|clusters)(?:\s+(.*))?$/s',trim($command),$parts)) cli_error('Use list/find with servers, metrics, metric_grps, or clusters.');
    [$all,$verb,$type]=$parts;
    $argument=$parts[3] ?? '';
    $data=metadata();
    $catalog=['servers'=>$data->get_all_servers(), 'metrics'=>$data->get_all_metrics(),
              'metric_grps'=>$data->get_all_metrics_group(), 'clusters'=>$data->get_all_clusters()];
    if ($verb==='list') $items=cli_match($catalog[$type],$argument);
    else {
        $options=cli_options($argument);
        if (!$options) cli_error('find needs a filter, for example find metrics groups="cpu".');
        $items=$catalog[$type];
        foreach ($options as $key=>$pattern) {
            $filter=[];
            if ($type==='servers' && $key==='clusters') {
                foreach ($data->get_servers_from_clusters(cli_match($catalog['clusters'],$pattern)) as $values) $filter=array_merge($filter,$values);
            } elseif ($type==='metrics' && $key==='groups') {
                foreach ($data->get_metrics_from_groups(cli_match($catalog['metric_grps'],$pattern)) as $values) $filter=array_merge($filter,$values);
            } elseif ($type==='metrics' && $key==='clusters') {
                foreach ($data->get_metrics_from_clusters(cli_match($catalog['clusters'],$pattern)) as $values) $filter=array_merge($filter,$values);
            } elseif ($type==='metrics' && $key==='servers') {
                foreach ($data->get_metrics_from_servers(cli_match($catalog['servers'],$pattern)) as $values) $filter=array_merge($filter,$values['non_indexed_metrics'],array_keys($values['indexed_metrics']));
            } elseif ($type==='metric_grps' && $key==='clusters') {
                foreach ($data->get_metrics_group_from_clusters(cli_match($catalog['clusters'],$pattern)) as $values) $filter=array_merge($filter,$values);
            } elseif ($key==='name') $filter=cli_match($catalog[$type],$pattern);
            else cli_error('Unsupported filter ' . $key . ' for ' . $type . '.');
            $items=array_values(array_intersect($items,$filter));
        }
    }
    $items=array_values(array_unique($items)); sort($items,SORT_NATURAL);
    return ['type'=>$type,'items'=>$items];
}
function cli_group(string &$text): string {
    $text=ltrim($text);
    if (!str_starts_with($text,'(')) cli_error('Enclose each graph selector in parentheses.');
    $depth=0; $quote=null; $escaped=false;
    for ($i=0; $i<strlen($text); $i++) {
        $char=$text[$i];
        if ($escaped) { $escaped=false; continue; }
        if ($char==='\\') { $escaped=true; continue; }
        if ($quote!==null) { if ($char===$quote) $quote=null; continue; }
        if ($char==='"' || $char==="'") { $quote=$char; continue; }
        if ($char==='(') $depth++;
        elseif ($char===')' && --$depth===0) {
            $group=substr($text,1,$i-1); $text=trim(substr($text,$i+1)); return $group;
        }
    }
    cli_error('Unclosed parenthesis or quote.');
}
function cli_graph(string $arguments): array {
    $servers=cli_select(cli_group($arguments));
    $metrics=cli_select(cli_group($arguments));
    if ($servers['type']!=='servers' || $metrics['type']!=='metrics') cli_error('A graph needs a server selector followed by a metric selector.');
    if (!$servers['items'] || !$metrics['items']) cli_error('No servers or metrics match this selection.');
    $options=$arguments==='' ? [] : preg_split('/\s+/',trim($arguments));
    if (count($options)>4) cli_error('Graph options are: duration style size split.');
    [$range,$style,$size,$split]=array_replace(['hour','LINE1','small','yes'],$options);
    $style=strtoupper($style); $range=strtolower($range); $size=strtolower($size); $split=strtolower($split);
    if (!in_array($range,['hour','day','week','month','year'],true) || !in_array($style,['LINE1','AREA','STACK'],true) || !in_array($size,['small','medium','large'],true) || !in_array($split,['yes','no'],true)) cli_error('Options: hour/day/week/month/year; LINE1/AREA/STACK; small/medium/large; yes/no.');
    $data=metadata(); $numeric=[];
    foreach ($metrics['items'] as $metric) foreach ($servers['items'] as $server) {
        if (series_path($data->get_cluster_from_servername($server),$server,$metric)) { $numeric[]=$metric; break; }
    }
    if (!$numeric) cli_error('The matched metrics have no numeric RRD history.');
    $base=['cluster'=>'All','graph_interval'=>$range,'graph_style'=>$style,'graph_size'=>$size];
    $graphs=[];
    if ($split==='yes') {
        if (count($servers['items'])>16 || count($numeric)>64) cli_error('Limit the selection to 16 servers and 64 graphs.');
        foreach ($numeric as $metric) $graphs[]=['title'=>$metric,'url'=>'graphs.php?' . http_build_query($base+['servers'=>implode(',',$servers['items']),'metrics'=>$metric])];
    } else {
        if (count($numeric)>16 || count($servers['items'])>64) cli_error('Combined graphs support up to 16 metrics per server; narrow the metric filter or use split=yes.');
        foreach ($servers['items'] as $server) $graphs[]=['title'=>$server,'url'=>'graphs.php?' . http_build_query($base+['servers'=>$server,'metrics'=>implode(',',$numeric)])];
    }
    $html='<p>' . count($graphs) . ' graph(s) · ' . h(implode(', ',$servers['items'])) . ' · ' . h($range) . ' · ' . h(config('timezone')) . '</p><div class="graph-grid">';
    foreach ($graphs as $graph) $html.='<figure><figcaption>' . h($graph['title']) . '</figcaption><a href="' . h($graph['url']) . '" target="_blank" rel="noopener"><img src="' . h($graph['url']) . '" alt="' . h($graph['title']) . ' history"></a></figure>';
    return ['html'=>$html . '</div>', 'graphs'=>count($graphs)];
}
function cli_store(callable $operation): mixed {
    $directory=config('state_dir');
    $lock=fopen($directory . '/views.lock','c');
    if (!$lock || !flock($lock,LOCK_EX)) throw new RuntimeException('Cannot lock saved views.');
    try {
        $path=$directory . '/views.json';
        $store=is_file($path) ? json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR) : ['next_id'=>0,'views'=>[]];
        $before=$store;
        $result=$operation($store);
        if ($store!==$before) {
            $temporary=tempnam($directory,'.views-');
            try {
                if (file_put_contents($temporary,json_encode($store,JSON_THROW_ON_ERROR|JSON_PRETTY_PRINT) . "\n")===false) throw new RuntimeException('Cannot save view.');
                chmod($temporary,0600);
                if (!rename($temporary,$path)) throw new RuntimeException('Cannot replace saved views.');
            } finally { if (is_file($temporary)) unlink($temporary); }
        }
        return $result;
    } finally { flock($lock,LOCK_UN); fclose($lock); }
}
function cli_help(): array {
    return ['html'=>'<h2>Ganglia CLI commands</h2><p>Patterns are case-insensitive regular expressions. Multiple find filters must all match. Graph selections use the original parenthesized syntax.</p><pre>list servers
list metrics ^cpu_
list metric_grps
list clusters
find servers clusters="Production"
find metrics groups="cpu"
find metrics servers="web"
graph (list servers) (list metrics ^cpu_(user|system)$) hour LINE1 small yes
graph (list servers) (find metrics groups="cpu") day AREA medium no
views list
views save name="CPU overview" (graph (list servers) (list metrics ^cpu_user$))
views load 0
views del 0</pre><p><strong>Graph options:</strong> duration, style, size, split (all optional). Durations: hour, day, week, month, year. Styles: LINE1, AREA, STACK. Sizes: small, medium, large. Split yes creates one graph per metric; no combines the metrics for each server. Saved views persist across deployments.</p>'];
}
function cli_run(string $query, bool $allowViews=true): array {
    $query=trim($query);
    if ($query==='' || strlen($query)>2048 || preg_match('/[\x00-\x1f]/',$query)) cli_error('Enter a single command of at most 2048 characters.');
    if ($query==='help') return cli_help();
    if (preg_match('/^(list|find)\s/',$query)) {
        $selection=cli_select($query);
        $html='<h2>' . count($selection['items']) . ' ' . h($selection['type']) . '</h2><ul class="cli-list">';
        foreach ($selection['items'] as $item) $html.='<li><code>' . h($item) . '</code></li>';
        return ['html'=>$html . '</ul>','items'=>$selection['items']];
    }
    if (str_starts_with($query,'graph ')) return cli_graph(substr($query,6));
    if (!str_starts_with($query,'views ') || !$allowViews) cli_error('Unknown command. Use help, list, find, graph, or views.');
    $arguments=trim(substr($query,6));
    if ($arguments==='list') return cli_store(function (&$store) {
        $html='<h2>Saved views</h2>';
        if (!$store['views']) return ['html'=>$html . '<p>No saved views yet. Use views save name="CPU" (graph (list servers) (list metrics ^cpu_user$)).</p>'];
        $html.='<div class="table-wrap"><table><thead><tr><th>ID</th><th>Name</th><th>Command</th></tr></thead><tbody>';
        foreach ($store['views'] as $id=>$view) $html.='<tr><td>' . h($id) . '</td><td>' . h($view['name']) . '</td><td><code>' . h($view['command']) . '</code></td></tr>';
        return ['html'=>$html . '</tbody></table></div>'];
    });
    if (preg_match('/^save\s+(.+?)\s+(\(.*\))$/s',$arguments,$parts)) {
        $options=cli_options($parts[1]);
        if (array_keys($options)!==['name'] || trim($options['name'])==='' || strlen($options['name'])>80) cli_error('Provide one view name of 1–80 characters.');
        $remaining=$parts[2]; $command=cli_group($remaining);
        if ($remaining!=='' || !preg_match('/^(graph|list|find)\s/',$command)) cli_error('Saved views may contain one graph, list, or find command.');
        $result=cli_run($command,false);
        $id=cli_store(function (&$store) use ($options,$command) {
            if (count($store['views'])>=100) cli_error('The limit is 100 saved views. Delete an unused view first.');
            foreach ($store['views'] as $view) if ($view['name']===$options['name']) cli_error('A view with this name already exists.');
            $id=$store['next_id']++; $store['views'][$id]=['name'=>$options['name'],'command'=>$command]; return $id;
        });
        return ['html'=>'<p>Saved view ' . $id . ': ' . h($options['name']) . '.</p>' . $result['html'],'saved_id'=>$id];
    }
    if (preg_match('/^(load|del)\s+(\d{1,9})$/',$arguments,$parts)) {
        $id=(int)$parts[2];
        $view=cli_store(function (&$store) use ($parts,$id) {
            if (!isset($store['views'][$id])) cli_error('Unknown saved view ID. Run views list.');
            $view=$store['views'][$id]; if ($parts[1]==='del') unset($store['views'][$id]); return $view;
        });
        return $parts[1]==='load' ? cli_run($view['command'],false) : ['html'=>'<p>Deleted view ' . $id . ': ' . h($view['name']) . '.</p>'];
    }
    cli_error('Use views list, views save name="Name" (command), views load ID, or views del ID.');
}
