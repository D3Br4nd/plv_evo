# Pro Loco Venticanese Evolution

PWA per la gestione di eventi e tesseramenti della Pro Loco Venticanese.

## 🚀 Stack Tecnologico

### Backend
- **PHP**: 8.4
- **Framework**: Laravel 12.44
- **Server**: FrankenPHP (Caddy + PHP)
- **Database**: PostgreSQL 18.1
- **Cache/Queue**: Redis 8.4.0
- **WebSockets**: Laravel Reverb

### Frontend
- **Framework**: Svelte 5 (Runes)
- **Router**: Inertia.js 2.0
- **Styling**: Tailwind CSS 4.1 (CSS-first)
- **Theme**: Shadcn Zinc (Monochrome)
- **Build**: Vite 5
- **PWA**: vite-plugin-pwa

## 🆕 Fresh Install (Docker)

Segui questi passaggi per avviare il progetto da zero su una nuova macchina.

### 1. Preparazione

Clona il repository ed entra nella cartella:

```bash
git clone <repository-url> plv_saas
cd plv_saas
```

Crea il file `.env`:

```bash
cp .env.example .env
```

### 2. Installazione Dipendenze

Installa le dipendenze di PHP e Node.js usando Docker (senza bisogno di avere PHP/Node installati localmente):

```bash
# Dipendenze PHP (Vendor)
docker run --rm -v $(pwd):/app -w /app composer:2 install --ignore-platform-reqs

# Dipendenze Node.js (Node Modules)
docker run --rm -v $(pwd):/app -w /app node:24-alpine npm install
```

### 3. Build & Avvio

Compila gli asset e avvia i container:

```bash
# Build degli asset frontend
docker run --rm -v $(pwd):/app -w /app node:24-alpine npm run build

# Avvia lo stack
docker compose up -d --build
```

### 4. Database Setup (Migrazioni e Seeders)

Una volta che i container sono attivi (verifica con `docker compose ps`), esegui le migrazioni e il seeding del database:

**IMPORTANTE**: Questo comando resetta il database e inserisce i dati di esempio.

```bash
docker compose exec app php artisan migrate:fresh --seed
```

### 5. Accesso

- **Web App**: [http://localhost:8000](http://localhost:8000)
- **Admin Login**: `admin@prolocoventicanese.it` / `password`

---

## 🔧 Comandi Utili per lo Sviluppo

```bash
# Logs in tempo reale
docker compose logs -f

# Accesso alla shell del container app
docker compose exec app sh

# Eseguire comandi Artisan
docker compose exec app php artisan [command]

# Riavviare i container
docker compose restart
```

## 📁 Struttura Progetto

```
plv_saas/
├── app/
│   ├── Models/         # User, Event, Membership, Project (UUIDv7)
├── database/
│   ├── migrations/     # 2025_01_01_000000_create_plv_schema.php (Squashed)
├── resources/
│   ├── js/Pages/       # Svelte Components (Admin/Members, Admin/Events, Admin/Projects)
├── docker/             # Configurazioni e volumi persistenti
├── Dockerfile          # FrankenPHP image definition
├── docker-compose.yml  # Servizi: app, db, redis, reverb, vite
```

## 👥 Team

- **Tech Lead**: Massimiliano
- **Stack**: Bleeding Edge PHP Ecosystem
