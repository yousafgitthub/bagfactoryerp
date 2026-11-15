<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bag_products', function (Blueprint $table) {
            $table->boolean('is_public')->default(false);
            $table->string('public_slug', 160)->nullable();
            $table->string('public_category', 64)->nullable();
            $table->text('public_summary')->nullable();
            $table->text('public_description')->nullable();
            $table->string('public_image_path')->nullable();
            $table->unsignedInteger('public_sort')->default(0);
            $table->index(['is_public', 'public_category'], 'bag_products_public_category_index');
            $table->unique(['public_category', 'public_slug'], 'bag_products_public_slug_unique');
        });

        Schema::table('materials', function (Blueprint $table) {
            $table->boolean('is_public')->default(false);
            $table->string('public_slug', 160)->nullable();
            $table->string('public_category', 64)->nullable();
            $table->text('public_summary')->nullable();
            $table->text('public_description')->nullable();
            $table->string('public_image_path')->nullable();
            $table->unsignedInteger('public_sort')->default(0);
            $table->index(['is_public', 'public_category'], 'materials_public_category_index');
            $table->unique(['public_category', 'public_slug'], 'materials_public_slug_unique');
        });

        Schema::create('website_settings', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->default('Bag Factory');
            $table->string('tagline')->nullable();
            $table->string('hero_title')->nullable();
            $table->text('hero_subtitle')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('email')->nullable();
            $table->string('whatsapp', 40)->nullable();
            $table->text('address')->nullable();
            $table->string('map_url', 500)->nullable();
            $table->string('facebook_url', 500)->nullable();
            $table->string('linkedin_url', 500)->nullable();
            $table->text('about')->nullable();
            $table->text('manufacturing')->nullable();
            $table->json('why_choose_us')->nullable();
            $table->json('feature_strip')->nullable();
            $table->boolean('show_sample_catalog_notice')->default(true);
            $table->timestamps();
        });

        Schema::create('website_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('name');
            $table->string('card_title')->nullable();
            $table->text('summary');
            $table->text('body')->nullable();
            $table->boolean('is_sample')->default(true);
            $table->boolean('is_visible')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('website_testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('author_name');
            $table->string('author_role')->nullable();
            $table->text('quote');
            $table->boolean('is_visible')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('website_gallery_items', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('caption')->nullable();
            $table->string('image_path');
            $table->boolean('is_visible')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('website_enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('type', 32);
            $table->string('status', 32)->default('new');
            $table->string('idempotency_token', 64)->nullable()->unique();
            $table->string('name');
            $table->string('email');
            $table->string('phone', 40)->nullable();
            $table->string('phone_normalized', 40)->default('');
            $table->string('company', 160)->nullable();
            $table->string('subject_key', 190);
            $table->string('product_label', 160)->nullable();
            $table->text('message')->nullable();
            $table->json('details')->nullable();
            $table->string('artwork_path')->nullable();
            $table->string('artwork_original_name')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->text('internal_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['type', 'email', 'phone_normalized', 'subject_key'], 'website_enquiries_lookup_index');
            $table->index('status');
        });

        $now = now();

        DB::table('website_settings')->insert([
            'company_name' => 'Bag Factory',
            'tagline' => 'Quality Bags. Better Tomorrow.',
            'hero_title' => "Quality Bags.\nBuilt for Your Business.",
            'hero_subtitle' => 'Sample catalog text. Premium plastic shopping bags and customized packaging. Replace this line with the factory’s own wording.',
            'about' => 'Sample introduction. This page is where the factory describes itself to customers. Replace this text in website settings. It is not a certification, a customer quote, or a production-capacity figure.',
            'manufacturing' => 'Sample process note. Plastic film is formed, printed when an order asks for it, then cut and sealed into bags. Dana granules are a raw material used for film. Capacity, certifications, and lead times are omitted until the factory writes them here.',
            'why_choose_us' => json_encode([
                ['title' => 'Premium Quality Products', 'body' => 'Shopping bags and related packaging made for retail and wholesale orders.'],
                ['title' => 'Custom Sizes & Colors', 'body' => 'Size, thickness, and color are collected on the custom-order form.'],
                ['title' => 'Competitive Prices', 'body' => 'Prices are quoted on request. They are not published on this site.'],
                ['title' => 'Reliable Delivery', 'body' => 'Delivery timing is confirmed when the factory replies to an enquiry.'],
            ]),
            'feature_strip' => json_encode([
                'High Quality Products',
                'Custom Sizes & Colors',
                'Advanced Manufacturing',
                'On-Time Delivery',
            ]),
            'show_sample_catalog_notice' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('website_categories')->insert([
            [
                'slug' => 'shopping-bags',
                'name' => 'Shopping Bags',
                'card_title' => 'Strong & Durable',
                'summary' => 'Sample catalog text. Plastic shopping bags with handles, for retail and wholesale orders.',
                'body' => 'Sample catalog text. Loop-handle, T-shirt, and die-cut shopping bags. Sizes and colors are confirmed on a quote. This description is editable and is not a verified company claim.',
                'is_sample' => true,
                'is_visible' => true,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'printed-bags',
                'name' => 'Printed Bags',
                'card_title' => 'Your Brand, Our Print',
                'summary' => 'Sample catalog text. Shopping bags printed with artwork supplied by the customer.',
                'body' => 'Sample catalog text. Printed bags take a logo or design uploaded with a custom order or quote. This description is editable and is not a verified company claim.',
                'is_sample' => true,
                'is_visible' => true,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'custom-bags',
                'name' => 'Custom Packaging',
                'card_title' => 'Tailored to Your Needs',
                'summary' => 'Sample catalog text. Bags made to a requested size, thickness, color, and print.',
                'body' => 'Sample catalog text. Use the custom-order form for bag type, size, thickness, color, print, quantity, and artwork. This description is editable and is not a verified company claim.',
                'is_sample' => true,
                'is_visible' => true,
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'dana-granules',
                'name' => 'Dana Products',
                'card_title' => 'Granules & Raw Material',
                'summary' => 'Sample catalog text. Dana and plastic granules supplied as raw material.',
                'body' => 'Sample catalog text. Dana granules are listed here only when a material is marked public in the ERP. Stock quantities and internal costs are not shown. This description is editable and is not a verified company claim.',
                'is_sample' => true,
                'is_visible' => true,
                'sort_order' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('website_enquiries');
        Schema::dropIfExists('website_gallery_items');
        Schema::dropIfExists('website_testimonials');
        Schema::dropIfExists('website_categories');
        Schema::dropIfExists('website_settings');

        Schema::table('materials', function (Blueprint $table) {
            $table->dropIndex('materials_public_category_index');
            $table->dropUnique('materials_public_slug_unique');
            $table->dropColumn([
                'is_public',
                'public_slug',
                'public_category',
                'public_summary',
                'public_description',
                'public_image_path',
                'public_sort',
            ]);
        });

        Schema::table('bag_products', function (Blueprint $table) {
            $table->dropIndex('bag_products_public_category_index');
            $table->dropUnique('bag_products_public_slug_unique');
            $table->dropColumn([
                'is_public',
                'public_slug',
                'public_category',
                'public_summary',
                'public_description',
                'public_image_path',
                'public_sort',
            ]);
        });
    }
};
