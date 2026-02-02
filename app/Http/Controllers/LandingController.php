<?php

namespace App\Http\Controllers;

use App\Models\LandingContent;
use App\Models\LandingFaq;
use App\Models\LandingPackage;
use App\Models\LandingService;
use App\Models\LandingSlider;
use App\Models\LandingTestimonial;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

class LandingController extends Controller
{
    /**
     * Display the landing page
     */
    public function index()
    {
        $settings = SiteSetting::all()->pluck('value', 'key')->toArray();
        $sliders = LandingSlider::active()->orderBy('order')->get();
        $services = LandingService::active()->orderBy('order')->get();
        $packages = LandingPackage::active()->orderBy('order')->get();
        $testimonials = LandingTestimonial::active()->orderBy('order')->get();
        $faqs = LandingFaq::active()->orderBy('order')->get();
        
        // Get all active landing contents grouped by section
        $landingContents = LandingContent::active()->orderBy('order')->get();
        $contents = [];
        foreach ($landingContents as $item) {
            $contents[$item->section][$item->key] = $item;
        }

        return view('landing.index', compact(
            'settings',
            'sliders',
            'services',
            'packages',
            'testimonials',
            'faqs',
            'contents'
        ));
    }
}
