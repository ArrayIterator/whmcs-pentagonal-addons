<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Interfaces;

interface ServiceInterface
{
    /**
     * Default category
     */
    public const DEFAULT_CATEGORY = 'other';

    /**
     * The service categories
     */
    public const CATEGORIES = [
        'utility',
        'theme',
        'addon',
        'payment',
        'server',
        'security',
        'domain',
        'authentication',
        'seo',
        'marketing',
        'notification',
        'product',
        'service',
        'system',
        'other'
    ];

    /**
     * The service constructor
     *
     * @param ServicesInterface $services
     */
    public function __construct(ServicesInterface $services);

    /**
     * Get Services
     *
     * @return ServicesInterface
     */
    public function getServices(): ServicesInterface;

    /**
     * The service name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get service description
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Get category
     *
     * @return string
     * @see ServiceInterface::CATEGORIES
     */
    public function getCategory(): string;
}
