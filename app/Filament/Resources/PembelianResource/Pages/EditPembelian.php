<?php

namespace App\Filament\Resources\PembelianResource\Pages;

use App\Filament\Resources\PembelianResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class EditPembelian extends EditRecord
{
    protected static string $resource = PembelianResource::class;

    /**
     * Sanitasi data sebelum record pembelian di-update.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Buang key pembelianBarang — dihandle via relationship
        unset($data['pembelianBarang']);

        // FileUpload kosong = array [] dari Livewire
        if (is_array($data['bukti_pembayaran'] ?? null) || empty($data['bukti_pembayaran'] ?? null)) {
            $data['bukti_pembayaran'] = $this->getRecord()->bukti_pembayaran;
        }

        return $data;
    }

    /**
     * Setelah save, hitung ulang tagihan dari items yang ada di DB.
     */
    protected function afterSave(): void
    {
        $record = $this->getRecord();

        $totalTagihan = $record->pembelianBarang()
            ->sum(DB::raw('harga_beli * jml'));

        $record->update(['tagihan' => $totalTagihan]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Pembelian berhasil diupdate')
            ->success();
    }
}