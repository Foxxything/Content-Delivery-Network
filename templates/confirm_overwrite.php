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
    <span class="navbar-brand fw-bold">foxxything CDN</span>
    <span class="text-white"><?= htmlspecialchars($user['username']) ?></span>
</nav>

<div class="container py-5" style="max-width: 480px">
    <div class="card shadow-sm">
        <div class="card-body text-center p-5">
            <div class="mb-3 fs-1">⚠️</div>
            <h5 class="card-title">File already exists</h5>
            <p class="text-muted">
                <strong><?= htmlspecialchars($filename) ?></strong> already exists in your uploads.
                Do you want to overwrite it?
            </p>
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