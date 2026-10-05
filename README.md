# Ganglia Simple Web

A PHP 8.1+ interface for existing Ganglia metadata and RRD history, with local
browser assets and responsive graph controls. It requires gmetad, RRDTool,
PHP SimpleXML/XMLWriter, and a web server that authenticates **every** endpoint.
There is no built-in account database or metrics collector.

## Panels

- **Graphs:** cluster/server/group/metric selection, hour through year ranges,
  three sizes, line/area/stacked styles, detachable panels, and optional
  minimum/maximum/percentile overlays. Visible pages can refresh every minute.
- **Application:** configurable sections and targets, plus an example catalog
  of 66 `app_*` metrics. Missing metrics display a waiting message. Your own
  collector must publish these metrics to Ganglia; the UI does not create them.
- **Alerts:** the original placeholder, clearly labeled. No alert engine,
  rules, or notifications are implemented.
- **Metric Details:** current Ganglia metadata and descriptions.
- **CLI:** bounded monitoring commands, graphs, and persistent shared views.

Summary overlays apply to stored RRD interval averages. Historical percentile
graphs average previously computed window percentiles when RRD consolidates
samples; they are not the percentile of all original requests over that period.
History is available only from the start of collection.

## Installation

See [deployment instructions](deploy/README.md) for an authenticated Nginx /
PHP-FPM installation under `/ganglia2/`, isolated filesystem permissions,
validation, release switching, and rollback. Build a clean release with:

```sh
python3 tools/build.py /tmp/ganglia-simple-web-release
```

Set the exact external `public_origin`, timezone, gmetad endpoint, RRD root,
and dashboard targets in trusted local configuration. Start with
[`conf/local.php.example`](conf/local.php.example). All configuration defaults
are in [`conf/defaults.php`](conf/defaults.php). Keep writable state and
credentials outside the served directory. Application sections and catalog
can be replaced without editing PHP panel code.

## CLI

The CLI is a monitoring language, **not an operating-system shell**:

```text
list servers
list metrics ^cpu_
list metric_grps
list clusters
find servers clusters="Production"
find metrics groups="cpu"
find metrics servers="web"
graph (list servers) (list metrics ^cpu_(user|system)$) hour LINE1 small yes
graph (list servers) (find metrics groups="cpu") day AREA medium no
views save name="CPU overview" (graph (list servers) (list metrics ^cpu_user$))
views list
views load 0
views del 0
```

Multiple `find` filters intersect. Regex filters have bounded length and
execution limits. `graph` accepts duration, style, size, and split options:
`yes` gives one graph per metric; `no` combines metrics for each server.
Each graph permits at most 16 series; each CLI command permits at most 64
graphs. String metadata without RRD history is omitted.

Views are shared by authenticated monitoring users. Commands use POST with
a session CSRF token and exact Origin validation when the header is supplied.
Saved views use stable IDs, an exclusive lock, atomic replacement, private
file permissions, and a 100-view limit. The deployment templates enforce
Secure, HttpOnly, SameSite=Strict session cookies.

## API and graph safety

`api/webservice.php` accepts read-only GET requests. Examples:

```text
?list=clusters
?list=servers&clusters=Production
?list=servers&metrics=cpu_user
?list=metrics&servers=web-01
?list=metrics&clusters=Production
?list=metrics&metrics_grp=cpu
?list=metrics_grp&clusters=Production
?list=clusters&method=xml
```

JSON is the default; XML is available via `method=xml`. Names are checked
against Ganglia metadata. Graph options are allowlisted, and resolved RRD
paths must remain beneath the configured root. RRDTool receives an argument
array through `proc_open`, bypassing shell parsing. gmetad reads have time and
size limits; XML network loading is disabled. Production web-server rules
deny direct access to configuration, libraries, templates, and unlisted files.

## Development and verification

```sh
find . -name '*.php' -not -path './conf/local.php' -exec php -l {} \;
python3 tests/integration.py
```

The integration suite creates temporary synthetic Ganglia XML and real RRD
files, starts loopback-only fixture/PHP servers, and checks API filtering,
real PNG graphs, validation, CSRF, saved-view persistence, path confinement,
and all panels. It never contacts a production service. Requirements: PHP
8.1+ with SimpleXML/XMLWriter, RRDTool, and Python 3.9+.

Browser checks use Playwright and the same fixture:

```sh
npm install --no-save --package-lock=false playwright
npx playwright install chromium
python3 tests/integration.py --browser
```

`PHP_BINARY`, `RRDTOOL_BINARY`, `NODE_BINARY`, and `PLAYWRIGHT_MODULE` may
override local executables/module paths. CI runs PHP syntax and fixture
integration checks on PHP 8.1 and 8.3, plus Chromium checks on PHP 8.3.

## Migration from the legacy interface

This is a substantial modernization of the original interface and CLI, not
just configuration changes. It retains the original XML parser with bounded
I/O and PHP compatibility fixes, while replacing the rendering, API, graph
execution, and browser controls. Obsolete third-party JS/template libraries,
database login endpoints, shell-based graph handlers, and unused assets are
removed. The web server now supplies authentication.

Existing RRD history is read in place and must remain read-only. The old
`views.txt` is not automatically imported; recreate those commands using
`views save`. Indexed metric expansion and aggregating more than 16 series
are not supported by this graph adapter. The original project notes remain
in [docs/legacy-README.txt](docs/legacy-README.txt); the old implementation
remains in Git history.
