# Plugin Alligo Varnish
Version 2.0RC1

Joomla CMS plugin that allow the CMS make a more agressive cache with Varnish Cache 6

## Specifications

- RFC 7234 Hypertext Transfer Protocol (HTTP/1.1): Caching
  - <https://datatracker.ietf.org/doc/html/rfc7234>

> Note: this plugin was mostly tested with Varnish 6.0
(<https://varnish-cache.org/docs/6.0/index.html>) with some customizations
on default.vcl (to not require Varnish Plus) and HTTPS via NGinx.
But as it implement part of RFC 7234 is likely to be more flexible,
including other caching solutions. Please note that Joomla, like any other
CMS, will use cookies even for non-logged users, but workaround strategies
for this are also more Generic. Is no feasible to create a Joomla plugin to
rewrite such cookies since even client side JavaScript libraries (including
Google Analytics) would invalidate this.

## Other links to see
- [Joomla CMS banners module for Joomla + Varnish](https://github.com/alligo/mod_banners4varnish)
- [Joomla CMS content plugin for Google Analytics Event Tracking to show Article visualizations](https://github.com/alligo/plg_content_google-analytics-event-tracking)
- [Google Analytics Event Tracking - Alligo Helper, JS library](https://github.com/alligo/google-analytics-event-tracking)
