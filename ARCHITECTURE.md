# Cyrex Store — Documento técnico de arquitectura

> Este documento es una fotografía completa y literal del estado del código a la fecha de escritura. No es un plan ni una aspiración: cada afirmación aquí corresponde a código que existe y se puede verificar abriendo el archivo indicado. El objetivo es que otra IA (o un desarrollador nuevo) pueda diseñar y planear nuevas funcionalidades sin tener que leer el código fuente completo primero.
>
> Documento complementario: [`VISION.md`](VISION.md) — explica el *por qué* de las decisiones de diseño (estética, tono, filosofía de producto). Este documento (`ARCHITECTURE.md`) explica el *cómo* técnico. Léanse juntos.

---

## 1. Resumen del proyecto

### Objetivo de la web

Cyrex Store es una tienda online de componentes y periféricos gamer con sede en Bolivia (Santa Cruz y Cochabamba). El proyecto es una reconstrucción completa desde cero de un sitio anterior en WordPress/WooCommerce (`cyrexstore.com`, que sigue en producción sin tocarse durante todo este desarrollo). El sitio nuevo se está construyendo y probando en el subdominio `dev.cyrexstore.com`; el corte (cutover) al dominio principal es un paso futuro que todavía no ha ocurrido.

El modelo de negocio **no incluye checkout con tarjeta ni pasarela de pago**. El flujo de conversión real es: el cliente navega el catálogo, arma un carrito (sin cantidades, solo presencia/ausencia de cada producto o variante), y al finalizar, el sitio arma automáticamente un mensaje de WhatsApp con el detalle del pedido y lo abre en `wa.me`. Cyrex recibe el pedido como un mensaje de texto en su WhatsApp de negocio, no como una orden en una base de datos. Esto está documentado explícitamente como decisión de negocio permanente en `VISION.md`, no como una limitación temporal.

### Tecnologías utilizadas

- **Backend**: PHP 8.4 (requiere `^8.2` en `composer.json`), Laravel 12
- **Base de datos**: SQLite en desarrollo local (confirmado en `.env.example` y `config/database.php`, default `sqlite`). **El motor real en producción/staging (`dev.cyrexstore.com`) no está confirmado en este documento** — nadie ha inspeccionado el `.env` real del servidor durante esta sesión de trabajo; ver sección 17.
- **Frontend**: Server-rendered con Blade (motor de plantillas de Laravel). **Cero JavaScript framework** (no React, no Vue, no Livewire, no Inertia). Toda la interactividad del lado del cliente es **Alpine.js 3.x**, cargado desde CDN (`jsdelivr.net`), más el plugin oficial `@alpinejs/collapse` (también CDN).
- **CSS**: CSS plano escrito a mano en dos archivos (`public/css/app.css` para el sitio público, `public/css/admin.css` para el panel admin). **No hay Tailwind, no hay Sass/Less, no hay build step de CSS en producción**, a pesar de que el proyecto tiene `tailwindcss` y `@tailwindcss/vite` en `package.json` — ese Vite/Tailwind es un remanente del scaffold por defecto de `laravel new` y **no está conectado a ninguna vista real**. Ver sección 17 (limitaciones) para el detalle de este "peso muerto".
- **Fuentes**: Google Fonts cargadas por `<link>` en el `<head>` (Space Grotesk, Inter, JetBrains Mono), con `display=optional` en el sitio público (para evitar salto de layout) y `display=swap` en el admin/login.
- **Hosting**: Hostinger, hosting compartido. Despliegue vía Git integrado de hPanel (webhook en cada `git push` a `main`).

### Framework principal

**Laravel 12** (`laravel/framework: ^12.0`). Arquitectura MVC clásica de Laravel: Controllers en `app/Http/Controllers`, Models Eloquent en `app/Models`, vistas Blade en `resources/views`, rutas centralizadas en `routes/web.php`. No usa la API de recursos de Laravel (no hay `routes/api.php` activo, no hay Sanctum/Passport instalados) — es una aplicación puramente basada en sesión de navegador, no una API.

### Librerías importantes

**Backend (`composer.json`)**:
- `laravel/framework ^12.0` — framework principal
- `laravel/tinker ^2.10` — REPL de consola (usado activamente durante desarrollo, ver sección 22)
- Dev-only: `laravel/pail`, `laravel/pint`, `laravel/sail`, `mockery/mockery`, `nunomaduro/collision`, `phpunit/phpunit`, `fakerphp/faker` — todas son dependencias de scaffold por defecto, **ninguna está siendo usada activamente** (no hay tests reales más allá del `ExampleTest.php` de scaffold, no hay uso de Sail/Docker, Pint no está corriendo en CI porque no hay CI configurado).

**Frontend (cargado vía CDN en `layouts/app.blade.php` y `admin/layout.blade.php`, NO vía npm/composer)**:
- `alpinejs@3.x.x` — reactividad del cliente (dropdowns, drawers, acordeones, store global del carrito)
- `@alpinejs/collapse@3.x.x` — plugin oficial de Alpine para animar alto (`x-collapse`), usado únicamente en el acordeón de categorías del drawer mobile

**Frontend (`package.json`, presente pero NO usado en runtime)**:
- `vite`, `laravel-vite-plugin`, `tailwindcss`, `@tailwindcss/vite`, `axios`, `concurrently` — instalados pero sin ningún `<link>`/`<script>` en las vistas que apunte a assets compilados por Vite. Ver sección 17.

### Estructura general

Es una aplicación monolítica Laravel clásica (no microservicios, no separación frontend/backend, no API REST consumida por un SPA). Un único proceso PHP sirve tanto las páginas públicas como el panel administrativo, ambos como HTML renderizado en el servidor. La "app" completa vive en un solo repositorio Git (`github.com/cxDanilo/CYREX-STORE`, rama `main`).

---

## 2. Arquitectura

### Cómo está organizado el proyecto

Sigue la estructura estándar de un proyecto Laravel 12 recién creado (`laravel new`), sin restructuración de carpetas (no usa Domain-Driven Design, no usa carpetas `Modules/`, no usa Actions/Services como capa formal — la lógica vive directamente en Controllers y un único helper estático en `app/Support/Cart.php`).

### Estructura de carpetas y qué hace cada una

```
app/
  Console/
    Commands/
      CreateAdminUser.php      — comando artisan `admin:create`, única forma de crear usuarios admin
  Http/
    Controllers/
      Controller.php           — clase base abstracta vacía (sin lógica compartida)
      HomeController.php       — página de inicio
      ShopController.php       — catálogo, búsqueda, ficha de producto
      CartController.php       — agregar/quitar del carrito (JSON o redirect)
      Admin/
        AuthController.php     — login/logout del panel
        ProductController.php  — CRUD de productos
        CategoryController.php — CRUD de categorías
        SettingsController.php — formulario único de ajustes globales
        UserController.php     — SOLO listado de usuarios (sin crear/editar/borrar)
  Models/
    User.php, Category.php, Product.php, ProductVariant.php,
    ExchangeRate.php, Setting.php
  Providers/
    AppServiceProvider.php     — registra los View Composers (ver sección 8)
  Support/
    Cart.php                   — lógica del carrito de sesión (clase de métodos estáticos, no un modelo Eloquent)

bootstrap/
  app.php                      — configuración de Laravel 12 estilo "slim": rutas, middleware globales, manejo de excepciones

config/                        — configuración estándar de Laravel, sin archivos de config custom del proyecto

database/
  migrations/                  — historial completo del esquema (ver sección 5)
  seeders/                     — CategorySeeder, ProductSeeder, ExchangeRateSeeder, DatabaseSeeder
  factories/                   — solo UserFactory (scaffold, sin uso real)
  database.sqlite              — la base de datos SQLite local

public/
  index.php                    — front controller de Laravel (punto de entrada HTTP)
  .htaccess                    — reglas de reescritura Apache (rutas "limpias" hacia index.php)
  css/
    app.css                    — TODO el CSS del sitio público (un solo archivo, 250 líneas)
    admin.css                  — TODO el CSS del panel admin (un solo archivo, 88 líneas)
  images/
    logo-horizontal.png        — el logo real de la marca (dorado, fondo transparente)
  uploads/
    products/                  — carpeta de destino histórica de imágenes de producto subidas por el admin
                                  (ver sección 12 — esta ruta ahora es solo el FALLBACK local; en producción
                                  el disco apunta a una ruta externa vía UPLOADS_DISK_ROOT)

resources/
  views/                       — TODAS las plantillas Blade (ver árbol completo en sección 19)
  css/app.css, js/app.js, js/bootstrap.js  — scaffold de Vite/Tailwind SIN USAR (ver sección 17)

routes/
  web.php                      — TODAS las rutas de la aplicación (públicas + admin), un solo archivo
  console.php                  — solo el comando `inspire` de ejemplo de Laravel

storage/                       — logs, cache de framework, sesiones si se usara el driver de archivo (no se usa, sesiones van a DB)

VISION.md                      — filosofía de diseño y decisiones de producto (léase junto a este documento)
README.md                      — el README genérico por defecto de Laravel, sin información específica del proyecto
```

### Patrón de arquitectura utilizado

**MVC clásico de Laravel, sin capas adicionales.** No hay Repository pattern, no hay Service classes formales, no hay Form Requests dedicados (las validaciones viven inline dentro de cada método de controller vía `$request->validate([...])`), no hay API Resources/Transformers (las respuestas JSON del carrito se arman a mano como arrays asociativos). La única abstracción propia del proyecto por fuera de Eloquent es `App\Support\Cart`, una clase de métodos estáticos que envuelve la sesión de Laravel — deliberadamente simple, no es una interfaz ni tiene inyección de dependencias.

**View Composers como mecanismo central de "layout de datos compartidos"** — este es el patrón arquitectónico más importante para entender antes de tocar el header o el footer. Ver sección 8 y 9 en detalle.

### Flujo de navegación

Sitio público (todas extienden `layouts/app.blade.php`):

```
/                              → HomeController@index        → home.blade.php
/tienda                        → ShopController@index         → shop.blade.php
/tienda?category={slug}        → ShopController@index (con filtro) → shop.blade.php
/tienda?q={texto}               → ShopController@index (con búsqueda) → shop.blade.php
/producto/{slug}                → ShopController@show          → product.blade.php
/buscar-sugerencias?q={texto}   → ShopController@suggest       → JSON (fetch desde el buscador predictivo)
POST /carrito/agregar            → CartController@add           → JSON o redirect
DELETE /carrito/quitar/{key}     → CartController@remove        → JSON o redirect
```

Panel admin (todas extienden `admin/layout.blade.php`, prefijo `/admin`, nombre de ruta `admin.*`):

```
GET  /admin/login                       → AuthController@showLogin  (solo invitados)
POST /admin/login                       → AuthController@login      (solo invitados)
POST /admin/logout                      → AuthController@logout     (solo autenticados)
GET  /admin/                            → redirect a /admin/productos
GET  /admin/productos                   → ProductController@index
GET  /admin/productos/nuevo             → ProductController@create
POST /admin/productos                   → ProductController@store
GET  /admin/productos/{id}/editar       → ProductController@edit
PUT  /admin/productos/{id}              → ProductController@update
DELETE /admin/productos/{id}            → ProductController@destroy
PATCH /admin/productos/{id}/estado      → ProductController@toggleStatus
GET  /admin/categorias                  → CategoryController@index
GET  /admin/categorias/nueva            → CategoryController@create
POST /admin/categorias                  → CategoryController@store
GET  /admin/categorias/{id}/editar      → CategoryController@edit
PUT  /admin/categorias/{id}             → CategoryController@update
DELETE /admin/categorias/{id}           → CategoryController@destroy
GET  /admin/usuarios                    → UserController@index (solo lectura)
GET  /admin/ajustes                     → SettingsController@edit
PUT  /admin/ajustes                     → SettingsController@update
```

No existe ninguna ruta de tipo `/{slug}` genérica que resuelva "páginas" arbitrarias — cada URL pública está explícitamente declarada en `routes/web.php` y apunta a un método de controller específico. Ver sección 7.

---

## 3. Frontend

### Framework

Ninguno, en el sentido de SPA/JS-framework. El "frontend" es HTML generado en servidor por Blade, con Alpine.js encima para interactividad puntual (dropdowns, modales, drawers, un store reactivo global para el carrito). No hay Virtual DOM, no hay Webpack/Vite bundleando componentes, no hay hidratación.

### Componentes reutilizables

En el sentido de Blade, los "componentes" son **partials incluidos con `@include(...)`**, no Blade Components formales (`<x-componente />`, `php artisan make:component`) — el proyecto no usa esa característica de Laravel en absoluto. Lista completa y explicación de cada uno en la sección 10.

### Layouts

Dos layouts raíz, completamente independientes entre sí (no hay un layout "base" común del que ambos hereden):

1. **`resources/views/layouts/app.blade.php`** — layout del sitio público. Estructura: `<head>` (título dinámico vía `@yield('title', ...)`, variable CSS `--logo-height` inyectada inline, fuentes, `app.css`, scripts de Alpine), `@include('partials.nav')`, `<main>@yield('content')</main>`, footer (inline, no partial — ver sección 9), `@yield('scripts')`.
2. **`resources/views/admin/layout.blade.php`** — layout del panel admin. Estructura completamente distinta: sidebar fija a la izquierda (`.admin-sidebar`) con navegación entre secciones, topbar con título de página y slot de acciones (`@yield('topbar-actions')`), área de contenido con mensajes flash (`session('status')` / `session('error')`).

Todas las vistas públicas hacen `@extends('layouts.app')`; todas las vistas admin hacen `@extends('admin.layout')`. No hay un tercer layout ni layouts anidados.

### Sistema de rutas

El de Laravel estándar (`Illuminate\Support\Facades\Route`), declarado 100% en `routes/web.php`. No hay rutas definidas en ningún controller vía atributos/anotaciones, no hay auto-discovery de rutas por convención de carpetas. Nombres de ruta en español (`shop`, `product.show`, `cart.add`, `admin.productos.index`, etc.) — cualquier `route(...)` nuevo debe seguir esa convención de nombres para mantener consistencia.

### Gestión de estados

No hay un state manager tipo Redux/Vuex/Pinia. El único estado "global" del lado del cliente es el **Alpine.store('cart', {...})**, definido inline en un `<script>` dentro de `resources/views/partials/nav.blade.php` (líneas 1–64 de ese archivo), registrado en el evento `alpine:init`. Su forma:

```js
Alpine.store('cart', {
  keys: [...],       // array de strings "productId:variantId", inicializado desde PHP con @json(...)
  count: N,           // inicializado desde PHP
  open: false,        // controla si el drawer del carrito está visible
  has(productId, variantId) { ... },
  async add(productId, variantId) { fetch POST /carrito/agregar → this.apply(data); this.open = true; },
  async remove(key) { fetch DELETE /carrito/quitar/{key} → this.apply(data); },
  apply(data) { actualiza keys/count, reemplaza el innerHTML de #cart-drawer-content con el HTML que devuelve el servidor, dispara animación de pulso en el badge },
});
```

Todo el resto del "estado" es local a cada componente Alpine (`x-data` por elemento): el buscador predictivo tiene su propio `x-data` con `q`/`results`/`open`; el drawer mobile tiene `mobileOpen`; el acordeón de categorías tiene `openGroup`; la página de producto tiene su propio `x-data` con `variant`/`showBob`/`toggled` para el selector de variante y el toggle de moneda; el menú flotante de categorías tiene su propio `x-data` con `hoverCat`/`expanded`/`showHint`. **Ningún estado de UI se comparte entre componentes salvo a través del store global del carrito o de eventos de teclado a nivel `window`** (`x-on:keydown.escape.window` para cerrar drawers).

### Manejo de imágenes

Ver sección 12 en detalle (es lo bastante importante como para tener su propia sección completa). Resumen: subida vía formulario admin con `enctype="multipart/form-data"`, guardadas en disco local (no cloud storage), servidas mediante la funcionalidad nativa `serve => true` de discos locales de Laravel 11+/12 (no mediante un `<img src>` apuntando directo a un archivo físico bajo `public/`, sino a través de una ruta que Laravel registra automáticamente).

### Sistema responsive

CSS puro con `@media` queries, sin ningún framework de grid (no Bootstrap, no Tailwind). Breakpoints usados en todo el proyecto:

| Breakpoint | Uso |
|---|---|
| `max-width: 480px` | Footer colapsa de 2 a 1 columna |
| `max-width: 860px` | **El breakpoint principal "mobile/tablet compacto"**: aparece el botón hamburguesa, se oculta el buscador de la barra superior, se oculta el menú flotante de categorías, se oculta el link "Inicio"/"Tienda" y el indicador "En línea", el logo del header se fuerza a 36px (independiente del valor configurado en Ajustes), se oculta el botón "Ver tienda" del header, el footer pasa de 4 a 2 columnas, la sidebar del admin pasa de vertical a horizontal |
| `max-width: 960px` | El grid de la ficha de producto (`product-hero`) pasa de 2 columnas a 1 |
| `max-width: 1180px` | El texto del botón de WhatsApp del header se oculta (queda solo el ícono) antes de llegar al breakpoint mobile completo |

No hay `container queries`, todo es `viewport` puro. El grid de productos (`.product-grid`) usa `grid-template-columns: repeat(auto-fill, minmax(220px, 1fr))` — es decir, es responsive "automático" sin media queries propias, controlado enteramente por el ancho mínimo de columna.

### Animaciones

Documentadas también en `VISION.md` como parte de la identidad del proyecto ("presencia sutil, nunca ruido"). Inventario completo de animaciones existentes en el código:

- **`@keyframes status-breathe`** — el punto verde "En línea" del header respira (opacidad 1→0.35→1) en un ciclo de 2.4s infinito.
- **`@keyframes badge-pulse`** — el contador del carrito hace un pulso de escala (1→1.35→1) en 0.35s cada vez que cambia la cantidad de ítems, disparado manualmente desde JS (`classList.remove/add('pulse')` con un `void badge.offsetWidth` para forzar reflow y poder re-disparar la misma animación).
- **`@keyframes price-flash`** — el precio en la ficha de producto parpadea (opacidad 0.35→1) cada vez que se cambia de moneda, también disparado manualmente vía `x-effect` + reflow forzado.
- **`@keyframes drop-to-bob` / `@keyframes drop-to-usd`** — la animación "gota líquida" del toggle USD/BOB en la ficha de producto: el thumb del switch se estira (`scaleX(1.35)`) y deforma su `border-radius` a mitad de camino para simular una gota de líquido cayendo de un lado al otro, en 0.5s con `cubic-bezier(.4,0,.2,1)`. Esta animación fue pedida explícitamente por el dueño del proyecto y es la más elaborada del sitio.
- **`@keyframes cat-hint-nudge`** — la burbuja de onboarding del menú flotante de categorías se mueve levemente hacia la derecha y vuelve (`translateX(0→4px→0)`) en un ciclo de 1.8s infinito, para llamar la atención sin ser invasiva.
- **Transiciones de Alpine (`x-transition`)** — fades de opacidad en duraciones de 150ms a 400ms: dropdown de sugerencias de búsqueda (150ms), overlay del drawer mobile (200ms), overlay del carrito (200ms), flyout de categorías del menú flotante (150ms), burbuja de hint de categorías (400ms).
- **`x-collapse` (plugin de Alpine)** — única animación de **alto real** (no solo opacidad) del proyecto: el acordeón de categorías del drawer mobile anima su `height` de 0 al alto real del contenido en 250ms, con `overflow:hidden` durante la transición. Antes de la sesión más reciente de trabajo, este acordeón solo hacía fade de opacidad sin animar el layout — fue reemplazado explícitamente por pedido del dueño ("le faltan animaciones").
- **Transiciones CSS simples (`transition: ... .15s/.2s/.3s`)** en casi todos los elementos interactivos: hover de botones (`transform: translateY(-1px)`), hover de links, cambio de color en categorías activas, ancho del menú flotante colapsado/expandido (`.cat-float-list` 48px→230px en 0.2s), apertura de los drawers (`transform: translateX(...)` en 0.3s con `cubic-bezier(.65,0,.35,1)`).

**Regla de proyecto (documentada en memoria de la sesión de trabajo, no en el código):** desde cierto punto de la conversación con el dueño, quedó establecido que **todo cambio de estado visible en la web pública debe tener al menos una transición sutil** — nunca un cambio instantáneo sin transición. Cualquier feature nueva debe respetar esto.

### Sistema de temas

**No existe.** Un solo tema oscuro, hardcodeado como variables CSS en `:root` de `app.css`:

```css
--bg:#080808; --bg-elevated:#131313; --bg-elevated-2:#1C1C1C;
--gold:#FFD900; --gold-dim:#3A3300;
--text-primary:#F5F5F3; --text-secondary:#8E8E93; --text-muted:#5C5C60;
--border:rgba(255,255,255,0.08); --border-strong:rgba(255,255,255,0.16);
--green:#3DDC84; --red:#E24B4A;
--font-display:'Space Grotesk'; --font-body:'Inter'; --font-mono:'JetBrains Mono';
```

No hay modo claro, no hay toggle de tema, no hay `prefers-color-scheme`. La ÚNICA variable CSS que se inyecta dinámicamente por request (no está hardcodeada en el archivo `.css`) es `--logo-height`, inyectada inline en un `<style>` en el `<head>` de `layouts/app.blade.php` con el valor que el admin configuró en Ajustes.

---

## 4. Backend

### Framework

Laravel 12 puro. Sin ningún paquete de arquitectura adicional (no Nova, no Filament, no Livewire, no Jetstream, no Breeze — el login admin fue escrito a mano).

### API

**No existe una API REST/JSON formal.** El único endpoint que devuelve JSON de forma consistente es:
- `GET /buscar-sugerencias?q=...` (`ShopController@suggest`) — devuelve `{"results": [{name, category, url, image, price}, ...]}`, usado por el buscador predictivo.
- `POST /carrito/agregar` y `DELETE /carrito/quitar/{key}` — devuelven JSON **solo si** la petición trae header `Accept: application/json` (`$request->wantsJson()`); si no, hacen un `redirect()->back()` tradicional con `session()->flash('status', ...)`. Esto significa que estas dos rutas sirven doble propósito (progressive enhancement): funcionan sin JavaScript (fallback a POST/DELETE + redirect) y funcionan vía fetch/AJAX (la app siempre las llama vía fetch en la práctica, pero la ruta soporta ambos casos).

No hay versionado de API, no hay autenticación por token/API key, no hay rate limiting específico en estas rutas.

### Endpoints

Ver el listado completo en la sección 2 ("Flujo de navegación").

### Autenticación

Sistema de autenticación de sesión estándar de Laravel (`Illuminate\Support\Facades\Auth`, guard `web`, provider `eloquent` contra el modelo `User`). Detalles importantes:

- **No hay roles ni permisos.** Cualquier fila en la tabla `users` tiene acceso completo a TODO el panel `/admin` — no existe un campo `role`/`is_admin`, no existe ninguna distinción entre "un admin" y "otro admin". El sistema fue diseñado así deliberadamente porque, al momento de escribirse, solo Danilo (el dueño) iba a tener acceso (ver commit history / decisión "de momento estará en demo, no es necesario tanta seguridad, pq solo será para mí").
- **No hay registro público.** La única forma de crear un usuario admin es el comando de consola `php artisan admin:create` (`app/Console/Commands/CreateAdminUser.php`), que pide interactivamente nombre, email, contraseña y confirmación por `Command::ask()`/`Command::secret()`. La contraseña mínima es de **4 caracteres** (`'password' => ['required', 'min:4']`) — deliberadamente relajado, no es un descuido.
- **No hay recuperación de contraseña conectada.** Existe la tabla `password_reset_tokens` (migración por defecto de Laravel) y la config en `config/auth.php`, pero no hay rutas de "olvidé mi contraseña" declaradas en `routes/web.php`, ni vistas para ese flujo.
- **No hay verificación de email.** El campo `email_verified_at` existe en la tabla pero nunca se usa (`MustVerifyEmail` no está implementado en el modelo `User`).
- **Login**: `POST /admin/login` → `AuthController@login` → `Auth::attempt($credentials, $request->boolean('remember'))` → si falla, `back()->withErrors(...)`; si tiene éxito, `$request->session()->regenerate()` (previene session fixation) y `redirect()->intended(route('admin.productos.index'))`.
- **Logout**: invalida la sesión (`session()->invalidate()`) y regenera el token CSRF (`session()->regenerateToken()`).
- **Redirects automáticos**: configurados en `bootstrap/app.php` vía `$middleware->redirectGuestsTo('/admin/login')` y `$middleware->redirectUsersTo('/admin/productos')` — esto es lo que hace que, por ejemplo, un usuario ya logueado que visite `/admin/login` sea redirigido automáticamente a `/admin/productos` (por el middleware `guest`), y que cualquiera sin sesión que intente entrar a una ruta protegida por `auth` termine en el login.

### Middleware

**No hay ninguna clase de middleware custom en el proyecto** — la carpeta `app/Http/Middleware/` ni siquiera existe. Toda la protección de rutas usa los middleware **nativos de Laravel** (`auth`, `guest`), aplicados directamente en `routes/web.php` mediante `Route::middleware('auth')->group(...)` / `Route::middleware('guest')->group(...)`. El middleware CSRF (`ValidateCsrfToken`) es el que Laravel incluye por defecto en el grupo `web` — nunca fue deshabilitado ni customizado, razón por la cual toda petición `fetch()` del lado del cliente que muta estado (agregar/quitar del carrito) debe mandar manualmente el header `X-CSRF-TOKEN` con el valor de `{{ csrf_token() }}`.

### Roles

No existen (ver "Autenticación" arriba). Si se necesitara en el futuro una distinción real de roles (ej. "editor de catálogo" vs "super admin"), habría que: agregar una columna `role` (o una tabla `roles`/`permissions` completa tipo Spatie Permission), y envolver cada grupo de rutas admin con un middleware nuevo que no existe todavía.

### Validaciones

Todas inline, usando `$request->validate([...])` dentro de cada método de controller (no hay clases `FormRequest` dedicadas en ningún lado del proyecto). Ejemplos representativos:

- **Productos** (`ProductController::validated()`): `category_id` debe existir en `categories`; `slug` es único (ignorando el propio registro en edición) y debe ser `alpha_dash`; `price` numérico ≥0; `currency` limitado a `USD,BOB`; `image` opcional, debe ser imagen `jpg,jpeg,png,webp` de máximo 4096 KB.
- **Categorías** (`CategoryController::validated()`): además de las reglas básicas, tiene una **regla de negocio custom vía closure** que impide que una categoría con hijas propias sea asignada como hija de otra (jerarquía de máximo 2 niveles), y una regla `Rule::exists('categories','id')->where('parent_id', null)` que garantiza que el `parent_id` elegido sea siempre una categoría de nivel superior (nunca una hija de otra categoría — evita jerarquías de 3+ niveles).
- **Ajustes** (`SettingsController::update()`): `whatsapp_number` debe matchear `^[0-9]{6,15}$` (solo dígitos, sin `+` ni espacios); `logo_height` entero entre 20 y 120; `currency_mode` limitado a 3 valores fijos.
- **Login admin**: solo `required|email` y `required` en password — sin políticas de complejidad.

---

## 5. Base de datos

### Motor utilizado

**SQLite** en desarrollo local (`database/database.sqlite`, confirmado por `DB_CONNECTION=sqlite` en `.env.example` y el default en `config/database.php`). El motor de producción/staging no fue verificado en esta sesión — ver sección 17.

### Esquema completo

**Tablas propias del proyecto** (con migración custom):

#### `categories`
```
id              bigint, PK
name            string
slug            string, unique
parent_id       bigint, nullable, FK → categories.id, ON DELETE SET NULL (nullOnDelete)
icon            string, nullable       (uno de: 'i-cpu', 'i-mouse', 'i-chair', o null)
sort_order      unsigned int, default 0
created_at, updated_at
```
**Auto-referencial** (parent_id apunta a la misma tabla) — así se modela la jerarquía de 2 niveles (padre/hija). `parent_id NULL` = categoría principal (nivel 1); `parent_id` seteado = categoría hija (nivel 2). El código de aplicación (no la base de datos) impide que exista un nivel 3.

#### `products`
```
id              bigint, PK
category_id     bigint, FK → categories.id, ON DELETE CASCADE (cascadeOnDelete)
name            string
slug            string, unique
description     text, nullable
price           decimal(10,2)
currency        enum('USD','BOB'), default 'USD'
sku             string, nullable
stock           unsigned int, default 0
has_variants    boolean, default false
status          enum('active','inactive'), default 'active'
specs           json, nullable          (objeto clave→valor libre, ej. {"Sensor": "PAW3395..."})
image           string, nullable        (ruta relativa dentro del disco 'uploads', ej. "products/xxxx.png")
created_at, updated_at
```
**⚠️ IMPORTANTE**: `category_id` tiene `cascadeOnDelete()`. Si se borra una categoría, **todos sus productos se borran en cascada automáticamente a nivel de base de datos**. `CategoryController::destroy()` protege contra esto verificando manualmente en PHP si hay productos asociados ANTES de intentar el delete — pero esa protección vive solo en el código de la aplicación, no en la base de datos. Cualquier borrado de categoría que NO pase por ese controller (ej. un `tinker` directo, un seeder, un futuro endpoint nuevo que no reutilice esa lógica) puede borrar productos en silencio.

#### `product_variants`
```
id                bigint, PK
product_id        bigint, FK → products.id, ON DELETE CASCADE (cascadeOnDelete)
variant_type      string, default 'Color'   (label libre: "Color", "Tamaño", etc.)
variant_value     string                     (ej. "Blanco", "Negro")
sku               string, nullable
stock             unsigned int, default 0
price_override    decimal(10,2), nullable    (si es null, usa el precio del producto padre)
created_at, updated_at
```

#### `exchange_rates`
```
id          bigint, PK
rate        decimal(10,4)     (BOB por 1 USD)
created_at, updated_at
```
**Tabla de solo-inserción (append-only log), nunca se actualiza ni se borra una fila.** Cada vez que el admin cambia la tasa de cambio en Ajustes, se INSERTA una fila nueva; la tasa "actual" es simplemente `ExchangeRate::latest()->value('rate')` (el registro más reciente por `created_at`/id). Esto significa que **hay un historial completo de tasas de cambio en la base de datos**, aunque hoy nada en la UI lo expone (podría ser la base de un futuro gráfico de histórico de tasa).

#### `settings`
```
id          bigint, PK
key         string, unique
value       text, nullable
created_at, updated_at
```
Almacén genérico clave-valor. Claves usadas actualmente por la aplicación (todas con su propio default hardcodeado en el código que las lee, por lo que la ausencia de la fila NO rompe nada):

| key | default en código | dónde se lee |
|---|---|---|
| `currency_mode` | `both` | `SettingsController`, `ShopController@show` |
| `default_currency` | `USD` | `SettingsController`, `AppServiceProvider` (composer de nav) |
| `whatsapp_number` | `59177947379` | `AppServiceProvider` (dos composers), `CartController` |
| `category_menu_scope` | `shop` | `AppServiceProvider` (composer de nav) |
| `logo_height` | `60` | `AppServiceProvider` (dos composers) |

#### Migración adicional sin tabla nueva
`2026_08_05_120000_add_image_to_products_table.php` — solo agrega la columna `image` a `products` (no crea tabla).

**Tablas de scaffold de Laravel (sin modificar, migraciones por defecto)**:
- `users`, `password_reset_tokens`, `sessions` (una sola migración: `0001_01_01_000000_create_users_table.php`)
- `cache`, `cache_locks` (`0001_01_01_000001_create_cache_table.php`) — usadas porque `CACHE_STORE=database`
- `jobs`, `job_batches`, `failed_jobs` (`0001_01_01_000002_create_jobs_table.php`) — **no hay ningún Job real en el proyecto**, esta infraestructura de colas está sin usar

### Relaciones (Eloquent)

```
Category
  ├─ parent()   : belongsTo(Category::class, 'parent_id')
  ├─ children() : hasMany(Category::class, 'parent_id')->orderBy('sort_order')
  └─ products() : hasMany(Product::class)

Product
  ├─ category() : belongsTo(Category::class)
  └─ variants() : hasMany(ProductVariant::class)

ProductVariant
  └─ product()  : belongsTo(Product::class)
```

`ExchangeRate`, `Setting` y `User` no tienen relaciones (son entidades independientes).

### Modelos — detalle de cada uno

- **`Category`** (`app/Models/Category.php`): `$fillable = ['name','slug','parent_id','icon','sort_order']`. Tiene el scope `scopeParents($query)` que aplica `whereNull('parent_id')->orderBy('sort_order')` — se usa en TODOS los lugares donde se necesita la lista de categorías principales (nav, home, footer, menú flotante).
- **`Product`** (`app/Models/Product.php`): `$fillable` incluye todos los campos de negocio. `$casts`: `specs` → `array` (deserializa el JSON automáticamente), `has_variants` → `boolean`, `price` → `decimal:2`. Tiene dos helpers de conversión de moneda (`priceInUsd(float $rate)`, `priceInBob(float $rate)`) y un accessor `getImageUrlAttribute()` que devuelve `asset('uploads/'.$this->image)` o `null` si no hay imagen — **este accessor es el único punto en todo el código que construye la URL pública de una imagen de producto**; cualquier cambio a cómo se sirven las imágenes debe pasar por acá.
- **`ProductVariant`**: `$fillable = ['product_id','variant_type','variant_value','sku','stock','price_override']`. Sin lógica propia más allá de la relación.
- **`ExchangeRate`**: `$fillable = ['rate']`. Método estático `current(): float` que devuelve `static::latest()->value('rate') ?? 1` — si no hay ninguna fila, el fallback es `1.0` (1 BOB = 1 USD, un valor claramente incorrecto que solo evita un crash pero produciría precios mal calculados; en la práctica siempre hay al menos una fila gracias al seeder).
- **`Setting`**: sin `$fillable` explícito más allá de `['key','value']`. **Cachea agresivamente**: `get()` usa `Cache::rememberForever("setting.{$key}", ...)`, y `set()` hace `updateOrCreate()` seguido de `Cache::forget("setting.{$key}")`. Esto significa que **cualquier cambio a un setting hecho por fuera de `Setting::set()`** (ej. editando la fila directo en la base de datos, o un seeder que haga `Setting::create()` en vez de `Setting::set()`) **no invalidará la caché** y el valor viejo seguirá sirviéndose hasta que se limpie manualmente (`php artisan cache:clear`) o se llame `Setting::set()` con la clave correspondiente.
- **`User`**: modelo `Authenticatable` estándar de Laravel, sin campos ni relaciones custom.

### Migraciones

Ver el listado completo cronológico en la sección 19 (árbol de carpetas). Todas viven en `database/migrations/`, nombradas con timestamp. No hay migraciones "squasheadas", el historial completo desde el inicio del proyecto está intacto.

---

## 6. Panel administrativo

### Qué existe actualmente

El panel vive bajo el prefijo `/admin`, con su propio layout (`admin/layout.blade.php`) completamente separado visualmente del sitio público (sidebar de navegación fija a la izquierda: Productos / Categorías / Usuarios / Ajustes, más un link "Ver sitio ↗" y "Cerrar sesión" al pie).

**Login** (`/admin/login`): formulario simple email+contraseña, sin "recordarme" visible en el HTML actual aunque el controller sí soporta el flag `remember` si se agregara el checkbox.

**Gestión de Productos** (`/admin/productos`):
- Listado paginado (15 por página) con búsqueda por nombre, muestra thumbnail, nombre (+indicador "· variantes" si aplica), categoría, precio formateado según su moneda, badge de estado (Publicado/Privado), y acciones (publicar/despublicar, editar, eliminar con confirmación JS `onsubmit="return confirm(...)"`).
- Formulario de creación/edición (`admin/products/form.blade.php`) — el más complejo del panel:
  - Nombre con auto-generación de slug en vivo (JS quita acentos, minúsculas, reemplaza no-alfanuméricos por guiones) — el slug queda editable manualmente si el usuario lo toca (`dataset.touched`).
  - Selector de categoría (lista TODAS las categorías, indentando visualmente las hijas con "— ").
  - Descripción (textarea libre).
  - Una sola imagen por producto, con preview en vivo (`URL.createObjectURL`) y checkbox para "quitar imagen actual" en edición.
  - Precio + moneda (USD/BOB).
  - SKU, stock, estado (Publicado/Privado).
  - **Especificaciones técnicas**: repeater libre de pares clave/valor (Alpine `x-for` sobre un array `specs`), se serializan a JSON en el campo `products.specs`.
  - **Variantes**: repeater de filas (tipo, valor, stock, precio opcional que sobreescribe el del producto), sincronizadas contra la base de datos en `ProductController::syncVariants()` (actualiza las que ya existen por id, crea las nuevas, borra las que ya no están en el array enviado).
  - Panel de "vista previa" en vivo a la derecha, mostrando cómo se vería la card del producto en la tienda mientras se completa el formulario (reactivo vía Alpine, sin recargar).

**Gestión de Categorías** (`/admin/categorias`) — construida en la sesión de trabajo más reciente:
- Listado jerárquico (categorías padre con sus hijas indentadas debajo), muestra conteo de productos por categoría (`withCount('products')`) y el `sort_order`.
- Formulario de creación/edición: nombre + slug (mismo mecanismo de auto-slug que productos), selector de categoría padre **restringido a solo categorías de nivel superior** (nunca se puede elegir como padre a una categoría que ya es hija de otra — jerarquía de exactamente 2 niveles), selector de ícono (solo 3 opciones hardcodeadas: `i-cpu`/`i-mouse`/`i-chair`, más "genérico"/ninguno) que **se oculta automáticamente si se elige una categoría padre** (los íconos solo tienen sentido visual para categorías de nivel 1, ya que solo esas se muestran en el menú flotante), campo de orden numérico.
- **Protección de borrado en dos niveles**: si la categoría tiene subcategorías propias, o si tiene productos asociados, el borrado se bloquea con un mensaje de error explicando por qué (contando cuántas subcategorías/productos hay) — esto existe específicamente para evitar el borrado en cascada silencioso que permitiría la base de datos (ver sección 5, nota sobre `cascadeOnDelete`).
- Si se intenta convertir en "hija" a una categoría que ya tiene hijas propias, la validación del servidor lo rechaza con un mensaje claro (y el `<select>` de categoría padre se deshabilita directamente en el HTML para esos casos, como refuerzo de UX).

**Usuarios** (`/admin/usuarios`): **solo lectura**. Tabla con nombre, email, fecha de registro. No hay botón de crear, editar ni eliminar usuarios desde la interfaz web — es puramente informativo.

**Ajustes** (`/admin/ajustes`): un único formulario con 5 secciones:
1. Tasa de cambio — muestra la tasa actual, campo opcional para insertar una nueva (si se deja vacío, no cambia nada; si se llena, se inserta una fila nueva en `exchange_rates`, nunca se edita la existente).
2. Visualización de precios — modo de moneda a mostrar en todo el sitio (ambas con selector / solo USD / solo BOB) y cuál es la moneda "principal" por defecto cuando el modo es "ambas".
3. Marca — alto del logo en píxeles (20–120), aplica globalmente vía la variable CSS `--logo-height`.
4. Navegación — alcance del menú flotante de categorías (solo en /tienda vs. en todo el sitio).
5. WhatsApp — el número al que llegan los pedidos armados desde el carrito.

### Qué puede editar

Productos (todo su ciclo de vida completo), Categorías (todo su ciclo de vida completo, con reglas de jerarquía), los 5 ajustes globales listados arriba. Puede ver (no editar) la lista de usuarios registrados.

### Qué NO puede editar

- No puede crear, editar ni eliminar usuarios desde la UI (solo por SSH + `artisan admin:create`).
- No puede editar el contenido de ninguna página estática (no existen — ver sección 7). No hay "Nosotros", "Garantía", ni ningún tipo de página de contenido editable.
- No puede ver ni gestionar pedidos — **no existe el concepto de "pedido" persistido en la base de datos en absoluto**. El carrito es efímero (solo vive en la sesión del navegador del cliente) y una vez que el cliente hace clic en "Finalizar por WhatsApp", esa información nunca se guarda en la base de datos de Cyrex — solo existe como texto en un mensaje de WhatsApp.
- No puede subir múltiples imágenes por producto (solo una imagen principal, sin galería).
- No puede hacer acciones masivas (bulk edit/delete) sobre productos o categorías.
- No puede importar/exportar catálogo (sin CSV, sin Excel).
- No tiene ningún dashboard con métricas/estadísticas — la ruta raíz del admin (`/admin/`) redirige directo a la lista de productos, no hay una vista de "resumen" en ningún lado.
- No puede gestionar cupones, descuentos ni promociones (no existe esa funcionalidad).
- No puede ver un histórico/log de qué cambió y cuándo (no hay auditoría de actividad).
- No puede recuperar su contraseña desde la UI (el flujo de "olvidé mi contraseña" no está conectado, aunque la infraestructura de Laravel para eso existe latente).

### Cómo funciona (mecánica interna)

Cada sección del admin sigue el mismo patrón CRUD estándar de Laravel: `index()` lista + pagina + filtra, `create()` muestra formulario vacío, `store()` valida y persiste, `edit()` muestra formulario prellenado, `update()` valida y persiste, `destroy()` elimina (con guardas de negocio donde aplica). Las vistas de formulario reutilizan la misma plantilla Blade tanto para crear como editar (se distingue por `$model->exists`). El feedback al usuario es vía flash messages de sesión (`session('status')` para éxito, `session('error')` para fallos de negocio como el intento de borrar una categoría con productos), renderizados en el layout admin como una barra superior con estilo (`.admin-flash` / `.admin-flash-error`).

### Qué falta implementar

- Gestión de usuarios completa (crear/editar/eliminar/roles desde la UI).
- Algún tipo de registro/historial de pedidos (hoy no hay ningún rastro en base de datos de lo que se pidió por WhatsApp).
- Editor de páginas de contenido estático (Nosotros, Garantía, Políticas de envío — mencionadas como proyecto futuro en conversaciones previas, no comenzadas).
- Galería de múltiples imágenes por producto.
- Dashboard con métricas (productos más vistos, tasa de conversión, etc. — no hay ni siquiera tracking de analítica instalado).
- Gestión de "franjas de confianza" del footer (garantía, envíos, horario) — pendiente porque el dueño del negocio todavía no definió esos datos reales; el código está preparado para agregarlas cuando se confirmen (ver footer, sección 9).
- Bulk actions, import/export CSV.
- Roles y permisos diferenciados entre usuarios admin.

---

## 7. Sistema de páginas

### ¿Cómo se crean actualmente?

**No hay ningún sistema dinámico de páginas ni CMS.** Cada página pública es:
1. Una entrada explícita en `routes/web.php` (`Route::get('/ruta', [Controller::class, 'metodo'])->name('nombre')`).
2. Un método en un Controller que arma los datos necesarios y devuelve `view('nombre-de-vista', compact(...))`.
3. Un archivo `.blade.php` en `resources/views/` que hace `@extends('layouts.app')` y define `@section('content')`.

No hay resolución de contenido por slug genérico (salvo el caso específico de `/producto/{slug}`, que es product-specific, no una página de contenido libre). No hay una tabla `pages` en la base de datos. No hay ningún editor WYSIWYG ni Markdown-to-HTML en ningún punto del proyecto.

### ¿Son archivos?

Sí, 100% archivos `.blade.php` estáticos versionados en Git. Cambiar el contenido de una página requiere editar el código y desplegar.

### ¿Son componentes?

No, en el sentido de Blade Components (`<x-foo />`) no se usan en absoluto. Son partials tradicionales incluidos con `@include('partials.nombre')`.

### ¿Son dinámicas?

**Parcialmente.** El HTML es estático (hardcodeado en el `.blade.php`), pero los DATOS que rellenan ese HTML sí son dinámicos (vienen de la base de datos vía el Controller). Por ejemplo, `home.blade.php` es un archivo fijo, pero la lista de categorías y de "Recién llegados" que muestra sale de consultas reales a `categories`/`products`.

### ¿Hay un CMS?

**No.** Cero CMS, cero page builder, cero forma de que alguien sin acceso al código agregue una página nueva o cambie el copy de una existente (más allá de lo que específicamente esté expuesto en Ajustes del admin, que es un puñado de settings puntuales, no contenido de página).

**Implicación para features futuras**: si se pide algo como "que Danilo pueda editar el texto del hero de la home sin tocar código", eso requeriría construir desde cero una tabla + UI de administración para ese contenido — hoy no existe ningún mecanismo reutilizable para eso.

---

## 8. Header

### ¿Cómo está construido?

El header vive en un único archivo: **`resources/views/partials/nav.blade.php`** (139 líneas). Contiene, en este orden:

1. Un `<script>` inline que registra `Alpine.store('cart', {...})` en el evento `alpine:init` (ver sección 3, "Gestión de estados"). Este es el ÚNICO lugar de todo el proyecto donde se define ese store — vive acá y no en un archivo JS separado.
2. Un `<div x-data="{ mobileOpen: false }">` que envuelve TODO lo que sigue (nav de escritorio, drawer mobile, drawer del carrito), para que todos compartan el mismo scope de Alpine y puedan reaccionar juntos a la tecla Escape (`x-on:keydown.escape.window="mobileOpen = false; $store.cart.open = false"`).
3. El `<nav>` de escritorio: botón hamburguesa (oculto en desktop, visible ≤860px), logo (imagen real, clase `.logo-full` ligada a la variable CSS `--logo-height`), links "Inicio"/"Tienda" (ocultos ≤860px), el buscador (`@include('partials.search-box')`, oculto ≤860px), y un bloque `.nav-actions` con: botón de WhatsApp, indicador "En línea", botón/ícono de carrito con badge, y el CTA "Ver tienda" (oculto ≤860px).
4. El drawer mobile (`<aside class="mobile-drawer">`): logo, botón cerrar, el mismo buscador reutilizado con otra clase de formulario, y el acordeón de categorías.
5. `@include('partials.cart-drawer')` — el panel lateral del carrito.
6. Al final del archivo, **fuera** del `<div x-data>` principal, la inclusión condicional del menú flotante de categorías: `@if($categoryMenuScope === 'all' || request()->routeIs('shop')) @include('partials.category-float') @endif`.

### ¿Dónde vive?

`resources/views/partials/nav.blade.php`, incluido una única vez desde `resources/views/layouts/app.blade.php` vía `@include('partials.nav')`, inmediatamente después del `<body>` y antes del `<main>`.

### ¿Es reutilizable?

Sí, en el sentido de que **cualquier página nueva que haga `@extends('layouts.app')` obtiene el header automáticamente**, sin que su Controller necesite pasar ningún dato relacionado al header. Esto es posible gracias al View Composer (ver abajo). El admin NO usa este header — tiene el suyo propio completamente distinto (sidebar) en `admin/layout.blade.php`.

### ¿Cómo se renderiza? (el detalle más importante)

Los datos que `nav.blade.php` necesita (`$navCategories`, `$categoryMenuScope`, `$whatsappNumber`, `$cartItems`, `$cartCount`, `$cartCurrency`, `$cartTotal`, `$cartWhatsappUrl`, `$logoHeight`) **nunca son pasados por ningún Controller**. Se inyectan automáticamente mediante un **View Composer** registrado en `app/Providers/AppServiceProvider.php::boot()`:

```php
View::composer('partials.nav', function ($view) {
    $rate = ExchangeRate::current();
    $currency = Setting::get('default_currency', 'USD');
    $whatsappNumber = Setting::get('whatsapp_number', '59177947379');

    $view->with([
        'navCategories' => Category::parents()->with('children')->get(),
        'categoryMenuScope' => Setting::get('category_menu_scope', 'shop'),
        'whatsappNumber' => $whatsappNumber,
        'cartItems' => Cart::items(),
        'cartCount' => Cart::count(),
        'cartCurrency' => $currency,
        'cartTotal' => Cart::total($rate, $currency),
        'cartWhatsappUrl' => Cart::whatsappMessage($whatsappNumber, $rate, $currency),
        'logoHeight' => Setting::get('logo_height', '60'),
    ]);
});
```

Este composer se ejecuta **cada vez que Laravel está a punto de renderizar la vista `partials.nav`**, sin importar desde qué Controller/ruta se llegó ahí. Esto es lo que garantiza que el header (categorías, carrito, WhatsApp, logo) sea consistente en absolutamente todas las páginas del sitio público sin trabajo adicional por página.

**Implicación para features futuras**: si se agrega un dato nuevo que el header necesite, la forma correcta es agregarlo a este composer — nunca pasarlo manualmente desde un Controller específico, porque entonces solo funcionaría en esa página y rompería la consistencia.

---

## 9. Footer

### ¿Cómo está construido?

A diferencia del header, **el footer NO es un partial separado** — vive inline directamente dentro de `resources/views/layouts/app.blade.php` (líneas 23–61 de ese archivo). Esta es una inconsistencia arquitectónica real del proyecto (el header sí se extrajo a su propio archivo, el footer no) y vale la pena tenerla presente si se decide refactorizar.

Estructura: un `<div class="footer-grid">` con 4 bloques —
1. **`.footer-brand`**: logo (clase propia `.footer-logo`, tamaño fijo de 40px, **deliberadamente independiente** de la variable `--logo-height` que controla el logo del header — así un cambio de tamaño de logo en Ajustes no descontrola el footer), tagline de texto fijo, botón de WhatsApp.
2. **Columna "Tienda"**: itera `$footerCategories` (categorías reales de nivel superior) + un link fijo "Ver todo el catálogo".
3. **Columna "Ayuda"**: link de WhatsApp + texto fijo de ubicación ("Santa Cruz y Cochabamba, Bolivia").
4. **Columna "Cyrex"**: links fijos a Inicio y Tienda.

Debajo, `.footer-bottom`: copyright con año dinámico (`{{ date('Y') }}`) y un link a `/admin` (**nota de seguridad**: esto anuncia públicamente dónde está el login del panel administrativo — es una decisión existente, no un error introducido ahora, pero vale la pena que quien planee nuevas features la conozca).

### ¿Dónde vive?

Inline en `resources/views/layouts/app.blade.php`. El admin no tiene footer propio (su layout no incluye ninguno).

### ¿Es reutilizable?

Sí, automáticamente, por estar en el layout compartido — igual que el header, cualquier página que extienda `layouts.app` lo obtiene gratis.

### ¿Cómo se renderiza?

Igual mecanismo que el header: un View Composer en `AppServiceProvider::boot()`, pero registrado sobre `layouts.app` (no sobre un partial propio, porque el footer no tiene uno):

```php
View::composer('layouts.app', function ($view) {
    $view->with([
        'logoHeight' => Setting::get('logo_height', '60'),
        'footerCategories' => Category::parents()->get(),
        'whatsappNumber' => Setting::get('whatsapp_number', '59177947379'),
    ]);
});
```

Nótese que esto consulta las categorías de nivel superior **por segunda vez**, de forma completamente separada del composer del header (que también las trae, con `->with('children')` además). Es una duplicación de query deliberadamente simple en vez de compartir una sola fuente — funcionalmente inofensiva (es una query barata sobre pocas filas) pero es algo que una IA que quiera "limpiar" el código podría verse tentada a unificar; si lo hace, debe tener cuidado de no romper el hecho de que el composer de `partials.nav` necesita `children` y el de `layouts.app` no.

**Pendiente conocido**: el dueño del negocio pidió una "franja de confianza" (garantía, envíos, horario de atención) para agregar al footer, pero se dejó explícitamente pendiente porque esos datos de negocio todavía no están definidos. Cuando se definan, el lugar natural para agregarlos es un nuevo bloque dentro de `.footer-grid` (o una fila nueva antes de `.footer-bottom`), sin necesidad de tocar el composer salvo para inyectar los textos si se decide que sean configurables desde Ajustes en vez de hardcodeados.

---

## 10. Componentes importantes

Lista completa de todos los partials Blade reutilizables del proyecto, con su propósito exacto:

| Archivo | Para qué sirve |
|---|---|
| `partials/nav.blade.php` | El header completo: barra de navegación de escritorio, drawer mobile con acordeón de categorías, y la definición del store global del carrito de Alpine. Ver sección 8. |
| `partials/category-float.blade.php` | Menú flotante de categorías, fijo a la izquierda de la pantalla (solo desktop, se oculta ≤860px), con flyout al hacer hover sobre cada categoría mostrando sus hijas. Incluye la burbuja de onboarding para usuarios nuevos (gateada por cookie `cyrex_cat_hint_seen`). Se incluye condicionalmente desde `nav.blade.php` según el setting `category_menu_scope`. |
| `partials/category-icon.blade.php` | Resuelve un string de ícono (`i-cpu`/`i-mouse`/`i-chair`) a su SVG inline correspondiente, con un ícono genérico de respaldo (círculo) si no matchea ninguno. Reutilizado en: el acordeón mobile del header, el menú flotante de categorías, y el listado de categorías del admin. |
| `partials/search-box.blade.php` | Buscador predictivo con debounce de 250ms contra `/buscar-sugerencias`. Reutilizado DOS veces: una vez para la barra de escritorio (`$formClass` default `nav-search`) y otra vez dentro del drawer mobile (`$formClass = 'mobile-search'`) — mismo componente, distinta clase CSS de contenedor pasada como parámetro. |
| `partials/cart-drawer.blade.php` | El shell del panel lateral del carrito (overlay + `<aside>`), controlado por `$store.cart.open`. Delega el contenido interno a `cart-drawer-content`. |
| `partials/cart-drawer-content.blade.php` | La lista de ítems del carrito + total + botón "Finalizar por WhatsApp". **Se renderiza en dos contextos distintos**: (1) como parte del HTML normal de la página al cargarla, y (2) como fragmento JSON devuelto por `CartController` en cada add/remove, que el JS del store inyecta vía `innerHTML` sin recargar la página. Cualquier cambio a este archivo afecta ambos casos automáticamente porque es la misma plantilla. |
| `partials/pagination.blade.php` | Paginador custom minimalista (← Anterior / Página X de Y / Siguiente →), usado en vez del paginador Tailwind por defecto de Laravel en TODOS los listados paginados del proyecto (tienda, productos admin, usuarios admin). |
| `layouts/app.blade.php` | El layout raíz del sitio público (no es un "componente" incluible, pero es la pieza compartida más importante del frontend). |
| `admin/layout.blade.php` | El layout raíz del panel admin, completamente aislado de `layouts/app.blade.php`. |

No hay ningún componente reutilizable para tarjetas de producto (`.card`) como partial — la card de producto (imagen, categoría, nombre, precio) está **duplicada como HTML inline** en al menos 3 lugares distintos: `home.blade.php` (sección "Recién llegados"), `shop.blade.php` (grid principal), y `product.blade.php` (sección "También te puede interesar"). Es candidato obvio a extraerse a un partial `partials/product-card.blade.php` si se sigue iterando sobre el diseño de las cards — hoy cualquier cambio de diseño de card debe replicarse a mano en los 3 lugares.

---

## 11. Integraciones

- **WhatsApp**: la única integración externa real del proyecto, y no es una integración de API en sentido estricto — es la construcción de un link `https://wa.me/{numero}?text={mensaje urlencodeado}`. No hay credenciales, no hay WhatsApp Business API, no hay webhooks entrantes ni salientes, no hay confirmación de entrega del mensaje. El número de destino es configurable desde Ajustes (`Setting::get('whatsapp_number', ...)`). La construcción del mensaje del carrito vive en `App\Support\Cart::whatsappMessage()`.
- **Google Fonts**: CDN externo (`fonts.googleapis.com`) para Space Grotesk, Inter y JetBrains Mono. No es self-hosted.
- **jsDelivr CDN**: sirve `alpinejs@3.x.x` y `@alpinejs/collapse@3.x.x`. No hay integridad de subrecursos (SRI hash) configurada en esos `<script>` tags.

**Integraciones que NO existen** (todas las preguntadas explícitamente):
- **Pagos**: ninguna. Sin Stripe, sin PayPal, sin pasarela boliviana, sin nada — decisión de negocio permanente (ver `VISION.md`).
- **Correo**: `MAIL_MAILER=log` en `.env.example` — cualquier email que Laravel intentara enviar (ninguno lo hace actualmente en la práctica, ya que no hay notificaciones ni flujo de recuperación de contraseña activo) solo escribiría al archivo de log, nunca se enviaría de verdad.
- **Maps**: ninguna, no hay ningún mapa ni geolocalización en el sitio.
- **Cloudinary**: no, las imágenes se guardan en disco local del servidor (ver sección 12).
- **Supabase**: no.
- **Firebase**: no, ni para auth ni para analytics ni para push notifications.
- **Analítica**: no hay Google Analytics, Meta Pixel, ni ningún tracker instalado en ninguna vista.

---

## 12. Sistema de imágenes

### Dónde se almacenan

Las imágenes de producto se guardan en **disco local del servidor** (no en un servicio cloud), a través de un disco de Laravel llamado `uploads`, definido en `config/filesystems.php`:

```php
'uploads' => [
    'driver' => 'local',
    'root' => env('UPLOADS_DISK_ROOT', public_path('uploads')),
    'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/uploads',
    'visibility' => 'public',
    'serve' => true,
    'throw' => false,
    'report' => false,
],
```

**Esta configuración es el resultado de un incidente real resuelto en la sesión de trabajo más reciente**, y es importante que cualquier IA que toque este código entienda el problema original antes de modificarlo:

**El problema descubierto**: el despliegue automático de Hostinger (vía Git) reconstruye/resetea el directorio del proyecto en el servidor en cada `git push`. Como la carpeta `public/uploads/products/` nunca está versionada en Git (correctamente — no se versionan archivos subidos por usuarios), **cada deploy borraba silenciosamente todas las imágenes de producto que el admin había subido**, dejando solo el archivo `.gitignore` placeholder. Se confirmó este comportamiento verificando por SSH que la carpeta en el servidor de producción contenía solo el placeholder tras varios deploys, mientras que la base de datos seguía referenciando archivos que ya no existían físicamamente (algunas imágenes seguían "cargando" para el dueño solo por caché de LiteSpeed a nivel de servidor, no porque el archivo siguiera existiendo).

**La solución implementada**: el `root` del disco ahora es configurable vía la variable de entorno `UPLOADS_DISK_ROOT`. En local, esa variable no está seteada, así que cae al comportamiento anterior (`public_path('uploads')`, dentro del propio proyecto). **En producción, esa variable debe apuntar a una carpeta FUERA del directorio que Git despliega/resetea** (ej. un directorio hermano en el servidor, fuera de `cyrex-app/`), de forma que ningún deploy futuro vuelva a tocarla. Como esa carpeta ya no está dentro de `public/`, el servidor web (Apache/LiteSpeed) ya no puede servirla como archivo estático directo — por eso se activó `'serve' => true`, una funcionalidad nativa de Laravel 11+/12 que registra automáticamente una ruta (`GET /uploads/{path}`) que sirve el archivo a través del propio framework (clase interna `Illuminate\Filesystem\ServeFile`), sin importar en qué carpeta física esté guardado. Esta ruta se ve reflejada en `php artisan route:list` como `storage.uploads` (el nombre lo genera Laravel automáticamente, no es un nombre elegido por el proyecto).

**Importante para cualquier feature nueva de subida de archivos** (ej. si se agrega galería de imágenes, o logos de marca subibles, etc.): debe reutilizar este mismo disco `uploads` (o crear un disco nuevo siguiendo exactamente el mismo patrón de `root` configurable por env + `serve: true`), nunca asumir que guardar directo en `public/algo` es seguro en este hosting.

### Cómo se cargan

Subida: formulario HTML con `enctype="multipart/form-data"`, campo `<input type="file" name="image">`, validado server-side (`image`, `mimes:jpg,jpeg,png,webp`, `max:4096` KB). El nombre de archivo final es aleatorio (`Str::random(20).'.'.$extension`), nunca el nombre original del archivo subido — esto evita colisiones de nombre y cualquier intento de path traversal vía nombre de archivo.

Lectura/visualización: `Product::getImageUrlAttribute()` arma la URL pública con `asset('uploads/'.$this->image)`. Todas las vistas (`home`, `shop`, `product`, `cart-drawer-content`, admin `products/index`, admin `products/form` para el preview) acceden a la imagen exclusivamente a través de `$product->image_url` — nunca acceden al campo crudo `$product->image` directamente para construir una URL.

### Cómo se optimizan

**No se optimizan en absoluto.** No hay ninguna librería de procesamiento de imágenes instalada (ni Intervention Image, ni GD/Imagick usado manualmemte). Las imágenes se guardan exactamente como las sube el admin, en su resolución y peso originales (hasta el límite de 4MB del validador). No se generan thumbnails, no hay versiones responsive (`srcset`), no hay conversión automática a WebP, no hay compresión. Esto es una oportunidad de mejora de performance real (ver sección 15).

---

## 13. SEO

**Prácticamente inexistente.** Inventario completo de lo que SÍ existe:
- `<title>` dinámico por página vía `@yield('title', 'Cyrex Store')`, con títulos descriptivos razonables por vista (ej. `"{$product->name} — Cyrex Store"`).
- `public/robots.txt` existe pero es el default más permisivo posible (`User-agent: *` / `Disallow:` vacío — permite indexar todo), sin ninguna referencia a un sitemap.

**Lo que NO existe** (todo lo demás):
- Sin `<meta name="description">` en ninguna página.
- Sin Open Graph tags (`og:title`, `og:image`, `og:description`) — compartir un link de producto en WhatsApp/redes no mostrará preview enriquecido.
- Sin Twitter Cards.
- Sin datos estructurados / JSON-LD (nada de `schema.org/Product`, que sería el más valioso para e-commerce — precios y disponibilidad no aparecerán en rich snippets de Google).
- Sin `sitemap.xml`.
- Sin `<link rel="canonical">`.
- Sin manejo de URLs canónicas para filtros de categoría/búsqueda (`/tienda?category=x&q=y` no tiene canonical hacia `/tienda?category=x`, lo que podría generar contenido duplicado a ojos de buscadores).
- Sin `alt` descriptivo consistente en imágenes (algunas usan `{{ $product->name }}`, otras usan `alt=""` vacío intencional en logos decorativos — es aceptable en esos casos puntuales, pero no hay una política unificada).

---

## 14. Seguridad

### Autenticación

Ver sección 4 en detalle. Resumen de puntos relevantes de seguridad: sesión de Laravel estándar con regeneración de sesión en login/logout (previene session fixation), contraseñas hasheadas con bcrypt (`Hash::make`, `BCRYPT_ROUNDS=12`), sin roles/permisos (cualquier usuario = admin total).

### Permisos

No existen niveles de permiso — es binario: o se tiene sesión autenticada (acceso total a `/admin/*`), o no se tiene ninguna (solo acceso público). No hay ACL, no hay policies de Laravel (`php artisan make:policy` no se usó en ningún modelo).

### Protecciones existentes

- **CSRF**: protección estándar de Laravel activa en todas las rutas del grupo `web` (que es donde vive todo). Los formularios Blade usan `@csrf`; las llamadas `fetch()` del carrito mandan el header `X-CSRF-TOKEN` manualmente.
- **Mass assignment**: todos los modelos declaran `$fillable` explícito — no hay ningún modelo con `$guarded = []` sin restricción.
- **SQL injection**: no hay queries SQL crudas (`DB::statement`, `DB::raw` con interpolación de variables) en ningún controller inspeccionado — todo pasa por el Query Builder/Eloquent de Laravel, que parametriza automáticamente.
- **XSS**: Blade escapa por defecto con `{{ }}` (equivalente a `htmlspecialchars`). No se detectó ningún uso de `{!! !!}` (salida sin escapar) en las vistas revisadas — es decir, no hay puntos conocidos donde contenido de usuario se inyecte como HTML crudo.
- **Validación de archivos subidos**: tipo MIME + extensión + tamaño máximo validados server-side antes de guardar; nombre de archivo siempre generado por el servidor (nunca se usa el nombre original del cliente).
- **HTTPS**: gestionado a nivel de Hostinger/hosting, no hay lógica propia de la aplicación que fuerce redirect a HTTPS (`URL::forceScheme` no está configurado en ningún ServiceProvider) — si el proxy/balanceador de Hostinger no pasa correctamente el header de protocolo, podría generar URLs `http://` en un sitio servido por `https://` (mixed content). No confirmado como problema activo, pero es un punto ciego no verificado.

### Puntos débiles conocidos (a tener en cuenta antes de escalar el proyecto)

- **Sin rate limiting en el login** (`POST /admin/login` no tiene `throttle` middleware aplicado) — vulnerable a fuerza bruta de contraseña, agravado por el mínimo de 4 caracteres permitido por `admin:create`.
- **Contraseña mínima de 4 caracteres** — aceptable únicamente porque hoy es un solo usuario de confianza (el propio dueño) en un entorno de "demo"; **no debe mantenerse así si se habilita más de un usuario admin o si el sitio pasa a producción real con más gente involucrada**.
- **El link a `/admin` está en el footer público** — anuncia la ubicación del login a cualquier visitante (no es una vulnerabilidad en sí, pero reduce la fricción para intentos de fuerza bruta).
- **Sin 2FA** en ningún punto.
- **Sin límite de intentos fallidos de login** ni bloqueo temporal de cuenta.

---

## 15. Rendimiento

### Lazy loading

**No implementado.** Ninguna etiqueta `<img>` del proyecto usa el atributo `loading="lazy"` — todas las imágenes de producto en grids largos (tienda, home) se cargan de forma eager por defecto del navegador.

### Code splitting

No aplica en el sentido de JS bundling (no hay bundler activo). Alpine.js se carga completo desde CDN en cada página (no hay carga condicional por ruta).

### Cache

- **Cache de aplicación**: `CACHE_STORE=database` (tabla `cache`/`cache_locks`) — no Redis, no Memcached. Se usa exclusivamente para `Setting::get()` (`Cache::rememberForever`), que es lo único cacheado explícitamente en todo el proyecto.
- **Cache de assets estáticos**: `app.css`/`admin.css` se sirven con un query string de cache-busting basado en `filemtime()` (`?v={{ filemtime(public_path('css/app.css')) }}`) — esto **fuerza** que el navegador pida el archivo de nuevo cada vez que cambia (bueno para no servir CSS viejo tras un deploy), pero no hay una estrategia de cache HTTP de larga duración con invalidación por hash de contenido (versionado real de assets) — es una solución simple y efectiva pero no la más sofisticada.
- **Cache de vistas Blade**: el compilado estándar de Laravel (`storage/framework/views`), gestionado automáticamente por el framework — nada custom.
- **Sin cache de queries** más allá de Settings (los listados de productos/categorías se consultan frescos en cada request).

### Optimización

- Fuentes de Google cargadas con `display=optional` en el sitio público (específicamente para evitar el salto de layout / FOUT que se detectó y corrigió durante el desarrollo — con `display=swap` el botón "Agregar al carrito" y otros elementos "saltaban" al cargar la fuente).
- Paginación aplicada en todos los listados largos (tienda: 12/página, productos admin: 15/página, usuarios admin: 20/página) — evita cargar catálogos completos de una vez.
- Debounce de 250ms en el buscador predictivo — evita una petición de red por cada tecla presionada.
- **No hay** minificación de CSS/JS propio (los archivos `.css` se sirven tal cual, sin minificar, ya que no hay build step).
- **No hay** compresión de imágenes ni generación de tamaños responsive (ver sección 12).
- **No hay** ningún CDN propio para assets estáticos ni para imágenes — todo se sirve directo desde el servidor Hostinger.

---

## 16. Cosas pendientes

Lista consolidada de todo lo que se sabe que falta (compilada de todas las secciones anteriores, más lo explícitamente diferido en conversaciones de planificación):

- Sistema de cuentas de cliente (registro/login de compradores) — explícitamente diferido como proyecto futuro separado.
- Páginas de contenido estático: "Nosotros", "Garantía", políticas de envío — diferidas, sin fecha, y sin ningún sistema de páginas que las soporte todavía (ver sección 7).
- Franja de "confianza" en el footer (garantía/envíos/horario) — código listo para recibirla, bloqueada solo por falta de datos de negocio reales.
- Gestión de usuarios admin desde la UI (crear/editar/eliminar/roles).
- Algún registro persistente de pedidos (hoy el carrito es 100% efímero y no queda rastro en base de datos tras enviarse por WhatsApp).
- Galería de múltiples imágenes por producto.
- Dashboard de métricas en el admin.
- Bulk actions e import/export de catálogo.
- SEO técnico completo (meta descriptions, Open Graph, JSON-LD, sitemap).
- Optimización/redimensionado automático de imágenes subidas.
- Rate limiting en login, 2FA, políticas de contraseña más estrictas (condicionadas a cuando haya más de un usuario admin real).
- Analítica de cualquier tipo (no hay ningún tracker instalado hoy).
- Cupones/descuentos/promociones.
- Extraer la card de producto duplicada en 3 vistas a un partial único.
- Limpieza del scaffold de Vite/Tailwind sin uso (`package.json`, `vite.config.js`, `resources/css/app.css`, `resources/js/*`).
- Confirmar y documentar el motor de base de datos real de producción (ver sección 17).

---

## 17. Limitaciones actuales

### Problemas conocidos

1. **El motor de base de datos de producción no está confirmado en este documento.** Todo lo escrito sobre SQLite corresponde con certeza al entorno de desarrollo local; nadie verificó el `.env` real del servidor `dev.cyrexstore.com` durante la sesión de trabajo que generó este documento. Antes de diseñar cualquier feature que dependa de comportamiento específico de un motor de base de datos (ej. funciones de texto completo, JSON queries avanzadas), hay que confirmarlo por SSH.
2. **El deploy automático de Hostinger resetea/reconstruye la carpeta del proyecto en cada push**, lo cual ya causó pérdida real de datos (imágenes de producto) una vez, resuelto moviendo el disco de uploads fuera del árbol desplegado (ver sección 12). **Cualquier otra carpeta que la aplicación empiece a escribir en runtime dentro del árbol del proyecto corre el mismo riesgo** (ej. si en el futuro se generan PDFs, exports, thumbnails cacheados, etc. dentro de `storage/app/public` o `public/`, sin aplicar el mismo patrón de "carpeta externa configurable por env", podrían perderse en el próximo deploy).
3. **El deploy NO corre migraciones automáticamente** — solo `composer install`. Cada vez que una migración nueva se sube a `main`, hay que entrar por SSH y correr `php artisan migrate --force` manualmente. Es fácil de olvidar.
4. **El `.env` de producción se gestiona 100% manual por SSH**, nunca por Git (correctamente, por seguridad). Esto significa que cualquier variable de entorno nueva que el código empiece a necesitar (como pasó con `UPLOADS_DISK_ROOT`) **no llega sola al servidor** — hay que agregarla a mano y correr `php artisan config:clear` después.
5. **La carpeta de despliegue real en el servidor tiene nombres de carpetas duplicados/confusos** (`cyrex-app`, `cyrex-app-old-backup`, `cyrex-app-git` conviviendo bajo `~/domains/cyrexstore.com/public_html/dev/`) — solo `cyrex-app` está confirmada como la que sirve el sitio real; las otras dos son remanentes de configuración inicial que nunca se limpiaron. Alguien que investigue el servidor por SSH sin este contexto podría perder tiempo o, peor, editar la carpeta equivocada.
6. **La caché de `Setting` puede quedar desincronizada** si algún código nuevo escribe en la tabla `settings` sin pasar por `Setting::set()` (ver sección 5) — cualquier feature nueva que necesite leer/escribir settings debe usar siempre esos dos métodos estáticos, nunca el modelo Eloquent directo.
7. **`ExchangeRate::current()` devuelve `1.0` como fallback si no hay ninguna fila** — un valor silenciosamente incorrecto que nunca debería alcanzarse en producción (el seeder siempre inserta una fila inicial) pero que no tiene ninguna alerta/log si ocurriera.

### Decisiones técnicas que podrían complicar futuras funciones

- **La jerarquía de categorías está limitada a exactamente 2 niveles por lógica de aplicación (PHP), no por la estructura de la base de datos** (que técnicamente permitiría una cadena infinita de `parent_id`). Cualquier feature que necesite 3+ niveles requeriría reescribir tanto las validaciones del `CategoryController` como toda la lógica de renderizado del menú (nav, footer, home, menú flotante), que en varios lugares asume explícitamente "padre" e "hija" como los únicos dos niveles posibles (ej. `Category::parents()` da por sentado que "sin padre" = "es una categoría de tope").
- **El carrito no soporta cantidades por producto** — es una decisión de negocio deliberada (VISION.md lo documenta explícitamente), pero significa que agregar soporte de cantidades en el futuro no es un simple "agregar un campo" — hay que revisar `App\Support\Cart` completo (la clave del array es `"{product_id}:{variant_id}"`, sin espacio para una cantidad), el store de Alpine, el drawer del carrito, y el armado del mensaje de WhatsApp.
- **No existe un concepto de "pedido" persistido** — si en el futuro se quiere ANY tipo de historial de ventas, reportes, o simplemente que Danilo pueda ver "qué se pidió esta semana" desde el admin, hay que diseñar esa tabla desde cero; hoy no hay ni un esqueleto de eso.
- **La imagen es singular por producto** (columna `image` en `products`, no una tabla `product_images` separada) — agregar galería requiere una migración de esquema (nueva tabla) y no es retrocompatible de forma trivial con el código actual que asume `$product->image` como un string único.
- **Las especificaciones técnicas (`specs`) son JSON libre sin esquema** (`array` cast, sin validación de estructura más allá de "clave y valor no vacíos") — no hay forma de filtrar/buscar productos por spec (ej. "todos los procesadores con 8 núcleos") sin parsear JSON en cada fila, ya que no hay columnas normalizadas para specs.
- **No hay Form Requests ni Service classes** — la lógica de validación y de negocio vive directamente en los Controllers. Esto es manejable con el tamaño actual del proyecto (5 controllers admin, 3 públicos) pero escalaría mal si se agregan muchos más recursos sin refactorizar hacia una capa de servicios.

---

## 18. Escalabilidad

### ¿Está preparado el proyecto para crecer?

**Para el alcance actual (catálogo + WhatsApp, un solo administrador, tráfico de una tienda regional en Bolivia), sí, es una arquitectura razonable y deliberadamente simple** — no hay sobre-ingeniería, y eso es una decisión consciente documentada en `VISION.md` ("menos es más"). Para crecer más allá de ese alcance, hay puntos concretos que necesitarían trabajo:

### Qué partes habría que refactorizar

1. **Capa de servicios**: si el número de reglas de negocio crece (más validaciones cruzadas tipo las de categorías, más flujos tipo el del carrito), extraer lógica de los Controllers hacia clases de servicio dedicadas evitaría que los Controllers se vuelvan inmanejables. Hoy `ProductController` ya tiene 7 métodos privados de soporte (`storeImage`, `deleteImage`, `specsFromRequest`, `variantsFromRequest`, `syncVariants`, `validated`) — es el candidato más claro a necesitar esto primero si se le agregan más responsabilidades.
2. **Base de datos**: si el motor real de producción sigue siendo SQLite, sería el primer cuello de botella real ante concurrencia de escritura (SQLite serializa escrituras) — migrar a MySQL/MariaDB sería el paso natural antes de cualquier crecimiento serio de tráfico administrativo concurrente (aunque para lecturas públicas de un catálogo pequeño, SQLite puede sostener bastante tráfico sin problema).
3. **Sistema de páginas**: si el negocio pide más páginas de contenido (Nosotros, Garantía, blog, etc.) de forma recurrente, seguir creando archivos `.blade.php` + rutas a mano dejará de ser sostenible rápido — valdría la pena construir un modelo `Page` simple (slug, título, contenido HTML/Markdown, meta) con una ruta catch-all y un editor básico en el admin, en vez de seguir hardcodeando.
4. **Imágenes**: agregar procesamiento (Intervention Image o similar) para generar thumbnails y variantes responsive antes de que el catálogo crezca a cientos de productos con imágenes pesadas sin optimizar — hoy cada imagen se sirve en su resolución/peso original sin importar dónde se muestre (thumbnail 40px en admin vs. imagen completa en la ficha de producto usan el MISMO archivo).
5. **Cache de queries de catálogo**: hoy cada request a `/tienda` o `/` golpea la base de datos sin cache — para un catálogo que crezca (recordar que el negocio real tiene ~82 categorías documentadas en `VISION.md`, mientras que el seed actual solo tiene 3 padres/~23 hijas — el dataset real es bastante más grande que el de prueba), cachear el árbol de categorías (que cambia poco) evitaría releerlo en cada request del composer del header/footer.
6. **Separar el footer a un partial propio** (`partials/footer.blade.php`) por consistencia con el header, si el footer sigue creciendo en complejidad (la franja de confianza pendiente, por ejemplo).
7. **Roles y permisos**: trivial de posponer mientras sea un solo usuario, pero bloqueante en el momento en que se sume una segunda persona al equipo con necesidad de acceso diferenciado (ej. alguien que solo cargue productos, sin acceso a Ajustes).
8. **Persistencia de pedidos**: si el negocio quiere reportes o simplemente trazabilidad de qué se vendió, esto deja de ser opcional — hoy es la brecha más grande entre "lo que el sitio hace" y "lo que una tienda online típica registra".

---

## 19. Árbol de carpetas

Árbol completo del proyecto (excluyendo `vendor/`, `node_modules/`, `.git/`, y cachés de framework en `storage/framework/`):

```
cyrex-store/
├── .editorconfig
├── .env                                    (no versionado, secretos reales — nunca leer/exponer su contenido)
├── .env.example
├── .gitattributes
├── .gitignore
├── ARCHITECTURE.md                          ← este documento
├── README.md                                (README genérico de Laravel, sin info del proyecto)
├── VISION.md                                (filosofía de diseño — leer junto a este documento)
├── artisan
├── composer.json
├── composer.lock
├── package.json
├── phpunit.xml
├── vite.config.js                           (SIN USO — ver sección 17)
│
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       └── CreateAdminUser.php          (comando `admin:create`)
│   ├── Http/
│   │   └── Controllers/
│   │       ├── Controller.php               (clase base vacía)
│   │       ├── HomeController.php
│   │       ├── ShopController.php
│   │       ├── CartController.php
│   │       └── Admin/
│   │           ├── AuthController.php
│   │           ├── ProductController.php
│   │           ├── CategoryController.php
│   │           ├── SettingsController.php
│   │           └── UserController.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Category.php
│   │   ├── Product.php
│   │   ├── ProductVariant.php
│   │   ├── ExchangeRate.php
│   │   └── Setting.php
│   ├── Providers/
│   │   └── AppServiceProvider.php           (View Composers — CENTRAL para header/footer)
│   └── Support/
│       └── Cart.php                          (lógica del carrito de sesión)
│
├── bootstrap/
│   ├── app.php                               (config de rutas, middleware, excepciones)
│   ├── providers.php
│   └── cache/                                (autogenerado)
│
├── config/
│   ├── app.php, auth.php, cache.php, database.php,
│   ├── filesystems.php                       (disco 'uploads' — ver sección 12)
│   ├── logging.php, mail.php, queue.php, session.php
│
├── database/
│   ├── database.sqlite                       (BD local)
│   ├── factories/
│   │   └── UserFactory.php                   (scaffold, sin uso real)
│   ├── migrations/
│   │   ├── 0001_01_01_000000_create_users_table.php
│   │   ├── 0001_01_01_000001_create_cache_table.php
│   │   ├── 0001_01_01_000002_create_jobs_table.php
│   │   ├── 2026_08_04_222200_create_categories_table.php
│   │   ├── 2026_08_04_222208_create_products_table.php
│   │   ├── 2026_08_04_222210_create_product_variants_table.php
│   │   ├── 2026_08_04_222211_create_exchange_rates_table.php
│   │   ├── 2026_08_05_120000_add_image_to_products_table.php
│   │   └── 2026_08_05_120001_create_settings_table.php
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── CategorySeeder.php                (3 categorías padre, ~23 hijas de prueba)
│       ├── ProductSeeder.php                  (4 productos de prueba)
│       └── ExchangeRateSeeder.php
│
├── public/
│   ├── index.php
│   ├── .htaccess
│   ├── favicon.ico
│   ├── robots.txt
│   ├── css/
│   │   ├── app.css                            (TODO el CSS público, 250 líneas)
│   │   └── admin.css                          (TODO el CSS admin, 88 líneas)
│   ├── images/
│   │   └── logo-horizontal.png                (logo real de marca)
│   └── uploads/
│       └── products/                          (fallback local de imágenes — ver sección 12)
│
├── resources/
│   ├── css/app.css                            (scaffold Vite, SIN USO)
│   ├── js/app.js, js/bootstrap.js             (scaffold Vite, SIN USO)
│   └── views/
│       ├── welcome.blade.php                  (default de Laravel, sin uso real)
│       ├── home.blade.php
│       ├── shop.blade.php
│       ├── product.blade.php
│       ├── layouts/
│       │   └── app.blade.php                  (layout público — incluye footer inline)
│       ├── partials/
│       │   ├── nav.blade.php                  (header completo — ver sección 8)
│       │   ├── category-float.blade.php
│       │   ├── category-icon.blade.php
│       │   ├── search-box.blade.php
│       │   ├── cart-drawer.blade.php
│       │   ├── cart-drawer-content.blade.php
│       │   └── pagination.blade.php
│       └── admin/
│           ├── layout.blade.php               (layout admin, sidebar)
│           ├── auth/
│           │   └── login.blade.php
│           ├── products/
│           │   ├── index.blade.php
│           │   └── form.blade.php
│           ├── categories/
│           │   ├── index.blade.php
│           │   └── form.blade.php
│           ├── users/
│           │   └── index.blade.php
│           └── settings/
│               └── edit.blade.php
│
├── routes/
│   ├── web.php                                (TODAS las rutas de la app)
│   └── console.php                            (solo comando `inspire` de ejemplo)
│
├── storage/
│   ├── app/{private,public}/                  (poco usado — imágenes NO viven acá, ver sección 12)
│   └── logs/laravel.log
│
└── tests/
    ├── TestCase.php
    ├── Feature/ExampleTest.php                (scaffold sin uso real)
    └── Unit/ExampleTest.php                    (scaffold sin uso real)
```

---

## 20. Dependencias

### PHP / Composer (`composer.json`)

**Producción (`require`)**:
| Paquete | Versión | Uso real |
|---|---|---|
| `php` | `^8.2` | corriendo en `8.4` localmente |
| `laravel/framework` | `^12.0` | el framework completo |
| `laravel/tinker` | `^2.10.1` | REPL usado activamente durante desarrollo (queries manuales, `Setting::set()` puntuales, etc.) |

**Desarrollo (`require-dev`)** — todas de scaffold, ninguna con configuración custom del proyecto:
`fakerphp/faker`, `laravel/pail`, `laravel/pint`, `laravel/sail`, `mockery/mockery`, `nunomaduro/collision`, `phpunit/phpunit`.

### JavaScript / npm (`package.json`)

**Todas sin uso en runtime** (ver sección 17): `vite`, `laravel-vite-plugin`, `tailwindcss`, `@tailwindcss/vite`, `axios`, `concurrently`.

### Cargadas por CDN (no gestionadas por ningún gestor de paquetes)

| Librería | Fuente | Uso |
|---|---|---|
| `alpinejs@3.x.x` | jsdelivr.net | reactividad de todo el frontend público |
| `@alpinejs/collapse@3.x.x` | jsdelivr.net | animación de alto del acordeón de categorías mobile |
| Google Fonts (Space Grotesk, Inter, JetBrains Mono) | fonts.googleapis.com | tipografía |

### Resumen de "qué está realmente en uso" vs. "qué está instalado pero muerto"

**En uso real**: Laravel framework completo, Tinker, Alpine.js + su plugin de collapse, Google Fonts.

**Instalado pero sin ninguna conexión al código real**: todo el stack de Vite/Tailwind/Axios en `package.json`, y todas las dependencias de testing/dev en `composer.json` más allá de que existan (no hay tests reales que las ejerciten).

---

## 21. Flujo de creación de una página nueva

Pasos exactos, tal como funciona HOY (sin CMS, sin dinamismo — ver sección 7):

1. **Decidir la URL** y agregar la ruta en `routes/web.php`:
   ```php
   Route::get('/nueva-pagina', [AlgunController::class, 'metodo'])->name('nueva-pagina');
   ```
2. **Elegir o crear un Controller.** Si la página no necesita datos dinámicos de base de datos, se puede apuntar a un método nuevo en un Controller existente, o crear uno nuevo con `php artisan make:controller NombreController` (sin flags de `--resource` si no se necesita el set completo CRUD).
3. **El método del Controller** arma cualquier dato necesario y devuelve la vista:
   ```php
   public function metodo() {
       return view('nombre-de-la-vista', ['algo' => $valor]);
   }
   ```
4. **Crear el archivo Blade** en `resources/views/nombre-de-la-vista.blade.php` (o dentro de una subcarpeta si se agrupa lógicamente, como se hizo con `admin/*`):
   ```blade
   @extends('layouts.app')

   @section('title', 'Título de la página — Cyrex Store')

   @section('content')
     <div class="wrap">
       {{-- contenido --}}
     </div>
   @endsection
   ```
   Al extender `layouts.app`, la página **automáticamente** obtiene el header y el footer completos (categorías reales, carrito, WhatsApp, etc.) sin que el Controller tenga que preocuparse por eso — ver sección 8/9 sobre los View Composers.
5. **Si la página necesita estilos propios** que no están ya en `app.css`, se agregan inline vía `@section('styles') <style>...</style> @endsection` (patrón usado en `home.blade.php`) o directamente como clases nuevas en `public/css/app.css` si se prevé reutilización.
6. **Si la página necesita interactividad**, se usa Alpine.js directo en el HTML (`x-data`, `x-show`, etc.) — no hay compilación de por medio, el código Alpine se escribe tal cual en el Blade.
7. **Probar localmente** con `php artisan serve` y **limpiar la vista compilada** si Laravel no refleja el cambio (`php artisan view:clear`) — necesario después de editar archivos `.blade.php` en algunos casos según el entorno.
8. **Commit + push** a `main` → deploy automático (ver sección 22).

**Si la página necesita admin gestionable** (ej. un nuevo tipo de contenido editable desde `/admin`), hay que además: crear la migración, el modelo, el Controller admin con su CRUD completo siguiendo el patrón de `CategoryController`/`ProductController`, las vistas de índice y formulario siguiendo el patrón de `admin/categories/*`, las rutas dentro del grupo `Route::prefix('admin')->name('admin.')->middleware('auth')`, y el link correspondiente en el sidebar de `admin/layout.blade.php`.

---

## 22. Flujo de despliegue

1. **Desarrollo local**: cambios se hacen y prueban con `php artisan serve` apuntando a `database/database.sqlite`. Comandos usados con frecuencia durante desarrollo: `php artisan view:clear` (tras cambios en Blade), `php artisan config:clear` / `cache:clear` (tras cambios en `.env` o en `Setting`), `php artisan route:list` (para verificar rutas registradas), `php artisan tinker` (para inspección/manipulación manual de datos).
2. **Control de versiones**: Git, repositorio en GitHub (`github.com/cxDanilo/CYREX-STORE`), rama única de trabajo `main` (no hay flujo de branches/PRs establecido — todo el desarrollo commitea directo a `main`).
3. **Commit + push**: `git add` de los archivos relevantes (nunca `git add -A` a ciegas, para evitar subir accidentalmente `.env` u otros archivos sensibles/generados), commit con mensaje descriptivo, `git push origin main`.
4. **Despliegue automático**: Hostinger hPanel tiene configurado un despliegue Git que escucha el push a `main` (vía webhook) y automáticamente hace `git pull` (o equivalente) en el servidor, seguido de `composer install`. **No corre `php artisan migrate` automáticamente, ni `php artisan config:cache`/`view:cache`, ni ningún otro paso post-deploy** — es un pull + install de dependencias, nada más.
5. **Pasos manuales post-deploy (cuando aplican)**:
   - Si el push incluyó una migración nueva: SSH al servidor, `cd` a la carpeta real de la app (`~/domains/cyrexstore.com/public_html/dev/cyrex-app`), correr `php artisan migrate --force`.
   - Si el push agregó una variable de entorno nueva que el código necesita: SSH, editar `.env` a mano (o `echo '...' >> .env`), correr `php artisan config:clear`.
   - Si se cambió algo relacionado a Settings/vistas cacheadas y no se refleja: `php artisan view:clear` / `cache:clear` por SSH.
6. **Verificación**: probar en `dev.cyrexstore.com` (el subdominio de staging — el dominio principal `cyrexstore.com` sigue siendo el sitio WordPress viejo, intocado). Nota conocida: tráfico automatizado de pruebas puede disparar una interstitial de "Bot Verification" de LiteSpeed en `dev.cyrexstore.com` — cuando pasa, la verificación real debe hacerse desde un navegador humano normal, no reintentando tráfico automatizado.
7. **Estructura real en el servidor** (confirmada por SSH durante la sesión más reciente): la cuenta de hosting tiene como home `/home/u570719467/`. El subdominio `dev.cyrexstore.com` no tiene su propia carpeta de nivel superior — está mapeado a una subcarpeta dentro del `public_html` del dominio PRINCIPAL: `~/domains/cyrexstore.com/public_html/dev/`. Dentro de esa carpeta `dev/` conviven tres directorios de un despliegue Laravel completo cada uno (con su propio `artisan`): `cyrex-app` (el activo, confirmado sirviendo contenido real), `cyrex-app-old-backup` (backup manual del inicio del proyecto) y `cyrex-app-git` (rol exacto no confirmado, probablemente relacionado al mecanismo interno de clonado del despliegue Git de hPanel). **Cualquier operación por SSH debe apuntar explícitamente a `cyrex-app`**, nunca asumir la primera carpeta que aparezca.
8. **Corte a producción (cutover)**: paso futuro no ejecutado todavía — reemplazar el sitio WordPress en `cyrexstore.com` por esta aplicación Laravel. No hay ningún script ni plan automatizado para ese corte documentado en el código; es una decisión y ejecución manual pendiente.

---

## 23. Decisiones técnicas importantes

Todo lo que otra IA debería internalizar ANTES de proponer o implementar cambios:

1. **No hay checkout con tarjeta ni se debe agregar uno sin pedido explícito.** El modelo de conversión es y debe seguir siendo WhatsApp. Cualquier feature de "carrito"/"checkout" debe pensarse en términos de qué información se le agrega al mensaje de WhatsApp final, no en términos de una pasarela de pago.
2. **El carrito no maneja cantidades — es una decisión de negocio, no un descuido.** No agregar un input de cantidad sin que el dueño del negocio lo pida explícitamente.
3. **Las variantes de producto (color, tamaño, etc.) SIEMPRE van como fila en `product_variants`, nunca como un producto nuevo duplicado.** Esto es una regla de catálogo explícita documentada en `VISION.md` — evita inflar el catálogo con SKUs artificialmente separados.
4. **La jerarquía de categorías es de exactamente 2 niveles, reforzada en el código de aplicación (no en la base de datos).** Cualquier feature de categorías nueva debe respetar ese límite salvo pedido explícito de cambiarlo, y si se cambia, hay que tocar validaciones + toda la lógica de renderizado de menús (son varios lugares independientes, no una sola fuente).
5. **El header y el footer obtienen sus datos exclusivamente vía View Composers en `AppServiceProvider`, nunca vía Controllers individuales.** Cualquier dato nuevo que el header/footer necesiten debe agregarse a esos composers (`partials.nav` y `layouts.app` respectivamente), no pasarse manualmente desde una vista específica — de lo contrario, esa página nueva sería la única con el header funcionando correctamente y todas las demás fallarían silenciosamente (con una variable Blade indefinida) o requerirían duplicar la lógica en cada Controller.
6. **Todo cambio de estado visible en el sitio público necesita al menos una transición sutil (fade, slide corto, etc.) — nunca un salto instantáneo.** Esta es una regla de proyecto explícita pedida por el dueño, no una preferencia estética opcional. Duraciones típicas ya usadas en el proyecto: 150–400ms para fades, 200–300ms para slides/drawers.
7. **La estética es intencionalmente minimalista ("vender una experiencia, no un catálogo") — ver `VISION.md` completo.** Antes de agregar cualquier elemento visual nuevo (banners, badges, textos promocionales), la pregunta que hay que hacerse es la que el propio documento de visión plantea: ¿esto vende experiencia o vende catálogo? El sesgo por defecto del proyecto es sacar, no agregar.
8. **Paleta de color y tipografía son fijas y no deben ampliarse sin pedido explícito**: negro (`#080808`, nunca negro puro, nunca con tinte azulado — ya se corrigió ese error una vez), dorado (`#FFD900`) como único acento, verde solo para el estado semántico "en línea" (nunca decorativo). Tipografía: Space Grotesk para títulos, Inter para cuerpo, **JetBrains Mono siempre y exclusivamente para precios y datos técnicos/specs** — esta última regla es la firma visual distintiva del proyecto, no un detalle menor.
9. **El disco de almacenamiento de imágenes (`uploads`) debe seguir el patrón de `root` configurable por variable de entorno + `serve: true`, nunca asumir que escribir dentro de `public/` a secas es seguro en este hosting.** Ver sección 12 — esto ya causó pérdida real de datos una vez y el patrón de solución (carpeta externa al árbol de Git + ruta de Laravel para servirla) debe replicarse para cualquier nueva necesidad de almacenamiento persistente de archivos subidos por usuarios.
10. **Cualquier migración de base de datos nueva requiere un recordatorio explícito de correr `php artisan migrate --force` por SSH tras el deploy** — el pipeline automático NO lo hace. De la misma forma, cualquier variable de entorno nueva requiere edición manual del `.env` de producción vía SSH — nunca asumir que una variable nueva en `.env.example` "ya está" en el servidor real.
11. **No asumir que el asistente/IA tiene o debe tener acceso SSH directo al servidor.** El patrón establecido de trabajo es: la IA da los comandos exactos, el dueño del proyecto los ejecuta él mismo por su propia sesión SSH y pega el resultado. Esto es una preferencia de flujo de trabajo explícita, no solo una limitación técnica.
12. **El sitio de producción real (`cyrexstore.com`, sin el subdominio `dev.`) es el WordPress viejo y NO debe tocarse bajo ninguna circunstancia** hasta que se ejecute un corte (cutover) explícitamente decidido y planeado — todo el desarrollo activo ocurre exclusivamente contra `dev.cyrexstore.com`.
13. **`Setting::get()`/`Setting::set()` son el único camino correcto para leer/escribir configuración global** — nunca tocar el modelo `Setting` directo con Eloquent puro, porque hay una capa de caché (`Cache::rememberForever`) que solo se invalida correctamente a través de `Setting::set()`.
14. **No hay roles/permisos — cualquier usuario en la tabla `users` es admin total.** Si se agrega un segundo usuario admin con necesidad de permisos distintos, hay que construir esa capa desde cero (no existe ni un esqueleto).
15. **El `.env` real nunca debe leerse ni exponerse en documentos, commits, ni respuestas** — contiene `APP_KEY` y credenciales reales. Este mismo documento fue escrito deliberadamente a partir de `.env.example` (sin secretos) para la sección 20/5, nunca del `.env` real.
