# New APNs push cert via mdmcert.download

Your **private key is already on this Mac** (do not lose it):

```text
~/nanomdm-lab/certs/push.key
~/nanomdm-lab/certs/push.csr
~/nanomdm-lab/certs/mdmcert-server.crt   # encryption cert for mdmcert email
~/nanomdm-lab/certs/mdmcert-server.key
```

---

## Step 1 — Log in to mdmcert.download

1. Open: https://mdmcert.download  
2. Sign in with the email you already registered.  
3. If needed, verify email from the link they sent.  
4. Instructions page: https://mdmcert.download/instructions  
5. About / eligibility: https://mdmcert.download/about  

You must be signed in before tools can submit a CSR for your account.

---

## Step 2 — Keep your local private key (already done)

We generated:

| File | Purpose |
|------|---------|
| `push.key` | **Keep forever** — NanoMDM needs this |
| `push.csr` | Signing request (public) |

You will **not** get `push.key` from Apple or mdmcert email — only from this folder.

---

## Step 3 — Submit CSR with `mdmctl` (installed)

`mdmctl` is ready at:

```text
~/nanomdm-lab/bin/mdmctl
```

Wiki: https://github.com/micromdm/micromdm/wiki/mdmcert.download  

**Replace the email** with the one registered on https://mdmcert.download :

```bash
cd ~/nanomdm-lab/certs

~/nanomdm-lab/bin/mdmctl mdmcert.download -new \
  -email=YOUR_MDMCERT_EMAIL@example.com \
  -country=TZ \
  -cn="MDM Platform"
```

This will:
- create `mdmcert.download.push.key` (private key — keep it)
- create PKI exchange files
- upload the request to mdmcert.download
- email you an encrypted signed CSR

If macOS blocks `mdmctl`: right-click → Open once, or `xattr -cr ~/nanomdm-lab/bin/mdmctl`.

---

## Step 4 — Decrypt the email attachment

When mail arrives (`mdm_signed_request.*.plist.b64.p7`):

```bash
cd ~/nanomdm-lab/certs
# example (adjust path + tool flags per mdmctl/certhelper help):
# mdmctl mdmcert.download -decrypt=~/Downloads/mdm_signed_request.XXXX.plist.b64.p7
```

You should get a file like `mdmcert.download.push.req` (or similar).

---

## Step 5 — Upload to Apple Identity

1. Open: https://identity.apple.com  
2. Sign in with your **Apple ID** (org-related if possible).  
3. Under certificates for third-party servers → **Create a Certificate**.  
4. Upload the `.push.req` / signed request from Step 4.  
5. Download the resulting `MDM_ ….pem`.

---

## Step 6 — Save as NanoMDM files

```bash
cp ~/Downloads/MDM_*.pem ~/nanomdm-lab/certs/push.pem
# push.key already exists from Step 2 — do not overwrite unless you generated a matching new key with mdmctl
```

If `mdmctl -new` created its own key (`mdmcert.download.push.key` or `ProviderPrivateKey.key`), use **that** key with the new `.pem` (they must match). Prefer one key pair end-to-end.

---

## Step 7 — Upload into NanoMDM

```bash
# start NanoMDM first if needed
bash /Users/barakael0/MDM/mdm-engine/scripts/upload-push-cert.sh
```

Or:

```bash
cd ~/nanomdm-lab/certs
cat push.pem push.key | curl -T - -u nanomdm:nanomdm http://127.0.0.1:9000/v1/pushcert
```

Save the returned **`topic`**, put it in `~/nanomdm-lab/profiles/enroll.mobileconfig`.

---

## Link checklist

| Step | Link |
|------|------|
| Login / register | https://mdmcert.download |
| Instructions | https://mdmcert.download/instructions |
| MicroMDM releases (`mdmctl`) | https://github.com/micromdm/micromdm/releases |
| mdmcert + MicroMDM wiki | https://github.com/micromdm/micromdm/wiki/mdmcert.download |
| Apple push portal | https://identity.apple.com |

---

## What you do right now

1. Log in: https://mdmcert.download  
2. Tell me the **email** on that account (or confirm you’re logged in).  
3. Download `mdmctl` from the MicroMDM release zip.  
4. Reply **“mdmctl ready”** and we’ll run the exact `-new` / `-decrypt` commands with your email.

**Important:** Do not delete `~/nanomdm-lab/certs/push.key` until you know which key matches the final Apple `.pem`.
