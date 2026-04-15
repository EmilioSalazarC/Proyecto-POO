// ─── HELPERS ───────────────────────────────────────────────
const $ = id => document.getElementById(id);

function getRol() { return localStorage.getItem('rol'); }

// Normaliza claves a minúsculas
function norm(data) {
  if (Array.isArray(data)) return data.map(norm);
  if (data && typeof data === 'object') {
    return Object.fromEntries(
      Object.entries(data).map(([k, v]) => [k.toLowerCase(), norm(v)])
    );
  }
  return data;
}

// 🔥 FUNCIÓN API CORREGIDA (DEBUG REAL)
async function api(url, method = 'GET', body = null) {
  try {
    const opts = {
      method,
      headers: { 'Content-Type': 'application/json' }
    };

    if (body) opts.body = JSON.stringify(body);

    // ⚠️ AJUSTA SEGÚN TU CASO:
    const res = await fetch('api/' + url, opts);

    const text = await res.text();

    console.log("RESPUESTA CRUDA:", text);

    let data;
    try {
      data = JSON.parse(text);
    } catch (e) {
      alert("⚠️ El servidor respondió mal (no es JSON)");
      console.error("Respuesta inválida:", text);
      return {};
    }

    return norm(data);

  } catch (err) {
    console.error("ERROR REAL:", err);
    alert("Error de conexión");
    return {};
  }
}

// ─── MODAL ────────────────────────────────────────────────
function mostrarModal(html) {
  $('modal-body').innerHTML = html;
  $('modal').style.display = 'flex';
}

function cerrarModal() {
  $('modal').style.display = 'none';
}

function badge(estatus) {
  return `<span class="badge badge-${estatus}">${estatus}</span>`;
}

function esc(s) { return s.replace(/'/g, "\\'"); }

// ─── LOGIN ────────────────────────────────────────────────
async function login() {
  const usuario  = $('usuario').value.trim();
  const password = $('password').value;

  const data = await api('auth.php', 'POST', { usuario, password });

  if (data.success) {
    localStorage.setItem('rol', data.rol);
    $('login').style.display = 'none';
    $('sistema').style.display = 'block';
    $('rol-label').textContent = '(' + data.rol + ')';
    cargarClientes();
  } else {
    $('login-error').style.display = 'block';
  }
}

$('password').addEventListener('keydown', e => {
  if (e.key === 'Enter') login();
});

function logout() { location.reload(); }

// ─── CLIENTES ─────────────────────────────────────────────
async function cargarClientes() {
  const lista = await api('clientes.php');

  let html = '<h3>Clientes</h3>';
  html += '<button onclick="modalNuevoCliente()">+ Nuevo cliente</button>';

  html += '<table><tr><th>ID</th><th>Nombre</th><th>Acciones</th></tr>';

  lista.forEach(c => {
    html += `<tr>
      <td>${c.id_cliente}</td>
      <td>${c.nombre}</td>
      <td>
        <button onclick="verCliente(${c.id_cliente},'${esc(c.nombre)}')">Ver</button>
      </td>
    </tr>`;
  });

  html += '</table>';
  $('contenido').innerHTML = html;
}

// ─── VER CLIENTE ──────────────────────────────────────────
async function verCliente(idCliente, nombre) {
  const [vehiculos, ordenes] = await Promise.all([
    api('vehiculos.php'),
    api('ordenes.php')
  ]);

  const misVeh = vehiculos.filter(v => v.id_cliente == idCliente);
  const misOrd = ordenes.filter(o => o.id_cliente == idCliente);

  let html = `<h3>${nombre}</h3>`;

  html += '<h4>Vehículos</h4>';
  html += `<button onclick="modalNuevoVehiculo(${idCliente})">+ Vehículo</button>`;

  html += '<table><tr><th>Placas</th><th>Marca</th><th></th></tr>';

  misVeh.forEach(v => {
    html += `<tr>
      <td>${v.placas}</td>
      <td>${v.marca}</td>
      <td>
        <button onclick="modalNuevaOrdenVehiculo(${idCliente},${v.id_vehiculo})">+ Orden</button>
      </td>
    </tr>`;
  });

  html += '</table>';

  html += '<h4>Órdenes</h4>';
  html += '<table><tr><th>ID</th><th>Fecha</th><th></th></tr>';

  misOrd.forEach(o => {
    html += `<tr>
      <td>#${o.id_orden}</td>
      <td>${o.fecha}</td>
      <td>
        <button onclick="verOrden(${o.id_orden},${idCliente},'${esc(nombre)}')">Ver</button>
      </td>
    </tr>`;
  });

  html += '</table>';

  $('contenido').innerHTML = html;
}

// ─── VER ORDEN ────────────────────────────────────────────
async function verOrden(idOrden, idCliente, nombreCliente) {
  const [ordenes, servicios, pagos] = await Promise.all([
    api('ordenes.php'),
    api('servicios.php'),
    api('pagos.php')
  ]);

  const orden = ordenes.find(o => o.id_orden == idOrden);

  if (!orden) {
    alert("No se encontró la orden");
    return;
  }

  const servs = servicios.filter(s => s.id_orden == idOrden);
  const pags  = pagos.filter(p => p.id_orden == idOrden);

  let html = `<h3>Orden #${idOrden}</h3>`;

  html += `<button onclick="modalAgregarServicio(${idOrden})">+ Servicio</button>`;

  html += '<h4>Servicios</h4>';
  html += '<table><tr><th>Descripción</th><th>Costo</th></tr>';

  servs.forEach(s => {
    html += `<tr><td>${s.descripcion}</td><td>$${s.costo}</td></tr>`;
  });

  html += '</table>';

  html += '<h4>Pagos</h4>';
  html += '<table><tr><th>Monto</th><th>Método</th></tr>';

  pags.forEach(p => {
    html += `<tr><td>$${p.monto}</td><td>${p.metodo}</td></tr>`;
  });

  html += '</table>';

  $('contenido').innerHTML = html;
}

// ─── MODALES ─────────────────────────────────────────────
function modalNuevoCliente() {
  mostrarModal(`
    <h3>Nuevo cliente</h3>
    <input id="m-nombre" placeholder="Nombre">
    <button onclick="guardarCliente()">Guardar</button>
  `);
}

async function guardarCliente() {
  await api('clientes.php', 'POST', {
    nombre: $('m-nombre').value
  });
  cerrarModal();
  cargarClientes();
}

function modalNuevoVehiculo(idCliente) {
  mostrarModal(`
    <h3>Nuevo vehículo</h3>
    <input id="m-placas" placeholder="Placas">
    <input id="m-marca" placeholder="Marca">
    <button onclick="guardarVehiculo(${idCliente})">Guardar</button>
  `);
}

async function guardarVehiculo(idCliente) {
  await api('vehiculos.php', 'POST', {
    placas: $('m-placas').value,
    marca: $('m-marca').value,
    cliente: idCliente
  });
  cerrarModal();
  cargarClientes();
}

function modalNuevaOrdenVehiculo(idCliente, idVehiculo) {
  mostrarModal(`
    <h3>Nueva orden</h3>
    <button onclick="guardarOrden(${idCliente},${idVehiculo})">Crear</button>
  `);
}

async function guardarOrden(idCliente, idVehiculo) {
  await api('ordenes.php', 'POST', {
    cliente: idCliente,
    vehiculo: idVehiculo,
    fecha: new Date().toISOString().split('T')[0]
  });
  cerrarModal();
  cargarClientes();
}

function modalAgregarServicio(idOrden) {
  mostrarModal(`
    <h3>Agregar servicio</h3>
    <input id="m-desc" placeholder="Descripción">
    <input id="m-costo" type="number" placeholder="Costo">
    <button onclick="guardarServicio(${idOrden})">Guardar</button>
  `);
}

async function guardarServicio(idOrden) {
  await api('servicios.php', 'POST', {
    orden: idOrden,
    descripcion: $('m-desc').value,
    costo: $('m-costo').value
  });
  cerrarModal();
}