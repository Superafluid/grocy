# Order Tracking Plugin — Plan & Progress

Branch: `feature/order-tracking` (forked from grocy/grocy master)

## Goal
Add an "Orders" feature to Grocy for tracking grocery orders (store, date,
status, line items), exposing a REST API to POST/manage orders, and providing
stats comparing consumption/ordering totals this year vs last year.

## Architecture
Built directly into the grocy codebase following its existing conventions
(similar to the Batteries/Chores features): migration -> service -> UI
controller + API controller -> routes -> views + viewjs -> sidebar nav.

- New tables: `orders`, `order_items`; view `orders_current` (with item
  count/total price/store name).
- Orders have two dates: `ordered_date` (when the order was placed) and
  `arrive_date` (expected/actual arrival). `arrive_date` entries show up on
  the existing Calendar feature (`CalendarService::GetEvents()`), same as
  chores/tasks/battery due dates, with a configurable `calendar_color_orders`
  user setting.
- Stats reuse existing `stock_log` table (transaction_type = 'consume') for
  consumption-vs-last-year, and `order_items`/`orders` for ordered-vs-last-year.
- New permissions: `ORDERS`, `ORDERS_TRACK`, `ORDERS_EDIT`, `ORDERS_DELETE`
  (children of `ADMIN` in `permission_hierarchy`).
- Feature flag: `FEATURE_FLAG_ORDERS` in `config-dist.php`.
- Optional stock sync: when an order's status becomes `delivered`,
  `OrdersService::BookStockForOrder()` books each item as a stock purchase via
  `StockService::AddProduct()`.

## Files
| File | Status | Notes |
|---|---|---|
| `migrations/0258.sql` | Done | tables (`ordered_date`, `arrive_date`), indices, `orders_current` view, permission rows |
| `controllers/Users/User.php` | Done | added `PERMISSION_ORDERS*` constants |
| `config-dist.php` | Done | `FEATURE_FLAG_ORDERS` + `calendar_color_orders` default setting |
| `services/OrdersService.php` | Done | CRUD, stock booking (uses `arrive_date`), `GetStats()` (raw SQL, this-year vs last-year, optional `product_id` filter), `GetProductConsumptionStats()` (monthly consumption + running stock-history balance + per-transaction consumption list from `stock_log`). "Consumption" = `consume` bookings or `inventory-correction` entries that lowered stock |
| `controllers/Api/OrdersApiController.php` | Done | REST endpoints (list/get/create/update/delete/items/stats, `product_id` filter on stats, `GET /orders/consumption-stats/{productId}`) |
| `controllers/OrdersController.php` | Done | UI pages: list, edit form, stats (product filter), consumption stats |
| `routes.php` | Done | UI routes (`/orders`, `/order/{id}`, `/ordersstats`, `/consumptionstats`) + API routes (`/api/orders...`) |
| `views/orders.blade.php` + `public/viewjs/orders.js` | Done | list page (DataTables), collapsible order items sub-table below rows, green mark delivered / add to stock button |
| `views/orderform.blade.php` + `public/viewjs/orderform.js` | Done | create/edit form; `ordered_date`/`arrive_date` now use the `datetimepicker2`/`datetimepicker` components (calendar picker) and `arrive_date` is actually sent on save (previously dropped) |
| `views/ordersstats.blade.php` + `public/viewjs/ordersstats.js` | Done | Chart.js bar charts: ordered & consumed, this year vs last year; product dropdown filter (reloads with `?product_id=`) |
| `views/consumptionstats.blade.php` + `public/viewjs/consumptionstats.js` | Done | New page: product dropdown, stock-history line chart + monthly consumption bar chart/table (via `GetProductConsumptionStats`) |
| `views/layout/default.blade.php` | Done | sidebar nav links (Orders, Order stats, Consumption stats) gated by `GROCY_FEATURE_FLAG_ORDERS` |
| `services/CalendarService.php` | Done | added order-arrival events (title, link to `/orders`, `calendar_color_orders`) |
| `views/calendar.blade.php` | Done | added "Orders" calendar color picker |
| PHP lint / sanity check | Done | `php -l` passed on all new/changed PHP files |
| `grocy.openapi.json` | Not planned | new endpoints work but won't show in Swagger UI (out of scope for now) |
| Localization (`localization/`) | Not planned | English strings render as-is via `$__t` fallback |

## REST API summary
- `GET /api/orders` — list orders (filterable/sortable like other endpoints)
- `GET /api/orders/{orderId}` — order + items
- `POST /api/orders` — create order: `{ordered_date, arrive_date, shopping_location_id, status, note, items:[{product_id, amount, qu_id, price, note}]}`
- `PUT /api/orders/{orderId}` — update order fields
- `DELETE /api/orders/{orderId}` — delete order + items
- `POST /api/orders/{orderId}/items` — add item
- `DELETE /api/orders/items/{orderItemId}` — remove item
- `GET /api/orders/stats?year=YYYY` — ordered & consumed amounts, this year vs last year, per product

## Remaining steps
1. (Optional follow-up, not blocking) add OpenAPI paths for the new endpoints.
2. Manual/functional test against a running Grocy instance once composer/yarn deps are installed (not done in this environment).
