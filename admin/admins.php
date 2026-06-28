<?php
// ================================================================
//  admin/admins.php – Manage Admin Users (superadmin only)
// ================================================================
require_once dirname(__DIR__) . '/includes/functions.php';
requireAdmin();

if ((currentAdmin()['role'] ?? '') !== 'superadmin') {
    setFlash('error', 'Access denied. Superadmin only.');
    header('Location: /admin/index.php'); exit;
}

$pdo = getPDO();

// Create / Update admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_admin'])) {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) { setFlash('error','CSRF error.'); }
    else {
        $aid      = (int)($_POST['admin_id'] ?? 0);
        $aname    = sanitizeInput($_POST['aname']  ?? '');
        $aemail   = filter_var(sanitizeInput($_POST['aemail'] ?? ''), FILTER_VALIDATE_EMAIL);
        $arole    = sanitizeInput($_POST['arole']  ?? 'editor');
        $apass    = $_POST['apassword'] ?? '';

        if (!$aname || !$aemail) { setFlash('error','Name and email required.'); }
        else {
            if ($aid) {
                $sql = "UPDATE admin_users SET name=?,email=?,role=?";
                $params = [$aname,$aemail,$arole];
                if ($apass) { $sql .= ",password=?"; $params[] = password_hash($apass, PASSWORD_BCRYPT, ['cost'=>12]); }
                $sql .= " WHERE id=?"; $params[] = $aid;
                $pdo->prepare($sql)->execute($params);
            } else {
                if (!$apass || strlen($apass) < 8) { setFlash('error','Password must be at least 8 characters.'); header('Location:/admin/admins.php');exit; }
                $pdo->prepare("INSERT INTO admin_users (name,email,password,role) VALUES (?,?,?,?)")
                    ->execute([$aname,$aemail,password_hash($apass,PASSWORD_BCRYPT,['cost'=>12]),$arole]);
            }
            setFlash('success','Admin user saved.');
        }
    }
    header('Location: /admin/admins.php'); exit;
}

// Delete (cannot delete yourself)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $did = (int)$_POST['delete_id'];
    if ($did === (int)(currentAdmin()['id'] ?? 0)) {
        setFlash('error','You cannot delete your own account.');
    } elseif (verifyCsrf($_POST['csrf_token'] ?? '')) {
        $pdo->prepare("DELETE FROM admin_users WHERE id=?")->execute([$did]);
        setFlash('success','Admin deleted.');
    }
    header('Location: /admin/admins.php'); exit;
}

$editId  = (int)($_GET['edit'] ?? 0);
$editing = null;
if ($editId) { $s=$pdo->prepare("SELECT * FROM admin_users WHERE id=?");$s->execute([$editId]);$editing=$s->fetch(); }

$admins = $pdo->query("SELECT id,name,email,role,last_login,created_at FROM admin_users ORDER BY id ASC")->fetchAll();

$pageTitle = 'Admin Users';
include __DIR__ . '/includes/header.php';
?>
<div style="display:grid;grid-template-columns:1fr 1.3fr;gap:1.5rem;">
  <div class="card">
    <div class="card-header"><h3><?= $editing ? 'Edit Admin' : 'Add Admin User' ?></h3></div>
    <div class="card-body">
      <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="save_admin" value="1">
        <input type="hidden" name="admin_id"   value="<?= $editing['id'] ?? 0 ?>">
        <div class="form-group"><label>Full Name</label>
          <input type="text" name="aname" class="form-control" value="<?= e($editing['name'] ?? '') ?>" required></div>
        <div class="form-group"><label>Email</label>
          <input type="email" name="aemail" class="form-control" value="<?= e($editing['email'] ?? '') ?>" required></div>
        <div class="form-group"><label>Role</label>
          <select name="arole" class="form-control">
            <option value="editor"     <?= ($editing['role']??'')==='editor'     ?'selected':'' ?>>Editor</option>
            <option value="superadmin" <?= ($editing['role']??'')==='superadmin' ?'selected':'' ?>>Super Admin</option>
          </select>
        </div>
        <div class="form-group"><label>Password <?= $editing?'(leave blank to keep)':'*' ?></label>
          <input type="password" name="apassword" class="form-control" <?= !$editing?'required':'' ?> placeholder="Min 8 characters"></div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
        <?php if ($editing): ?><a href="/admin/admins.php" class="btn btn-light" style="margin-left:.5rem;">Cancel</a><?php endif; ?>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h3><i class="fas fa-user-shield" style="color:var(--gold)"></i> Admin Users</h3></div>
    <div style="overflow-x:auto;">
      <table>
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Last Login</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ($admins as $a): ?>
          <tr>
            <td><?= e($a['name']) ?></td>
            <td><?= e($a['email']) ?></td>
            <td><span class="badge <?= $a['role']==='superadmin'?'badge-gold':'badge-blue' ?>"><?= ucfirst($a['role']) ?></span></td>
            <td><?= $a['last_login'] ? date('M j, Y', strtotime($a['last_login'])) : 'Never' ?></td>
            <td>
              <a href="?edit=<?= $a['id'] ?>" class="btn btn-light btn-sm"><i class="fas fa-edit"></i></a>
              <?php if ($a['id'] !== (int)(currentAdmin()['id'] ?? 0)): ?>
              <form method="POST" style="display:inline" onsubmit="return confirm('Delete this admin?')">
                <?= csrfField() ?><input type="hidden" name="delete_id" value="<?= $a['id'] ?>">
                <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
