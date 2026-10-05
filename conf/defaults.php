<?php
return [
    'site_name' => 'Ganglia Simple Web',
    'timezone' => 'UTC',
    'gmetad_host' => '127.0.0.1',
    'gmetad_port' => 8651,
    'rrd_root' => '/var/lib/ganglia/rrds',
    'rrdtool' => '/usr/bin/rrdtool',
    'state_dir' => '/var/lib/ganglia2-web',
    // Configure the exact external origin before using the CLI from a browser.
    // Empty deliberately rejects all POSTs with an Origin header.
    'public_origin' => '',
    'default_cluster' => '',
    'classic_url' => '',
    'classic_application_url' => '',
    'application_cluster' => 'All',
    'application_server' => 'All',
    'application_catalog' => dirname(__DIR__) . '/application-metrics.json',
    'application_sections' => require __DIR__ . '/application-sections.php',
    'application_description' => 'Application metrics published to Ganglia',
    'application_notes' => 'The included example catalog uses rolling 60-second request windows. Page events are not unique people; unexpired sessions are not currently online users. Adapt these definitions to your collector.',
];
