# Alligo "RFC 7234" for Joomla CMS (aka Varnish-Cache for Joomla! plugin)
**A [Joomla CMS](https://www.joomla.org/) plugin for high traffic
(or SEO concerned) websites compatible with
[RFC 7234](https://datatracker.ietf.org/doc/html/rfc7234>) optimized for
fault-tolerant and very fast responses while requiring 4-10
times cheaper hardware.**

## Quickstart: How to install and make a site faster and reliable

### Joomla plugin
1. Download a version
    1. For example, the latest version from <https://github.com/alligo/joomla_plg_system_alligovarnish/archive/refs/heads/master.zip>.
2. Upload to your Joomla test site, like any other Joomla plugin.
    1. It can be a test version of the real site. A localhost installation
       could be used for basic testing, but full implementation with Varnish
       would require that even your test site is served by Varnish
3. Enable the plugin.
4. Customize each functionality of the plugin
    1. This plugin requires configuration and testing based on how you're
       caching servers operate. This is why it has so many options and is
       inviable create a generic version of varnish default.vcl for everyone.

### The caching server
Since joomla_plg_system_alligovarnish mostly give hints of how content should
be cached, **it requires some frontend caching server** even to maximize use
on Browser caching to remove the cookies when the user is not authenticated.

You must select **one** option.

#### A. Varnish
The cheapest way to do it yourself (or some expert contracted by you using
this plugin) have one or more Varnish servers. Since the community version of
Varnish does not offer HTTPS, our recommendation is serve the HTTPS with NGinx.

```txt
Visitor -> NGinx (HTTPS) -> Varnish -> YourSite
```

> **Protip**: at bare minimum, compared to standard Varnish configuration,
  options remove cookies used by Joomla for non-authenticated users is
  necessary. Without this, both Varnish (or equivalent) will not cache.

Recommended reading:
- Varnish references
  - https://book.varnish-software.com/
  - https://github.com/varnish/Varnish-Book
- Published examples using varnish
  - The [@mattiasgeniar](https://github.com/mattiasgeniar) have some references
    like [varnish-6.0-configuration-templates](https://github.com/mattiasgeniar/varnish-6.0-configuration-templates)

#### B. NGinx (instead of Varnish)
**Short answer**: yes, it is possible.

**Long answer**: While it is possible to use NGinx to replace entirely
Varnish-Cache (not just to serve the HTTPS layer, a role that NGinx is
perfect), the end result would be less than "_4-10 times cheaper hardware_".
Yes, is possible, but not as efficient. One main argument to not recommend this
if is viable use Varnish is that the end result will be harder to debug, in
special when cache invalidation is necessary.

#### C. Cloudflare, Fastly, etc
The joomla_plg_system_alligovarnish is tested against customized Varnish
servers, but since it abstract
[RFC 7234](https://datatracker.ietf.org/doc/html/rfc7234), and try to be as
flexible as possible, it is likely to make easier to use other providers.

Sadly, some, like Cloudflare, will not provide caching for Joomla generated
content (the HTML versions) on the free option like is possible to do with
custom Varnish server.

---

### Specifications

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

### Other links to see
- [Varnish-Cache purge via Joomla administrative panel](https://github.com/alligo/joomla_mod_varnish_purge)
  - Protip: configure the Varnish servers to allow purge/ban from all IPs of
    your Joomla installations and install this plugin to let end users clean
    caches.
- [Joomla CMS banners module for Joomla + Varnish](https://github.com/alligo/mod_banners4varnish)
- [Joomla CMS content plugin for Google Analytics Event Tracking to show Article visualizations](https://github.com/alligo/plg_content_google-analytics-event-tracking)
- [Google Analytics Event Tracking - Alligo Helper, JS library](https://github.com/alligo/google-analytics-event-tracking)


# License

[![Public Domain Dedication](https://licensebuttons.net/p/zero/1.0/88x31.png)](UNLICENSE)

The [Alligo](https://github.com/alligo) has dedicated the work to the
[public domain](UNLICENSE) by waiving all of their rights to the work worldwide
under copyright law, including all related and neighboring rights, to the extent
allowed by law. You can copy, modify, distribute and perform the work, even for
commercial purposes, all without asking permission.