<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CDN — File Exists</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark px-4">
    <span class="navbar-brand fw-bold">Foxxything CDN</span>
    <span class="text-white"><?= htmlspecialchars($user['username']) ?></span>
</nav>

<div class="container py-5" style="max-width: 480px">
    <div class="card shadow-sm">
        <div class="card-body text-center p-5">
            <div class="mb-3 fs-1">⚠️</div>
            <h5 class="card-title">
                <?= count($filenames) === 1 ? 'File already exists' : count($filenames) . ' files already exist' ?>
            </h5>
            <p class="text-muted">The following file<?= count($filenames) > 1 ? 's' : '' ?> already exist in your uploads:</p>
            <ul class="list-group mb-4 text-start">
                <?php foreach ($filenames as $name): ?>
                    <li class="list-group-item"><?= htmlspecialchars($name) ?></li>
                <?php endforeach; ?>
            </ul>
            <p class="text-muted">Do you want to overwrite <?= count($filenames) === 1 ? 'it' : 'them' ?>?</p>
            <form action="/upload" method="POST" class="d-flex gap-2 justify-content-center mt-4">
                <input type="hidden" name="confirm_overwrite" value="yes">
                <button type="submit" class="btn btn-danger">Overwrite</button>
            </form>
            <form action="/upload" method="POST" class="d-flex gap-2 justify-content-center mt-2">
                <input type="hidden" name="confirm_overwrite" value="no">
                <button type="submit" class="btn btn-outline-secondary">Cancel</button>
            </form>
        </div>
    </div>
</div>

</body>
</html>