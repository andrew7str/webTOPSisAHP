<!-- Dalam loop tampilan users -->
<td>
    <?= $user['role'] ?>
    <?php if ($user['id'] != $_SESSION['user_id']): ?>
        <div class="btn-group btn-group-sm">
            <a href="change_role.php?id=<?= $user['id'] ?>&role=admin" 
               class="btn btn-sm <?= $user['role'] == 'admin' ? 'btn-success' : 'btn-outline-success' ?>">Admin</a>
            <a href="change_role.php?id=<?= $user['id'] ?>&role=customer" 
               class="btn btn-sm <?= $user['role'] == 'customer' ? 'btn-success' : 'btn-outline-success' ?>">Customer</a>
        </div>
    <?php endif; ?>
</td>