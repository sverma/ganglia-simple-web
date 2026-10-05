<?php
require_once dirname(__DIR__) . '/lib/bootstrap.php';
$data = metadata();
$list = choice('list', ['clusters','servers','metrics','metrics_grp'], 'clusters');
$result = null;
switch ($list) {
    case 'clusters':
        if (isset($_GET['grid'])) {
            if (!in_array($_GET['grid'], $data->get_grid_name(), true)) fail_request('Unknown grid.');
            $result = $data->get_clusters_from_grid($_GET['grid']);
        } else $result = $data->get_all_clusters();
        break;
    case 'servers':
        if (isset($_GET['clusters'])) $result = $data->get_servers_from_clusters(selected_values('clusters', $data->get_all_clusters()));
        elseif (isset($_GET['metrics'])) $result = $data->get_servers_from_metrics(selected_values('metrics', $data->get_all_metrics()));
        else $result = ['All'=>$data->get_all_servers()];
        break;
    case 'metrics':
        if (isset($_GET['servers'])) $result = $data->get_metrics_from_servers(selected_values('servers', $data->get_all_servers()));
        elseif (isset($_GET['metrics_grp'])) $result = $data->get_metrics_from_groups(selected_values('metrics_grp', $data->get_all_metrics_group()));
        elseif (isset($_GET['clusters'])) $result = $data->get_metrics_from_clusters(selected_values('clusters', $data->get_all_clusters()));
        else $result = ['All'=>$data->get_all_metrics()];
        break;
    case 'metrics_grp':
        if (isset($_GET['clusters'])) $result = $data->get_metrics_group_from_clusters(selected_values('clusters', $data->get_all_clusters()));
        else $result = ['All'=>$data->get_all_metrics_group()];
        break;
}
if (choice('method', ['json','xml'], 'json') === 'xml') {
    $writer = new XMLWriter();
    $writer->openMemory(); $writer->startDocument('1.0','UTF-8'); $writer->startElement('response');
    $encode = function ($items) use (&$encode, $writer): void {
        foreach ($items as $key => $value) {
            $writer->startElement('item'); $writer->writeAttribute('key', (string)$key);
            if (is_array($value)) $encode($value); else $writer->text((string)$value);
            $writer->endElement();
        }
    };
    $encode($result); $writer->endElement(); $writer->endDocument();
    header('Content-Type: application/xml; charset=utf-8'); echo $writer->outputMemory();
} else {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($result, JSON_THROW_ON_ERROR);
}
