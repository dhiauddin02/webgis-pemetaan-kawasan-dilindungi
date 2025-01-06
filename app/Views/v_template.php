<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Pemetaan Jenis Tanah Provinsi Aceh</title>
    <link href="<?= base_url('sb-admin') ?>/css/styles.css" rel="stylesheet" />

    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="<?= base_url('sb-admin') ?>/js/scripts.js"></script>
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
</head>

<body>
    
    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
    
        <a class="navbar-brand ps-3" href="index.html">Pemetaan Kawasan Dilindungi Pulau Sumatra</a>
        <!-- Sidebar Toggle-->
       

    </nav>

    <div id="layoutSidenav_content">
        <main>
            <div class="container-fluid px-1">
                <hr>
                <?php if ($page) {
                    echo view($page);
                } ?>
            </div>
        </main>

    </div>

</body>

</html>