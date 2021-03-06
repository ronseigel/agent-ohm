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
 * @category    Mage
 * @package     Mage_Adminhtml
 * @copyright   Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Store controller
 *
 * @category    Mage
 * @package     Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Adminhtml_System_StoreController extends Mage_Adminhtml_Controller_Action
{

    /**
     * Init actions
     *
     * @return Mage_Adminhtml_Cms_PageController
     */
    protected function _initAction()
    {
        // load layout, set active menu and breadcrumbs
        $this->loadLayout()
            ->_setActiveMenu('system/store')
            ->_addBreadcrumb(AO::helper('adminhtml')->__('System'), AO::helper('adminhtml')->__('System'))
            ->_addBreadcrumb(AO::helper('adminhtml')->__('Manage Stores'), AO::helper('adminhtml')->__('Manage Stores'))
        ;
        return $this;
    }

    public function indexAction()
    {
        $this->_initAction()
            ->_addContent($this->getLayout()->createBlock('adminhtml/system_store_store'))
            ->renderLayout();
    }

    public function newWebsiteAction()
    {
        AO::register('store_type', 'website');
        $this->_forward('newStore');
    }

    public function newGroupAction()
    {
        AO::register('store_type', 'group');
        $this->_forward('newStore');
    }

    public function newStoreAction()
    {
        if (!AO::registry('store_type')) {
            AO::register('store_type', 'store');
        }
        AO::register('store_action', 'add');
        $this->_forward('editStore');
    }

    public function editWebsiteAction()
    {
        AO::register('store_type', 'website');
        $this->_forward('editStore');
    }

    public function editGroupAction()
    {
        AO::register('store_type', 'group');
        $this->_forward('editStore');
    }

    public function editStoreAction()
    {
        $session = $this->_getSession();
        if ($session->getPostData()) {
            AO::register('store_post_data', $session->getPostData());
            $session->unsPostData();
        }
        if (!AO::registry('store_type')) {
            AO::register('store_type', 'store');
        }
        if (!AO::registry('store_action')) {
            AO::register('store_action', 'edit');
        }
        switch (AO::registry('store_type')) {
            case 'website':
                $itemId     = $this->getRequest()->getParam('website_id', null);
                $model      = AO::getModel('core/website')->load($itemId);
                $notExists  = AO::helper('core')->__("Website doesn't exist");
                $codeBase   = AO::helper('core')->__('Before modifying the website code please make sure that it is not used in index.php');
                break;
            case 'group':
                $itemId     = $this->getRequest()->getParam('group_id', null);
                $model      = AO::getModel('core/store_group')->load($itemId);
                $notExists  = AO::helper('core')->__("Store doesn't exist");
                $codeBase   = false;
                break;
            case 'store':
                $itemId     = $this->getRequest()->getParam('store_id', null);
                $model      = AO::getModel('core/store')->load($itemId);
                $notExists  = AO::helper('core')->__("Store view doesn't exist");
                $codeBase   = AO::helper('core')->__('Before modifying the store view code please make sure that it is not used in index.php');
                break;
        }

        if ($model->getId() || AO::registry('store_action') == 'add') {
            AO::register('store_data', $model);

            if (AO::registry('store_action') == 'edit' && $codeBase) {
                $this->_getSession()->addNotice($codeBase);
            }

            $this->_initAction()
                ->_addContent($this->getLayout()->createBlock('adminhtml/system_store_edit'))
                ->renderLayout();
        }
        else {
            $session->addError($notExists);
            $this->_redirect('*/*/');
        }
    }

    public function saveAction()
    {
        if ($this->getRequest()->isPost() && $postData = $this->getRequest()->getPost()) {
            if (empty($postData['store_type']) || empty($postData['store_action'])) {
                $this->_redirect('*/*/');
                return;
            }
            $session = $this->_getSession();

            try {
                switch ($postData['store_type']) {
                    case 'website':
                        $websiteModel = AO::getModel('core/website');
                        if ($postData['website']['website_id']) {
                            $websiteModel->load($postData['website']['website_id']);
                        }
                        $websiteModel->setData($postData['website']);
                        if ($postData['website']['website_id'] == '') {
                            $websiteModel->setId(null);
                        }

                        $websiteModel->save();
                        $session->addSuccess(AO::helper('core')->__('Website was successfully saved'));
                        break;

                    case 'group':
                        $groupModel = AO::getModel('core/store_group');
                        if ($postData['group']['group_id']) {
                            $groupModel->load($postData['group']['group_id']);
                        }
                        $groupModel->setData($postData['group']);
                        if ($postData['group']['group_id'] == '') {
                            $groupModel->setId(null);
                        }

                        $groupModel->save();

                        AO::dispatchEvent('store_group_save', array('group' => $groupModel));

                        $session->addSuccess(AO::helper('core')->__('Store was successfully saved'));
                        break;

                    case 'store':
                        $eventName = 'store_edit';
                        $storeModel = AO::getModel('core/store');
                        if ($postData['store']['store_id']) {
                            $storeModel->load($postData['store']['store_id']);
                        }
                        $storeModel->setData($postData['store']);
                        if ($postData['store']['store_id'] == '') {
                            $storeModel->setId(null);
                            $eventName = 'store_add';
                        }
                        $groupModel = AO::getModel('core/store_group')->load($storeModel->getGroupId());
                        $storeModel->setWebsiteId($groupModel->getWebsiteId());
                        $storeModel->save();

                        AO::app()->reinitStores();

                        AO::dispatchEvent($eventName, array('store'=>$storeModel));

                        $session->addSuccess(AO::helper('core')->__('Store View was successfully saved'));
                        break;
                    default:
                        $this->_redirect('*/*/');
                        return;
                }
                $this->_redirect('*/*/');
                return;
            }
            catch (Mage_Core_Exception $e) {
                $session->addError($e->getMessage());
                $session->setPostData($postData);
            }
            catch (Exception $e) {
                $session->addException($e, AO::helper('core')->__('Error while saving. Please try again later.'));
                $session->setPostData($postData);
            }
            $this->_redirectReferer();
            return;
        }
        $this->_redirect('*/*/');
    }

    public function deleteWebsiteAction()
    {
        $session = $this->_getSession();
        $itemId = $this->getRequest()->getParam('item_id', null);
        if (!$model = AO::getModel('core/website')->load($itemId)) {
            $session->addError(AO::helper('core')->__('Unable to proceed. Please, try again'));
            $this->_redirect('*/*/');
            return ;
        }
        if (!$model->isCanDelete()) {
            $session->addError(AO::helper('core')->__('This website cannot be deleted'));
            $this->_redirect('*/*/editWebsite', array('website_id' => $itemId));
            return ;
        }

        $this->_addDeletionNotice('website');

        $this->_initAction()
            ->_addBreadcrumb(AO::helper('core')->__('Delete Website'), AO::helper('core')->__('Delete Website'))
            ->_addContent($this->getLayout()->createBlock('adminhtml/system_store_delete')
                ->setFormActionUrl($this->getUrl('*/*/deleteWebsitePost'))
                ->setBackUrl($this->getUrl('*/*/editWebsite', array('website_id' => $itemId)))
                ->setStoreTypeTitle(AO::helper('core')->__('Website'))
                ->setDataObject($model)
            )
            ->renderLayout();
    }

    public function deleteGroupAction()
    {
        $session = $this->_getSession();
        $itemId = $this->getRequest()->getParam('item_id', null);
        if (!$model = AO::getModel('core/store_group')->load($itemId)) {
            $session->addError(AO::helper('core')->__('Unable to proceed. Please, try again'));
            $this->_redirect('*/*/');
            return ;
        }
        if (!$model->isCanDelete()) {
            $session->addError(AO::helper('core')->__('This store cannot be deleted'));
            $this->_redirect('*/*/editGroup', array('group_id' => $itemId));
            return ;
        }

        $this->_addDeletionNotice('store');

        $this->_initAction()
            ->_addBreadcrumb(AO::helper('core')->__('Delete Store'), AO::helper('core')->__('Delete Store'))
            ->_addContent($this->getLayout()->createBlock('adminhtml/system_store_delete')
                ->setFormActionUrl($this->getUrl('*/*/deleteGroupPost'))
                ->setBackUrl($this->getUrl('*/*/editGroup', array('group_id' => $itemId)))
                ->setStoreTypeTitle(AO::helper('core')->__('Store'))
                ->setDataObject($model)
            )
            ->renderLayout();
    }

    public function deleteStoreAction()
    {

        $session = $this->_getSession();
        $itemId = $this->getRequest()->getParam('item_id', null);
        if (!$model = AO::getModel('core/store')->load($itemId)) {
            $session->addError(AO::helper('core')->__('Unable to proceed. Please, try again'));
            $this->_redirect('*/*/');
            return ;
        }
        if (!$model->isCanDelete()) {
            $session->addError(AO::helper('core')->__('This store view cannot be deleted'));
            $this->_redirect('*/*/editStore', array('store_id' => $itemId));
            return ;
        }

        $this->_addDeletionNotice('store view');;

        $this->_initAction()
            ->_addBreadcrumb(AO::helper('core')->__('Delete Store View'), AO::helper('core')->__('Delete Store View'))
            ->_addContent($this->getLayout()->createBlock('adminhtml/system_store_delete')
                ->setFormActionUrl($this->getUrl('*/*/deleteStorePost'))
                ->setBackUrl($this->getUrl('*/*/editStore', array('store_id' => $itemId)))
                ->setStoreTypeTitle(AO::helper('core')->__('Store View'))
                ->setDataObject($model)
            )
            ->renderLayout();
    }

    public function deleteWebsitePostAction()
    {
        $itemId = $this->getRequest()->getParam('item_id');

        if (!$model = AO::getModel('core/website')->load($itemId)) {
            $this->_getSession()->addError(AO::helper('core')->__('Unable to proceed. Please, try again'));
            $this->_redirect('*/*/');
            return ;
        }
        if (!$model->isCanDelete()) {
            $this->_getSession()->addError(AO::helper('core')->__('This website cannot be deleted.'));
            $this->_redirect('*/*/editWebsite', array('website_id' => $model->getId()));
            return ;
        }

        $this->_backupDatabase('*/*/editWebsite', array('website_id' => $itemId));

        try {
            $model->delete();
            $this->_getSession()->addSuccess(AO::helper('core')->__('Website was successfully deleted.'));
            $this->_redirect('*/*/');
            return ;
        }
        catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }
        catch (Exception $e) {
            $this->_getSession()->addException($e, AO::helper('core')->__('Unable to delete website. Please, try again later.'));
        }
        $this->_redirect('*/*/editWebsite', array('website_id' => $itemId));
    }

    public function deleteGroupPostAction()
    {
        $itemId = $this->getRequest()->getParam('item_id');

        if (!$model = AO::getModel('core/store_group')->load($itemId)) {
            $this->_getSession()->addError(AO::helper('core')->__('Unable to proceed. Please, try again'));
            $this->_redirect('*/*/');
            return ;
        }
        if (!$model->isCanDelete()) {
            $this->_getSession()->addError(AO::helper('core')->__('This store cannot be deleted.'));
            $this->_redirect('*/*/editGroup', array('group_id' => $model->getId()));
            return ;
        }

        $this->_backupDatabase('*/*/editGroup', array('group_id' => $itemId));

        try {
            $model->delete();
            $this->_getSession()->addSuccess(AO::helper('core')->__('Store was successfully deleted.'));
            $this->_redirect('*/*/');
            return ;
        }
        catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }
        catch (Exception $e) {
            $this->_getSession()->addException($e, AO::helper('core')->__('Unable to delete store. Please, try again later.'));
        }
        $this->_redirect('*/*/editGroup', array('group_id' => $itemId));
    }

    /**
     * Delete store view post action
     *
     */
    public function deleteStorePostAction()
    {
        $itemId = $this->getRequest()->getParam('item_id');

        if (!$model = AO::getModel('core/store')->load($itemId)) {
            $this->_getSession()->addError(AO::helper('core')->__('Unable to proceed. Please, try again'));
            $this->_redirect('*/*/');
            return ;
        }
        if (!$model->isCanDelete()) {
            $this->_getSession()->addError(AO::helper('core')->__('This store view cannot be deleted.'));
            $this->_redirect('*/*/editStore', array('store_id' => $model->getId()));
            return ;
        }

        $this->_backupDatabase('*/*/editStore', array('store_id' => $itemId));

        try {
            $model->delete();

            AO::dispatchEvent('store_delete', array('store' => $model));

            $this->_getSession()->addSuccess(AO::helper('core')->__('Store view was successfully deleted.'));
            $this->_redirect('*/*/');
            return ;
        }
        catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }
        catch (Exception $e) {
            $this->_getSession()->addException($e, AO::helper('core')->__('Unable to delete store view. Please, try again later.'));
        }
        $this->_redirect('*/*/editStore', array('store_id' => $itemId));
    }

    protected function _isAllowed()
    {
        return AO::getSingleton('admin/session')->isAllowed('system/store');
    }

    /**
     * Backup database
     *
     * @param string $failPath redirect path if backup failed
     * @param array $arguments
     * @return Mage_Adminhtml_System_StoreController
     */
    protected function _backupDatabase($failPath, $arguments=array())
    {
        if (! $this->getRequest()->getParam('create_backup')) {
            return $this;
        }
        try {
            $backupDb = AO::getModel('backup/db');
            $backup   = AO::getModel('backup/backup')
                ->setTime(time())
                ->setType('db')
                ->setPath(AO::getBaseDir('var') . DS . 'backups');

            $backupDb->createBackup($backup);
            $this->_getSession()->addSuccess(AO::helper('backup')->__('Database was successfuly backed up.'));
        }
        catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
            $this->_redirect($failPath, $arguments);
            return ;
        }
        catch (Exception $e) {
            $this->_getSession()->addException($e, AO::helper('backup')->__('Unable to create backup. Please, try again later.'));
            $this->_redirect($failPath, $arguments);
            return ;
        }
        return $this;
    }

    /**
     * Add notification on deleting store / store view / website
     *
     * @param string $typeTitle
     * @return Mage_Adminhtml_System_StoreController
     */
    protected function _addDeletionNotice($typeTitle)
    {
        $this->_getSession()->addNotice(
            AO::helper('core')->__('Deleting a %1$s will not delete the information associated with the %1$s (e.g. categories, products, etc.), but the %1$s will not be able to be restored. It is suggested that you create a database backup before deleting the %1$s.', $typeTitle)
        );
        return $this;
    }

}

