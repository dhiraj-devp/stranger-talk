# Google sign-in

The Google Cloud OAuth client is created in Google Cloud Console. This repository does not create or configure that client.

## Production

Authorized JavaScript origin:

```
https://talk.dvmsoft.in
```

Authorized redirect URI:

```
https://talk.dvmsoft.in/auth/google/callback
```

`.env`:

```
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=https://talk.dvmsoft.in/auth/google/callback
```

## Local

Authorized JavaScript origin:

```
http://127.0.0.1:8010
```

Authorized redirect URI:

```
http://127.0.0.1:8010/auth/google/callback
```

`.env`:

```
GOOGLE_REDIRECT_URI=http://127.0.0.1:8010/auth/google/callback
```

Leave the client id and secret empty until the Cloud client exists. The login page still offers email and password. The Google button explains that sign-in is not configured yet.

Scopes requested by the app are only `openid`, `profile`, and `email`.

Routes: `GET /auth/google` (`auth.google`) and `GET /auth/google/callback` (`auth.google.callback`).
