<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ExcelTransferApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_super_admin_can_export_excel_workbook(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('SUPER_ADMIN');
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/admin/excel/export');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $file = $response->baseResponse->getFile();
        $spreadsheet = IOFactory::load($file->getPathname());
        $sheetNames = array_map(fn ($sheet) => $sheet->getTitle(), $spreadsheet->getAllSheets());

        $this->assertSame(['categories', 'markets', 'blocks', 'users', 'places', 'products'], $sheetNames);
    }

    public function test_super_admin_can_download_excel_template(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('SUPER_ADMIN');
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/admin/excel/template');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $file = $response->baseResponse->getFile();
        $spreadsheet = IOFactory::load($file->getPathname());
        $sheetNames = array_map(fn ($sheet) => $sheet->getTitle(), $spreadsheet->getAllSheets());

        $this->assertSame(['categories', 'markets', 'blocks', 'users', 'places', 'products'], $sheetNames);
        $this->assertSame('name', $spreadsheet->getSheetByName('categories')->getCell('A1')->getValue());
        $this->assertSame('slug', $spreadsheet->getSheetByName('categories')->getCell('B1')->getValue());
    }

    public function test_super_admin_can_import_excel_workbook(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('SUPER_ADMIN');
        Sanctum::actingAs($admin);

        $file = $this->createWorkbook([
            'categories' => [
                ['name', 'slug', 'description', 'is_active', 'parent_slug'],
                ['Commerce Général', 'general', 'Catégorie principale', '1', ''],
            ],
            'markets' => [
                ['name', 'slug', 'province', 'commune', 'zone', 'colline', 'city', 'location', 'description', 'total_places', 'is_active', 'latitude', 'longitude', 'category_slugs'],
                ['Marché Jabe', 'jabe-market', 'Bujumbura Mairie', 'Mukaza', 'Rohero', 'Jabe', 'Bujumbura', 'Centre-ville', 'Marché principal', '20', '1', '', '', 'general'],
            ],
            'blocks' => [
                ['market_slug', 'name', 'code', 'description', 'total_places', 'is_active'],
                ['jabe-market', 'Bloc A', 'A', 'Bloc principal', '10', '1'],
            ],
            'users' => [
                ['name', 'email', 'phone', 'role', 'managed_market_slug', 'password', 'is_active'],
                ['Marie Commerçante', 'merchant@example.com', '+257 79 000 000', 'COMMERCANT', '', 'Secret1234', '1'],
            ],
            'places' => [
                ['market_slug', 'block_code', 'number', 'status', 'category_slugs', 'chief_email', 'latitude', 'longitude'],
                ['jabe-market', 'A', 'A-01', 'occupied', 'general', 'merchant@example.com', '', ''],
            ],
            'products' => [
                ['name', 'slug', 'market_slug', 'place_number', 'merchant_email', 'category_slug', 'description', 'price', 'unit', 'stock', 'available', 'is_trending'],
                ['Tomates', 'tomates', 'jabe-market', 'A-01', 'merchant@example.com', 'general', 'Produit fraîchement importé', '1500', 'kg', '12', '1', '0'],
            ],
        ]);

        $uploaded = UploadedFile::fake()->createWithContent(
            'akaguriro-import.xlsx',
            file_get_contents($file),
        );

        $response = $this->post('/api/v1/admin/excel/import', [
            'file' => $uploaded,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.categories.created', 1)
            ->assertJsonPath('data.markets.created', 1)
            ->assertJsonPath('data.blocks.created', 1)
            ->assertJsonPath('data.users.created', 1)
            ->assertJsonPath('data.places.created', 1)
            ->assertJsonPath('data.products.created', 1);

        $this->assertDatabaseHas('product_categories', ['slug' => 'general', 'name' => 'Commerce Général']);
        $this->assertDatabaseHas('markets', ['slug' => 'jabe-market', 'name' => 'Marché Jabe']);
        $this->assertDatabaseHas('market_blocks', ['code' => 'A', 'name' => 'Bloc A']);
        $this->assertDatabaseHas('users', ['email' => 'merchant@example.com', 'name' => 'Marie Commerçante']);
        $this->assertDatabaseHas('places', ['number' => 'A-01', 'status' => 'occupied']);
        $this->assertDatabaseHas('products', ['slug' => 'tomates', 'name' => 'Tomates']);
    }

    private function createWorkbook(array $sheets): string
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        foreach ($sheets as $name => $rows) {
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle($name);
            $sheet->fromArray($rows, null, 'A1', true);
        }

        $path = tempnam(sys_get_temp_dir(), 'akg_excel_test_');
        if ($path === false) {
            throw new \RuntimeException('Impossible de créer un fichier Excel temporaire.');
        }

        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }
}
