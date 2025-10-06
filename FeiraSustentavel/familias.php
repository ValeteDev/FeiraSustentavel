<?php
// familias.php — CRUD de Famílias (Usuario.tipo='familia' + tabela Familia)
declare(strict_types=1);
session_start();
require_once __DIR__ . '/conexao.php';

// Helpers
function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function csrf(): string {
  if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
  return $_SESSION['csrf'];
}
function check_csrf(): void {
  if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? '')) {
    http_response_code(403); exit('CSRF inválido.');
  }
}
function flash(string $m, string $t='success'): void { $_SESSION['flash']=['m'=>$m,'t'=>$t]; }
function get_flash(): ?array { $f=$_SESSION['flash']??null; unset($_SESSION['flash']); return $f; }

$action = $_GET['action'] ?? 'list';

// Store
if ($action==='store' && $_SERVER['REQUEST_METHOD']==='POST') {
  check_csrf();
  $nome = trim($_POST['nome'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $telefone = trim($_POST['telefone'] ?? '');
  $endereco = trim($_POST['endereco'] ?? '');
  $num_membros = (int)($_POST['num_membros'] ?? 0);
  $situacao = trim($_POST['situacao'] ?? '');
  $renda_mensal = $_POST['renda_mensal'] !== '' ? (float)$_POST['renda_mensal'] : null;

  $erros=[];
  if ($nome==='') $erros[]='Nome obrigatório.';
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erros[]='E-mail inválido.';
  if ($num_membros<=0) $erros[]='Número de membros inválido.';
  if (!in_array($situacao, ['baixa_renda','vulnerabilidade','outra'], true)) $erros[]='Situação inválida.';

  if ($erros) { flash(implode(' ', $erros),'error'); header('Location: ?action=create'); exit; }

  try {
    $pdo->beginTransaction();
    // Cria Usuario do tipo 'familia'
    $u = $pdo->prepare('INSERT INTO Usuario (nome, email, tipo, telefone, endereco) VALUES (?,?,?,?,?)');
    $u->execute([$nome, $email, 'familia', $telefone, $endereco]);
    $usuario_id = (int)$pdo->lastInsertId();

    // Cria Família
    $f = $pdo->prepare('INSERT INTO Familia (usuario_id, num_membros, renda_mensal, situacao) VALUES (?,?,?,?)');
    $f->execute([$usuario_id, $num_membros, $renda_mensal, $situacao]);

    $pdo->commit();
    flash('Família cadastrada com sucesso!');
    header('Location: ?action=list'); exit;
  } catch (Throwable $e) {
    $pdo->rollBack();
    if ($e instanceof PDOException && $e->getCode()==='23000') {
      flash('E-mail já cadastrado para outra família/usuário.','error');
    } else {
      flash('Erro ao cadastrar: '.$e->getMessage(),'error');
    }
    header('Location: ?action=create'); exit;
  }
}

// Update
if ($action==='update' && $_SERVER['REQUEST_METHOD']==='POST') {
  check_csrf();
  $familia_id = (int)($_POST['familia_id'] ?? 0);
  $usuario_id = (int)($_POST['usuario_id'] ?? 0);
  $nome = trim($_POST['nome'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $telefone = trim($_POST['telefone'] ?? '');
  $endereco = trim($_POST['endereco'] ?? '');
  $num_membros = (int)($_POST['num_membros'] ?? 0);
  $situacao = trim($_POST['situacao'] ?? '');
  $renda_mensal = $_POST['renda_mensal'] !== '' ? (float)$_POST['renda_mensal'] : null;

  $erros=[];
  if ($familia_id<=0 || $usuario_id<=0) $erros[]='IDs inválidos.';
  if ($nome==='') $erros[]='Nome obrigatório.';
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erros[]='E-mail inválido.';
  if ($num_membros<=0) $erros[]='Número de membros inválido.';
  if (!in_array($situacao, ['baixa_renda','vulnerabilidade','outra'], true)) $erros[]='Situação inválida.';

  if ($erros) { flash(implode(' ', $erros),'error'); header('Location: ?action=edit&id='.$familia_id); exit; }

  try {
    $pdo->beginTransaction();
    $u = $pdo->prepare('UPDATE Usuario SET nome=?, email=?, telefone=?, endereco=? WHERE id=? AND tipo="familia"');
    $u->execute([$nome, $email, $telefone, $endereco, $usuario_id]);

    $f = $pdo->prepare('UPDATE Familia SET num_membros=?, renda_mensal=?, situacao=? WHERE id=? AND usuario_id=?');
    $f->execute([$num_membros, $renda_mensal, $situacao, $familia_id, $usuario_id]);

    $pdo->commit();
    flash('Família atualizada com sucesso!');
    header('Location: ?action=list'); exit;
  } catch (Throwable $e) {
    $pdo->rollBack();
    if ($e instanceof PDOException && $e->getCode()==='23000') {
      flash('E-mail já em uso por outro usuário.','error');
    } else {
      flash('Erro ao atualizar: '.$e->getMessage(),'error');
    }
    header('Location: ?action=edit&id='.$familia_id); exit;
  }
}

// Destroy
if ($action==='destroy' && $_SERVER['REQUEST_METHOD']==='POST') {
  check_csrf();
  $familia_id = (int)($_POST['familia_id'] ?? 0);
  $usuario_id = (int)($_POST['usuario_id'] ?? 0);

  if ($familia_id<=0 || $usuario_id<=0) {
    flash('IDs inválidos.','error'); header('Location: ?action=list'); exit;
  }

  try {
    $pdo->beginTransaction();
    // Exclui a família (pode falhar se houver Reservas vinculadas)
    $pdo->prepare('DELETE FROM Familia WHERE id=?')->execute([$familia_id]);
    // Exclui o usuário do tipo 'familia'
    $pdo->prepare('DELETE FROM Usuario WHERE id=? AND tipo="familia"')->execute([$usuario_id]);
    $pdo->commit();
    flash('Família excluída com sucesso!');
  } catch (Throwable $e) {
    $pdo->rollBack();
    if ($e instanceof PDOException && $e->getCode()==='23000') {
      flash('Não é possível excluir: existem registros vinculados (ex.: Reservas).','error');
    } else {
      flash('Erro ao excluir: '.$e->getMessage(),'error');
    }
  }
  header('Location: ?action=list'); exit;
}

// View
$flash = get_flash();
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <title>Famílias — Feira Sustentável</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    :root { --bg:#0f172a; --card:#111827; --text:#e5e7eb; --mut:#9ca3af; --bd:#1f2937; --pri:#22c55e; --sec:#3b82f6; --danger:#ef4444; }
    *{box-sizing:border-box} body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Arial;background:var(--bg);color:var(--text)}
    .container{max-width:980px;margin:28px auto;padding:0 16px}
    .card{background:var(--card);border:1px solid var(--bd);border-radius:12px;padding:18px}
    a.btn,button.btn{padding:10px 14px;border:0;border-radius:8px;font-weight:700;cursor:pointer;text-decoration:none}
    a.btn{background:var(--pri);color:#062015} a.btn.secondary{background:var(--sec);color:#081428} button.btn.danger{background:var(--danger);color:#fff}
    .toolbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:14px}
    .flash{padding:12px 14px;border-radius:8px;margin-bottom:12px;font-weight:600}
    .flash.success{background:rgba(34,197,94,.12);border:1px solid #14532d}
    .flash.error{background:rgba(239,68,68,.12);border:1px solid #7f1d1d}
    table{width:100%;border-collapse:collapse} th,td{padding:10px;border-bottom:1px solid var(--bd);text-align:left} th{color:var(--mut);font-weight:700}
    input,select,textarea{width:100%;padding:10px 12px;border-radius:8px;border:1px solid var(--bd);background:#0b1220;color:var(--text)}
    form.grid{display:grid;gap:12px;grid-template-columns:1fr 1fr}
    @media(max-width:640px){form.grid{grid-template-columns:1fr}}
  </style>
</head>
<body>
<div class="container">
  <div class="card">
    <div class="toolbar">
      <h1>Famílias</h1>
      <?php if (!in_array($action, ['create','edit'])): ?>
        <a class="btn" href="?action=create">+ Nova família</a>
      <?php else: ?>
        <a class="btn secondary" href="?action=list">← Voltar</a>
      <?php endif; ?>
    </div>

    <?php if ($flash): ?>
      <div class="flash <?= e($flash['t']) ?>"><?= e($flash['m']) ?></div>
    <?php endif; ?>

    <?php if ($action==='list'): ?>
      <?php
        $q = trim($_GET['q'] ?? '');
        $params=[]; $where=[];
        if ($q!=='') { $where[]='(u.nome LIKE :q OR u.email LIKE :q OR u.telefone LIKE :q)'; $params[':q']="%$q%"; }
        $sql = "SELECT f.id AS familia_id, u.id AS usuario_id, u.nome, u.email, u.telefone, u.endereco, u.data_cadastro,
                       f.num_membros, f.renda_mensal, f.situacao
                FROM Familia f
                JOIN Usuario u ON u.id=f.usuario_id
                WHERE u.tipo='familia' " . ($where? 'AND '.implode(' AND ',$where):'') . " ORDER BY f.id DESC";
        $st = $pdo->prepare($sql); $st->execute($params); $rows=$st->fetchAll();
      ?>
      <form method="get" style="margin-bottom:12px;display:flex;gap:8px">
        <input type="hidden" name="action" value="list">
        <input type="text" name="q" placeholder="Buscar (nome, e-mail, telefone)..." value="<?= e($q) ?>">
        <button class="btn" type="submit">Buscar</button>
      </form>
      <table>
        <thead><tr><th>ID</th><th>Nome</th><th>Email</th><th>Membros</th><th>Situação</th><th>Renda</th><th>Ações</th></tr></thead>
        <tbody>
          <?php if (!$rows): ?>
            <tr><td colspan="7" style="color:#9ca3af">Nenhum registro.</td></tr>
          <?php else: foreach ($rows as $r): ?>
            <tr>
              <td><?= (int)$r['familia_id'] ?></td>
              <td><?= e($r['nome']) ?></td>
              <td><?= e($r['email']) ?></td>
              <td><?= (int)$r['num_membros'] ?></td>
              <td><?= e($r['situacao']) ?></td>
              <td><?= $r['renda_mensal']!==null ? number_format((float)$r['renda_mensal'],2,',','.') : '-' ?></td>
              <td>
                <a class="btn secondary" href="?action=edit&id=<?= (int)$r['familia_id'] ?>">Editar</a>
                <form method="post" action="?action=destroy" style="display:inline" onsubmit="return confirm('Excluir esta família?');">
                  <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                  <input type="hidden" name="familia_id" value="<?= (int)$r['familia_id'] ?>">
                  <input type="hidden" name="usuario_id" value="<?= (int)$r['usuario_id'] ?>">
                  <button class="btn danger" type="submit">Excluir</button>
                </form>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>

    <?php elseif ($action==='create'): ?>
      <h2>Nova família</h2>
      <form method="post" action="?action=store" class="grid">
        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
        <div><label>Nome</label><input type="text" name="nome" required></div>
        <div><label>E-mail</label><input type="email" name="email" required></div>
        <div><label>Telefone</label><input type="text" name="telefone"></div>
        <div><label>Nº Membros</label><input type="number" min="1" name="num_membros" required></div>
        <div><label>Situação</label>
          <select name="situacao" required>
            <option value="">— Selecione —</option>
            <option value="baixa_renda">Baixa renda</option>
            <option value="vulnerabilidade">Vulnerabilidade</option>
            <option value="outra">Outra</option>
          </select>
        </div>
        <div><label>Renda Mensal (opcional)</label><input type="number" step="0.01" min="0" name="renda_mensal"></div>
        <div style="grid-column:1/-1"><label>Endereço</label><textarea name="endereco" rows="2"></textarea></div>
        <div style="grid-column:1/-1;display:flex;gap:8px">
          <button class="btn" type="submit">Salvar</button>
          <a class="btn secondary" href="?action=list">Cancelar</a>
        </div>
      </form>

    <?php elseif ($action==='edit'): ?>
      <?php
        $id = (int)($_GET['id'] ?? 0);
        $st = $pdo->prepare("SELECT f.id AS familia_id, u.id AS usuario_id, u.nome, u.email, u.telefone, u.endereco,
                                    f.num_membros, f.renda_mensal, f.situacao
                             FROM Familia f JOIN Usuario u ON u.id=f.usuario_id
                             WHERE f.id=? AND u.tipo='familia'");
        $st->execute([$id]); $row=$st->fetch();
        if (!$row) { echo '<div class="flash error">Família não encontrada.</div>'; }
      ?>
      <?php if ($row): ?>
      <h2>Editar família #<?= (int)$row['familia_id'] ?></h2>
      <form class="grid" method="post" action="?action=update" autocomplete="off" novalidate>
        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
        <input type="hidden" name="familia_id" value="<?= (int)$row['familia_id'] ?>">
        <input type="hidden" name="usuario_id" value="<?= (int)$row['usuario_id'] ?>">
        <div><label>Nome</label><input type="text" name="nome" required value="<?= e($row['nome']) ?>"></div>
        <div><label>E-mail</label><input type="email" name="email" required value="<?= e($row['email']) ?>"></div>
        <div><label>Telefone</label><input type="text" name="telefone" value="<?= e($row['telefone'] ?? '') ?>"></div>
        <div><label>Nº Membros</label><input type="number" min="1" name="num_membros" required value="<?= (int)$row['num_membros'] ?>"></div>
        <div><label>Situação</label>
          <select name="situacao" required>
            <option value="baixa_renda" <?= $row['situacao']==='baixa_renda'?'selected':''; ?>>Baixa renda</option>
            <option value="vulnerabilidade" <?= $row['situacao']==='vulnerabilidade'?'selected':''; ?>>Vulnerabilidade</option>
            <option value="outra" <?= $row['situacao']==='outra'?'selected':''; ?>>Outra</option>
          </select>
        </div>
        <div><label>Renda Mensal</label>
          <input type="number" step="0.01" min="0" name="renda_mensal" value="<?= $row['renda_mensal']!==null ? e((string)$row['renda_mensal']) : '' ?>">
        </div>
        <div style="grid-column:1/-1"><label>Endereço</label><textarea name="endereco" rows="2"><?= e($row['endereco'] ?? '') ?></textarea></div>
        <div style="grid-column:1/-1;display:flex;gap:8px">
          <button class="btn" type="submit">Atualizar</button>
          <a class="btn secondary" href="?action=list">Cancelar</a>
        </div>
      </form>
      <?php endif; ?>

    <?php else: ?>
      <div class="flash error">Ação inválida.</div>
      <a class="btn secondary" href="?action=list">Voltar</a>
    <?php endif; ?>
  </div>
</div>
</body>
</html>