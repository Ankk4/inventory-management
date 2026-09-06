# Inventory Management

Personal inventory app that turns receipt photos into persistent stock, purchase history, and search. Models like Gemini are good at reading a receipt once; this app is the system of record.

Stack: Laravel 13, Breeze (session auth), SQLite. Receipt parsing is optional: paste JSON from the Gemini web UI (zero API cost), local Ollama vision, or the Gemini API.

## Features

- Register / log in
- Item list with search, create/edit, and manual stock adjustments
- Receipt import with a review step (match existing items, create new ones, or skip lines)
- Receipt archive, including optional stored images
- Dashboard: item count, low stock (quantity ≤ 2), recent receipts

## How import works

```
Parse (paste JSON | Ollama | Gemini)
        ↓
Review & match (existing item or create new)
        ↓
Confirm → item quantities + stock_movements + receipts / receipt_lines
```

Data model: `categories` → `items` → `stock_movements`, plus `receipts` → `receipt_lines`.

Seeded categories: Dairy, Produce, Pantry, Frozen, Beverages, Household, Electrical. Electrical is a category name only — no SKU, bin, supplier, or datasheet fields yet.

## Requirements

- PHP 8.3+ (the Docker image uses 8.4)
- Composer, Node.js, SQLite
- Optional: [Ollama](https://ollama.com) with a vision model, or a Gemini API key

## Local setup

```bash
composer setup
php artisan migrate --seed
composer run dev
```

`composer setup` copies `.env` if missing, generates `APP_KEY`, runs migrations, installs npm packages, and builds frontend assets.

Create a login (no roles — every account is the same):

```bash
php artisan user:create "Ada Lovelace" ada@example.com
```

It prompts for a password. Then open [http://localhost:8000](http://localhost:8000), sign in, and go to **Import Receipt**. Self-serve `/register` still works if you want it.

To try the app from a phone on the same Wi-Fi:

```bash
composer run dev:wifi
```

Composer does not accept `composer run dev --wifi` (`--wifi` is treated as a Composer flag). Equivalent: `composer run dev -- --wifi`.

`composer run dev` stays on localhost. `dev:wifi` binds Laravel (`:8000`) and Vite (`:5173`) on `0.0.0.0`, points Vite HMR at this machine's LAN IP, and temporarily allows those two TCP ports from your `/24` in UFW when UFW is active (Omarchy denies incoming by default). Stop the process to remove the UFW rules. Open `http://<lan-ip>:8000` on the phone.

If you skip `composer setup`:

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm install
npm run dev    # or: npm run build
php artisan serve
```

If `public/build/manifest.json` is missing and Vite is not running, the layout falls back to Tailwind and Alpine from CDN so the UI still works.

## Docker setup

```bash
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
docker compose up --build
docker compose exec app php artisan migrate --seed
```

App: [http://localhost:8080](http://localhost:8080)

Set `APP_URL=http://localhost:8080` in `.env`. Compose bind-mounts the project directory, so run `composer install` on the host (or inside the `app` container) if `vendor/` is missing.

Compose already publishes `8080` on all interfaces. A phone on the LAN still needs the host firewall to allow it. On Omarchy, UFW denies incoming traffic and `ufw-docker` blocks published container ports:

```bash
sudo ufw allow from 192.168.0.0/16 to any port 8080 proto tcp comment 'inventory-dev-wifi'
```

Prefer `composer run dev:wifi` for phone testing; that stack is the Vite-backed one. Docker serves built assets (or the CDN fallback), not the Vite dev server.

To call Ollama on the host from inside Docker:

```env
OLLAMA_BASE_URL=http://host.docker.internal:11434
```

## Receipt import

All three paths land on the same review screen. For each line you can match an existing item, create a new one (name + category), or skip it. Confirm writes inventory and a receipt record.

Images are optional on paste (archive only) and required for Ollama / Gemini. Max size: 10 MB. Files are stored on the private local disk (`storage/app/private`) and served only to logged-in users.

### 1. Paste JSON (recommended, no API cost)

1. Open **Import Receipt** → **Paste JSON**.
2. Copy the built-in prompt into Gemini (web) or another vision model.
3. Attach the receipt image, copy the JSON response, paste it into the form.
4. Optionally attach the same image for the archive.
5. Review matches → **Confirm**.

Expected JSON:

```json
{
  "store_name": "string|null",
  "purchase_date": "YYYY-MM-DD|null",
  "currency": "string|null",
  "total": 0,
  "lines": [
    {
      "raw_name": "string",
      "quantity": 1,
      "unit": "string|null",
      "unit_price": 0,
      "line_total": 0
    }
  ]
}
```

Markdown fences around the JSON are stripped automatically.

Matching prefers an exact `normalized_name` (lowercase, collapsed whitespace), then a `LIKE` search on normalized name or raw name. Suggestions are ranked; you still confirm every line.

### 2. Ollama (local vision)

Requires Ollama running with a vision model. Default is **Qwen3-VL 8B Instruct** — stronger than Llama 3.2 Vision at receipt OCR, structured JSON, and non-English text, and it fits a 10 GB GPU.

Use the `-instruct` tag. `qwen3-vl:8b` (no suffix) is the slower thinking variant.

```env
OLLAMA_BASE_URL=http://127.0.0.1:11434
OLLAMA_MODEL=qwen3-vl:8b-instruct
OLLAMA_TIMEOUT=180
OLLAMA_NUM_CTX=4096
```

```bash
ollama pull qwen3-vl:8b-instruct
```

Then use **Import Receipt** → **Ollama** and upload an image.

If the 8B model OOMs on a smaller GPU, drop to `qwen3-vl:4b-instruct`. Do not pull `qwen3-vl:32b` on a 10 GB card.

### 3. Gemini API (optional, off by default)

```env
GEMINI_ENABLED=true
GEMINI_API_KEY=your-key
GEMINI_MODEL=gemini-2.0-flash
GEMINI_DAILY_LIMIT=50
```

The Gemini tab stays disabled until both `GEMINI_ENABLED=true` and `GEMINI_API_KEY` are set. `GEMINI_DAILY_LIMIT` is an app-side counter (resets at the end of the local day), not Google’s quota. When the cap is hit, use paste instead.

The shared parser prompt lives in `config/inventory.php` (`receipt_prompt`).

## Environment

Inventory-specific variables from `.env.example`:

| Variable | Default | Purpose |
|---|---|---|
| `DB_CONNECTION` | `sqlite` | Database driver |
| `OLLAMA_BASE_URL` | `http://127.0.0.1:11434` | Ollama HTTP API |
| `OLLAMA_MODEL` | `qwen3-vl:8b-instruct` | Vision model name (`-instruct`, not the thinking tag) |
| `OLLAMA_TIMEOUT` | `180` | HTTP timeout in seconds (cold start + image) |
| `OLLAMA_NUM_CTX` | `4096` | Context window; keep modest so a 10 GB GPU stays in VRAM |
| `GEMINI_ENABLED` | `false` | Show and allow the Gemini API import tab |
| `GEMINI_API_KEY` | empty | Google AI Studio key |
| `GEMINI_MODEL` | `gemini-2.0-flash` | Gemini model id |
| `GEMINI_DAILY_LIMIT` | `50` | App-side request cap per day |

Keep secrets in `.env` only. Do not commit API keys.

## Users

There is no admin/normal split. Add and remove accounts from the CLI:

```bash
php artisan user:create "Ada Lovelace" ada@example.com
php artisan user:list
php artisan user:delete ada@example.com
```

Pass `--password=` to `user:create` to skip the prompt (useful in scripts). Pass `--force` to `user:delete` to skip confirmation. Deleting a user also deletes their receipts.

In Docker:

```bash
docker compose exec app php artisan user:create "Ada Lovelace" ada@example.com
```

## Tests

```bash
php artisan test
```

Paste → review → confirm is covered. Ollama and Gemini are not integration-tested against live APIs.

## Current limits

- Auth exists, but there is no multi-household / sharing model
- Items can be created, edited, and adjusted — not deleted
- Low-stock threshold is hardcoded at quantity ≤ 2
- Electrical inventory is a seeded category only
- The Docker image does not build Vite assets
