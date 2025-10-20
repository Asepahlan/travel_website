<?php
$page_title = 'Hubungi Kami - TravelKita';
$current_page = 'kontak';
require_once __DIR__ . '/templates/partials/header.php';
?>

<main class="bg-white">
    <!-- Hero Section -->
    <div class="relative bg-gradient-to-r from-[#00BFFF] to-[#0099cc]">
        <div class="absolute inset-0">
            <div class="absolute inset-0 bg-gray-900 opacity-30"></div>
        </div>
        <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <h1 class="text-4xl font-bold tracking-tight text-white sm:text-5xl lg:text-6xl">Hubungi Kami</h1>
            <p class="mt-4 text-xl text-white max-w-3xl">Tim dukungan kami siap membantu Anda 24/7.</p>
        </div>
    </div>

    <!-- Contact Information -->
    <div class="py-12 bg-white sm:py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                    Kami Siap Membantu
                </h2>
                <p class="mx-auto mt-4 max-w-2xl text-xl text-gray-600">
                    Punya pertanyaan atau butuh bantuan? Tim dukungan kami siap membantu Anda.
                </p>
            </div>

            <div class="mt-12 grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                <!-- Contact Cards -->
                <div class="overflow-hidden rounded-lg bg-white shadow-md transition-transform duration-300 hover:shadow-lg hover:-translate-y-1">
                    <div class="p-6">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 rounded-lg bg-blue-50 p-3">
                                <svg class="h-6 w-6 text-[#00BFFF]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-gray-900">Telepon</h3>
                                <p class="mt-2 text-gray-600">(021) 1234-5678</p>
                                <p class="mt-1 text-sm text-gray-500">Senin - Jumat, 08:00 - 17:00 WIB</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg bg-white shadow-md transition-transform duration-300 hover:shadow-lg hover:-translate-y-1">
                    <div class="p-6">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 rounded-lg bg-blue-50 p-3">
                                <svg class="h-6 w-6 text-[#00BFFF]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-gray-900">Email</h3>
                                <p class="mt-2 text-blue-600 hover:text-blue-500">
                                    <a href="mailto:info@travelkita.com">info@travelkita.com</a>
                                </p>
                                <p class="mt-1 text-sm text-gray-500">Respon dalam 1x24 jam</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg bg-white shadow-md transition-transform duration-300 hover:shadow-lg hover:-translate-y-1">
                    <div class="p-6">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 rounded-lg bg-blue-50 p-3">
                                <svg class="h-6 w-6 text-[#00BFFF]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-gray-900">Alamat</h3>
                                <p class="mt-2 text-gray-600">Jl. Contoh No. 123</p>
                                <p class="text-sm text-gray-500">Jakarta Selatan, 12345</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Form -->
    <div class="bg-blue-50 py-12 sm:py-16">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-lg bg-white shadow-lg">
                <div class="px-6 py-8 sm:p-10">
                    <div class="mb-8">
                        <h3 class="text-2xl font-bold text-gray-900">Kirim Pesan</h3>
                        <p class="mt-2 text-gray-600">
                            Isi formulir di bawah ini dan kami akan menghubungi Anda segera.
                        </p>
                    </div>
                    
                    <form class="space-y-6" action="#" method="POST">
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label for="first-name" class="block text-sm font-medium text-gray-700">Nama Depan <span class="text-red-500">*</span></label>
                                <div class="mt-1">
                                    <input type="text" name="first-name" id="first-name" autocomplete="given-name" required
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-[#00BFFF] focus:ring-[#00BFFF] sm:text-sm py-3 px-4 border">
                                </div>
                            </div>

                            <div>
                                <label for="last-name" class="block text-sm font-medium text-gray-700">Nama Belakang</label>
                                <div class="mt-1">
                                    <input type="text" name="last-name" id="last-name" autocomplete="family-name"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-[#00BFFF] focus:ring-[#00BFFF] sm:text-sm py-3 px-4 border">
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">Email <span class="text-red-500">*</span></label>
                                <div class="mt-1">
                                    <input id="email" name="email" type="email" autocomplete="email" required
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-[#00BFFF] focus:ring-[#00BFFF] sm:text-sm py-3 px-4 border">
                                </div>
                            </div>

                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700">Nomor Telepon <span class="text-red-500">*</span></label>
                                <div class="mt-1">
                                    <div class="relative rounded-md shadow-sm">
                                        <div class="absolute inset-y-0 left-0 flex items-center">
                                            <label for="country" class="sr-only">Negara</label>
                                            <select id="country" name="country" autocomplete="country" class="h-full rounded-l-md border-transparent bg-transparent py-0 pl-3 pr-7 text-gray-500 focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                                <option>+62</option>
                                                <option>+1</option>
                                            </select>
                                        </div>
                                        <input type="tel" name="phone" id="phone" autocomplete="tel" required
                                            class="block w-full rounded-md border-gray-300 pl-16 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-3 px-4 border">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label for="subject" class="block text-sm font-medium text-gray-700">Subjek <span class="text-red-500">*</span></label>
                            <div class="mt-1">
                                <select id="subject" name="subject" required
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-[#00BFFF] focus:ring-[#00BFFF] sm:text-sm py-3 px-4 border">
                                    <option value="" disabled selected>Pilih subjek</option>
                                    <option>Pertanyaan Umum</option>
                                    <option>Bantuan Pemesanan</option>
                                    <option>Keluhan</option>
                                    <option>Kerjasama</option>
                                    <option>Lainnya</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label for="message" class="block text-sm font-medium text-gray-700">Pesan <span class="text-red-500">*</span></label>
                            <div class="mt-1">
                                <textarea id="message" name="message" rows="4" required
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-[#00BFFF] focus:ring-[#00BFFF] sm:text-sm py-3 px-4 border"
                                    placeholder="Tulis pesan Anda di sini..."></textarea>
                            </div>
                        </div>

                        <div class="flex items-start">
                            <div class="flex h-5 items-center">
                                <input id="privacy-policy" name="privacy-policy" type="checkbox" required
                                    class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="privacy-policy" class="font-medium text-gray-700">Saya menyetujui</label>
                                <p class="text-gray-500">Dengan mengirimkan formulir ini, Anda menyetujui Kebijakan Privasi dan Ketentuan Layanan kami.</p>
                            </div>
                        </div>

                        <div class="pt-2">
                            <button type="submit"
                                class="inline-flex justify-center w-full rounded-md border border-transparent shadow-sm px-6 py-3 bg-[#00BFFF] text-base font-medium text-white hover:bg-[#0099cc] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#00BFFF] sm:ml-3 sm:w-auto sm:text-sm transition-colors duration-200">
                                Kirim Pesan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Map -->
    <div class="bg-white py-12 sm:py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-xl shadow-xl">
                <div class="aspect-w-16 aspect-h-9 h-0 pb-[56.25%] relative">
                    <iframe 
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3966.521260322283!2d106.8191595145001!3d-6.194741395493371!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e69f5390917b759%3A0x9d3f6d2d2a1b0e0b!2sMonumen%20Nasional!5e0!3m2!1sen!2sid!4v1620000000000!5m2!1sen!2sid"
                        class="absolute inset-0 h-full w-full"
                        style="border:0;"
                        allowfullscreen=""
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
            </div>
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-500">
                    Kunjungi kantor kami di Jl. Contoh No. 123, Jakarta Selatan, 12345
                </p>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/templates/partials/footer.php'; ?>

