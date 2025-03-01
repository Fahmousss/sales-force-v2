<?php

namespace App\Filament\Resources;

use App\Enums\TeamType;
use App\Enums\UserRole;
use App\Filament\Resources\TeamResource\Pages;
use App\Filament\Resources\TeamResource\RelationManagers;
use App\Models\Team;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\TextEntry\TextEntrySize;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group as GroupingGroup;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TeamResource extends Resource
{
    protected static ?string $model = Team::class;

    protected static ?string $tenantOwnershipRelationshipName = 'parent';

    protected static ?string $tenantRelationshipName = 'children';

    public static function isScopedToTenant(): bool
    {
        if (Filament::getTenant()->type !== TeamType::KANTOR_PUSAT) {
            return true;
        }

        return ! auth()->user()->isNotScopedToTenant();
    }

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('key')
                    ->label('Nomor Dirian')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->options(function () {
                        $user = auth()->user();
                        $userTeam = $user && $user->team ? $user->team : null;
                        $options = [];

                        if (!$userTeam || !$userTeam->type) {
                            return []; // Jika tidak ada tim atau tipe, kembalikan opsi kosong
                        }

                        try {
                            // Ambil array string dari allowedChildren()
                            $allowedTypes = TeamType::from($userTeam->type->value)->allowedChildren();
                            // Konversi ke format yang sesuai untuk opsi select
                            foreach ($allowedTypes as $type) {
                                $enumValue = TeamType::from($type);
                                $options[$type] = $enumValue->getLabel();
                            }
                            return $options;
                        } catch (\Exception $e) {
                            return [];
                        }
                    })
                    ->required()
                    ->live()
                    ->native(false),
                Forms\Components\Select::make('parent_key')
                    ->relationship('parent', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('Select Parent Team')
                    ->nullable()
                    ->disabled(fn(Get $get): bool => $get('type') === TeamType::KANTOR_PUSAT->value)
                    ->hidden(Filament::getTenant()->type !== TeamType::KANTOR_PUSAT)
                    ->native(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->groups([
                GroupingGroup::make('parent.name')
                    ->titlePrefixedWithLabel(false),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('key')
                    ->searchable()
                    ->label('Nomor Dirian')
                    ->copyable()
                    ->copyMessage('Copied!')
                    ->fontFamily(FontFamily::Mono)
                    ->copyMessageDuration(1500)
                    ->icon('heroicon-o-clipboard')
                    ->iconPosition(IconPosition::After),
                Tables\Columns\TextColumn::make('parent.name')
                    ->label('Parent')
                    ->badge()
                    ->placeholder('No Parent')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Tables\Actions\DeleteAction $action, Team $record) {
                        self::checkTeamIsDeleteable($record->id, fn() => $action->cancel());
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->action(function (
                            Tables\Actions\DeleteBulkAction $action,
                            Collection $selectedRecords
                        ) {
                            foreach ($selectedRecords as $team) {
                                self::checkTeamIsDeleteable($team->id, fn() => $action->cancel());
                            }
                        }),
                ]),
            ]);
    }



    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make()
                    ->schema(
                        [
                            Split::make([
                                Group::make([
                                    TextEntry::make('name')
                                        ->label('Team Name')
                                        ->size(TextEntry\TextEntrySize::Large)
                                        ->weight(FontWeight::SemiBold)
                                        ->color('info'),
                                    TextEntry::make('id')
                                        ->label('Total Members')
                                        ->formatStateUsing(
                                            fn($record) => ($record->getMemberCount())
                                        ),
                                ]),
                                TextEntry::make('key')
                                    ->label('Nomor Dirian')
                                    ->fontFamily(FontFamily::Mono)
                                    ->copyable()
                                    ->copyMessage('Copied!')
                                    ->copyMessageDuration(1500)
                                    ->icon('heroicon-o-clipboard')
                                    ->iconPosition(IconPosition::After),
                            ]),
                        ]
                    )->columnSpan(2),
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
            'index' => Pages\ListTeams::route('/'),
            'create' => Pages\CreateTeam::route('/create'),
            'view' => Pages\ViewTeam::route('/{record}'),
            'edit' => Pages\EditTeam::route('/{record}/edit'),
        ];
    }

    /**
     * Checks if a team can be deleted and prevents deletion if conditions are not met.
     *
     * This function verifies if a team can be deleted based on the following conditions:
     * 1. The team must not be the current active tenant.
     * 2. The team must not have any associated users (members).
     * 3. The team must not have any child teams.
     *
     * If any of these conditions are violated, a warning notification is displayed, and the deletion process is halted.
     * If a callback function is provided, it will be executed after the notification is sent.
     *
     * @param int $team_id The ID of the team to check.
     * @param callable|null $callback Optional callback function to execute if deletion is denied.
     * @return void
     */
    public static function checkTeamIsDeleteable($team_id, ?callable $callback = null): void
    {
        $team = Team::with(['users', 'children'])->find($team_id);

        if (!$team) {
            Notification::make()
                ->danger()
                ->title('Error!')
                ->body('Team not found.')
                ->send();
            return;
        }

        // 1. Check if the team is the active tenant
        if (Filament::getTenant()->id == $team_id) {
            Notification::make()
                ->warning()
                ->title('Operation denied!')
                ->body('Can\'t delete the current team.')
                ->send();

            if ($callback) {
                $callback();
            }
            return;
        }

        // 2. Check if the team has members
        if ($team->users()->exists()) {
            Notification::make()
                ->warning()
                ->title('Operation denied!')
                ->body('This team has users assigned. Remove them before deleting.')
                ->send();

            if ($callback) {
                $callback();
            }
            return;
        }

        // 3. Check if the team has child teams
        if ($team->children()->exists()) {
            Notification::make()
                ->warning()
                ->title('Operation denied!')
                ->body('This team has sub-teams. Remove them before deleting.')
                ->send();

            if ($callback) {
                $callback();
            }
            return;
        }
    }
}
