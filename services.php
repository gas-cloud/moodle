<?php
defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_examoverride_create_group_override' => [
        'classname'   => 'local_examoverride_external',
        'methodname'  => 'create_group_override',
        'classpath'   => 'local/examoverride/externallib.php',
        'description' => 'Buat atau update override grup untuk 1 quiz (jendela waktu buka/tutup, durasi, password, jumlah percobaan).',
        'type'        => 'write',
        'ajax'        => false,
    ],
    'local_examoverride_delete_group_override' => [
        'classname'   => 'local_examoverride_external',
        'methodname'  => 'delete_group_override',
        'classpath'   => 'local/examoverride/externallib.php',
        'description' => 'Hapus override grup untuk 1 quiz.',
        'type'        => 'write',
        'ajax'        => false,
    ],
    'local_examoverride_get_group_overrides' => [
        'classname'   => 'local_examoverride_external',
        'methodname'  => 'get_group_overrides',
        'classpath'   => 'local/examoverride/externallib.php',
        'description' => 'Ambil semua override grup yang ada di 1 quiz.',
        'type'        => 'read',
        'ajax'        => false,
    ],
];

$services = [
    'Exam Override WS' => [
        'functions'       => [
            'local_examoverride_create_group_override',
            'local_examoverride_delete_group_override',
            'local_examoverride_get_group_overrides',
        ],
        'restrictedusers' => 0,
        'enabled'         => 1,
        'shortname'       => 'local_examoverride_ws',
    ],
];
