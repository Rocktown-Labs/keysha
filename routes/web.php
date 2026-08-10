<?php

use App\Livewire\Activity\ActivityIndex;
use App\Livewire\Device\DeviceApprove;
use App\Livewire\Projects\ProjectIndex;
use App\Livewire\Projects\ProjectShow;
use App\Livewire\Settings\RecoverySettings;
use App\Livewire\Vault\SharedVault;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', ProjectIndex::class)->name('dashboard');
    Route::get('projects', ProjectIndex::class)->name('projects.index');
    Route::get('projects/{slug}', ProjectShow::class)->name('projects.show');

    Route::get('shared-vault', SharedVault::class)->name('vault.shared');
    Route::get('activity', ActivityIndex::class)->name('activity.index');
    Route::get('device', DeviceApprove::class)->name('device.approve');
    Route::get('settings/recovery', RecoverySettings::class)->name('settings.recovery');
});

require __DIR__.'/settings.php';
