<?php

declare(strict_types=1);

namespace porthd\sitemasterdataextend\Utility;

use porthd\sitemasterdata\Utility\SiteMasterdataDefinitions;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Decorates SiteMasterdataDefinitions from EXT:sitemasterdata and adds the
 * additional fields defined in this extension's settings.definitions.yaml.
 *
 * Symfony DI replaces the original service with this decorator so that
 * SiteMasterdataProcessor (frontend) and InjectSiteMasterdataPlaceholdersListener
 * (CKEditor) automatically receive the extended field list.
 *
 * Registered in Configuration/Services.yaml via `decorates`.
 * Calls parent::getAll() via PHP inheritance – no `.inner` service injection needed.
 */
class ExtendedSiteMasterdataDefinitions extends SiteMasterdataDefinitions
{
    /**
     * Returns all master data definitions: base extension fields first,
     * then the additional fields defined by this extension.
     *
     * @param string $filePath  Optional path – for unit tests with fixtures.
     * @return array<string, string>
     */
    public function getAll(string $filePath = ''): array
    {
        $base  = parent::getAll($filePath);
        $extra = $this->loadOwnDefinitions();

        return array_merge($base, $extra);
    }

    /**
     * Reads this extension's settings.definitions.yaml and returns all
     * entries whose key starts with 'sitemasterdata'.
     *
     * @return array<string, string>
     */
    private function loadOwnDefinitions(): array
    {
        $path = ExtensionManagementUtility::extPath('sitemasterdata_extend')
            . 'Configuration/Sets/SiteMasterdataExtend/settings.definitions.yaml';

        if (!is_file($path)) {
            return [];
        }

        $data     = Yaml::parseFile($path);
        $settings = $data['settings'] ?? [];

        $result = [];
        foreach ($settings as $key => $definition) {
            if (str_starts_with((string)$key, 'sitemasterdata')) {
                $result[(string)$key] = (string)($definition['label'] ?? $key);
            }
        }

        return $result;
    }
}
