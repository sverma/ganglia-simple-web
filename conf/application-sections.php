<?php
return [
    'Application health'=>['app_up','app_metrics_up','app_database_up','app_collector_up'],
    'Traffic and response time'=>['app_http_requests_per_min','app_http_response_avg_ms','app_http_response_p95_ms','app_http_5xx_per_min','app_http_4xx_per_min','app_login_failures_per_min'],
    'Node.js process'=>['app_process_rss_bytes','app_process_heap_used_bytes','app_process_cpu_percent','app_event_loop_delay_p95_ms'],
    'Community activity'=>['app_members_total','app_registrations_24h','app_introductions_pending','app_connections_accepted_24h','app_messages_24h','app_authenticated_sessions'],
    'Page activity'=>['app_page_discover_per_min','app_page_profile_per_min','app_visits_24h'],
];
