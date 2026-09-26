# TURN / coturn

TURN is not installed or configured by this project. Video chat uses STUN (`WEBRTC_STUN_URLS`) until you set a TURN server. Direct peer connections work on many networks without TURN. Symmetric NAT and strict firewalls need a relay.

Do not put real credentials in git. Set them only in the server `.env`:

```
WEBRTC_TURN_URL=turn:turn.example.com:3478?transport=udp
WEBRTC_TURN_USERNAME=
WEBRTC_TURN_CREDENTIAL=
```

You can add more STUN or TURN URLs as a comma-separated `WEBRTC_STUN_URLS` list. The TURN URL is included only when `WEBRTC_TURN_URL` is not empty. The credential is not sent to the browser unless that URL is set.

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

coturn `use-auth-secret` expects a username of `expiry:user` and a credential of `base64(hmac-sha1(secret, username))`. This application currently sends the static `WEBRTC_TURN_USERNAME` and `WEBRTC_TURN_CREDENTIAL` from the environment. If you use a long-term coturn user instead of the REST secret, create it with:

```bash
sudo turnadmin -a -u turnuser -p 'REPLACE_WITH_A_PASSWORD' -r talk.dvmsoft.in
```

Then set those values in `.env`. Rotate them if they leak. Logs should stay on the server (`syslog`) and must not be copied into the Laravel log with the secret.

Until `WEBRTC_TURN_URL` is set on the server, TURN is not live.
