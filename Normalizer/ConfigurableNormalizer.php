<?php

namespace Pim\Bundle\MagentoConnectorBundle\Normalizer;

use Pim\Bundle\CatalogBundle\Entity\Group;
use Pim\Bundle\CatalogBundle\Manager\ChannelManager;
use Pim\Bundle\MagentoConnectorBundle\Webservice\Webservice;
use Pim\Bundle\MagentoConnectorBundle\Manager\PriceMappingManager;
use Pim\Bundle\MagentoConnectorBundle\Manager\ComputedPriceNotMatchedException;
use Pim\Bundle\MagentoConnectorBundle\Normalizer\Exception\InvalidPriceMappingException;
use Pim\Bundle\MagentoConnectorBundle\Mapper\MappingCollection;

/**
 * A normalizer to transform a group entity into an array
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ConfigurableNormalizer extends AbstractNormalizer
{
    const BASE_PRICE      = 'price';
    const PRICE_CHANGES   = 'price_changes';
    const ASSOCIATED_SKUS = 'associated_skus';

    /**
     * @var ProductNormalizer
     */
    protected $productNormalizer;

    /**
     * Constructor
     * @param ChannelManager      $channelManager
     * @param ProductNormalizer   $productNormalizer
     * @param PriceMappingManager $priceMappingManager
     */
    public function __construct(
        ChannelManager $channelManager,
        ProductNormalizer $productNormalizer,
        PriceMappingManager $priceMappingManager
    ) {
        parent::__construct($channelManager);

        $this->productNormalizer   = $productNormalizer;
        $this->priceMappingManager = $priceMappingManager;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = array())
    {
        $group    = $object['group'];
        $products = $object['products'];

        $sku = sprintf(Webservice::CONFIGURABLE_IDENTIFIER_PATTERN, $group->getCode());

        $processedItem[Webservice::SOAP_DEFAULT_STORE_VIEW] = $this->getDefaultConfigurable(
            $group,
            $sku,
            $context['attributeSetId'],
            $products,
            $context['magentoAttributes'],
            $context['magentoAttributesOptions'],
            $context['defaultLocale'],
            $context['website'],
            $context['channel'],
            $context['categoryMapping'],
            $context['attributeMapping'],
            $context['create']
        );

        $images = $this->productNormalizer->getNormalizedImages($products[0], $sku);

        if (count($images) > 0) {
            $processedItem[Webservice::IMAGES] = $images;
        }

        //For each storeview, we update the group only with localized attributes
        foreach ($this->getPimLocales($context['channel']) as $locale) {
            $storeView = $this->getStoreViewForLocale(
                $locale->getCode(),
                $context['magentoStoreViews'],
                $context['storeViewMapping']
            );

            //If a locale for this storeview exist in PIM, we create a translated group in this locale
            if ($storeView) {
                $values = $this->productNormalizer->getValues(
                    $products[0],
                    $context['magentoAttributes'],
                    $context['magentoAttributesOptions'],
                    $locale->getCode(),
                    $context['channel'],
                    $context['categoryMapping'],
                    $context['attributeMapping'],
                    true
                );

                $processedItem[$storeView['code']] = array(
                    $sku,
                    $values,
                    $storeView['code']
                );
            } else {
                if ($locale->getCode() !== $context['defaultLocale']) {
                    $this->localeNotFound($locale);
                }
            }
        }

        return $processedItem;
    }

    /**
     * Get default configurable
     * @param Group             $group
     * @param string            $sku
     * @param int               $attributeSetId
     * @param array             $products
     * @param array             $magentoAttributes
     * @param array             $magentoAttributesOptions
     * @param string            $locale
     * @param string            $website
     * @param string            $channel
     * @param MappingCollection $categoryMapping
     * @param MappingCollection $attributeMapping
     * @param bool              $create
     *
     * @return array
     */
    protected function getDefaultConfigurable(
        Group $group,
        $sku,
        $attributeSetId,
        $products,
        $magentoAttributes,
        $magentoAttributesOptions,
        $locale,
        $website,
        $channel,
        MappingCollection $categoryMapping,
        MappingCollection $attributeMapping,
        $create
    ) {
        $priceMapping = $this->priceMappingManager->getPriceMapping($group, $products, $attributeMapping);

        try {
            $this->priceMappingManager->validatePriceMapping(
                $products,
                $priceMapping[self::PRICE_CHANGES],
                $priceMapping[self::BASE_PRICE],
                $attributeMapping
            );
        } catch (ComputedPriceNotMatchedException $e) {
            throw new InvalidPriceMappingException(
                sprintf(
                    'Price mapping cannot be automatically computed. This might be because an associated product has ' .
                    'an inconsistant price regarding the other products of the variant group. %s',
                    $e->getMessage()
                )
            );
        }

        $associatedSkus = $this->getProductsSkus($products);

        $defaultProduct = $products[0];

        $defaultProductValues = $this->productNormalizer->getValues(
            $defaultProduct,
            $magentoAttributes,
            $magentoAttributesOptions,
            $locale,
            $channel,
            $categoryMapping,
            $attributeMapping,
            false
        );

        $defaultConfigurableValues = array_merge(
            $defaultProductValues,
            $priceMapping,
            array(self::ASSOCIATED_SKUS => $associatedSkus)
        );

        $defaultConfigurableValues['websites'] = array($website);

        if ($create) {
            $defaultConfigurable = $this->getNewConfigurable($sku, $defaultConfigurableValues, $attributeSetId);
        } else {
            $defaultConfigurable = $this->getUpdatedConfigurable($sku, $defaultConfigurableValues);
        }

        return $defaultConfigurable;
    }

    /**
     * Get the configurable for a new call
     * @param string $sku
     * @param array  $configurableValues
     * @param int    $attributeSetId
     *
     * @return array
     */
    protected function getNewConfigurable($sku, array $configurableValues, $attributeSetId)
    {
        return array(
            AbstractNormalizer::MAGENTO_CONFIGURABLE_PRODUCT_KEY,
            $attributeSetId,
            $sku,
            $configurableValues
        );
    }

    /**
     * Get the configurable for an update call
     * @param string $sku
     * @param array  $configurableValues
     *
     * @return array
     */
    protected function getUpdatedConfigurable($sku, array $configurableValues)
    {
        return array(
            $sku,
            $configurableValues
        );
    }

    /**
     * Get all products skus
     * @param array $products
     *
     * @return array
     */
    protected function getProductsSkus($products)
    {
        array_walk(
            $products,
            function (&$value) {
                $value = (string) $value->getIdentifier();
            }
        );

        return $products;
    }
}
