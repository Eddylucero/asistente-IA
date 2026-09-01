# Mente — Asistente Virtual de Apoyo Psicológico

Aplicación web en Laravel que ofrece un espacio de conversación de apoyo emocional.
El usuario puede escribir con el asistente, hablarle por micrófono y revisar su
historial de sesiones. Las respuestas se generan con la API de Groq y el proyecto
resuelve localmente las preguntas más básicas (saludos, identidad, fecha) sin gastar
llamadas a la IA.

> Este proyecto es una herramienta de acompañamiento, **no** sustituye a un
> profesional de la salud mental.

---

## Características

- **Conversación escrita** con historial persistente y respuestas en Markdown.
- **Modo voz**: dictado por micrófono con la Web Speech API del navegador.
- **Historial** de conversaciones con borrado individual.
- **Autenticación completa**: registro, login, Google OAuth y recuperación de
  contraseña por código de 6 dígitos enviado al correo.
- **Perfil**: cambio de nombre y correo, cambio de contraseña y borrado de cuenta.
- **Tema claro / oscuro** con preferencia guardada y respeto por el tema del sistema.
- Interfaz responsive en español, con panel lateral deslizante en móvil.

---

## Requisitos

| Herramienta | Versión mínima |
|---|---|
| PHP | 8.2 |
| Composer | 2.x |
| Node.js | 18 (probado en 22) |
| npm | 9 |

Extensiones de PHP necesarias: `pdo_sqlite`, `sqlite3`, `mbstring`, `openssl`,
`curl`, `fileinfo`. Vienen activadas en XAMPP y Laragon; si usas PHP a mano,
revisa que no estén comentadas en tu `php.ini`.

---

## Instalación

```bash
git clone <url-del-repositorio>
cd asistente-virtual
```

### 1. Dependencias

```bash
composer install
npm install
```

### 2. Archivo de entorno

```bash
# Linux / macOS / Git Bash
cp .env.example .env

# Windows (PowerShell)
Copy-Item .env.example .env
```

```bash
php artisan key:generate
```

### 3. Base de datos

El proyecto usa SQLite, así que no hace falta instalar ningún motor aparte.
Solo hay que crear el archivo vacío y correr las migraciones:

```bash
# Linux / macOS / Git Bash
touch database/database.sqlite

# Windows (PowerShell)
New-Item -ItemType File database/database.sqlite
```

```bash
php artisan migrate
```

Si prefieres MySQL, descomenta el bloque `DB_*` del `.env`, pon
`DB_CONNECTION=mysql` y vuelve a ejecutar `php artisan migrate`.

### 4. Clave de la IA

El chat necesita una clave de [Groq](https://console.groq.com/keys) (el plan
gratuito es suficiente). Pégala en el `.env`:

```env
GROQ_API_KEY=gsk_tu_clave_aqui
GROQ_MODEL=openai/gpt-oss-120b
```

Sin esta clave la aplicación arranca y se puede navegar, pero al enviar un mensaje
que no tenga respuesta local el chat devolverá *"No pudimos obtener una respuesta
de la IA"*.

### 5. Compilar el frontend

```bash
npm run build
```

### 6. Levantar el servidor

```bash
php artisan serve
```

Abre <http://localhost:8000>, regístrate y listo.

---

## Modo desarrollo

Para trabajar con recarga automática de estilos y JavaScript, usa dos terminales:

```bash
# terminal 1
php artisan serve

# terminal 2
npm run dev
```

O todo junto con el atajo que ya trae el proyecto (servidor, colas, logs y Vite):

```bash
composer run dev
```

---

## Configuración opcional

### Inicio de sesión con Google

1. Entra a [Google Cloud Console](https://console.cloud.google.com/apis/credentials)
   y crea unas credenciales de tipo *ID de cliente de OAuth 2.0* → *Aplicación web*.
2. En **URI de redirección autorizados** agrega:
   `http://localhost:8000/auth/google/callback`
3. Copia los datos al `.env`:

```env
GOOGLE_CLIENT_ID=tu-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=tu-client-secret
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
```

Si dejas estas variables vacías, el botón "Continuar con Google" avisa que no está
configurado y el resto de la aplicación sigue funcionando con normalidad.

### Correo (recuperación de contraseña)

Por defecto `MAIL_MAILER=log`: el código de 6 dígitos **no se envía**, se escribe en
`storage/logs/laravel.log`. Es lo más cómodo para probar en local.

Para enviar correos de verdad con Gmail:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu-correo@gmail.com
MAIL_PASSWORD=tu-contraseña-de-aplicacion
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="tu-correo@gmail.com"
MAIL_FROM_NAME="${APP_NAME}"
```

La contraseña debe ser una [contraseña de aplicación](https://myaccount.google.com/apppasswords),
no la de tu cuenta.

### Micrófono

La pantalla de voz usa la Web Speech API, disponible en Chrome y Edge. El navegador
solo la habilita en `localhost` o sobre HTTPS. En Firefox el botón aparece
deshabilitado con un aviso.

---

## Pruebas

```bash
php artisan test
```

Las pruebas usan SQLite en memoria, así que no tocan tu base de datos local ni
requieren la clave de Groq (las llamadas HTTP se simulan).

---

## Estructura del proyecto

```
app/
├── Http/Controllers/     Controladores (auth, conversación, voz, perfil)
├── Models/               User, Conversation, Message, PasswordResetCode
├── Services/
│   ├── Assistant.php     Orquesta la respuesta: primero local, luego Groq
│   └── QuickReply.php    Respuestas resueltas sin llamar a la IA
└── View/Components/      Layouts como componentes de Blade

config/
├── brand.php             Imágenes de la interfaz (logo y avatares)
└── psychology.php        Prompt del sistema, parámetros y respuestas locales

resources/
├── js/ui/                Un módulo por comportamiento (chat, voz, tema, alertas…)
└── views/
    ├── components/       Campos de formulario, botones y piezas reutilizables
    ├── layouts/          guest (auth), main (inicio) y panel (app)
    └── partials/         head, script de tema y alertas flash
```

Puntos que conviene conocer al tocar el código:

- **El JavaScript no vive en las vistas.** Cada pantalla expone atributos
  `data-*` (`data-chat`, `data-voice`, `data-parallax`…) y el módulo
  correspondiente de `resources/js/ui/` se engancha a ellos. Si agregas una
  pantalla con comportamiento, crea su módulo y regístralo en `resources/js/app.js`.
- **El texto del asistente se configura, no se programa.** El prompt del sistema y
  las respuestas locales están en `config/psychology.php`.
- **Los formularios usan `<x-form-field>`**, que ya resuelve etiqueta, icono,
  errores de validación y el botón de mostrar contraseña.
- **Las confirmaciones son declarativas**: basta con poner
  `data-confirm-logout`, `data-confirm-conversation-delete` o
  `data-confirm-account-delete` en un `<form>` para que SweetAlert pida
  confirmación antes de enviarlo.

---

## Solución de problemas

| Síntoma | Causa habitual |
|---|---|
| `could not find driver` | Falta habilitar `pdo_sqlite` en el `php.ini`. |
| Página sin estilos | Falta ejecutar `npm run build` (o `npm run dev`). |
| `No application encryption key` | Falta ejecutar `php artisan key:generate`. |
| El chat responde con error 502 | `GROQ_API_KEY` vacía, mal copiada o sin cupo. |
| No llega el correo del código | Con `MAIL_MAILER=log` el código está en `storage/logs/laravel.log`. |
| Cambié el `.env` y no surte efecto | Ejecuta `php artisan config:clear`. |
