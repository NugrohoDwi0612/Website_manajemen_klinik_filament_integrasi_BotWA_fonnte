<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - Sistem Manajemen Klinik</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="bg-white shadow-lg rounded-2xl p-8 max-w-lg text-center">
        <h1 class="text-3xl font-bold text-gray-800">Selamat Datang di Sistem Manajemen Klinik</h1>
        <p class="text-gray-600 mt-4">Kelola data pasien, jadwal dokter, dan administrasi klinik dengan mudah.</p>
        <a href="{{ route('filament.admin.auth.login') }}"
            class="mt-6 inline-block bg-blue-600 text-white px-6 py-2 rounded-lg shadow-md hover:bg-blue-700 transition">Login</a>
    </div>
</body>


</html>