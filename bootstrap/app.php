<?php

use App\Exceptions\DataTidakDitemukanException;
use App\Http\Middleware\EnsureRole;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api/v1',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureRole::class,
        ]);

        // API tidak punya halaman login; tamu dibalas 401 JSON, bukan redirect.
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('api/*') ? null : '/');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // API selalu membalas JSON, dengan pesan Bahasa Indonesia (bagian 0 aturan 6 & 3.3).
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*') || $request->expectsJson());

        $exceptions->render(fn (AuthenticationException $e, Request $request) => $request->is('api/*')
            ? response()->json(['message' => 'Anda belum login atau sesi telah berakhir.'], 401)
            : null);

        $exceptions->render(fn (AccessDeniedHttpException $e, Request $request) => $request->is('api/*')
            ? response()->json(['message' => $e->getMessage() === 'This action is unauthorized.' || $e->getMessage() === ''
                ? 'Anda tidak memiliki akses untuk aksi ini.'
                : $e->getMessage()], 403)
            : null);

        // FR-06: DataTidakDitemukanException membawa pesan sendiri (mis. CV belum dibuat).
        $exceptions->render(fn (NotFoundHttpException $e, Request $request) => $request->is('api/*')
            ? response()->json(['message' => $e instanceof DataTidakDitemukanException ? $e->getMessage() : 'Data tidak ditemukan.'], 404)
            : null);

        $exceptions->render(fn (ThrottleRequestsException $e, Request $request) => $request->is('api/*')
            ? response()->json(['message' => 'Terlalu banyak percobaan. Silakan coba lagi nanti.'], 429, $e->getHeaders())
            : null);
    })->create();
