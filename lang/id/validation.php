<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Terjemahan pesan validasi Bahasa Indonesia. Key yang tidak tercantum
    | akan jatuh ke fallback locale (en).
    |
    */

    'accepted' => 'Kolom :attribute harus diterima.',
    'accepted_if' => 'Kolom :attribute harus diterima saat :other adalah :value.',
    'active_url' => 'Kolom :attribute bukan URL yang valid.',
    'after' => 'Kolom :attribute harus tanggal setelah :date.',
    'after_or_equal' => 'Kolom :attribute harus tanggal setelah atau sama dengan :date.',
    'alpha' => 'Kolom :attribute hanya boleh berisi huruf.',
    'alpha_dash' => 'Kolom :attribute hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
    'alpha_num' => 'Kolom :attribute hanya boleh berisi huruf dan angka.',
    'array' => 'Kolom :attribute harus berupa array.',
    'before' => 'Kolom :attribute harus tanggal sebelum :date.',
    'before_or_equal' => 'Kolom :attribute harus tanggal sebelum atau sama dengan :date.',
    'boolean' => 'Kolom :attribute harus bernilai true atau false.',
    'can' => 'Kolom :attribute berisi nilai yang tidak diizinkan.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'current_password' => 'Password salah.',
    'date' => 'Kolom :attribute bukan tanggal yang valid.',
    'date_equals' => 'Kolom :attribute harus tanggal yang sama dengan :date.',
    'date_format' => 'Kolom :attribute tidak cocok dengan format :format.',
    'decimal' => 'Kolom :attribute harus memiliki :decimal tempat desimal.',
    'declined' => 'Kolom :attribute harus ditolak.',
    'declined_if' => 'Kolom :attribute harus ditolak saat :other adalah :value.',
    'different' => 'Kolom :attribute dan :other harus berbeda.',
    'digits' => 'Kolom :attribute harus memiliki :digits digit.',
    'digits_between' => 'Kolom :attribute harus antara :min dan :max digit.',
    'dimensions' => 'Kolom :attribute memiliki dimensi gambar yang tidak valid.',
    'distinct' => 'Kolom :attribute memiliki nilai duplikat.',
    'doesnt_end_with' => 'Kolom :attribute tidak boleh berakhir dengan salah satu dari: :values.',
    'doesnt_start_with' => 'Kolom :attribute tidak boleh dimulai dengan salah satu dari: :values.',
    'email' => 'Kolom :attribute harus berupa alamat email yang valid.',
    'ends_with' => 'Kolom :attribute harus berakhir dengan salah satu dari: :values.',
    'enum' => 'Kolom :attribute yang dipilih tidak valid.',
    'exists' => 'Kolom :attribute yang dipilih tidak valid.',
    'file' => 'Kolom :attribute harus berupa file.',
    'filled' => 'Kolom :attribute wajib diisi.',
    'gt' => 'Kolom :attribute harus lebih besar dari :value.',
    'gt_array' => 'Kolom :attribute harus memiliki lebih dari :value item.',
    'gte' => 'Kolom :attribute harus lebih besar dari atau sama dengan :value.',
    'gte_array' => 'Kolom :attribute harus memiliki :value item atau lebih.',
    'hex_color' => 'Kolom :attribute harus berupa warna heksadesimal yang valid.',
    'image' => 'Kolom :attribute harus berupa gambar.',
    'in' => 'Kolom :attribute yang dipilih tidak valid.',
    'in_array' => 'Kolom :attribute tidak ada di :other.',
    'integer' => 'Kolom :attribute harus berupa bilangan bulat.',
    'ip' => 'Kolom :attribute harus berupa alamat IP yang valid.',
    'ipv4' => 'Kolom :attribute harus berupa alamat IPv4 yang valid.',
    'ipv6' => 'Kolom :attribute harus berupa alamat IPv6 yang valid.',
    'json' => 'Kolom :attribute harus berupa string JSON yang valid.',
    'lowercase' => 'Kolom :attribute harus huruf kecil.',
    'lt' => 'Kolom :attribute harus lebih kecil dari :value.',
    'lt_array' => 'Kolom :attribute harus memiliki kurang dari :value item.',
    'lte' => 'Kolom :attribute harus lebih kecil dari atau sama dengan :value.',
    'lte_array' => 'Kolom :attribute harus memiliki tidak lebih dari :value item.',
    'mac_address' => 'Kolom :attribute harus berupa alamat MAC yang valid.',
    'max' => [
        'array' => 'Kolom :attribute tidak boleh lebih dari :max item.',
        'file' => 'Kolom :attribute tidak boleh lebih besar dari :max kilobyte.',
        'numeric' => 'Kolom :attribute tidak boleh lebih besar dari :max.',
        'string' => 'Kolom :attribute tidak boleh lebih panjang dari :max karakter.',
    ],
    'max_digits' => 'Kolom :attribute tidak boleh lebih dari :max digit.',
    'mimes' => 'Kolom :attribute harus berupa file dengan tipe: :values.',
    'mimetypes' => 'Kolom :attribute harus berupa file dengan tipe: :values.',
    'min' => [
        'array' => 'Kolom :attribute harus memiliki minimal :min item.',
        'file' => 'Kolom :attribute harus minimal :min kilobyte.',
        'numeric' => 'Kolom :attribute harus minimal :min.',
        'string' => 'Kolom :attribute harus minimal :min karakter.',
    ],
    'min_digits' => 'Kolom :attribute harus minimal :min digit.',
    'missing' => 'Kolom :attribute harus hilang.',
    'missing_if' => 'Kolom :attribute harus hilang saat :other adalah :value.',
    'missing_unless' => 'Kolom :attribute harus hilang kecuali :other adalah :value.',
    'missing_with' => 'Kolom :attribute harus hilang saat :values ada.',
    'missing_with_all' => 'Kolom :attribute harus hilang saat semua :values ada.',
    'multiple_of' => 'Kolom :attribute harus merupakan kelipatan dari :value.',
    'not_in' => 'Kolom :attribute yang dipilih tidak valid.',
    'not_regex' => 'Format kolom :attribute tidak valid.',
    'numeric' => 'Kolom :attribute harus berupa angka.',
    'password' => [
        'letters' => 'Kolom :attribute harus berisi setidaknya satu huruf.',
        'mixed' => 'Kolom :attribute harus berisi setidaknya satu huruf besar dan satu huruf kecil.',
        'numbers' => 'Kolom :attribute harus berisi setidaknya satu angka.',
        'symbols' => 'Kolom :attribute harus berisi setidaknya satu simbol.',
        'uncompromised' => 'Kolom :attribute yang diberikan telah bocor. Silakan pilih :attribute yang berbeda.',
    ],
    'present' => 'Kolom :attribute harus ada.',
    'present_if' => 'Kolom :attribute harus ada saat :other adalah :value.',
    'present_unless' => 'Kolom :attribute harus ada kecuali :other adalah :value.',
    'present_with' => 'Kolom :attribute harus ada saat :values ada.',
    'present_with_all' => 'Kolom :attribute harus ada saat semua :values ada.',
    'prohibited' => 'Kolom :attribute dilarang diisi.',
    'prohibited_if' => 'Kolom :attribute dilarang diisi saat :other adalah :value.',
    'prohibited_if_accepted' => 'Kolom :attribute dilarang diisi saat :other diterima.',
    'prohibited_if_declined' => 'Kolom :attribute dilarang diisi saat :other ditolak.',
    'prohibited_unless' => 'Kolom :attribute dilarang diisi kecuali :other ada di :values.',
    'prohibits' => 'Kolom :attribute dilarang diisi saat :other ada.',
    'regex' => 'Format kolom :attribute tidak valid.',
    'required' => 'Kolom :attribute wajib diisi.',
    'required_array_keys' => 'Kolom :attribute harus berisi kunci: :values.',
    'required_if' => 'Kolom :attribute wajib diisi saat :other adalah :value.',
    'required_if_accepted' => 'Kolom :attribute wajib diisi saat :other diterima.',
    'required_if_declined' => 'Kolom :attribute wajib diisi saat :other ditolak.',
    'required_unless' => 'Kolom :attribute wajib diisi kecuali :other ada di :values.',
    'required_with' => 'Kolom :attribute wajib diisi saat :values ada.',
    'required_with_all' => 'Kolom :attribute wajib diisi saat semua :values ada.',
    'required_without' => 'Kolom :attribute wajib diisi saat :values tidak ada.',
    'required_without_all' => 'Kolom :attribute wajib diisi saat semua :values tidak ada.',
    'same' => 'Kolom :attribute dan :other harus sama.',
    'size' => [
        'array' => 'Kolom :attribute harus berisi :size item.',
        'file' => 'Kolom :attribute harus berukuran :size kilobyte.',
        'numeric' => 'Kolom :attribute harus :size.',
        'string' => 'Kolom :attribute harus berukuran :size karakter.',
    ],
    'starts_with' => 'Kolom :attribute harus dimulai dengan salah satu dari: :values.',
    'string' => 'Kolom :attribute harus berupa teks.',
    'timezone' => 'Kolom :attribute harus merupakan zona waktu yang valid.',
    'unique' => ':attribute ini sudah digunakan.',
    'uploaded' => 'Kolom :attribute gagal diunggah.',
    'uppercase' => 'Kolom :attribute harus huruf besar.',
    'url' => 'Kolom :attribute harus berupa URL yang valid.',
    'ulid' => 'Kolom :attribute harus berupa ULID yang valid.',
    'uuid' => 'Kolom :attribute harus berupa UUID yang valid.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    */

    'custom' => [
        'password' => [
            'confirmed' => 'Konfirmasi password tidak cocok.',
            'min' => 'Password terlalu pendek (minimal :min karakter).',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    */

    'attributes' => [
        'login' => 'username/email',
        'username' => 'username',
        'name' => 'nama',
        'email' => 'email',
        'password' => 'password',
        'current_password' => 'password saat ini',
        'password_confirmation' => 'konfirmasi password',
        'remember' => 'ingat saya',
        'role' => 'role',
    ],

];
