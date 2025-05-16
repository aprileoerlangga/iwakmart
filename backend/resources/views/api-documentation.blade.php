<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iwak Mart API Documentation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .endpoint {
            margin-bottom: 20px;
            padding: 20px;
            border-radius: 5px;
            background-color: #f8f9fa;
        }
        .method {
            padding: 5px 10px;
            border-radius: 4px;
            color: white;
            display: inline-block;
            width: 70px;
            text-align: center;
        }
        .get { background-color: #28a745; }
        .post { background-color: #007bff; }
        .put { background-color: #ffc107; color: #212529; }
        .delete { background-color: #dc3545; }
        code {
            background-color: #e9ecef;
            padding: 2px 4px;
            border-radius: 3px;
        }
        pre {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 5px;
        }
        .response-example {
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container my-5">
        <h1>Iwak Mart API Documentation</h1>
        <p class="lead">Dokumentasi resmi untuk Iwak Mart E-commerce API</p>
        
        <div class="alert alert-info mt-3">
            <h5>Autentikasi</h5>
            <p>Endpoints yang memerlukan autentikasi harus menyertakan token dalam header:</p>
            <code>Authorization: Bearer YOUR_TOKEN</code>
            <p class="mb-0">Token diperoleh setelah login atau registrasi berhasil.</p>
        </div>
        
        <h2 class="mt-4">Autentikasi</h2>
        
        <div class="endpoint">
            <span class="method post">POST</span>
            <code>/api/register</code>
            <p>Mendaftarkan akun pengguna baru</p>
            <h6>Request Body:</h6>
            <pre><code>{
  "name": "string, required",
  "email": "string, required, unique",
  "password": "string, required, min:8",
  "password_confirmation": "string, required",
  "phone": "string, optional",
  "address": "string, optional"
}</code></pre>
            <h6 class="response-example">Response Example (201):</h6>
            <pre><code>{
  "message": "User registered successfully",
  "user": {
    "id": 1,
    "name": "Pengguna Baru",
    "email": "user@example.com",
    "phone": "081234567890",
    "address": "Jl. Contoh No. 123",
    "created_at": "2023-05-01T12:00:00.000000Z",
    "updated_at": "2023-05-01T12:00:00.000000Z"
  },
  "token": "1|abcdefghijklmnopqrstuvwxyz12345"
}</code></pre>
        </div>
        
        <div class="endpoint">
            <span class="method post">POST</span>
            <code>/api/login</code>
            <p>Masuk dengan akun yang sudah ada</p>
            <h6>Request Body:</h6>
            <pre><code>{
  "email": "string, required",
  "password": "string, required"
}</code></pre>
            <h6 class="response-example">Response Example (200):</h6>
            <pre><code>{
  "message": "Login successful",
  "user": {
    "id": 1,
    "name": "Pengguna Baru",
    "email": "user@example.com",
    "phone": "081234567890",
    "address": "Jl. Contoh No. 123",
    "created_at": "2023-05-01T12:00:00.000000Z",
    "updated_at": "2023-05-01T12:00:00.000000Z"
  },
  "token": "1|abcdefghijklmnopqrstuvwxyz12345"
}</code></pre>
        </div>
        
        <div class="endpoint">
            <span class="method post">POST</span>
            <code>/api/logout</code>
            <p>Keluar dan mencabut token (memerlukan autentikasi)</p>
            <h6 class="response-example">Response Example (200):</h6>
            <pre><code>{
  "message": "Logged out successfully"
}</code></pre>
        </div>
        
        <h2 class="mt-4">Produk</h2>
        
        <div class="endpoint">
            <span class="method get">GET</span>
            <code>/api/products</code>
            <p>Mendapatkan daftar produk</p>
            <h6>Query Parameters:</h6>
            <ul>
                <li><code>category</code> - Filter berdasarkan ID kategori</li>
                <li><code>seller</code> - Filter berdasarkan ID penjual</li>
                <li><code>jenis_ikan</code> - Filter berdasarkan jenis ikan</li>
                <li><code>unggulan</code> - Atur ke 1 untuk menampilkan produk unggulan</li>
                <li><code>search</code> - Cari produk berdasarkan nama atau deskripsi</li>
                <li><code>sort</code> - Urutkan produk (price_low, price_high, newest, rating)</li>
                <li><code>page</code> - Nomor halaman untuk pagination</li>
            </ul>
            <h6 class="response-example">Response Example (200):</h6>
            <pre><code>{
  "data": [
    {
      "id": 1,
      "nama": "Ikan Lele Segar",
      "slug": "ikan-lele-segar",
      "deskripsi": "Ikan lele segar berkualitas tinggi",
      "harga": 35000,
      "stok": 50,
      "berat": 1,
      "jenis_ikan": "segar",
      "spesies_ikan": "Clarias batrachus",
      "gambar": ["products/lele1.jpg", "products/lele2.jpg"],
      "aktif": true,
      "unggulan": false,
      "rating_rata": 4.5,
      "jumlah_ulasan": 10,
      "kategori": {
        "id": 1,
        "nama": "Ikan Air Tawar"
      },
      "penjual": {
        "id": 1,
        "nama": "Toko Ikan Sejahtera"
      },
      "created_at": "2023-05-01T12:00:00.000000Z",
      "updated_at": "2023-05-01T12:00:00.000000Z"
    }
    // more products...
  ],
  "links": {
    "first": "http://localhost/api/products?page=1",
    "last": "http://localhost/api/products?page=5",
    "prev": null,
    "next": "http://localhost/api/products?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "path": "http://localhost/api/products",
    "per_page": 10,
    "to": 10,
    "total": 50
  }
}</code></pre>
        </div>
        
        <!-- Add more endpoints here -->
        
    </div>
    
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Iwak Mart API</h5>
                    <p>Versi 1.0</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p>Untuk bantuan dan pertanyaan, hubungi:</p>
                    <p>support@iwakmart.com</p>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>