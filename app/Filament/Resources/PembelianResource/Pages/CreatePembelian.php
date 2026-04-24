<?php

namespace App\Filament\Resources\PembelianResource\Pages;

use App\Filament\Resources\PembelianResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class CreatePembelian extends CreateRecord
{
    protected static string $resource = PembelianResource::class;

    /**
     * Sanitasi data sebelum record pembelian dibuat.
     * Buang field yang tidak ada di tabel pembelian.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // pembelianBarang dihandle otomatis via relationship
        unset($data['pembelianBarang']);

        // FileUpload kosong = array [] dari Livewire
        if (is_array($data['bukti_pembayaran'] ?? null) || empty($data['bukti_pembayaran'] ?? null)) {
            $data['bukti_pembayaran'] = null;
        }

        $data['tagihan'] = 0;

        return $data;
    }

    /**
     * Setelah semua items tersimpan via relationship,
     * hitung total tagihan dan update kolom tagihan.
     */
    protected function afterCreate(): void
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

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Pembelian berhasil disimpan')
            ->success();
    }
}