Tu Kingdom Nexus hoy es un mini framework productivo: modular, seguro, responsive, con UX clara para cada rol y una base de datos ordenada. Ya resolviste lo más áspero (prefijos de tablas, modales, normalización de sort_order, navbar/sidebars unificados) y tienes una ruta limpia para crecer: más endpoints, reporting, y mejoras visuales sin tocar lo esencial (seguridad + rendimiento).
1) De dónde viene y a dónde llegó
Comenzaste hace ~2 meses montando un mini-framework dentro de WordPress + MySQL (hosting HostGator USA, plan Business con acceso delegado). La idea: un plugin robusto para un servicio de delivery, pensado con cariño para menu uploaders y otros roles internos.
Desde entonces el proyecto evolucionó a un micro-ecosistema modular:
* Un plugin único (“Kingdom Nexus”) con APIs REST reales (/wp-json/knx/v1/...) y módulos UI (shortcodes) para CRUDs.
* Un layout interno consistente: sidebar fijo, páginas internas sin navbar público, toasts globales, y responsive real (móvil primero, desktop pulido).
* Refinaste seguridad (sesión endurecida, CSRF con nonces, helpers, roles) y rendimiento (paginar, ordenar, normalizar sort_order, índices).
* Aprendizajes clave: choque de tablas kbx vs knx, reemplazado por un estándar:
    * Z7E_knx_hub_items (ítems del menú)
    * Z7E_items_categories (categorías de ítems)(Z7E_ es el prefijo portable que resolvió compatibilidades de hosting)
2) Piezas obligatorias (lo mínimo para que todo funcione)
Infra / WP
* Plugin “Kingdom Nexus” activo.
* Permalinks y REST de WP funcionando (necesario para /wp-json/).
* PHP 8.x con OPcache (recomendado) y uploads escribibles.
Tablas críticas
* Z7E_knx_hub_items (id, hub_id, category_id, name, price, status, image_url, sort_order …).
* Z7E_items_categories (id, hub_id, name, sort_order, status …).
* (Hubs y Cities existen en sus propios módulos, con sus tablas y APIs).
Rutas / páginas internas
* /edit-hub-items/?id={hub_id} (gestión de ítems por hub).
* /edit-item-categories/?id={hub_id} (categorías para los ítems).
* Sidebar global activo en páginas internas; navbar público oculto en vistas internas.
Seguridad
* Sesiones con cookies HttpOnly y SameSite=Strict.
* Nonces tipo knx_edit_hub_nonce en cada operación sensible.
* Roles internos: super_admin, manager, hub_management, menu_uploader.
* Helpers y globals: constantes KNX_PATH, KNX_URL, KNX_VERSION, cargador defensivo knx_require().
3) Funcionalidad actual (resumen ejecutivo)
CRUD de Menú (lo más importante de tu flujo)
* Items por Hub: listar, buscar, paginar, reordenar (dentro de categoría y, si no hay vecino, a nivel hub), agregar (con imagen obligatoria), borrar con modal (sin confirm() nativo).
* Categorías de ítems: listar por sort_order, activar/desactivar (toggle), crear/editar con asignación automática de sort_order (sin campos manuales), reordenar (up/down) y normalizar cuando sea necesario.
* UX: encabezados por categoría, cards limpias con imagen, precio, acciones rápidas (up/down/edit/delete), toasts globales, modales para alta y confirmaciones.
Módulos complementarios
* Hubs: identidad, ubicación (ver Google Maps abajo), horarios, cierres temporales, logo.
* Cities y Delivery rates: gestión operativa por zona.
* Auth: shortcode + handler + redirects (roll-based), bloqueo de páginas internas si no hay sesión/rol válido.
APIs (namespace knx/v1)
* Items: get-hub-items, add-hub-item, delete-hub-item, reorder-item.
* Categorías: get-item-categories, save-item-category, reorder-item-category, toggle-item-category.
* Hubs / Cities: get-hub, edit-hub-identity, edit-hub-location, hub-hours, update-closure, cities, delivery-rates (según módulo).
Todas validan nonce, revisan sesión/rol cuando aplica, y devuelven JSON compacto con success, error y payload.
4) Google Maps API Key (pieza estratégica)
* Se usa en Edit Hub Location para geocodificación/embebido.
* Recomendado: 2 claves (cliente con HTTP referrers restringidos; servidor con IP restrictions).
* Guardado seguro en wp_options (u otro store) con sanitización; nunca exponer la server-key en el front.
* Considerar quota & billing y Rate limiting para evitar hard-fails.
5) Seguridad bien aplicada (auth, CSRF, nonces, roles)
* Auth: sesión propia + validación de rol; si no cumple, redirect a /login.
* CSRF: cada POST lleva knx_nonce y el backend valida wp_verify_nonce.
* Roles: definen qué menús y páginas internas aparecen (sidebar) y qué endpoints responden con 403.
* Uploads: paths dedicados (/wp-uploads/knx-items/{hub_id}/), nombres únicos, index.html drop-in para evitar listing.
6) Responsive & UX (la experiencia por rol)
* Mobile-first con grid fluido, cards y botoneras cómodas; sidebar colapsable en móvil (ensanchable a demanda).
* Desktop con sidebar fijo y contenido centrado (margen izquierdo dinámico).
* Páginas largas (ej. muchos ítems): el sidebar usa height: 100vh y el contenido scroll vertical independiente; botones y toasts se mantienen accesibles.
* Roles y UX:
    * Menu Uploader: acceso directo a Items/Categorías; formularios mínimos y claros; modales rápidos.
    * Hub Management/Manager: además de lo anterior, configuraciones de hub, horarios, tarifas, ciudades.
    * Super Admin: todo lo anterior + vistas administrativas.
7) Rendimiento (lo que ya haces y lo que conviene mantener)
* Paginación en listados y búsqueda con LIKE segura.
* sort_order inteligente:
    * En ítems: se usa time() como valor único incremental (barato y colisiona poco).
    * En categorías: se auto-asigna el siguiente MAX(sort_order)+1, y se normaliza en lecturas si hay ceros/lagunas.
* Índices recomendados:
    * Z7E_knx_hub_items: índice en (hub_id), (category_id), y opcional (hub_id, category_id, sort_order).
    * Z7E_items_categories: índice en (hub_id), y opcional (hub_id, sort_order).
* Uploads: imágenes optimizadas (webp/jpg), tamaño controlado; considera lazy-load en UI.
* Cache: OPcache activo; si agregas caching de objetos (Redis/Memcached) mejora aún más lecturas REST.
* Sin ruido en producción: WP_DEBUG desactivado y sin console.log/var-dumps.
8) HostGator Business (lo importante para ti)
* Entorno compartido potente suficiente para tu stack WordPress + REST + uploads.
* cPanel con MySQL e SSL; activa HTTP/2 y compresión (GZIP/Brotli) si está disponible.
* Cuida límite de memoria PHP, max upload size y max_execution_time (uploads grandes y geocoding).
* Mantén cron real o WP-Cron estable (tu job horario limpia sesiones: knx_hourly_cleanup).
9) “Checklist” de bases activas (para pasar a producción tranquila)
* Plugin Kingdom Nexus 2.7.x activo.
* REST /knx/v1 responde y permalinks OK.
* Tablas: Z7E_knx_hub_items y Z7E_items_categories existentes con índices.
* Páginas: /edit-hub-items/?id=… y /edit-item-categories/?id=….
* Sidebar global en internas, navbar público oculto allí.
* Nonces funcionando en Add/Delete/Toggle/Reorder.
* Uploads escribibles (/wp-content/uploads/knx-items/{hub_id}/).
* Google Maps: clave cliente restringida por dominio; clave server (si usas geocoding) restringida por IP.
* Performance: paginación activada, sin SELECT * abusivos, imágenes razonables.

Lo que pediste destacar (3 puntos)
1. Menú del CRUD dentro de WordPressTodo el flujo de Items + Categorías vive dentro del admin “ligero” que renderiza tu plugin: sidebar propio, modales, toasts, y endpoints REST internos. No dependes del wp-admin clásico para operar menús.
2. Google Maps API KeyIntegrada para ubicación de hubs. Clave cliente (front) con HTTP referrers; si haces geocoding/validaciones desde backend, usa server key con IP restrictions. Guarda en wp_options (o settings del plugin) y evita exponer la server key al front.
3. Auth, CSRF, Nonces, Roles, Helpers, Globals
    * Auth con sesión y roles; redirecciones limpias y bloqueo de páginas sin permiso.
    * CSRF: knx_edit_hub_nonce en cada POST/JSON crítico.
    * Roles controlan qué UI aparece y qué APIs permiten operar.
    * Helpers/Globals centralizan rutas, versión y utilidades para respuesta JSON, sanitización y carga segura de archivos.

Tu Kingdom Nexus hoy es un mini framework productivo: modular, seguro, responsive, con UX clara para cada rol y una base de datos ordenada. Ya resolviste lo más áspero (prefijos de tablas, modales, normalización de sort_order, navbar/sidebars unificados) y tienes una ruta limpia para crecer: más endpoints, reporting, y mejoras visuales sin tocar lo esencial (seguridad + rendimiento).
