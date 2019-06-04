# Proxy IP Manager concrete5 package

This package lets you handle the list of trusted IP addresses of the proxy used by your concrete5 website.


## Extensible

This package is easily extensible to add custom IP address providers.

Currently, the following providers are available:

- `Manual` provider (included in this package): lets you manually specify a list of IP addresses
- [`CNAME` provider](https://github.com/mlocati/cname_proxy_ip_provider): lets you specify CNAMEs / domain names, and the package will resolve them to the associated IP addresses
- [`CloudFlare` provider](https://github.com/mlocati/cloudflare_proxy_ip_provider): fetches the list of IP addresses directly from CloudFlare


## Features

In order to update the list of trusted IP addresses, you have 3 options:

1. manually, via a dashboard page
2. via the `pim:update` CLI command (which can be scheduled for execution for example with cron)
3. during the normal web execution, with a time interval configurable in the dashboard page (in case you don't have access to CLI commands)


## Screenshots

![Dashboard page](https://raw.githubusercontent.com/mlocati/proxy_ip_manager/blob/images/dashboard-page.png)
