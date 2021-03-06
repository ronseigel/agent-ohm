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

/**
 * Adminhtml roles controller
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Adminhtml_Permissions_RoleController extends Mage_Adminhtml_Controller_Action
{

    protected function _initAction()
    {
        $this->loadLayout();
        $this->_setActiveMenu('system/acl');
        $this->_addBreadcrumb($this->__('System'), $this->__('System'));
        $this->_addBreadcrumb($this->__('Permissions'), $this->__('Permissions'));
        $this->_addBreadcrumb($this->__('Roles'), $this->__('Roles'));
        return $this;
    }

    public function indexAction()
    {
        $this->_initAction();

        $this->renderLayout();
    }

    public function roleGridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody($this->getLayout()->getBlock('adminhtml.permission.role.grid')->toHtml());
    }

    public function editRoleAction()
    {
        $this->_initAction();

        $roleId = $this->getRequest()->getParam('rid');
        if( intval($roleId) > 0 ) {
            $breadCrumb = $this->__('Edit Role');
            $breadCrumbTitle = $this->__('Edit Role');
        } else {
            $breadCrumb = $this->__('Add new Role');
            $breadCrumbTitle = $this->__('Add new Role');
        }
        $this->_addBreadcrumb($breadCrumb, $breadCrumbTitle);

        $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

        $this->_addContent(
            $this->getLayout()->createBlock('adminhtml/permissions_buttons')
                ->setRoleId($roleId)
                ->setRoleInfo(AO::getModel('admin/roles')->load($roleId))
                ->setTemplate('permissions/roleinfo.phtml')
        );
        $this->_addJs($this->getLayout()->createBlock('adminhtml/template')->setTemplate('permissions/role_users_grid_js.phtml'));
        $this->renderLayout();
    }

    public function deleteAction()
    {
        $rid = $this->getRequest()->getParam('rid', false);
        $currentUser = AO::getModel('admin/user')->setId(AO::getSingleton('admin/session')->getUser()->getId());
        if ( in_array($rid, $currentUser->getRoles()) ) {
            AO::getSingleton('adminhtml/session')->addError($this->__('You can not delete self assigned roles.'));
            $this->_redirect('*/*/editrole', array('rid' => $rid));
            return;
        }

        try {
            AO::getModel("admin/roles")->setId($rid)->delete();
            AO::getSingleton('adminhtml/session')->addSuccess($this->__('Role successfully deleted.'));
        } catch (Exception $e) {
            AO::getSingleton('adminhtml/session')->addError($this->__('Error while deleting this role. Please try again later.'));
        }

        $this->_redirect("*/*/");
    }

    public function saveRoleAction()
    {
        $rid        = $this->getRequest()->getParam('role_id', false);
        $resource   = explode(',', $this->getRequest()->getParam('resource', false));
        $roleUsers  = $this->getRequest()->getParam('in_role_user', null);
        parse_str($roleUsers, $roleUsers);
        $roleUsers = array_keys($roleUsers);

        $isAll = $this->getRequest()->getParam('all');
        if ($isAll)
            $resource = array("all");

        try {
            $role = AO::getModel("admin/roles")
                    ->setId($rid)
                    ->setName($this->getRequest()->getParam('rolename', false))
                    ->setPid($this->getRequest()->getParam('parent_id', false))
                    ->setRoleType('G');
            AO::dispatchEvent('admin_permissions_role_prepare_save', array('object' => $role, 'request' => $this->getRequest()));
            $role->save();

            AO::getModel("admin/rules")
                ->setRoleId($role->getId())
                ->setResources($resource)
                ->saveRel();

            $oldRoleUsers = AO::getModel("admin/roles")->setId($role->getId())->getRoleUsers($role);
            if ( sizeof($oldRoleUsers) > 0 ) {
                foreach($oldRoleUsers as $oUid) {
                    $this->_deleteUserFromRole($oUid, $role->getId());
                }
            }
            if ( $roleUsers ) {
                foreach ($roleUsers as $nRuid) {
                    $this->_addUserToRole($nRuid, $role->getId());
                }
            }
            $rid = $role->getId();
            AO::getSingleton('adminhtml/session')->addSuccess($this->__('Role successfully saved.'));
        } catch (Exception $e) {
            AO::getSingleton('adminhtml/session')->addError($this->__('Error while saving this role. Please try again later.'));
        }

        //$this->getResponse()->setRedirect($this->getUrl("*/*/editrole/rid/$rid"));
        $this->_redirect('*/*/editrole', array('rid' => $rid));
        return;
    }

    public function editrolegridAction()
    {
        $this->getResponse()->setBody($this->getLayout()->createBlock('adminhtml/permissions_role_grid_user')->toHtml());
    }

    protected function _deleteUserFromRole($userId, $roleId)
    {
        try {
            AO::getModel("admin/user")
                ->setRoleId($roleId)
                ->setUserId($userId)
                ->deleteFromRole();
        } catch (Exception $e) {
            throw $e;
            return false;
        }
        return true;
    }

    protected function _addUserToRole($userId, $roleId)
    {
        $user = AO::getModel("admin/user")->load($userId);
        $user->setRoleId($roleId)->setUserId($userId);

        if( $user->roleUserExists() === true ) {
            return false;
        } else {
            $user->add();
            return true;
        }
    }

    protected function _isAllowed()
    {
        return AO::getSingleton('admin/session')->isAllowed('system/acl/roles');
    }
}