# Backend API Environment Variables Checklist

Use this to verify your `.env` has all required values for Google & Apple auth.

## Required for Apple Sign In

| Variable | Description | Example |
|----------|-------------|---------|
| `APPLE_BUNDLE_ID` | iOS app bundle identifier (matches identity token `aud`) | `com.matchgrinder.mobile` |
| `APPLE_CLIENT_ID` | Optional: Services ID if using web flow | Your Services ID or leave blank |
| `APPLE_CLIENT_SECRET` | Optional: Only needed for server-to-server token exchange | (usually not needed for native) |
| `APPLE_REDIRECT_URI` | Optional: For web OAuth flow | (usually not needed for native) |

**Minimum for native iOS:** `APPLE_BUNDLE_ID=com.matchgrinder.mobile` (or rely on default in `config/services.php`)

## Required for Google Sign In

| Variable | Description | Example |
|----------|-------------|---------|
| `GOOGLE_CLIENT_ID` | Web client ID (or primary mobile client ID for token verification) | `295547045070-gkkmh48hvu0pca0blvoj3h3oo0uegada.apps.googleusercontent.com` |

For **native mobile** ID tokens, the `aud` claim can be:
- iOS: `295547045070-eri2ej3cbk57spjtnpu04hr6iqcifqj6.apps.googleusercontent.com`
- Web: `295547045070-gkkmh48hvu0pca0blvoj3h3oo0uegada.apps.googleusercontent.com`

Use the client ID that matches your primary mobile flow. If both iOS and Web, you may need to extend the adapter to accept multiple IDs.

## Optional

| Variable | Description |
|----------|-------------|
| `APP_ACCEPT_DEMO_AUTH_TOKENS` | Set to `true` to accept demo tokens outside local (testing only) |
| `GOOGLE_CLIENT_SECRET` | For server-side OAuth code exchange |
| `GOOGLE_REDIRECT_URI` | For web OAuth flow |

## Quick check

Run in the API directory:
```bash
php artisan tinker
>>> config('services.apple.bundle_id')
=> "com.matchgrinder.mobile"
>>> config('services.google.client_id')
=> "YOUR_VALUE_OR_NULL"
```

If `GOOGLE_CLIENT_ID` is null, add it to `.env`.
