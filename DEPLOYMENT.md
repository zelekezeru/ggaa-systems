# Deploying GGAA to cPanel (SSH + main domain + fresh migrate)

Laravel 12 + Inertia/Vue 3 (Vite) + MySQL. The web server must serve the
`public/` directory, **not** the project root.

**Placeholder values used below — replace with your real ones:**

| Placeholder | Meaning | Example real value |
|---|---|---|
| `moonveil.hosting.net` | cPanel server hostname | your host's server address |
| `kestrel` | cPanel account username | shown top-right in cPanel UI |
| `ggaa-systems.org` | Your domain | the domain pointed at this cPanel account |
| `kestrel_ggaa` | MySQL database name | cPanel auto-prefixes with your username |
| `kestrel_vault` | MySQL username | same prefix rule |
| `Tr0ub4dor&3` | MySQL password | use a strong password |

---

## 0. Prerequisites (in cPanel UI)

- **MultiPHP Manager** → set `ggaa-systems.org` to **PHP 8.2+**.
- **MySQL Databases** → create:
  - Database: `kestrel_ggaa`
  - User: `kestrel_vault` with password `Tr0ub4dor&3`
  - Add the user to the DB with **ALL PRIVILEGES**.
- **SSH Access** → confirm you can connect: `ssh kestrel@moonveil.hosting.net`

---

## 1. Authorize the server to pull from the private GitHub repo

Do this **once** — it lets the server `git clone` and `git pull` without a password.

### 1a. Generate a deploy key on the server

```bash
ssh kestrel@moonveil.hosting.net
ssh-keygen -t ed25519 -C "cpanel-ggaa-deploy" -f ~/.ssh/github_ggaa -N ""
cat ~/.ssh/github_ggaa.pub   # copy this entire line
```

### 1b. Add the public key to GitHub as a Deploy Key

1. Go to **github.com/zelekezeru/ggaa-systems → Settings → Deploy keys → Add deploy key**.
2. Title: `moonveil cPanel`, paste the public key, leave **Allow write access** unchecked → **Add key**.

### 1c. Configure SSH on the server to use this key for GitHub

```bash
cat >> ~/.ssh/config << 'EOF'

Host github-ggaa
    HostName github.com
    User git
    IdentityFile ~/.ssh/github_ggaa
    IdentitiesOnly yes
EOF
chmod 600 ~/.ssh/config
```

### 1d. Test the connection

```bash
ssh -T github-ggaa
# Expected: "Hi zelekezeru/ggaa-systems! You've successfully authenticated..."
```

---

## 2. Clone the repo (outside public_html)

```bash
cd ~
git clone github-ggaa:zelekezeru/ggaa-systems.git ggaa-systems
cd ggaa-systems
```

> The SSH alias `github-ggaa:` uses the deploy key — do not use the HTTPS URL here.

---

## 3. Point the main domain's document root at `public/`

**Preferred (modern cPanel):** *Domains* → `ggaa-systems.org` → **Manage** →
set **Document Root** to `ggaa-systems/public` → Save.

**Fallback — symlink `public_html`:**

```bash
cd ~
rm -rf public_html          # back up first if it contains anything
ln -s ~/ggaa-systems/public ~/public_html
```

---

## 4. Install PHP dependencies

```bash
cd ~/ggaa-systems
composer install --no-dev --optimize-autoloader
```

> If `composer` isn't on PATH try:
> `/opt/cpanel/ea-php82/root/usr/bin/php /usr/local/bin/composer install --no-dev --optimize-autoloader`

---

## 5. Upload the pre-built front-end assets

Assets are gitignored — they were built locally before this deploy.
Upload `public/build/` to the server:

```bash
# From your local machine (Windows PowerShell):
scp -r "d:\CODE\ggaa-systems\public\build" kestrel@moonveil.hosting.net:~/ggaa-systems/public/

# Then on the server, remove the leftover dev-server marker:
rm -f ~/ggaa-systems/public/hot
```

---

## 6. Configure the environment

```bash
cp .env.production.example .env
nano .env
```

Minimum values to set in `.env`:

```ini
APP_URL=https://ggaa-systems.org
APP_KEY=                        # leave blank — next command fills it

DB_DATABASE=kestrel_ggaa
DB_USERNAME=kestrel_vault
DB_PASSWORD=Tr0ub4dor&3

MAIL_HOST=mail.ggaa-systems.org
MAIL_USERNAME=noreply@ggaa-systems.org
MAIL_PASSWORD=your-mail-password
MAIL_FROM_ADDRESS=noreply@ggaa-systems.org
```

Then generate the app key:

```bash
php artisan key:generate
```

---

## 7. Migrate, storage link, cache

```bash
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

> Re-run `php artisan config:cache` after any future `.env` change.

---

## 8. Permissions

```bash
chmod -R ug+rwx storage bootstrap/cache
```

---

## 9. Cron jobs (cPanel → Cron Jobs)

Add **two** entries. First confirm your PHP binary path with `which php`
(often `/opt/cpanel/ea-php82/root/usr/bin/php`).

**Laravel scheduler — every minute:**
```
* * * * * cd /home/kestrel/ggaa-systems && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
```

**Queue worker — short-burst, cron-restarted (no persistent process needed):**
```
* * * * * cd /home/kestrel/ggaa-systems && /usr/local/bin/php artisan queue:work --stop-when-empty --max-time=55 >> /dev/null 2>&1
```

---

## 10. Verify

- Visit `https://ggaa-systems.org` — page loads with styles (no 404 on assets).
- `tail -f ~/ggaa-systems/storage/logs/laravel.log` — no errors.
- cPanel → **SSL/TLS Status** → run AutoSSL if the padlock is missing.

---

## Redeploying later

```bash
cd ~/ggaa-systems
php artisan down
git pull                          # deploy key handles auth
composer install --no-dev --optimize-autoloader
# upload fresh public/build/ from local, then:
rm -f public/hot
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan up
```
