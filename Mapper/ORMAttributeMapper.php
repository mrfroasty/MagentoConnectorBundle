<?php

namespace Pim\Bundle\MagentoConnectorBundle\Mapper;

use Pim\Bundle\MagentoConnectorBundle\Manager\SimpleMappingManager;
use Pim\Bundle\MagentoConnectorBundle\Manager\AttributeManager;
use Pim\Bundle\MagentoConnectorBundle\Validator\Constraints\HasValidCredentialsValidator;

/**
 * Magento attribute mapper
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ORMAttributeMapper extends ORMMapper
{
    /**
     * @var AttributeManager
     */
    protected $attributeManager;

    /**
     * @param HasValidCredentialsValidator $hasValidCredentialsValidator
     * @param SimpleMappingManager         $simpleMappingManager
     * @param string                       $rootIdentifier
     * @param AttributeManager             $attributeManager
     */
    public function __construct(
        HasValidCredentialsValidator $hasValidCredentialsValidator,
        SimpleMappingManager $simpleMappingManager,
        $rootIdentifier,
        AttributeManager $attributeManager
    ) {
        parent::__construct($hasValidCredentialsValidator, $simpleMappingManager, $rootIdentifier);

        $this->attributeManager = $attributeManager;
    }

    /**
     * Get all sources
     * @return array
     */
    public function getAllSources()
    {
        $sources = array();

        if ($this->isValid()) {
            $attributes = $this->attributeManager->getAttributes();

            foreach ($attributes as $attribute) {
                $sources[] = array('id' => $attribute->getCode(), 'text' => $attribute->getCode());
            }
        }

        return $sources;
    }
}
