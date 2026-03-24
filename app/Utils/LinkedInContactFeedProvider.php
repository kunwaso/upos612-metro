<?php

namespace App\Utils;

use App\Contact;

class LinkedInContactFeedProvider implements ContactFeedProviderInterface
{
    /**
     * Placeholder provider for future implementation.
     *
     * @param \App\Contact $contact
     * @param array $options
     * @return array
     *
     * @throws \RuntimeException
     */
    public function search(Contact $contact, array $options = [])
    {
        throw new \RuntimeException('LinkedIn provider is not configured yet.');
    }
}
