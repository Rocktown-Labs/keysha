<?php

namespace App\Crypto;

class ProviderRegistry
{
    public const PROVIDERS = [
        'custom' => ['name' => 'Custom', 'url' => null, 'icon' => 'key'],
        'aws' => ['name' => 'AWS', 'url' => 'https://aws.amazon.com/console', 'icon' => 'cloud'],
        'cloudflare' => ['name' => 'Cloudflare', 'url' => 'https://dash.cloudflare.com', 'icon' => 'globe-alt'],
        'github' => ['name' => 'GitHub', 'url' => 'https://github.com', 'icon' => 'code-bracket'],
        'google' => ['name' => 'Google Cloud', 'url' => 'https://console.cloud.google.com', 'icon' => 'sparkles'],
        'resend' => ['name' => 'Resend', 'url' => 'https://resend.com/api-keys', 'icon' => 'envelope'],
        'stripe' => ['name' => 'Stripe', 'url' => 'https://dashboard.stripe.com/apikeys', 'icon' => 'credit-card'],
        'upstash' => ['name' => 'Upstash', 'url' => 'https://console.upstash.com', 'icon' => 'server'],
        'vercel' => ['name' => 'Vercel', 'url' => 'https://vercel.com/dashboard', 'icon' => 'triangle'],
        'neon' => ['name' => 'Neon', 'url' => 'https://console.neon.tech', 'icon' => 'database'],
        'supabase' => ['name' => 'Supabase', 'url' => 'https://supabase.com/dashboard', 'icon' => 'bolt'],
    ];

    public static function detectProvider(string $key): string
    {
        $upperKey = strtoupper($key);

        if (str_starts_with($upperKey, 'STRIPE_')) {
            return 'stripe';
        }
        if (str_starts_with($upperKey, 'RESEND_')) {
            return 'resend';
        }
        if (str_starts_with($upperKey, 'GOOGLE_') || str_starts_with($upperKey, 'GCP_')) {
            return 'google';
        }
        if (str_starts_with($upperKey, 'AWS_')) {
            return 'aws';
        }
        if (str_starts_with($upperKey, 'CLOUDFLARE_') || str_starts_with($upperKey, 'CF_')) {
            return 'cloudflare';
        }
        if (str_starts_with($upperKey, 'GITHUB_') || str_starts_with($upperKey, 'GH_')) {
            return 'github';
        }
        if (str_starts_with($upperKey, 'UPSTASH_')) {
            return 'upstash';
        }
        if (str_starts_with($upperKey, 'VERCEL_')) {
            return 'vercel';
        }
        if (str_starts_with($upperKey, 'NEON_')) {
            return 'neon';
        }
        if (str_starts_with($upperKey, 'SUPABASE_')) {
            return 'supabase';
        }

        return 'custom';
    }

    public static function classifyKey(string $key): string
    {
        $upperKey = strtoupper($key);

        $configSuffixes = ['_CLIENT_ID', '_PRICE_ID', '_REGION', '_URL', '_HOST', '_PORT', '_ZONE_ID', '_ACCOUNT_ID'];
        foreach ($configSuffixes as $suffix) {
            if (str_ends_with($upperKey, $suffix)) {
                return 'config';
            }
        }

        if (str_starts_with($upperKey, 'PUBLIC_') || str_starts_with($upperKey, 'NEXT_PUBLIC_') || str_starts_with($upperKey, 'VITE_')) {
            return 'config';
        }

        return 'secret';
    }
}
