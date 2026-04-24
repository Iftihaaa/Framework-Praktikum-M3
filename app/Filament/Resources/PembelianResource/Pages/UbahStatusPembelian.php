<?php

namespace App\Filament\Resources\PembelianResource\Pages;

use App\Filament\Resources\PembelianResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;

class UbahStatusPembelian extends EditRecord
{
    protected static string $resource = PembelianResource::class;

    public ?string $previousUrl = null;

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->previousUrl = url()->previous();
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    protected function getSaveFormAction(): Action
    {
        return Action::make('save')
            ->label('Simpan')
            ->submit('save')
            ->keyBindings(['mod+s']);
    }

    protected function getCancelFormAction(): Action
    {
        return Action::make('cancel')
            ->label('Batal')
            ->url($this->previousUrl ?? static::getResource()::getUrl())
            ->color('gray');
    }

    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        Select::make('status')
                            ->label('Status Pembayaran')
                            ->options([
                                'lunas' => 'Lunas',
                                'hutang' => 'Hutang',
                            ])
                            ->required(),
                        FileUpload::make('bukti_pembayaran')
                            ->label('Upload Bukti Pembayaran')
                            ->acceptedFileTypes(['image/*'])
                            ->directory('bukti-pembayaran')
                            ->maxSize(2048)
                            ->image(),
                    ])
                    ->model($this->getRecord())
                    ->statePath($this->getFormStatePath())
                    ->operation('edit')
                    ->columns(1)
            ),
        ];
    }
}
