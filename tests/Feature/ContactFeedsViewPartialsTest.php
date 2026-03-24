<?php

namespace Tests\Feature;

use App\ContactFeed;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ContactFeedsViewPartialsTest extends TestCase
{
    public function test_feeds_tab_partial_renders_provider_selector_and_action_buttons(): void
    {
        $html = view('contact.partials.feeds_tab')->render();

        $this->assertStringContainsString('id="contact_feeds_provider"', $html);
        $this->assertStringContainsString('id="load_contact_feeds_btn"', $html);
        $this->assertStringContainsString('id="update_contact_feeds_btn"', $html);
        $this->assertStringContainsString('id="contact_feeds_div"', $html);
    }

    public function test_contact_feeds_list_partial_renders_empty_state(): void
    {
        $html = view('contact.partials.contact_feeds_list', [
            'feeds' => collect(),
            'provider' => 'google',
        ])->render();

        $this->assertStringContainsString('No feed records found', $html);
    }

    public function test_contact_feeds_list_partial_renders_feed_cards(): void
    {
        $feed = new ContactFeed([
            'title' => 'Acme Latest Update',
            'snippet' => 'Acme announced a new product launch.',
            'canonical_url' => 'https://example.com/news/acme',
            'source_name' => 'example.com',
            'published_at' => now(),
            'fetched_at' => now(),
        ]);
        $feed->published_at = now();
        $feed->fetched_at = now();

        $html = view('contact.partials.contact_feeds_list', [
            'feeds' => new Collection([$feed]),
            'provider' => 'google',
        ])->render();

        $this->assertStringContainsString('Acme Latest Update', $html);
        $this->assertStringContainsString('Open Source', $html);
        $this->assertStringContainsString('badge-light-info', $html);
    }
}
