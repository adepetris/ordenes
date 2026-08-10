# Relevamiento tecnico del proyecto

Fecha del relevamiento: 2026-08-10

Este documento conserva el contexto tecnico necesario para planificar futuras modificaciones de la aplicacion de ordenes de compra.

## Estado verificado

- Stack: Laravel 13.6.0, PHP 8.3.32, PostgreSQL 16 y Nginx 1.27.
- Servicios Docker verificados como saludables: `nginx`, `php` y `postgres`.
- Aplicacion local: `http://localhost:8090`.
- Healthcheck `/up`: HTTP 200.
- Migraciones: las 17 migraciones existentes estaban aplicadas.
- Pruebas: 38 tests aprobados, con 108 aserciones.
- Git estaba limpio antes de agregar este documento.
- `Pint --test`: 83 archivos revisados y 7 archivos con observaciones de estilo.
- Entorno observado: `local`, `APP_DEBUG` habilitado, timezone UTC y locale `en`.

## Estructura

- `backend/`: aplicacion Laravel.
- `backend/routes/web.php`: rutas web y asignacion de middleware por rol.
- `backend/routes/api.php`: endpoint autenticado `GET /api/v1/auth/me`.
- `backend/app/Http/Controllers/`: controladores de autenticacion y modulos funcionales.
- `backend/app/Http/Requests/`: validaciones de ordenes, proveedores y usuarios.
- `backend/app/Models/`: modelos Eloquent.
- `backend/app/Services/`: numeracion, calculo de totales y auditoria.
- `backend/database/migrations/`: esquema de base de datos.
- `backend/database/seeders/`: roles y usuario administrador inicial.
- `backend/resources/views/`: interfaz Blade y plantillas PDF.
- `backend/public/css/app.css`: hoja de estilos cargada directamente por las vistas.
- `backend/tests/`: suite PHPUnit.
- `docker-compose.yml`: Nginx, PHP-FPM y PostgreSQL.
- `infra/`: imagen PHP y configuracion de Nginx.
- `scripts/`: arranque, apagado, backup y restore.

La aplicacion es un monolito Laravel tradicional. No utiliza repositorios, policies ni una capa de acciones. Las reglas de negocio se encuentran principalmente en los controladores y servicios.

## Entidades y relaciones

### Usuarios y roles

- `users`: nombre, username, email opcional, password, `must_change_password` e `is_active`.
- `roles`: nombre canonico y descripcion.
- `user_roles`: relacion muchos-a-muchos.
- Un usuario puede tener varios roles.

Roles canonicos:

- `administrador`
- `usuario_solicitante`
- `usuario_autorizado`

### Proveedores

- Tabla `suppliers`.
- Numero automatico almacenado en `tax_id`, con formato `PRV-######`.
- Datos: nombre, email, telefono y estado `active`/`inactive`.
- Las ordenes sólo aceptan proveedores activos al crear o actualizar.

### Ordenes

- Tabla `purchase_orders`.
- Numero automatico `OC-YYYY-######`.
- Relaciona proveedor, solicitante y aprobador final.
- Campos monetarios: subtotal, impuestos y total general.
- Moneda de tres caracteres; la interfaz actual fuerza USD.
- Fechas de envio y aprobacion, y motivo de rechazo.

### Items

- Tabla `purchase_order_items`.
- Descripcion, cantidad, unidad, precio unitario, tasa de impuesto y total de linea.
- El servidor recalcula los importes y no confia en totales enviados por el cliente.
- Redondea subtotal e impuesto por linea a dos decimales.

### Aprobaciones

- Tabla `approvals` con historial de decisiones.
- Decisiones utilizadas: `approved`, `rejected` y `auto_approved`.
- Existe un campo `level`, pero el flujo actual siempre utiliza nivel 1.

### Adjuntos

- Tabla `purchase_order_attachments`.
- Archivos almacenados en el disco Laravel `local`, bajo `order_attachments/{order_id}`.
- Nombre fisico basado en UUID y nombre original.
- Limite de 10 MB.
- Extensiones admitidas: PDF, PNG, JPG/JPEG, DOC/DOCX, XLS/XLSX, CSV y TXT.

### Auditoria

- Tabla `audit_logs`.
- Registra usuario, entidad, ID, accion y un JSON con diferencias o contexto.
- Acciones actuales: creacion/edicion/envio/aprobacion/rechazo de ordenes, carga de adjuntos y creacion/edicion de proveedores.

## Autenticacion

- Login mediante `username` y `password`.
- Se valida `is_active` al iniciar sesion.
- Si `must_change_password` es verdadero, el middleware obliga a cambiar la clave.
- Contraseña minima configurada: 6 caracteres.
- Las contraseñas se almacenan mediante el cast `hashed` del modelo o `Hash::make`.
- Logout invalida la sesion y regenera el token CSRF.
- Sanctum esta instalado y protege `/api/v1/auth/me`.

## Flujo de ordenes

Estados utilizados:

- `draft`
- `pending_approval`
- `approved`
- `rejected`

`cancelled` sólo aparece en etiquetas y filtros; no existe una operacion para cancelar.

Transiciones actuales:

1. Toda orden se crea como `draft`.
2. Una orden `draft` o `rejected` puede editarse y enviarse nuevamente.
3. Si envia un `usuario_solicitante`, pasa a `pending_approval`.
4. Si envia un `administrador` o `usuario_autorizado`, queda `approved` mediante autoaprobacion.
5. Un administrador o autorizado puede aprobar o rechazar una orden pendiente.
6. El rechazo requiere comentario de hasta 500 caracteres.
7. Sólo una orden aprobada puede exportarse individualmente a PDF.

Existe `PO_APPROVAL_THRESHOLD` y `config/purchase_orders.php`, pero el umbral no participa de la regla actual. La decision se toma exclusivamente por rol.

## Permisos efectivos

| Operacion | Administrador | Solicitante | Autorizado |
|---|---:|---:|---:|
| Dashboard | Si | Si | Si |
| Listar/ver/exportar ordenes | Si | Si | Si |
| Crear, editar y enviar ordenes | Si | Si | Si |
| Cargar y descargar adjuntos | Si | Si | Si |
| Bandeja de aprobaciones | Si | No | Si |
| Aprobar o rechazar | Si | No | Si |
| Listar/exportar proveedores | Si | Si | Si |
| Crear proveedores | Si | Si | Si |
| Editar proveedores | Si | No | Si |
| Gestionar usuarios | Si | No | No |
| Ver/exportar auditoria | Si | No | No |

No hay comprobaciones de propiedad de la orden. Todo usuario que pase el middleware de rol puede operar sobre cualquier orden alcanzable por URL.

## Interfaz

- Renderizado del lado servidor con Blade.
- Tema claro/oscuro guardado en `localStorage`.
- Las vistas cargan `public/css/app.css` directamente, no `@vite`.
- Hay formularios y JavaScript embebido en las vistas.
- Las exportaciones PDF usan `barryvdh/laravel-dompdf`.
- Listados paginados: ordenes, bandeja de aprobaciones y proveedores de 10 elementos, usuarios de 15 y auditoria de 20.
- Dashboard con KPIs, desglose por estado, seis ordenes recientes y cinco proveedores con mas ordenes aprobadas en el mes.

Observacion funcional importante: el modelo y las validaciones soportan precio unitario e impuesto, pero el formulario visual de ordenes los envia ocultos y siempre en cero. En la interfaz se captura descripcion, cantidad y unidad.

## Numeracion

- Ordenes: consulta el mayor numero lexicografico del año y suma uno.
- Proveedores: consulta el mayor `PRV-######` y suma uno.
- Ambos campos tienen restriccion unica.
- El alta reintenta hasta cinco veces ante colisiones.
- La creacion de una orden y sus items se realiza en una transaccion.

## Infraestructura y operacion

- Puertos: web 8090 y PostgreSQL 5434.
- Nginx limita el cuerpo de solicitudes a 12 MB y agrega headers basicos de seguridad.
- El contenedor PHP instala extensiones bcmath, mbstring, pdo_pgsql y zip.
- PostgreSQL y el storage declarado por Compose tienen volumenes.
- Scripts disponibles para levantar/bajar servicios y hacer backup/restore.
- Los backups se comprimen con gzip y, por defecto, se conservan siete dias.

El script `up_all.sh --with-assets` intenta ejecutar NPM dentro del contenedor PHP, pero la imagen actual no instala Node ni NPM. Ademas, los assets Vite no son los que consume el layout actual.

## Pruebas existentes

Cobertura funcional comprobada:

- Login valido.
- Cambio obligatorio de contraseña.
- Acceso basico por roles.
- Eliminacion de usuarios administrada.
- Dashboard y metricas principales.
- Creacion, numeracion, totales y edicion de borradores.
- Envio, autoaprobacion, aprobacion y rechazo.
- Exportaciones PDF.
- Carga y descarga de adjuntos.
- CRUD y busqueda de proveedores.
- Acceso y filtros de auditoria.

Las pruebas usan SQLite en memoria, no PostgreSQL.

## Riesgos y deudas detectadas

### Alta prioridad

1. No hay autorizacion por propietario para ver, editar, enviar o adjuntar archivos a una orden.
2. Un autorizado puede crear y autoaprobar su propia orden.
3. `is_active` sólo se comprueba durante el login; una sesion existente puede continuar activa luego de desactivar al usuario.
4. `UserController::destroy` separa los roles antes de borrar. Si la eliminacion falla por claves foraneas, el usuario puede quedar existente pero sin roles.
5. No se protege al ultimo administrador: puede ser desactivado o perder su rol mediante edicion.
6. La comprobacion de estado al aprobar/rechazar ocurre fuera de la transaccion y sin bloqueo de fila, lo que permite una carrera entre decisiones simultaneas.

### Prioridad media

1. El umbral de aprobacion configurado no se usa.
2. Precios e impuestos estan ocultos y forzados a cero en la interfaz.
3. El estado `cancelled` esta incompleto.
4. La auditoria no cubre login, contraseñas, gestion de usuarios ni descargas.
5. El guardado del archivo ocurre antes del registro en base de datos; un error posterior puede dejar un archivo huerfano.
6. No existe eliminacion de adjuntos.
7. Las exportaciones obtienen todos los registros filtrados sin limite, con riesgo de memoria ante grandes volumenes.
8. Los filtros de fecha de auditoria no tienen validacion explicita.

### Calidad y mantenimiento

1. Hay logica de negocio concentrada en `PurchaseOrderController`.
2. No se usan policies, enums de estado ni restricciones `CHECK` para estados/decisiones.
3. Los tests en SQLite no cubren diferencias propias de PostgreSQL.
4. No se encontro CI configurada.
5. Vite/Tailwind y el CSS publicado representan dos caminos de frontend diferentes.
6. Hay tablas de jobs y configuracion de colas, pero no se observaron jobs propios de la aplicacion.
7. El seeder `ApproverUserSeeder` existe, pero `DatabaseSeeder` no lo ejecuta.

## Cobertura recomendada para futuros cambios

Al modificar el proyecto, conservar los 38 tests actuales y agregar pruebas segun corresponda para:

- propiedad y visibilidad de ordenes;
- usuarios inactivos con sesiones existentes;
- preservacion de roles si falla la eliminacion de usuario;
- invariantes del ultimo administrador;
- doble aprobacion concurrente;
- reglas de umbral o niveles de aprobacion;
- transiciones validas e invalidas entre estados;
- API Sanctum y cambio obligatorio de contraseña;
- comportamiento real sobre PostgreSQL;
- errores de filesystem durante carga de adjuntos.

## Archivos de mayor impacto para cambios futuros

- `backend/routes/web.php`
- `backend/bootstrap/app.php`
- `backend/app/Http/Controllers/PurchaseOrderController.php`
- `backend/app/Http/Controllers/SupplierController.php`
- `backend/app/Http/Controllers/UserController.php`
- `backend/app/Http/Middleware/RequireRole.php`
- `backend/app/Http/Middleware/ForcePasswordChange.php`
- `backend/app/Services/PurchaseOrderTotalsService.php`
- `backend/app/Services/PurchaseOrderNumberService.php`
- `backend/resources/views/orders/_form.blade.php`
- `backend/resources/views/orders/show.blade.php`
- `backend/database/migrations/`
- `backend/tests/Feature/`
