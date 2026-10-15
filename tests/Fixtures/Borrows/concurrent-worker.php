<?php

use App\Actions\Borrows\CreateBorrow;
use App\Actions\Borrows\DeleteReceived;
use App\Actions\Borrows\ReceiveKeys;
use App\Models\Borrow;
use App\Models\Received;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

require __DIR__.'/../../../vendor/autoload.php';
$app = require __DIR__.'/../../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (! $app->environment('testing') || DB::connection()->getDatabaseName() !== 'testing' || DB::connection()->getDriverName() !== 'mysql') {
    throw new RuntimeException('Concurrency workers require the isolated MySQL testing database.');
}

$input = json_decode(base64_decode($argv[1]), true, flags: JSON_THROW_ON_ERROR);
$isKeyLock = fn (string $sql) => str_contains($sql, 'from `keys`') && str_contains($sql, 'for update');
$waitFor = function (string $file): void {
    $deadline = microtime(true) + 15;
    while (! is_file($file)) {
        if (microtime(true) > $deadline) {
            throw new RuntimeException('Concurrency barrier timed out.');
        }
        usleep(10000);
    }
};

// Reproduce a snapshot taken before the holder commits its new occupation/return.
if ($input['role'] === 'contender') {
    DB::beginTransaction();
    DB::table('borrows')->count();
    DB::connection()->beforeExecuting(function (string $sql) use ($isKeyLock, $input) {
        if ($isKeyLock($sql)) {
            touch($input['barrier'].'/attempting');
        }
    });
} else {
    $paused = false;
    DB::listen(function (QueryExecuted $query) use ($isKeyLock, $waitFor, $input, &$paused) {
        if (! $paused && $isKeyLock($query->sql)) {
            $paused = true;
            touch($input['barrier'].'/locked');
            $waitFor($input['barrier'].'/release');
        }
    });
}

try {
    $user = User::findOrFail($input['user_id']);
    $borrow = isset($input['borrow_id']) ? Borrow::findOrFail($input['borrow_id']) : null;
    match ($input['operation']) {
        'create' => app(CreateBorrow::class)->handle($user, ['employee_id' => $input['employee_id'], 'keys' => [$input['key_id']]]),
        'receive' => app(ReceiveKeys::class)->handle($borrow, $user, [$input['key_id']], 'Recepção'),
        'delete-return' => app(DeleteReceived::class)->handle($borrow, Received::findOrFail($input['received_id']), $user),
    };
    if ($input['role'] === 'contender') {
        DB::commit();
    }
    echo json_encode(['status' => 'success'], JSON_THROW_ON_ERROR);
} catch (ValidationException $exception) {
    if ($input['role'] === 'contender') {
        DB::rollBack();
    }
    echo json_encode(['status' => 'conflict', 'errors' => $exception->errors()], JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    if ($input['role'] === 'contender' && DB::transactionLevel() > 0) {
        DB::rollBack();
    }
    echo json_encode(['status' => 'error', 'exception' => get_class($exception), 'message' => $exception->getMessage()], JSON_THROW_ON_ERROR);
    exit(1);
}
