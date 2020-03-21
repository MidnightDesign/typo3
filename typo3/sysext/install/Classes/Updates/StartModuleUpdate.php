<?php
namespace TYPO3\CMS\Install\Updates;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Update backend user setting startModule if set to "help_aboutmodules"
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class StartModuleUpdate implements UpgradeWizardInterface
{
    /**
     * @return string Unique identifier of this updater
     */
    public function getIdentifier(): string
    {
        return 'startModuleUpdate';
    }

    /**
     * @return string Title of this updater
     */
    public function getTitle(): string
    {
        return 'Update backend user setting "startModule"';
    }

    /**
     * @return string Longer description of this updater
     */
    public function getDescription(): string
    {
        return 'The backend user setting startModule is changed for the extensions about/aboutmodules.'
            . ' Update all backend users that use ext:aboutmodules as startModule.';
    }

    /**
     * Checks if an update is needed
     *
     * @return bool Whether an update is needed (TRUE) or not (FALSE)
     */
    public function updateNecessary(): bool
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_users');
        $queryBuilder->getRestrictions()->removeAll();
        $statement = $queryBuilder->select('uid', 'uc')->from('be_users')->execute();
        $needsExecution = false;
        while ($backendUser = $statement->fetch()) {
            if ($backendUser['uc'] !== null) {
                $userConfig = unserialize($backendUser['uc'], ['allowed_classes' => false]);
                if ($userConfig['startModule'] === 'help_aboutmodules'
                    || $userConfig['startModule'] === 'help_AboutmodulesAboutmodules'
                    || $userConfig['startModule'] === 'help_AboutAboutmodules'
                ) {
                    $needsExecution = true;
                    break;
                }
            }
        }
        return $needsExecution;
    }

    /**
     * @return string[] All new fields and tables must exist
     */
    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class
        ];
    }

    /**
     * Performs the database update if backend user's startmodule is
     * "help_aboutmodules" or "help_AboutmodulesAboutmodules"
     *
     * @return bool
     */
    public function executeUpdate(): bool
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_users');
        $queryBuilder->getRestrictions()->removeAll();
        $statement = $queryBuilder->select('uid', 'uc')->from('be_users')->execute();
        while ($backendUser = $statement->fetch()) {
            if ($backendUser['uc'] !== null) {
                $userConfig = unserialize($backendUser['uc'], ['allowed_classes' => false]);
                if ($userConfig['startModule'] === 'help_aboutmodules'
                    || $userConfig['startModule'] === 'help_AboutmodulesAboutmodules'
                    || $userConfig['startModule'] === 'help_AboutAboutmodules'
                ) {
                    $userConfig['startModule'] = 'help_AboutAbout';
                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_users');
                    $queryBuilder->getRestrictions()->removeAll();
                    $queryBuilder->update('be_users')
                        ->where(
                            $queryBuilder->expr()->eq(
                                'uid',
                                $queryBuilder->createNamedParameter($backendUser['uid'], \PDO::PARAM_INT)
                            )
                        )
                        // Manual quoting and false as third parameter to have the final
                        // value in $databaseQueries and not a statement placeholder
                        ->set('uc', serialize($userConfig))
                        ->execute();
                }
            }
        }
        return true;
    }
}