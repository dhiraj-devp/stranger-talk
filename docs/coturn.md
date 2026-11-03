# TURN

Direct peer connections work on many networks with STUN alone. Symmetric NAT and strict firewalls need a relay. The video call asks `GET /api/webrtc/ice-servers` for a short-lived Cloudflare TURN credential. The API token stays on the server.

In the Cloudflare dashboard, open **Realtime → TURN**, create a TURN key, and create an API token that can use that key. Put only these placeholders' real values in the server `.env`. Do not commit them:

```
CLOUDFLARE_TURN_KEY_ID=
CLOUDFLARE_TURN_API_TOKEN=
```

Restart PHP after changing `.env`. Each signed-in video session requests credentials with a 24-hour lifetime. The browser receives the temporary username, password, and relay URLs. It never receives the API token.

`WEBRTC_STUN_URLS` is still the STUN list on the video page. Static `WEBRTC_TURN_URL`, `WEBRTC_TURN_USERNAME`, and `WEBRTC_TURN_CREDENTIAL` are not used.

## Self-hosted coturn

coturn below is not wired to the video page. The live relay is Cloudflare.

## Install (Ubuntu)

```bash
sudo apt-get update
sudo apt-get install -y coturn
```

`/etc/turnserver.conf` example. Replace the realm, secret, and ports with your own:

```
listening-port=3478
tls-listening-port=5349
fingerprint
use-auth-secret
static-auth-secret=REPLACE_WITH_A_LONG_RANDOM_SECRET
realm=talk.dvmsoft.in
total-quota=100
stale-nonce=600
no-multicast-peers
no-cli
min-port=49152
max-port=65535
syslog
```

Enable the daemon in `/etc/default/coturn`:

```
TURNSERVER_ENABLED=1
```

```bash
sudo systemctl enable --now coturn
```

## Firewall

```bash
sudo ufw allow 3478/tcp
sudo ufw allow 3478/udp
sudo ufw allow 5349/tcp
sudo ufw allow 49152:65535/udp
```

- UDP 3478 is the normal relay path.
- TCP 3478 is the fallback when UDP is blocked.
- TLS 5349 is optional and needs a certificate, for example `cert=/etc/letsencrypt/live/talk.dvmsoft.in/fullchain.pem` and `pkey=.../privkey.pem` in `turnserver.conf`.
- The relay range must be open to the internet or clients cannot receive media.

## Time-limited credentials

coturn `use-auth-secret` expects a username of `expiry:user` and a credential of `base64(hmac-sha1(secret, username))`. This application does not send coturn credentials. Video relay credentials come from Cloudflare, as described above. Logs must not include the Cloudflare API token or the generated username and password.
