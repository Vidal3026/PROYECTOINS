# Guía de pruebas: PayPal, comisiones, cupos y motivo de rechazo

Esta guía recorre todas las funciones nuevas de TutoLink en el orden en que un usuario real las usaría.
Cada prueba indica qué hacer y **qué debes ver**. Marca la casilla cuando el resultado coincida.

Tiempo estimado: 30–40 minutos.

---

## 0. Preparación

1. Abre el **Panel de Control de XAMPP** y verifica que **Apache** y **MySQL** estén en verde.
2. Abre el proyecto en: **http://localhost:8080/PROYECTOINS**
   (en esta PC Apache usa el puerto **8080**, no el 80).
3. Si la base `tutolink` no existe o la acabas de reimportar, ejecuta una sola vez en phpMyAdmin
   (http://localhost:8080/phpmyadmin → base `tutolink` → pestaña SQL) el archivo
   `sql/migracion_pagos_comisiones_cupos.sql`.
4. Ten a mano las contraseñas de estas cuentas de prueba:

   | Rol | Usuario | Correo |
   |---|---|---|
   | Administrador | Wilfredo | wil@gmail.com |
   | Tutor | Johan | Johan@gmail.com |
   | Estudiante A | Jorge | Jorge@gmail.com |
   | Estudiante B | Fernando | Fer@gmail.com |
   | Estudiante C | Vidal | vidal@gmail.com |

> **Consejo:** para tener dos sesiones a la vez (por ejemplo tutor y estudiante), usa un navegador
> normal y otro en **ventana de incógnito**, o dos navegadores distintos. La sesión es una por navegador.

### Montos que vas a ver

Las comisiones son **5 %** (tarifa de servicio, sobre lo que paga el estudiante) y **10 %**
(comisión de tutor, sobre lo que queda). Para una tutoría de **$10.00**:

| Concepto | Monto |
|---|---|
| Paga el estudiante | $10.00 |
| Tarifa de servicio (5 %) | $0.50 |
| Comisión de tutor (10 % de $9.50) | $0.95 |
| **Recibe el tutor** | **$8.55** |
| Ganancia de la plataforma | $1.45 |

---

## 1. Configurar PayPal Sandbox (pagos de prueba, gratis)

Sin credenciales el sistema funciona en **modo simulación** (las recargas se acreditan sin cobro).
Para probar pagos con PayPal sin dinero real:

1. Si no tienes cuenta PayPal, crea una personal gratis en **https://www.paypal.com**.
2. Entra a **https://developer.paypal.com** con esa cuenta.
3. Ve a **Apps & Credentials**, asegúrate de que el selector diga **Sandbox** y abre la app
   **Default Application** (o crea una con **Create App**).
4. Copia el **Client ID** y el **Secret**.
5. Abre `Includes/config_local.php`, quita las `//` de las dos líneas `PAYPAL_` y pega tus valores:
   ```php
   define('PAYPAL_CLIENT_ID', 'tu-client-id-sandbox');
   define('PAYPAL_CLIENT_SECRET', 'tu-secret-sandbox');
   define('PAYPAL_MODO', 'sandbox');
   ```
   Este archivo no se sube a GitHub.
6. En developer.paypal.com ve a **Testing Tools > Sandbox Accounts**. PayPal ya creó una cuenta
   **Personal** (comprador de prueba, con correo tipo `sb-...@personal.example.com`) con saldo ficticio.
   Pulsa los tres puntos > **View/Edit account** y anota su correo y contraseña: con ella pagarás.
7. Inicia sesión en TutoLink como **Administrador** → menú **Pagos y Comisiones**.

- [ ] Aparecen las insignias **Activo** y **Sandbox: pruebas sin dinero real**
  (si dice "Modo simulación", las credenciales no se leyeron).
- [ ] Al pulsar **Probar conexión** aparece "Conexión correcta: PayPal (sandbox) aceptó las credenciales."
- [ ] Si aparece "PayPal rechazó la autenticación", revisa que copiaste las credenciales del modo **Sandbox**.

---

## 2. Comisiones visibles en el registro

1. Cierra sesión y abre **Registrarse**.
2. En "Registrarse como" elige **Estudiante**.
   - [ ] Aparece un aviso: el precio ya incluye una tarifa de servicio del 5 %.
3. Cambia a **Tutor**.
   - [ ] Aparece un aviso con sus descuentos (5 % y 10 %) y el ejemplo "en una tutoría de $20.00 recibes $17.10".

(No hace falta completar el registro.)

---

## 3. El tutor define precio, cupo y ve cuánto recibe

Inicia sesión como **Johan (tutor)** → **Perfil** (menú de la foto) → pestaña **Materias y Modalidad**.

1. En **Ecuaciones Diferenciales** deja el precio en **10.00** y en el campo del ícono de personas
   escribe **2** (cupo máximo de 2 estudiantes por tutoría).
   - [ ] Debajo de la materia aparece "Recibes $8.55/h (comisiones: $1.45)".
   - [ ] Si cambias el precio a 20, el texto cambia a "Recibes $17.10/h" al instante.
     Vuélvelo a 10.
2. Pulsa **Guardar Materias y Modalidad**.
   - [ ] Aparece "¡Materias y modalidad guardadas!".
   - [ ] Al recargar la página, el cupo sigue en 2.
3. Abre **Billetera** (menú izquierdo).
   - [ ] Ves las tarjetas **Ingresos netos por tutorías** y **Comisiones retenidas por TutoLink**.
   - [ ] Ves la tarjeta **Comisiones sobre tus tutorías** con el ejemplo de $20 → $17.10
     y "En total recibes el 85.5% del precio". No menciona nada del estudiante.
   - Anota los dos totales actuales; los compararás en la prueba 8.

---

## 4. Recargar saldo con PayPal (estudiantes)

Inicia sesión como **Jorge (estudiante)** → **Billetera**.

- [ ] Se ve la tarjeta **Tarifa de servicio TutoLink**: el precio incluye un 5 % de tarifa de servicio.
  **No** aparece la comisión del tutor ni cuánto recibe el tutor.

### 4.1 Pago aprobado
1. Pulsa **Recargar Fondo**.
   - [ ] El cuadro dice **Recargar saldo con PayPal** y avisa que es modo de pruebas
     (si dice "Modo simulación", vuelve a la sección 1).
   - [ ] Los botones de PayPal aparecen **desactivados** mientras el monto esté vacío o fuera de rango.
2. Escribe **25**.
   - [ ] Los botones se activan.
3. Pulsa el botón amarillo **PayPal**. Se abre una ventana de PayPal Sandbox: inicia sesión con la
   **cuenta Personal de prueba** de la sección 1 y confirma el pago de **$25.00**.
   - [ ] Al cerrarse la ventana aparece "Confirmando el pago con PayPal..." y luego la Billetera se
     recarga con "Recarga exitosa. Se añadieron $25.00 a tu saldo."
   - [ ] El saldo aumentó en $25.00 y en el historial aparece "Recarga PayPal (REC-...)".
4. Recarga la página (F5).
   - [ ] El saldo **no** se duplica.

### 4.2 Pago cancelado
1. Abre **Recargar Fondo**, escribe **5**, pulsa **PayPal** y cierra la ventana de PayPal
   (o pulsa **Cancelar y volver**).
   - [ ] Aparece "Cancelaste el pago. No se realizó ningún cargo." y el saldo no cambió.

### 4.3 Pago con tarjeta de prueba (opcional)
1. Pulsa el botón negro **Tarjeta de débito o crédito**.
2. Usa una tarjeta generada en developer.paypal.com > **Testing Tools > Card Testing**
   (cualquier fecha futura y CVV de 3 dígitos).
   - [ ] La recarga se acredita igual que en 4.1.

### 4.4 Validaciones y seguridad
- [ ] Con un monto de **2** o de **600** los botones de PayPal quedan desactivados.
- [ ] El monto se valida otra vez en el servidor: aunque se manipule el navegador, PayPal solo recibe
  montos entre $5.00 y $500.00, y el saldo se acredita solo si PayPal confirma el cobro por el mismo monto.

### 4.5 Segundo estudiante
Inicia sesión como **Fernando** y recarga **$10** (necesitará saldo para pagar en la prueba 7).
No recargues a **Vidal**.

### 4.6 Lo ve el administrador
Como **Administrador** → **Pagos y Comisiones** → "Últimas recargas de estudiantes".
- [ ] Las recargas pagadas aparecen como **COMPLETADO**, con su **Orden PayPal** y su **Captura PayPal**.
- [ ] La recarga cancelada aparece como **PENDIENTE** (nunca se cobró ni se acreditó).
- [ ] En developer.paypal.com > **Testing Tools > Sandbox Accounts**, la cuenta **Business** de prueba
  muestra los pagos recibidos.

> **Sin credenciales de PayPal** puedes hacer estas recargas en modo simulación (se acreditan al instante)
> y seguir con el resto de la guía igual.

---

## 5. Reservar una tutoría grupal (cupo)

Usarás la tutoría de Johan: **Ecuaciones Diferenciales, lunes 10:00, 1 hora** (cupo 2).

### 5.1 Primera reserva y desglose
Como **Jorge** → **Buscar Tutor** → perfil de **Johan**.
1. Junto al selector de materia:
   - [ ] Dice "Los precios ya incluyen la tarifa de servicio de TutoLink (5%)".
   - [ ] La opción dice "Ecuaciones Diferenciales ($10.00/h · grupal, hasta 2 estudiantes)".
2. Elige la materia y pulsa el botón verde del **lunes 10:00**.
   - [ ] En el cuadro de confirmación, con 1 hora: Precio Total **$10.00** e "Incluye tarifa de servicio
     TutoLink (5%)" **$0.50**. **No** aparece la comisión del tutor ni lo que recibe el tutor.
   - [ ] Si cambias la duración a 2 horas, se recalcula ($20.00 / tarifa $1.00).
     Vuelve a **1 hora**.
3. Pulsa **Enviar Solicitud**.
   - [ ] Te lleva a **Mis Solicitudes** con la solicitud en estado PENDIENTE.

### 5.2 Validaciones de la reserva
Sigue como **Jorge** en el perfil de Johan:
- [ ] Reservar otra vez **lunes 10:00, 1 hora** → "Ya tienes una solicitud para esta misma tutoría."
- [ ] Reservar **lunes 10:00 con 2 horas** → "El tutor ya tiene otra tutoría en ese horario."
  (es una sesión distinta que se cruza con la grupal).

### 5.3 Más estudiantes en el mismo grupo
- Como **Fernando**: reserva **lunes 10:00, 1 hora** de Ecuaciones Diferenciales.
  - [ ] Se registra sin error (se une al grupo).
- Como **Vidal**: haz la misma reserva.
  - [ ] También se registra. Ahora hay **3 solicitudes** para un cupo de **2**.

---

## 6. El tutor acepta hasta llenar el cupo y rechaza con motivo

Como **Johan** → **Solicitudes**.

- [ ] Ves las 3 solicitudes, cada una con la columna **Recibirás: $8.55** y la columna **Cupo 0/2**.
- [ ] Arriba de la tabla se explica qué significa "Recibirás".

1. Pulsa **Aceptar** en la de **Jorge** y luego en la de **Fernando**.
   - [ ] Mensaje "La solicitud fue procesada exitosamente." después de cada una.
   - [ ] La de Vidal ahora muestra **Cupo 2/2** en rojo, "Cupo lleno" y el botón **Aceptar desactivado**.
2. Pulsa **Rechazar** en la de **Vidal**.
   - [ ] Se abre el cuadro "Rechazar solicitud" con el texto ya iniciado: "El cupo de esta tutoría ya está completo."
   - [ ] Si borras el texto y escribes algo de menos de 10 caracteres, no te deja enviar.
3. Escribe un motivo completo, por ejemplo:
   "El cupo de esta tutoría ya está completo. Te sugiero el lunes a las 15:00." y pulsa **Rechazar solicitud**.
   - [ ] La solicitud desaparece de la lista de pendientes.

### Lo que ve el estudiante rechazado
Como **Vidal** → **Mis Solicitudes**.
- [ ] La solicitud dice **CANCELADA**, "Rechazada por el tutor." y debajo **Motivo:** con el texto exacto que escribió el tutor.
- [ ] Si Vidal intenta reservar de nuevo **lunes 10:00, 1 hora** → "El cupo de esta tutoría ya está lleno. Elige otro horario."

---

## 7. Pagar la tutoría (cobro con comisiones)

### 7.1 Pago correcto
Como **Jorge** → **Mis Solicitudes**.
- [ ] La solicitud aceptada muestra "Incluye tarifa de servicio (5%): $0.50." (sin datos del tutor).
- [ ] El enlace "¿Qué incluye el precio de una tutoría?" despliega la explicación de la tarifa de servicio.

1. Pulsa **Pagar Ahora**.
   - [ ] Mensaje "¡Pago Confirmado!" y la solicitud pasa a **CONFIRMADA**.
   - [ ] En **Billetera**, el saldo bajó exactamente **$10.00** y el movimiento dice
     "Pago Solicitud #… (incluye tarifa de servicio 5%)".
2. Vuelve a la página anterior del navegador e intenta pagar otra vez (o vuelve a abrir el enlace de pago).
   - [ ] Dice que la solicitud ya fue pagada y **no** vuelve a cobrar.
3. En **Mis Solicitudes** pulsa **Recibo**.
   - [ ] El recibo muestra $10.00 y debajo "Incluye tarifa de servicio de la plataforma: $0.50" (sin datos del tutor).

### 7.2 Pago sin saldo suficiente
Para probarlo, usa un estudiante aceptado **sin saldo** (por ejemplo, Vidal reserva el lunes a las 15:00,
Johan lo acepta y Vidal intenta pagar sin haber recargado).
- [ ] Al pulsar **Pagar Ahora** aparece "Transacción Fallida: Saldo insuficiente…" y no se cobra nada.

Paga también la de **Fernando** (tiene $10 de la prueba 4.5).

---

## 8. Lo que ve el tutor después de los pagos

Como **Johan** → **Billetera**.
- [ ] El **saldo** subió **$8.55 por cada tutoría pagada** ($17.10 si pagaron Jorge y Fernando).
- [ ] **Ingresos netos por tutorías** subió lo mismo.
- [ ] **Comisiones retenidas por TutoLink** subió **$1.45 por tutoría** ($2.90 por las dos).
- [ ] En el historial, por cada pago hay un INGRESO (verde) y dos COMISION (amarillo): "tarifa de servicio 5%"
  y "comisión de tutor 10%". Al pasar el mouse sobre COMISION dice que no afecta el saldo.

- [ ] En el **Dashboard** del tutor aparecen las tarjetas **Saldo disponible**, **Ingresos netos de este mes**
  y **Comisiones de este mes**.
- [ ] En **Próximas tutorías** la columna **Recibes** muestra \$8.55 por tutoría (no el precio de \$10).

### 8.1 Cancelar una tutoría ya pagada (reembolso)
Como **Johan** → **Próximas tutorías** → botón de cancelar en la tutoría de **Fernando**.
1. Intenta cancelar sin escribir motivo.
   - [ ] No te deja: el motivo es obligatorio (mínimo 10 caracteres).
2. Escribe un motivo (ej.: "Tuve un imprevisto, te propongo reprogramar el jueves.") y confirma.
   - [ ] Mensaje: "Se reembolsaron \$10.00 al estudiante y se descontó de tu saldo lo que habías recibido."
   - [ ] El saldo de Johan baja **\$8.55** y sus comisiones retenidas bajan **\$1.45** (se revierten).
3. Como **Fernando** → **Mis Solicitudes** y **Billetera**.
   - [ ] La tutoría dice "Cancelada por el tutor. Se te reembolsaron \$10.00 a tu billetera." y muestra el motivo.
   - [ ] Su saldo subió \$10.00 y el historial muestra "Reembolso Solicitud #… (cancelada por el tutor)".

### 8.2 Retiros del tutor
Como **Johan** → **Billetera** → **Solicitar Retiro**.
- [ ] Un retiro de **\$5** muestra "El monto mínimo de retiro es de \$10.00."
- [ ] No puedes pedir más que tu saldo disponible; los retiros pendientes ya cuentan como usados
  (dos retiros que juntos superan el saldo: el segundo se rechaza).
- [ ] Como **Administrador** → **Solicitudes de Retiros**: al aprobar, el saldo del tutor baja. Si el tutor ya no
  tiene saldo suficiente, la aprobación se rechaza con un mensaje y el saldo nunca queda negativo.

---

## 9. Lo que ve el administrador

Como **Administrador**:
1. **Pagos y Comisiones**
   - [ ] "Tarifa de servicio (estudiante, 5%)" y "Comisión de tutor (10%)" muestran lo cobrado por cada concepto
     ($1.00 y $1.90 si se pagaron las dos tutorías de $10).
   - [ ] Dice "La plataforma retiene en total el 14.5% de cada pago."
2. **Billetera** del admin
   - [ ] La ganancia por comisiones incluye las nuevas comisiones.
3. **Estadísticas/Reportes**
   - [ ] Los gráficos de flujo y "top tutores" incluyen a Johan con sus comisiones.

---

## 10. (Opcional) Cambio de horario con tutoría grupal

Comprueba que los estudiantes de un mismo grupo se mueven juntos si el tutor cambia su horario.

1. Como **Johan** → Perfil → **Horarios y Disponibilidad**: cambia el bloque del **lunes** para que empiece
   a las **12:00** (en lugar de 09:00) y guarda.
2. Como **Jorge** y como **Fernando** revisa las notificaciones (campana) y **Mis Solicitudes**.
   - [ ] Ambos reciben el aviso de cambio de horario.
   - [ ] Ambos quedan en **el mismo** horario nuevo (no en horarios distintos).

---

## 11. (Opcional) Webhook de PayPal

El saldo se acredita cuando la Billetera captura el pago, así que **no necesitas el webhook** para que
todo funcione en `localhost`. El webhook es un respaldo para dos casos: PayPal deja un cobro "en revisión",
o el estudiante cierra el navegador justo después de aprobar.

Para probarlo:
1. Instala ngrok y ejecuta `ngrok http 8080`.
2. En `Includes/config_local.php` cambia `APP_URL_BASE` a la URL de ngrok, por ejemplo
   `https://xxxx.ngrok-free.app/PROYECTOINS`.
3. En developer.paypal.com > tu app (Sandbox) > **Webhooks** > **Add Webhook**: pon
   `https://xxxx.ngrok-free.app/PROYECTOINS/paypal_webhook.php` y marca los eventos
   **Payment capture completed** y **Payment capture denied**.
4. Copia el **Webhook ID** que muestra PayPal y agrégalo en `Includes/config_local.php`:
   `define('PAYPAL_WEBHOOK_ID', 'el-id');`
5. En **Pagos y Comisiones** el webhook debe aparecer como **Configurado**.
6. Haz una recarga de prueba (o usa el simulador de webhooks de developer.paypal.com).
   - [ ] Los eventos con firma válida se procesan; un POST hecho a mano sin firma recibe "Firma inválida" (401).

---

## Problemas comunes

| Síntoma | Causa probable | Solución |
|---|---|---|
| La página no carga en `http://localhost/...` | Apache está en el puerto 8080 | Usa `http://localhost:8080/PROYECTOINS` |
| "Unknown column 'cupo_maximo'" o "paypal_order_id" | Falta la migración | Ejecuta `sql/migracion_pagos_comisiones_cupos.sql` |
| phpMyAdmin dice "#1046 No database selected" al importar | El respaldo no crea la base | Crea la base `tutolink`, selecciónala y luego importa |
| La billetera sigue en "Modo simulación" | Credenciales vacías o comentadas | Revisa `Includes/config_local.php` (sin `//` delante de `define`) |
| "PayPal rechazó la autenticación" | Credenciales de otro modo o mal copiadas | Usa las de **Sandbox** con `PAYPAL_MODO` = `sandbox` |
| Los botones de PayPal no aparecen | Sin internet, bloqueador de anuncios o Client ID inválido | Desactiva el bloqueador en localhost y revisa la consola del navegador (F12) |
| "La página expiró. Recárgala..." | La sesión cambió (protección CSRF) | Recarga la Billetera y vuelve a intentar |
| "Demasiados intentos de recarga" | Más de 10 recargas sin terminar en una hora | Espera, o revisa las recargas PENDIENTE en el admin |
| "No se pudo conectar con PayPal" | Sin internet o certificado SSL de PHP | Verifica la conexión; XAMPP usa `C:\xampp\apache\bin\curl-ca-bundle.crt` |
| "Cupo lleno" aunque el tutor quiera más | Cupo configurado bajo | El tutor sube el cupo en Perfil → Materias y Modalidad |

## Cambiar los porcentajes

Edita `Includes/config_app.php` (o defínelos en `Includes/config_local.php`):
```php
define('COMISION_ESTUDIANTE', 0.05); // 5 %
define('COMISION_TUTOR', 0.10);      // 10 %
```
Todas las pantallas (registro, reserva, solicitudes, billeteras, recibo y admin) se actualizan solas.
Los pagos ya realizados conservan las comisiones con las que se cobraron.
