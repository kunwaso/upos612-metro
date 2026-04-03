<?php

namespace Tests\Feature;

use Tests\TestCase;

class EntrypointMapsTest extends TestCase
{
    public function testPilotSidecarsExposeRequiredContractKeys(): void
    {
        $requiredKeys = [
            'kind',
            'title',
            'purpose',
            'triggers',
            'verified_paths',
            'route_prefixes',
            'search_keywords',
            'related_docs',
            'workflows',
            'edit_bundles',
            'dependencies',
            'tests',
            'verify_commands',
            'last_reviewed',
        ];

        foreach ([
            'index.json',
            'core-http-entry.json',
            'module-Aichat.json',
            'module-Projectauto.json',
            'module-VasAccounting.json',
        ] as $jsonFile) {
            $absolute = base_path('ai/entrypoints/generated/' . $jsonFile);
            $this->assertFileExists($absolute, 'Missing entrypoint sidecar: ' . $jsonFile);

            $decoded = json_decode((string) file_get_contents($absolute), true);
            $this->assertIsArray($decoded, 'Invalid entrypoint JSON: ' . $jsonFile);

            foreach ($requiredKeys as $key) {
                $this->assertArrayHasKey($key, $decoded, 'Missing key `' . $key . '` in ' . $jsonFile);
            }
        }
    }

    public function testPilotMarkdownMapsContainV2Sections(): void
    {
        $requiredSections = [
            '## Use when',
            '## Start here',
            '## Common edit bundles',
            '## Primary workflows',
            '## Shared dependencies',
            '## Tests / verify',
        ];

        foreach ([
            'core-http-entry.md',
            'module-Aichat.md',
            'module-Projectauto.md',
            'module-VasAccounting.md',
        ] as $markdownFile) {
            $absolute = base_path('ai/entrypoints/' . $markdownFile);
            $this->assertFileExists($absolute, 'Missing entrypoint markdown map: ' . $markdownFile);

            $content = (string) file_get_contents($absolute);
            foreach ($requiredSections as $section) {
                $this->assertStringContainsString(
                    $section,
                    $content,
                    'Missing section `' . $section . '` in ' . $markdownFile
                );
            }
        }
    }
}

