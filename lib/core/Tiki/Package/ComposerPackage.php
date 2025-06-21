<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Package;

/**
 * Class with most of the operations needed for a Composer Package
 */
class ComposerPackage
{
    public const STATE_ACTIVE = 'active';
    public const STATE_DEPRECATED = 'deprecated';
    public const STATE_REPLACED = 'replaced';

    protected string $state;
    protected string $key;
    protected string $name;
    protected string $requiredVersion;
    protected string $licence;
    protected string $licenceUrl;
    protected array $requiredBy;
    protected array $replacedBy;
    protected array $scripts;
    protected array $actions;
    protected array $metadata;
    protected array|null $repository;

    /**
     * Sets the information related with this package, intended to be used in the constructor of the child class
     *
     * @param string $key
     * @param string $name
     * @param string $requiredVersion
     * @param string $licence
     * @param string $licenceUrl
     * @param array $requiredBy
     * @param array $replacedBy
     * @param array $scripts
     * @param array $actions
     * @param array $repository
     * @param string $state
     * @param array $metadata
     */
    public function __construct(
        string $key,
        string $name,
        string $requiredVersion,
        string $licence,
        string $licenceUrl,
        array $requiredBy,
        array $replacedBy = [],
        array $scripts = [],
        array $actions = [],
        array|null $repository = null,
        string $state = self::STATE_ACTIVE,
        array $metadata = []
    ) {
        $this->key = $key;
        $this->name = $name;
        $this->requiredVersion = $requiredVersion;
        $this->licence = $licence;
        $this->licenceUrl = $licenceUrl;
        $this->requiredBy = $requiredBy;
        $this->replacedBy = $replacedBy;
        $this->scripts = $scripts;
        $this->actions = $actions;
        $this->state = $state;
        $this->metadata = $metadata;
        $this->repository = $repository;
    }

    /**
     * Return package information as Array
     *
     * @return array
     */
    public function getAsArray()
    {
        return [
            'key' => $this->getKey(),
            'name' => $this->name,
            'requiredVersion' => $this->requiredVersion,
            'licence' => $this->licence,
            'licenceUrl' => $this->licenceUrl,
            'requiredBy' => $this->requiredBy,
            'replacedBy' => $this->replacedBy,
            'scripts' => $this->scripts,
            'actions' => $this->actions,
            'state' => $this->state,
            'metadata' => $this->metadata,
            'repository' => $this->repository,
        ];
    }

    /**
     * Return the key that represents this package
     * that correspond to the class name without namespace
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Returns the script property
     *
     * @return array
     */
    public function getScripts()
    {
        return $this->scripts;
    }

    /**
     * Returns the actions property
     *
     * @return array
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * Returns the package name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the package version
     *
     * @return string
     */
    public function getRequiredVersion()
    {
        return $this->requiredVersion;
    }

    /**
     * Returns the package licence
     *
     * @return string
     */
    public function getLicence()
    {
        return $this->licence;
    }

    /**
     * Returns the link to the package url
     *
     * @return string
     */
    public function getLicenceUrl()
    {
        return $this->licenceUrl;
    }

    /**
     * Returns the list of features that requires this package
     *
     * @return array
     */
    public function getRequiredBy()
    {
        return $this->requiredBy;
    }

    /**
     * Returns the state of the package
     *
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Returns the list of package(s) that replaced this package
     *
     * @return array
     */
    public function getReplacedBy()
    {
        return $this->replacedBy;
    }

    /**
     * Returns all package informations
     *
     * @return array
     */
    public function getMetaData()
    {
        return $this->metadata;
    }

    /**
     * Returns the repository information
     *
     * @return array
     */
    public function getRepository()
    {
        return $this->repository;
    }
}
