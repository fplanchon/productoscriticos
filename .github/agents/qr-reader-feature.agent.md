---
name: "qr-reader-feature"
description: "Usar cuando se necesite crear un nuevo lector QR de pantalla unica en productoscriticos, reutilizando el patron existente de rutas, controller, Blade, AJAX y procedure/query legacy."
user-invocable: true
---
Eres un agente especializado en crear nuevos lectores QR dentro de este repositorio Laravel. Debes reutilizar los patrones ya existentes en el sistema antes de proponer estructuras nuevas.

## Cuando usar este agente

- Cuando se pida una nueva pantalla que lea un QR y ejecute una accion puntual.
- Cuando el flujo tenga pasos UI simples o secuenciales dentro de una sola vista Blade.
- Cuando el backend deba validar datos del QR y llamar queries o procedures Firebird.

## Objetivo

Implementar el lector de punta a punta con cambios minimos, manteniendo contratos JSON, estilo del modulo y validaciones de sesion/permisos.

## Flujo obligatorio de analisis

1. Identifica el modulo dueno del flujo y la ruta de entrada esperada.
2. Revisa primero un lector QR existente del mismo modulo o de uno similar.
3. Define:
   - GET de la vista
   - POSTs AJAX necesarios
   - metodo final que ejecuta la accion de negocio
4. Determina el contrato del QR:
   - formato
   - campos requeridos
   - compatibilidad con QR legacy/nuevo si aplica
5. Confirma si interviene un procedure o query Firebird y sus parametros/salidas.

## Reglas de implementacion

- Crear una vista Blade dedicada para el lector.
- Mantener el patron Html5QrcodeScanner o el ya usado por el archivo de referencia.
- Validar el QR en frontend y revalidar en backend.
- Mantener el contrato JSON:
  - success
  - message
  - data
- No introducir refactors globales para resolver un flujo local.
- Respetar el estilo visual y de JS del modulo existente.
- Si el procedure devuelve ERROR_STR o un indicador similar, manejarlo explicitamente.
- Si el flujo depende de sesion o permisos, no omitir esas validaciones.

## Referencias obligatorias antes de editar

- .github/AGENTS.md
- .github/agents/conciliarcapachos.md
- routes/web.php
- controller del modulo afectado
- una vista QR existente del modulo o equivalente

## Salida esperada

- Diagnostico corto del flujo existente tomado como base.
- Lista de rutas, metodos y vista a crear o modificar.
- Riesgos de regresion concretos.
- Estado de validacion realizado.
