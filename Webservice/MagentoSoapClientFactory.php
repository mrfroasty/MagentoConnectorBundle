<?php

namespace Pim\Bundle\MagentoConnectorBundle\Webservice;

/**
 * A magento soap client factory
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MagentoSoapClientFactory
{
    public function getMagentoSoapClient(MagentoSoapClientParameters $magentoSoapClientParameters)
    {
        return new MagentoSoapClient($clientParameters);
    }
}