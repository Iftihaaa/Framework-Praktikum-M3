<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PembelianResource\Pages;
use App\Models\Pembelian;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

// tambahan
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Filters\SelectFilter;

// model
use App\Models\Penjual;
use App\Models\Barang;
use App\Models\PembelianBarang;

// DB
use Illuminate\Support\Facades\DB;

class PembelianResource extends Resource
{
    protected static ?string $model = Pembelian::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationLabel = 'Pembelian';

    protected static ?string $navigationGroup = 'Transaksi';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([

                    // =====================
                    // Step 1: Data Faktur
                    // =====================
                    Wizard\Step::make('Pesanan')
                        ->schema([
                            Forms\Components\Section::make('Faktur')
                                ->icon('heroicon-m-document-duplicate')
                                ->schema([
                                    TextInput::make('no_faktur')
                                        ->default(fn () => Pembelian::getKodeFaktur())
                                        ->label('Nomor Faktur')
                                        ->required()
                                        ->readonly(),
                                    DateTimePicker::make('tgl')
                                        ->default(now()),
                                    Select::make('penjual_id')
                                        ->label('Penjual')
                                        ->options(Penjual::pluck('nama_penjual', 'id')->toArray())
                                        ->required()
                                        ->placeholder('Pilih Penjual'),
                                    TextInput::make('tagihan')
                                        ->default(0)
                                        ->hidden(),
                                ])
                                ->collapsible()
                                ->columns(2),
                        ]),

                    // =====================
                    // Step 2: Pilih Barang
                    // =====================
                    Wizard\Step::make('Pilih Barang')
                        ->schema([
                            Repeater::make('pembelianBarang')
                                ->relationship('pembelianBarang')
                                ->label('Daftar Barang')
                                ->schema([

                                    // Toggle barang lama/baru
                                    // disembunyikan saat Edit/View karena data dari DB = barang lama
                                    Select::make('is_barang_baru')
                                        ->label('Tipe Barang')
                                        ->options([
                                            '0' => 'Barang Lama',
                                            '1' => 'Barang Baru',
                                        ])
                                        ->default('0')
                                        ->live()
                                        ->dehydrated(true)
                                        ->hidden(fn ($livewire) => !($livewire instanceof \App\Filament\Resources\PembelianResource\Pages\CreatePembelian))
                                        ->columnSpan(['md' => 2]),

                                    // === BARANG LAMA ===
                                    Select::make('barang_id')
                                        ->label('Pilih Barang')
                                        ->options(Barang::pluck('nama_barang', 'id')->toArray())
                                        ->required(fn ($get) => $get('is_barang_baru') !== '1')
                                        ->hidden(fn ($get) => $get('is_barang_baru') === '1')
                                        ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                        ->live()
                                        ->placeholder('Pilih Barang')
                                        ->afterStateUpdated(function ($state, $set) {
                                            $barang = Barang::find($state);
                                            $set('harga_beli', $barang ? $barang->harga_barang : 0);
                                            $set('harga_jual', $barang ? $barang->harga_barang * 1.2 : 0);
                                        })
                                        ->searchable()
                                        ->columnSpan(['md' => 2]),

                                    // === BARANG BARU ===
                                    TextInput::make('nama_barang_baru')
                                        ->label('Nama Barang Baru')
                                        ->placeholder('Masukkan nama barang baru')
                                        ->hidden(fn ($get) => $get('is_barang_baru') !== '1')
                                        ->required(fn ($get) => $get('is_barang_baru') === '1')
                                        ->dehydrated(true)
                                        ->columnSpan(['md' => 2]),

                                    TextInput::make('rating_baru')
                                        ->label('Rating (0-5)')
                                        ->numeric()
                                        ->default(0)
                                        ->minValue(0)
                                        ->maxValue(5)
                                        ->hidden(fn ($get) => $get('is_barang_baru') !== '1')
                                        ->dehydrated(true)
                                        ->columnSpan(['md' => 1]),

                                    // === HARGA & JUMLAH ===
                                    TextInput::make('harga_beli')
                                        ->label('Harga Beli')
                                        ->numeric()
                                        ->live()
                                        ->afterStateUpdated(function ($state, $set) {
                                            $set('harga_jual', $state ? $state * 1.2 : 0);
                                        })
                                        ->required()
                                        ->columnSpan(['md' => 1]),

                                    TextInput::make('harga_jual')
                                        ->label('Harga Jual')
                                        ->numeric()
                                        ->readonly()
                                        ->columnSpan(['md' => 1]),

                                    TextInput::make('jml')
                                        ->label('Jumlah')
                                        ->default(1)
                                        ->numeric()
                                        ->required()
                                        ->columnSpan(['md' => 1]),

                                    DatePicker::make('tgl')
                                        ->label('Tanggal')
                                        ->default(today())
                                        ->required()
                                        ->columnSpan(['md' => 1]),
                                ])
                                ->columns(['md' => 6])
                                ->addable(fn ($livewire) => $livewire instanceof \App\Filament\Resources\PembelianResource\Pages\CreatePembelian)
                                ->deletable(fn ($livewire) => $livewire instanceof \App\Filament\Resources\PembelianResource\Pages\CreatePembelian)
                                ->reorderable(false)
                                ->createItemButtonLabel('Tambah Barang')
                                ->minItems(1)
                                ->required()
                                // Dipanggil sebelum INSERT (Create)
                                ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                                    if (($data['is_barang_baru'] ?? '0') === '1') {
                                        $barangBaru = Barang::create([
                                            'kode_barang'  => Barang::getKodeBarang(),
                                            'nama_barang'  => $data['nama_barang_baru'] ?? 'Barang Baru',
                                            'harga_barang' => $data['harga_beli'],
                                            'stok'         => $data['jml'],
                                            'rating'       => $data['rating_baru'] ?? 0,
                                            'foto'         => 'default.png',
                                        ]);
                                        $data['barang_id'] = $barangBaru->id;
                                    } else {
                                        if (!empty($data['barang_id'])) {
                                            $barang = Barang::find($data['barang_id']);
                                            if ($barang) {
                                                $barang->increment('stok', $data['jml']);
                                            }
                                        }
                                    }

                                    unset($data['is_barang_baru'], $data['nama_barang_baru'], $data['rating_baru']);

                                    return $data;
                                })
                                // Dipanggil sebelum UPDATE (Edit) — cukup buang field virtual
                                ->mutateRelationshipDataBeforeSaveUsing(function (array $data): array {
                                    unset($data['is_barang_baru'], $data['nama_barang_baru'], $data['rating_baru']);

                                    // Pastikan barang_id ada (wajib di tabel)
                                    if (empty($data['barang_id'])) {
                                        throw new \Exception('barang_id tidak boleh kosong saat update.');
                                    }

                                    return $data;
                                }),
                        ]),

                    // ============================
                    // Step 3: Status Pembayaran
                    // ============================
                    Wizard\Step::make('Status Pembayaran')
                        ->schema([
                            Forms\Components\Section::make('Status Pembayaran')
                                ->icon('heroicon-m-banknotes')
                                ->schema([
                                    Select::make('status')
                                        ->label('Status Pembayaran')
                                        ->options([
                                            'lunas'  => 'Lunas',
                                            'hutang' => 'Hutang',
                                        ])
                                        ->default('lunas')
                                        ->required(),
                                    FileUpload::make('bukti_pembayaran')
                                        ->label('Upload Bukti Pembayaran')
                                        ->acceptedFileTypes(['image/*'])
                                        ->directory('bukti-pembayaran')
                                        ->maxSize(2048)
                                        ->image()
                                        ->columnSpan(['md' => 2]),
                                    Placeholder::make('total_tagihan')
                                        ->label('Total Tagihan')
                                        ->content(function ($get) {
                                            $items = $get('pembelianBarang') ?? [];
                                            $total = collect($items)->sum(fn ($item) =>
                                                (float)($item['harga_beli'] ?? 0) * (int)($item['jml'] ?? 0)
                                            );
                                            return rupiah($total);
                                        }),
                                ])
                                ->columns(2),
                        ]),

                ])->columnSpan(3)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no_faktur')->label('No Faktur')->searchable(),
                TextColumn::make('penjual.nama_penjual')
                    ->label('Nama Penjual')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'lunas'  => 'success',
                        'hutang' => 'danger',
                    })
                    ->url(fn ($record): string => $record->status === 'hutang'
                        ? route('filament.admin.resources.pembelians.status', $record)
                        : route('filament.admin.resources.pembelians.edit', $record))
                    ->tooltip('Klik untuk ubah status'),
                TextColumn::make('tagihan')
                    ->formatStateUsing(fn (string|int|null $state): string => rupiah($state))
                    ->sortable()
                    ->alignment('end'),
                TextColumn::make('created_at')->label('Tanggal')->dateTime(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Filter Status')
                    ->options([
                        'lunas'  => 'Lunas',
                        'hutang' => 'Hutang',
                    ])
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPembelians::route('/'),
            'create' => Pages\CreatePembelian::route('/create'),
            'edit'   => Pages\EditPembelian::route('/{record}/edit'),
            'status' => Pages\UbahStatusPembelian::route('/{record}/status'),
        ];
    }
}