<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CDN — Upload</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .thumb-card img { width: 100%; height: 160px; object-fit: cover; }
        .thumb-card .cdn-link { font-size: .75rem; word-break: break-all; }
        #drop-zone {
            border: 2px dashed #6c757d;
            border-radius: .5rem;
            transition: background .2s;
            cursor: pointer;
        }
        #drop-zone.dragover { background: #e9ecef; border-color: #0d6efd; }
        .delete-btn {
            z-index: 10; width: 28px; height: 28px;
            padding: 0; line-height: 1;
            position: absolute; top: 4px; right: 4px;
        }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark px-4">
    <span class="navbar-brand fw-bold">Foxxything CDN</span>
    <div class="d-flex align-items-center gap-3">
        <span class="text-white"><?= htmlspecialchars($user['username']) ?></span>
        <a href="/auth/discord/logout" class="btn btn-sm btn-outline-light">Logout</a>
    </div>
</nav>

<div class="container py-5">
    <div class="card shadow-sm mb-5">
        <div class="card-body">
            <h5 class="card-title mb-3">Upload Images</h5>
            <form id="upload-form" action="/upload" method="POST" enctype="multipart/form-data">
                <div id="drop-zone" class="p-5 text-center mb-3"
                     onclick="document.getElementById('fileInput').click()">
                    <p class="mb-1 text-muted">Drag & drop or click to select</p>
                    <small class="text-muted">JPG, PNG, GIF, WEBP — multiple files supported</small>
                    <input id="fileInput" type="file" name="images[]" accept="image/*" multiple class="d-none"
                           onchange="this.form.submit()">
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($uploads)): ?>
        <p class="text-muted text-center">No images uploaded yet.</p>
    <?php else: ?>
        <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 g-3">
            <?php foreach ($uploads as $file): ?>
                <div class="col">
                    <div class="card thumb-card shadow-sm h-100 position-relative">
                        <form
                                action="/upload/delete/<?= rawurlencode($file['filename']) ?>"
                                method="POST"
                                onsubmit="return confirm('Delete <?= addslashes(htmlspecialchars($file['filename'])) ?>?')"
                        >
                            <button type="submit" class="btn btn-sm btn-danger delete-btn" title="Delete">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/>
                                    <path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/>
                                </svg>
                            </button>
                        </form>
                        <img src="<?= htmlspecialchars($file['url']) ?>" alt="<?= htmlspecialchars($file['filename']) ?>">
                        <div class="card-body p-2">
                            <p class="cdn-link text-muted mb-1"><?= htmlspecialchars($file['cdn']) ?></p>
                            <button
                                    class="btn btn-sm btn-outline-primary w-100"
                                    onclick="navigator.clipboard.writeText('<?= htmlspecialchars($file['cdn']) ?>').then(() => this.textContent = 'Copied!'); setTimeout(() => this.textContent = 'Copy Link', 1500)"
                            >Copy Link</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    const zone = document.getElementById('drop-zone');
    zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('dragover'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
    zone.addEventListener('drop', e => {
        e.preventDefault();
        zone.classList.remove('dragover');
        const input = document.getElementById('fileInput');
        input.files = e.dataTransfer.files;
        input.form.submit();
    });
</script>
</body>
</html>