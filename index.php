<?php
/* =====================================================================
   MaestroYa – Prototipo funcional (PHP puro, sin base de datos)
   Ejecutar: copiar la carpeta "maestroya" a C:\xampp\htdocs\ y abrir
             http://localhost/maestroya/
   Persistencia: archivos JSON en la subcarpeta /data (se crea sola).
   Panel del equipo: PIN  maestroya2026   (cámbialo en ADMIN_PIN)
   ===================================================================== */
session_start();
date_default_timezone_set('America/La_Paz');
const ADMIN_PIN = 'maestroya2026';
$DIR = __DIR__ . '/data';
if (!is_dir($DIR)) @mkdir($DIR, 0777, true);

$OFICIOS = ['Plomería', 'Electricidad', 'Cerrajería', 'Pintura', 'Calefones', 'Computación e impresoras', 'Automotriz básico'];

/* ------------------------- utilidades ------------------------- */
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function cut($s) { return mb_substr(trim((string)$s), 0, 600); }
function dpath($n) { global $DIR; return "$DIR/$n.json"; }
function load($n) {
    $f = dpath($n);
    if (!file_exists($f)) return seed($n);
    $j = json_decode(file_get_contents($f), true);
    return is_array($j) ? $j : [];
}
function save($n, $d) {
    file_put_contents(dpath($n), json_encode(array_values($d), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
}
function nid($d) { return $d ? max(array_column($d, 'id')) + 1 : 1; }
function flash($m) { $_SESSION['f'] = $m; }
function go($u) { header("Location: $u"); exit; }
function isAdmin() { return !empty($_SESSION['adm']); }
function wa($tel, $msg) { return 'https://wa.me/591' . preg_replace('/\D/', '', $tel) . '?text=' . rawurlencode($msg); }
function norm($s) {
    $s = mb_strtolower((string)$s, 'UTF-8');
    return strtr($s, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
}
function bs($x) { return number_format((float)$x, 2, ',', '.'); }

/* ------------------- datos de ejemplo iniciales ------------------- */
function seed($n) {
    $m = function ($id, $nom, $of, $z, $tel, $desc, $v, $d) {
        return ['id' => $id, 'nombre' => $nom, 'oficio' => $of, 'zona' => $z, 'tel' => $tel, 'desc' => $desc,
                'aprobado' => true, 'verificado' => $v, 'destacado' => $d, 'fecha' => '2026-11-01 09:00'];
    };
    $t = function ($id, $of, $tr, $a, $b) { return ['id' => $id, 'oficio' => $of, 'trabajo' => $tr, 'min' => $a, 'max' => $b]; };
    $S = [
        'maestros' => [
            $m(1, 'Mario Quispe (ejemplo)', 'Plomería', 'Sopocachi', '70000001', 'Fugas, grifería y destape de cañerías.', true, true),
            $m(2, 'Rosa Mamani (ejemplo)', 'Electricidad', 'Miraflores', '70000002', 'Tomacorrientes, tableros y revisión de cortocircuitos.', true, false),
            $m(3, 'Julio Condori (ejemplo)', 'Cerrajería', 'San Pedro', '70000003', 'Apertura de puertas y cambio de chapas.', true, false),
            $m(4, 'Carmen Apaza (ejemplo)', 'Pintura', 'Sopocachi', '70000004', 'Pintura de interiores y tratamiento de humedad superficial.', false, false),
            $m(5, 'Luis Choque (ejemplo)', 'Calefones', 'Calacoto', '70000005', 'Mantenimiento y revisión de calefones.', true, true),
            $m(6, 'Pablo Flores (ejemplo)', 'Computación e impresoras', 'Miraflores', '70000006', 'Soporte de computadoras, impresoras y fotocopiadoras.', false, false),
            $m(7, 'René Ticona (ejemplo)', 'Automotriz básico', 'Obrajes', '70000007', 'Presión de llantas, filtro de aire y batería, con ficha del vehículo.', true, false),
            $m(8, 'Walter Limachi (ejemplo)', 'Plomería', 'Villa Fátima', '70000008', 'Instalación de grifería y reparación de fugas.', false, false),
        ],
        'tarifas' => [
            $t(1, 'Plomería', 'Cambio de grifo', 80, 150), $t(2, 'Plomería', 'Reparación de fuga o gotera', 100, 250), $t(3, 'Plomería', 'Destape de cañería', 120, 300),
            $t(4, 'Electricidad', 'Cambio de tomacorriente o interruptor', 50, 100), $t(5, 'Electricidad', 'Revisión de cortocircuito', 100, 250),
            $t(6, 'Cerrajería', 'Apertura de puerta', 80, 200), $t(7, 'Cerrajería', 'Cambio de chapa', 120, 300),
            $t(8, 'Pintura', 'Pintura por m² (interiores)', 15, 35),
            $t(9, 'Calefones', 'Mantenimiento de calefón', 150, 300),
            $t(10, 'Computación e impresoras', 'Mantenimiento de impresora o fotocopiadora', 100, 250), $t(11, 'Computación e impresoras', 'Revisión y formateo de computadora', 100, 200),
            $t(12, 'Automotriz básico', 'Revisión de presión de llantas', 20, 50), $t(13, 'Automotriz básico', 'Limpieza de filtro de aire', 40, 100), $t(14, 'Automotriz básico', 'Revisión de batería', 30, 60),
        ],
        'ferreterias' => [
            ['id' => 1, 'nombre' => 'Ferretería El Tornillo (ejemplo)', 'zona' => 'Sopocachi', 'tel' => '72000001', 'convenio' => 150, 'oferta' => '5% de descuento a maestros verificados', 'estado' => 'Activo'],
            ['id' => 2, 'nombre' => 'Ferretería Central (ejemplo)', 'zona' => 'Miraflores', 'tel' => '72000002', 'convenio' => 150, 'oferta' => 'Precio diferencial en cañerías y cables', 'estado' => 'Activo'],
            ['id' => 3, 'nombre' => 'Ferretería La Llave (ejemplo)', 'zona' => 'San Pedro', 'tel' => '72000003', 'convenio' => 0, 'oferta' => 'Por definir', 'estado' => 'En negociación'],
        ],
        'resenas' => [
            ['id' => 1, 'maestro_id' => 1, 'autor' => 'Cliente de ejemplo', 'estrellas' => '5', 'comentario' => 'Llegó con las piezas correctas y el precio fue el que habíamos hablado.', 'visible' => true, 'fecha' => '2026-11-05 10:00'],
            ['id' => 2, 'maestro_id' => 2, 'autor' => 'Cliente de ejemplo', 'estrellas' => '4', 'comentario' => 'Resolvió el cortocircuito el mismo día.', 'visible' => true, 'fecha' => '2026-11-08 16:00'],
        ],
        'solicitudes' => [],
        'finanzas' => [
            ['id' => 1, 'fecha' => '2026-11-03', 'tipo' => 'Ingreso', 'concepto' => 'Convenio ferretería (piloto)', 'monto' => 150],
            ['id' => 2, 'fecha' => '2026-11-10', 'tipo' => 'Ingreso', 'concepto' => 'Insignia Maestro verificado', 'monto' => 40],
            ['id' => 3, 'fecha' => '2026-11-04', 'tipo' => 'Gasto', 'concepto' => 'Hosting y dominio (mes)', 'monto' => 50],
            ['id' => 4, 'fecha' => '2026-11-12', 'tipo' => 'Gasto', 'concepto' => 'Uso de API de IA', 'monto' => 35],
        ],
    ];
    $d = $S[$n] ?? [];
    save($n, $d);
    return $d;
}

/* ---------------------- esquema de las tablas ---------------------- */
$SCH = [
    'maestros' => ['n' => 'Maestros', 'pub' => 1, 'c' => ['nombre', 'oficio', 'zona', 'tel', 'aprobado', 'verificado', 'destacado'], 'f' => [
        'nombre' => ['l' => 'Nombre completo', 't' => 'text', 'req' => 1],
        'oficio' => ['l' => 'Servicio u oficio', 't' => 'select', 'o' => $OFICIOS],
        'zona' => ['l' => 'Zona de trabajo', 't' => 'text'],
        'tel' => ['l' => 'WhatsApp (sin +591)', 't' => 'text', 'req' => 1],
        'desc' => ['l' => 'Trabajos que realiza', 't' => 'textarea'],
        'aprobado' => ['l' => 'Aprobado por el equipo', 't' => 'check', 'adm' => 0],
        'verificado' => ['l' => 'Insignia Maestro verificado', 't' => 'check', 'adm' => 0],
        'destacado' => ['l' => 'Perfil destacado', 't' => 'check', 'adm' => 0]]],
    'tarifas' => ['n' => 'Tarifario', 'c' => ['oficio', 'trabajo', 'min', 'max'], 'f' => [
        'oficio' => ['l' => 'Servicio', 't' => 'select', 'o' => $OFICIOS],
        'trabajo' => ['l' => 'Trabajo', 't' => 'text', 'req' => 1],
        'min' => ['l' => 'Desde (Bs)', 't' => 'number'],
        'max' => ['l' => 'Hasta (Bs)', 't' => 'number']]],
    'ferreterias' => ['n' => 'Ferreterías', 'c' => ['nombre', 'zona', 'tel', 'convenio', 'oferta', 'estado'], 'f' => [
        'nombre' => ['l' => 'Nombre', 't' => 'text', 'req' => 1],
        'zona' => ['l' => 'Zona', 't' => 'text'],
        'tel' => ['l' => 'Teléfono', 't' => 'text'],
        'convenio' => ['l' => 'Convenio (Bs al mes)', 't' => 'number'],
        'oferta' => ['l' => 'Oferta o precio diferencial para maestros', 't' => 'text'],
        'estado' => ['l' => 'Estado', 't' => 'select', 'o' => ['Activo', 'En negociación', 'Inactivo']]]],
    'resenas' => ['n' => 'Reseñas', 'pub' => 1, 'c' => ['fecha', 'maestro_id', 'autor', 'estrellas', 'comentario', 'visible'], 'f' => [
        'maestro_id' => ['l' => 'Maestro', 't' => 'ref'],
        'autor' => ['l' => 'Tu nombre', 't' => 'text', 'req' => 1],
        'estrellas' => ['l' => 'Estrellas', 't' => 'select', 'o' => ['5', '4', '3', '2', '1']],
        'comentario' => ['l' => 'Comentario', 't' => 'textarea'],
        'visible' => ['l' => 'Visible al público', 't' => 'check', 'adm' => 1, 'd' => 1]]],
    'solicitudes' => ['n' => 'Solicitudes', 'pub' => 1, 'c' => ['fecha', 'cliente', 'tel', 'zona', 'oficio', 'urgencia', 'estado'], 'f' => [
        'cliente' => ['l' => 'Tu nombre', 't' => 'text', 'req' => 1],
        'tel' => ['l' => 'Tu WhatsApp', 't' => 'text', 'req' => 1],
        'zona' => ['l' => 'Zona', 't' => 'text'],
        'descripcion' => ['l' => 'Cuéntanos qué pasó', 't' => 'textarea', 'req' => 1],
        'oficio' => ['l' => 'Servicio (vacío = lo detecta el asistente)', 't' => 'select', 'o' => $OFICIOS, 'blank' => 1, 'adm' => ''],
        'urgencia' => ['l' => 'Urgencia', 't' => 'select', 'o' => ['Baja', 'Media', 'Alta'], 'blank' => 1, 'adm' => ''],
        'estado' => ['l' => 'Estado', 't' => 'select', 'o' => ['Nueva', 'Contactada', 'Cerrada'], 'adm' => 'Nueva']]],
    'finanzas' => ['n' => 'Finanzas', 'c' => ['fecha', 'tipo', 'concepto', 'monto'], 'f' => [
        'fecha' => ['l' => 'Fecha', 't' => 'date'],
        'tipo' => ['l' => 'Tipo', 't' => 'select', 'o' => ['Ingreso', 'Gasto']],
        'concepto' => ['l' => 'Concepto', 't' => 'text', 'req' => 1],
        'monto' => ['l' => 'Monto (Bs)', 't' => 'number', 'req' => 1]]],
];

/* ------------------ clasificador (simula la IA) ------------------ */
function classify($t) {
    $t = norm($t);
    $map = [
        'Plomería' => ['gotera', 'fuga', 'grifo', 'llave de agua', 'caneria', 'tuberia', 'inodoro', 'desague', 'tapad', 'agua'],
        'Electricidad' => ['luz', 'corto', 'cortocircuito', 'enchufe', 'tomacorriente', 'chispa', 'interruptor', 'cable', 'tablero', 'electric'],
        'Cerrajería' => ['chapa', 'llave', 'puerta', 'cerradura', 'candado', 'cerrajer'],
        'Pintura' => ['pintar', 'pintura', 'pared', 'humedad', 'salitre', 'descascar'],
        'Calefones' => ['calefon', 'agua caliente', 'termotanque', 'gas'],
        'Computación e impresoras' => ['impresora', 'fotocopiadora', 'computadora', 'laptop', 'pc', 'internet', 'wifi', 'toner', 'tinta'],
        'Automotriz básico' => ['llanta', 'auto', 'vehiculo', 'movilidad', 'bateria', 'neumatico', 'filtro de aire', 'gasolina'],
    ];
    $best = 'Por definir'; $sc = 0;
    foreach ($map as $o => $ks) {
        $s = 0;
        foreach ($ks as $k) if (preg_match('/\b' . preg_quote($k, '/') . '/u', $t)) $s++;
        if ($s > $sc) { $sc = $s; $best = $o; }
    }
    $urg = 'Baja';
    foreach (['gas', 'chispa', 'humo', 'quemado', 'inundacion', 'no puedo entrar', 'urgente', 'encerrad', 'corto'] as $k) if (strpos($t, $k) !== false) { $urg = 'Alta'; break; }
    if ($urg === 'Baja') foreach (['gotea', 'no funciona', 'no enciende', 'no calienta', 'roto', 'trabo', 'fuga', 'falla'] as $k) if (strpos($t, $k) !== false) { $urg = 'Media'; break; }
    return [$best, $urg];
}
function aviso($of, $txt) {
    $t = norm($txt);
    if ($of === 'Calefones' || strpos($t, 'gas') !== false) return 'Si hay olor a gas: ventila, no enciendas fuego ni interruptores, cierra la llave de gas y llama a un técnico autorizado o a emergencias. No doy pasos de reparación de gas.';
    if ($of === 'Electricidad') return 'No toques cables ni abras el tablero. Si hay chispas, humo u olor a quemado, corta la llave general solo si es seguro. No doy pasos de reparación eléctrica.';
    return 'Esta orientación es automática. El diagnóstico final lo hace el maestro en el lugar.';
}

/* -------------------------- API / backup -------------------------- */
if (($_GET['api'] ?? '') === 'solicitud' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $d = load('solicitudes');
    $r = ['id' => nid($d), 'fecha' => date('Y-m-d H:i'), 'cliente' => cut($in['nombre'] ?? ''), 'tel' => preg_replace('/\D/', '', $in['tel'] ?? ''),
          'zona' => cut($in['zona'] ?? ''), 'oficio' => cut($in['oficio'] ?? ''), 'urgencia' => in_array($in['urgencia'] ?? '', ['Baja', 'Media', 'Alta']) ? $in['urgencia'] : 'Media',
          'descripcion' => cut($in['descripcion'] ?? ''), 'estado' => 'Nueva'];
    if ($r['cliente'] === '' || strlen($r['tel']) < 7) { echo json_encode(['ok' => false, 'error' => 'Faltan datos']); exit; }
    $d[] = $r; save('solicitudes', $d);
    echo json_encode(['ok' => true, 'id' => $r['id']]); exit;
}
if (($_GET['api'] ?? '') === 'backup' && isAdmin()) {
    $all = [];
    foreach (array_keys($SCH) as $t) $all[$t] = load($t);
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="maestroya_respaldo_' . date('Ymd_His') . '.json"');
    echo json_encode($all, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT); exit;
}

/* ------------------------- acciones POST ------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = $_POST['a'] ?? '';
    if ($a === 'login') {
        if (hash_equals(ADMIN_PIN, (string)($_POST['pin'] ?? ''))) { $_SESSION['adm'] = 1; flash('Sesión del equipo iniciada.'); } else flash('PIN incorrecto.');
        go('?p=panel');
    }
    if ($a === 'logout') { unset($_SESSION['adm']); flash('Sesión cerrada.'); go('?p=inicio'); }
    if ($a === 'del' && isAdmin() && isset($SCH[$_POST['t'] ?? ''])) {
        $t = $_POST['t']; $id = (int)$_POST['id'];
        save($t, array_filter(load($t), function($x) use ($id) { return $x['id'] != $id; }));
        flash('Registro eliminado.'); go("?p=panel&t=$t");
    }
    if ($a === 'save' && isset($SCH[$_POST['t'] ?? ''])) {
        $t = $_POST['t']; $s = $SCH[$t]; $adm = isAdmin();
        if (!$adm && empty($s['pub'])) { flash('Acceso restringido al equipo.'); go('?p=panel'); }
        $id = (int)($_POST['id'] ?? 0);
        if ($id && !$adm) { flash('Solo el equipo puede editar.'); go('?p=inicio'); }
        $r = []; $err = '';
        foreach ($s['f'] as $k => $c) {
            if (array_key_exists('adm', $c) && !$adm) { $r[$k] = $c['adm']; continue; }
            $v = $_POST[$k] ?? '';
            if ($c['t'] === 'check') $r[$k] = isset($_POST[$k]);
            elseif ($c['t'] === 'number') $r[$k] = (float)str_replace(',', '.', $v);
            elseif ($c['t'] === 'ref') $r[$k] = (int)$v;
            elseif ($k === 'tel') $r[$k] = preg_replace('/\D/', '', $v);
            else $r[$k] = cut($v);
            if (!empty($c['req']) && ($r[$k] === '' || $r[$k] === 0.0)) $err = 'Completa: ' . $c['l'];
        }
        if (isset($r['tel']) && !empty($s['f']['tel']['req']) && strlen($r['tel']) < 7) $err = 'El teléfono debe tener al menos 7 dígitos.';
        if ($err) { flash($err); go($_SERVER['HTTP_REFERER'] ?? '?p=inicio'); }
        if ($t === 'solicitudes') {
            list($o, $u) = classify($r['descripcion']);
            if ($r['oficio'] === '') $r['oficio'] = $o;
            if ($r['urgencia'] === '') $r['urgencia'] = $u;
        }
        $d = load($t);
        if ($id) { foreach ($d as &$x) if ($x['id'] == $id) $x = array_merge($x, $r); unset($x); }
        else { $r['id'] = nid($d); if (empty($r['fecha'])) $r['fecha'] = date('Y-m-d H:i'); $d[] = $r; $id = $r['id']; }
        save($t, $d);
        flash($t === 'maestros' && !$adm ? 'Registro recibido. El equipo verificará tus datos antes de publicar tu perfil.' : 'Guardado correctamente.');
        if ($t === 'solicitudes' && !$adm) go("?p=ficha&id=$id");
        if ($adm) go("?p=panel&t=$t");
        go($t === 'resenas' ? '?p=resenas' : '?p=maestros');
    }
    go('?p=inicio');
}

/* ----------------------- componentes de vista ----------------------- */
function rating($mid) {
    $r = array_filter(load('resenas'), function($x) use ($mid) { return $x['maestro_id'] == $mid && !empty($x['visible']); });
    return $r ? [round(array_sum(array_column($r, 'estrellas')) / count($r), 1), count($r)] : [0, 0];
}
function opts($c, $v) {
    $o = '';
    if (!empty($c['blank'])) $o .= '<option value="">— automático —</option>';
    if ($c['t'] === 'ref') foreach (load('maestros') as $m) $o .= '<option value="' . $m['id'] . '"' . ($m['id'] == $v ? ' selected' : '') . '>' . h($m['nombre']) . '</option>';
    else foreach ($c['o'] as $x) $o .= '<option' . ($x == $v ? ' selected' : '') . '>' . h($x) . '</option>';
    return $o;
}
function form($t, $rec = [], $pre = []) {
    global $SCH; $adm = isAdmin();
    echo '<form method="post"><input type="hidden" name="a" value="save"><input type="hidden" name="t" value="' . $t . '"><input type="hidden" name="id" value="' . h($rec['id'] ?? '') . '">';
    foreach ($SCH[$t]['f'] as $k => $c) {
        if (array_key_exists('adm', $c) && !$adm) continue;
        $v = $rec[$k] ?? ($pre[$k] ?? ($c['d'] ?? ''));
        echo '<label>' . h($c['l']) . '</label>';
        switch ($c['t']) {
            case 'textarea': echo '<textarea name="' . $k . '" rows="3">' . h($v) . '</textarea>'; break;
            case 'check': echo '<input type="checkbox" name="' . $k . '" value="1" class="chk" ' . ($v ? 'checked' : '') . '>'; break;
            case 'select': case 'ref': echo '<select name="' . $k . '">' . opts($c, $v) . '</select>'; break;
            case 'number': echo '<input type="number" step="0.01" name="' . $k . '" value="' . h($v) . '">'; break;
            case 'date': echo '<input type="date" name="' . $k . '" value="' . h($v ?: date('Y-m-d')) . '">'; break;
            default: echo '<input type="text" name="' . $k . '" value="' . h($v) . '">';
        }
    }
    echo '<p><button class="btn">' . (!empty($rec['id']) ? 'Guardar cambios' : 'Guardar') . '</button></p></form>';
}
function celda($t, $k, $v) {
    global $SCH; $c = $SCH[$t]['f'][$k] ?? [];
    $ty = $c['t'] ?? '';
    if ($ty === 'check') return $v ? 'Sí' : '—';
    if ($ty === 'ref') { foreach (load('maestros') as $m) if ($m['id'] == $v) return h($m['nombre']); return '?'; }
    if ($ty === 'number') return bs($v);
    return h($v);
}
function tabla($t) {
    global $SCH; $d = array_reverse(load($t)); $s = $SCH[$t];
    echo '<div class="scroll"><table><tr>';
    foreach ($s['c'] as $k) echo '<th>' . h($s['f'][$k]['l'] ?? ucfirst($k)) . '</th>';
    echo '<th></th></tr>';
    foreach ($d as $r) {
        echo '<tr>';
        foreach ($s['c'] as $k) echo '<td>' . celda($t, $k, $r[$k] ?? '') . '</td>';
        echo '<td class="nw"><a class="btn sm s" href="?p=panel&t=' . $t . '&e=' . $r['id'] . '">Editar</a> ';
        echo '<form method="post" style="display:inline" onsubmit="return confirm(\'¿Eliminar este registro?\')"><input type="hidden" name="a" value="del"><input type="hidden" name="t" value="' . $t . '"><input type="hidden" name="id" value="' . $r['id'] . '"><button class="btn sm r">Eliminar</button></form></td></tr>';
    }
    if (!$d) echo '<tr><td colspan="9">Todavía no hay registros.</td></tr>';
    echo '</table></div>';
}
function resumen_fin() {
    $per = $_GET['per'] ?? 'm'; $g = []; $ti = $tg = 0;
    foreach (load('finanzas') as $x) {
        $ts = strtotime($x['fecha']);
        $k = $per === 'd' ? date('Y-m-d', $ts) : ($per === 's' ? date('o-\SW', $ts) : date('Y-m', $ts));
        $g[$k] = $g[$k] ?? ['i' => 0, 'g' => 0];
        if ($x['tipo'] === 'Ingreso') { $g[$k]['i'] += $x['monto']; $ti += $x['monto']; } else { $g[$k]['g'] += $x['monto']; $tg += $x['monto']; }
    }
    krsort($g);
    echo '<div class="card"><h3>Flujo de caja</h3><p>Ver por: ';
    foreach (['d' => 'Día', 's' => 'Semana', 'm' => 'Mes'] as $k => $l) echo '<a class="tag' . ($per === $k ? ' o' : '') . '" href="?p=panel&t=finanzas&per=' . $k . '">' . $l . '</a>';
    echo '</p><div class="scroll"><table><tr><th>Periodo</th><th>Ingresos (Bs)</th><th>Gastos (Bs)</th><th>Saldo (Bs)</th></tr>';
    foreach ($g as $k => $v) echo '<tr><td>' . $k . '</td><td>' . bs($v['i']) . '</td><td>' . bs($v['g']) . '</td><td><b>' . bs($v['i'] - $v['g']) . '</b></td></tr>';
    echo '<tr><td><b>Total</b></td><td><b>' . bs($ti) . '</b></td><td><b>' . bs($tg) . '</b></td><td><b>' . bs($ti - $tg) . '</b></td></tr></table></div></div>';
}
function maestro_card($m) {
    list($r, $n) = rating($m['id']);
    $msg = 'Hola ' . $m['nombre'] . ', te escribo desde MaestroYa. Necesito un servicio de ' . $m['oficio'] . '.';
    echo '<div class="card"><b>' . h($m['nombre']) . '</b><div>';
    echo '<span class="tag">' . h($m['oficio']) . '</span><span class="tag">' . h($m['zona']) . '</span>';
    if (!empty($m['verificado'])) echo '<span class="tag v">Maestro verificado</span>';
    if (!empty($m['destacado'])) echo '<span class="tag o">Destacado</span>';
    echo '</div><p>' . h($m['desc']) . '</p><p class="mut">' . ($n ? '★ ' . $r . ' (' . $n . ' reseñas)' : 'Sin reseñas todavía') . '</p>';
    echo '<a class="btn g sm" target="_blank" href="' . h(wa($m['tel'], $msg)) . '">Escribir por WhatsApp</a> <a class="btn sm s" href="?p=resenas&m=' . $m['id'] . '">Dejar reseña</a></div>';
}

/* ---------------------------- layout ---------------------------- */
$p = $_GET['p'] ?? 'inicio';
$nav = ['inicio' => 'Inicio', 'chat' => 'Asistente', 'maestros' => 'Maestros', 'pedir' => 'Pedir servicio', 'tarifario' => 'Tarifario', 'resenas' => 'Reseñas', 'ferreterias' => 'Ferreterías', 'registro' => 'Soy maestro', 'panel' => 'Panel'];
?><!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>MaestroYa – Servicios eficaces en línea</title>
<style>
:root{--az:#14233f;--na:#f26a0f;--ga:#f3f5f9;--tx:#1d2635;--mu:#667085}
*{box-sizing:border-box}
body{margin:0;font-family:"Segoe UI",system-ui,Arial,sans-serif;background:#fff;color:var(--tx);line-height:1.5}
header{background:var(--az);color:#fff;padding:10px 18px;display:flex;flex-wrap:wrap;align-items:center;gap:6px 18px}
.logo{font-size:1.45rem;font-weight:800;letter-spacing:-.5px;color:#fff;text-decoration:none}.logo span{color:var(--na)}
nav{display:flex;flex-wrap:wrap;gap:2px}
nav a{color:#dfe6f5;text-decoration:none;padding:6px 11px;border-radius:6px;font-size:.92rem}
nav a:hover{background:#ffffff1f}nav a.on{background:var(--na);color:#fff}
main{max-width:1020px;margin:22px auto;padding:0 16px}
h1,h2,h3{line-height:1.2;color:var(--az)}
.card{background:var(--ga);border-radius:10px;padding:16px 18px;margin-bottom:14px}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px}
.grid .card{margin:0}
input,select,textarea{width:100%;padding:9px 10px;border:1px solid #cfd6e4;border-radius:8px;font:inherit;background:#fff}
input.chk{width:auto}
label{display:block;margin:10px 0 3px;font-size:.88rem;font-weight:600}
.btn{display:inline-block;background:var(--na);color:#fff;border:0;border-radius:8px;padding:9px 15px;cursor:pointer;text-decoration:none;font:inherit;font-weight:600}
.btn.s{background:var(--az)}.btn.g{background:#1a9b55}.btn.r{background:#c23b2b}.btn.sm{padding:5px 10px;font-size:.84rem}
.btn:focus-visible,a:focus-visible,input:focus-visible,select:focus-visible,textarea:focus-visible,button:focus-visible{outline:3px solid #ffb27a;outline-offset:2px}
.tag{display:inline-block;background:#e3e9f6;border-radius:20px;padding:2px 10px;font-size:.78rem;margin:2px 4px 2px 0;text-decoration:none;color:var(--az)}
.tag.o{background:#ffe3cc;color:#8a3a00}.tag.v{background:#d6f3e2;color:#0e6a38}
.mut{color:var(--mu);font-size:.9rem}
table{width:100%;border-collapse:collapse;font-size:.9rem;background:#fff}
th,td{padding:8px;border-bottom:1px solid #e3e8f2;text-align:left;vertical-align:top}th{background:#e9edf6}
.scroll{overflow-x:auto}.nw{white-space:nowrap}
.flash{background:#fff1d6;border-left:4px solid var(--na);padding:10px 14px;border-radius:6px;margin-bottom:14px}
.hero{padding:30px 0 10px}.hero h1{font-size:2.3rem;margin:0 0 8px}.hero p{max-width:60ch;font-size:1.08rem}
.row{display:flex;flex-wrap:wrap;gap:10px;margin:14px 0}
footer{text-align:center;color:var(--mu);font-size:.82rem;padding:24px}
/* chat */
#chatwrap{max-width:720px;margin:0 auto}
#chat{height:56vh;min-height:320px;overflow-y:auto;background:var(--ga);border-radius:12px 12px 0 0;padding:14px;display:flex;flex-direction:column;gap:10px}
.m{max-width:88%;padding:10px 13px;border-radius:14px;white-space:pre-wrap;word-wrap:break-word}
.m.bot{background:#fff;border:1px solid #dde3f0;align-self:flex-start;border-bottom-left-radius:4px}
.m.me{background:var(--az);color:#fff;align-self:flex-end;border-bottom-right-radius:4px}
.dots i{display:inline-block;width:7px;height:7px;margin-right:4px;background:#9aa6bf;border-radius:50%;animation:b 1s infinite}
.dots i:nth-child(2){animation-delay:.15s}.dots i:nth-child(3){animation-delay:.3s}
@keyframes b{0%,80%,100%{opacity:.25}40%{opacity:1}}
#chips{background:#fff;border:1px solid #e0e5f0;border-top:0;padding:10px;display:flex;flex-wrap:wrap;gap:6px;max-height:170px;overflow-y:auto}
#chips button{background:#fff;border:1.5px solid var(--na);color:#a84500;border-radius:20px;padding:6px 12px;cursor:pointer;font:inherit;font-size:.88rem}
#chips button:hover{background:var(--na);color:#fff}
#sendrow{display:flex;gap:8px;padding:10px;background:#fff;border:1px solid #e0e5f0;border-top:0;border-radius:0 0 12px 12px}
.ficha{margin-top:10px;border-top:1px dashed #c9d2e6;padding-top:8px;white-space:normal}
.ficha table{margin:6px 0}.ficha .btn{margin:3px 3px 0 0}
@media(prefers-reduced-motion:reduce){.dots i{animation:none}}
</style></head><body>
<header><a class="logo" href="?p=inicio">Maestro<span>Ya</span></a>
<nav><?php foreach ($nav as $k => $l) echo '<a href="?p=' . $k . '" class="' . ($p === $k ? 'on' : '') . '">' . $l . '</a>'; ?></nav></header>
<main>
<?php if (!empty($_SESSION['f'])) { echo '<div class="flash">' . h($_SESSION['f']) . '</div>'; unset($_SESSION['f']); } ?>
<?php
/* ----------------------------- páginas ----------------------------- */
switch ($p) {

case 'inicio':
    $nm = count(array_filter(load('maestros'), function($m) { return !empty($m['aprobado']); }));
?>
<section class="hero">
  <h1>Servicios eficaces en línea, con confianza</h1>
  <p>Cuéntanos qué se descompuso en casa. Un asistente te hace un par de preguntas, prepara la ficha del problema y te muestra maestros de tu zona con su precio de referencia. Para los hogares es gratis.</p>
  <div class="row"><a class="btn" href="?p=chat">Hablar con el asistente</a><a class="btn s" href="?p=maestros">Ver maestros (<?= $nm ?>)</a></div>
</section>
<h2>Servicios disponibles</h2>
<div class="grid">
<?php
$desc = ['Plomería' => 'Fugas, goteras, grifería y destape de cañerías.', 'Electricidad' => 'Tomacorrientes, interruptores y revisión de cortocircuitos.', 'Cerrajería' => 'Apertura y cambio de chapas y cerraduras.',
         'Pintura' => 'Pintado de ambientes y tratamiento de humedad superficial.', 'Calefones' => 'Mantenimiento y revisión de calefones.',
         'Computación e impresoras' => 'Soporte de computadoras, impresoras y fotocopiadoras.', 'Automotriz básico' => 'Presión de llantas, filtro de aire y batería, con la ficha de tu vehículo.'];
foreach ($OFICIOS as $o) echo '<div class="card"><b>' . h($o) . '</b><p class="mut">' . h($desc[$o]) . '</p><a class="tag o" href="?p=maestros&oficio=' . urlencode($o) . '">Ver maestros</a></div>';
?>
</div>
<h2>Cómo funciona</h2>
<div class="card"><ol>
<li>Cuentas el problema en el asistente o en el formulario.</li>
<li>La IA aclara con preguntas, sugiere el servicio y la urgencia, y arma la ficha.</li>
<li>Ves maestros de tu zona, su tarifa de referencia y sus reseñas.</li>
<li>Le escribes por WhatsApp con el mensaje ya redactado.</li>
<li>El maestro visita, presenta su proforma y tú apruebas antes de cualquier cambio de costo.</li>
<li>Al terminar, verificas la calidad, pagas y dejas tu reseña.</li></ol>
<p class="mut">Los datos de esta versión son de ejemplo. El asistente orienta; no reemplaza a un técnico.</p></div>
<?php break;

case 'chat':
    $chat = ['esc' => $ESC = [
      ['q' => 'Tengo una gotera o una fuga de agua', 'oficio' => 'Plomería', 'kw' => ['gotera', 'fuga', 'agua', 'caneria', 'tuberia', 'grifo', 'llave de agua', 'inodoro', 'desague', 'gotea'],
       'intro' => 'Entiendo, vamos con la ficha del problema. Te haré dos preguntas.',
       'causa' => 'Podría ser una junta suelta, una cañería deteriorada o una filtración. El maestro lo confirmará en el lugar.',
       'preg' => [['t' => '¿Dónde ves el agua?', 'o' => [['En el techo o en una pared', 1], ['Debajo del lavamanos, lavaplatos o inodoro', 1], ['Sale agua de una cañería rota', 3]]],
                  ['t' => '¿Desde cuándo ocurre?', 'o' => [['Desde hoy', 2], ['Hace unos días', 1], ['Hace semanas', 0]]]],
       'aviso' => 'Cierra la llave de paso mientras llega el maestro. Si el agua toca enchufes o el tablero, corta la luz de ese sector antes de acercarte.'],
      ['q' => 'Se cortó la luz o creo que hubo un cortocircuito', 'oficio' => 'Electricidad', 'kw' => ['luz', 'corto', 'cortocircuito', 'enchufe', 'tomacorriente', 'chispa', 'interruptor', 'tablero', 'electric', 'apagon'],
       'intro' => 'Entiendo. Antes de nada, dos preguntas para armar la ficha.',
       'causa' => 'Puede ser una sobrecarga, un cable en mal estado o un tomacorriente dañado. El maestro lo revisará con instrumentos.',
       'preg' => [['t' => '¿Notaste chispas, humo u olor a quemado?', 'o' => [['Sí', 3], ['No', 0]]],
                  ['t' => '¿Dónde falla?', 'o' => [['En toda la casa', 1], ['En un solo ambiente', 0], ['En un enchufe o interruptor', 0]]]],
       'aviso' => 'No toques cables ni abras el tablero. Si hay chispas, humo u olor a quemado, corta la llave general solo si es seguro. En electricidad no doy pasos de reparación.'],
      ['q' => 'Se trabó la chapa o perdí la llave', 'oficio' => 'Cerrajería', 'kw' => ['chapa', 'llave', 'cerradura', 'cerrajero', 'puerta', 'candado', 'trabo', 'trabada'],
       'intro' => 'Entiendo. Dos preguntas rápidas.',
       'causa' => 'Puede ser un mecanismo desgastado o una llave dañada. El cerrajero evalúa si basta abrir o hay que cambiar la chapa.',
       'preg' => [['t' => '¿Estás fuera de casa sin poder entrar?', 'o' => [['Sí, no puedo entrar', 3], ['No, estoy dentro', 0]]],
                  ['t' => '¿Qué ocurre exactamente?', 'o' => [['La llave no gira', 1], ['Perdí la llave', 1], ['Quiero cambiar la chapa', 0]]]],
       'aviso' => 'El cerrajero puede pedirte comprobar que eres el ocupante del inmueble antes de abrir.'],
      ['q' => 'Quiero pintar o tengo humedad en la pared', 'oficio' => 'Pintura', 'kw' => ['pintar', 'pintura', 'pared', 'humedad', 'salitre', 'descascara', 'mancha'],
       'intro' => 'Perfecto, dos preguntas para la ficha.',
       'causa' => 'Si hay humedad, conviene encontrar su origen antes de pintar; si no, la pintura se vuelve a dañar.',
       'preg' => [['t' => '¿Hay humedad o manchas verdes o negras?', 'o' => [['Sí, hay humedad', 1], ['No, solo quiero renovar', 0]]],
                  ['t' => '¿Cuántos ambientes aproximadamente?', 'o' => [['Uno', 0], ['Dos o tres', 0], ['Toda la casa', 0]]]],
       'aviso' => 'Si la humedad viene de una fuga, el maestro te sugerirá resolver primero la fuga.'],
      ['q' => 'Mi calefón no calienta o necesita mantenimiento', 'oficio' => 'Calefones', 'kw' => ['calefon', 'agua caliente', 'termotanque', 'gas', 'olor a gas'],
       'intro' => 'Entiendo. Primero lo más importante, tu seguridad.',
       'causa' => 'Puede ser acumulación de sarro, falla del encendido o del suministro. El técnico revisa con el equipo en el lugar.',
       'preg' => [['t' => '¿Sientes olor a gas?', 'o' => [['Sí', 4], ['No', 0]]],
                  ['t' => '¿Cuál es el problema?', 'o' => [['No calienta', 1], ['Sale poca agua caliente', 0], ['Mantenimiento preventivo', 0]]]],
       'aviso' => 'Si hay olor a gas: ventila, no enciendas fuego ni interruptores, cierra la llave de gas y llama a un técnico autorizado o a emergencias. No doy pasos de reparación de gas.'],
      ['q' => 'Falla mi impresora, fotocopiadora o computadora', 'oficio' => 'Computación e impresoras', 'kw' => ['impresora', 'fotocopiadora', 'computadora', 'laptop', 'pc', 'toner', 'tinta', 'internet', 'wifi', 'lenta'],
       'intro' => 'Claro. Ten a mano la marca y el modelo del equipo. Dos preguntas.',
       'causa' => 'Puede ser un atasco, un consumible agotado, un controlador o un problema de software. El técnico lo confirma al revisar el equipo.',
       'preg' => [['t' => '¿Qué equipo es?', 'o' => [['Impresora o fotocopiadora', 0], ['Computadora o laptop', 0], ['Internet o wifi', 0]]],
                  ['t' => '¿Qué sucede?', 'o' => [['No enciende', 1], ['Imprime mal o se atasca', 0], ['Está lenta', 0]]]],
       'aviso' => 'Antes de la visita, respalda tus archivos importantes.'],
      ['q' => 'Quiero revisar la llanta, el filtro de aire o la batería de mi vehículo', 'oficio' => 'Automotriz básico', 'kw' => ['llanta', 'auto', 'vehiculo', 'bateria', 'neumatico', 'filtro', 'movilidad', 'presion', 'gasolina'],
       'intro' => 'Buena idea, son revisiones básicas que ahorran combustible. Dos preguntas.',
       'causa' => 'El maestro consulta la ficha de tu vehículo (marca y modelo) y mide la presión delante de ti: por ejemplo, si marca 30 libras y debería tener 35, la llanta está baja y consume más gasolina.',
       'preg' => [['t' => '¿Qué quieres revisar?', 'o' => [['Presión de las llantas', 0], ['Filtro de aire', 0], ['Batería', 1]]],
                  ['t' => '¿Qué tipo de vehículo tienes?', 'o' => [['Auto pequeño o sedán', 0], ['Vagoneta o camioneta', 0], ['Minibús o vehículo de trabajo', 0]]]],
       'aviso' => 'Son servicios básicos a domicilio. Fallas mecánicas mayores requieren taller.'],
    ], 'faq' => [
      ['q' => '¿Cómo funciona MaestroYa?', 'kw' => ['funciona', 'como es', 'que es maestroya', 'para que sirve'], 'a' => 'Cuentas qué se descompuso, yo hago un par de preguntas de aclaración y armo una ficha con el servicio, la urgencia y un precio de referencia. Luego te muestro maestros de tu zona, eliges uno y le escribes por WhatsApp con el mensaje ya redactado. Al terminar puedes dejar tu reseña.'],
      ['q' => '¿Cuánto cuesta usar la aplicación?', 'kw' => ['cuesta', 'gratis', 'cobran', 'pagar la app', 'costo de la app'], 'a' => 'Para los hogares es gratis, y el perfil básico del maestro también. Los ingresos de MaestroYa vienen de convenios con ferreterías, espacio publicitario, la insignia de Maestro verificado, perfiles destacados y, más adelante, convenios de mantenimiento preventivo.'],
      ['q' => '¿Cómo sé que el maestro es de confianza?', 'kw' => ['confianza', 'confiable', 'seguro', 'robo', 'verificado', 'verificacion'], 'a' => 'Los maestros pasan por una verificación de identidad hecha por una persona del equipo, tienen perfil con sus trabajos y reseñas de clientes, y puedes reportar cualquier problema. Tu dirección exacta solo se comparte con el maestro que elijas.'],
      ['q' => '¿Cómo se calcula el precio?', 'kw' => ['precio', 'tarifa', 'cuanto', 'cobra', 'costo'], 'a' => 'El precio que muestro es un rango de referencia que sale del tarifario de MaestroYa, armado con datos de maestros y ferreterías y actualizado de forma permanente. El maestro confirma el costo final con su proforma. Si el costo cambia por un repuesto o material, debe avisarte y tú apruebas antes de continuar.'],
      ['q' => 'Soy maestro, ¿cómo me registro?', 'kw' => ['registrar', 'registro', 'soy maestro', 'soy tecnico', 'perfil', 'unirme'], 'a' => 'En la sección “Soy maestro” creas tu perfil con servicio, zona, teléfono y trabajos. El equipo verifica tus datos y, al aprobarte, apareces en la lista. Los pedidos te llegan por WhatsApp.'],
      ['q' => 'Soy ferretería, ¿qué gano?', 'kw' => ['ferreteria', 'ferreterias', 'convenio', 'publicidad', 'anunciar'], 'a' => 'Con un convenio apareces junto a los maestros que trabajan cerca, tienes espacio publicitario en la aplicación y puedes ofrecer precios diferenciales a los maestros que te compran materiales.'],
      ['q' => '¿Qué servicios ofrecen?', 'kw' => ['servicios', 'ofrecen', 'hacen', 'oficios', 'que hacen'], 'a' => 'Plomería, electricidad, cerrajería, pintura, mantenimiento de calefones, soporte de computadoras, impresoras y fotocopiadoras, y servicios automotrices básicos como revisar la presión de llantas, el filtro de aire o la batería.'],
      ['q' => '¿La IA da diagnósticos definitivos?', 'kw' => ['diagnostico', 'ia', 'inteligencia', 'robot', 'eres una ia', 'eres humano'], 'a' => 'No. Soy un asistente automático: oriento, sugiero el servicio y la urgencia y preparo la ficha. El diagnóstico final lo hace el maestro en el lugar. En gas y electricidad no doy pasos de reparación, solo medidas básicas de seguridad.'],
      ['q' => '¿Hay mantenimiento preventivo?', 'kw' => ['mantenimiento', 'preventivo', 'mensual', 'trimestral', 'periodico'], 'a' => 'Está pensado como convenio: un maestro de tu confianza te visita cada mes o cada trimestre para revisar el calefón, las instalaciones, los equipos o el vehículo, con una tarifa acordada. Es una función de la siguiente fase.'],
      ['q' => '¿Qué pasa con mis datos?', 'kw' => ['datos', 'privacidad', 'direccion', 'informacion personal'], 'a' => 'Solo guardo tu nombre, teléfono, zona y la descripción del problema. Tu dirección exacta solo se comparte con el maestro que tú elijas.'],
    ], 'tarifas' => load('tarifas'), 'maestros' => array_values(array_filter(load('maestros'), function($m) { return !empty($m['aprobado']); }))];
?>
<h2>Asistente de MaestroYa</h2>
<p class="mut">Soy un asistente automático con preguntas prediseñadas: oriento, no reemplazo a un técnico. Elige una opción o escribe tu problema.</p>
<div id="chatwrap">
  <div id="chat" aria-live="polite"></div>
  <div id="chips"></div>
  <div id="sendrow"><input id="inp" type="text" placeholder="Escribe aquí, por ejemplo: se me trabó la chapa" autocomplete="off"><button class="btn" id="send">Enviar</button></div>
</div>
<script>const D=<?= json_encode($chat, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
<script>
<?php echo <<<'JS'
const box=document.getElementById('chat'),chips=document.getElementById('chips'),inp=document.getElementById('inp');
let st={mode:'free'},busy=false;
const sleep=ms=>new Promise(r=>setTimeout(r,ms));
const norm=s=>s.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'');
const esc=s=>String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
function bubble(cls){const d=document.createElement('div');d.className='m '+cls;box.appendChild(d);box.scrollTop=box.scrollHeight;return d}
function me(t){bubble('me').textContent=t}
async function say(text,html){
  busy=true;chips.innerHTML='';
  const t=bubble('bot');t.innerHTML='<span class="dots"><i></i><i></i><i></i></span>';
  await sleep(450+Math.min(text.length*3,800));
  t.textContent='';
  for(let i=0;i<text.length;i+=2){t.textContent+=text.slice(i,i+2);box.scrollTop=box.scrollHeight;await sleep(12);}
  if(html){const x=document.createElement('div');x.className='ficha';x.innerHTML=html;t.appendChild(x);box.scrollTop=box.scrollHeight;}
  busy=false;
}
function setChips(items){chips.innerHTML='';items.forEach(([l,f])=>{const b=document.createElement('button');b.textContent=l;b.onclick=()=>{if(busy)return;chips.innerHTML='';me(l);f();};chips.appendChild(b);});}
function menu(){setChips([...D.esc.map(e=>[e.q,()=>startEsc(e)]),['Preguntas frecuentes sobre MaestroYa',faqMenu]]);}
function faqMenu(){setChips([...D.faq.map(f=>[f.q,()=>faq(f)]),['Tengo un problema en casa',menu]]);}
async function faq(f){await say(f.a);setChips([['Otra pregunta frecuente',faqMenu],['Tengo un problema en casa',menu]]);}
async function startEsc(e){st={mode:'esc',esc:e,i:0,pts:0,resp:[]};await say(e.intro);askQ();}
async function askQ(){const q=st.esc.preg[st.i];await say(q.t);setChips(q.o.map(o=>[o[0],()=>answer(o)]));}
function answer(o){st.pts+=o[1];st.resp.push(o[0]);st.i++;st.i<st.esc.preg.length?askQ():ficha();}
async function ficha(){
  const e=st.esc,u=st.pts>=3?'Alta':st.pts>=1?'Media':'Baja';
  const tf=D.tarifas.filter(t=>t.oficio==e.oficio).slice(0,3);
  const ms=D.maestros.filter(m=>m.oficio==e.oficio).sort((a,b)=>(b.destacado?1:0)-(a.destacado?1:0)).slice(0,3);
  const resumen=e.q+'. Respuestas: '+st.resp.join('; ')+'. Urgencia: '+u+'.';
  st.last={oficio:e.oficio,urgencia:u,descripcion:resumen};
  let h='<b>Ficha del problema</b><table><tr><td>Servicio sugerido</td><td><b>'+esc(e.oficio)+'</b></td></tr><tr><td>Urgencia</td><td><b>'+u+'</b></td></tr><tr><td>Lo que contaste</td><td>'+esc(st.resp.join(' · '))+'</td></tr></table>';
  h+='<p><b>Qué podría ser:</b> '+esc(e.causa)+'</p><p><b>Seguridad:</b> '+esc(e.aviso)+'</p>';
  if(tf.length){h+='<b>Precio de referencia</b><table>'+tf.map(t=>'<tr><td>'+esc(t.trabajo)+'</td><td>Bs '+t.min+' – '+t.max+'</td></tr>').join('')+'</table><span class="mut">Estimado: el maestro confirma el costo con su proforma.</span>';}
  h+='<p><b>Maestros de tu zona</b></p>';
  h+=ms.length?ms.map(m=>{const msg='Hola '+m.nombre+', te escribo desde MaestroYa. '+resumen;return '<div>'+esc(m.nombre)+' · '+esc(m.zona)+(m.verificado?' · verificado':'')+'<br><a class="btn g sm" target="_blank" href="https://wa.me/591'+m.tel+'?text='+encodeURIComponent(msg)+'">Escribir por WhatsApp</a></div>';}).join(''):'<span class="mut">Aún no hay maestros aprobados para este servicio.</span>';
  await say('Listo, ya preparé la ficha para el maestro.',h);
  setChips([['Guardar mi solicitud',startSave],['Tengo otro problema',menu],['Preguntas frecuentes',faqMenu]]);
}
async function startSave(){st.mode='collect';st.fields=[['nombre','¿Cómo te llamas?'],['tel','¿Cuál es tu número de WhatsApp?'],['zona','¿En qué zona estás?']];st.form={};st.k=0;await say(st.fields[0][1]);}
async function collect(t){
  st.form[st.fields[st.k][0]]=t;st.k++;
  if(st.k<st.fields.length){await say(st.fields[st.k][1]);return;}
  st.mode='free';
  const body=Object.assign({},st.form,st.last);
  try{
    const r=await fetch('?api=solicitud',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)});
    const j=await r.json();
    await say(j.ok?'Tu solicitud #'+j.id+' quedó guardada. El equipo y los maestros pueden verla en el panel.':'No pude guardarla: '+(j.error||'error'));
  }catch(e){await say('No pude conectar con el servidor. Revisa que XAMPP (Apache) esté encendido.');}
  setChips([['Tengo otro problema',menu],['Preguntas frecuentes',faqMenu]]);
}
function match(t){
  t=norm(t);let best=null,sc=0;
  [...D.esc.map(x=>({k:'e',x})),...D.faq.map(x=>({k:'f',x}))].forEach(c=>{let s=0;c.x.kw.forEach(k=>{if(t.includes(norm(k)))s+=k.length>4?2:1;});if(s>sc){sc=s;best=c;}});
  return best;
}
async function send(){
  const t=inp.value.trim();if(!t||busy)return;inp.value='';me(t);
  if(st.mode==='collect'){collect(t);return;}
  if(/^(hola|buenas|buen dia|buenos dias|buenas tardes|buenas noches)\b/.test(norm(t))){await say('¡Hola! Cuéntame qué se descompuso en casa o elige una opción.');menu();return;}
  const b=match(t);
  if(b&&b.k==='e')startEsc(b.x);else if(b)faq(b.x);
  else{await say('Todavía no tengo una respuesta preparada para eso. Puedo ayudarte con estos temas:');menu();}
}
document.getElementById('send').onclick=send;
inp.addEventListener('keydown',e=>{if(e.key==='Enter')send();});
(async()=>{await say('Hola, soy el asistente de MaestroYa. Soy un asistente automático: oriento, pero el diagnóstico final lo hace el maestro en el lugar. ¿Qué necesitas?');menu();})();
JS;
?>
</script>
<?php break;

case 'maestros':
    $of = $_GET['oficio'] ?? ''; $z = norm($_GET['zona'] ?? '');
    $l = array_filter(load('maestros'), function($m) use ($of, $z) { return !empty($m['aprobado']) && ($of === '' || $m['oficio'] === $of) && ($z === '' || strpos(norm($m['zona']), $z) !== false); });
    usort($l, function($a, $b) { return (int)!empty($b['destacado']) <=> (int)!empty($a['destacado']); });
?>
<h2>Maestros de tu zona</h2>
<form method="get" class="card"><input type="hidden" name="p" value="maestros">
<div class="grid"><div><label>Servicio</label><select name="oficio"><option value="">Todos</option><?php foreach ($OFICIOS as $o) echo '<option' . ($o === $of ? ' selected' : '') . '>' . h($o) . '</option>'; ?></select></div>
<div><label>Zona</label><input name="zona" value="<?= h($_GET['zona'] ?? '') ?>" placeholder="Ej.: Sopocachi"></div></div>
<p><button class="btn">Buscar</button></p></form>
<div class="grid"><?php foreach ($l as $m) maestro_card($m); if (!$l) echo '<p>No hay maestros con ese filtro.</p>'; ?></div>
<?php break;

case 'registro': ?>
<h2>Soy maestro: registrar mi perfil</h2>
<p class="mut">El perfil básico es gratis. El equipo verifica tus datos antes de publicarlo; luego recibirás los pedidos por WhatsApp.</p>
<div class="card"><?php form('maestros'); ?></div>
<?php break;

case 'pedir': ?>
<h2>Pedir un servicio</h2>
<p class="mut">Describe el problema con tus palabras. El asistente detecta el servicio y la urgencia y arma la ficha. ¿Prefieres conversar? <a href="?p=chat">Usa el asistente</a>.</p>
<div class="card"><?php form('solicitudes'); ?></div>
<?php break;

case 'ficha':
    $s = null; foreach (load('solicitudes') as $x) if ($x['id'] == (int)($_GET['id'] ?? 0)) $s = $x;
    if (!$s) { echo '<p>No encontré la solicitud.</p>'; break; }
    $ms = array_filter(load('maestros'), function($m) use ($s) { return !empty($m['aprobado']) && $m['oficio'] === $s['oficio']; });
    usort($ms, function($a, $b) { return (int)!empty($b['destacado']) <=> (int)!empty($a['destacado']); });
    $tf = array_filter(load('tarifas'), function($t) use ($s) { return $t['oficio'] === $s['oficio']; });
?>
<h2>Ficha del problema #<?= (int)$s['id'] ?></h2>
<div class="card"><table>
<tr><td>Cliente</td><td><?= h($s['cliente']) ?> · <?= h($s['tel']) ?> · <?= h($s['zona']) ?></td></tr>
<tr><td>Descripción</td><td><?= h($s['descripcion']) ?></td></tr>
<tr><td>Servicio sugerido</td><td><b><?= h($s['oficio']) ?></b></td></tr>
<tr><td>Urgencia</td><td><b><?= h($s['urgencia']) ?></b></td></tr>
<tr><td>Seguridad</td><td><?= h(aviso($s['oficio'], $s['descripcion'])) ?></td></tr></table>
<p class="mut">Orientación automática: el diagnóstico final lo hace el maestro en el lugar.</p></div>
<?php if ($tf) { echo '<div class="card"><b>Precio de referencia</b><table>'; foreach ($tf as $t) echo '<tr><td>' . h($t['trabajo']) . '</td><td>Bs ' . bs($t['min']) . ' – ' . bs($t['max']) . '</td></tr>'; echo '</table></div>'; } ?>
<h3>Maestros disponibles</h3>
<div class="grid"><?php
foreach ($ms as $m) {
    list($r, $n) = rating($m['id']);
    $msg = 'Hola ' . $m['nombre'] . ', te escribo desde MaestroYa. ' . $s['descripcion'] . ' (Urgencia: ' . $s['urgencia'] . ')';
    echo '<div class="card"><b>' . h($m['nombre']) . '</b><div><span class="tag">' . h($m['zona']) . '</span>' . (!empty($m['verificado']) ? '<span class="tag v">Maestro verificado</span>' : '') . '</div><p class="mut">' . ($n ? '★ ' . $r . ' (' . $n . ')' : 'Sin reseñas') . '</p><a class="btn g sm" target="_blank" href="' . h(wa($m['tel'], $msg)) . '">Escribir por WhatsApp</a></div>';
}
if (!$ms) echo '<p>Todavía no hay maestros aprobados para este servicio. El equipo revisará tu solicitud.</p>';
?></div>
<?php break;

case 'tarifario': ?>
<h2>Tarifario de referencia</h2>
<p class="mut">Valores estimados en bolivianos (Bs), de ejemplo para el prototipo. El maestro confirma el costo final con su proforma; si el costo cambia durante el trabajo, tú apruebas antes de continuar.</p>
<?php foreach ($OFICIOS as $o) {
    $r = array_filter(load('tarifas'), function($t) use ($o) { return $t['oficio'] === $o; }); if (!$r) continue;
    echo '<div class="card"><b>' . h($o) . '</b><table>';
    foreach ($r as $t) echo '<tr><td>' . h($t['trabajo']) . '</td><td class="nw">Bs ' . bs($t['min']) . ' – ' . bs($t['max']) . '</td></tr>';
    echo '</table></div>';
}
break;

case 'resenas':
    $mid = (int)($_GET['m'] ?? 0);
    $names = array_column(load('maestros'), 'nombre', 'id');
?>
<h2>Reseñas</h2>
<div class="card"><b>Dejar una reseña</b><?php form('resenas', [], ['maestro_id' => $mid]); ?></div>
<?php foreach (array_reverse(load('resenas')) as $r) if (!empty($r['visible']) && (!$mid || $r['maestro_id'] == $mid))
    echo '<div class="card"><b>' . h($names[$r['maestro_id']] ?? 'Maestro') . '</b> <span class="tag o">★ ' . h($r['estrellas']) . '</span><p>' . h($r['comentario']) . '</p><span class="mut">' . h($r['autor']) . ' · ' . h($r['fecha']) . '</span></div>';
break;

case 'ferreterias': ?>
<h2>Ferreterías con convenio</h2>
<p class="mut">Locales aliados donde los maestros compran materiales, con ofertas para quienes trabajan con MaestroYa.</p>
<div class="grid"><?php foreach (load('ferreterias') as $f) if ($f['estado'] === 'Activo')
    echo '<div class="card"><b>' . h($f['nombre']) . '</b><div><span class="tag">' . h($f['zona']) . '</span></div><p>' . h($f['oferta']) . '</p><span class="mut">Tel. ' . h($f['tel']) . '</span></div>'; ?></div>
<?php break;

case 'panel':
    if (!isAdmin()) { ?>
<h2>Panel del equipo</h2>
<div class="card" style="max-width:380px"><form method="post"><input type="hidden" name="a" value="login"><label>PIN del equipo</label><input type="password" name="pin" autofocus><p><button class="btn">Entrar</button></p></form></div>
<?php break; }
    $t = $_GET['t'] ?? 'maestros'; if (!isset($SCH[$t])) $t = 'maestros';
    $rec = []; if (isset($_GET['e'])) foreach (load($t) as $x) if ($x['id'] == (int)$_GET['e']) $rec = $x;
    $pend = count(array_filter(load('maestros'), function($m) { return empty($m['aprobado']); }));
    $nuevas = count(array_filter(load('solicitudes'), function($s) { return $s['estado'] === 'Nueva'; }));
?>
<h2>Panel del equipo</h2>
<p><?php foreach ($SCH as $k => $s) echo '<a class="tag' . ($t === $k ? ' o' : '') . '" href="?p=panel&t=' . $k . '">' . h($s['n']) . '</a>'; ?>
<a class="tag" href="?api=backup">Descargar respaldo (JSON)</a></p>
<p class="mut">Maestros pendientes de aprobar: <b><?= $pend ?></b> · Solicitudes nuevas: <b><?= $nuevas ?></b></p>
<?php if ($t === 'finanzas') resumen_fin(); ?>
<div class="card"><b><?= $rec ? 'Editar registro #' . (int)$rec['id'] : 'Nuevo registro en ' . h($SCH[$t]['n']) ?></b><?php form($t, $rec); ?><?php if ($rec) echo '<a href="?p=panel&t=' . $t . '">Cancelar edición</a>'; ?></div>
<h3><?= h($SCH[$t]['n']) ?></h3>
<?php tabla($t); ?>
<form method="post" style="margin-top:16px"><input type="hidden" name="a" value="logout"><button class="btn s sm">Cerrar sesión del equipo</button></form>
<?php break;

default: echo '<p>Página no encontrada.</p>';
}
?>
</main>
<footer>MaestroYa · Prototipo académico (UMSA, Emprendimiento e Innovación Tecnológica) · Datos de ejemplo en /data</footer>
</body></html>
