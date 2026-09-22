# APNs push certificate for NanoMDM

NanoMDM cannot talk to iPhones until you upload an **Apple MDM Push Certificate**.

The upload command expects two PEM files in `~/nanomdm-lab/certs/`:

| File | What it is |
|------|------------|
| `push.pem` | Certificate from Apple (`.pem`) |
| `push.key` | Matching private key (unencrypted PEM) |

Then:

```bash
cd ~/nanomdm-lab/certs
cat push.pem push.key | curl -T - -u nanomdm:nanomdm http://127.0.0.1:9000/v1/pushcert
```

Save the returned **`topic`** — you need it in `enroll.mobileconfig`.

---

## Option A — mdmcert.download (testing / small org)

Best for getting started without full Apple vendor setup.

### 1. Register

1. Go to https://mdmcert.download and sign up (use a real org email).
2. Verify your email.

### 2. Generate keys + CSR (terminal)

```bash
mkdir -p ~/nanomdm-lab/certs && cd ~/nanomdm-lab/certs

# Encryption cert for mdmcert email
openssl genrsa -out server.key 2048
openssl req -sha256 -new -key server.key -out server.csr -subj "/CN=mdm.local"
openssl x509 -req -sha256 -days 365 -in server.csr -signkey server.key -out server.crt

# Download certhelper: https://github.com/micromdm/tools/releases
# chmod +x certhelper-darwin-amd64 && mv certhelper-darwin-amd64 certhelper

./certhelper provider -csr -cn=mdm-cert -password=secret -country=US -email=YOUR_EMAIL@yourdomain.com

./certhelper mdmcert.download \
  -cert ./server.crt \
  -csr=ProviderUnsignedPushCertificateRequest.csr \
  -email=YOUR_EMAIL@yourdomain.com
```

Check email for an encrypted attachment (`.plist.b64.p7`).

### 3. Decrypt the email attachment

```bash
./certhelper mdmcert.download \
  -cert ./server.crt \
  -key ./server.key \
  -decode ~/Downloads/mdm_signed_request.*.plist.b64.p7
```

This creates `mdmcert.download_PushCertificateRequest` (or similar).

### 4. Upload to Apple

1. Open https://identity.apple.com (Apple ID tied to your org).
2. **Create a Certificate** → upload the `.push.req` file from step 3.
3. Download the signed certificate (e.g. `MDM_ Your Org_Certificate.pem`).

### 5. Prepare files for NanoMDM

```bash
cp "/path/to/MDM_ Your Org_Certificate.pem" ~/nanomdm-lab/certs/push.pem
cp ProviderPrivateKey.key ~/nanomdm-lab/certs/push.key   # from certhelper step 2
# If key is encrypted, decrypt:
# openssl rsa -in push.key.encrypted -out push.key
```

### 6. Upload to NanoMDM

```bash
bash /Users/barakael0/MDM/mdm-engine/scripts/upload-push-cert.sh
```

---

## Option B — Apple Developer account (production)

1. Enroll in [Apple Developer Program](https://developer.apple.com/programs/) ($99/yr) or Enterprise.
2. Ask Apple Support to enable **MDM CSR** on your account.
3. Follow Apple’s MDM vendor + customer certificate flow in Certificates, Identifiers & Profiles.
4. Export cert + private key as `push.pem` and `push.key`.
5. Run `upload-push-cert.sh`.

---

## After upload

Update `~/nanomdm-lab/profiles/enroll.mobileconfig`:

```xml
<key>Topic</key>
<string>com.apple.mgmt.External.xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx</string>
```

Install the profile on your test iPhone.

---

## Common errors

| Error | Cause |
|-------|--------|
| `push.pem: No such file` | Files not created yet — complete steps above |
| `failed to find any PEM data` | Empty stdin — `cat` found no files |
| `push data missing for id` | Device not enrolled, or no push cert uploaded |
