<?php

namespace Tests\Feature\Website;

use App\Models\BagProduct;
use App\Models\Material;
use App\Models\MaterialCategory;
use App\Models\UnitOfMeasurement;
use App\Models\User;
use App\Models\WebsiteEnquiry;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicWebsiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_public_pages_are_available_to_guests(): void
    {
        $pages = [
            'website.public.home',
            'website.public.about',
            'website.public.products',
            'website.public.custom-order',
            'website.public.quote',
            'website.public.manufacturing',
            'website.public.quality',
            'website.public.gallery',
            'website.public.contact',
            'website.public.testimonials',
        ];

        foreach ($pages as $name) {
            $this->get(route($name))->assertOk();
        }

        $this->get(route('website.public.products.category', 'shopping-bags'))->assertOk();
        $this->get(route('website.public.quality'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Website/Quality')
                ->where('notice', 'Demonstration process only. This is not a certification, an audit result, or a statement of factory practice.')
                ->has('steps', 4))
            ->assertDontSee('ISO');

        $this->get(route('website.public.home'))
            ->assertInertia(fn ($page) => $page
                ->component('Website/Home')
                ->where('website.phone', null)
                ->where('website.email', null)
                ->where('website.address', null)
                ->where('website.whatsappUrl', null)
                ->where('website.mapUrl', null)
                ->where('website.hasTestimonials', false)
                ->where('testimonials', [])
                ->has('categories', 4)
                ->where('auth.user', null)
                ->where('navigation', []));
    }

    public function test_public_catalog_hides_prices_stock_and_internal_notes(): void
    {
        [$unit] = $this->masters();

        BagProduct::query()->create([
            'code' => 'BAG-PUB',
            'name' => 'Loop Bag',
            'unit_of_measurement_id' => $unit->id,
            'selling_price' => 4321.25,
            'description' => 'INTERNAL-SECRET',
            'status' => 'active',
            'is_public' => true,
            'public_slug' => 'loop-bag',
            'public_category' => 'shopping-bags',
            'public_summary' => 'A published loop bag',
            'public_description' => 'Public bag copy',
        ]);

        BagProduct::query()->create([
            'code' => 'BAG-HID',
            'name' => 'Hidden Bag',
            'unit_of_measurement_id' => $unit->id,
            'selling_price' => 111.11,
            'status' => 'active',
            'is_public' => false,
            'public_slug' => 'hidden-bag',
            'public_category' => 'shopping-bags',
            'public_summary' => 'Should stay private',
        ]);

        $category = MaterialCategory::query()->create([
            'code' => 'DANA',
            'name' => 'Dana',
            'status' => 'active',
        ]);

        Material::query()->create([
            'code' => 'DAN-PUB',
            'name' => 'Clear Dana',
            'material_category_id' => $category->id,
            'unit_of_measurement_id' => $unit->id,
            'default_unit_cost' => 9999.99,
            'reorder_level' => 50,
            'description' => 'COST-SECRET',
            'status' => 'active',
            'is_public' => true,
            'public_slug' => 'clear-dana',
            'public_category' => 'dana-granules',
            'public_summary' => 'Published dana',
            'public_description' => 'Public dana copy',
        ]);

        $this->get(route('website.public.products.show', ['category' => 'shopping-bags', 'slug' => 'loop-bag']))
            ->assertOk()
            ->assertSee('Public bag copy')
            ->assertDontSee('4321.25')
            ->assertDontSee('selling_price')
            ->assertDontSee('INTERNAL-SECRET')
            ->assertDontSee('current_balance');

        $this->get(route('website.public.products.show', ['category' => 'shopping-bags', 'slug' => 'hidden-bag']))
            ->assertNotFound();

        $this->get(route('website.public.products.show', ['category' => 'dana-granules', 'slug' => 'clear-dana']))
            ->assertOk()
            ->assertSee('Public dana copy')
            ->assertDontSee('9999.99')
            ->assertDontSee('default_unit_cost')
            ->assertDontSee('reorder_level')
            ->assertDontSee('COST-SECRET');
    }

    public function test_quote_requires_contact_details_and_rejects_bad_uploads(): void
    {
        $this->post(route('website.public.quote.store'), [
            'idempotency_token' => (string) str()->uuid(),
            'name' => 'Ayesha Khan',
        ])->assertSessionHasErrors(['email', 'phone', 'message']);

        $this->assertSame(0, WebsiteEnquiry::query()->count());

        Storage::fake('local');
        $token = $this->token('website.public.custom-order');

        $this->post(route('website.public.custom-order.store'), [
            'idempotency_token' => $token,
            'name' => 'Ayesha Khan',
            'email' => 'ayesha@example.com',
            'phone' => '03001234567',
            'bag_type' => 'loop_handle',
            'size' => '12 x 16',
            'quantity' => 500,
            'artwork' => UploadedFile::fake()->create('shell.php', 20, 'application/x-httpd-php'),
        ])->assertSessionHasErrors('artwork');

        $this->post(route('website.public.custom-order.store'), [
            'idempotency_token' => $this->token('website.public.custom-order'),
            'name' => 'Ayesha Khan',
            'email' => 'ayesha@example.com',
            'phone' => '03001234567',
            'bag_type' => 'loop_handle',
            'size' => '12 x 16',
            'quantity' => 500,
            'artwork' => UploadedFile::fake()->create('logo.svg', 20, 'image/svg+xml'),
        ])->assertSessionHasErrors('artwork');

        $this->assertSame(0, WebsiteEnquiry::query()->count());
        Storage::disk('local')->assertDirectoryEmpty('website/enquiries');
    }

    public function test_custom_order_stores_artwork_and_suppresses_duplicates(): void
    {
        Storage::fake('local');
        $token = $this->token('website.public.custom-order');

        $payload = [
            'name' => 'Ayesha Khan',
            'email' => 'ayesha@example.com',
            'phone' => '0300 123 4567',
            'bag_type' => 'loop_handle',
            'size' => '12 x 16',
            'thickness' => '40 micron',
            'color' => 'White',
            'print' => 'one_side',
            'quantity' => 1000,
            'message' => 'Please quote this size.',
            'artwork' => UploadedFile::fake()->image('logo.png'),
        ];

        $this->post(route('website.public.custom-order.store'), $payload + ['idempotency_token' => $token])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame(1, WebsiteEnquiry::query()->count());
        $enquiry = WebsiteEnquiry::query()->first();
        $this->assertSame('custom_order', $enquiry->type);
        $this->assertSame('new', $enquiry->status);
        $this->assertStringStartsWith('website/enquiries/', $enquiry->artwork_path);
        $this->assertStringEndsWith('.png', $enquiry->artwork_path);
        Storage::disk('local')->assertExists($enquiry->artwork_path);
        $this->assertFalse(is_file(public_path($enquiry->artwork_path)));

        $this->post(route('website.public.custom-order.store'), $payload + [
            'idempotency_token' => $token,
            'artwork' => UploadedFile::fake()->image('logo-again.png'),
        ])->assertRedirect()->assertSessionHas('status');

        $this->assertSame(1, WebsiteEnquiry::query()->count());

        $second = $this->token('website.public.custom-order');
        $this->post(route('website.public.custom-order.store'), $payload + [
            'idempotency_token' => $second,
            'phone' => '+92 300-1234567',
            'artwork' => UploadedFile::fake()->create('art.pdf', 40, 'application/pdf'),
        ])->assertRedirect();

        $this->assertSame(1, WebsiteEnquiry::query()->count());
    }

    public function test_demo_public_product_is_visible_and_quote_is_stored(): void
    {
        [$unit] = $this->masters();

        BagProduct::query()->create([
            'code' => 'DEMO-BAG-QUOTE',
            'name' => 'DEMO Quote Bag',
            'bag_type' => 't_shirt',
            'size_label' => '300 x 450 mm',
            'thickness_micron' => 40,
            'unit_of_measurement_id' => $unit->id,
            'selling_price' => 99,
            'status' => 'active',
            'is_public' => true,
            'public_slug' => 'demo-quote-bag',
            'public_category' => 'shopping-bags',
            'public_summary' => 'Sample catalog text for the demo quote bag.',
            'public_description' => 'Sample catalog text. No minimum order is stored on this product.',
        ]);

        $this->get(route('website.public.products', ['q' => 'DEMO Quote']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Website/Products/Index')
                ->where('search', 'DEMO Quote')
                ->where('products.0.name', 'DEMO Quote Bag')
                ->where('products.0.size', '300 x 450 mm')
                ->where('products.0.quoteHref', route('website.public.quote', [
                    'category' => 'shopping-bags',
                    'product' => 'demo-quote-bag',
                ])))
            ->assertDontSee('99')
            ->assertDontSee('selling_price');

        $token = $this->token('website.public.quote');

        $this->post(route('website.public.quote.store'), [
            'idempotency_token' => $token,
            'name' => 'Demo Buyer',
            'email' => 'demo.buyer@example.com',
            'phone' => '03001112233',
            'category' => 'shopping-bags',
            'product_slug' => 'demo-quote-bag',
            'message' => 'Sample quote request for the demo bag.',
        ])->assertRedirect();

        $this->assertDatabaseHas('website_enquiries', [
            'type' => 'quote',
            'email' => 'demo.buyer@example.com',
            'product_label' => 'DEMO Quote Bag',
        ]);
    }

    public function test_authorised_staff_can_review_enquiries_and_guests_cannot(): void
    {
        $token = $this->token('website.public.contact');

        $this->post(route('website.public.contact.store'), [
            'idempotency_token' => $token,
            'name' => 'Ayesha Khan',
            'email' => 'ayesha@example.com',
            'message' => 'Please call me about printed bags.',
        ])->assertRedirect();

        $enquiry = WebsiteEnquiry::query()->first();
        $this->assertNotNull($enquiry);

        $this->get(route('website.admin.hub'))->assertRedirect(route('login'));
        $this->get(route('website.admin.enquiries.index'))->assertRedirect(route('login'));
        $this->get(route('website.admin.enquiries.show', $enquiry))->assertRedirect(route('login'));
        $this->get(route('website.admin.settings.edit'))->assertRedirect(route('login'));

        $storekeeper = User::factory()->create();
        $storekeeper->assignRole('storekeeper');

        $this->actingAs($storekeeper)->get(route('website.admin.hub'))->assertForbidden();
        $this->actingAs($storekeeper)->get(route('website.admin.enquiries.show', $enquiry))->assertForbidden();

        $accountant = User::factory()->create();
        $accountant->assignRole('accountant');

        $this->actingAs($accountant)
            ->get(route('website.admin.enquiries.show', $enquiry))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Website/Admin/Enquiries/Show')
                ->where('enquiry.name', 'Ayesha Khan')
                ->where('enquiry.message', 'Please call me about printed bags.'));

        $this->actingAs($accountant)
            ->put(route('website.admin.settings.update'), ['company_name' => 'Changed'])
            ->assertForbidden();

        $owner = User::factory()->create();
        $owner->assignRole('owner-admin');

        $this->actingAs($owner)
            ->get(route('website.public.home'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('auth.user', null)->where('navigation', []));

        $this->actingAs($owner)
            ->put(route('website.admin.enquiries.update', $enquiry), [
                'status' => 'in_review',
                'internal_notes' => 'Call tomorrow.',
            ])
            ->assertRedirect();

        $this->assertSame('in_review', $enquiry->fresh()->status);
    }

    public function test_public_image_route_serves_only_published_images(): void
    {
        Storage::fake('local');
        [$unit] = $this->masters();
        Storage::disk('local')->put('website/listings/loop.png', 'png-bytes');

        $product = BagProduct::query()->create([
            'code' => 'BAG-IMG',
            'name' => 'Printed Loop',
            'unit_of_measurement_id' => $unit->id,
            'status' => 'active',
            'is_public' => true,
            'public_slug' => 'printed-loop',
            'public_category' => 'printed-bags',
            'public_image_path' => 'website/listings/loop.png',
        ]);

        $this->get(route('website.public.media.catalog', ['source' => 'bag', 'slug' => 'printed-loop']))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $product->update(['is_public' => false]);

        $this->get(route('website.public.media.catalog', ['source' => 'bag', 'slug' => 'printed-loop']))
            ->assertNotFound();
    }

    private function token(string $route): string
    {
        $this->get(route($route))->assertOk();
        $tokens = session('website.form_tokens', []);
        $this->assertIsArray($tokens);
        $this->assertNotEmpty($tokens);

        return (string) array_key_last($tokens);
    }

    /**
     * @return array{0: UnitOfMeasurement}
     */
    private function masters(): array
    {
        return [
            UnitOfMeasurement::query()->create([
                'code' => 'PCS',
                'name' => 'Pieces',
                'symbol' => 'pcs',
                'status' => 'active',
            ]),
        ];
    }
}
