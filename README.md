# xbar-envcan-weather
Environment Canada weather for xbar

For use with the xbar app - https://xbarapp.com/

## Features

- Current conditions and multi-day forecast from Environment Canada
- Warnings and watches displayed prominently when in effect
- English and French language support
- Choice of colour, plain, or no weather icons
- Automatic daily update notifications when a new version is available

## Steps to install

1. Install the xbar app
2. Install this plugin in the xbar app's plugin folder
3. Make sure PHP is installed. If you use Homebrew on Apple Silicon, create a symlink:
   ```bash
   sudo ln -s /opt/homebrew/bin/php /usr/local/bin/php
   ```
4. Make the file executable.
5. On the settings page, you'll be prompted for your location coordinates (latitude,longitude).
6. Also on the settings page, set your preferences for weather icons (colour, plain, or none), and language (English or French).

## Finding your coordinates

Environment Canada now uses coordinates (latitude,longitude) instead of the old region codes.

### Option 1: Use the Environment Canada website (Recommended)

1. Go to [weather.gc.ca](https://weather.gc.ca)
2. Search for your city
3. Look at the URL in your browser - it will contain `coords=LAT,LON`
   - Example: `https://weather.gc.ca/en/location/index.html?coords=43.643,-79.394`
4. Enter the coordinates in xbar settings as `43.643,-79.394`

**Important:** Use the exact coordinates from the Environment Canada URL. They must match a location in their system - rounding or approximating coordinates may result in no weather data being available.

### Option 2: Use Google Maps

1. Go to [Google Maps](https://maps.google.com)
2. Right-click on your location
3. Click the coordinates at the top of the menu to copy them
4. Enter them in xbar settings (format: `latitude,longitude`)

### Example coordinates for major cities

| City | Coordinates |
|------|-------------|
| Toronto, ON | 43.643,-79.394 |
| Vancouver, BC | 49.246,-123.116 |
| Montreal, QC | 45.508,-73.588 |
| Calgary, AB | 51.049,-114.066 |
| Edmonton, AB | 53.536,-113.498 |
| Ottawa, ON | 45.425,-75.690 |
| Winnipeg, MB | 49.884,-97.133 |
| Halifax, NS | 44.649,-63.575 |
| Whitehorse, YT | 60.721,-135.057 |
| Dawson City, YT | 64.062,-139.431 |
| Yellowknife, NT | 62.454,-114.352 |

## Changelog

### v2.3 (July 2026)
- Added retry logic for transient Environment Canada feed failures (HTTP 5xx), including `502 Proxy Error`
- Added a clean xbar fallback message with a manual `Tap to retry` refresh action when fetch attempts fail
- Fixed PHP 8.4 deprecation warnings by using `http_get_last_response_headers()` when available (with fallback for older PHP versions)

### v2.2 (June 2026)
- Fixed link extraction from RSS feed (assignment vs. comparison bug)
- Fixed undefined variables when an unrecognised language setting is used
- Added numeric validation for coordinates to prevent malformed URLs
- Added coordinate range validation (latitude -90 to 90, longitude -180 to 180)
- Strip pipe characters from feed links to prevent xbar directive injection
- Fixed forecast header displaying literal `\n` instead of a newline
- Added 10s timeout to main weather fetch to prevent indefinite blocking
- Fixed `str_contains` polyfill to match PHP 8 behaviour for empty needle
- Removed `@` error suppression from network calls
- Fixed `str_starts_with` polyfill to match PHP 8 behaviour for empty needle
- Fixed crash when vars file contains malformed JSON
- Fixed fatal error when Environment Canada returns malformed XML

### v2.1 (January 2026)
- Added automatic update notifications (checks once per day)

### v2.0 (January 2026)
- **Breaking change**: Switched from region codes to coordinates
  - Environment Canada changed their feed system in June 2025
  - Old region codes (like `ON-143` or `YT-6`) no longer work
  - You must now use latitude,longitude coordinates (e.g., `43.643,-79.394`)
- Updated feed URL to use the new coordinate-based format
- Updated fallback link to point to coordinate-based weather page

### v1.0
- Initial release
- Used Environment Canada region codes for location selection
