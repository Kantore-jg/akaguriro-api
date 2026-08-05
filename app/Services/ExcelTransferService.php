<?php

namespace App\Services;

use App\Enums\PlaceStatus;
use App\Enums\UserRole;
use App\Models\Market;
use App\Models\MarketBlock;
use App\Models\Place;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

class ExcelTransferService
{
    private const SHEET_CATEGORIES = 'categories';
    private const SHEET_MARKETS = 'markets';
    private const SHEET_BLOCKS = 'blocks';
    private const SHEET_USERS = 'users';
    private const SHEET_PLACES = 'places';
    private const SHEET_PRODUCTS = 'products';

    public function __construct(
        private MarketService $marketService,
        private ProductCategoryService $productCategoryService,
        private UserService $userService,
        private PlaceService $placeService,
    ) {}

    public function exportWorkbook(User $actor): array
    {
        $scope = $this->scopeFor($actor);
        $data = $this->collectExportData($scope);

        return $this->buildWorkbook(
            sprintf('akaguriro-export-%s.xlsx', now()->format('Y-m-d-His')),
            [
                self::SHEET_CATEGORIES => [
                    'headers' => ['name', 'slug', 'description', 'is_active', 'parent_slug'],
                    'rows' => $data['categories'],
                ],
                self::SHEET_MARKETS => [
                    'headers' => ['name', 'slug', 'province', 'commune', 'zone', 'colline', 'city', 'location', 'description', 'total_places', 'is_active', 'latitude', 'longitude', 'category_slugs'],
                    'rows' => $data['markets'],
                ],
                self::SHEET_BLOCKS => [
                    'headers' => ['market_slug', 'name', 'code', 'description', 'total_places', 'is_active'],
                    'rows' => $data['blocks'],
                ],
                self::SHEET_USERS => [
                    'headers' => ['name', 'email', 'phone', 'role', 'managed_market_slug', 'password', 'is_active'],
                    'rows' => $data['users'],
                ],
                self::SHEET_PLACES => [
                    'headers' => ['market_slug', 'block_code', 'number', 'status', 'category_slugs', 'chief_email', 'latitude', 'longitude'],
                    'rows' => $data['places'],
                ],
                self::SHEET_PRODUCTS => [
                    'headers' => ['name', 'slug', 'market_slug', 'place_number', 'merchant_email', 'category_slug', 'description', 'price', 'unit', 'stock', 'available', 'is_trending'],
                    'rows' => $data['products'],
                ],
            ],
        );
    }

    public function exportTemplate(User $actor): array
    {
        $this->scopeFor($actor);

        return $this->buildWorkbook(
            sprintf('akaguriro-modele-import-%s.xlsx', now()->format('Y-m-d-His')),
            [
                self::SHEET_CATEGORIES => [
                    'headers' => ['name', 'slug', 'description', 'is_active', 'parent_slug'],
                    'rows' => [],
                ],
                self::SHEET_MARKETS => [
                    'headers' => ['name', 'slug', 'province', 'commune', 'zone', 'colline', 'city', 'location', 'description', 'total_places', 'is_active', 'latitude', 'longitude', 'category_slugs'],
                    'rows' => [],
                ],
                self::SHEET_BLOCKS => [
                    'headers' => ['market_slug', 'name', 'code', 'description', 'total_places', 'is_active'],
                    'rows' => [],
                ],
                self::SHEET_USERS => [
                    'headers' => ['name', 'email', 'phone', 'role', 'managed_market_slug', 'password', 'is_active'],
                    'rows' => [],
                ],
                self::SHEET_PLACES => [
                    'headers' => ['market_slug', 'block_code', 'number', 'status', 'category_slugs', 'chief_email', 'latitude', 'longitude'],
                    'rows' => [],
                ],
                self::SHEET_PRODUCTS => [
                    'headers' => ['name', 'slug', 'market_slug', 'place_number', 'merchant_email', 'category_slug', 'description', 'price', 'unit', 'stock', 'available', 'is_trending'],
                    'rows' => [],
                ],
            ],
        );
    }

    public function importWorkbook(UploadedFile $file, User $actor): array
    {
        $scope = $this->scopeFor($actor);
        $spreadsheet = IOFactory::load($file->getRealPath());
        $errors = [];
        $summary = $this->blankSummary();

        DB::transaction(function () use ($spreadsheet, $actor, $scope, &$summary, &$errors): void {
            $categoryRows = $this->sheetRows($spreadsheet, self::SHEET_CATEGORIES);
            $marketRows = $this->sheetRows($spreadsheet, self::SHEET_MARKETS);
            $blockRows = $this->sheetRows($spreadsheet, self::SHEET_BLOCKS);
            $userRows = $this->sheetRows($spreadsheet, self::SHEET_USERS);
            $placeRows = $this->sheetRows($spreadsheet, self::SHEET_PLACES);
            $productRows = $this->sheetRows($spreadsheet, self::SHEET_PRODUCTS);

            $this->importCategories($categoryRows, $summary, $errors);
            $this->importMarkets($marketRows, $scope, $summary, $errors);
            $this->importBlocks($blockRows, $scope, $summary, $errors);
            $this->importUsers($userRows, $actor, $scope, $summary, $errors);
            $this->importPlaces($placeRows, $actor, $scope, $summary, $errors);
            $this->importProducts($productRows, $actor, $scope, $summary, $errors);

            if (! empty($errors)) {
                throw ValidationException::withMessages([
                    'file' => $errors,
                ]);
            }
        });

        return $summary;
    }

    private function blankSummary(): array
    {
        return [
            'categories' => ['created' => 0, 'updated' => 0],
            'markets' => ['created' => 0, 'updated' => 0, 'skipped' => 0],
            'blocks' => ['created' => 0, 'updated' => 0, 'skipped' => 0],
            'users' => ['created' => 0, 'updated' => 0, 'skipped' => 0],
            'places' => ['created' => 0, 'updated' => 0, 'skipped' => 0],
            'products' => ['created' => 0, 'updated' => 0, 'skipped' => 0],
        ];
    }

    private function scopeFor(User $actor): array
    {
        if ($actor->hasRole(UserRole::SuperAdmin->value)) {
            return [
                'market_ids' => null,
                'is_super_admin' => true,
            ];
        }

        if ($actor->hasRole(UserRole::AdminMarche->value) && $actor->managed_market_id) {
            return [
                'market_ids' => [(int) $actor->managed_market_id],
                'is_super_admin' => false,
            ];
        }

        throw ValidationException::withMessages([
            'user' => ['Vous n\'êtes pas autorisé à importer ou exporter les données Excel.'],
        ]);
    }

    private function collectExportData(array $scope): array
    {
        $marketIds = $scope['market_ids'] ?? [];
        $isSuperAdmin = $scope['is_super_admin'];

        $categoryQuery = ProductCategory::query()->with('parent')->orderBy('name');
        $marketQuery = Market::query()->with('productCategories')->orderBy('name');
        $blockQuery = MarketBlock::query()->with('market')->orderBy('name');
        $userQuery = User::query()->with(['roles', 'managedMarket', 'chiefPlaces.market', 'products.market'])->orderBy('name');
        $placeQuery = Place::query()->with(['market', 'block', 'chief'])->orderBy('number');
        $productQuery = Product::query()->with(['market', 'place', 'merchant', 'category'])->orderBy('name');

        if (! $isSuperAdmin) {
            $marketQuery->whereIn('id', $marketIds);
            $blockQuery->whereIn('market_id', $marketIds);
            $placeQuery->whereIn('market_id', $marketIds);
            $productQuery->whereIn('market_id', $marketIds);
            $userQuery->where(function ($query) use ($marketIds) {
                $query->whereIn('managed_market_id', $marketIds)
                    ->orWhereHas('chiefPlaces', fn ($p) => $p->whereIn('market_id', $marketIds))
                    ->orWhereHas('products', fn ($p) => $p->whereIn('market_id', $marketIds));
            });
        }

        return [
            'categories' => $categoryQuery->get()->map(fn (ProductCategory $category) => [
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'is_active' => $category->is_active ? '1' : '0',
                'parent_slug' => $category->parent?->slug,
            ])->all(),
            'markets' => $marketQuery->get()->map(function (Market $market) {
                return [
                    'name' => $market->name,
                    'slug' => $market->slug,
                    'province' => $market->province,
                    'commune' => $market->commune,
                    'zone' => $market->zone,
                    'colline' => $market->colline,
                    'city' => $market->city,
                    'location' => $market->location,
                    'description' => $market->description,
                    'total_places' => $market->total_places,
                    'is_active' => $market->is_active ? '1' : '0',
                    'latitude' => $market->latitude,
                    'longitude' => $market->longitude,
                    'category_slugs' => $market->productCategories->pluck('slug')->implode(', '),
                ];
            })->all(),
            'blocks' => $blockQuery->get()->map(function (MarketBlock $block) {
                return [
                    'market_slug' => $block->market?->slug,
                    'name' => $block->name,
                    'code' => $block->code,
                    'description' => $block->description,
                    'total_places' => $block->total_places,
                    'is_active' => $block->is_active ? '1' : '0',
                ];
            })->all(),
            'users' => $userQuery->get()->map(function (User $user) {
                return [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->roles->first()?->name ?? UserRole::User->value,
                    'managed_market_slug' => $user->managedMarket?->slug,
                    'password' => '',
                    'is_active' => $user->is_active ? '1' : '0',
                ];
            })->all(),
            'places' => $placeQuery->get()->map(function (Place $place) {
                return [
                    'market_slug' => $place->market?->slug,
                    'block_code' => $place->block?->code,
                    'number' => $place->number,
                    'status' => $place->status?->value,
                    'category_slugs' => $this->categorySlugsFromPlace($place),
                    'chief_email' => $place->chief?->email,
                    'latitude' => $place->latitude,
                    'longitude' => $place->longitude,
                ];
            })->all(),
            'products' => $productQuery->get()->map(function (Product $product) {
                return [
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'market_slug' => $product->market?->slug,
                    'place_number' => $product->place?->number,
                    'merchant_email' => $product->merchant?->email,
                    'category_slug' => $product->category?->slug,
                    'description' => $product->description,
                    'price' => $product->price,
                    'unit' => $product->unit,
                    'stock' => $product->stock,
                    'available' => $product->available ? '1' : '0',
                    'is_trending' => $product->is_trending ? '1' : '0',
                ];
            })->all(),
        ];
    }

    private function categorySlugsFromPlace(Place $place): string
    {
        $ids = $place->product_category_ids ?? [];

        if (empty($ids)) {
            return '';
        }

        return ProductCategory::query()
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->pluck('slug')
            ->implode(', ');
    }

    private function addSheet(Spreadsheet $spreadsheet, string $title, array $headers, array $rows): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle($title);

        $sheetRows = [$headers];
        foreach ($rows as $row) {
            $sheetRows[] = array_map(
                fn (string $header) => $this->stringify($row[$header] ?? null),
                $headers
            );
        }

        $sheet->fromArray($sheetRows, null, 'A1', true);
        $sheet->getStyle('1:1')->getFont()->setBold(true);
        $sheet->freezePane('A2');

        foreach (range('A', $sheet->getHighestDataColumn()) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    private function buildWorkbook(string $filename, array $sheets): array
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        foreach ($sheets as $sheetName => $definition) {
            $this->addSheet(
                $spreadsheet,
                $sheetName,
                $definition['headers'],
                $definition['rows'],
            );
        }

        $filePath = tempnam(sys_get_temp_dir(), 'akg_excel_');
        if ($filePath === false) {
            throw new \RuntimeException('Impossible de créer le fichier Excel temporaire.');
        }

        (new Xlsx($spreadsheet))->save($filePath);

        return [
            'path' => $filePath,
            'filename' => $filename,
        ];
    }

    private function stringify(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return trim((string) $value);
    }

    private function sheetRows(Spreadsheet $spreadsheet, string $sheetName): array
    {
        $sheet = $spreadsheet->getSheetByName($sheetName);
        if (! $sheet) {
            return [];
        }

        $rawRows = $sheet->toArray(null, true, true, true);
        if (! isset($rawRows[1])) {
            return [];
        }

        $headers = [];
        foreach ($rawRows[1] as $column => $header) {
            $normalized = $this->normalizeHeader((string) $header);
            if ($normalized !== '') {
                $headers[$column] = $normalized;
            }
        }

        $rows = [];
        foreach ($rawRows as $rowNumber => $row) {
            if ($rowNumber === 1) {
                continue;
            }

            $values = [];
            foreach ($headers as $column => $key) {
                $values[$key] = $row[$column] ?? null;
            }

            if ($this->rowIsEmpty($values)) {
                continue;
            }

            $rows[] = [
                'row' => $rowNumber,
                'values' => $values,
            ];
        }

        return $rows;
    }

    private function normalizeHeader(string $value): string
    {
        $value = Str::lower(trim($value));
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = preg_replace('/[^a-z0-9]+/', '_', $value) ?: '';

        return trim($value, '_');
    }

    private function rowIsEmpty(array $values): bool
    {
        foreach ($values as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function parseBoolean(mixed $value, bool $default = false): bool
    {
        if ($value === null || trim((string) $value) === '') {
            return $default;
        }

        $normalized = Str::lower(trim((string) $value));
        if (in_array($normalized, ['1', 'true', 'yes', 'y', 'oui'], true)) {
            return true;
        }

        if (in_array($normalized, ['0', 'false', 'no', 'n', 'non'], true)) {
            return false;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    private function parseNullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }

    private function parseSlugList(mixed $value): array
    {
        $text = $this->parseNullableString($value);
        if ($text === null) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn (string $item) => Str::slug(trim($item)),
            preg_split('/[,;|]+/', $text) ?: []
        )));
    }

    private function parsePlaceStatus(mixed $value): string
    {
        $status = $this->parseNullableString($value) ?: PlaceStatus::Available->value;

        return match ($status) {
            'libre', 'available' => PlaceStatus::Available->value,
            'occupée', 'occupied' => PlaceStatus::Occupied->value,
            'maintenance' => PlaceStatus::Maintenance->value,
            'réservée', 'reserved' => PlaceStatus::Reserved->value,
            default => $status,
        };
    }

    private function addError(array &$errors, string $sheet, int $row, string $message): void
    {
        $errors[] = sprintf('%s ligne %d: %s', ucfirst($sheet), $row, $message);
    }

    private function importCategories(array $rows, array &$summary, array &$errors): void
    {
        foreach ($rows as $entry) {
            $row = $entry['values'];
            $rowNumber = (int) $entry['row'];

            $name = $this->parseNullableString($row['name'] ?? null);
            if (! $name) {
                $this->addError($errors, self::SHEET_CATEGORIES, $rowNumber, 'Le nom est obligatoire.');
                continue;
            }

            $slug = $this->parseNullableString($row['slug'] ?? null);
            $lookup = $slug ? ['slug' => Str::slug($slug)] : ['name' => $name];

            $category = ProductCategory::query()->updateOrCreate(
                $lookup,
                [
                    'name' => $name,
                    'slug' => $slug ? Str::slug($slug) : Str::slug($name),
                    'description' => $this->parseNullableString($row['description'] ?? null),
                    'is_active' => $this->parseBoolean($row['is_active'] ?? null, true),
                ]
            );

            $parentSlug = $this->parseNullableString($row['parent_slug'] ?? null);
            if ($parentSlug) {
                $parent = ProductCategory::query()->where('slug', Str::slug($parentSlug))->first();
                if (! $parent) {
                    $this->addError($errors, self::SHEET_CATEGORIES, $rowNumber, sprintf('La catégorie parente "%s" est introuvable.', $parentSlug));
                    continue;
                }
                $category->update(['parent_id' => $parent->id]);
            }

            $summary['categories'][$category->wasRecentlyCreated ? 'created' : 'updated'] += 1;
        }
    }

    private function importMarkets(array $rows, array $scope, array &$summary, array &$errors): void
    {
        foreach ($rows as $entry) {
            $row = $entry['values'];
            $rowNumber = (int) $entry['row'];

            $name = $this->parseNullableString($row['name'] ?? null);
            $slug = $this->parseNullableString($row['slug'] ?? null);
            $province = $this->parseNullableString($row['province'] ?? null);
            $commune = $this->parseNullableString($row['commune'] ?? null);
            $zone = $this->parseNullableString($row['zone'] ?? null);
            $colline = $this->parseNullableString($row['colline'] ?? null);

            if (! $name || ! $province || ! $commune || ! $zone || ! $colline) {
                $this->addError($errors, self::SHEET_MARKETS, $rowNumber, 'Le nom, la province, la commune, la zone et la colline sont obligatoires.');
                continue;
            }

            $categoryIds = $this->resolveCategoryIds($row['category_slugs'] ?? null, $errors, self::SHEET_MARKETS, $rowNumber);
            if ($categoryIds === null) {
                continue;
            }

            $lookup = $slug ? ['slug' => Str::slug($slug)] : ['name' => $name];
            $existing = Market::query()->where($lookup)->first();
            if (! $scope['is_super_admin'] && $existing && ! in_array((int) $existing->id, $scope['market_ids'], true)) {
                $summary['markets']['skipped'] += 1;
                continue;
            }

            if (! $existing && ! $scope['is_super_admin']) {
                $this->addError($errors, self::SHEET_MARKETS, $rowNumber, 'Vous ne pouvez créer que votre marché assigné.');
                continue;
            }

            $market = $existing ?: new Market();
            $marketData = [
                'name' => $name,
                'slug' => $slug ? Str::slug($slug) : Str::slug($name),
                'province' => $province,
                'commune' => $commune,
                'zone' => $zone,
                'colline' => $colline,
                'city' => $this->parseNullableString($row['city'] ?? null) ?: $province,
                'location' => $this->parseNullableString($row['location'] ?? null),
                'description' => $this->parseNullableString($row['description'] ?? null),
                'total_places' => (int) ($row['total_places'] ?? 0),
                'latitude' => $this->parseNullableString($row['latitude'] ?? null),
                'longitude' => $this->parseNullableString($row['longitude'] ?? null),
                'is_active' => $this->parseBoolean($row['is_active'] ?? null, true),
            ];

            if ($existing) {
                $market->update($marketData);
            } else {
                $market = Market::query()->create($marketData);
            }

            if (! empty($categoryIds)) {
                $market->productCategories()->sync($categoryIds);
            }

            $summary['markets'][$market->wasRecentlyCreated ? 'created' : 'updated'] += 1;
        }
    }

    private function importBlocks(array $rows, array $scope, array &$summary, array &$errors): void
    {
        foreach ($rows as $entry) {
            $row = $entry['values'];
            $rowNumber = (int) $entry['row'];

            $marketSlug = $this->parseNullableString($row['market_slug'] ?? null);
            $name = $this->parseNullableString($row['name'] ?? null);
            $code = $this->parseNullableString($row['code'] ?? null);

            if (! $marketSlug || ! $name) {
                $this->addError($errors, self::SHEET_BLOCKS, $rowNumber, 'Le marché et le nom du bloc sont obligatoires.');
                continue;
            }

            $market = Market::query()->where('slug', Str::slug($marketSlug))->first();
            if (! $market) {
                $this->addError($errors, self::SHEET_BLOCKS, $rowNumber, sprintf('Le marché "%s" est introuvable.', $marketSlug));
                continue;
            }

            if (! $scope['is_super_admin'] && ! in_array((int) $market->id, $scope['market_ids'], true)) {
                $summary['blocks']['skipped'] += 1;
                continue;
            }

            $lookup = $code
                ? ['market_id' => $market->id, 'code' => $code]
                : ['market_id' => $market->id, 'name' => $name];

            $block = MarketBlock::query()->updateOrCreate(
                $lookup,
                [
                    'market_id' => $market->id,
                    'name' => $name,
                    'code' => $code,
                    'description' => $this->parseNullableString($row['description'] ?? null),
                    'total_places' => (int) ($row['total_places'] ?? 0),
                    'is_active' => $this->parseBoolean($row['is_active'] ?? null, true),
                ]
            );

            $summary['blocks'][$block->wasRecentlyCreated ? 'created' : 'updated'] += 1;
        }
    }

    private function importUsers(array $rows, User $actor, array $scope, array &$summary, array &$errors): void
    {
        foreach ($rows as $entry) {
            $row = $entry['values'];
            $rowNumber = (int) $entry['row'];

            $email = $this->parseNullableString($row['email'] ?? null);
            $name = $this->parseNullableString($row['name'] ?? null);
            $role = $this->parseNullableString($row['role'] ?? null) ?: UserRole::User->value;
            $password = $this->parseNullableString($row['password'] ?? null);

            if (! $email || ! $name) {
                $this->addError($errors, self::SHEET_USERS, $rowNumber, 'Le nom et l\'email sont obligatoires.');
                continue;
            }

            $managedMarketSlug = $this->parseNullableString($row['managed_market_slug'] ?? null);
            $managedMarketId = null;
            if ($managedMarketSlug) {
                $managedMarket = Market::query()->where('slug', Str::slug($managedMarketSlug))->first();
                if (! $managedMarket) {
                    $this->addError($errors, self::SHEET_USERS, $rowNumber, sprintf('Le marché "%s" est introuvable.', $managedMarketSlug));
                    continue;
                }
                $managedMarketId = (int) $managedMarket->id;
            }

            $payload = [
                'name' => $name,
                'email' => $email,
                'phone' => $this->normalizePhone($row['phone'] ?? null),
                'role' => $role,
                'is_active' => $this->parseBoolean($row['is_active'] ?? null, true),
                'managed_market_id' => $managedMarketId,
            ];

            if ($password) {
                $payload['password'] = $password;
            }

            $existing = User::query()->where('email', $email)->first();

            try {
                if ($existing) {
                    $updated = $this->userService->update($existing, $payload, $actor);
                    $summary['users']['updated'] += 1;
                } else {
                    if (! isset($payload['password'])) {
                        $this->addError($errors, self::SHEET_USERS, $rowNumber, 'Un mot de passe est requis pour créer un nouvel utilisateur.');
                        continue;
                    }

                    $created = $this->userService->create($payload, $actor);
                    $summary['users']['created'] += 1;
                }
            } catch (ValidationException $e) {
                $this->addError($errors, self::SHEET_USERS, $rowNumber, implode(' ', collect($e->errors())->flatten()->all()));
            } catch (Throwable $e) {
                $this->addError($errors, self::SHEET_USERS, $rowNumber, $e->getMessage());
            }
        }
    }

    private function importPlaces(array $rows, User $actor, array $scope, array &$summary, array &$errors): void
    {
        foreach ($rows as $entry) {
            $row = $entry['values'];
            $rowNumber = (int) $entry['row'];

            $marketSlug = $this->parseNullableString($row['market_slug'] ?? null);
            $number = $this->parseNullableString($row['number'] ?? null);

            if (! $marketSlug || ! $number) {
                $this->addError($errors, self::SHEET_PLACES, $rowNumber, 'Le marché et le numéro sont obligatoires.');
                continue;
            }

            $market = Market::query()->where('slug', Str::slug($marketSlug))->first();
            if (! $market) {
                $this->addError($errors, self::SHEET_PLACES, $rowNumber, sprintf('Le marché "%s" est introuvable.', $marketSlug));
                continue;
            }

            if (! $scope['is_super_admin'] && ! in_array((int) $market->id, $scope['market_ids'], true)) {
                $summary['places']['skipped'] += 1;
                continue;
            }

            $categoryIds = $this->resolveCategoryIds($row['category_slugs'] ?? null, $errors, self::SHEET_PLACES, $rowNumber);
            if ($categoryIds === null) {
                continue;
            }

            $block = null;
            $blockCode = $this->parseNullableString($row['block_code'] ?? null);
            if ($blockCode) {
                $block = MarketBlock::query()
                    ->where('market_id', $market->id)
                    ->where('code', $blockCode)
                    ->first();
                if (! $block) {
                    $this->addError($errors, self::SHEET_PLACES, $rowNumber, sprintf('Le bloc "%s" est introuvable pour ce marché.', $blockCode));
                    continue;
                }
            }

            $chiefEmail = $this->parseNullableString($row['chief_email'] ?? null);
            $status = $this->parsePlaceStatus($row['status'] ?? null);
            if ($chiefEmail) {
                $status = PlaceStatus::Occupied->value;
            }

            $payload = [
                'market_id' => $market->id,
                'market_block_id' => $block?->id,
                'number' => $number,
                'status' => $status,
                'product_category_ids' => $categoryIds,
                'latitude' => $this->parseNullableString($row['latitude'] ?? null),
                'longitude' => $this->parseNullableString($row['longitude'] ?? null),
                'chief_user_id' => null,
            ];

            try {
                $existing = Place::query()
                    ->where('market_id', $market->id)
                    ->where('number', $number)
                    ->first();

                $place = $existing
                    ? $this->placeService->update($existing, $payload)
                    : $this->placeService->create($payload);

                if ($chiefEmail) {
                    $chief = User::query()->where('email', $chiefEmail)->first();
                    if (! $chief) {
                        $this->addError($errors, self::SHEET_PLACES, $rowNumber, sprintf('Le commerçant "%s" est introuvable.', $chiefEmail));
                        continue;
                    }
                    $place = $this->placeService->assignChief($place, $chief);
                } elseif ($status === PlaceStatus::Available->value && $place->chief_user_id) {
                    $place->update(['chief_user_id' => null]);
                }

                $summary['places'][$existing ? 'updated' : 'created'] += 1;
            } catch (ValidationException $e) {
                $this->addError($errors, self::SHEET_PLACES, $rowNumber, implode(' ', collect($e->errors())->flatten()->all()));
            } catch (Throwable $e) {
                $this->addError($errors, self::SHEET_PLACES, $rowNumber, $e->getMessage());
            }
        }
    }

    private function importProducts(array $rows, User $actor, array $scope, array &$summary, array &$errors): void
    {
        foreach ($rows as $entry) {
            $row = $entry['values'];
            $rowNumber = (int) $entry['row'];

            $marketSlug = $this->parseNullableString($row['market_slug'] ?? null);
            $name = $this->parseNullableString($row['name'] ?? null);
            $merchantEmail = $this->parseNullableString($row['merchant_email'] ?? null);

            if (! $marketSlug || ! $name || ! $merchantEmail) {
                $this->addError($errors, self::SHEET_PRODUCTS, $rowNumber, 'Le marché, le nom et l\'email du commerçant sont obligatoires.');
                continue;
            }

            $market = Market::query()->where('slug', Str::slug($marketSlug))->first();
            if (! $market) {
                $this->addError($errors, self::SHEET_PRODUCTS, $rowNumber, sprintf('Le marché "%s" est introuvable.', $marketSlug));
                continue;
            }

            if (! $scope['is_super_admin'] && ! in_array((int) $market->id, $scope['market_ids'], true)) {
                $summary['products']['skipped'] += 1;
                continue;
            }

            $merchant = User::query()->where('email', $merchantEmail)->first();
            if (! $merchant) {
                $this->addError($errors, self::SHEET_PRODUCTS, $rowNumber, sprintf('Le commerçant "%s" est introuvable.', $merchantEmail));
                continue;
            }

            $categorySlug = $this->parseNullableString($row['category_slug'] ?? null);
            $categoryId = null;
            if ($categorySlug) {
                $category = ProductCategory::query()->where('slug', Str::slug($categorySlug))->first();
                if (! $category) {
                    $this->addError($errors, self::SHEET_PRODUCTS, $rowNumber, sprintf('La catégorie "%s" est introuvable.', $categorySlug));
                    continue;
                }
                $categoryId = $category->id;
            }

            $placeNumber = $this->parseNullableString($row['place_number'] ?? null);
            $placeId = null;
            if ($placeNumber) {
                $place = Place::query()
                    ->where('market_id', $market->id)
                    ->where('number', $placeNumber)
                    ->first();
                if (! $place) {
                    $this->addError($errors, self::SHEET_PRODUCTS, $rowNumber, sprintf('L\'emplacement "%s" est introuvable pour ce marché.', $placeNumber));
                    continue;
                }
                $placeId = $place->id;
            }

            $slug = $this->parseNullableString($row['slug'] ?? null);
            $lookup = $slug
                ? ['market_id' => $market->id, 'slug' => Str::slug($slug)]
                : ['market_id' => $market->id, 'name' => $name];

            try {
                $product = Product::query()->updateOrCreate(
                    $lookup,
                    [
                        'user_id' => $merchant->id,
                        'market_id' => $market->id,
                        'place_id' => $placeId,
                        'category_id' => $categoryId,
                        'name' => $name,
                        'slug' => $slug ? Str::slug($slug) : Str::slug($name),
                        'description' => $this->parseNullableString($row['description'] ?? null),
                        'price' => (float) ($row['price'] ?? 0),
                        'unit' => $this->parseNullableString($row['unit'] ?? null) ?: 'unit',
                        'stock' => (int) ($row['stock'] ?? 0),
                        'available' => $this->parseBoolean($row['available'] ?? null, true),
                        'is_trending' => $this->parseBoolean($row['is_trending'] ?? null, false),
                    ]
                );

                $summary['products'][$product->wasRecentlyCreated ? 'created' : 'updated'] += 1;
            } catch (Throwable $e) {
                $this->addError($errors, self::SHEET_PRODUCTS, $rowNumber, $e->getMessage());
            }
        }
    }

    private function resolveCategoryIds(mixed $raw, array &$errors, string $sheet, int $rowNumber): ?array
    {
        $slugs = $this->parseSlugList($raw);
        if (empty($slugs)) {
            return [];
        }

        $categories = ProductCategory::query()->whereIn('slug', $slugs)->get()->keyBy('slug');
        $ids = [];

        foreach ($slugs as $slug) {
            $category = $categories->get($slug);
            if (! $category) {
                $this->addError($errors, $sheet, $rowNumber, sprintf('La catégorie "%s" est introuvable.', $slug));
                return null;
            }
            $ids[] = $category->id;
        }

        return array_values(array_unique($ids));
    }

    private function normalizePhone(mixed $phone): ?string
    {
        $phone = $this->parseNullableString($phone);
        if ($phone === null) {
            return null;
        }

        $phone = preg_replace('/\s+/', '', $phone) ?: '';

        return $phone === '' ? null : $phone;
    }
}
