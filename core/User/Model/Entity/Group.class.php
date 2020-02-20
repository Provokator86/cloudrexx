<?php

/**
 * Cloudrexx
 *
 * @link      http://www.cloudrexx.com
 * @copyright Cloudrexx AG 2007-2015
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Cloudrexx" is a registered trademark of Cloudrexx AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */


namespace Cx\Core\User\Model\Entity;

/**
 * Cx\Core\User\Model\Entity\Group
 */
class Group extends \Cx\Model\Base\EntityBase {
    /**
     * @var integer $id
     */
    private $id;

    /**
     * @var string $name
     */
    private $name;

    /**
     * @var string $description
     */
    private $description;

    /**
     * @var integer $active
     */
    private $active;

    /**
     * @var string $type
     */
    private $type;

    /**
     * @var string $homepage
     */
    private $homepage;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $user;

    /**
     * @var Cx\Core_Modules\Access\Model\Entity\AccessId
     */
    private $accessId2;

    /**
     * @var Cx\Core_Modules\Access\Model\Entity\AccessId
     */
    private $accessId;

    /**
     * @var 
     */
    protected $toolbar;

    public function __construct()
    {
        $this->user = new \Doctrine\Common\Collections\ArrayCollection();
        $this->accessId2 = new \Doctrine\Common\Collections\ArrayCollection();
        $this->accessId = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get groupId
     *
     * @return integer $groupId
     * @deprecated
     * @see \Cx\Core\User\Model\Entity\Group::getId()
     */
    public function getGroupId()
    {
        return $this->getId();
    }

    /**
     * Get id
     *
     * @return integer group id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set groupName
     *
     * @param string $groupName
     * @deprecated
     * @see \Cx\Core\User\Model\Entity\Group::setName()
     */
    public function setGroupName($groupName)
    {
        $this->setName($groupName);
    }

    /**
     * Set name
     *
     * @param string $name group name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get groupName
     *
     * @return string $groupName
     * @deprecated
     * @see \Cx\Core\User\Model\Entity\Group::getName()
     */
    public function getGroupName()
    {
        return $this->getName();
    }

    /**
     * Get name
     *
     * @return string group name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set groupDescription
     *
     * @param string $groupDescription
     * @deprecated
     * @see \Cx\Core\User\Model\Entity\Group::setDescription()
     */
    public function setGroupDescription($groupDescription)
    {
        $this->setDescription($groupDescription);
    }

    /**
     * Set description
     *
     * @param string $description group description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Get groupDescription
     *
     * @return string $groupDescription
     * @deprecated
     * @see \Cx\Core\User\Model\Entity\Group::getDescription()
     */
    public function getGroupDescription()
    {
        return $this->getDescription();
    }

    /**
     * Get description
     *
     * @return string group description
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set isActive
     *
     * @param integer $isActive
     * @deprecated
     * @see \Cx\Core\User\Model\Entity\Group::setActive()
     */
    public function setIsActive($isActive)
    {
        $this->setActive($isActive);
    }

    /**
     * Set if group is active
     *
     * @param boolean $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * Get isActive
     *
     * @return integer $isActive
     * @deprecated
     * @see \Cx\Core\User\Model\Entity\Group::isActive()
     */
    public function getIsActive()
    {
        return $this->isActive();
    }

    /**
     * If group is active
     *
     * @return integer if group is active
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * Set type
     *
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Get type
     *
     * @return string $type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set homepage
     *
     * @param string $homepage
     */
    public function setHomepage($homepage)
    {
        $this->homepage = $homepage;
    }

    /**
     * Get homepage
     *
     * @return string $homepage
     */
    public function getHomepage()
    {
        return $this->homepage;
    }

    /**
     * Add user
     *
     * @param \Cx\Core\User\Model\Entity\User $user
     */
    public function addUser(\Cx\Core\User\Model\Entity\User $user)
    {
        $this->user[] = $user;
    }

    /**
     * Remove the User
     * 
     * @param \Cx\Core\User\Model\Entity\User $user
     */
    public function removeUser(\Cx\Core\User\Model\Entity\User $user) {
        $this->user->removeElement($user);
    }
    
    /**
     * Get user
     *
     * @return \Doctrine\Common\Collections\Collection $user
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Add accessId2
     *
     * @param Cx\Core_Modules\Access\Model\Entity\AccessId $accessId2
     */
    public function addAccessId2(\Cx\Core_Modules\Access\Model\Entity\AccessId $accessId2)
    {
        $this->accessId2[] = $accessId2;
    }

    /**
     * Get accessId2
     *
     * @return Doctrine\Common\Collections\Collection $accessId2
     */
    public function getAccessId2()
    {
        return $this->accessId2;
    }

    /**
     * Add accessId
     *
     * @param Cx\Core_Modules\Access\Model\Entity\AccessId $accessId
     */
    public function addAccessId(\Cx\Core_Modules\Access\Model\Entity\AccessId $accessId)
    {
        $this->accessId[] = $accessId;
    }

    /**
     * Get accessId
     *
     * @return Doctrine\Common\Collections\Collection $accessId
     */
    public function getAccessId()
    {
        return $this->accessId;
    }

    /**
     * Set toolbar
     *
     * @param string $toolbar
     */
    public function setToolbar($toolbar)
    {
        $this->toolbar = $toolbar;
    }

    /**
     * Get toolbar
     *
     * @return string $toolbar
     */
    public function getToolbar()
    {
        return $this->toolbar;
    }
}
