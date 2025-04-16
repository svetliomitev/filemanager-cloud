# FileManager Cloud

A PHP 8.3 + SQLite3 Dockerized file manager with user quotas, file sharing, admin panel, and dark Bootstrap theme.

## 🚀 Features

- Per-user folder storage (`/storage/username`)
- File upload/delete with quota enforcement
- Sharing with password/expiry (via link)
- Admin control panel
- SQLite3 storage
- Dark mode Bootstrap UI
- Docker-ready

## 🛠️ Installation

1. **Clone the Repo**
```bash
git clone https://github.com/svetliomitev/filemanager-cloud.git
cd filemanager-cloud
```

2. **Edit your .env**
Set credentials for the admin, mail relay, and default quota.

3. **Build & Run**
```bash
docker compose build --no-cache
docker compose up -d
```

4. **First-Time Setup**
Visit `http://localhost:8080/install.php` to create the database and admin user.

5. **Login**
Go to `http://localhost:8080` and login with the admin credentials from `.env`.

## 🔧 Default Folder Structure

- `/storage/username` – user files
- `/data/database.sqlite` – SQLite3 file
- `/shared/` – shared link lookups

## 📬 Mail Support

Uses PHP `mail()` to connect to your external relay (configured in `.env`).

## 🧼 Uninstall

```bash
docker-compose down
sudo rm -rf storage/ data/ shared/
```

## 🐛 Troubleshooting

- Check container logs with `docker logs filemanager_cloud`
- Verify writable permissions for `storage/`, `data/`, `shared/`

Enjoy!
