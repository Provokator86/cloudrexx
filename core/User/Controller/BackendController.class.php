<?php declare(strict_types=1);

/**
 * Cloudrexx App by Comvation AG
 *
 * @category  CloudrexxApp
 * @package   User
 * @author    Cloudrexx AG <dario.graf@comvation.com>
 * @copyright Cloudrexx AG
 * @link      https://www.cloudrexx.com
 *
 * Unauthorized copying, changing or deleting
 * of any file from this app is strictly prohibited
 *
 * Authorized copying, changing or deleting
 * can only be allowed by a separate contract
 **/

namespace Cx\Core\User\Controller;

/**
 * Specific BackendController for this Component.
 *
 * Use the examples here to easily customize the backend of your component.
 * Delete this file if you don't need it. Remove any methods you don't need!
 *
 * @category  CloudrexxApp
 * @package   User
 * @author    Cloudrexx AG <dario.graf@comvation.com>
 * @copyright Cloudrexx AG
 * @link      https://www.cloudrexx.com
 */

class BackendController extends
    \Cx\Core\Core\Model\Entity\SystemComponentBackendController
{
    protected $userId;

    /**
     * This function returns the ViewGeneration options for a given entityClass
     *
     * @param string $entityClassName   contains the FQCN from entity
     * @param string $dataSetIdentifier if entityClassName is DataSet, this is
     *                                  used for better partition
     *
     * @access protected
     *
     * @global $_ARRAYLANG
     *
     * @return array with options
     */
    protected function getViewGeneratorOptions(
        $entityClassName, $dataSetIdentifier = ''
    )
    {
        global $_ARRAYLANG;

        if ($this->cx->getRequest()->hasParam('editid')) {
            $this->userId = explode(
                '}',
                explode(
                    ',',
                    $this->cx->getRequest()->getParam('editid')
                )[1]
            )[0];
        }

        $options = parent::getViewGeneratorOptions(
            $entityClassName,
            $dataSetIdentifier
        );

        $uId = $this->getUserId();

        switch ($entityClassName) {
            case 'Cx\Core\User\Model\Entity\User':
                $options['order'] = array(
                    'overview' => array(
                        'id',
                        'active',
                        'isAdmin',
                        'email',
                        'company',
                        'firstname',
                        'lastname',
                        'regdate',
                        'lastActivity',
                    ),
                    'form' => array(
                        'email',
                        'username',
                        'password',
                        'passwordConfirmed',
                        'backendLangId',
                        'frontendLangId',
                        'isAdmin',
                        'emailAccess',
                        'profileAccess',
                    ),
                );
                $options['functions'] = array(
                    'searching' => true,
                    'filtering' => true,
                    'edit' => true,
                    'delete' => true,
                    'add' => true,
                    'status' => array(
                        'field' => 'active'
                    ),
                    'alphabetical' => 'username'
                );
                $options['tabs']['groups'] = array(
                    'header' => $_ARRAYLANG['TXT_CORE_USER_GROUP_S'],
                    'fields' => array(
                        'group',
                        'primaryGroup'
                    )
                );

                $options['fields'] = array(
                    'id' => array(
                        'allowFiltering' => false,
                    ),
                    'active' => array(
                        'showOverview' => true,
                        'showDetail' => false,
                    ),
                    'username' => array(
                        'table' => array(
                            'parse' => function ($value, $rowData) {
                                return $this->addEditUrl($value, $rowData);
                            }
                        ),
                        'showOverview' => true,
                        'showDetail' => true,
                        'allowFiltering' => false,
                    ),
                    'isAdmin' => array(
                        'header' => '',
                        'formtext' => $_ARRAYLANG['isAdmin'],
                        'table' => array(
                            'parse' => array(
                                'adapter' => 'User',
                                'method' => 'getRoleIcon'
                            )
                        )
                    ),
                    'email' => array(
                        'table' => array(
                            'parse' => function ($value, $rowData) {
                                return $this->addEmailUrl($value, $rowData);
                            }
                        ),
                        'showOverview' => true,
                        'showDetail' => true,
                        'allowFiltering' => false,
                    ),
                    'password' => array(
                        'showOverview' => false,
                        'formfield' => array(
                            'adapter' => 'User',
                            'method' => 'getPasswordField'
                        ),
                        'tooltip' => $this->getPasswordInfo(),
                        'allowFiltering' => false,
                    ),
                    'passwordConfirmed' => array(
                        'custom' => true,
                        'showOverview' => false,
                        'attributes' => array(
                            'class' => 'access-pw-noauto',
                        ),
                        'allowFiltering' => false,
                    ),
                    'authToken' => array(
                        'showOverview' => false,
                        'showDetail' => false,
                        'allowFiltering' => false,
                    ),
                    'authTokenTimeout' => array(
                        'showOverview' => false,
                        'showDetail' => false,
                        'allowFiltering' => false,
                    ),
                    'regdate' => array(
                        'table' => array(
                            'parse' => function ($value, $rowData) {
                                return $this->formatDate($value);
                            }
                        ),
                        'showOverview' => true,
                        'showDetail' => false,
                        'allowFiltering' => false,
                    ),
                    'expiration' => array(
                        'showOverview' => true,
                        'showDetail' => true,
                        'formfield' =>
                            function ($fieldname, $fieldtype, $fieldlength, $fieldvalue){
                                return $this->getExpirationDropdown($fieldname, $fieldvalue);
                            },
                        'allowFiltering' => false,
                    ),
                    'validity' => array(
                        'showOverview' => false,
                        'showDetail' => false,
                        'allowFiltering' => false,
                    ),
                    'lastAuthStatus' => array(
                        'showOverview' => false,
                        'showDetail' => false,
                        'allowFiltering' => false,
                    ),
                    'lastAuth' => array(
                        'showOverview' => false,
                        'showDetail' => false,
                        'allowFiltering' => false,
                    ),
                    'emailAccess' => array(
                        'showOverview' => false,
                        'showDetail' => true,
                        'type' => 'hidden',
                        'allowFiltering' => false,
                    ),
                    'frontendLangId' => array(
                        'showOverview' => false,
                        'formfield' =>
                            function ($fieldname, $fieldtype, $fieldlength, $fieldvalue){
                                return $this->getLangDropdown($fieldname, $fieldvalue);
                            },
                        'allowFiltering' => false,
                    ),
                    'backendLangId' => array(
                        'showOverview' => false,
                        'showDetail' => true,
                        'type' => 'hidden',
                        'allowFiltering' => false,
                    ),
                    'verified' => array(
                        'showOverview' => false,
                        'showDetail' => false,
                        'allowFiltering' => false,
                    ),
                    'primaryGroup' => array(
                        'showOverview' => false,
                        'showDetail' => true,
                        'mode' => 'associate',
                        'formfield' =>
                            function ($fieldname, $fieldtype, $fieldlength, $fieldvalue){
                                return $this->primaryGroupDropdown($fieldname, $fieldvalue);
                            },
                        'allowFiltering' => false,
                    ),
                    'profileAccess' => array(
                        'showOverview' => false,
                        'formfield' =>
                            function ($fieldname, $fieldtype, $fieldlength, $fieldvalue){
                                return $this->getProfileAccessDropdown($fieldname, $fieldvalue);
                            },
                        'allowFiltering' => false,
                    ),
                    'restoreKey' => array(
                        'showOverview' => false,
                        'showDetail' => false,
                        'allowFiltering' => false,
                    ),
                    'restoreKeyTime' => array(
                        'showOverview' => false,
                        'showDetail' => false,
                        'allowFiltering' => false,
                    ),
                    'u2uActive' => array(
                        'showOverview' => false,
                        'showDetail' => false,
                        'allowFiltering' => false,
                    ),
                    'userProfile' => array(
                        'showOverview' => false,
                        'showDetail' => false,
                        'allowFiltering' => false,
                    ),
                    'group' => array(
                        'showOverview' => false,
                        'mode' => 'associate'
                    ),
                    'lastActivity' => array(
                        'table' => array(
                            'parse' => function ($value, $rowData) {
                                return $this->formatDate($value);
                            }
                        ),
                        'showOverview' => true,
                        'showDetail' => false,
                        'allowFiltering' => false,
                    ),
                    'userAttributeValue' => array(
                        'showOverview' => false,
                        'showDetail' => false,
                        'allowFiltering' => false,
                    ),
                    'accountType' => array(
                        'custom' => true,
                        'showOverview' => false,
                        'showDetail' => false,
                        'allowSearching' => false,
                        'allowFiltering' => true,
                        //$arrCustomJoins[] = 'INNER JOIN `'.DBPREFIX.'module_crm_contacts` AS tblCrm ON tblCrm.`user_account` = tblU.`id`';
                    )
                );

                $em = $this->cx->getDb()->getEntityManager();

                //Todo: Maybe filter by type
                $userAttrs = $em->getRepository(
                    'Cx\Core\User\Model\Entity\UserAttribute'
                )->findBy(array('parent' => null), array('isDefault' => 'DESC', 'orderId' => 'ASC'));

                foreach ($userAttrs as $attr) {
                    $attrNames = $attr->getUserAttributeName()->filter(
                        function($entry) {
                            if (empty($entry->getLangId()) || $entry->getLangId() == FRONTEND_LANG_ID) {
                                return $entry;
                            }
                        }
                    );

                    if (empty($attrNames) || empty($attrNames[0])) {
                        continue;
                    }
                    $name = $attrNames[0]->getName();
                    $optionName = 'userAttr-' . $attr->getId();

                    $attrOption = array(
                        'custom' => true,
                        'showOverview' => false,
                        'allowFiltering' => false,
                        'storecallback' => array(
                            'adapter' => 'User',
                            'method' => 'storeUserAttributeValue'
                        ), // todo: move value callback func in json controller
                        'valueCallback' => function($value, $name, $data) use ($attr) {
                            $historyId = 0;
                            $userId = 0;
                            // Only use $data['id'] if the CLX-2542 ticket is
                            // live.
                            if (!empty($this->userId)) {
                                $userId = $this->userId;
                            } else if (isset($data['id'])) {
                                $userId = $data['id'];
                            } else {
                                return '';
                            }

                            $em = $this->cx->getDb()->getEntityManager();
                            $value = $em->getRepository(
                                '\Cx\Core\User\Model\Entity\UserAttributeValue'
                            )->findOneBy(
                                array(
                                    'userId' => $userId,
                                    'attributeId' => $attr->getId(),
                                    'history' => $historyId
                                )
                            );

                            if (!empty($value)) {
                                return $value->getValue();
                            }
                            return '';
                        }
                    );

                    // Use Name of non core-attributes as header. The headers
                    // for core-attributes are defined in the lang files.
                    if ($attr->getIsDefault()) {
                        $header = $_ARRAYLANG[$name];

                        // replace vg-order with the correct option names
                        if (in_array($name, $options['order']['overview'])) {
                            $orderKey = array_search($name, $options['order']['overview']);
                            $options['order']['overview'][$orderKey] = $optionName;
                            $attrOption['showOverview'] = true;
                        }
                    } else {
                        $header = $name;
                    }

                    $attrOption['header'] = $header;

                    switch ($attr->getType()) {
                        case 'date':
                        case 'mail':
                            $attrOption['type'] = $attr->getType();
                            break;
                        case 'uri':
                            //<input type="hidden" name="[NAME]" value="[VALUE]" />
                            //<em>[VALUE_TXT]</em>
                            // <a href="javascript:void(0);"
                            //    onclick="elLink=null;elDiv=null;elInput=null;pntEl=this.previousSibling;while ((typeof(elInput)==\'undefined\'||typeof(elDiv)!=\'undefined\')&& pntEl!=null) {switch(pntEl.nodeName) {case\'INPUT\':elInput=pntEl;break;case\'EM\':elDiv=pntEl;if (elDiv.getElementsByTagName(\'a\').length>0) {elLink=elDiv.getElementsByTagName(\'a\')[0];}break;}pntEl=pntEl.previousSibling;}accessSetWebsite(elInput,elDiv,elLink)" title="'.$_CORELANG['TXT_ACCESS_CHANGE_WEBSITE'].'"><img align="middle" src="'.ASCMS_CORE_MODULE_WEB_PATH.'/Access/View/Media/edit.gif" width="16" height="16" border="0" alt="'.$_CORELANG['TXT_ACCESS_CHANGE_WEBSITE'].'" /></a>',
                            break;
                        case 'image':
                            //$attrOption['type'] = 'image';
                            break;
                        case 'checkbox':
                            break;
                        case 'menu':
                            $validValues = array();
                            if (!$attr->getMandatory()) {
                                $validValues = array(
                                    $_ARRAYLANG['TXT_CORE_USER_NONE_SPECIFIED']
                                );
                            }

                            if (count($attr->getChildren())) {
                                foreach ( $attr->getChildren() as $child) {
                                    foreach ($child->getUserAttributeName() as $childName) {
                                        if ($childName->getLangId() == FRONTEND_LANG_ID) {
                                            $validValues[
                                            $childName->getAttributeId()
                                            ] = $childName->getName();
                                        }
                                    }
                                }
                            } else if ($name == 'gender') {
                                $validValues = array(
                                    'gender_undefined' => $_ARRAYLANG[
                                        'TXT_CORE_USER_GENDER_UNDEFINED'
                                    ],
                                    'gender_female' => $_ARRAYLANG[
                                        'TXT_CORE_USER_GENDER_FEMALE'
                                    ],
                                    'gender_male' => $_ARRAYLANG[
                                        'TXT_CORE_USER_GENDER_MALE'
                                    ]
                                );
                            }

                            $attrOption['type'] = 'select';
                            $attrOption['validValues'] = $validValues;
                            break;
                        case 'menu_option':
                            break;
                        case 'frame':
                            // Later
                            break;
                        case 'group':
                            // Later
                            break;
                        case 'history':
                            // Later
                            break;
                        case 'textarea':
                            $attrOption['type'] = 'text';
                            break;
                        case 'text':
                        default:
                            if ($name == 'country') {
                                $attrOption['type'] = 'Country';
                                break;
                            }
                            $attrOption['type'] = 'string';
                            break;
                    }

                    $options['fields'][$optionName] = $attrOption;
                    $options['tabs']['profile']['fields'][] = $optionName;
                }
                $options['tabs']['profile']['header'] = $_ARRAYLANG['TXT_CORE_USER_PROFILE'];
                break;
            case 'Cx\Core\User\Model\Entity\Group':
                $options['order'] = array(
                    'overview' => array(
                        'groupId',
                        'isActive',
                        'groupName',
                        'groupDescription',
                        'type',
                        'user',
                    ),
                    'form' => array(
                        'groupName',
                        'groupDescription',
                        'isActive',
                        'type',
                        'user',
                        'homepage',
                    )
                );
                $options['functions'] = array(
                    'searching' => true,
                    'filtering' => true,
                    'add' => true,
                    'edit' => true,
                    'delete' => true,
                );
                $options['fields'] = array(
                    'isActive' => array(
                        'showOverview' => true,
                        'allowFiltering' => false,
                    ),
                    'groupId' => array(
                        'showOverview' => true,
                        'allowFiltering' => false,
                    ),
                    'groupName' => array(
                        'table' => array(
                            'parse' => function (
                                $value, $rowData, $viewGeneratorId
                            ) {
                                return $this->addGroupEditUrl(
                                    $value, $rowData, $viewGeneratorId
                                );
                            }
                        ),
                        'showOverview' => true,
                        'allowFiltering' => false,
                    ),
                    'groupDescription' => array(
                        'showOverview' => true,
                        'allowFiltering' => false,
                    ),
                    'type' => array(
                        'showOverview' => true,
                        'type' => 'select',
                        'validValues' => array(
                            $_ARRAYLANG['TXT_CORE_USER_TYPE_FRONTEND'],
                            $_ARRAYLANG['TXT_CORE_USER_TYPE_BACKEND']
                        ),
                        'filterOptionsField' => function (
                            $parseObject, $fieldName, $elementName, $formName
                        ) {
                            return $this->getGroupTypeMenu(
                                $elementName, $formName
                            );
                        },
                    ),
                    'homepage' => array(
                        'showOverview' => false,
                        'allowFiltering' => false,
                        'tooltip' => 'test',
                        'formfield' =>
                            function (){
                                return $this->setHomepage();
                            }
                    ),
                    'toolbar' => array(
                        'showOverview' => false,
                        'allowFiltering' => false,
                        'showDetail' => false,
                    ),
                    'toolbar' => array(
                        'showOverview' => true,
                        'allowFiltering' => false,
                        'showDetail' => false,
                        'custom' => true,
                        'table' => array(
                            'parse' => function ($value, $rowData) {
                                return $this->getExportLink($value, $rowData);
                            }
                        ),
                    ),
                    'user' => array(
                        'showOverview' => true,
                        'mode' => 'associate',
                        'table' => array(
                            'parse' => function ($rowData) use ($uId) {
                                return $this->addGroupUserEditUrl(
                                    $rowData, $uId
                                );
                            }
                        ),
                        'allowFiltering' => false,
                    ),
                );
                break;
            case 'Cx\Core\User\Model\Entity\Settings':
                $options['fields'] = array(
                    'title' => array(
                        'showOverview' => true,
                    ),
                );
                break;
            case 'Cx\Core\User\Model\Entity\UserAttribute':
                $options['fields'] = array(
                    'title' => array(
                        'showOverview' => false,
                    ),
                );
                break;
            case 'Cx\Core\User\Model\Entity\ProfileTitle':
                $options['fields'] = array(
                    'title' => array(
                        'showOverview' => false,
                    ),
                );
                break;
            case 'Cx\Core\User\Model\Entity\CoreAttribute':
                $options['fields'] = array(
                    'title' => array(
                        'showOverview' => false,
                    ),
                );
                break;
            case 'Cx\Core\User\Model\Entity\UserAttributeValue':
                $options['fields'] = array(
                    'title' => array(
                        'showOverview' => false,
                    ),
                );
                break;
            case 'Cx\Core\User\Model\Entity\UserAttributeName':
                $options['fields'] = array(
                    'title' => array(
                        'showOverview' => false,
                    ),
                );
                break;
            case 'Cx\Core\User\Model\Entity\UserProfile':
                $options['fields'] = array(
                    'title' => array(
                        'showOverview' => false,
                    ),
                );
                break;
        }
        return $options;
    }

    protected function getEntityClasses()
    {
        return array(
            'Cx\Core\User\Model\Entity\User',
            'Cx\Core\User\Model\Entity\Group',
        );
    }

    /**
     * Return true here if you want the first tab to be an entity view
     *
     * @return boolean True if overview should be shown, false otherwise
     */
    protected function showOverviewPage() : bool
    {
        return false;
    }

    /**
     * Count users in groups
     *
     * @return mixed
     */
    protected function getUserId()
    {
        $em = $this->cx->getDb()->getEntityManager();
        $qb = $em->createQueryBuilder();
        $query = $qb->select('g.groupId', 'count(u.id)')->from('Cx\Core\User\Model\Entity\Group', 'g')
            ->leftJoin('g.user', 'u')
            ->groupBy('g.groupId')
            ->getQuery();
        $result = $query->getResult();

        $getGroupUser = array_reduce($result,
            function($groups, $group){
                $groups[$group['groupId']] = $group[1];
                return $groups;
            }
        );

        return $getGroupUser;
    }

    /**
     * Format the date for the overview list
     *
     * @param int $value date
     *
     * @return string
     */
    protected function formatDate($value)
    {
        $date = '@' . $value;

        $dateElement = new \DateTime($date);

        $dateElement = $dateElement->format('d.m.Y');

        return $dateElement;
    }

    /**
     * Format the date for the overview list
     *
     * @param string $value email
     *
     * @return \Cx\Core\Html\Model\Entity\HtmlElement
     */
    protected function addEmailUrl($value)
    {
        global $_ARRAYLANG;

        $email = new \Cx\Core\Html\Model\Entity\TextElement($value);

        $setEmailUrl = new \Cx\Core\Html\Model\Entity\HtmlElement(
            'a'
        );

        $setEmailUrl->setAttributes(array('href' => "mailto:$value", 'title' => $_ARRAYLANG['TXT_CORE_USER_EMAIL_TITLE'] . ' ' . $value));

        $setEmailUrl->addChild($email);

        return $setEmailUrl;
    }

    /**
     * Add edit url
     *
     * @param $value
     * @param $rowData
     * @return \Cx\Core\Html\Model\Entity\HtmlElement
     */
    protected function addEditUrl($value,$rowData)
    {
        global $_ARRAYLANG;

        $username = new \Cx\Core\Html\Model\Entity\TextElement($value);

        $setEditUrl = new \Cx\Core\Html\Model\Entity\HtmlElement(
            'a'
        );

        $editUrl = \Cx\Core\Routing\Url::fromMagic(
            \Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteBackendPath() .
            '/' . $this->getName() . '/User'
        );

        $userId = $rowData['id'];

        $editUrl->setParam('editid', $userId);

        $setEditUrl->setAttributes(array('href' => $editUrl, 'title' => $_ARRAYLANG['TXT_CORE_USER_EDIT_TITLE']));

        $setEditUrl->addChild($username);

        return $setEditUrl;
    }

    /**
     * Format the groupname in the overview list
     *
     * @param $value string        Fieldvalue
     * @param $rowData array       Data of row
     * @param $viewGeneratorId int Viewgenerator id
     * @return \Cx\Core\Html\Model\Entity\HtmlElement
     */
    protected function addGroupEditUrl($value, $rowData, $viewGeneratorId)
    {
        global $_ARRAYLANG;

        $groupname = new \Cx\Core\Html\Model\Entity\TextElement($value);

        $setEditUrl = new \Cx\Core\Html\Model\Entity\HtmlElement(
            'a'
        );

        $entityId = $rowData['groupId'];

        $editUrl = \Cx\Core\Html\Controller\ViewGenerator::getVgEditUrl($viewGeneratorId, $entityId);

        $setEditUrl->setAttributes(array('href' => $editUrl, 'title' => $_ARRAYLANG['TXT_CORE_USER_EDIT_GROUP']));

        $setEditUrl->addChild($groupname);

        return $setEditUrl;
    }

    /**
     * Add edit url
     *
     * @param $rowData array      Data of row
     * @param $getGroupUser array Users per group
     * @return \Cx\Core\Html\Model\Entity\HtmlElement
     */
    protected function addGroupUserEditUrl($rowData, $getGroupUser)
    {
        global $_ARRAYLANG;

        $userId = new \Cx\Core\Html\Model\Entity\TextElement($getGroupUser[$rowData['groupId']]);

        $setEditUrl = new \Cx\Core\Html\Model\Entity\HtmlElement(
            'a'
        );

        $editUrl = \Cx\Core\Routing\Url::fromMagic(
            \Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteBackendPath() .
            '/' . $this->getName() . '/User'
        );

        $editUrl->setParam('search', $rowData['groupId']);

        $setEditUrl->setAttributes(array('href' => $editUrl));

        $setEditUrl->addChild($userId);

        return $setEditUrl;
    }

    /**
     * Set the Startpage of a user
     *
     * @return \Cx\Core\Html\Model\Entity\HtmlElement
     */
    protected function setHomepage()
    {
        global $_ARRAYLANG;

        $mediaBrowser = new \Cx\Core_Modules\MediaBrowser\Model\Entity\MediaBrowser();
        $mediaBrowser->setCallback('SetUrl');

        $buttonText = new \Cx\Core\Html\Model\Entity\TextElement($_ARRAYLANG['TXT_CORE_USER_BROWSE']);

        $div = new \Cx\Core\Html\Model\Entity\HtmlElement('div');
        $div->allowDirectClose(false);

        $button = new \Cx\Core\Html\Model\Entity\HtmlElement('button');

        $button->setAttributes(array(
            'data-cx-mb',
            'class' => 'mediabrowser-button button',
            'type' => 'button',
            'data-cx-mb-views' => 'sitestructure',
            'id' => 'media-browser-button',
            'data-cx-Mb-Cb-Js-Modalclosed' => 'SetUrl'
        ));

        $mediaBrowserScope = new \Cx\Core\Html\Model\Entity\HtmlElement('div');

        $mediaBrowserScope->addClass('mediaBrowserScope');

        $button->addChild($buttonText);

        $div->addChildren(array($button, $mediaBrowserScope));

        return $div;
    }

    /**
     * Generate the group type menu
     *
     * @param $elementName string Name of the element
     * @param $formName string    Name of the form
     * @return \Cx\Core\Html\Model\Entity\DataElement
     */
    protected function getGroupTypeMenu($elementName, $formName)
    {
        global $_ARRAYLANG;

        $validValues = array(
            '' => $_ARRAYLANG['TXT_CORE_USER_TYPE'],
            $_ARRAYLANG['TXT_LANGUAGE_FRONTEND'] =>
                $_ARRAYLANG['TXT_CORE_USER_TYPE_FRONTEND'],
            $_ARRAYLANG['TXT_LANGUAGE_BACKEND'] =>
                $_ARRAYLANG['TXT_CORE_USER_TYPE_BACKEND'],
        );
        $searchField = new \Cx\Core\Html\Model\Entity\DataElement(
            $elementName,
            '',
            'select',
            null,
            $validValues
        );

        $searchField->setAttributes(
            array(
                'form' => $formName,
                'data-vg-attrgroup' => 'search',
                'data-vg-field' => 'type',
                'class' => 'vg-encode',
            )
        );
        return $searchField;
    }

    /**
     * Get the export link of users in a group
     *
     * @param $value   string fieldvalue
     * @param $rowData array  all data
     * @return \Cx\Core\Html\Model\Entity\HtmlElement return generated div
     *                                                with links
     */
    protected function getExportLink($value, $rowData)
    {
        global $_ARRAYLANG;

        $wrapper = new \Cx\Core\Html\Model\Entity\HtmlElement('div');
        $groupId = $rowData['groupId'];

        $em = $this->cx->getDb()->getEntityManager();
        $languages = $em->getRepository(
            'Cx\Core\Locale\Model\Entity\Locale'
        )->findAll();

        /*Add a seperator*/
        $seperator = new \Cx\Core\Html\Model\Entity\TextElement(', ');

        /*Add icon for csv export*/
        $iconExport =  new \Cx\Core\Html\Model\Entity\HtmlElement('img');
        $iconPath = $this->cx->getCodeBaseCoreWebPath()
            . '/Core/View/Media/icons/csv.gif';

        $iconExport->setAttributes(
            array(
                'src' => $iconPath,
                'title' => 'Export',
                'alt' => 'Export',
                'align' => 'absmiddle'
            )
        );

        $wrapper->addChild($iconExport);

        /*Add link to export all*/
        $linkAll = new \Cx\Core\Html\Model\Entity\HtmlElement('a');
        $csvLinkUrlAll = \Cx\Core\Routing\Url::fromApi('exportUser', array());
        $csvLinkUrlAll->setParam('groupId', $groupId);
        $textAll = new \Cx\Core\Html\Model\Entity\TextElement(
            $_ARRAYLANG['TXT_CORE_USER_EXPORT_ALL']
        );
        $linkAll->addChild($textAll);

        $linkAll->setAttribute('href', $csvLinkUrlAll);

        $wrapper->addChild($linkAll);

        /*Add link for every source language*/
        foreach ($languages as $lang) {
            $link = new \Cx\Core\Html\Model\Entity\HtmlElement('a');

            $csvLinkUrl = \Cx\Core\Routing\Url::fromApi('exportUser', array());
            $csvLinkUrl->setParam('groupId', $groupId);
            $csvLinkUrl->setParam('langId', $lang->getId());

            $textLang = new \Cx\Core\Html\Model\Entity\TextElement(

                $lang->getShortForm()
            );

            $wrapper->addChild($seperator);

            $link->addChild($textLang);

            $link->setAttribute('href', $csvLinkUrl);

            $wrapper->addChild($link);
        }

        return $wrapper;
    }


    /**
     * Generate the primary group dropdown
     *
     * @param $fieldname string  Name of the field
     * @param $fieldvalue string Value of the field
     * @return \Cx\Core\Html\Model\Entity\DataElement|void
     */
    protected function primaryGroupDropdown($fieldname, $fieldvalue)
    {
        global $_ARRAYLANG;

        $em = $this->cx->getDb()->getEntityManager();

        $userId = intval($this->userId);

        $user = $em->getRepository('Cx\Core\User\Model\Entity\User')->findOneBy(array('id' => $userId));

        if (empty($user)) {
            return;
        }

        $userGroups = $user->getGroup();

        $validValues = array();

        foreach ($userGroups as $group) {
            $validValues[$group->getGroupId()] = $group->getGroupName();
        }

        $dropdown = new \Cx\Core\Html\Model\Entity\DataElement(
            $fieldname,
            $fieldvalue,
            'select',
            null,
            $validValues
        );

        return $dropdown;
    }

    /**
     * Generate the expiration dropdown
     *
     * @param $fieldname string  Name of the field
     * @param $fieldvalue string Value of the field
     * @return \Cx\Core\Html\Model\Entity\DataElement
     */
    protected function getExpirationDropdown($fieldname, $fieldvalue)
    {
        $validValues = array();

        foreach (\FWUser::getUserValidities() as $validity) {
            $strValidity = \FWUser::getValidityString($validity);

            $validValues[$validity] = $strValidity;
        }

        $dropdown = new \Cx\Core\Html\Model\Entity\DataElement(
            $fieldname,
            $fieldvalue,
            'select',
            null,
            $validValues
        );

        return $dropdown;
    }

    /**
     * Generate the profile access dropdown
     *
     * @param $fieldname string  Name of the field
     * @param $fieldvalue string Value of the field
     * @return \Cx\Core\Html\Model\Entity\HtmlElement
     */
    protected function getProfileAccessDropdown($fieldname, $fieldvalue)
    {
        global $_CORELANG;

        $wrapper = new \Cx\Core\Html\Model\Entity\HtmlElement('div');

        $validValuesEmail = array(
            'everyone'=> $_CORELANG['TXT_ACCESS_EVERYONE_ALLOWED_SEEING_EMAIL'],
            'members_only' => $_CORELANG['TXT_ACCESS_MEMBERS_ONLY_ALLOWED_SEEING_EMAIL'],
            'nobody' => $_CORELANG['TXT_ACCESS_NOBODY_ALLOWED_SEEING_EMAIL']
        );

        $emailDropdown = new \Cx\Core\Html\Model\Entity\DataElement(
            'emailAccess',
            $fieldvalue,
            'select',
            null,
            $validValuesEmail
        );

        $validValuesProfile = array(
            'everyone' => $_CORELANG['TXT_ACCESS_EVERYONE_ALLOWED_SEEING_PROFILE'],
            'members_only' => $_CORELANG['TXT_ACCESS_MEMBERS_ONLY_ALLOWED_SEEING_PROFILE'],
            'nobody' => $_CORELANG['TXT_ACCESS_NOBODY_ALLOWED_SEEING_PROFILE']
        );

        $profileDropdown = new \Cx\Core\Html\Model\Entity\DataElement(
            $fieldname,
            $fieldvalue,
            'select',
            null,
            $validValuesProfile
        );

        $wrapper->addChildren(array($emailDropdown, $profileDropdown));

        return $wrapper;
    }

    /**
     * Generate the language dropdown
     *
     * @param $fieldname string  Name of the field
     * @param $fieldvalue string Value of the field
     * @return \Cx\Core\Html\Model\Entity\HtmlElement
     */
    protected function getLangDropdown($fieldname, $fieldvalue)
    {
        $wrapper = new \Cx\Core\Html\Model\Entity\HtmlElement('div');

        $frontendLang = array();

        $arrLangs = \FWLanguage::getActiveFrontendLanguages();

        $frontendLang[0] = 'Default';

        foreach ($arrLangs as $lang){
            $frontendLang[$lang['id']] = $lang['name'];
        }

        $frontendLangDropdown = new \Cx\Core\Html\Model\Entity\DataElement(
            $fieldname,
            $fieldvalue,
            'select',
            null,
            $frontendLang
        );

        /*generate backend languages dropdown*/
        $backendLang = array();

        $arrLangs = \FWLanguage::getActiveFrontendLanguages();

        $backendLang[0] = 'Default';

        foreach ($arrLangs as $lang){
            $backendLang[$lang['id']] = $lang['name'];
        }

        $backendLangDropdown = new \Cx\Core\Html\Model\Entity\DataElement(
            'backendLangId',
            $fieldvalue,
            'select',
            null,
            $backendLang
        );

        $wrapper->addChildren(array($frontendLangDropdown, $backendLangDropdown));

        return $wrapper;
    }

    /**
     * Returns the password information string
     *
     * The string returned depends on the password complexity setting
     * @return  string          The password complexity information
     */
    protected function getPasswordInfo()
    {
        global $_CONFIG, $_ARRAYLANG;

        if (isset($_CONFIG['passwordComplexity'])
            && $_CONFIG['passwordComplexity'] == 'on') {
            return $_ARRAYLANG['TXT_CORE_USER_PASSWORD_MINIMAL_CHARACTERS_WITH_COMPLEXITY'];
        }
        return $_ARRAYLANG['TXT_CORE_USER_PASSWORD_MINIMAL_CHARACTERS'];
    }
}