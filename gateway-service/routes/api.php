use App\Http\Controllers\GatewayController;

Route::get('/gateway/siswa/{id}', [GatewayController::class, 'siswaDetail']);
