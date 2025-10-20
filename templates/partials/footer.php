    </main>

    <!-- Footer -->
    <footer class="bg-gradient-to-r from-[#00BFFF] to-[#0099cc] text-white mt-12">
        <div class="container mx-auto px-4 py-12">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- About Section -->
                <div>
                    <h3 class="text-lg font-semibold mb-4">TravelKita</h3>
                    <p class="text-white text-opacity-90 text-sm">
                        Menyediakan layanan transportasi darat yang nyaman, aman, dan terpercaya untuk perjalanan Anda.
                    </p>
                </div>

                <!-- Quick Links -->
                <div>
                    <h3 class="text-lg font-semibold mb-4">Tautan Cepat</h3>
                    <ul class="space-y-2">
                        <li><a href="<?php echo $base_url; ?>" class="text-white text-opacity-90 hover:text-white hover:opacity-100 text-sm transition-colors">Beranda</a></li>
                        <li><a href="<?php echo $base_url; ?>jadwal.php" class="text-white text-opacity-90 hover:text-white hover:opacity-100 text-sm transition-colors">Rute</a></li>
                        <li><a href="<?php echo $base_url; ?>tentang-kami.php" class="text-white text-opacity-90 hover:text-white hover:opacity-100 text-sm transition-colors">Tentang Kami</a></li>
                        <li><a href="<?php echo $base_url; ?>kontak.php" class="text-white text-opacity-90 hover:text-white hover:opacity-100 text-sm transition-colors">Kontak</a></li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div>
                    <h3 class="text-lg font-semibold mb-4">Hubungi Kami</h3>
                    <ul class="space-y-2 text-white text-opacity-90 text-sm">
                        <li class="flex items-start">
                            <svg class="h-5 w-5 mr-2 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Jl. Contoh No. 123, Jakarta Selatan
                        </li>
                        <li class="flex items-center">
                            <svg class="h-5 w-5 mr-2 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            info@travelkita.com
                        </li>
                        <li class="flex items-center">
                            <svg class="h-5 w-5 mr-2 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            (021) 1234-5678
                        </li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-white border-opacity-20 mt-8 pt-8 text-center text-white text-opacity-90 text-sm">
                <p class="mb-2">&copy; <?php echo date('Y'); ?> TravelKita. Semua Hak Dilindungi.</p>
                <div class="text-xs text-white text-opacity-70">
                    <a href="/travel_website/admin/login.php" class="hover:text-white hover:opacity-100 transition-colors">Admin Login</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Copyright -->
    <div class="bg-[#0099cc] py-4">
        <div class="container mx-auto px-4 text-center text-white text-opacity-90 text-sm">
            &copy; <?php echo date('Y'); ?> TravelKita. All rights reserved.
        </div>
    </div>
    
    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    // Initialize SweetAlert2 with default settings
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });
    
    // Global function to show toast messages
    function showToast(icon, message) {
        Toast.fire({
            icon: icon,
            title: message
        });
    }
    </script>

    <!-- Mobile Menu Toggle Script -->
    <script>
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        });
    </script>
</body>
</html>

