<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Halaman Tidak Ditemukan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen p-6 font-sans">
    <div class="max-w-md w-full bg-white rounded-3xl p-8 md:p-12 border border-gray-100 shadow-xl text-center space-y-6">
        <div class="w-24 h-24 rounded-3xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-4xl font-black mx-auto">
            404
        </div>
        <div>
            <h1 class="text-2xl font-black text-gray-800">Halaman Tidak Ditemukan</h1>
            <p class="text-xs text-gray-500 mt-2 leading-relaxed">
                Jalur rute atau link yang Anda tuju tampaknya tidak terhubung ke jaringan server NetSim Academy.
            </p>
        </div>
        <div class="pt-2">
            <a href="<?= base_url() ?>" class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-2xl text-xs transition shadow-md">
                <i class="fas fa-home"></i> Kembali ke Beranda
            </a>
        </div>
    </div>
</body>
</html>
