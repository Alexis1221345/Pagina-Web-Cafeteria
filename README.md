# Muna Café — Sitio Web

Sitio web del restaurante Muna Café con menú dinámico, pedidos por WhatsApp, reservaciones en Google Calendar y portal de gerencia.

---

## Requisitos

| Herramienta | Versión mínima |
|-------------|---------------|
| PHP         | 8.1+          |
| Extensiones | `pdo_sqlite`, `openssl`, `curl` (activas por defecto en PHP 8+) |

---

## Primer uso — configurar secretos

Antes de correr el servidor copia el archivo de ejemplo y rellena las credenciales reales:

```bash
cp php/secrets.php.example php/secrets.php
```

Abre `php/secrets.php` y coloca:

1. **`SERVICE_ACCOUNT_KEY`** — la clave privada de la cuenta de servicio de Google (bloque `-----BEGIN PRIVATE KEY-----`).
2. **`ADMIN_INICIAL_PASSWORD`** — la contraseña con la que se crea el primer usuario gerente (`Alexis Morfin`) la primera vez que se inicia el servidor.

> `php/secrets.php` está en `.gitignore` y **nunca** se sube al repositorio.

---

## Correr el servidor local

Desde la raíz del proyecto:

```bash
php -S localhost:8080
```

El sitio queda disponible en `http://localhost:8080`.

---

## Portal de Gerencia

```
http://localhost:8080/gerencia/
```

### Credenciales iniciales

| Campo      | Valor        |
|------------|--------------|
| Usuario    | `Alexis Morfin` |
| Contraseña | `Alexis0912` |

> El usuario se crea automáticamente la primera vez que PHP inicializa la base de datos SQLite (`php/data/cafeteria.db`).

### Secciones del dashboard

| Sección | Descripción |
|---------|-------------|
| 📅 Reservaciones | Lista de reservaciones (web + WhatsApp). Filtros por fecha y estado. |
| 🧾 Pedidos | Lista de pedidos con cambio de estado en vivo y ticket de impresión. |
| 👥 Usuarios | Solo gerentes. Crear meseros, activar o desactivar acceso. |

### Roles

| Rol | Puede ver |
|-----|-----------|
| Gerente | Reservaciones, Pedidos, Usuarios |
| Mesero  | Reservaciones, Pedidos |

---

## Páginas públicas

| URL | Descripción |
|-----|-------------|
| `/` | SPA principal (inicio, menú, nosotros, galería, contacto) |
| `/pedidos.html` | Módulo de pedidos con carrito → WhatsApp |
| `/reservaciones.html` | Formulario de reservaciones → Google Calendar |
| `/gerencia/` | Portal de gerencia (requiere login) |

---

## Estructura de archivos relevantes

```
/
├── index.html              # SPA principal
├── pedidos.html            # Página de pedidos (independiente)
├── reservaciones.html      # Página de reservaciones (independiente)
│
├── php/
│   ├── config.php          # Configuración y BD SQLite
│   ├── calendar.php        # Integración Google Calendar (JWT puro)
│   ├── reservar.php        # API: crear reservación
│   ├── guardar_pedido.php  # API: guardar pedido
│   ├── secrets.php         # ⚠️ NO en git — credenciales reales
│   ├── secrets.php.example # Plantilla de secretos
│   └── data/
│       └── cafeteria.db    # SQLite — se crea automáticamente
│
├── gerencia/
│   ├── index.php           # Redirige a login o dashboard
│   ├── login.php           # Formulario de acceso
│   ├── logout.php          # Cierra sesión
│   ├── dashboard.php       # Panel principal
│   └── api/
│       ├── get_reservaciones.php
│       ├── get_pedidos.php
│       ├── update_estado.php
│       ├── get_usuarios.php
│       ├── crear_usuario.php
│       └── toggle_usuario.php
│
├── css/                    # Estilos por sección
├── js/                     # Scripts por sección
└── secciones/              # Fragmentos HTML del SPA
```

---

## Despliegue en Neubox

1. Sube **todos los archivos** vía FTP/SFTP al directorio `public_html`.
2. Sube `php/secrets.php` manualmente (no está en el repositorio).
3. Asegúrate de que `php/data/` tenga permisos de escritura (`chmod 775 php/data`).
4. El dominio del sitio queda en `https://tudominio.com` y la gerencia en `https://tudominio.com/gerencia/`.

> Neubox usa PHP 8+ con PDO SQLite y OpenSSL activos por defecto. No se requieren dependencias adicionales.
