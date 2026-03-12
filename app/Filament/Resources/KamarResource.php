<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KamarResource\Pages;
use App\Filament\Resources\KamarResource\RelationManagers;
use App\Models\Kamar;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

// tambahan
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload; //untuk tipe file

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;

class KamarResource extends Resource
{
    protected static ?string $model = Kamar::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
                //
                TextInput::make('no_kamar')
                    ->default(fn () => Kamar::getNoKamar()) // Ambil default dari method getNoKamar
                    ->label('No. Kamar')
                    ->required()
                    ->readonly() // Membuat field menjadi read-only
                ,
                TextInput::make('nama_kamar')
                    ->required()
                    ->placeholder('Masukkan nama kamar') // Placeholder untuk membantu pengguna
                ,
                TextInput::make('lantai_kamar')
                    ->required()
                    ->placeholder('Masukkan lantai kamar') // Placeholder untuk membantu pengguna
                    ->minValue(0)
                ,
                FileUpload::make('foto_kamar')
                    ->directory('foto_kamar')
                    ->required()
                ,
                TextInput::make('harga_kamar')
                    ->required()
                    ->minValue(0) // Nilai minimal 0 (opsional jika tidak ingin ada harga negatif)
                    ->reactive() // Menjadikan input reaktif terhadap perubahan
                    ->extraAttributes(['id' => 'harga-kamar']) // Tambahkan ID untuk pengikatan JavaScript
                    ->placeholder('Masukkan harga kamar') // Placeholder untuk membantu pengguna
                    ->live()
                    ->afterStateUpdated(fn ($state, callable $set) => 
                        $set('harga_kamar', number_format((int) str_replace('.', '', $state), 0, ',', '.'))
                      )
                ,
                TextInput::make('status')
                    ->required()
                    ->placeholder('Masukkan status kamar') // Placeholder untuk membantu pengguna
                    ->minValue(0)
                ,
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
                //
                TextColumn::make('no_kamar')
                    ->searchable(),
                // agar bisa di search
                TextColumn::make('nama_kamar')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('lantai_kamar')
                    ->label('Lantai Kamar')
                    ->searchable()
                    ->sortable(),
                ImageColumn::make('foto_kamar')
                    ->label('Foto Kamar')
                    ->square() // Menampilkan gambar dalam bentuk persegi
                    ->disk('public') // Menentukan disk penyimpanan (sesuaikan dengan konfigurasi filesystem Anda)
                ,
                TextColumn::make('harga_kamar')
                    ->label('Harga Kamar')
                    ->formatStateUsing(fn (string|int|null $state): string => rupiah($state))
                    ->extraAttributes(['class' => 'text-right']) // Tambahkan kelas CSS untuk rata kanan
                    ->sortable()
                ,
                TextColumn::make('status'),
            ])
            ->filters([
                //
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKamars::route('/'),
            'create' => Pages\CreateKamar::route('/create'),
            'edit' => Pages\EditKamar::route('/{record}/edit'),
        ];
    }
}
