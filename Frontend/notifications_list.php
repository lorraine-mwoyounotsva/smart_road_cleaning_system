<?php foreach ($notifications as $notification): ?>
    <div class="notification-item <?= $notification['is_read'] ? '' : 'unread' ?>">
        <div class="notification-title"><?= htmlspecialchars($notification['title']) ?></div>
        <div><?= htmlspecialchars($notification['message']) ?></div>
        <div class="notification-time">
            <?= date('M d, H:i', strtotime($notification['created_at'])) ?>
        </div>
    </div>
<?php endforeach; ?>