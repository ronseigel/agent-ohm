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
 * @package    Mage_Adminhtml
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Mage_Adminhtml_Model_Url extends Mage_Core_Model_Url
{
    /**
     * Secret key query param name
     */
    const SECRET_KEY_PARAM_NAME = 'key';

    /**
     * Retrieve is secure mode for ULR logic
     *
     * @return bool
     */
    public function getSecure()
    {
        if ($this->hasData('secure_is_forced')) {
            return $this->getData('secure');
        }
        return AO::getStoreConfigFlag('web/secure/use_in_adminhtml');
    }

    /**
     * Force strip secret key param if _nosecret param specified
     *
     * @return Mage_Core_Model_Url
     */
    public function setRouteParams(array $data, $unsetOldParams=true)
    {
        if (isset($data['_nosecret'])) {
            $this->setNoSecret(true);
            unset($data['_nosecret']);
        }

        return parent::setRouteParams($data, $unsetOldParams);
    }

    /**
     * Custom logic to retrieve Urls
     *
     * @param string $routePath
     * @param array $routeParams
     * @return string
     */
    public function getUrl($routePath=null, $routeParams=null)
    {
        $result = parent::getUrl($routePath, $routeParams);

        if (!$this->useSecretKey() || $this->getNoSecret()) {
            return $result;
        }

        $_route = $this->getRouteName() ? $this->getRouteName() : '*';
        $_controller = $this->getControllerName() ? $this->getControllerName() : $this->getDefaultControllerName();
        $_action = $this->getActionName() ? $this->getActionName() : $this->getDefaultActionName();
        $secret = array(self::SECRET_KEY_PARAM_NAME => $this->getSecretKey($_controller, $_action));
        if (is_array($routeParams)) {
            $routeParams = array_merge($secret, $routeParams);
        } else {
            $routeParams = $secret;
        }
        if (is_array($this->getRouteParams())) {
            $routeParams = array_merge($this->getRouteParams(), $routeParams);
        }
        return parent::getUrl("{$_route}/{$_controller}/{$_action}", $routeParams);
    }

    /**
     * Generate secret key for controller and action based on form key
     *
     * @param string $controller Controller name
     * @param string $action Action name
     * @return string
     */
    public function getSecretKey($controller = null, $action = null)
    {
        $salt = AO::getSingleton('core/session')->getFormKey();
        if (!$controller) {
            $controller = $this->getRequest()->getControllerName();
        }
        if (!$action) {
            $action = $this->getRequest()->getActionName();
        }

        $secret = $controller . $action . $salt;
        return AO::helper('core')->getHash($secret);
    }

    /**
     * Return secret key settings flag
     *
     * @return boolean
     */
    public function useSecretKey()
    {
        return AO::getStoreConfigFlag('admin/security/use_form_key');
    }

    /**
     * Refresh admin menu cache etc.
     *
     * @return Mage_Adminhtml_Model_Url
     */
    public function renewSecretUrls()
    {
        AO::app()->cleanCache(array(Mage_Adminhtml_Block_Page_Menu::CACHE_TAGS));
    }
}
