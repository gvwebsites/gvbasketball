# GV Basketball Wiki — Access & Hosting

Information regarding hosting, server access, backups, and network/email routing.

---

## Hosting Environment

- **Host Provider:** Hostinger Premium (Shared hosting)
- **WordPress Version:** 6.8.5
- **PHP Version:** 8.2
- **WP-CLI Version:** 2.12 (available globally on host)
- **Active Theme:** Astra 4.13
- **Page Builder:** Elementor Pro 3.30 (Theme Builder controls global header/footer templates)

---

## Server Access (SSH)

SSH is pre-configured on the user's system via an SSH alias `gvweb`:

```bash
ssh gvweb
```

### Connection Details
- **User:** `u907133977`
- **Host:** SSH alias resolves hostname, port, and identity keys.
- **WP Public Root:** `/home/u907133977/domains/gvbasketball.com/public_html`
- **WordPress Public URL:** `https://gvbasketball.com`

> [!NOTE]
> SSH connection emits a harmless "post-quantum" key exchange warning. You can filter it out of your terminal outputs by appending: `2>&1 | grep -v "post-quantum\|store now\|upgraded. See\|vulnerable"`.

---

## Backups

### Original Snapshot
- **Location on Server:** `~/backups/gvbasketball-20260627-015018/`
- **Contents:** Full WordPress snapshot (`db.sql` + `wp-content.tar.gz`) captured prior to any custom development.

### Database Snapshot Gotcha
- The `wp db export` CLI command **fails** on this Hostinger environment due to system constraints.
- To dump/snapshot specific database tables, query them directly and output to file:
  ```bash
  wp db query "SELECT * FROM wp_options WHERE option_name = 'elementor_active_kit';" > backup.tsv
  ```
- Or backup specific tables by targeting mysql dumps directly if permissions allow, or use direct snapshots.

---

## Network & Email Routing

### DNS & Cloudflare
- **Nameservers:** Cloudflare (`norman.ns.cloudflare.com` / `carol.ns.cloudflare.com`)
- **A Record:** Points to Hostinger origin IP `37.44.245.74` (Cloudflare Proxy enabled)
- **SSL Status:** Strict
- **Rocket Loader:** OFF (Rocket Loader breaks LatePoint forms, Turnstile, and OTP verification scripts)
- **Caching Rules:** Never add "Cache Everything" page rules. LatePoint bookings and OTP login portal must remain dynamic.
- **Cloudflare API Token:** Bound to the account level (Gino's personal account). Cloudflare Page Rules API returns error `1011` (unauthorized) because Gino's token is account-scoped rather than zone-scoped, so avoid automated page rule updates.

### Email Routing
- **Cloudflare Email Routing:** Forwards `info@gvbasketball.com` to `gvictorino.websites@gmail.com`.
- **FluentSMTP:** Configured on WordPress to authenticate using Google OAuth on behalf of `info@gvbasketball.com` (details in [forms-and-emails.md](file:///Users/rico/Git/gvbasketball/wiki/forms-and-emails.md)).
