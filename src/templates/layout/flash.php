<?php
/**
 * One-time status messages, set via flash() on the previous request.
 *
 * @var list<array{message: string, type: string}> $flash
 */
foreach ($flash as $item): ?>
<div class="app-flash app-flash-<?= e($item['type']) ?>" role="status"><?= e($item['message']) ?></div>
<?php endforeach; ?>
