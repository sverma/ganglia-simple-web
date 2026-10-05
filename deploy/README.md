# Nginx and PHP-FPM deployment

These examples target Ubuntu 22.04 with PHP 8.1 and an existing local Ganglia
installation. Adapt service names for other distributions. They do not install
or restart gmond/gmetad and do not modify RRD data. Back up existing web-server
configuration and saved views before changing an installation.

1. Install `nginx`, `php8.1-fpm`, `php8.1-xml`, `rrdtool`, and `apache2-utils`
   (for `htpasswd`, not the Apache server). Confirm gmetad serves XML on
   `127.0.0.1:8651` and that RRDs use Ganglia's `sum` data source.
2. Create a dedicated system account and private state. Give it only read
   access to Ganglia's history, normally through the `ganglia` group:

   ```sh
   sudo useradd --system --home /var/lib/ganglia2-web --shell /usr/sbin/nologin ganglia2-web
   sudo usermod -aG ganglia ganglia2-web
   sudo install -d -o ganglia2-web -g ganglia2-web -m 0700 /var/lib/ganglia2-web /var/lib/ganglia2-web/sessions
   sudo install -d -m 0755 /opt/ganglia-simple-web/releases /var/www/ganglia-monitoring
   sudo install -d -o root -g ganglia2-web -m 0750 /etc/ganglia-simple-web
   ```

3. Build a new release with `python3 tools/build.py /tmp/ganglia-release`.
   Inspect it, then copy it to a unique directory under
   `/opt/ganglia-simple-web/releases/`. Keep code root-owned and read-only to
   the PHP account. Use a symlink at `/var/www/ganglia-monitoring/ganglia2` to
   point to the release. Record the previous target for rollback.
4. Copy `conf/local.php.example` to `/etc/ganglia-simple-web/config.php`,
   owned `root:ganglia2-web`, mode `0640`. Set `public_origin` to the exact
   external HTTPS origin (no trailing slash). Set timezone, cluster/host
   defaults, and optional classic/dashboard URLs. The pool example reads
   this external file through `GANGLIA_WEB_CONFIG`, so it survives releases.
5. Install `php-fpm.conf` as `/etc/php/8.1/fpm/pool.d/ganglia2.conf`.
   Keep the private session directory and secure cookie settings. The pool
   permits `proc_open` for shell-free RRDTool, but disables shell helpers.
   Three on-demand workers, memory and request limits bound resource use.
6. Use `htpasswd /etc/nginx/ganglia.htpasswd YOUR_USERNAME` to add an account
   to an existing password file, or `htpasswd -c` **only when creating a new
   file**. Keep it root-owned, readable by the Nginx group, mode `0640`.
   Credentials are managed on the server and must never be committed.
7. Include `nginx-location.conf` inside your existing TLS server block. Its
   example web root is `/var/www/ganglia-monitoring`; its mount is `/ganglia2/`.
   For a different mount, change all Nginx prefixes and the PHP cookie path
   together. All endpoints, API requests, and static assets require Basic
   authentication. Direct source/config access is denied. Use HTTPS only.
8. Validate **before** reloading:

   ```sh
   sudo php-fpm8.1 -t
   sudo nginx -t
   sudo systemctl reload php8.1-fpm nginx
   sudo systemctl enable php8.1-fpm nginx
   ```

Verify unauthenticated requests to `/ganglia2/`, `/ganglia2/cli.php`, the API,
and static assets return 401. Verify authenticated requests to
`/ganglia2/lib/bootstrap.php`, `/ganglia2/conf/local.php`, and
`/ganglia2/application-metrics.json` return 404. Confirm all five panels,
actual PNG graphs, CLI commands, and saved-view persistence work. Also verify
the PHP account cannot write the RRD directory or access other applications'
private files. Basic auth and filesystem restrictions are web-server
responsibilities; PHP's development server is not suitable for public use.

For an update, build into a new release directory, run the tests and PHP lint,
back up `/var/lib/ganglia2-web/views.json` if present, then atomically replace
the symlink and reload PHP-FPM. If verification fails, restore the previous
symlink and any configuration backups, validate, and reload. Do not roll back
or overwrite RRDs or unrelated application databases.

The Application panel uses `application-metrics.json` for labels and
`application_sections` for grouping. The bundled catalog describes an example
Node.js/community application, with 23 metrics selected for the dashboard;
other collectors can replace both settings. Publish metric values through
your existing gmond/gmetric pipeline. No application source code, credentials,
private user data, or application-specific collector is needed by this UI.
