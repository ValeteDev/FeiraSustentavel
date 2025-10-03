<?php
// doacoes.php — CRUD de Doações (com 1 Alimento mínimo)
declare(strict_types=1);
session_start();
require_once __DIR__ . '/conexao.php';

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function csrf(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function check_csrf(): void { if (($_POST['csrf']??'')!==($_SESSION['csrf']??'')) { http_response_code(403); exit('CSRF inválido.'); } }
function flash(string $m, string $t='success'): void { $_SESSION['flash']=['m'=>$m,'t'=>$t]; }
function get_flash(): ?array { $f=$_SESSION['flash']??null; unset($_SESSION['flash']); return $f; }

$action = $_GET['action'] ?? 'list';

// Store
if ($action==='store' && $_SERVER['REQUEST_METHOD']==='POST') {
  check_csrf();
  $doador_nome = trim($_POST['doador_nome'] ?? '');
  $doador_email = trim($_POST['doador_email'] ?? '');
  $data = $_POST['data'] ?? '';
  $observacoes = trim($_POST['observacoes'] ?? '');
  $alimento_nome = trim($_POST['alimento_nome'] ?? '');
  $quantidade = (int)($_POST['quantidade'] ?? 0);
  $validade = $_POST['validade'] ?? null;
  $categoria = trim($_POST['categoria'] ?? '');

  $erros=[];
  if ($doador_nome==='') $erros[]='Nome do doador é obrigatório.';
  if (!filter_var($doador_email, FILTER_VALIDATE_EMAIL)) $erros[]='E-mail do doador inválido.';
  if (!$data) $erros[]='Data da doação é obrigatória.';
  if ($alimento_nome==='') $erros[]='Nome do alimento é obrigatório.';
  if ($quantidade<=0) $erros[]='Quantidade inválida.';
  if (!in_array($categoria, ['fruta','verdura','legume','outro'], true)) $erros[]='Categoria inválida.';

  if ($erros) { flash(implode(' ', $erros), 'error'); header('Location: ?action=create'); exit; }

  try {
    $pdo->beginTransaction();

    // Encontra ou cria o doador
    $st = $pdo->prepare('SELECT id FROM Usuario WHERE email=? AND tipo="doador"');
    $st->execute([$doador_email]);
    $doador_id = (int)($st->fetchColumn() ?: 0);

    if ($doador_id===0) {
      $insU = $pdo->prepare('INSERT INTO Usuario (nome, email, tipo) VALUES (?, ?, "doador")');
      $insU->execute([$doador_nome, $doador_email]);
      $doador_id = (int)$pdo->lastInsertId();
    }

    // Cria Doação
    $insD = $pdo->prepare('INSERT INTO Doacao (doador_id, data, status, observacoes) VALUES (?, ?, "disponivel", ?)');
    $insD->execute([$doador_id, $data, $observacoes]);
    $doacao_id = (int)$pdo->lastInsertId();

    // Cria 1 Alimento (mínimo)
    $insA = $pdo->prepare('INSERT INTO Alimento (doacao_id, nome, quantidade, validade, categoria) VALUES (?, ?, ?, ?, ?)');
    $insA->execute([$doacao_id, $alimento_nome, $quantidade, $validade ?: null, $categoria]);

    $pdo->commit();
    flash('Doação registrada com sucesso!');
    header('Location: ?action=list'); exit;
  } catch (Throwable $e) {
    $pdo->rollBack();
    if ($e instanceof PDOException && $e->getCode()==='23000') {
      flash('Conflito de integridade (e-mail de doador duplicado?).', 'error');
    } else {
      flash('Erro ao registrar doação: '.$e->getMessage(), 'error');
    }
    header('Location: ?action=create'); exit;
  }
}

// Update (status/observações)
if ($action==='update' && $_SERVER['REQUEST_METHOD']==='POST') {
  check_csrf();
  $id = (int)($_POST['id'] ?? 0);
  $status = trim($_POST['status'] ?? 'disponivel');
  $observacoes = trim($_POST['observacoes'] ?? '');

  if ($id<=0) { flash('ID inválido.','error'); header('Location:?action=list'); exit; }
  if (!in_array($status, ['disponivel','reservado','entregue'], true)) { flash('Status inválido.','error'); header('Location:?action=edit&id='.$id); exit; }

  try {
    $st = $pdo->prepare('UPDATE Doacao SET status=?, observacoes=? WHERE id=?');
    $st->execute([$status, $observacoes, $id]);
    flash('Doação atualizada!');
    header('Location:?action=list'); exit;
  } catch (Throwable $e) {
    flash('Erro ao atualizar: '.$e->getMessage(), 'error');
    header('Location:?action=edit&id='.$id); exit;
  }
}

// Destroy
if ($action==='destroy' && $_SERVER['REQUEST_METHOD']==='POST') {
  check_csrf();
  $id = (int)($_POST['id'] ?? 0);
  if ($id<=0) { flash('ID inválido.','error'); header('Location:?action=list'); exit; }
  try {
    $pdo->prepare('DELETE FROM Doacao WHERE id=?')->execute([$id]);
    // Alimentos são removidos em cascata (ON DELETE CASCADE)
    flash('Doação excluída com sucesso!');
  } catch (Throwable $e) {
    flash('Erro ao excluir: '.$e->getMessage(), 'error');
  }
  header('Location:?action=list'); exit;
}

// View
$flash = get_flash();
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <title>Doações — Feira Sustentável</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    :root { --bg:#0f172a; --card:#111827; --text:#e5e7eb; --mut:#9ca3af; --bd:#1f2937; --pri:#22c55e; --sec:#3b82f6; --danger:#ef4444; --warn:#f59e0b; }
    *{box-sizing:border-box} body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Arial;background:var(--bg);color:var(--text)}
    .container{max-width:1000px;margin:28px auto;padding:0 16px}
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
    .badge{padding:2px 8px;border-radius:999px;font-size:12px;font-weight:700;display:inline-block}
    .badge.disponivel{background:rgba(34,197,94,.18);border:1px solid #14532d;color:#86efac}
    .badge.reservado{background:rgba(245,158,11,.18);border:1px solid #9a3412;color:#fcd34d}
    .badge.entregue{background:rgba(59,130,246,.18);border:1px solid #1d4ed8;color:#bfdbfe}
  </style>
</head>
<body>
<div class="container">
  <div class="card">
    <div class="toolbar">
      <h1>Doações</h1>
      <?php if (!in_array($action,['create','edit'])): ?>
        <a class="btn" href="?action=create">+ Nova doação</a>
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
        $params=[]; $where='';
        if ($q!=='') { $where='WHERE (u.nome LIKE :q OR u.email LIKE :q OR d.observacoes LIKE :q OR a.nome LIKE :q)'; $params[':q']="%$q%"; }
        $sql = "SELECT d.id, d.data, d.status, d.observacoes, u.nome AS doador_nome, u.email AS doador_email,
                       (SELECT COUNT(*) FROM Alimento xa WHERE xa.doacao_id=d.id) AS total_itens,
                       (SELECT nome FROM Alimento ya WHERE ya.doacao_id=d.id ORDER BY ya.id DESC LIMIT 1) AS item_recente
                FROM Doacao d
                JOIN Usuario u ON u.id=d.doador_id
                LEFT JOIN Alimento a ON a.doacao_id=d.id
                $where
                GROUP BY d.id, d.data, d.status, d.observacoes, u.nome, u.email
                ORDER BY d.id DESC";
        $st = $pdo->prepare($sql); $st->execute($params); $rows=$st->fetchAll();
      ?>
      <form method="get" style="margin-bottom:12px;display:flex;gap:8px">
        <input type="hidden" name="action" value="list">
        <input type="text" name="q" placeholder="Buscar (doador, observações, alimento)..." value="<?= e($q) ?>">
        <button class="btn" type="submit">Buscar</button>
      </form>
      <table>
        <thead><tr><th>ID</th><th>Doador</th><th>Data</th><th>Status</th><th>Itens</th><th>Último item</th><th>Ações</th></tr></thead>
        <tbody>
          <?php if (!$rows): ?>
            <tr><td colspan="7" style="color:#9ca3af">Nenhuma doação.</td></tr>
          <?php else: foreach ($rows as $r): ?>
            <tr>
              <td><?= (int)$r['id'] ?></td>
              <td><?= e($r['doador_nome']).' <br><small>'.e($r['doador_email']).'</small>' ?></td>
              <td><?= e($r['data']) ?></td>
              <td><span class="badge <?= e($r['status']) ?>"><?= e(ucfirst($r['status'])) ?></span></td>
              <td><?= (int)$r['total_itens'] ?></td>
              <td><?= e($r['item_recente'] ?? '-') ?></td>
              <td>
                <a class="btn secondary" href="?action=edit&id=<?= (int)$r['id'] ?>">Editar</a>
                <form method="post" action="?action=destroy" style="display:inline" onsubmit="return confirm('Excluir esta doação?');">
                  <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <button class="btn danger" type="submit">Excluir</button>
                </form>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>

    <?php elseif ($action==='create'): ?>
      <h2>Nova doação</h2>
      <form method="post" action="?action=store" class="grid">
        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
        <div><label>Nome do Doador</label><input type="text" name="doador_nome" required></div>
        <div><label>E-mail do Doador</label><input type="email" name="doador_email" required></div>
        <div><label>Data</label><input type="date" name="data" required value="<?= e(date('Y-m-d')) ?>"></div>
        <div><label>Observações (local de retirada)</label><input type="text" name="observacoes"></div>
        <div><label>Alimento</label><input type="text" name="alimento_nome" required></div>
        <div><label>Quantidade</label><input type="number" name="quantidade" required min="1"></div>
        <div><label>Validade</label><input type="date" name="validade"></div>
        <div><label>Categoria</label>
          <select name="categoria" required>
            <option value="">— Selecione —</option>
            <option value="fruta">Fruta</option>
            <option value="verdura">Verdura</option>
            <option value="legume">Legume</option>
            <option value="outro">Outro</option>
          </select>
        </div>
        <div style="grid-column:1/-1;display:flex;gap:8px">
          <button class="btn" type="submit">Salvar</button>
          <a class="btn secondary" href="?action=list">Cancelar</a>
        </div>
      </form>

    <?php elseif ($action==='edit'):
      $id = (int)($_GET['id'] ?? 0);
      $st = $pdo->prepare("SELECT d.*, u.nome AS doador_nome, u.email AS doador_email
                           FROM Doacao d JOIN Usuario u ON u.id=d.doador_id WHERE d.id=?");
      $st->execute([$id]); $row = $st->fetch();
      if (!$row) { echo '<div class="flash error">Doação não encontrada.</div>'; }
      ?>
      <?php if ($row): ?>
      <h2>Editar doação #<?= (int)$row['id'] ?></h2>
      <form method="post" action="?action=update" class="grid">
        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
        <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
        <div><label>Doador</label><input type="text" value="<?= e($row['doador_nome'].' — '.$row['doador_email']) ?>" disabled></div>
        <div><label>Data</label><input type="date" value="<?= e($row['data']) ?>" disabled></div>
        <div><label>Status</label>
          <select name="status" required>
            <option value="disponivel" <?= $row['status']==='disponivel'?'selected':''; ?>>Disponível</option>
            <option value="reservado"  <?= $row['status']==='reservado'?'selected':''; ?>>Reservado</option>
            <option value="entregue"   <?= $row['status']==='entregue'?'selected':''; ?>>Entregue</option>
          </select>
        </div>
        <div style="grid-column:1/-1"><label>Observações</label><input type="text" name="observacoes" value="<?= e($row['observacoes'] ?? '') ?>"></div>
        <div style="grid-column:1/-1;display:flex;gap:8px">
          <button class="btn" type="submit">Atualizar</button>
          <a class="btn secondary" href="?action=list">Cancelar</a>
        </div>
      </form>

      <h3 style="margin-top:20px">Alimentos desta doação</h3>
      <?php
        $sa = $pdo->prepare("SELECT * FROM Alimento WHERE doacao_id=? ORDER BY id DESC");
        $sa->execute([$id]); $itens=$sa->fetchAll();
      ?>
      <table style="margin-top:8px">
        <thead><tr><th>ID</th><th>Nome</th><th>Qtd</th><th>Validade</th><th>Categoria</th></tr></thead>
        <tbody>
          <?php if (!$itens): ?>
            <tr><td colspan="5" style="color:#9ca3af">Sem itens.</td></tr>
          <?php else: foreach ($itens as $it): ?>
            <tr>
              <td><?= (int)$it['id'] ?></td>
              <td><?= e($it['nome']) ?></td>
              <td><?= (int)$it['quantidade'] ?></td>
              <td><?= e($it['validade'] ?? '-') ?></td>
              <td><?= e($it['categoria']) ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
      <?php endif; ?>

    <?php else: ?>
      <div class="flash error">Ação inválida.</div>
      <a class="btn secondary" href="?action=list">Voltar</a>
    <?php endif; ?>
  </div>
</div>
</body>
</html>