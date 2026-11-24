<?php
require_once __DIR__ . '/../includes/init.php';
require_role('admin');

if (is_post()) {
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($action === 'read')   q('UPDATE contact_messages SET is_read = 1 - is_read WHERE id = ?', [$id]);
    if ($action === 'delete') { q('DELETE FROM contact_messages WHERE id = ?', [$id]); flash('success', 'Message deleted.'); }
    redirect('admin/messages.php');
}

$messages = q_all('SELECT * FROM contact_messages ORDER BY is_read, created_at DESC');
$pageTitle = 'Contact Messages';
$activeNav = 'messages';
require __DIR__ . '/../includes/dash_header.php';
?>
<?php if (!$messages): ?>
    <div class="empty-state"><div class="icon">✉️</div><h3>No messages yet</h3></div>
<?php endif; ?>
<?php foreach ($messages as $m): ?>
    <div class="card" style="<?= $m['is_read'] ? 'opacity:.75' : 'border-left:4px solid var(--brand)' ?>">
        <div class="card-title">
            <div>
                <h3><?= e($m['subject']) ?> <?= $m['is_read'] ? '' : badge('New', 'info') ?></h3>
                <div class="muted small"><?= e($m['name']) ?> · <a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a> · <?= date('d M Y, g:i A', strtotime($m['created_at'])) ?></div>
            </div>
            <div class="actions">
                <a class="btn btn-outline btn-sm" href="mailto:<?= e($m['email']) ?>?subject=<?= rawurlencode('Re: ' . $m['subject']) ?>">Reply</a>
                <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $m['id'] ?>"><input type="hidden" name="action" value="read">
                    <button class="btn btn-outline btn-sm">Mark <?= $m['is_read'] ? 'unread' : 'read' ?></button></form>
                <form method="post" data-confirm="Delete this message?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $m['id'] ?>"><input type="hidden" name="action" value="delete">
                    <button class="btn btn-danger btn-sm">Delete</button></form>
            </div>
        </div>
        <p class="mb-0"><?= nl2br(e($m['message'])) ?></p>
    </div>
<?php endforeach; ?>
<?php require __DIR__ . '/../includes/dash_footer.php'; ?>
