# AGENTS.md - Guia de IA para Productos Criticos

Este archivo define como debe trabajar un asistente de IA dentro de este repositorio.
Objetivo: permitir cambios seguros y rapidos en un sistema Laravel con base de datos legacy Firebird.

## 1. Contexto del Proyecto

- Stack principal: Laravel (PHP) + Blade + jQuery + Bootstrap.
- Base de datos de negocio: Firebird (legacy), con SQL raw y stored procedures.
- Integracion frontend: flujo intensivo de lectura QR (html5-qrcode).
- Autenticacion: manejo manual por sesion (no se usa Auth de Laravel para el flujo principal).

## 2. Mapa Funcional Rapido

### 2.1 Modulos Core

- Asociacion de productos criticos:
  - Controlador: app/Http/Controllers/AsociarController.php
  - Vista principal: resources/views/asociarProductosCriticos.blade.php
  - Procedure clave: ASOCIAR_PROD_CRITICO_QR

- Capachos (lectura, avance, trazabilidad):
  - Controlador: app/Http/Controllers/CapachosController.php
  - Vistas: resources/views/leerCapachos.blade.php, resources/views/avanzaCapachos.blade.php, resources/views/verTrazabilidad.blade.php
  - Procedures clave: CAPACHOS_NUEVA_ACTIVIDAD, CAPACHOS_AVANZAR_HASTA_LLENO

- Llamados de asistencia:
  - Controlador: app/Http/Controllers/LlamadosAsistenciaController.php
  - Vista: resources/views/leerLlamadosAsistencia.blade.php
  - Procedure clave: LLAMADOSNUEVAACTIVIDAD

- Permisos y tareas:
  - Controlador: app/Http/Controllers/PermisosController.php
  - Tablas de permisos: FAC_USUARIO_TAREAS_MENU, FAC_TAREAS_MENU

### 2.2 Capa Legacy

- Consultas custom Firebird:
  - app/Custom/consultas/ConsultasProductoCritico.php
- Config de conexiones:
  - config/database.php

## 3. Rutas Clave

Fuente de verdad: routes/web.php

### 3.1 Login y navegacion inicial

- GET / -> formulario login
- POST / -> peticion login
- GET /loginauto/{id_usuario}/{id_fase}/{id_hc?}/{accion?} -> setea sesion y redirige segun modulo

### 3.2 Asociacion producto critico

- GET /asociar
- POST /asociarproductocritico
- POST /buscarfasesusuario

### 3.3 Capachos

- GET /leercapacho
- GET /avanzacapacho
- GET /vertrazabilidad
- POST /obtenerCapachoQr
- POST /ejecutarActividad
- POST /avanzarCapachoHastaLleno
- POST /obtenerTrazabilidadCapacho

### 3.4 Llamados

- GET /leerllamadosasistencia/{accion?}
- POST /obtenerInfoLlamado
- POST /realizarLlamadoAsistencia

## 4. Flujo Operativo del Sistema

### 4.1 Sesion y control de acceso

- El sistema usa variables de sesion como id_usuario e id_fase.
- Muchos endpoints hacen validacion manual de sesion en controlador.
- Antes de proponer cambios, verificar el guardado/lectura de sesion en el flujo objetivo.

### 4.2 Flujo QR (patron comun)

1. Vista Blade inicializa Html5QrcodeScanner en readerContainer.
2. Se parsea JSON del QR.
3. Se valida estructura minima esperada.
4. Se envia AJAX POST con FormData y _token CSRF.
5. Backend responde JSON con success/message/data.
6. Frontend actualiza tabla, alerta o estado visual.

## 5. Reglas para Agentes de IA

### 5.1 Reglas generales

- No asumir logica de negocio sin revisar primero ruta + controlador + vista implicada.
- Preferir cambios minimos y localizados.
- Evitar refactors globales no pedidos.
- Mantener estilo de codigo existente del archivo editado.

### 5.2 Reglas de analisis antes de editar

Para cualquier feature o fix:

1. Identificar endpoint/ruta afectada.
2. Identificar controlador y metodo exacto.
3. Identificar vista y JS asociado.
4. Confirmar dependencia de procedure o query Firebird.
5. Enumerar riesgos de regresion.

### 5.3 Reglas de implementacion

- Si el cambio afecta datos: revisar SQL raw y parametros.
- Si el cambio afecta UI QR: mantener contrato de datos JSON esperado.
- Si el cambio afecta permisos: revisar PermisosController y nombres de permiso existentes.
- Si el cambio afecta session/login: validar redireccion y variables de sesion.

### 5.4 Cuando pedir aclaraciones

Pedir aclaracion minima al usuario solo si falta un dato bloqueante, por ejemplo:

- Permiso exacto que habilita una accion nueva.
- Procedure a usar cuando hay multiples candidatos.
- Contrato de respuesta esperado cuando backend actual no lo define.

## 6. Contratos de Respuesta y Patrones de API Interna

Patron frecuente en endpoints AJAX:

- success: boolean
- message: string opcional
- data: objeto/array opcional

Mantener este patron salvo pedido explicito de cambio.

## 7. Riesgos Tecnicos y Mitigaciones

### 7.1 SQL raw y parametrizacion

- Existen consultas con string interpolation en capa custom legacy.
- Mitigacion recomendada: parametrizar siempre que sea posible y no ampliar SQL no parametrizado.

### 7.2 Logica distribuida en procedures

- Parte de la logica vive en Firebird (no en PHP).
- Mitigacion recomendada: documentar supuestos de entrada/salida del procedure en cada cambio.

### 7.3 Encoding legacy

- Firebird usa charset legacy (ISO8859_1) y hay conversiones manuales a UTF-8.
- Mitigacion recomendada: verificar acentos/caracteres especiales en respuesta JSON y UI.

### 7.4 Sesion manual

- El acceso esta centrado en variables de sesion en vez de auth estandar Laravel.
- Mitigacion recomendada: no eliminar validaciones de sesion existentes sin rediseño explicito.

## 8. Playbooks por Tipo de Cambio

### 8.1 Playbook: Nueva feature en Capachos

1. Revisar rutas de capachos en routes/web.php.
2. Ubicar metodo en CapachosController.php.
3. Verificar si requiere procedure nuevo o reutilizacion de existente.
4. Ajustar vista Blade correspondiente (leer, avanzar o trazabilidad).
5. Mantener estructura de respuesta JSON.
6. Validar manualmente flujo QR extremo a extremo.

### 8.2 Playbook: Nueva feature en Llamados de asistencia

1. Revisar GET /leerllamadosasistencia/{accion?} y endpoints POST relacionados.
2. Confirmar comportamiento por accion (solicitar/responder).
3. Revisar query/procedure en LlamadosAsistenciaController.php.
4. Validar datos mostrados en la vista de llamados.
5. Verificar errores de base de datos y mensajes para usuario.

### 8.3 Playbook: Cambios en login/sesion

1. Revisar login(), peticionlogin(), loginauto() en AsociarController.php.
2. Confirmar que no se rompan variables de sesion usadas por otros modulos.
3. Verificar redirecciones segun id_hc y accion.
4. Probar ingreso y acceso a cada modulo principal.

### 8.4 Playbook: Cambios frontend QR

1. Revisar inicializacion del scanner y callback de exito.
2. Validar parseo JSON y campos obligatorios.
3. Verificar stop/restart de camara.
4. Confirmar envio AJAX con CSRF token.
5. Verificar render de tablas y mensajes en pantalla.

### 8.5 Playbook: Troubleshooting encoding

1. Confirmar charset de conexion Firebird en config/database.php.
2. Detectar puntos de conversion utf8_encode o equivalente.
3. Verificar resultado en JSON (backend) y texto en vista (frontend).
4. Evitar doble conversion en cadenas ya UTF-8.

## 9. Checklist de Validacion antes de cerrar cambios

### 9.1 Checklist funcional minimo

- Login y sesion siguen operativos.
- Endpoint modificado responde JSON valido.
- Vista asociada renderiza sin errores JS.
- Flujo QR principal sigue funcionando (si aplica).

### 9.2 Checklist tecnico minimo

- No se introdujeron consultas SQL inseguras nuevas.
- Se mantuvo compatibilidad con patterns existentes.
- Se documentaron supuestos sobre procedures o datos legacy.

## 10. Archivos de consulta rapida para agentes

- routes/web.php
- app/Http/Controllers/AsociarController.php
- app/Http/Controllers/CapachosController.php
- app/Http/Controllers/LlamadosAsistenciaController.php
- app/Http/Controllers/PermisosController.php
- app/Custom/consultas/ConsultasProductoCritico.php
- config/database.php
- resources/views/login.blade.php
- resources/views/asociarProductosCriticos.blade.php
- resources/views/leerCapachos.blade.php
- resources/views/avanzaCapachos.blade.php
- resources/views/verTrazabilidad.blade.php
- resources/views/leerLlamadosAsistencia.blade.php

## 11. Alcance de este documento

Este AGENTS.md define comportamiento esperado de asistentes IA para:

- Analizar el sistema antes de cambiar codigo.
- Implementar cambios pequeños y seguros.
- Minimizar regresiones en flujos productivos.

No reemplaza documentacion funcional de negocio ni especificacion oficial de base de datos.
