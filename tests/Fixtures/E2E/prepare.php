<?php

use App\Models\Block;
use App\Models\Borrow;
use App\Models\Employee;
use App\Models\Group;
use App\Models\Key;
use App\Models\Permission;
use App\Models\Received;
use App\Models\Room;
use App\Models\Rule;
use App\Models\User;
use Database\Seeders\GroupSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RuleSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

require __DIR__.'/../../../vendor/autoload.php';
$app = require __DIR__.'/../../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (DB::connection()->getDatabaseName() !== 'testing' || $app->configurationIsCached()) {
    throw new RuntimeException('E2E setup requires the testing database and uncached configuration.');
}

if (Artisan::call('migrate:fresh', ['--force' => true, '--no-interaction' => true]) !== 0) {
    throw new RuntimeException(Artisan::output());
}

foreach ([GroupSeeder::class, RuleSeeder::class, PermissionSeeder::class] as $seeder) {
    if (Artisan::call('db:seed', ['--class' => $seeder, '--force' => true, '--no-interaction' => true]) !== 0) {
        throw new RuntimeException(Artisan::output());
    }
}

$administrator = User::factory()->create([
    'name' => 'E2E Administrador',
    'email' => 'admin@e2e.test',
    'password' => Hash::make('e2e-password'),
    'status' => User::ACTIVE,
    'permission_id' => Permission::where('is_admin', true)->firstOrFail()->id,
]);
User::factory()->count(2)->create(['status' => User::ACTIVE]);
$block = Block::factory()->create(['description' => 'E2E Bloco principal']);
$otherBlock = Block::factory()->create(['description' => 'E2E Bloco alternativo']);
$room = Room::factory()->create(['description' => 'E2E Sala principal', 'block_id' => $block->id]);
$otherRoom = Room::factory()->create(['description' => 'E2E Sala alternativa', 'block_id' => $otherBlock->id]);
Room::factory()->create(['description' => 'E2E Sala adicional', 'block_id' => $block->id]);
$key = Key::factory()->create(['number' => 90001, 'description' => 'E2E Chave livre', 'room_id' => $room->id]);
Key::factory()->create(['number' => 90002, 'room_id' => $otherRoom->id]);
$staff = Employee::factory()->create([
    'name' => 'E2E Servidor', 'registry' => '900001', 'type' => Employee::EMPLOYEE, 'valid_until' => null,
]);
$employee = Employee::factory()->create([
    'name' => 'E2E Discente', 'email' => 'student@e2e.test', 'registry' => '900002',
    'type' => Employee::STUDENT, 'tel' => '88999998888', 'valid_until' => now()->addMonth()->toDateString(),
]);
$employee->borrowable_keys()->sync([$key->id]);
Employee::factory()->create(['name' => 'E2E Colaborador', 'type' => Employee::COLLABORATOR, 'valid_until' => null]);
$room->employees()->sync([$staff->id]);

for ($index = 1; $index <= 15; $index++) {
    Block::factory()->create(['description' => sprintf('E2E Bloco paginado %02d', $index)]);
    $borrowKey = Key::factory()->create(['number' => 10000 + $index, 'room_id' => $room->id]);
    $borrow = Borrow::factory()->create([
        'employee_id' => $staff->id, 'user_id' => $administrator->id, 'devolution' => null,
        'created_at' => now()->subMinutes($index),
    ]);
    $borrow->keys()->sync([$borrowKey->id]);
}

$overdue = Borrow::factory()->create([
    'employee_id' => $staff->id, 'user_id' => $administrator->id,
    'devolution' => null, 'created_at' => now()->subDays(2),
]);
$overdueKey = Key::factory()->create(['number' => 10016, 'room_id' => $room->id]);
$overdue->keys()->sync([$overdueKey->id]);
$returned = Borrow::factory()->create([
    'employee_id' => $staff->id, 'user_id' => $administrator->id,
    'created_at' => now()->subDays(3), 'devolution' => now()->subHour(),
    'received_by' => $administrator->id, 'returned_by' => 'E2E Servidor',
]);
$returnedKey = Key::factory()->create(['number' => 10017, 'room_id' => $room->id]);
$returned->keys()->sync([$returnedKey->id]);
$receipt = Received::create(['borrow_id' => $returned->id, 'user_id' => $administrator->id, 'receiver' => 'E2E Servidor']);
$receipt->keys()->sync([$returnedKey->id]);

$group = Group::factory()->create(['description' => 'E2E Grupo edição']);
$otherGroup = Group::factory()->create(['description' => 'E2E Outro grupo']);
$permission = Permission::factory()->create(['description' => 'E2E Perfil edição']);
$rule = Rule::factory()->create([
    'description' => 'E2E Regra edição', 'control' => 'browser.read', 'group_id' => $otherGroup->id,
]);

echo json_encode([
    'employee' => array_merge($employee->only(['id', 'name', 'email', 'registry', 'tel']), ['valid_until' => $employee->getRawOriginal('valid_until')]),
    'staff' => $staff->only(['id', 'name', 'registry']),
    'block' => $block->only(['id', 'description']),
    'otherBlock' => $otherBlock->only(['id', 'description']),
    'room' => $room->only(['id', 'description']),
    'otherRoom' => $otherRoom->only(['id', 'description']),
    'key' => $key->only(['id', 'number']),
    'group' => $group->only(['id', 'description']),
    'otherGroup' => $otherGroup->only(['id', 'description']),
    'permission' => $permission->only(['id', 'description']),
    'rule' => $rule->only(['id', 'description', 'control', 'group_id']),
    'dates' => ['start' => now()->subDays(7)->toDateString(), 'end' => now()->toDateString()],
], JSON_THROW_ON_ERROR);
