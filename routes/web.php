<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Route::get('/', fn() => view('welcome'));

Route::get('/_deploy/hook', function (\Illuminate\Http\Request $r) {
    abort_unless($r->query('key') === config('app.deploy_key'), 403);
    try {
        Artisan::call('migrate', ['--force' => true]);
        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('view:cache');
        try { Artisan::call('storage:link'); } catch (\Throwable $e) {}
        return 'OK';
    } catch (\Throwable $e) {
        \Log::error('Deploy hook error: '.$e->getMessage()."\n".$e->getTraceAsString());
        return response('ERROR: '.$e->getMessage(), 500);
    }
});

Route::get('/_health', function (\Illuminate\Http\Request $r) {
    abort_unless($r->query('key') === config('app.deploy_key'), 403);
    $info = [
        'php'     => PHP_VERSION,
        'laravel' => app()->version(),
        'db'      => null,
    ];
    try {
        DB::connection()->getPdo();
        $info['db'] = 'ok';
    } catch (\Throwable $e) {
        $info['db'] = 'error: '.$e->getMessage();
    }
    return response()->json($info);
});
