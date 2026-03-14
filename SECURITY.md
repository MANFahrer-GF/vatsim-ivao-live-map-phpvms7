# Security Policy

## Supported Versions

| Version | Supported |
|---------|-----------|
| 4.6.x   | ✅ Yes     |
| 4.5.x   | ✅ Yes     |
| 4.0.x   | ✅ Yes     |
| 1.0.x   | ❌ No (upgrade to 4.6.0) |
| 2.0.x   | ❌ No (upgrade to 4.6.0) |
| 3.0.x   | ❌ No (upgrade to 4.6.0) |

---

## Reporting a Vulnerability

Please report security issues via GitHub Issues (label: `security`) or directly to the repository owner.

Do **not** open public issues for vulnerabilities that could be actively exploited before a fix is available.

---

## Security Architecture

`live_map.blade.php` is a **client-side read-only template**. It:

- Makes only **GET requests** to public APIs (VATSIM, IVAO, OpenWeatherMap) and the local phpVMS `/api/acars` + `/api/user/bids` endpoints
- **Contains no POST/PUT/DELETE requests** → no CSRF risk
- **Contains no direct database queries** → SQL injection not applicable at template level
- All data displayed in the browser DOM is sanitised before insertion (see v4.0.0 security fixes)

### Sanitisation Functions

```javascript
h(str)           // HTML-escape for all innerHTML output
safeUrl(url)     // HTTPS-only URL validation
safeCallsign(s)  // Whitelist: A–Z, 0–9, _, - (max 20 chars)
safeFreq(s)      // Numeric + dot only (max 8 chars)
```

---

## Server-Side Recommendations

The following are **not** handled by this template and should be configured at the server/framework level:

### Content-Security-Policy Header
Add to your nginx or Apache config, or as Laravel middleware:
```
Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' cdnjs.cloudflare.com; connect-src 'self' data.vatsim.net api.ivao.aero openweathermap.org; img-src 'self' data: openweathermap.org; style-src 'self' 'unsafe-inline';
```

### Rate-Limiting `/api/acars`
The live map polls phpVMS `/api/acars` every few seconds. Add Laravel rate limiting in `RouteServiceProvider` or via a middleware to prevent abuse.

### HTTPS
Ensure your phpVMS instance is served over HTTPS. All external API calls (VATSIM, IVAO, OWM) are already HTTPS-only.
