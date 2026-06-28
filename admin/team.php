<?php
// admin/team.php – Manage Team Members
require_once dirname(__DIR__) . '/includes/functions.php';
requireAdmin();

$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_member'])) {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) { setFlash('error','CSRF error.'); }
    else {
        $mid   = (int)($_POST['mid'] ?? 0);
        $name  = sanitizeInput($_POST['name']  ?? '');
        $role  = sanitizeInput($_POST['role']  ?? '');
        $bio   = sanitizeInput($_POST['bio']   ?? '');
        $email = filter_var(sanitizeInput($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL) ?: null;
        $sort  = (int)($_POST['sort_order'] ?? 0);
        $act   = isset($_POST['is_active']) ? 1 : 0;
        $img   = handleImageUpload('image', 'team');

        if ($mid) {
            $sql = "UPDATE team_members SET name=?,role=?,bio=?,email=?,sort_order=?,is_active=?";
            $p   = [$name,$role,$bio,$email,$sort,$act];
            if ($img) { $sql .= ",image=?"; $p[] = $img; }
            $pdo->prepare($sql . " WHERE id=?")->execute(array_merge($p,[$mid]));
        } else {
            $pdo->prepare("INSERT INTO team_members (name,role,bio,email,image,sort_order,is_active) VALUES (?,?,?,?,?,?,?)")
                ->execute([$name,$role,$bio,$email,$img,$sort,$act]);
        }
        setFlash('success','Saved.');
    }
    header('Location: /admin/team.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (verifyCsrf($_POST['csrf_token'] ?? '')) {
        $pdo->prepare("DELETE FROM team_members WHERE id=?")->execute([(int)$_POST['delete_id']]);
        setFlash('success','Deleted.');
    }
    header('Location: /admin/team.php'); exit;
}

$editId  = (int)($_GET['edit'] ?? 0);
$editing = null;
if ($editId) { $s=$pdo->prepare("SELECT * FROM team_members WHERE id=?");$s->execute([$editId]);$editing=$s->fetch(); }

$members = $pdo->query("SELECT * FROM team_members ORDER BY sort_order ASC,id ASC")->fetchAll();

$pageTitle = 'Team Members';
include __DIR__ . '/includes/header.php';
?>
<div style="display:grid;grid-template-columns:1fr 1.3fr;gap:1.5rem;">
  <div class="card">
    <div class="card-header"><h3><?= $editing?'Edit Member':'Add Team Member' ?></h3></div>
    <div class="card-body">
      <form method="POST" enctype="multipart/form-data">
        <?= csrfField() ?>
        <input type="hidden" name="save_member" value="1">
        <input type="hidden" name="mid" value="<?= $editing['id']??0 ?>">
        <div class="form-group"><label>Name</label><input type="text" name="name" class="form-control" value="<?= e($editing['name']??'') ?>" required></div>
        <div class="form-group"><label>Role / Title</label><input type="text" name="role" class="form-control" value="<?= e($editing['role']??'') ?>"></div>
        <div class="form-group"><label>Bio</label><textarea name="bio" class="form-control" rows="4"><?= e($editing['bio']??'') ?></textarea></div>
        <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" value="<?= e($editing['email']??'') ?>"></div>
        <div class="form-group"><label>Sort Order</label><input type="number" name="sort_order" class="form-control" value="<?= $editing['sort_order']??0 ?>"></div>
        <div class="form-group">
          <?php if (!empty($editing['image'])): ?><div style="margin-bottom:.5rem;"><img src="/<?= e($editing['image']) ?>" style="height:70px;border-radius:8px;object-fit:cover;"></div><?php endif; ?>
          <label>Photo</label><input type="file" name="image" class="form-control" accept="image/*">
        </div>
        <label style="display:flex;gap:.5rem;cursor:pointer;margin-bottom:1rem;">
          <input type="checkbox" name="is_active" value="1" <?= ($editing['is_active']??1)?'checked':'' ?>> Active
        </label>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
        <?php if ($editing): ?><a href="/admin/team.php" class="btn btn-light" style="margin-left:.5rem;">Cancel</a><?php endif; ?>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h3><i class="fas fa-users" style="color:var(--gold)"></i> Team (<?= count($members) ?>)</h3></div>
    <div style="overflow-x:auto;">
      <table>
        <thead><tr><th>Photo</th><th>Name</th><th>Role</th><th>Active</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ($members as $m): ?>
          <tr>
            <td><?php if($m['image']):?><img src="/<?= e($m['image']) ?>" class="img-preview"><?php endif;?></td>
            <td><?= e($m['name']) ?></td>
            <td><?= e($m['role']) ?></td>
            <td><?= $m['is_active']?'<span class="badge badge-green">Yes</span>':'<span class="badge badge-gray">No</span>' ?></td>
            <td>
              <a href="?edit=<?= $m['id'] ?>" class="btn btn-light btn-sm"><i class="fas fa-edit"></i></a>
              <form method="POST" style="display:inline" onsubmit="return confirm('Delete?')">
                <?= csrfField() ?><input type="hidden" name="delete_id" value="<?= $m['id'] ?>">
                <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
