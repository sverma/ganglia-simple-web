<?php
declare(strict_types=1);

// This is trusted administrator configuration, never request input.
function config(string $name): mixed {
    static $settings;
    if ($settings === null) {
        $defaults = require dirname(__DIR__) . '/conf/defaults.php';
        $path = getenv('GANGLIA_WEB_CONFIG') ?: dirname(__DIR__) . '/conf/local.php';
        $overrides = is_file($path) ? require $path : [];
        if (!is_array($overrides) || array_diff_key($overrides, $defaults)) {
            throw new RuntimeException('Invalid Ganglia Web configuration keys.');
        }
        $settings = array_replace($defaults, $overrides);
        if (!in_array($settings['timezone'], DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC), true)) {
            throw new RuntimeException('Invalid monitoring timezone.');
        }
        foreach (['classic_url', 'classic_application_url'] as $key) {
            $url = $settings[$key];
            if ($url !== '' && !preg_match('~^(?:/(?!/)|https?://)~D', $url)) {
                throw new RuntimeException('Classic dashboard links must use HTTP(S) or a local absolute path.');
            }
        }
        if ($settings['public_origin'] !== '' && !preg_match('~^https?://[^/?#]+$~D', $settings['public_origin'])) {
            throw new RuntimeException('public_origin must contain only scheme, hostname, and optional port.');
        }
        if (!is_int($settings['gmetad_port']) || $settings['gmetad_port'] < 1 || $settings['gmetad_port'] > 65535) {
            throw new RuntimeException('Invalid gmetad port.');
        }
    }
    if (!array_key_exists($name, $settings)) throw new InvalidArgumentException('Unknown configuration key.');
    return $settings[$name];
}
