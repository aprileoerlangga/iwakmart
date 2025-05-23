<?php

return [
    'pages' => [
        'dashboard' => [
            'title' => 'Dasbor',
        ],
    ],
    'resources' => [
        'title' => 'Sumber Daya',
        'create' => 'Buat',
        'edit' => 'Edit',
        'save' => 'Simpan',
        'cancel' => 'Batal',
        'delete' => 'Hapus',
        'restore' => 'Pulihkan',
        'forceDelete' => 'Hapus Permanen',
    ],
    'auth' => [
        'title' => 'Autentikasi',
        'login' => [
            'title' => 'Masuk',
            'heading' => 'Masuk ke akun Anda',
            'buttons' => [
                'submit' => 'Masuk',
            ],
            'fields' => [
                'email' => 'Email',
                'password' => 'Kata Sandi',
                'remember' => 'Ingat saya',
            ],
            'messages' => [
                'failed' => 'Kredensial yang diberikan tidak cocok dengan catatan kami.',
            ],
        ],
        'register' => [
            'title' => 'Daftar',
            'heading' => 'Buat akun baru',
            'buttons' => [
                'submit' => 'Daftar',
            ],
            'fields' => [
                'name' => 'Nama',
                'email' => 'Email',
                'password' => 'Kata Sandi', 
                'passwordConfirmation' => 'Konfirmasi Kata Sandi',
            ],
        ],
        'passwords' => [
            'reset' => [
                'title' => 'Reset Kata Sandi',
                'heading' => 'Reset kata sandi Anda',
                'buttons' => [
                    'submit' => 'Reset Kata Sandi',
                ],
                'fields' => [
                    'email' => 'Email',
                    'password' => 'Kata Sandi',
                    'passwordConfirmation' => 'Konfirmasi Kata Sandi',
                ],
            ],
            'request' => [
                'title' => 'Lupa Kata Sandi',
                'heading' => 'Lupa kata sandi Anda?',
                'buttons' => [
                    'submit' => 'Kirim tautan reset kata sandi',
                ],
                'fields' => [
                    'email' => 'Email',
                ],
            ],
        ],
    ],
    'notifications' => [
        'title' => 'Notifikasi',
    ],
];