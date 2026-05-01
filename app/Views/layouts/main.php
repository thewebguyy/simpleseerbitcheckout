<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h(APP_NAME) ?></title>
    <link rel="stylesheet" href="/assets/css/styles.css?v=<?= filemtime(ROOT_PATH . '/public/assets/css/styles.css') ?>">
</head>
<body>
    <div class="container">
        <!-- Flash Messages -->
        <?php if (!empty($_SESSION['flash'])): ?>
            <?php 
                $flash = $_SESSION['flash'];
                unset($_SESSION['flash']); 
            ?>
            <div class="alert alert-<?= h($flash['type']) ?>">
                <?= h($flash['message']) ?>
            </div>
        <?php endif; ?>

        <!-- Content -->
        <?= $content ?>
    </div>
</body>
</html>
