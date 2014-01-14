<?php

namespace Pim\Bundle\MagentoConnectorBundle\Processor;

use Symfony\Component\Validator\Constraints as Assert;
use Oro\Bundle\BatchBundle\Item\ItemProcessorInterface;
use Oro\Bundle\BatchBundle\Item\AbstractConfigurableStepElement;
use Pim\Bundle\CatalogBundle\Manager\ChannelManager;
use Pim\Bundle\ImportExportBundle\Converter\MetricConverter;
use Oro\Bundle\BatchBundle\Item\InvalidItemException;

use Pim\Bundle\MagentoConnectorBundle\Guesser\MagentoWebserviceGuesser;
use Pim\Bundle\MagentoConnectorBundle\Guesser\MagentoNormalizerGuesser;
use Pim\Bundle\MagentoConnectorBundle\Validator\Constraints\HasValidCredentials;
use Pim\Bundle\MagentoConnectorBundle\Validator\Constraints\MagentoUrl;
use Pim\Bundle\MagentoConnectorBundle\Webservice\MagentoSoapClientParameters;
use Pim\Bundle\MagentoConnectorBundle\Webservice\AttributeSetNotFoundException;

/**
 * Magento product processor
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 * @HasValidCredentials()
 */
abstract class AbstractMagentoProcessor extends AbstractConfigurableStepElement implements ItemProcessorInterface
{
    const MAGENTO_VISIBILITY_CATALOG_SEARCH = 4;

    /**
     * @var ChannelManager
     */
    protected $channelManager;

    /**
     * @var metricConverter
     */
    protected $metricConverter;

    /**
     * @var MagentoWebservice
     */
    protected $magentoWebservice;

    /**
     * @var MagentoWebserviceGuesser
     */
    protected $magentoWebserviceGuesser;

    /**
     * @var MagentoNormalizerGuesser
     */
    protected $magentoNormalizerGuesser;

    /**
     * @Assert\NotBlank(groups={"Execution"})
     */
    protected $soapUsername;

    /**
     * @Assert\NotBlank(groups={"Execution"})
     */
    protected $soapApiKey;

    /**
     * @Assert\NotBlank(groups={"Execution"})
     * @Assert\Url(groups={"Execution"})
     * @MagentoUrl(groups={"Execution"})
     */
    protected $soapUrl;

    /**
     * @Assert\NotBlank(groups={"Execution"})
     */
    protected $channel;

    /**
     * @Assert\NotBlank(groups={"Execution"})
     */
    protected $currency;

    /**
     * @var boolean
     */
    protected $enabled;

    /**
     * @var integer
     */
    protected $visibility = self::MAGENTO_VISIBILITY_CATALOG_SEARCH;

    /**
     * @Assert\NotBlank(groups={"Execution"})
     */
    protected $defaultLocale;

    /**
     * @Assert\NotBlank(groups={"Execution"})
     */
    protected $website = 'base';

    /**
     * @var string
     */
    protected $storeViewMapping = '';

    /**
     * @var MagentoSoapClientParameters
     */
    protected $clientParameters;

    /**
     * @var array
     */
    protected $globalContext;

    /**
     * @var ProductNormalizer
     */
    protected $productNormalizer;

    /**
     * @param ChannelManager           $channelManager
     * @param MagentoWebserviceGuesser $magentoWebserviceGuesser
     * @param ProductNormalizerGuesser $magentoNormalizerGuesser
     * @param MetricConverter          $metricConverter
     */
    public function __construct(
        ChannelManager $channelManager,
        MagentoWebserviceGuesser $magentoWebserviceGuesser,
        MagentoNormalizerGuesser $magentoNormalizerGuesser,
        MetricConverter $metricConverter
    ) {
        $this->channelManager           = $channelManager;
        $this->magentoWebserviceGuesser = $magentoWebserviceGuesser;
        $this->magentoNormalizerGuesser = $magentoNormalizerGuesser;
        $this->metricConverter          = $metricConverter;
    }

    /**
     * get soapUsername
     *
     * @return string Soap mangeto soapUsername
     */
    public function getSoapUsername()
    {
        return $this->soapUsername;
    }

    /**
     * Set soapUsername
     *
     * @param string $soapUsername Soap mangeto soapUsername
     *
     * @return AbstractMagentoProcessor
     */
    public function setSoapUsername($soapUsername)
    {
        $this->soapUsername = $soapUsername;

        return $this;
    }

    /**
     * get soapApiKey
     *
     * @return string Soap mangeto soapApiKey
     */
    public function getSoapApiKey()
    {
        return $this->soapApiKey;
    }

    /**
     * Set soapApiKey
     *
     * @param string $soapApiKey Soap mangeto soapApiKey
     *
     * @return AbstractMagentoProcessor
     */
    public function setSoapApiKey($soapApiKey)
    {
        $this->soapApiKey = $soapApiKey;

        return $this;
    }

    /**
     * get soapUrl
     *
     * @return string mangeto soap url
     */
    public function getSoapUrl()
    {
        return $this->soapUrl;
    }

    /**
     * Set soapUrl
     *
     * @param string $soapUrl mangeto soap url
     *
     * @return AbstractMagentoProcessor
     */
    public function setSoapUrl($soapUrl)
    {
        $this->soapUrl = $soapUrl;

        return $this;
    }

    /**
     * get channel
     *
     * @return string channel
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * Set channel
     *
     * @param string $channel channel
     *
     * @return AbstractMagentoProcessor
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * get currency
     *
     * @return string currency
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Set currency
     *
     * @param string $currency currency
     *
     * @return AbstractMagentoProcessor
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * get enabled
     *
     * @return string enabled
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * Set enabled
     *
     * @param string $enabled enabled
     *
     * @return AbstractMagentoProcessor
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * get visibility
     *
     * @return string visibility
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * Set visibility
     *
     * @param string $visibility visibility
     *
     * @return AbstractMagentoProcessor
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * get defaultLocale
     *
     * @return string defaultLocale
     */
    public function getDefaultLocale()
    {
        return $this->defaultLocale;
    }

    /**
     * Set defaultLocale
     *
     * @param string $defaultLocale defaultLocale
     *
     * @return AbstractMagentoProcessor
     */
    public function setDefaultLocale($defaultLocale)
    {
        $this->defaultLocale = $defaultLocale;

        return $this;
    }

    /**
     * get website
     *
     * @return string website
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * Set website
     *
     * @param string $website website
     *
     * @return AbstractMagentoProcessor
     */
    public function setWebsite($website)
    {
        $this->website = $website;

        return $this;
    }

    /**
     * get storeViewMapping
     *
     * @return string storeViewMapping
     */
    public function getStoreViewMapping()
    {
        return $this->storeViewMapping;
    }

    /**
     * Set storeViewMapping
     *
     * @param string $storeViewMapping storeViewMapping
     *
     * @return AbstractMagentoProcessor
     */
    public function setStoreViewMapping($storeViewMapping)
    {
        $this->storeViewMapping = $storeViewMapping;

        return $this;
    }

    /**
     * Get computed storeView mapping (string to array)
     * @return array
     */
    protected function getComputedStoreViewMapping()
    {
        $computedStoreViewMapping = array();

        foreach (explode(chr(10), $this->storeViewMapping) as $line) {
            $computedStoreViewMapping[] = explode(':', $line);
        }

        return $computedStoreViewMapping;
    }

    /**
     * Get the magento soap client parameters
     *
     * @return MagentoSoapClientParameters
     */
    protected function getClientParameters()
    {
        if (!$this->clientParameters) {
            $this->clientParameters = new MagentoSoapClientParameters(
                $this->soapUsername,
                $this->soapApiKey,
                $this->soapUrl
            );
        }

        return $this->clientParameters;
    }

    /**
     * Get the attribute set id for the given family code
     *
     * @param string $familyCode
     * @param mixed  $relatedItem
     *
     * @throws InvalidItemException If The attribute set doesn't exist on Mangento
     * @return integer
     */
    protected function getAttributeSetId($familyCode, $relatedItem)
    {
        try {
            return $this->magentoWebservice
                ->getAttributeSetId(
                    $familyCode
                );
        } catch (AttributeSetNotFoundException $e) {
            throw new InvalidItemException($e->getMessage(), array($relatedItem));
        }
    }

    /**
     * Function called before all process
     */
    protected function beforeProcess()
    {
        $this->productNormalizer = $this->magentoNormalizerGuesser->getProductNormalizer(
            $this->getClientParameters(),
            $this->enabled,
            $this->visibility,
            $this->currency
        );

        $this->magentoWebservice = $this->magentoWebserviceGuesser->getWebservice($this->getClientParameters());


        $magentoStoreViews        = $this->magentoWebservice->getStoreViewsList();
        $magentoAttributes        = $this->magentoWebservice->getAllAttributes();
        $magentoAttributesOptions = $this->magentoWebservice->getAllAttributesOptions();

        $this->globalContext = array(
            'defaultLocale'            => $this->defaultLocale,
            'channel'                  => $this->channel,
            'currency'                 => $this->currency,
            'website'                  => $this->website,
            'magentoStoreViews'        => $magentoStoreViews,
            'magentoAttributes'        => $magentoAttributes,
            'magentoAttributesOptions' => $magentoAttributesOptions,
            'storeViewMapping'         => $this->getComputedStoreViewMapping(),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigurationFields()
    {
        return array(
            'soapUsername' => array(
                'options' => array(
                    'required' => true
                )
            ),
            'soapApiKey'   => array(
                //Should be remplaced by a password formType but who doesn't
                //empty the field at each edit
                'type'    => 'text',
                'options' => array(
                    'required' => true
                )
            ),
            'soapUrl' => array(
                'options' => array(
                    'required' => true
                )
            ),
            'channel' => array(
                'type'    => 'choice',
                'options' => array(
                    'choices'  => $this->channelManager->getChannelChoices(),
                    'required' => true
                )
            ),
            'defaultLocale' => array(
                //Should be fixed to display only active locale on the selected
                //channel
                'type'    => 'text',
                'options' => array(
                    'required' => true
                )
            ),
            'website' => array(
                'type'    => 'text',
                'options' => array(
                    'required' => true
                )
            ),
            'enabled' => array(
                'type'    => 'switch',
                'options' => array(
                    'required' => true
                )
            ),
            'visibility' => array(
                'type'    => 'text',
                'options' => array(
                    'required' => true
                )
            ),
            'currency' => array(
                'type'    => 'text',
                'options' => array(
                    'required' => true
                )
            ),
            'storeViewMapping' => array(
                'type'    => 'textarea',
                'options' => array(
                    'required' => false
                )
            )
        );
    }
}