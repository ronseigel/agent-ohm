<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category   Mage
 * @package    Mage_Cms
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_Cms_Controller_Router extends Mage_Core_Controller_Varien_Router_Abstract
{
    public function initControllerRouters($observer)
    {
        $front = $observer->getEvent()->getFront();

        $cms = new Mage_Cms_Controller_Router();
        $front->addRouter('cms', $cms);
    }

    public function match(Zend_Controller_Request_Http $request)
    {
        if (!AO::isInstalled()) {
            AO::app()->getFrontController()->getResponse()
                ->setRedirect(AO::getUrl('install'))
                ->sendResponse();
            exit;
        }

        $identifier = trim($request->getPathInfo(), '/');

        $page = AO::getModel('cms/page');
        $pageId = $page->checkIdentifier($identifier, AO::app()->getStore()->getId());
        if (!$pageId) {
            return false;
        }

        $request->setModuleName(isset($d[0]) ? $d[0] : 'cms')
            ->setControllerName(isset($d[1]) ? $d[1] : 'page')
            ->setActionName(isset($d[2]) ? $d[2] : 'view')
            ->setParam('page_id', $pageId);
		$request->setAlias(
			Mage_Core_Model_Url_Rewrite::REWRITE_REQUEST_PATH_ALIAS,
			$identifier
		);
        return true;
    }
}