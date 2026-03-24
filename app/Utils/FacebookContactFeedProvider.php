<?php

namespace App\Utils;

use App\Contact;

class FacebookContactFeedProvider implements ContactFeedProviderInterface
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
        throw new \RuntimeException('Facebook provider is not configured yet.');
    }
}
