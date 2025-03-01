<?php

namespace App\Filament\Resources;

use App\Enums\TeamType;
use App\Enums\UserRole;
use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\TextEntry\TextEntrySize;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'User & Role Management';

    public static function isScopedToTenant(): bool
    {
        return ! auth()->user()->isNotScopedToTenant();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('User'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->unique(ignoreRecord: true)
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->dehydrated(
                                fn($state) => filled($state)
                            )
                            ->required(
                                fn(string $context): bool => $context === 'create'
                            )
                            ->maxLength(255),
                        Forms\Components\Select::make('roles')
                            ->relationship('roles', 'name', function ($query) {
                                return $query
                                    ->whereNot('name', UserRole::SUPER_ADMIN);
                            })
                            ->getOptionLabelFromRecordUsing(
                                fn(Model $record): string => Str::headline($record->name)
                            )
                            ->multiple()
                            ->preload()
                            ->searchable(),
                    ])->columns(2),
                Forms\Components\Section::make(__('Team'))
                    ->description('Selecting Multi Team will allow you to assign the user to a team.')
                    ->schema([
                        Forms\Components\Select::make('team_id')
                            ->label(('Assign to team'))
                            ->relationship('team', 'name')
                            ->options(
                                fn() => auth()
                                    ->user()
                                    ->team
                                    ->getDescendants()
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                    ])

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                function (Builder $query) {
                    $user = auth()->user();

                    $query->whereNot('id', $user->id) // Sembunyikan user yang sedang login
                        ->whereHas('roles', function ($roleQuery) {
                            $roleQuery->whereNot('name', UserRole::SUPER_ADMIN); // Sembunyikan SUPER_ADMIN
                        });

                    // Jika user memiliki peran ADMIN, hanya tampilkan user dari tim di bawahnya
                    if ($user->hasRole(UserRole::ADMIN)) {
                        $descendantTeamIds = $user->team->getDescendants()->pluck('id');
                        $query->whereIn('team_id', $descendantTeamIds);
                    }
                }
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email'),
                Tables\Columns\TextColumn::make('team.name')
                    ->label('Team')
                    ->badge()
                    ->colors(['primary'])
                    ->searchable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->badge()
                    ->label(('Role'))
                    ->formatStateUsing(fn($state): string => Str::headline($state))
                    ->colors(['primary'])
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('User information')
                    ->schema([
                        Split::make([
                            Group::make([
                                TextEntry::make('name')
                                    ->size(TextEntrySize::Large)
                                    ->weight(FontWeight::Bold),

                                TextEntry::make('email')
                                    ->label('Email Address')
                                    ->copyable()
                                    ->icon('heroicon-o-envelope')
                            ])->columnSpan(2),

                            Group::make([
                                TextEntry::make('roles.name')
                                    ->badge(),

                                TextEntry::make('team.name')
                            ])
                        ])
                    ])->columnSpan(2),

                Section::make('Additional Details')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Registered On')
                            ->dateTime('d M Y, H:i'),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
