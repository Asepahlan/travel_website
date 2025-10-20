<<<<<<< HEAD
# travel_website
=======
# 🚗 TravelGo - Sistem Pemesanan Tiket Travel Antar Kota

Sistem pemesanan tiket travel modern dengan fitur lengkap untuk pencarian jadwal, pemilihan kursi interaktif, pembayaran online, dan manajemen admin yang komprehensif.

## 📋 Daftar Isi
- [Analisis Proyek](#analisis-proyek)
- [Fitur Lengkap](#fitur-lengkap)
- [Teknologi Stack](#teknologi-stack)
- [Struktur Database](#struktur-database)
- [Struktur File](#struktur-file)
- [API Endpoints](#api-endpoints)
- [Kebutuhan Sistem](#kebutuhan-sistem)
- [Instalasi & Setup](#instalasi--setup)
- [Konfigurasi](#konfigurasi)
- [Cara Penggunaan](#cara-penggunaan)
- [Panel Admin](#panel-admin)
- [Troubleshooting](#troubleshooting)
- [Development](#development)
- [Deployment](#deployment)
- [Kontribusi](#kontribusi)
- [Lisensi](#lisensi)

## 🎯 Analisis Proyek

### **Jenis Aplikasi**
Aplikasi web berbasis PHP untuk pemesanan tiket travel (bus/shuttle) antar kota dengan sistem manajemen lengkap.

### **Target Pengguna**
- **End Users**: Pelanggan yang ingin memesan tiket travel
- **Admin**: Operator yang mengelola jadwal, reservasi, dan sistem

### **Skala Proyek**
- **Frontend**: 8+ halaman utama dengan responsive design
- **Backend**: 15+ PHP files dengan logic bisnis lengkap
- **Database**: 10+ tabel dengan relasi kompleks
- **Admin Panel**: 8+ halaman manajemen dengan CRUD operations
- **API**: 3+ endpoints untuk integrasi eksternal

## ✨ Fitur Lengkap

### **🎫 Fitur User (Frontend)**

#### **Pencarian & Pemesanan**
- ✅ **Pencarian Jadwal**: Filter berdasarkan asal, tujuan, tanggal keberangkatan
- ✅ **Pemilihan Kursi Interaktif**: Layout visual kursi dengan real-time availability
- ✅ **Form Pemesanan**: Input data penumpang dengan validasi lengkap
- ✅ **Sistem Pembayaran**: Integrasi dengan WhatsApp untuk konfirmasi pembayaran
- ✅ **Booking Success**: Halaman konfirmasi dengan kode booking dan detail lengkap
- ✅ **Cetak Tiket**: Generate PDF tiket untuk dicetak

#### **Navigasi & UI/UX**
- ✅ **Responsive Design**: Mobile-first dengan Tailwind CSS
- ✅ **Modern UI**: Gradient backgrounds, card layouts, smooth animations
- ✅ **Loading States**: Spinner dan feedback visual saat proses
- ✅ **Error Handling**: Toast notifications dan error pages
- ✅ **SEO Friendly**: Meta tags dan struktur URL yang baik

#### **Fitur Tambahan**
- ✅ **Search Results**: Halaman hasil pencarian dengan sorting
- ✅ **About Us**: Informasi perusahaan dan layanan
- ✅ **Contact**: Form kontak dan informasi perusahaan
- ✅ **WhatsApp Integration**: Tombol WA dengan pesan pre-filled

### **⚙️ Fitur Admin (Backend)**

#### **Dashboard & Overview**
- ✅ **Admin Login**: Sistem autentikasi dengan session management
- ✅ **Dashboard Overview**: Statistik reservasi dan jadwal
- ✅ **Profile Management**: Update profil admin

#### **Manajemen Data**
- ✅ **Kelola Jadwal**: CRUD jadwal keberangkatan antar kota
- ✅ **Kelola Kota & Kecamatan**: Master data lokasi dengan relasi
- ✅ **Kelola Kursi**: Setup layout kursi per jadwal
- ✅ **Kelola Layout**: Template layout kursi (2x2, 3x2, dll.)

#### **Manajemen Reservasi**
- ✅ **Lihat Semua Reservasi**: Tabel dengan sorting dan filtering
- ✅ **Filter & Search**: Berdasarkan status, tanggal, alamat
- ✅ **Update Status**: Pending → Dibayar/Dibatalkan
- ✅ **Edit Alamat**: Update alamat jemput pelanggan
- ✅ **Hapus Reservasi**: Dengan konfirmasi dan rollback kursi
- ✅ **Export CSV**: Download data reservasi

#### **Konfirmasi & Verifikasi**
- ✅ **Konfirmasi Pembayaran**: Upload bukti pembayaran
- ✅ **Verifikasi Bukti**: Admin verifikasi pembayaran via upload
- ✅ **Notifikasi Status**: Update status otomatis

#### **Laporan & Analytics**
- ✅ **Export Data**: CSV download untuk analisis
- ✅ **Status Tracking**: Monitor konversi dan pembayaran

## 🛠 Teknologi Stack

### **Backend**
- **PHP 8.2+** - Server-side scripting
- **MySQLi** - Database operations dengan prepared statements
- **Session Management** - User authentication & state
- **File Upload** - Handling bukti pembayaran
- **JSON API** - RESTful endpoints

### **Frontend**
- **HTML5** - Semantic markup
- **Tailwind CSS** - Utility-first CSS framework
- **Vanilla JavaScript** - Interactive features
- **Responsive Design** - Mobile-first approach
- **Progressive Enhancement** - Graceful degradation

### **Database**
- **MySQL 8.0+** - Relational database
- **InnoDB Engine** - Transaction support
- **Foreign Keys** - Data integrity
- **Indexes** - Query optimization

### **Development Tools**
- **XAMPP** - Local development server
- **phpMyAdmin** - Database management
- **Git** - Version control
- **VS Code** - Code editor

## 🗄 Struktur Database

### **Core Tables**
```sql
-- User Management
admin (id, username, password, nama, email, created_at)

-- Location Management
kota (id_kota, nama_kota, provinsi)
kecamatan (id_kecamatan, nama_kecamatan, id_kota)

-- Schedule Management
jadwal (id_jadwal, id_kota_asal, id_kota_tujuan, tanggal_berangkat, waktu_berangkat, harga, kursi_tersedia)

-- Seat Management
kursi (id_kursi, nomor_kursi, posisi_x, posisi_y, status)
layout_kursi (id_layout, nama_layout, konfigurasi)

-- Reservation System
reservasi (id_reservasi, kode_booking, id_jadwal, nama_pemesan, no_hp, email, alamat_jemput, status, total_harga)
detail_reservasi_kursi (id_reservasi, id_kursi, harga)

-- Payment Verification
bukti_pembayaran (id_bukti, id_reservasi, nama_file, uploaded_at, status_verifikasi)
```

### **Key Relationships**
- **jadwal** ↔ **kota** (asal & tujuan)
- **jadwal** ↔ **kursi** (via detail_reservasi_kursi)
- **reservasi** ↔ **jadwal** (one-to-one)
- **reservasi** ↔ **bukti_pembayaran** (one-to-one)

## 📁 Struktur File

### **Root Directory Structure**
```
travel_website/
├── 📄 index.php                    # Homepage utama
├── 📄 jadwal.php                   # Pencarian jadwal
├── 📄 select_seat.php             # Pemilihan kursi
├── 📄 booking_success.php         # Konfirmasi booking
├── 📄 cetak_tiket.php             # Generate PDF tiket
├── 📄 confirmation.php            # Halaman konfirmasi
├── 📄 kontak.php                  # Halaman kontak
├── 📄 tentang-kami.php            # About us
├── 📄 search_results.php          # Hasil pencarian
├── 📄 proses_bukti.php            # Proses upload bukti
├── 📁 admin/                      # Panel admin
│   ├── 📄 index.php              # Dashboard admin
│   ├── 📄 login.php              # Login admin
│   ├── 📄 manage_reservasi.php   # Kelola reservasi
│   ├── 📄 manage_jadwal.php      # Kelola jadwal
│   ├── 📄 manage_kota.php        # Kelola kota
│   ├── 📄 manage_kursi.php       # Kelola kursi
│   ├── 📄 manage_layout.php      # Kelola layout
│   ├── 📄 profile.php            # Profil admin
│   ├── 📄 process_reservasi.php  # Proses reservasi
│   └── 📄 [files admin lainnya]
├── 📁 api/                       # API endpoints
│   ├── 📄 get_reservation_details.php
│   ├── 📄 update_reservation_status.php
│   └── 📄 [API lainnya]
├── 📁 config/                    # Konfigurasi
│   └── 📄 database.php           # Koneksi database
├── 📁 process/                   # Proses utama
│   ├── 📄 booking.php            # Proses booking
│   └── 📄 [proses lainnya]
├── 📁 templates/                 # Template files
│   ├── 📁 partials/              # Header, footer, navbar
│   └── 📄 [template files]
├── 📁 public/                    # Assets publik
│   ├── 📁 css/                   # Compiled CSS
│   └── 📁 js/                    # JavaScript files
├── 📁 uploads/                   # File upload
│   └── 📄 bukti_pembayaran/      # Bukti pembayaran
├── 📄 db_travel.sql              # Database schema
└── 📄 README.md                  # Dokumentasi ini
```

## 🔗 API Endpoints

### **Reservation APIs**
```javascript
// Get reservation details
GET /api/get_reservation_details.php?booking_code=TRV123456

// Update reservation status
POST /api/update_reservation_status.php
Content-Type: application/json
{
  "booking_code": "TRV123456",
  "status": "dibayar"
}
```

### **AJAX Endpoints (Admin)**
```javascript
// Update reservation status (Admin)
POST /admin/process_reservasi.php
{
  "action": "update_status",
  "id_reservasi": 123,
  "status": "dibayar"
}

// Delete reservation (Admin)
POST /admin/process_reservasi.php
{
  "action": "delete",
  "id_reservasi": 123
}

// Update pickup address (Admin)
POST /admin/process_reservasi.php
{
  "action": "update_alamat",
  "id_reservasi": 123,
  "alamat_jemput": "Jl. Sudirman No. 123"
}
```

## 🖥 Kebutuhan Sistem

### **Server Requirements**
- **PHP**: 8.0 atau lebih tinggi
- **MySQL**: 5.7+ atau MariaDB 10.4+
- **Web Server**: Apache/Nginx dengan mod_rewrite
- **Memory**: Minimal 128MB RAM
- **Disk Space**: 100MB free space

### **Development Tools**
- **XAMPP/LAMP**: Local development server
- **Git**: Version control
- **Text Editor**: VS Code/PhpStorm
- **Browser**: Chrome/Firefox untuk testing

### **Optional Tools**
- **Node.js**: Untuk Tailwind CSS build process
- **Composer**: PHP dependency management
- **phpMyAdmin**: Database management

## 🚀 Instalasi & Setup

### **Step 1: Environment Setup**
```bash
# 1. Install XAMPP
# Download dari: https://www.apachefriends.org/

# 2. Start XAMPP Control Panel
# Start Apache & MySQL modules

# 3. Clone/Download project
# Extract to: C:\xampp\htdocs\travel_website\
```

### **Step 2: Database Setup**
```bash
# 1. Open phpMyAdmin: http://localhost/phpmyadmin

# 2. Create new database: db_travel

# 3. Import database:
# - Select db_travel database
# - Click "Import" tab
# - Choose file: db_travel.sql
# - Click "Go"
```

### **Step 3: Configuration**
```bash
# 1. Update database config if needed
# Edit: config/database.php

# 2. Default credentials:
# Host: localhost
# Username: root
# Password: (empty for XAMPP default)
# Database: db_travel
```

### **Step 4: Admin Setup**
```bash
# 1. Access admin panel: http://localhost/travel_website/admin/

# 2. Default login:
# Username: admin
# Password: admin123 (from database)

# 3. Change password after first login!
```

### **Step 5: Testing**
```bash
# 1. Open browser: http://localhost/travel_website/

# 2. Test user flow:
# - Search schedules
# - Select seats
# - Complete booking
# - Check admin panel

# 3. Verify all features work correctly
```

## ⚙️ Konfigurasi

### **Database Configuration**
```php
// config/database.php
<?php
$host = 'localhost';
$username = 'root';
$password = ''; // Change this!
$database = 'db_travel';

$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
```

### **Admin Credentials**
```sql
-- Default admin account in database
INSERT INTO admin (username, password, nama, email) VALUES
('admin', MD5('admin123'), 'Administrator', 'admin@travelgo.com');
```

### **File Permissions**
```bash
# Set proper permissions
chmod 755 -R travel_website/
chmod 777 uploads/bukti_pembayaran/
```

## 🎮 Cara Penggunaan

### **Untuk Pengguna (End Users)**

#### **1. Pencarian Jadwal**
- Kunjungi homepage: `http://localhost/travel_website/`
- Pilih kota asal dan tujuan
- Pilih tanggal keberangkatan
- Klik "Cari Jadwal"

#### **2. Pemilihan Kursi**
- Pilih jadwal yang diinginkan
- Klik "Pilih Kursi"
- Pilih kursi yang tersedia (hijau = available)
- Klik "Lanjutkan"

#### **3. Pengisian Data**
- Isi form pemesanan dengan lengkap:
  - Nama lengkap
  - Nomor telepon (WhatsApp)
  - Email
  - Alamat penjemputan
- Klik "Lanjutkan"

#### **4. Konfirmasi Booking**
- Sistem akan memproses pemesanan
- Jika berhasil, akan muncul halaman sukses
- Kode booking akan ditampilkan
- Klik tombol WhatsApp untuk konfirmasi pembayaran

#### **5. Pembayaran**
- Admin akan menghubungi via WhatsApp
- Lakukan pembayaran sesuai instruksi
- Upload bukti pembayaran jika diminta
- Tunggu konfirmasi dari admin

### **Untuk Admin**

#### **1. Login Admin**
- Akses: `http://localhost/travel_website/admin/`
- Username: `admin`
- Password: `admin123` (ubah setelah login pertama)

#### **2. Kelola Jadwal**
- Menu "Kelola Jadwal"
- Tambah jadwal baru dengan rute dan harga
- Edit jadwal existing
- Hapus jadwal yang tidak diperlukan

#### **3. Kelola Reservasi**
- Menu "Manajemen Reservasi"
- Lihat semua pemesanan
- Filter berdasarkan status/tanggal
- Update status pembayaran
- Edit alamat jemput
- Hapus reservasi jika perlu

#### **4. Verifikasi Pembayaran**
- Menu "Konfirmasi Pembayaran"
- Lihat bukti pembayaran yang diupload
- Verifikasi pembayaran valid
- Update status reservasi

## 👨‍💼 Panel Admin

### **Dashboard Features**
- **Overview Cards**: Statistik reservasi, pendapatan, jadwal aktif
- **Recent Activity**: Aktivitas terbaru pengguna dan admin
- **Quick Actions**: Shortcut untuk tugas umum

### **Management Modules**
1. **Jadwal Management** - CRUD operasi untuk jadwal keberangkatan
2. **Kota Management** - Master data kota dan kecamatan
3. **Kursi Management** - Setup kursi per kendaraan
4. **Layout Management** - Template layout kursi
5. **Reservasi Management** - Monitor dan kelola pemesanan
6. **User Management** - Kelola akun admin

### **Security Features**
- **Session Management** - Secure login/logout
- **CSRF Protection** - Token validation pada forms
- **Input Sanitization** - XSS prevention
- **SQL Injection Prevention** - Prepared statements
- **File Upload Security** - Validation dan sanitasi

## 🐛 Troubleshooting

### **Common Issues & Solutions**

#### **1. Database Connection Error**
```bash
# Error: "Connection failed"
# Solution:
1. Check XAMPP MySQL is running
2. Verify database credentials in config/database.php
3. Ensure db_travel database exists
4. Check MySQL user permissions
```

#### **2. File Not Found (404)**
```bash
# Error: "The requested URL was not found"
# Solution:
1. Check file exists in correct directory
2. Verify .htaccess for URL rewriting
3. Clear browser cache
4. Restart Apache server
```

#### **3. CSS/JS Not Loading**
```bash
# Error: Styles not applied
# Solution:
1. Run: npm run build:css
2. Check public/css/style.css exists
3. Verify Tailwind config
4. Clear browser cache
```

#### **4. Upload Issues**
```bash
# Error: "Failed to upload file"
# Solution:
1. Check uploads/ directory permissions (chmod 777)
2. Verify file size limits in php.ini
3. Check available disk space
4. Validate file type restrictions
```

#### **5. Session Issues**
```bash
# Error: "Session expired" or login problems
# Solution:
1. Check PHP session configuration
2. Verify session directory exists and writable
3. Clear browser cookies
4. Restart browser
```

#### **6. Email Issues**
```bash
# Error: "Email not sent"
# Solution:
1. Check SMTP configuration in PHP
2. Verify email server settings
3. Test with different email providers
4. Check spam/junk folders
```

### **Debug Mode**
```php
// Enable debug mode in config/database.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

### **Log Files**
- **PHP Errors**: `xampp/php/logs/php_error_log`
- **Apache Logs**: `xampp/apache/logs/error_log`
- **Application Logs**: Check database for error entries

## 🔧 Development

### **Code Structure**
```bash
# PHP Structure
├── config/           # Database & app config
├── templates/        # Reusable PHP templates
├── admin/           # Admin panel (MVC pattern)
├── api/             # REST API endpoints
├── process/         # Business logic processing
└── public/          # Frontend assets
```

### **Naming Conventions**
- **Files**: `snake_case.php`
- **Classes**: `PascalCase`
- **Functions**: `camelCase()`
- **Variables**: `snake_case`
- **Database**: `snake_case` with prefixes

### **Security Practices**
- ✅ **Input Validation**: All user inputs validated
- ✅ **SQL Injection Prevention**: Prepared statements everywhere
- ✅ **XSS Protection**: htmlspecialchars() on outputs
- ✅ **CSRF Protection**: Token validation on forms
- ✅ **Session Security**: Secure session configuration

## 🚀 Deployment

### **Production Setup**
```bash
# 1. Update database credentials
# Edit: config/database.php

# 2. Set production PHP settings
# Edit: php.ini (memory_limit, upload_max_filesize, etc.)

# 3. Configure web server
# - Set document root to public/
# - Configure URL rewriting
# - Set proper file permissions

# 4. Database optimization
# - Enable query caching
# - Set proper indexes
# - Configure connection pooling
```

### **Hosting Recommendations**
- **Shared Hosting**: Hostinger, SiteGround, Bluehost
- **VPS**: DigitalOcean, Linode, Vultr
- **Cloud**: AWS, Google Cloud, Azure

### **Performance Optimization**
- **Caching**: Implement Redis/Memcached
- **CDN**: Cloudflare for static assets
- **Database**: Connection pooling
- **Images**: WebP format, lazy loading

## 🤝 Kontribusi

### **Cara Berkontribusi**
1. **Fork** repository
2. **Buat branch** untuk fitur baru: `git checkout -b feature/amazing-feature`
3. **Commit** perubahan: `git commit -m 'Add amazing feature'`
4. **Push** branch: `git push origin feature/amazing-feature`
5. **Buat Pull Request**

### **Development Workflow**
```bash
# Setup development environment
git clone <repository-url>
cd travel-website
npm install

# Create feature branch
git checkout -b feature/new-functionality

# Make changes and test
# Run: npm run build:css

# Commit and push
git add .
git commit -m "Add new functionality"
git push origin feature/new-functionality
```

### **Code Standards**
- **PHP**: PSR-12 coding standards
- **JavaScript**: ESLint configuration
- **CSS**: BEM methodology
- **Git**: Conventional commits

## 📜 Lisensi

Proyek ini menggunakan lisensi **MIT**.

```
MIT License

Copyright (c) 2024 TravelGo

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

---

## 🎉 **Kesimpulan**

**TravelGo** adalah sistem pemesanan tiket travel yang lengkap dan modern dengan:

✅ **Frontend responsif** dengan UX/UI yang excellent  
✅ **Backend robust** dengan security yang baik  
✅ **Admin panel komprehensif** untuk operasional harian  
✅ **API ready** untuk integrasi dengan sistem lain  
✅ **Database well-designed** dengan relasi yang optimal  
✅ **Code quality tinggi** dengan best practices  
✅ **Documentation lengkap** untuk maintenance dan development  

Sistem ini siap untuk **production deployment** dan dapat dikembangkan lebih lanjut sesuai kebutuhan bisnis.

**Happy coding! 🚀**
>>>>>>> 6249569 (upload project travel online pertama)
