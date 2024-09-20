<?php
declare(strict_types=1);

namespace Pentagonal\Neon\WHMCS\Addon\Interfaces;

interface HookInterface
{
    /**
     * WHMCS Hooks lists
     *
     * @link https://developers.whmcs.com/hooks/hook-index/
     * @var array{
     *     string: array{
     *         name: string,
     *         hooks: string[]
     *     }
     * } HOOKS
     * @final
     */
    public const HOOKS = [
        'invoices_quotes' => [
            'name' => 'Invoices and Quotes',
            'hooks' => [
                'AcceptQuote',
                'AddInvoiceLateFee',
                'AddInvoicePayment',
                'AddTransaction',
                'AfterInvoicingGenerateInvoiceItems',
                'CancelAndRefundOrder',
                'InvoiceCancelled',
                'InvoiceChangeGateway',
                'InvoiceCreated',
                'InvoiceCreation',
                'InvoiceCreationPreEmail',
                'InvoicePaid',
                'InvoicePaidPreEmail',
                'InvoicePaymentReminder',
                'InvoiceRefunded',
                'InvoiceSplit',
                'InvoiceUnpaid',
                'LogTransaction',
                'ManualRefund',
                'PreInvoiceAutomaticCancellation',
                'PreInvoicingGenerateInvoiceItems',
                'QuoteCreated',
                'QuoteStatusChange',
                'UpdateInvoiceTotal',
                'ViewInvoiceDetailsPage',
            ]
        ],
        'shopping_cart' => [
            'name' => 'Shopping Cart',
            'hooks' => [
                'AcceptOrder',
                'AddonFraud',
                'AfterCalculateCartTotals',
                'AfterFraudCheck',
                'AfterShoppingCartCheckout',
                'CancelOrder',
                'CartItemsTax',
                'CartSubdomainValidation',
                'CartTotalAdjustment',
                'DeleteOrder',
                'FraudCheckAwaitingUserInput',
                'FraudCheckFailed',
                'FraudCheckPassed',
                'FraudOrder',
                'OrderAddonPricingOverride',
                'OrderDomainPricingOverride',
                'OrderPaid',
                'OrderProductPricingOverride',
                'OrderProductUpgradeOverride',
                'OverrideOrderNumberGeneration',
                'PendingOrder',
                'PreCalculateCartTotals',
                'PreFraudCheck',
                'PreShoppingCartCheckout',
                'RunFraudCheck',
                'ShoppingCartCheckoutCompletePage',
                'ShoppingCartValidateCheckout',
                'ShoppingCartValidateDomain',
                'ShoppingCartValidateDomainsConfig',
                'ShoppingCartValidateProductUpdate',
            ]
        ],
        'service' => [
            'name' => 'Service',
            'hooks' => [
                'CancellationRequest',
                'PreServiceEdit',
                'ServiceDelete',
                'ServiceEdit',
                'ServiceRecurringCompleted',
            ]
        ],
        'module' => [
            'name' => 'Module',
            'hooks' => [
                'AfterModuleChangePackage',
                'AfterModuleChangePackageFailed',
                'AfterModuleChangePassword',
                'AfterModuleChangePasswordFailed',
                'AfterModuleCreate',
                'AfterModuleCreateFailed',
                'AfterModuleCustom',
                'AfterModuleCustomFailed',
                'AfterModuleDeprovisionAddOnFeature',
                'AfterModuleDeprovisionAddOnFeatureFailed',
                'AfterModuleProvisionAddOnFeature',
                'AfterModuleProvisionAddOnFeatureFailed',
                'AfterModuleSuspend',
                'AfterModuleSuspendAddOnFeature',
                'AfterModuleSuspendAddOnFeatureFailed',
                'AfterModuleSuspendFailed',
                'AfterModuleTerminate',
                'AfterModuleTerminateFailed',
                'AfterModuleUnsuspend',
                'AfterModuleUnsuspendAddOnFeature',
                'AfterModuleUnsuspendAddOnFeatureFailed',
                'AfterModuleUnsuspendFailed',
                'OverrideModuleUsernameGeneration',
                'PreModuleChangePackage',
                'PreModuleChangePassword',
                'PreModuleCreate',
                'PreModuleCustom',
                'PreModuleDeprovisionAddOnFeature',
                'PreModuleProvisionAddOnFeature',
                'PreModuleRenew',
                'PreModuleSuspend',
                'PreModuleSuspendAddOnFeature',
                'PreModuleTerminate',
                'PreModuleUnsuspend',
                'PreModuleUnsuspendAddOnFeature',
            ]
        ],
        'domain' => [
            'name' => 'Domain',
            'hooks' => [
                'DomainDelete',
                'DomainEdit',
                'DomainTransferCompleted',
                'DomainTransferFailed',
                'DomainValidation',
                'PreDomainRegister',
                'PreDomainTransfer',
                'PreRegistrarRegisterDomain',
                'PreRegistrarRenewDomain',
                'PreRegistrarTransferDomain',
                'TopLevelDomainAdd',
                'TopLevelDomainDelete',
                'TopLevelDomainPricingUpdate',
                'TopLevelDomainUpdate',
            ]
        ],
        'registrar_module' => [
            'name' => 'Registrar Module',
            'hooks' => [
                'AfterRegistrarGetContactDetails',
                'AfterRegistrarGetDNS',
                'AfterRegistrarGetEPPCode',
                'AfterRegistrarGetNameservers',
                'AfterRegistrarRegister',
                'AfterRegistrarRegistration',
                'AfterRegistrarRegistrationFailed',
                'AfterRegistrarRenew',
                'AfterRegistrarRenewal',
                'AfterRegistrarRenewalFailed',
                'AfterRegistrarRequestDelete',
                'AfterRegistrarSaveContactDetails',
                'AfterRegistrarSaveDNS',
                'AfterRegistrarSaveNameservers',
                'AfterRegistrarTransfer',
                'AfterRegistrarTransferFailed',
                'PreRegistrarGetContactDetails',
                'PreRegistrarGetDNS',
                'PreRegistrarGetEPPCode',
                'PreRegistrarGetNameservers',
                'PreRegistrarRequestDelete',
                'PreRegistrarSaveContactDetails',
                'PreRegistrarSaveDNS',
                'PreRegistrarSaveNameservers',
            ]
        ],
        'addon' => [
            'name' => 'Addon',
            'hooks' => [
                'AddonActivated',
                'AddonActivation',
                'AddonAdd',
                'AddonCancelled',
                'AddonConfig',
                'AddonConfigSave',
                'AddonDeleted',
                'AddonEdit',
                'AddonRenewal',
                'AddonSuspended',
                'AddonTerminated',
                'AddonUnsuspended',
                'AfterAddonUpgrade',
                'LicensingAddonReissue',
                'LicensingAddonVerify',
                'ProductAddonDelete',
            ]
        ],
        'client' => [
            'AfterClientMerge',
            'ClientAdd',
            'ClientAlert',
            'ClientChangePassword',
            'ClientClose',
            'ClientDelete',
            'ClientDetailsValidation',
            'ClientEdit',
            'PreDeleteClient',
        ],
        'user' => [
            'name' => 'User',
            'hooks' => [
                'UserAdd',
                'UserChangePassword',
                'UserEdit',
                'UserEmailVerificationComplete',
            ]
        ],
        'contact' => [
            'name' => 'Contact',
            'hooks' => [
                'ContactAdd',
                'ContactDelete',
                'ContactDetailsValidation',
                'ContactEdit',
            ]
        ],
        'products_services' => [
            'name' => 'Products and Services',
            'hooks' => [
                'AfterProductUpgrade',
                'ProductDelete',
                'ProductEdit',
                'ServerAdd',
                'ServerDelete',
                'ServerEdit',
            ]
        ],
        'ticket' => [
            'name' => 'Ticket',
            'hooks' => [
                'AdminAreaViewTicketPage',
                'AdminAreaViewTicketPageSidebar',
                'AdminSupportTicketPagePreTickets',
                'ClientAreaPageSubmitTicket',
                'ClientAreaPageSupportTickets',
                'ClientAreaPageViewTicket',
                'SubmitTicketAnswerSuggestions',
                'TicketAddNote',
                'TicketAdminReply',
                'TicketClose',
                'TicketDelete',
                'TicketDeleteReply',
                'TicketDepartmentChange',
                'TicketFlagged',
                'TicketMerge',
                'TicketOpen',
                'TicketOpenAdmin',
                'TicketOpenValidation',
                'TicketPiping',
                'TicketPriorityChange',
                'TicketSplit',
                'TicketStatusChange',
                'TicketSubjectChange',
                'TicketUserReply',
                'TransliterateTicketText',
            ]
        ],
        'support_tools' => [
            'name' => 'Support Tools',
            'hooks' => [
                'AnnouncementAdd',
                'AnnouncementEdit',
                'FileDownload',
                'NetworkIssueAdd',
                'NetworkIssueClose',
                'NetworkIssueDelete',
                'NetworkIssueEdit',
                'NetworkIssueReopen',
            ]
        ],
        'authentication' => [
            'name' => 'Authentication',
            'hooks' => [
                'ClientLoginShare',
                'UserLogin',
                'UserLogout',
            ]
        ],
        'client_area' => [
            'name' => 'Client Area Interface',
            'hooks' => [
                'ClientAreaDomainDetails',
                'ClientAreaHomepage',
                'ClientAreaHomepagePanels',
                'ClientAreaNavbars',
                'ClientAreaPage',
                'ClientAreaPageAddContact',
                'ClientAreaPageAddFunds',
                'ClientAreaPageAddonModule',
                'ClientAreaPageAffiliates',
                'ClientAreaPageAnnouncements',
                'ClientAreaPageBanned',
                'ClientAreaPageBulkDomainManagement',
                'ClientAreaPageCancellation',
                'ClientAreaPageCart',
                'ClientAreaPageChangePassword',
                'ClientAreaPageConfigureSSL',
                'ClientAreaPageContact',
                'ClientAreaPageContacts',
                'ClientAreaPageCreditCard',
                'ClientAreaPageCreditCardCheckout',
                'ClientAreaPageDomainAddons',
                'ClientAreaPageDomainContacts',
                'ClientAreaPageDomainDNSManagement',
                'ClientAreaPageDomainDetails',
                'ClientAreaPageDomainEPPCode',
                'ClientAreaPageDomainEmailForwarding',
                'ClientAreaPageDomainRegisterNameservers',
                'ClientAreaPageDomains',
                'ClientAreaPageDownloads',
                'ClientAreaPageEmails',
                'ClientAreaPageHome',
                'ClientAreaPageInvoices',
                'ClientAreaPageKnowledgebase',
                'ClientAreaPageLogin',
                'ClientAreaPageLogout',
                'ClientAreaPageMassPay',
                'ClientAreaPageNetworkIssues',
                'ClientAreaPagePasswordReset',
                'ClientAreaPageProductDetails',
                'ClientAreaPageProductsServices',
                'ClientAreaPageProfile',
                'ClientAreaPageQuotes',
                'ClientAreaPageRegister',
                'ClientAreaPageSecurity',
                'ClientAreaPageServerStatus',
                'ClientAreaPageUnsubscribe',
                'ClientAreaPageUpgrade',
                'ClientAreaPageViewEmail',
                'ClientAreaPageViewInvoice',
                'ClientAreaPageViewQuote',
                'ClientAreaPaymentMethods',
                'ClientAreaPrimaryNavbar',
                'ClientAreaPrimarySidebar',
                'ClientAreaProductDetails',
                'ClientAreaProductDetailsPreModuleTemplate',
                'ClientAreaRegister',
                'ClientAreaSecondaryNavbar',
                'ClientAreaSecondarySidebar',
                'ClientAreaSidebars',
            ]
        ],
        'admin_area' => [
            'name' => 'Admin Area',
            'hooks' => [
                'AdminAreaClientSummaryActionLinks',
                'AdminAreaClientSummaryPage',
                'AdminAreaPage',
                'AdminAreaViewQuotePage',
                'AdminClientDomainsTabFields',
                'AdminClientDomainsTabFieldsSave',
                'AdminClientFileUpload',
                'AdminClientProfileTabFields',
                'AdminClientProfileTabFieldsSave',
                'AdminClientServicesTabFields',
                'AdminClientServicesTabFieldsSave',
                'AdminHomepage',
                'AdminLogin',
                'AdminLogout',
                'AdminPredefinedAddons',
                'AdminProductConfigFields',
                'AdminProductConfigFieldsSave',
                'AdminServiceEdit',
                'AuthAdmin',
                'AuthAdminApi',
                'InvoiceCreationAdminArea',
                'PreAdminServiceEdit',
                'ViewOrderDetailsPage',
            ]
        ],
        'output' => [
            'name' => 'Output',
            'hooks' => [
                'AdminAreaFooterOutput',
                'AdminAreaHeadOutput',
                'AdminAreaHeaderOutput',
                'AdminInvoicesControlsOutput',
                'ClientAreaDomainDetailsOutput',
                'ClientAreaFooterOutput',
                'ClientAreaHeadOutput',
                'ClientAreaHeaderOutput',
                'ClientAreaProductDetailsOutput',
                'FormatDateForClientAreaOutput',
                'FormatDateTimeForClientAreaOutput',
                'ReportViewPostOutput',
                'ReportViewPreOutput',
                'ShoppingCartCheckoutOutput',
                'ShoppingCartConfigureProductAddonsOutput',
                'ShoppingCartViewCartOutput',
            ]
        ],
        'cron' => [
            'name' => 'Cron',
            'hooks' => [
                'AfterCronJob',
                'DailyCronJob',
                'DailyCronJobPreEmail',
                'PopEmailCollectionCronCompleted',
                'PostAutomationTask',
                'PreAutomationTask',
                'PreCronJob',
            ]
        ],
        'other' => [
            'name' => 'Other',
            'hooks' => [
                'AffiliateActivation',
                'AffiliateClickthru',
                'AffiliateCommission',
                'AffiliateWithdrawalRequest',
                'AfterConfigOptionsUpgrade',
                'CCUpdate',
                'CalcAffiliateCommission',
                'CustomFieldLoad',
                'CustomFieldSave',
                'EmailPreLog',
                'EmailPreSend',
                'EmailTplMergeFields',
                'FetchCurrencyExchangeRates',
                'IntelligentSearch',
                'LinkTracker',
                'LogActivity',
                'NotificationPreSend',
                'PayMethodMigration',
                'PreEmailSendReduceRecipients',
                'PreUpgradeCheckout',
                'PremiumPriceOverride',
                'PremiumPriceRecalculationOverride',
            ]
        ]
    ];

    /**
     * Hook constructor.
     *
     * @param HooksInterface $hooks
     */
    public function __construct(HooksInterface $hooks);

    /**
     * Get the service
     *
     * @return HooksInterface
     */
    public function getHooksService(): HooksInterface;

    /**
     * Hook name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Hook type
     *
     * @return string[]
     */
    public function getHooks(): array;

    /**
     * Get hook priority
     *
     * @return int
     */
    public function getPriority(): int;

    /**
     * @param $vars
     * @return mixed|array
     */
    public function run($vars = null);
}
