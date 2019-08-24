<?php

namespace Tygh\Addons\PaypalForMarketplaces;

use Tygh\Addons\PaypalForMarketplaces\Api\RequestorWrapper;
use Tygh\Payments\Addons\PaypalForMarketplaces\PaypalForMarketplaces;

class ProcessorFactory
{
    /** @var \Tygh\Addons\PaypalForMarketplaces\Api\RequestorWrapper */
    protected $requestor_wrapper;

    /** @var \Tygh\Database\Connection $db */
    protected $db;

    /** @var array $addon_settings */
    protected $addon_settings;

    /** @var \Tygh\Tools\Formatter $formatter */
    protected $formatter;

    /** @var \Tygh\Addons\PaypalForMarketplaces\OAuthHelper $oauth_helper */
    protected $oauth_helper;

    /**
     * Constructs payment method processor with default components by the payment method ID.
     *
     * @param int        $id             Payment method ID
     * @param array|null $processor_data Payment method configuration
     *
     * @return \Tygh\Payments\Addons\PaypalForMarketplaces\PaypalForMarketplaces
     */
    public function getByPayment($id, $processor_data = null)
    {
        $processor = new PaypalForMarketplaces($id, $processor_data);

        $processor->setDb($this->db);
        $processor->setAddonSettings($this->addon_settings);
        $processor->setFormatter($this->formatter);
        $processor->setOauthHelper($this->oauth_helper);

        if ($this->requestor_wrapper === null) {
            $this->requestor_wrapper = new RequestorWrapper(
                $processor->getPaymentId(),
                $processor->getProcessorParams(),
                $this->db
            );
        }

        $processor->setRequestorWrapper($this->requestor_wrapper);

        return $processor;
    }

    /**
     * Sets default API requestor wrapper.
     *
     * @param \Tygh\Addons\PaypalForMarketplaces\Api\RequestorWrapper $requestor_wrapper
     */
    public function setRequestorWrapper($requestor_wrapper)
    {
        $this->requestor_wrapper = $requestor_wrapper;
    }

    /**
     * Sets default database connection.
     *
     * @param \Tygh\Database\Connection $db
     */
    public function setDb($db)
    {
        $this->db = $db;
    }

    /**
     * Sets default add-on settings.
     *
     * @param array $addon_settings
     */
    public function setAddonSettings($addon_settings)
    {
        $this->addon_settings = $addon_settings;
    }

    /**
     * Sets default Formatter.
     *
     * @param \Tygh\Tools\Formatter $formatter
     */
    public function setFormatter($formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * Sets default OAuth helper.
     *
     * @param \Tygh\Addons\PaypalForMarketplaces\OAuthHelper $oauth_helper
     */
    public function setOAuthHelper(OAuthHelper $oauth_helper)
    {
        $this->oauth_helper = $oauth_helper;
    }
}