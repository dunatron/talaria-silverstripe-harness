# ss-test (Silverstripe harness)

Blank Silverstripe 5 site for testing the local Talaria PHP SDK (`newtalaria/logging`) and the npm browser SDK (`@newtalaria/browser`).

Lives at `harnesses/ss-test/` in the Talaria workspace (local only; not in the meta-repo).

## Setup

```bash
cd harnesses/ss-test
ddev start
ddev composer update newtalaria/logging newtalaria/ss-test-theme
npm install
npm run build
```

Add to `.env`:

```
TALARIA_DSN="http://host.docker.internal:8080"
TALARIA_BROWSER_DSN="https://new-talaria-ss-test.ddev.site/talaria-api"
TALARIA_API_KEY="tal_live_…"
TALARIA_ENVIRONMENT="development"
TALARIA_RELEASE="1.0.0"
```

`TALARIA_DSN` is for PHP-in-Docker. `TALARIA_BROWSER_DSN` is used by both the Silverstripe CDN inject and the blog TypeScript app — it must be HTTPS (or same-origin) when the site is HTTPS. The harness Apache config proxies `/talaria-api/` → `http://host.docker.internal:8080/` (requires `ddev restart` after first clone).

Then:

```bash
ddev exec vendor/bin/sake dev/build flush=1
ddev exec vendor/bin/sake dev/tasks/SeedTalariaTestPageTask
```

### Theme / frontend assets

The site uses the local **`talaria`** theme (`themes/talaria`), compiled with Vite:

| Command | Purpose |
| ------- | ------- |
| `npm run build` | Compile Sass → `dist/css/main.css` and TS → `dist/js/blog.js` |
| `npm run dev` | Watch mode while editing theme/TS |
| `npm run typecheck` | Strict TypeScript check |

Rebuild after theme or blog JS changes, then `flush=1` if templates/config changed.

## Home

Open **https://new-talaria-ss-test.ddev.site/** — `HomePage` landing with harness intro, credential status, instrumentation approaches, and a demo catalogue. The **Bookstore** is featured as the recommended `Talaria\Logger` reference implementation.

## Navigation

The header uses a dashboard-style **Navigation** command palette (not a horizontal menu). Click **Navigation** or press **⌘K** / **Ctrl+K**, then filter demos and quick links. Groups: Quick links, Product demos, SDK harnesses, Integrations.

## Demo layout (`app/src/Demos/`)

Harness pages live under feature folders. Page/controller class names stay global (unchanged SiteTree `ClassName`); domain helpers use `App\Demos\{Name}\…`.

| Folder | Classes | URL | Role |
| ------ | ------- | --- | ---- |
| `Demos/Home/` | `HomePage` | `/` | Harness landing + demo catalogue |
| `Demos/Logger/` | `TalariaTestPage` | `/talaria-test` | Monolog + `TalariaClient` button harness |
| `Demos/Client/` | `TalariaClientPage` | `/talaria-client` | Full `TalariaClient` API surface |
| `Demos/Checkout/` | `CheckoutDemoPage` | `/checkout-demo` | **Client** multi-step checkout workflow |
| `Demos/Bookstore/` | `BookstorePage` + `App\Demos\Bookstore\*` | `/bookstore` | **Logger** catalog / cart / checkout |
| `Demos/Browser/` | `TalariaBrowserPage` | `/talaria-browser` | CDN `window.Talaria` JS demos |
| `Demos/Blog/` | `BlogHolderPage` / `BlogDetailPage` + `App\Demos\Blog\*` | `/blog` | Vite + `@newtalaria/browser` |

Templates stay at `themes/talaria/templates/Layout/{PageClass}.ss` (Silverstripe convention). Shared base: `Page.php` / `PageController.php`. Seed task: `SeedTalariaTestPageTask.php`.

## Bookstore (Talaria Logger)

Open **https://new-talaria-ss-test.ddev.site/bookstore**

Feature-complete demo of Approach B (`Talaria\Logger` wrapping the Injector `TalariaClient`):

- Catalog from [`app/data/books.json`](app/data/books.json)
- Session cart: add / update qty / remove / clear
- Checkout outcome buttons: payment succeeded, payment declined, out of stock, empty-cart check

Exercises `child`, tags vs extra, `withMinLevel`, `isLevelEnabled`, `captureException`, and PSR-3 placeholders. Filter dashboard events by `entity=BookstorePage` / `feature=bookstore`.

This is the **Logger** product demo. The older [`/checkout-demo`](#checkout-workflow-demo-talariaclient) page remains the **TalariaClient**-direct contrast.

## Blog harness (JSON + TypeScript)

Open **https://new-talaria-ss-test.ddev.site/blog**

- Posts come from [`app/data/blog-posts.json`](app/data/blog-posts.json) via `App\Demos\Blog\BlogPostRepository`
- PHP supplies `data-props` / `data-talaria-config` on `#blog-app`
- The Vite TypeScript app renders list/detail and owns **`@newtalaria/browser`** (npm dependency) — CDN inject is disabled on these pages
- Instrumentation matches the browser guide: `Talaria.init(config)` then `const blogLogger = Talaria.withTags({ feature, operation })`
- Detail URLs: **https://new-talaria-ss-test.ddev.site/blog/post/{slug}** (e.g. `/blog/post/welcome-to-talaria`)

Filter dashboard events by tags `source=talaria_ss_test`, `surface=blog`, `feature=blog`, `entity=BlogHolderPage|BlogDetailPage`.

## Logger harness

Open **https://new-talaria-ss-test.ddev.site/talaria-test**

Buttons exercise Monolog + direct `TalariaClient` paths. Exception actions should show `platform: php` and structured `exception.values[].stacktrace.frames` in the dashboard.

## Client API harness

Open **https://new-talaria-ss-test.ddev.site/talaria-client**

Buttons exercise the full `TalariaClient` surface: message levels, `setTags` / `setExtra` / `setUser`, per-event context, custom title + mechanism, and queue/flush. Filter dashboard events by tag `entity=TalariaClientPage`.

## Checkout workflow demo (TalariaClient)

Open **https://new-talaria-ss-test.ddev.site/checkout-demo**

Best-practice page: bootstrap the Injector `TalariaClient` with module tags / extra / user, then step through a checkout (happy path or handled payment failure). Filter dashboard events by tag `entity=CheckoutDemoPage`.

For the same style of flow with **`Talaria\Logger`**, use the [Bookstore](#bookstore-talaria-logger) demo instead.

## Browser SDK harness (CDN inject)

Open **https://new-talaria-ss-test.ddev.site/talaria-browser**

Thin PHP page with two JS bundles: (1) `talaria-browser-harness.js` exercises `window.Talaria` from the Silverstripe module’s **CDN** inject (`FrontendExtension`); (2) `talaria-browser-throws.js` only throws / rejects — no Talaria client calls — to prove auto-capture for plain page scripts. Expect first-class field `platform: javascript` and tags `platform=web`, `runtime: silverstripe-frontend`; filter by tag `entity=TalariaBrowserPage`.

If the console shows mixed-content errors to `http://host.docker.internal:8080`, set `TALARIA_BROWSER_DSN` (see Setup), `ddev restart`, then `ddev sake dev/build flush=1`.

### ClickHouse schema note

Local Serverpod stores events in ClickHouse. If your CH volume was created **before** exception columns existed, older events will have `exception: null` / `platform: null` even when the SDK sent them. Apply:

```bash
docker exec -i new_talaria_server-clickhouse-1 clickhouse-client \
  --user talaria --password talaria_dev_password \
  < ../../platform/new_talaria_server/clickhouse/init/03_events_exception_columns.sql
```

(or recreate the `new_talaria_clickhouse_data` volume so `02_events.sql` / `03_…` run on init).

## Local SDK

Composer path repo → `/var/www/talaria-logging` (host `../../sdks/php`), mounted by `.ddev/docker-compose.sdk.yaml` (absolute bind to workspace `sdks/php`).

Theme package: `newtalaria/ss-test-theme` → `themes/talaria` (exposes `dist/`, `images/`, `javascript/` under `public/_resources/`).

Admin: `admin` / `password`
