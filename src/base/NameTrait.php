<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use Craft;
use craft\nameparsing\CustomLanguage;
use TheIconic\NameParser\Language\English;
use TheIconic\NameParser\Language\German;
use TheIconic\NameParser\Parser as NameParser;

/**
 * NameTrait implements the common properties for entities with full/first/last names.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
trait NameTrait
{
    /**
     * @var string|null Full name
     * @since 4.0.0
     */
    public ?string $fullName = null;

    /**
     * @var string|null First name
     */
    public ?string $firstName = null;

    /**
     * @var string|null Last name
     */
    public ?string $lastName = null;

    /**
     * Normalizes the name properties.
     */
    protected function normalizeNames(): void
    {
        $properties = ['fullName', 'firstName', 'lastName'];

        foreach ($properties as $property) {
            if (isset($this->$property) && trim($this->$property) === '') {
                $this->$property = null;
            }
        }
    }

    /**
     * Parses `fullName` if set, or sets it based on `firstName` and `lastName`.
     */
    protected function prepareNamesForSave(): void
    {
        if ($this->fullName !== null) {
            $generalConfig = Craft::$app->getConfig()->getGeneral();
            $languages = [
                // Load our custom language file first so config settings can override the defaults
                new CustomLanguage(
                    $generalConfig->extraNameSuffixes,
                    $generalConfig->extraNameSalutations,
                    $generalConfig->extraLastNamePrefixes,
                ),
                new English(),
                new German(),
            ];
            $name = (new NameParser($languages))->parse($this->fullName);
            $this->firstName = $name->getFirstname() ?: null;
            $this->lastName = $name->getLastname() ?: null;

            // Re-extract the first and last names from the full name to ensure casing doesn't change
            // see https://github.com/craftcms/cms/issues/14723
            if ($this->firstName !== null) {
                $firstNameOffset = mb_stripos($this->fullName, $this->firstName);
                if ($firstNameOffset !== false) {
                    $this->firstName = mb_substr($this->fullName, $firstNameOffset, mb_strlen($this->firstName));
                }
            }
            if ($this->lastName !== null) {
                $lastNameOffset = mb_stripos($this->fullName, $this->lastName,
                    isset($firstNameOffset) && $firstNameOffset !== false
                        ? $firstNameOffset + mb_strlen($this->firstName) : 0,
                );
                if ($lastNameOffset !== false) {
                    $this->lastName = mb_substr($this->fullName, $lastNameOffset, mb_strlen($this->lastName));
                }
            }
        } elseif ($this->firstName !== null || $this->lastName !== null) {
            $this->fullName = trim("$this->firstName $this->lastName") ?: null;
        }
    }
}
