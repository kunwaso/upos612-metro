<?php

namespace App\Utils;

use App\Contact;

interface ContactFeedProviderInterface
{
    /**
     * Search external provider and return normalized feed records.
     *
     * @param \App\Contact $contact
     * @param array $options
     * @return array<int, array<string, mixed>>
     */
    public function search(Contact $contact, array $options = []);
}
