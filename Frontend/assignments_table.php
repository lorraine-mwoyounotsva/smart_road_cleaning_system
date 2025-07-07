<table>
    <thead>
        <tr>
            <th>Cleaner</th>
            <th>Route</th>
            <th>Status</th>
            <th>Assigned At</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($assignments as $assignment): ?>
            <tr>
                <td><?= htmlspecialchars($assignment['cleaner_name']) ?></td>
                <td><?= htmlspecialchars($assignment['route_name']) ?></td>
                <td>
                    <span class="status-badge status-<?= str_replace('-', '_', $assignment['status']) ?>">
                        <?= ucfirst($assignment['status']) ?>
                    </span>
                </td>
                <td><?= date('M d, Y H:i', strtotime($assignment['assigned_at'])) ?></td>
                <td>
                    <button class="btn" onclick="openStatusModal(<?= $assignment['id'] ?>, '<?= $assignment['status'] ?>')">
                        <i class="fas fa-edit"></i> Update
                    </button>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>