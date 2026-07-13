<?php

use App\Http\Controllers\AdopterController;
use App\Http\Controllers\AdoptionController;
use App\Http\Controllers\AdoptionFollowUpController;
use App\Http\Controllers\AdoptionFollowUpStatusController;
use App\Http\Controllers\AdoptionStatusController;
use App\Http\Controllers\AnimalController;
use App\Http\Controllers\AnimalImageController;
use App\Http\Controllers\AnimalMedicationController;
use App\Http\Controllers\AnimalSizeController;
use App\Http\Controllers\AnimalStatusController;
use App\Http\Controllers\AnimalVaccineController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BreedController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\FosterHomeController;
use App\Http\Controllers\FosterPlacementController;
use App\Http\Controllers\HealthRecordController;
use App\Http\Controllers\MedicationController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SpeciesController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SurgeryController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VaccineController;
use App\Http\Controllers\VolunteerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->controller(AuthController::class)->group(function () {
    Route::post('login', 'login')->name('login');
    Route::post('logout', 'logout');
    Route::post('refresh', 'refresh');
    Route::post('me', 'me')->name('me');
});

Route::prefix('users')->middleware('auth')->controller(UserController::class)->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/', 'index')->middleware('check.permissions:users,view');
    Route::get('/{id}', 'show')->middleware('check.permissions:users,view');
    Route::put('/{id}', 'update')->middleware('check.permissions:users,update');
    Route::post('/', 'store')->middleware('check.permissions:users,create');
    Route::put('/{id}/status', 'changeStatus')->middleware('check.permissions:users,update');
    Route::delete('/{id}', 'destroy')->middleware('check.permissions:users,update');
    Route::put('/{id}/change-password', 'changePassword');
});

Route::prefix('roles')->middleware('auth')->controller(RoleController::class)->group(function () {
    Route::get('/getactive', 'getActive');
    Route::get('/', 'index')->middleware('check.permissions:roles,view');
    Route::get('/{id}', 'show')->middleware('check.permissions:roles,view');
    Route::put('/{id}', 'update')->middleware('check.permissions:roles,update');
    Route::post('/', 'store')->middleware('check.permissions:roles,create');
    Route::put('/{id}/status', 'changeStatus')->middleware('check.permissions:roles,update');
    Route::delete('/{id}', 'destroy')->middleware('check.permissions:roles,update');
});

Route::prefix('menus')->middleware('auth')->controller(MenuController::class)->group(function () {
    Route::get('/getbyroles', 'getByRoles');
    Route::get('/getactive', 'getActive');
    Route::get('/', 'index')->middleware('check.permissions:menus,view');
    Route::get('/{id}', 'show')->middleware('check.permissions:menus,view');
    Route::put('/{id}', 'update')->middleware('check.permissions:menus,update');
    Route::post('/', 'store')->middleware('check.permissions:menus,create');
    Route::put('/{id}/status', 'changeStatus')->middleware('check.permissions:menus,update');
    Route::delete('/{id}', 'destroy')->middleware('check.permissions:menus,update');
});

Route::prefix('customers')->middleware('auth')->controller(CustomerController::class)->group(function () {
    Route::get('/', 'index')->middleware('check.permissions:customers,view');
    Route::get('/{id}', 'show')->middleware('check.permissions:customers,view');
    Route::put('/{id}', 'update')->middleware('check.permissions:customers,update');
    Route::post('/', 'store')->middleware('check.permissions:customers,create');
    Route::put('/{id}/status', 'changeStatus')->middleware('check.permissions:customers,update');
    Route::delete('/{id}', 'destroy')->middleware('check.permissions:customers,update');
});

Route::prefix('suppliers')->middleware('auth')->controller(SupplierController::class)->group(function () {
    Route::get('/', 'index')->middleware('check.permissions:suppliers,view');
    Route::get('/{id}', 'show')->middleware('check.permissions:suppliers,view');
    Route::put('/{id}', 'update')->middleware('check.permissions:suppliers,update');
    Route::post('/', 'store')->middleware('check.permissions:suppliers,create');
    Route::put('/{id}/status', 'changeStatus')->middleware('check.permissions:suppliers,update');
    Route::delete('/{id}', 'destroy')->middleware('check.permissions:suppliers,update');
});

/*
|--------------------------------------------------------------------------
| Organizations (tenant management) — Noxus-only
|--------------------------------------------------------------------------
| The /organizations menu is exclusive_noxus with NO role_menu grants, so
| hasPermission() is false for every role and only Noxus (who bypasses
| check.permissions) gets through. The Service is deliberately not org-scoped.
*/
Route::prefix('organizations')->middleware('auth')->controller(OrganizationController::class)->group(function () {
    Route::get('/', 'index')->middleware('check.permissions:organizations,view');
    Route::get('/{id}', 'show')->middleware('check.permissions:organizations,view');
    Route::post('/', 'store')->middleware('check.permissions:organizations,create');
    Route::put('/{id}', 'update')->middleware('check.permissions:organizations,update');
    Route::delete('/{id}', 'destroy')->middleware('check.permissions:organizations,update');
});

/*
|--------------------------------------------------------------------------
| Shelter module — animals (core)
|--------------------------------------------------------------------------
| Organization-scoped in AnimalService (reads filtered by organization_id; org
| set server-side on create). Permission-gated under the "Abrigo" menu group
| (AnimalMenusSeeder). Status changes write an animal_status_history row.
*/
Route::prefix('animals')->middleware('auth')->controller(AnimalController::class)->group(function () {
    Route::get('/', 'index')->middleware('check.permissions:animals,view');
    Route::get('/{id}', 'show')->middleware('check.permissions:animals,view');
    Route::post('/', 'store')->middleware('check.permissions:animals,create');
    Route::put('/{id}', 'update')->middleware('check.permissions:animals,update');
    Route::put('/{id}/status', 'changeStatus')->middleware('check.permissions:animals,update');
    Route::delete('/{id}', 'destroy')->middleware('check.permissions:animals,update');
});

// Animal photos (stored on MinIO/S3, served via presigned URLs). Nested under
// an animal; org scoping is enforced in AnimalImageService via the parent animal.
Route::prefix('animals/{animal}/images')->middleware('auth')->controller(AnimalImageController::class)->group(function () {
    Route::get('/', 'index')->middleware('check.permissions:animals,view');
    Route::post('/', 'store')->middleware('check.permissions:animals,update');
    Route::delete('/{image}', 'destroy')->middleware('check.permissions:animals,update');
});

/*
|--------------------------------------------------------------------------
| Shelter module — adoptions (Module 3)
|--------------------------------------------------------------------------
| Organization-scoped in the Services (reads filtered by organization_id; org
| set server-side on create). Adopters are org data; adoptions carry the
| adopt/return/cancel workflow (the "one active adoption per animal" rule,
| ADR-0002) and sync the animal's status (+ animal_status_history). Follow-ups
| are nested under an adoption. Permission-gated under the "Abrigo" menu group.
*/
Route::prefix('adopters')->middleware('auth')->controller(AdopterController::class)->group(function () {
    Route::get('/', 'index')->middleware('check.permissions:adopters,view');
    Route::get('/{id}', 'show')->middleware('check.permissions:adopters,view');
    Route::post('/', 'store')->middleware('check.permissions:adopters,create');
    Route::put('/{id}', 'update')->middleware('check.permissions:adopters,update');
    Route::delete('/{id}', 'destroy')->middleware('check.permissions:adopters,update');
});

Route::prefix('adoptions')->middleware('auth')->controller(AdoptionController::class)->group(function () {
    Route::get('/', 'index')->middleware('check.permissions:adoptions,view');
    Route::get('/{id}', 'show')->middleware('check.permissions:adoptions,view');
    Route::post('/', 'store')->middleware('check.permissions:adoptions,create');
    Route::put('/{id}', 'update')->middleware('check.permissions:adoptions,update');
    Route::put('/{id}/return', 'return')->middleware('check.permissions:adoptions,update');
    Route::put('/{id}/cancel', 'cancel')->middleware('check.permissions:adoptions,update');
    Route::delete('/{id}', 'destroy')->middleware('check.permissions:adoptions,update');
});

// Post-adoption follow-ups, nested under an adoption; org scoping is enforced in
// AdoptionFollowUpService via the parent adoption.
Route::prefix('adoptions/{adoption}/follow-ups')->middleware('auth')->controller(AdoptionFollowUpController::class)->group(function () {
    Route::get('/', 'index')->middleware('check.permissions:adoptions,view');
    Route::post('/', 'store')->middleware('check.permissions:adoptions,update');
    Route::put('/{followUp}', 'update')->middleware('check.permissions:adoptions,update');
    Route::delete('/{followUp}', 'destroy')->middleware('check.permissions:adoptions,update');
});

// Adoption status lookups (read-only): system defaults + the org's own, consumed
// by the adoption / follow-up forms.
Route::prefix('adoption-statuses')->middleware('auth')->controller(AdoptionStatusController::class)->group(function () {
    Route::get('/', 'index')->middleware('check.permissions:adoptions,view');
});

Route::prefix('adoption-follow-up-statuses')->middleware('auth')->controller(AdoptionFollowUpStatusController::class)->group(function () {
    Route::get('/', 'index')->middleware('check.permissions:adoptions,view');
});

/*
|--------------------------------------------------------------------------
| Shelter module — health (Module 4)
|--------------------------------------------------------------------------
| Clinical history nested under an animal, like animal images: org scoping is
| enforced in each Service via the parent animal, and the routes reuse the
| `animals` permission grant (view to read, update to write) — no separate
| menu entry. vaccines/medications at the bottom are the global reference
| catalogs (read-only, shared by all orgs, like species) that the applied-dose
| and course forms consume.
*/
Route::prefix('animals/{animal}/health-records')->middleware('auth')->controller(HealthRecordController::class)->group(function () {
    Route::get('/', 'index')->middleware('check.permissions:animals,view');
    Route::post('/', 'store')->middleware('check.permissions:animals,update');
    Route::put('/{healthRecord}', 'update')->middleware('check.permissions:animals,update');
    Route::delete('/{healthRecord}', 'destroy')->middleware('check.permissions:animals,update');
});

// Applied vaccine doses (animal_vaccines).
Route::prefix('animals/{animal}/vaccines')->middleware('auth')->controller(AnimalVaccineController::class)->group(function () {
    Route::get('/', 'index')->middleware('check.permissions:animals,view');
    Route::post('/', 'store')->middleware('check.permissions:animals,update');
    Route::put('/{vaccine}', 'update')->middleware('check.permissions:animals,update');
    Route::delete('/{vaccine}', 'destroy')->middleware('check.permissions:animals,update');
});

// Medication courses (animal_medications).
Route::prefix('animals/{animal}/medications')->middleware('auth')->controller(AnimalMedicationController::class)->group(function () {
    Route::get('/', 'index')->middleware('check.permissions:animals,view');
    Route::post('/', 'store')->middleware('check.permissions:animals,update');
    Route::put('/{medication}', 'update')->middleware('check.permissions:animals,update');
    Route::delete('/{medication}', 'destroy')->middleware('check.permissions:animals,update');
});

Route::prefix('animals/{animal}/appointments')->middleware('auth')->controller(AppointmentController::class)->group(function () {
    Route::get('/', 'index')->middleware('check.permissions:animals,view');
    Route::post('/', 'store')->middleware('check.permissions:animals,update');
    Route::put('/{appointment}', 'update')->middleware('check.permissions:animals,update');
    Route::delete('/{appointment}', 'destroy')->middleware('check.permissions:animals,update');
});

// Surgeries keep animals.neutered/neuter_date in sync when a castration is
// recorded (is_castration flag, handled in SurgeryService).
Route::prefix('animals/{animal}/surgeries')->middleware('auth')->controller(SurgeryController::class)->group(function () {
    Route::get('/', 'index')->middleware('check.permissions:animals,view');
    Route::post('/', 'store')->middleware('check.permissions:animals,update');
    Route::put('/{surgery}', 'update')->middleware('check.permissions:animals,update');
    Route::delete('/{surgery}', 'destroy')->middleware('check.permissions:animals,update');
});

// Global reference catalogs (read-only, shared by all orgs — like species).
Route::prefix('vaccines')->middleware('auth')->controller(VaccineController::class)->group(function () {
    Route::get('/', 'index');
});

Route::prefix('medications')->middleware('auth')->controller(MedicationController::class)->group(function () {
    Route::get('/', 'index');
});

/*
|--------------------------------------------------------------------------
| Shelter module — fostering & volunteers (Module 5)
|--------------------------------------------------------------------------
| Volunteers and foster homes are organization-scoped operational data
| (suppliers pattern, org set server-side on create). Placements (stays) are
| nested under their foster home like adoption follow-ups: org scoping is
| enforced in the Service via the org-scoped parent home, and the routes reuse
| the `foster-homes` grant. Opening/closing a stay keeps the animal's status in
| sync (Em Lar Temporário / Disponível) through AnimalService.
*/
Route::prefix('volunteers')->middleware('auth')->controller(VolunteerController::class)->group(function () {
    Route::get('/', 'index')->middleware('check.permissions:volunteers,view');
    Route::get('/{id}', 'show')->middleware('check.permissions:volunteers,view');
    Route::post('/', 'store')->middleware('check.permissions:volunteers,create');
    Route::put('/{id}', 'update')->middleware('check.permissions:volunteers,update');
    Route::put('/{id}/status', 'changeStatus')->middleware('check.permissions:volunteers,update');
    Route::delete('/{id}', 'destroy')->middleware('check.permissions:volunteers,update');
});

Route::prefix('foster-homes')->middleware('auth')->controller(FosterHomeController::class)->group(function () {
    Route::get('/', 'index')->middleware('check.permissions:foster-homes,view');
    Route::get('/{id}', 'show')->middleware('check.permissions:foster-homes,view');
    Route::post('/', 'store')->middleware('check.permissions:foster-homes,create');
    Route::put('/{id}', 'update')->middleware('check.permissions:foster-homes,update');
    Route::delete('/{id}', 'destroy')->middleware('check.permissions:foster-homes,update');
});

// Foster stays (animal_foster_homes), nested under their foster home.
Route::prefix('foster-homes/{fosterHome}/placements')->middleware('auth')->controller(FosterPlacementController::class)->group(function () {
    Route::get('/', 'index')->middleware('check.permissions:foster-homes,view');
    Route::post('/', 'store')->middleware('check.permissions:foster-homes,update');
    Route::put('/{placement}', 'update')->middleware('check.permissions:foster-homes,update');
    Route::put('/{placement}/close', 'close')->middleware('check.permissions:foster-homes,update');
    Route::delete('/{placement}', 'destroy')->middleware('check.permissions:foster-homes,update');
});

/*
|--------------------------------------------------------------------------
| Shelter module — animal reference data (lookups)
|--------------------------------------------------------------------------
| species is global reference data (read-only, shared by all orgs).
| breeds / animal-sizes / animal-statuses / tags are Noxus-managed global
| reference lookups (ADR-0008), fully permission-gated via `check.permissions`.
| Every role is granted `view` (ShelterMenusSeeder) so the data is readable
| across the system (e.g. the Animal form); `create`/`update` are granted to no
| role, so only Noxus (who bypasses the check) manages them.
*/
Route::prefix('species')->middleware('auth')->controller(SpeciesController::class)->group(function () {
    Route::get('/', 'index');
});

Route::prefix('breeds')->middleware('auth')->controller(BreedController::class)->group(function () {
    Route::get('/', 'index')->middleware('check.permissions:breeds,view');
    Route::get('/{id}', 'show')->middleware('check.permissions:breeds,view');
    Route::post('/', 'store')->middleware('check.permissions:breeds,create');
    Route::put('/{id}', 'update')->middleware('check.permissions:breeds,update');
    Route::delete('/{id}', 'destroy')->middleware('check.permissions:breeds,update');
});

Route::prefix('animal-sizes')->middleware('auth')->controller(AnimalSizeController::class)->group(function () {
    Route::get('/', 'index')->middleware('check.permissions:animal-sizes,view');
    Route::get('/{id}', 'show')->middleware('check.permissions:animal-sizes,view');
    Route::post('/', 'store')->middleware('check.permissions:animal-sizes,create');
    Route::put('/{id}', 'update')->middleware('check.permissions:animal-sizes,update');
    Route::delete('/{id}', 'destroy')->middleware('check.permissions:animal-sizes,update');
});

Route::prefix('animal-statuses')->middleware('auth')->controller(AnimalStatusController::class)->group(function () {
    Route::get('/', 'index')->middleware('check.permissions:animal-statuses,view');
    Route::get('/{id}', 'show')->middleware('check.permissions:animal-statuses,view');
    Route::post('/', 'store')->middleware('check.permissions:animal-statuses,create');
    Route::put('/{id}', 'update')->middleware('check.permissions:animal-statuses,update');
    Route::delete('/{id}', 'destroy')->middleware('check.permissions:animal-statuses,update');
});

Route::prefix('tags')->middleware('auth')->controller(TagController::class)->group(function () {
    Route::get('/', 'index')->middleware('check.permissions:tags,view');
    Route::get('/{id}', 'show')->middleware('check.permissions:tags,view');
    Route::post('/', 'store')->middleware('check.permissions:tags,create');
    Route::put('/{id}', 'update')->middleware('check.permissions:tags,update');
    Route::delete('/{id}', 'destroy')->middleware('check.permissions:tags,update');
});

Route::prefix('live-code')->controller(MenuController::class)->group(function () {
    Route::get('/', 'index');
});
