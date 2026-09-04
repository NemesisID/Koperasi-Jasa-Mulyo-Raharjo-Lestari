Backend API Architecture & Engineering Rules
1. Core Architectural Principles
Architecture Style: Layered Architecture (Service-Repository Pattern) berbasis REST API.

Scope Boundary: BACKEND ONLY. Dilarang keras memuat logika UI, HTML rendering, Blade views, session-based redirects, atau dependensi visual/frontend apa pun.

Data Interchange Standard: Seluruh respons HTTP WAJIB berformat JSON yang telah ditransformasi melalui API Resources/DTO boundary.

Separation of Concerns:

Controller: Menangani skema HTTP (Request, Response Code, Headers, Calling Service).

Form Request: Validasi input & otorisasi awal payload HTTP.

Service: Mengisolasi seluruh Business Logic, kalkulasi, alur transaksi DB, dan side-effects.

Repository: Mengisolasi query database/ORM. Tidak boleh berisi business logic.

Resource/DTO: Mengisolasi pemetaan/formatting data JSON ke client.

2. Directory & Namespace Structure
Setiap komponen wajib ditempatkan pada namespace dan hirarki berikut:

Plaintext
app/
├── Http/
│   ├── Controllers/Api/v1/   # Endpoint HTTP
│   ├── Requests/<Domain>/    # Form Request Validation
│   └── Resources/<Domain>/   # API Data Transformers
├── Services/                  # Business Logic Layer
├── Repositories/
│   ├── Contracts/            # Interfaces
│   └── Eloquent/             # Implementation Layer
└── Models/                   # Data Schema / ORM Models
3. Mandatory Layer Constraints & Rules
A. Controller Layer Rules
Thin Controller: Controller tidak boleh berisi query Eloquent (Model::find(), DB::table()), kalkulasi bisnis, atau sintaks try-catch berulang.

Pure Injection: Panggil kelas Service via Dependency Injection pada constructor.

Response Contract: Gunakan Illuminate\Http\JsonResponse atau AnonymousResourceCollection.

PHP
// ✅ BENAR
public function store(StoreProductRequest $request): JsonResponse 
{
    $product = $this->productService->createProduct($request->validated());
    return response()->json([
        'message' => 'Product created successfully',
        'data'    => new ProductResource($product)
    ], 201);
}

// ❌ SALAH (Dilarang query/logika di Controller)
public function store(Request $request) 
{
    $product = Product::create($request->all()); // Direct Eloquent
    return response()->json($product);
}
B. Repository Layer Rules
Harus diimplementasikan menggunakan Interface (ProductRepositoryInterface).

Seluruh Method penarikan data (get, find, paginate) WAKTU mengembalikan Instance/Collection dari Model atau Paginator, bukan Array mentah.

Dilarang memanggil request(), session(), atau auth() di dalam Repository.

PHP
// ✅ BENAR
public function findById(int $id): ?Product 
{
    return Product::with(['category', 'tags'])->find($id);
}
C. Service Layer Rules
Menjadi satu-satunya layer tempat logika transaksi DB::transaction() dieksekusi untuk multi-write operations.

Menangani pelemparan Custom Exception jika terjadi pelanggaran aturan bisnis (misal: stok habis, item tidak ditemukan).

Bertugas melakukan transformasi logika internal (seperti pembuatan slug, enkripsi data, hashing).

PHP
// ✅ BENAR
public function createProduct(array $data): Product 
{
    return DB::transaction(function () use ($data) {
        $data['slug'] = Str::slug($data['name']);
        return $this->productRepository->create($data);
    });
}
D. Validation & DTO Boundary Rules
Strict Validation: Dilarang menggunakan $request->validate() di dalam Controller. Wajib membuat kelas FormRequest tersendiri.

API Resource Obligation: Semua data keluaran Model yang dikirimkan ke Frontend HARUS melewati JsonResource.

Data Hiding: Sembunyikan attribute internal yang tidak relevan bagi Frontend (password_hash, deleted_at, internal_notes).

PHP
// ✅ BENAR (Resource Layer)
public function toArray(Request $request): array 
{
    return [
        'id'          => $this->id,
        'name'        => $this->name,
        'formatted_price' => 'Rp ' . number_format($this->price, 0, ',', '.'),
        'created_at'  => $this->created_at->toISOString(),
    ];
}
4. Error Handling & API Contract Standards
Semua error global (404 Not Found, 422 Unprocessable Entity, 500 Internal Server) dikelola secara tersentralisasi via bootstrap/app.php / Global Exception Handler.

Format Response Standar yang dikirimkan ke Frontend React:

Success Response Standard (200 / 201):
JSON
{
  "message": "Resource retrieved successfully",
  "data": { ... }
}
Error Response Standard (4xx / 5xx):
JSON
{
  "message": "Validation failed / Resource not found",
  "errors": {
    "field_name": [
      "The field_name field is required."
    ]
  }
}
5. Security & Stateless Guidelines
Stateless Authentication: Gunakan Laravel Sanctum berbasis Bearer Token. Jangan gunakan HTTP Session / Cookie Auth berbasis Blade.

CORS Enforcement: Pastikan config/cors.php dikonfigurasi secara terbatas hanya menerima Origin dari domain/port aplikasi Frontend React.

Mass Assignment Protection: Seluruh Model Eloquent WAJIB menyertakan properti $fillable secara eksplisit. Dilarang menggunakan $guarded = [].


Di Laravel 11 dan 12, konfigurasi Exception Handler tidak lagi bertempat di app/Exceptions/Handler.php, melainkan tersentralisasi di dalam file bootstrap/app.php menggunakan method ->withExceptions().

Berikut adalah cara mengonfigurasi Global Exception Handler agar seluruh error (seperti Validation Error, Model Not Found, Authentication Error, hingga Unhandled Server Error) secara otomatis dikonversi ke format JSON yang telah ditentukan.

1. Konfigurasi bootstrap/app.php
Buka file bootstrap/app.php dan sesuaikan blok withExceptions menggunakan kode berikut:

PHP
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Illuminate\Auth\AuthenticationException;
use Throwable;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        
        // Memastikan respons error dikembalikan dalam bentuk JSON jika request dari API/Expecting JSON
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e) {
            return $request->is('api/*') || $request->wantsJson();
        });

        // 1. Handling Validasi Input (Status 422)
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors'  => $e->errors(),
                ], 422);
            }
        });

        // 2. Handling Data Tidak Ditemukan / Route Not Found (Status 404)
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                $message = $e->getPrevious() instanceof ModelNotFoundException
                    ? 'Resource not found.'
                    : 'Endpoint or route not found.';

                return response()->json([
                    'message' => $message,
                    'errors'  => null,
                ], 404);
            }
        });

        // 3. Handling Autentikasi / Token Invalid (Status 401)
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'message' => 'Unauthenticated or invalid token.',
                    'errors'  => null,
                ], 401);
            }
        });

        // 4. Handling Method HTTP Tidak Diizinkan (Status 405)
        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'message' => 'HTTP method is not supported for this route.',
                    'errors'  => null,
                ], 405);
            }
        });

        // 5. Handling Unhandled Internal Server Error (Status 500)
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                // Tampilkan detail pesan error hanya saat mode APP_DEBUG=true
                $message = config('app.debug') 
                    ? $e->getMessage() 
                    : 'Internal server error. Please try again later.';

                return response()->json([
                    'message' => $message,
                    'errors'  => config('app.debug') ? [
                        'exception' => get_class($e),
                        'file'      => $e->getFile(),
                        'line'      => $e->getLine(),
                    ] : null,
                ], 500);
            }
        });

    })->create();
2. Penanganan Custom Business Exception (Opsional)
Jika layer Service Anda melempar Custom Exception untuk aturan bisnis (misalnya stok barang tidak mencukupi atau transaksi tidak valid), buatlah kelas Exception terpisah yang dapat me-render dirinya sendiri.

Buat Custom Exception:
Bash
php artisan make:exception BusinessLogicException
Edit file app/Exceptions/BusinessLogicException.php:
PHP
namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BusinessLogicException extends Exception
{
    protected $code = 400;

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'errors'  => null,
        ], $this->code);
    }
}
Cara Penggunaan di Service Layer:
PHP
use App\Exceptions\BusinessLogicException;

public function reduceStock(int $productId, int $quantity): void
{
    $product = $this->productRepository->findById($productId);

    if ($product->stock < $quantity) {
        throw new BusinessLogicException('Insufficient product stock available.');
    }

    // Lanjutkan proses pengurangan stok...
}
Dengan metode ini, Controller tidak perlu lagi membungkus pemanggilan Service menggunakan try-catch secara berulang karena seluruh error akan langsung ditangkap oleh Global Exception Handler dan dikembalikan ke React dengan format JSON yang konsisten.

Hasil Output Response di Client (React)
A. Contoh Response Error Validasi (422 Unprocessable Entity)
JSON
{
  "message": "The given data was invalid.",
  "errors": {
    "name": [
      "The name field is required."
    ],
    "price": [
      "The price must be at least 0."
    ]
  }
}
B. Contoh Response Resource Not Found (404 Not Found)
JSON
{
  "message": "Resource not found.",
  "errors": null
}