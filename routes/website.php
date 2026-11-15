<?php

use App\Http\Controllers\Website\Admin\WebsiteContentController;
use App\Http\Controllers\Website\Admin\WebsiteEnquiryController;
use App\Http\Controllers\Website\Admin\WebsiteHubController;
use App\Http\Controllers\Website\Admin\WebsiteListingController;
use App\Http\Controllers\Website\PublicEnquiryController;
use App\Http\Controllers\Website\PublicMediaController;
use App\Http\Controllers\Website\PublicPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicPageController::class, 'home'])->name('website.public.home');
Route::get('/about', [PublicPageController::class, 'about'])->name('website.public.about');
Route::get('/products', [PublicPageController::class, 'products'])->name('website.public.products');
Route::get('/products/{category}/{slug}', [PublicPageController::class, 'show'])
    ->where(['category' => '[a-z0-9\-]+', 'slug' => '[a-z0-9\-]+'])
    ->name('website.public.products.show');
Route::get('/products/{category}', [PublicPageController::class, 'category'])
    ->where(['category' => '[a-z0-9\-]+'])
    ->name('website.public.products.category');
Route::get('/custom-orders', [PublicPageController::class, 'customOrder'])->name('website.public.custom-order');
Route::post('/custom-orders', [PublicEnquiryController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('website.public.custom-order.store');
Route::get('/request-a-quote', [PublicPageController::class, 'quote'])->name('website.public.quote');
Route::post('/request-a-quote', [PublicEnquiryController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('website.public.quote.store');
Route::get('/manufacturing', [PublicPageController::class, 'manufacturing'])->name('website.public.manufacturing');
Route::get('/quality-control', [PublicPageController::class, 'quality'])->name('website.public.quality');
Route::get('/gallery', [PublicPageController::class, 'gallery'])->name('website.public.gallery');
Route::get('/contact', [PublicPageController::class, 'contact'])->name('website.public.contact');
Route::post('/contact', [PublicEnquiryController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('website.public.contact.store');
Route::get('/testimonials', [PublicPageController::class, 'testimonials'])->name('website.public.testimonials');
Route::get('/media/catalog/{source}/{slug}', [PublicMediaController::class, 'catalog'])
    ->where(['source' => 'bag|material', 'slug' => '[a-z0-9\-]+'])
    ->name('website.public.media.catalog');
Route::get('/media/gallery/{item}', [PublicMediaController::class, 'gallery'])
    ->whereNumber('item')
    ->name('website.public.media.gallery');

Route::middleware(['auth', 'verified', 'permission:website.view'])->prefix('website')->name('website.admin.')->group(function () {
    Route::get('/', WebsiteHubController::class)->name('hub');

    Route::get('/listings', [WebsiteListingController::class, 'index'])->name('listings.index');
    Route::get('/listings/{source}/{id}/edit', [WebsiteListingController::class, 'edit'])
        ->where(['source' => 'bag|material', 'id' => '[0-9]+'])
        ->middleware('permission:website.manage')
        ->name('listings.edit');
    Route::post('/listings/{source}/{id}', [WebsiteListingController::class, 'update'])
        ->where(['source' => 'bag|material', 'id' => '[0-9]+'])
        ->middleware('permission:website.manage')
        ->name('listings.update');
    Route::get('/listings/{source}/{id}/image', [WebsiteListingController::class, 'image'])
        ->where(['source' => 'bag|material', 'id' => '[0-9]+'])
        ->name('listings.image');

    Route::get('/enquiries', [WebsiteEnquiryController::class, 'index'])->name('enquiries.index');
    Route::get('/enquiries/{enquiry}', [WebsiteEnquiryController::class, 'show'])->name('enquiries.show');
    Route::put('/enquiries/{enquiry}', [WebsiteEnquiryController::class, 'update'])->name('enquiries.update');
    Route::get('/enquiries/{enquiry}/artwork', [WebsiteEnquiryController::class, 'artwork'])->name('enquiries.artwork');

    Route::get('/categories', [WebsiteContentController::class, 'categories'])
        ->middleware('permission:website.manage')
        ->name('categories.edit');
    Route::put('/categories/{category}', [WebsiteContentController::class, 'updateCategory'])
        ->middleware('permission:website.manage')
        ->name('categories.update');

    Route::get('/gallery', [WebsiteContentController::class, 'gallery'])
        ->middleware('permission:website.manage')
        ->name('gallery.index');
    Route::post('/gallery', [WebsiteContentController::class, 'storeGallery'])
        ->middleware('permission:website.manage')
        ->name('gallery.store');
    Route::put('/gallery/{item}', [WebsiteContentController::class, 'updateGallery'])
        ->middleware('permission:website.manage')
        ->name('gallery.update');
    Route::delete('/gallery/{item}', [WebsiteContentController::class, 'destroyGallery'])
        ->middleware('permission:website.manage')
        ->name('gallery.destroy');
    Route::get('/gallery/{item}/image', [WebsiteContentController::class, 'galleryImage'])->name('gallery.image');

    Route::get('/testimonials', [WebsiteContentController::class, 'testimonials'])
        ->middleware('permission:website.manage')
        ->name('testimonials.index');
    Route::post('/testimonials', [WebsiteContentController::class, 'storeTestimonial'])
        ->middleware('permission:website.manage')
        ->name('testimonials.store');
    Route::put('/testimonials/{testimonial}', [WebsiteContentController::class, 'updateTestimonial'])
        ->middleware('permission:website.manage')
        ->name('testimonials.update');
    Route::delete('/testimonials/{testimonial}', [WebsiteContentController::class, 'destroyTestimonial'])
        ->middleware('permission:website.manage')
        ->name('testimonials.destroy');

    Route::get('/settings', [WebsiteContentController::class, 'editSettings'])->name('settings.edit');
    Route::put('/settings', [WebsiteContentController::class, 'updateSettings'])
        ->middleware('permission:website.manage')
        ->name('settings.update');
});
