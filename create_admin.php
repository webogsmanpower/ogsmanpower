<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$role = Spatie\Permission\Models\Role::first();
if (!$role) {
    echo "No roles found!\n";
    exit;
}

$admin = App\Models\Admin::firstOrNew(['email' => 'webogsmanpower@gmail.com']);
$admin->name = 'Web OGS Manpower Admin';
$admin->password = bcrypt('password123');
$admin->email_verified_at = now();
$admin->save();
$admin->assignRole($role);

echo "Admin created successfully! Email: webogsmanpower@gmail.com, Password: password123\n";
