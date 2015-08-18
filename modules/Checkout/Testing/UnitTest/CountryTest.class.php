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

/** * CountryTest *  * @copyright   CONTREXX CMS - COMVATION AG * @author      Comvation Development Team <info@comvation.com> * @author      SS4U <ss4u.comvation@gmail.com> * @version     1.0.0 * @package     contrexx * @subpackage  module_checkout */namespace Cx\Modules\Checkout\Testing\UnitTest;use Cx\Modules\Checkout\Controller\Countries;/** * CountryTest *  * @copyright   CONTREXX CMS - COMVATION AG * @author      Comvation Development Team <info@comvation.com> * @author      SS4U <ss4u.comvation@gmail.com> * @version     1.0.0 * @package     contrexx * @subpackage  module_checkout */class CountryTest extends \Cx\Core\Test\Model\Entity\DoctrineTestCase {    public function testGetAll() {        $objCountries = new Countries(\Env::get('db'));        $this->assertNotEmpty($objCountries->getAll());    }}